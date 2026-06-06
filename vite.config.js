import path from 'path';
import { defineConfig } from 'vite';
import copy from './.vite/copy';

const ROOT = path.resolve('../../../');
const BASE = import.meta.dirname.replace(ROOT, '');

export default defineConfig({
  base: process.env.NODE_ENV === 'production' ? `./` : BASE,

  build: {
    manifest: 'manifest.json',
    assetsDir: '.',
    assetsInlineLimit: 0,
    outDir: 'dist',
    emptyOutDir: true,
    cssMinify: 'lightningcss',
    rollupOptions: {
      input: [
        'resources/scripts/scripts.js',
        'resources/styles/styles.css',
        'resources/styles/admin.css',
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
  },

  server: {
    cors: true,
    host: true,
  },

  resolve: {
    alias: {
      '@': path.resolve(import.meta.dirname),
      '@scripts': path.resolve(import.meta.dirname, './resources/scripts'),
      '@styles': path.resolve(import.meta.dirname, './resources/styles'),
    },
  },

  logLevel: 'info',
});
