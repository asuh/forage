import assert from 'node:assert/strict';
import fs from 'node:fs';
import http from 'node:http';
import os from 'node:os';
import path from 'node:path';
import { once } from 'node:events';
import { gzipSync, brotliCompressSync, deflateSync } from 'node:zlib';
import test from 'node:test';
import { createServer } from 'vite';
import wordpressProxy from './wordpress-proxy.js';
import hot from './hot.js';

const BASE = '/wp-content/themes/forage/';

function get(url, options = {}, body) {
  return new Promise((resolve, reject) => {
    const req = http.request(url, options, (res) => {
      const chunks = [];
      res.on('data', (chunk) => chunks.push(chunk));
      res.on('end', () => resolve({
        status: res.statusCode,
        headers: res.headers,
        body: Buffer.concat(chunks),
        text: Buffer.concat(chunks).toString(),
      }));
      res.on('error', reject);
    });
    req.on('error', reject);
    req.end(body);
  });
}

async function fixture(t, handler, options = {}) {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'forage-proxy-'));
  fs.mkdirSync(path.join(root, 'resources'), { recursive: true });
  fs.writeFileSync(path.join(root, 'resources/main.js'), 'import "./style.css"; console.log("vite module");');
  fs.writeFileSync(path.join(root, 'resources/style.css'), 'body { color: red }');
  fs.writeFileSync(path.join(root, 'resources/secret.php'), '<?php echo "PHP SOURCE";');
  const upstream = http.createServer(handler);
  upstream.listen(0, '127.0.0.1');
  await once(upstream, 'listening');
  const target = `http://127.0.0.1:${upstream.address().port}`;
  let vite;
  t.after(async () => {
    await vite?.close();
    upstream.closeAllConnections();
    await new Promise((resolve) => upstream.close(resolve));
    fs.rmSync(root, { recursive: true, force: true });
  });
  vite = await createServer({
    configFile: false,
    root,
    base: BASE,
    plugins: [hot(), wordpressProxy({ target, ...options })],
    server: { host: '127.0.0.1', port: 0 },
    logLevel: 'silent',
  });
  await vite.listen();
  const origin = `http://127.0.0.1:${vite.httpServer.address().port}`;
  return { root, target, origin, vite, upstream };
}

test('routes WordPress before the base redirect while retaining Vite assets and host validation', async (t) => {
  let hits = 0;
  const f = await fixture(t, (req, res) => {
    hits++;
    res.setHeader('content-type', 'text/html');
    res.end(`WordPress: ${req.url}`);
  });
  for (const route of ['/', '/news/?page=2', '/wp-admin/', '/wp-login.php', '/wp-json/', '/wp-content/uploads/photo.jpg', `${BASE}functions.php`, `${BASE}resources/secret.php?raw`]) {
    const result = await get(f.origin + route);
    assert.equal(result.status, 200);
    assert.equal(result.text, `WordPress: ${route}`);
  }
  const previousHits = hits;
  const client = await get(`${f.origin}${BASE}@vite/client`);
  assert.equal(client.status, 200);
  assert.match(client.text, /new WebSocket/);
  const module = await get(`${f.origin}${BASE}resources/main.js`);
  assert.equal(module.status, 200);
  assert.match(module.text, /vite module/);
  const css = await get(`${f.origin}${BASE}resources/style.css`);
  assert.equal(css.status, 200);
  assert.match(css.text, /color: red/);
  assert.equal(hits, previousHits);
  const blocked = await get(f.origin, { headers: { host: 'untrusted.example' } });
  assert.equal(blocked.status, 403);
  assert.equal(hits, previousHits);
  const source = await get(`${f.origin}${BASE}@fs${f.root}/resources/secret.php?raw`);
  assert.equal(source.status, 403);
  assert.doesNotMatch(source.text, /PHP SOURCE/);
  const encoded = await get(`${f.origin}${BASE}resources/secret%2ephp?raw`);
  assert.match(encoded.text, /^WordPress:/);
  const ping = await get(f.origin, { headers: { accept: 'text/x-vite-ping' } });
  assert.equal(ping.status, 204);
  assert.equal((await get(`${f.origin}/%ZZ`)).status, 400);
});

