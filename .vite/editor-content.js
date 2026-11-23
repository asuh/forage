import { readFileSync } from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import { normalizePath } from 'vite';

// Use the same CSS compiler Vite uses for the production build.
const { transform } = createRequire(import.meta.resolve('vite'))('lightningcss');

const editorClasses = {
  'main-content': ['block-editor-block-list__layout', 'is-root-container'],
  'page-title': ['editor-post-title__input'],
};

function mapSelector(selector) {
  return selector.flatMap(part => {
    if (part.type === 'class' && Object.hasOwn(editorClasses, part.name)) {
      return editorClasses[part.name].map(name => ({ ...part, name }));
    }
    if (part.selectors) return { ...part, selectors: part.selectors.map(mapSelector) };
    return part;
  });
}

export function mapEditorContent(code, from, to = from) {
  const assetBase = normalizePath(path.relative(path.dirname(to), path.dirname(from)));
  return transform({
    code: Buffer.from(code),
    filename: from,
    visitor: {
      Selector: mapSelector,
      Url(url) {
        if (!assetBase || /^(?:[a-z][a-z\d+.-]*:|[/#])/i.test(url.url)) return;
        return { ...url, url: `${assetBase}/${url.url}` };
      },
    },
  }).code.toString();
}

export default function editorContent() {
  let source;
  let entry;
  let serving;

  return {
    name: 'forage-editor-content',
    enforce: 'pre',
    configResolved({ root, command }) {
      serving = command === 'serve';
      source = path.resolve(root, 'resources/styles/common/content.css');
      entry = normalizePath(path.resolve(root, 'resources/styles/editor.css'));
    },
    transform(code, id) {
      if (id.split('?')[0] !== entry) return;
      this.addWatchFile(source);
      // Tag this stylesheet so WordPress's other theme and block CSS stays in place.
      const marker = serving ? '\n:root { --forage-editor-live-css: 1; }' : '';
      return `${code}\n${mapEditorContent(readFileSync(source), source, entry)}${marker}`;
    },
  };
}
