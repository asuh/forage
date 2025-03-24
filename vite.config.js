import path from 'path';
import { defineConfig } from 'vite';
import copy from './.vite/copy';

const ROOT = path.resolve('../../../');
const BASE = __dirname.replace(ROOT, '');

export default defineConfig({
  base: process.env.NODE_ENV === 'production' ? `${BASE}/dist/` : BASE,

  build: {
    manifest: 'manifest.json',
    assetsDir: '.',
    assetsInlineLimit: 0,
    outDir: 'dist',
    emptyOutDir: true,
    // cssMinify: 'lightningcss',
    cssTarget: 'chrome125', // Chrome 125 is arbitrary, just picking a modern browser
    rollupOptions: {
      input: [
        'resources/scripts/scripts.js',
        'resources/styles/styles.css',
        'resources/scripts/blocks.js'
      ],
      output: {
        entryFileNames: '[hash].js',
        assetFileNames: '[hash].[ext]',
        chunkFileNames: '[hash].js',
      },
    },
  },
  plugins: [
    copy({
      targets: [
        {
          src: 'resources/images/**/*.{png,jpg,jpeg,svg,webp,avif}',
        },
      ],
    }),
    {
      name: 'php',
      handleHotUpdate({ file, server }) {
        if (file.endsWith('.php')) {
          server.ws.send({ type: 'full-reload' });
        }
      },
    },
  ],
  css: {
    devSourcemap: true
    // transformer: 'lightningcss',
  },
  server: {
    cors: true
  }
});