test('rewrites exact origins in HTML, JSON, CSS and headers for each preview host', async (t) => {
  let f;
  f = await fixture(t, (req, res) => {
    const desktop = fs.readFileSync(path.join(f.root, '.vite/hot'), 'utf8');
    const text = [
      `${f.target}/page`, f.target.replace('http:', '') + '/relative',
      JSON.stringify({ url: `${f.target}/json` }).replaceAll('/', '\\/'),
      encodeURIComponent(`${f.target}/return`),
      `url(${f.target})`,
      `${desktop}${BASE}@vite/client`,
      `${f.target}.evil/page`, `${f.target}0/page`,
      'https://external.example/image.png',
    ].join('\n');
    res.writeHead(200, {
      'content-type': req.url === '/css' ? 'text/css' : req.url === '/json' ? 'application/json' : 'text/html; charset=UTF-8',
      'content-length': Buffer.byteLength(text),
      etag: '"upstream"',
      'last-modified': 'Wed, 01 Jan 2025 00:00:00 GMT',
      link: `<${f.target}/wp-json/>; rel="https://api.w.org/"`,
    });
    res.end(text);
  });
  for (const host of ['192.168.50.214:5173', '192.168.50.215:5174', '[::1]:5173']) {
    const preview = `http://${host}`;
    for (const route of ['/', '/css', '/json']) {
      const result = await get(f.origin + route, { headers: { host, 'x-forwarded-host': 'attacker.example', 'x-forwarded-proto': 'https' } });
      assert.equal(result.status, 200);
      assert.ok(result.text.includes(`${preview}/page`));
      assert.ok(result.text.includes(`//${host}/relative`));
      assert.ok(result.text.includes(JSON.stringify({ url: `${preview}/json` }).replaceAll('/', '\\/')));
      assert.ok(result.text.includes(encodeURIComponent(`${preview}/return`)));
      assert.ok(result.text.includes(`url(${preview})`));
      assert.ok(result.text.includes(`${preview}${BASE}@vite/client`));
      assert.ok(result.text.includes(`${f.target}.evil/page`));
      assert.ok(result.text.includes(`${f.target}0/page`));
      assert.ok(result.text.includes('https://external.example/image.png'));
      assert.equal(result.headers.link, `<${preview}/wp-json/>; rel="https://api.w.org/"`);
      assert.equal(Number(result.headers['content-length']), result.body.length);
      assert.equal(result.headers.etag, undefined);
      assert.equal(result.headers['last-modified'], undefined);
      assert.equal(result.headers['cache-control'], 'no-store');
    }
  }
});

test('rewrites redirects and cookie domains while retaining cookie security attributes', async (t) => {
  let f;
  f = await fixture(t, (_req, res) => {
    res.writeHead(302, {
      location: `${f.target}/wp-login.php?redirect_to=${encodeURIComponent(`${f.target}/wp-admin/`)}`,
      'set-cookie': [
        'wordpress=session; Domain=.127.0.0.1; Path=/; HttpOnly; Secure; SameSite=Lax',
        'hostonly=yes; Path=/',
        'other=yes; Domain=external.example; Path=/',
      ],
    });
    res.end();
  });
  const result = await get(f.origin);
  assert.equal(result.status, 302);
  assert.equal(result.headers.location, `${f.origin}/wp-login.php?redirect_to=${encodeURIComponent(`${f.origin}/wp-admin/`)}`);
  assert.deepEqual(result.headers['set-cookie'], [
    'wordpress=session; Path=/; HttpOnly; Secure; SameSite=Lax',
    'hostonly=yes; Path=/',
    'other=yes; Domain=external.example; Path=/',
  ]);
});

test('forwards methods, queries, bodies and cookies with the upstream host and origin', async (t) => {
  let received;
  const f = await fixture(t, async (req, res) => {
    const chunks = [];
    for await (const chunk of req) chunks.push(chunk);
    received = { method: req.method, url: req.url, headers: req.headers, body: Buffer.concat(chunks) };
    res.end('ok');
  });
  const body = Buffer.from('action=save&value=%E2%9C%93');
  const result = await get(`${f.origin}/wp-admin/admin-ajax.php?test=1`, {
    method: 'POST',
    headers: {
      'content-type': 'application/x-www-form-urlencoded',
      'content-length': body.length,
      cookie: 'wordpress=session',
      origin: f.origin,
      referer: `${f.origin}/wp-admin/`,
      'x-wp-nonce': 'nonce',
      'x-forwarded-host': 'untrusted.example',
      'if-none-match': '"stale"',
      'accept-encoding': 'gzip, br',
    },
  }, body);
  assert.equal(result.status, 200);
  assert.equal(received.method, 'POST');
  assert.equal(received.url, '/wp-admin/admin-ajax.php?test=1');
  assert.deepEqual(received.body, body);
  assert.equal(received.headers.host, new URL(f.target).host);
  assert.equal(received.headers.origin, f.target);
  assert.equal(received.headers.referer, `${f.target}/wp-admin/`);
  assert.equal(received.headers.cookie, 'wordpress=session');
  assert.equal(received.headers['x-wp-nonce'], 'nonce');
  assert.equal(received.headers['x-forwarded-host'], undefined);
  assert.equal(received.headers['if-none-match'], undefined);
  assert.equal(received.headers['accept-encoding'], 'identity');
});

