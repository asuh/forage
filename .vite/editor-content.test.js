import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';
import { mapEditorContent } from './editor-content.js';

test('shares the authored block and title rules with the editor', () => {
  const source = new URL('../resources/styles/common/content.css', import.meta.url);
  const css = mapEditorContent(readFileSync(source), source.pathname);

  assert.match(css, /\.wp-block-quote\s*\{[^}]*hyphens: auto/);
  assert.match(css, /\.block-editor-block-list__layout\.is-root-container \.wp-block-quote\s*\{[^}]*overflow-wrap: anywhere/);
  assert.match(css, /\.editor-post-title__input\s*\{[^}]*line-height: 1\.15/);
  assert.doesNotMatch(css, /\.main-content|\.page-title/);
});
