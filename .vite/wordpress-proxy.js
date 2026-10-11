import http from 'node:http';
import https from 'node:https';
import { Writable } from 'node:stream';
import { pipeline } from 'node:stream/promises';
import { createBrotliDecompress, createGunzip, createInflate } from 'node:zlib';

const MAX_TEXT_BYTES = 16 * 1024 * 1024;
const HOP_HEADERS = [
  'connection', 'keep-alive', 'proxy-authenticate', 'proxy-authorization',
  'te', 'trailer', 'transfer-encoding', 'upgrade',
];
const URL_HEADERS = [
  'location', 'content-location', 'link', 'refresh',
  'access-control-allow-origin', 'content-security-policy',
  'content-security-policy-report-only',
];

function endError(res, status, message) {
  if (res.destroyed || res.writableEnded) return;
  if (res.headersSent) return res.destroy();
  res.writeHead(status, { 'content-type': 'text/plain; charset=utf-8' });
  res.end(message);
}

function cleanHeaders(original) {
  const headers = { ...original };
  const connection = String(headers.connection || '').toLowerCase().split(',');
  for (const name of [...HOP_HEADERS, ...connection]) delete headers[name.trim()];
  return headers;
}

function escapeRegex(value) {
  return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

// Exact authorities only: never replace test.local.evil, test.local:1234,
// credentials, or a different scheme just because it contains //test.local.
function originRewriter(origins, destination) {
  const replacements = [];
  for (const origin of new Set(origins)) {
    for (const [source, target] of [
      [origin, destination],
      [origin.replace(/^https?:/, ''), destination.replace(/^https?:/, '')],
    ]) {
      for (const encode of [
        (value) => value,
        (value) => value.replaceAll('/', '\\/'),
        encodeURIComponent,
      ]) {
        const from = encode(source);
        const boundary = encode === encodeURIComponent
          ? '(?=$|%2[fF]|%3[fF]|%23|[&\\s\"\'<>])'
          : '(?=$|[/?#\\s\"\'<>\\\\)\\]};,])';
        const prefix = source.startsWith('//') ? '(?<![:/\\\\\\w])' : '';
        replacements.push([new RegExp(prefix + escapeRegex(from) + boundary, 'g'), encode(target)]);
      }
    }
  }
  return (value) => replacements.reduce(
    (text, [pattern, replacement]) => text.replace(pattern, () => replacement),
    value,
  );
}

function isViteRequest(pathname, base) {
  const local = pathname.startsWith(base) ? pathname.slice(base.length) : pathname.slice(1);
  if (/^(?:@vite\/|@id\/|@fs\/|__vite\/)/.test(local)) return true;
  return pathname.startsWith(base)
    && /^(?:resources\/|node_modules\/)/.test(local)
    && !/\.(?:php\d*|phtml|phar)(?:\/|$)/i.test(local);
}

function isText(headers) {
  const type = String(headers['content-type'] || '');
  return /^(?:text\/(?:html|css|javascript|plain|xml)|application\/(?:[\w.-]+\+)?(?:json|xml)|application\/(?:javascript|x-javascript)|image\/svg\+xml)(?:;|$)/i.test(type)
    && !/charset\s*=\s*["']?(?!utf-8\b|us-ascii\b)[\w-]+/i.test(type)
    && !headers['content-disposition']?.toLowerCase().includes('attachment');
}

async function readText(response) {
  const decoders = { gzip: createGunzip, deflate: createInflate, br: createBrotliDecompress };
  const encoding = String(response.headers['content-encoding'] || 'identity').toLowerCase();
  if (encoding !== 'identity' && !decoders[encoding]) {
    throw new Error(`Unsupported upstream content encoding: ${encoding}`);
  }
  const chunks = [];
  let length = 0;
  const sink = new Writable({
    write(chunk, _encoding, callback) {
      length += chunk.length;
      if (length > MAX_TEXT_BYTES) return callback(new Error('WordPress text response exceeds 16 MiB'));
      chunks.push(chunk);
      callback();
    },
  });
  await pipeline(response, ...(decoders[encoding] ? [decoders[encoding]()] : []), sink);
  return Buffer.concat(chunks).toString('utf8');
}

function rewriteCookie(cookie, target) {
  return cookie.replace(/;\s*domain=([^;]+)/ig, (attribute, domain) => {
    const value = domain.trim().replace(/^"|"$/g, '').replace(/^\./, '').toLowerCase();
    const host = target.hostname.toLowerCase();
    // A host-only cookie works on any preview IP. Preserve Secure/HttpOnly/SameSite.
    return host === value || host.endsWith(`.${value}`) ? '' : attribute;
  });
}

function previewOrigin(req, secure) {
  const host = req.headers.host;
  if (!host || /[\s,/@#?\\]/.test(host)) throw new Error('Invalid Host header');
  const url = new URL(`${secure ? 'https' : 'http'}://${host}`);
  if (url.host.toLowerCase() !== host.toLowerCase()) throw new Error('Invalid Host header');
  return url.origin;
}

function viteOrigin(server) {
  const { host, https: secure, origin } = server.config.server;
  if (origin) return new URL(origin).origin;
  let hostname = typeof host === 'string' && !['0.0.0.0', '::'].includes(host) ? host : 'localhost';
  if (hostname.includes(':') && !hostname.startsWith('[')) hostname = `[${hostname}]`;
  return `${secure ? 'https' : 'http'}://${hostname}:${server.httpServer.address().port}`;
}

export default function wordpressProxy({ target, timeout = 30_000 } = {}) {
  if (!target) return null;
  let upstream;

  return {
    name: 'forage:wordpress-proxy',
    apply: 'serve',
    configResolved() {
      upstream = new URL(target);
      if (!['http:', 'https:'].includes(upstream.protocol)
        || upstream.username || upstream.password || upstream.pathname !== '/'
        || upstream.search || upstream.hash) {
        throw new Error('WP_DEV_PROXY_TARGET must be an HTTP(S) origin, e.g. http://test.local');
      }
    },
    configureServer(server) {
      const printUrls = server.printUrls.bind(server);
      server.printUrls = () => {
        printUrls();
        const urls = [...(server.resolvedUrls?.local || []), ...(server.resolvedUrls?.network || [])];
        for (const origin of new Set(urls.map((url) => new URL(url).origin))) {
          server.config.logger.info(`  ➜  WordPress preview: ${origin}/`);
        }
      };

      // Vite's host validation runs before this hook; its base-path redirect and
      // static serving run afterwards. Leave WebSocket upgrades with Vite.
      server.middlewares.use((req, res, next) => {
        let pathname;
        let preview;
        try {
          if (!req.url.startsWith('/') || req.url.startsWith('//')) throw new Error('Invalid path');
          pathname = decodeURIComponent(new URL(req.url, 'http://localhost').pathname);
          preview = previewOrigin(req, Boolean(server.config.server.https));
        } catch {
          return endError(res, 400, 'Invalid preview request.');
        }

        if (isViteRequest(pathname, server.config.base)) {
          // Never let Vite serve PHP source via @fs, ?raw, or encoded paths.
          if (/\.(?:php\d*|phtml|phar)(?:\/|$)/i.test(pathname)) {
            return endError(res, 403, 'PHP requests must be served by WordPress.');
          }
          return next();
        }
        if (req.headers.accept === 'text/x-vite-ping') return res.writeHead(204).end();

        const rewrite = originRewriter([upstream.origin, viteOrigin(server)], preview);
        const toUpstream = originRewriter([preview], upstream.origin);
        const headers = cleanHeaders(req.headers);
        headers.host = upstream.host;
        headers['accept-encoding'] = 'identity';
        // A cached 304 has no body to rewrite, and preview origins can change.
        delete headers['if-none-match'];
        delete headers['if-modified-since'];
        for (const name of Object.keys(headers)) {
          if (name === 'forwarded' || name.startsWith('x-forwarded-')) delete headers[name];
        }
        for (const name of ['origin', 'referer']) {
          if (headers[name]) headers[name] = toUpstream(headers[name]);
        }

        let response;
        const transport = upstream.protocol === 'https:' ? https : http;
        const request = transport.request(upstream, {
          method: req.method,
          path: req.url,
          headers,
        });
        const fail = (error) => {
          server.config.logger.error(`[wordpress-proxy] ${upstream.origin}: ${error.message}`);
          endError(res, 502, 'WordPress preview could not reach Local. Check that the site is running and WP_DEV_PROXY_TARGET is correct.');
        };
        request.setTimeout(timeout, () => request.destroy(new Error('Upstream request timed out')));
        request.on('error', fail);
        req.on('aborted', () => request.destroy());
        req.on('error', () => request.destroy());
        res.on('close', () => {
          if (!res.writableEnded) {
            request.destroy();
            response?.destroy();
          }
        });
        request.on('response', async (incoming) => {
          response = incoming;
          try {
            const outgoing = cleanHeaders(incoming.headers);
            for (const name of URL_HEADERS) {
              if (typeof outgoing[name] === 'string') outgoing[name] = rewrite(outgoing[name]);
            }
            if (outgoing['set-cookie']) {
              outgoing['set-cookie'] = outgoing['set-cookie'].map((cookie) => rewriteCookie(cookie, upstream));
            }
            outgoing['cache-control'] = 'no-store';
            const transform = isText(outgoing) && incoming.statusCode !== 206;
            if (transform) {
              for (const name of ['content-length', 'content-encoding', 'etag', 'last-modified', 'content-md5', 'digest', 'content-digest', 'repr-digest', 'accept-ranges']) {
                delete outgoing[name];
              }
            }
            if (transform && req.method !== 'HEAD' && ![204, 304].includes(incoming.statusCode)) {
              const body = Buffer.from(rewrite(await readText(incoming)));
              outgoing['content-length'] = body.length;
              res.writeHead(incoming.statusCode, outgoing);
              res.end(body);
            } else {
              res.writeHead(incoming.statusCode, outgoing);
              await pipeline(incoming, res);
            }
          } catch (error) {
            incoming.destroy();
            fail(error);
          }
        });
        req.pipe(request);
      });
    },
  };
}