test('decodes compressed text, updates its length, and streams binary data unchanged', async (t) => {
  let f;
  const binary = Buffer.from([0, 255, 128, 12, 11, 0]);
  f = await fixture(t, (req, res) => {
    if (req.url === '/binary') {
      res.writeHead(200, { 'content-type': 'image/png', 'content-length': binary.length });
      return res.end(binary);
    }
    const encoding = req.url.slice(1);
    const compress = { gzip: gzipSync, br: brotliCompressSync, deflate: deflateSync }[encoding];
    const body = compress(Buffer.from(`<a href="${f.target}/">✓</a>`));
    res.writeHead(200, { 'content-type': 'text/html', 'content-encoding': encoding, 'content-length': body.length });
    res.end(body);
  });
  for (const encoding of ['gzip', 'br', 'deflate']) {
    const result = await get(`${f.origin}/${encoding}`);
    assert.equal(result.text, `<a href="${f.origin}/">✓</a>`);
    assert.equal(result.headers['content-encoding'], undefined);
    assert.equal(Number(result.headers['content-length']), result.body.length);
    const head = await get(`${f.origin}/${encoding}`, { method: 'HEAD' });
    assert.equal(head.status, 200);
    assert.equal(head.body.length, 0);
  }
  assert.deepEqual((await get(`${f.origin}/binary`)).body, binary);
});

test('reports refused connections, timeouts and corrupt compression without stopping Vite', async (t) => {
  const f = await fixture(t, (req, res) => {
    if (req.url === '/timeout') return;
    res.writeHead(200, { 'content-type': 'text/html', 'content-encoding': 'gzip' });
    res.end('invalid gzip');
  }, { timeout: 100 });
  assert.equal((await get(`${f.origin}/timeout`)).status, 502);
  assert.equal((await get(`${f.origin}/corrupt`)).status, 502);
  f.upstream.closeAllConnections();
  await new Promise((resolve) => f.upstream.close(resolve));
  const result = await get(f.origin);
  assert.equal(result.status, 502);
  assert.match(result.text, /Check that the site is running/);
  assert.equal((await get(`${f.origin}${BASE}@vite/client`)).status, 200);
});

test('preserves partial responses, downloads, and non-UTF-8 bodies', async (t) => {
  let f;
  f = await fixture(t, (req, res) => {
    const body = Buffer.from(`${f.target}/asset`);
    const partial = req.url === '/range';
    res.writeHead(partial ? 206 : 200, {
      'content-type': req.url === '/latin' ? 'text/plain; charset=iso-8859-1' : 'text/plain',
      'content-length': body.length,
      ...(partial ? { 'content-range': `bytes 0-${body.length - 1}/${body.length}` } : {}),
      ...(req.url === '/download' ? { 'content-disposition': 'attachment; filename="export.txt"' } : {}),
    });
    res.end(body);
  });
  for (const route of ['/range', '/latin', '/download']) {
    const result = await get(f.origin + route);
    assert.equal(result.status, route === '/range' ? 206 : 200);
    assert.equal(result.text, `${f.target}/asset`);
    assert.equal(Number(result.headers['content-length']), result.body.length);
  }
});

test('keeps the HMR WebSocket on the preview address and delivers CSS updates and reloads', { timeout: 15_000 }, async (t) => {
  const f = await fixture(t, (_req, res) => res.end('WordPress'));
  const client = await get(`${f.origin}${BASE}@vite/client`);
  assert.match(client.text, /importMetaUrl\.hostname/);
  assert.match(client.text, /importMetaUrl\.port/);
  await get(`${f.origin}${BASE}resources/main.js`);
  await get(`${f.origin}${BASE}resources/style.css`);
  const socket = new WebSocket(`${f.origin.replace('http:', 'ws:')}${BASE}?token=${f.vite.config.webSocketToken}`, 'vite-hmr');
  t.after(() => socket.close());
  function message(type) {
    return new Promise((resolve) => {
      const listener = (event) => {
        const data = JSON.parse(event.data);
        if (data.type === type) {
          socket.removeEventListener('message', listener);
          resolve(data);
        }
      };
      socket.addEventListener('message', listener);
    });
  }
  const connected = message('connected');
  await connected;
  const update = message('update');
  fs.writeFileSync(path.join(f.root, 'resources/style.css'), 'body { color: blue }');
  const event = await update;
  assert.ok(event.updates.some((entry) => entry.path.endsWith('/resources/style.css')));
  const reload = message('full-reload');
  f.vite.ws.send({ type: 'full-reload' });
  assert.equal((await reload).type, 'full-reload');
  socket.close();
});

test('is disabled without a target and rejects non-origin upstream settings', async () => {
  assert.equal(wordpressProxy(), null);
  for (const target of ['ftp://test.local', 'http://user:pass@test.local', 'http://test.local/subdir', 'http://test.local/?a=1']) {
    const plugin = wordpressProxy({ target });
    assert.throws(() => plugin.configResolved(), /HTTP\(S\) origin/);
  }
});
