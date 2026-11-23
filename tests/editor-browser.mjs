import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { existsSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { createServer } from 'node:net';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';

const root = path.resolve(import.meta.dirname, '..');
for (const file of ['.env.development.local', '.env.local', '.env.development', '.env']) {
  if (existsSync(path.join(root, file))) process.loadEnvFile(path.join(root, file));
}

const live = process.argv.includes('--live');
const env = name => process.env[name]?.trim() || '';
const site = (env('WP_TEST_URL') || env('WP_DEV_PROXY_TARGET')).replace(/\/$/, '');
const user = env('WP_TEST_USER');
const password = process.env.WP_TEST_PASSWORD || '';
const post = env('WP_TEST_POST');
const widths = (env('WP_TEST_WIDTHS') || '680,1300').split(',').map(Number);
const firefoxPath = env('FIREFOX_PATH') ||
  (process.platform === 'darwin' ? '/Applications/Firefox.app/Contents/MacOS/firefox' : 'firefox');

const missing = [
  ['WP_TEST_URL', site],
  ['WP_TEST_USER', user],
  ['WP_TEST_PASSWORD', password],
  ['WP_TEST_POST', post],
].filter(([, value]) => !value).map(([name]) => name);
if (missing.length) {
  console.error(`Set ${missing.join(', ')} in the environment or .env.development.local. See docs/editor.md.`);
  process.exit(1);
}
assert.ok(widths.every(width => width >= 320), 'WP_TEST_WIDTHS must be a comma-separated list of pixel widths.');

const tolerance = 0.5;
const canvasSelector = 'iframe[name="editor-canvas"]';

const browser = await startFirefox();
try {
  await browser.command('session.new', { capabilities: { alwaysMatch: { acceptInsecureCerts: true } } });
  const { contexts } = await browser.command('browsingContext.getTree', {});
  const editor = contexts[0].context;

  await logIn(browser, editor);
  await browser.navigate(editor, `${site}/wp-admin/post.php?post=${encodeURIComponent(post)}&action=edit`);
  await waitFor('the editor canvas', () => browser.call(editor, canvasSelector => {
    const canvas = document.querySelector(canvasSelector)?.contentDocument;
    return Boolean(canvas?.querySelector('.editor-post-title__input') &&
      canvas.querySelector('.is-root-container > *'));
  }, canvasSelector), 30000);

  const liveScript = await browser.call(editor, () => Boolean(document.querySelector('script#forage-editor-live-css-js')));
  if (live) {
    assert.ok(liveScript, 'WordPress did not load the live CSS script. Start Vite with `npm start` and reload.');
    await checkLiveCss(browser, editor);
  } else {
    assert.ok(!liveScript, 'Vite is running. Stop it and run `npm run build`, or pass --live.');
    await checkProduction(browser, editor);
  }
} finally {
  await browser.close();
}

async function checkProduction(browser, editor) {
  const permalink = await browser.call(editor, () => window.wp.data.select('core/editor').getPermalink());
  assert.ok(permalink, 'The post has no published URL. Publish it before running this test.');
  const published = (await browser.command('browsingContext.create', { type: 'tab' })).context;
  await browser.navigate(published, permalink);

  const smokeTest = readFileSync(path.join(root, 'tests/editor-canvas.js'), 'utf8').trim().replace(/;$/, '');
  const differences = [];
  const compared = new Set();

  for (const width of widths) {
    await browser.command('browsingContext.setViewport', { context: editor, viewport: { width, height: 900 } });
    await delay(300);
    const smoke = await browser.evaluate(editor, `JSON.stringify(${smokeTest})`);
    console.log(`Editor smoke test at ${width}px`, JSON.parse(smoke));

    const canvasWidth = await browser.call(editor, canvasSelector =>
      document.querySelector(canvasSelector).contentWindow.innerWidth, canvasSelector);
    await browser.command('browsingContext.setViewport', { context: published, viewport: { width: canvasWidth, height: 900 } });
    await delay(300);

    const editorStyles = await browser.call(editor, collectStyles, canvasSelector);
    const publishedStyles = await browser.call(published, collectStyles, null);
    differences.push(...compare(publishedStyles, editorStyles, `${canvasWidth}px canvas`, compared));
  }

  if (differences.length) {
    assert.fail(`The editor differs from the published page:\n  ${differences.join('\n  ')}`);
  }
  assert.ok(compared.size, 'The published page and editor had no matching elements to compare.');
  console.log(`Editor matches the published page at ${widths.join(', ')}px browser widths. Compared: ${[...compared].join(', ')}.`);
}

async function checkLiveCss(browser, editor) {
  const title = 'Unsaved live CSS check';
  await browser.call(editor, (canvasSelector, title) => {
    window.__forageCanvas = document.querySelector(canvasSelector).contentDocument;
    window.wp.data.dispatch('core/editor').updateEditorSettings({ autosaveInterval: 86400 });
    window.wp.data.dispatch('core/editor').editPost({ title });
  }, canvasSelector, title);

  const state = () => browser.call(editor, canvasSelector => {
    const api = window.wp.data.select('core/editor');
    const canvas = document.querySelector(canvasSelector).contentDocument;
    const heading = canvas.querySelector('.editor-post-title__input');
    return {
      spacing: canvas.defaultView.getComputedStyle(heading).letterSpacing,
      sameCanvas: canvas === window.__forageCanvas,
      title: api.getEditedPostAttribute('title'),
      dirty: api.isEditedPostDirty(),
    };
  }, canvasSelector);
  const expectSpacing = async spacing => {
    await waitFor(`title letter spacing ${spacing}`, async () => {
      const current = await state();
      assert.equal(current.sameCanvas, true, 'The editor canvas reloaded.');
      assert.equal(current.title, title, 'The unsaved title changed.');
      assert.equal(current.dirty, true, 'The editor lost its unsaved changes.');
      return current.spacing === spacing;
    }, 15000);
  };

  const source = path.join(root, 'resources/styles/common/content.css');
  const original = readFileSync(source, 'utf8');
  const baseline = (await state()).spacing;
  const restore = () => writeFileSync(source, original);
  const interrupt = () => {
    restore();
    process.exit(130);
  };
  process.once('SIGINT', interrupt);
  process.once('SIGTERM', interrupt);
  try {
    writeFileSync(source, `${original}\n.page-title { letter-spacing: 3px; }\n`);
    await expectSpacing('3px');
    restore();
    await expectSpacing(baseline);
  } finally {
    restore();
    process.off('SIGINT', interrupt);
    process.off('SIGTERM', interrupt);
  }
  console.log('Live CSS applied and removed a shared rule without reloading the editor or losing unsaved edits.');
}

function collectStyles(canvasSelector) {
  const view = canvasSelector ? document.querySelector(canvasSelector).contentWindow : window;
  const doc = view.document;
  const article = doc.querySelector(canvasSelector ? '.block-editor-block-list__layout.is-root-container' : '.main-content');
  const title = doc.querySelector(canvasSelector ? '.editor-post-title__input' : '.page-title');
  const text = ['font-family', 'font-size', 'font-weight', 'font-style', 'line-height', 'letter-spacing', 'color'];
  const pick = (element, properties) => {
    if (!element) return null;
    const style = view.getComputedStyle(element);
    return Object.fromEntries(properties.map(property => [property, style.getPropertyValue(property)]));
  };
  const child = selector => article?.querySelector(`:scope > ${selector}`);

  const unwrap = element => element.tagName === 'DIV' && element.children.length === 1 &&
    ![...element.childNodes].some(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim())
    ? element.firstElementChild
    : element;
  const blocks = [...(article?.children ?? [])]
    .filter(element => canvasSelector ? element.hasAttribute('data-block') : !['SCRIPT', 'STYLE', 'TEMPLATE'].includes(element.tagName))
    .slice(0, 20)
    .map(unwrap);
  const layout = blocks.map((element, index) => {
    const box = element.getBoundingClientRect();
    const next = blocks[index + 1]?.getBoundingClientRect();
    return {
      block: element.tagName.toLowerCase(),
      width: `${box.width}px`,
      ...(next && { 'gap after': `${next.top - box.bottom}px` }),
    };
  });

  return {
    title: pick(title, [...text, 'text-wrap-style']),
    paragraph: pick(child('p'), text),
    heading: pick(child(':is(h2, h3, h4)'), text),
    list: pick(child(':is(ul, ol)'), [...text, 'padding-inline-start', 'list-style-type']),
    quote: pick(child('blockquote'), [...text, 'padding-inline-start', 'padding-inline-end',
      'border-inline-start-width', 'overflow-wrap', 'hyphens']),
    link: pick(article?.querySelector(':scope > p a'), ['color', 'font-weight', 'text-decoration-line', 'overflow-wrap']),
    layout,
  };
}

function compare(published, editor, label, compared) {
  const differences = [];
  const check = (name, expected, actual) => {
    compared.add(name.replace(/^block \d+ \((.+)\)$/, 'block layout'));
    for (const [property, value] of Object.entries(expected)) {
      if (!same(value, actual[property])) {
        differences.push(`${label} ${name} ${property}: published ${value}, editor ${actual[property]}`);
      }
    }
  };

  for (const [group, expected] of Object.entries(published)) {
    if (group !== 'layout' && expected && editor[group]) check(group, expected, editor[group]);
  }

  for (const [index, expected] of published.layout.entries()) {
    const actual = editor.layout[index];
    if (!actual || actual.block !== expected.block) break;
    check(`block ${index + 1} (${expected.block})`, expected, actual);
  }
  return differences;
}

function same(a, b) {
  const pixels = value => /^-?[\d.]+px$/.test(value) ? Number.parseFloat(value) : null;
  const [x, y] = [pixels(a), pixels(b)];
  return x !== null && y !== null ? Math.abs(x - y) <= tolerance : a === b;
}

async function logIn(browser, context) {
  await browser.navigate(context, `${site}/wp-login.php`);
  await browser.call(context, (user, password) => {
    document.getElementById('user_login').value = user;
    document.getElementById('user_pass').value = password;
    setTimeout(() => document.getElementById('loginform').submit());
  }, user, password);
  await waitFor('WordPress to log in', async () => {
    const page = await browser.call(context, () => ({
      admin: location.pathname.includes('/wp-admin'),
      error: document.getElementById('login_error')?.textContent.trim(),
    })).catch(() => ({}));
    if (page.error) throw new Error(`WordPress login failed: ${page.error}`);
    return page.admin;
  }, 20000);
}

async function waitFor(description, check, timeout) {
  const end = Date.now() + timeout;
  let lastError;
  while (Date.now() < end) {
    try {
      if (await check()) return;
      lastError = null;
    } catch (error) {
      if (error instanceof assert.AssertionError || /login failed/.test(error.message)) throw error;
      lastError = error;
    }
    await delay(250);
  }
  throw new Error(`Timed out waiting for ${description}${lastError ? `: ${lastError.message}` : ''}`);
}

async function startFirefox() {
  const port = await freePort();
  const profile = mkdtempSync(path.join(tmpdir(), 'forage-editor-test-'));
  writeFileSync(path.join(profile, 'user.js'), 'user_pref("layout.css.prefers-color-scheme.content-override", 1);\n');
  const firefox = spawn(firefoxPath, [
    '--headless', '--no-remote', '--profile', profile, '--remote-debugging-port', String(port), 'about:blank',
  ]);
  const cleanUp = async () => {
    if (firefox.exitCode === null && firefox.signalCode === null) {
      firefox.kill();
      await once(firefox, 'exit');
    }
    rmSync(profile, { recursive: true, force: true });
  };

  try {
    await new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Firefox did not start. Set FIREFOX_PATH.')), 20000);
      firefox.once('error', error => {
        clearTimeout(timer);
        reject(new Error(`Could not start Firefox at ${firefoxPath}. Set FIREFOX_PATH. ${error.message}`));
      });
      firefox.stderr.on('data', data => {
        if (String(data).includes('WebDriver BiDi listening')) {
          clearTimeout(timer);
          resolve();
        }
      });
    });
  } catch (error) {
    await cleanUp();
    throw error;
  }

  const socket = new WebSocket(`ws://127.0.0.1:${port}/session`);
  await once(socket, 'open');
  let id = 0;
  const command = (method, params) => new Promise((resolve, reject) => {
    const current = ++id;
    const timer = setTimeout(() => reject(new Error(`${method} timed out`)), 30000);
    const receive = ({ data }) => {
      const response = JSON.parse(data);
      if (response.id !== current) return;
      clearTimeout(timer);
      socket.removeEventListener('message', receive);
      if (response.type === 'error') reject(new Error(`${method}: ${response.error} ${response.message}`));
      else resolve(response.result);
    };
    socket.addEventListener('message', receive);
    socket.send(JSON.stringify({ id: current, method, params }));
  });
  const result = response => {
    if (response.type === 'exception') throw new Error(response.exceptionDetails.text);
    return response.result.value;
  };

  return {
    command,
    navigate: (context, url) => command('browsingContext.navigate', { context, url, wait: 'complete' }),
    evaluate: async (context, expression) =>
      result(await command('script.evaluate', { expression, target: { context }, awaitPromise: true })),
    call: async (context, fn, ...args) => JSON.parse(result(await command('script.callFunction', {
      functionDeclaration: `async (...args) => JSON.stringify((await (${fn})(...args.map(JSON.parse))) ?? null)`,
      arguments: args.map(value => ({ type: 'string', value: JSON.stringify(value ?? null) })),
      target: { context },
      awaitPromise: true,
    }))),
    async close() {
      socket.close();
      await cleanUp();
    },
  };
}

function freePort() {
  return new Promise((resolve, reject) => {
    const server = createServer();
    server.once('error', reject);
    server.listen(0, '127.0.0.1', () => {
      const { port } = server.address();
      server.close(() => resolve(port));
    });
  });
}
