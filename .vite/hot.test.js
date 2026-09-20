import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { createServer } from 'vite';
import hot from './hot.js';

test('Vite publishes its actual port and removes the hot file on close', async (t) => {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'forage-hot-'));
  const server = await createServer({
    root,
    configFile: false,
    plugins: [hot()],
    server: { host: '127.0.0.1', port: 0 },
    logLevel: 'silent',
  });
  t.after(async () => {
    await server.close();
    fs.rmSync(root, { recursive: true, force: true });
  });
  const hotFile = path.join(root, '.vite/hot');
  assert.equal(fs.existsSync(hotFile), false);
  await server.listen();
  assert.equal(fs.readFileSync(hotFile, 'utf8'), `http://127.0.0.1:${server.httpServer.address().port}`);
  await server.close();
  assert.equal(fs.existsSync(hotFile), false);
});
