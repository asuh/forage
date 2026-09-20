import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import copy from './copy.js';

test('copied assets keep their URLs until their contents change, including rebuilds', (t) => {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'forage-copy-'));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  const source = path.join(root, 'image.svg');
  const dest = path.join(root, 'dist');
  const manifestPath = path.join(dest, 'manifest.json');
  fs.mkdirSync(dest);
  fs.writeFileSync(source, '<svg>original</svg>');

  const plugin = copy({ targets: [{ src: source }] });
  plugin.config({ build: {
    outDir: dest,
    manifest: 'manifest.json',
    rollupOptions: { output: { assetFileNames: '[hash].[ext]' } },
  } });

  const build = () => {
    fs.writeFileSync(manifestPath, '{}');
    plugin.buildStart();
    plugin.writeBundle();
    plugin.closeBundle();
    return JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
  };

  const first = build();
  assert.deepEqual(build(), first);
  assert.equal(fs.readFileSync(path.join(dest, first[source].file), 'utf8'), '<svg>original</svg>');

  fs.writeFileSync(source, '<svg>changed</svg>');
  const changed = build();
  assert.notEqual(changed[source].file, first[source].file);
  assert.equal(fs.readFileSync(path.join(dest, changed[source].file), 'utf8'), '<svg>changed</svg>');

  fs.unlinkSync(source);
  assert.deepEqual(build(), {});
});
