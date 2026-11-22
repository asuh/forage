import assert from 'node:assert/strict'
import { EventEmitter } from 'node:events'
import fs from 'node:fs'
import { tmpdir } from 'node:os'
import path from 'node:path'
import test from 'node:test'
import themeJson, { applyTokens, parseTokens } from './theme-json.js'

test('uses only an immediately preceding comment as a token name', () => {
  const tokens = parseTokens(`
    :root {
      /* Colors */

      --color-primary: #123456;
      /* Accent */
      --color-accent: #abcdef;
      /* Unused: --color-unused: #000; */
    }
  `)

  assert.deepEqual(tokens.palette, [
    {
      slug: 'primary',
      color: '#123456',
      name: 'Primary',
    },
    {
      slug: 'accent',
      color: '#abcdef',
      name: 'Accent',
    },
  ])
})

test('leaves fluid font-size behavior to theme.json', () => {
  const tokens = parseTokens(`
    :root {
      --font-size-body: 1rem;
    }
  `)

  assert.deepEqual(tokens.fontSizes, [
    {
      slug: 'body',
      size: '1rem',
      name: 'Body',
    },
  ])
})

test('writes presets as native theme.json arrays', () => {
  const tokens = parseTokens(`
    :root {
      --color-primary: #123456;
      --font-family-heading: "Example Sans", sans-serif;
      --font-size-large: 2rem;
      --space-40: 1rem;
    }
  `)
  const themeJson = applyTokens({ version: 3, settings: {} }, tokens)

  assert.deepEqual(themeJson.settings.color.palette, tokens.palette)
  assert.deepEqual(
    themeJson.settings.typography.fontFamilies,
    tokens.fontFamilies,
  )
  assert.deepEqual(themeJson.settings.typography.fontSizes, tokens.fontSizes)
  assert.deepEqual(themeJson.settings.spacing.spacingSizes, tokens.spacingSizes)
})

test('syncs responsive presets, widths, and style defaults while preserving WordPress options', () => {
  const theme = {
    version: 3,
    settings: { appearanceTools: true, layout: { allowEditing: false }, spacing: { defaultSpacingSizes: false } },
    styles: { blocks: { 'core/group': { color: { text: '#101010' } } } },
  }
  const css = `
    :root {
      --content-measure: 82ch;
      --wide-content-measure: 1280px;
      --default-block-gap: var(--space-50);
      --default-font-family: var(--font-family-body);
      --font-family-body: "Example Sans", sans-serif;
      /* Medium */
      --space-50: 1rem;
    }

    @media (min-width: 800px) {
      :root { --space-50: 1.25rem; }
    }
  `
  applyTokens(theme, parseTokens(css))

  assert.deepEqual(theme.settings.layout, { allowEditing: false, contentSize: '82ch', wideSize: '1280px' })
  assert.deepEqual(theme.settings.spacing, {
    defaultSpacingSizes: false,
    spacingSizes: [{ slug: '50', name: 'Medium', size: 'var(--space-50, 1rem)' }],
  })
  assert.equal(theme.styles.spacing.blockGap, 'var:preset|spacing|50')
  assert.equal(theme.styles.typography.fontFamily, 'var:preset|font-family|body')

  applyTokens(theme, parseTokens(css.replaceAll('space-50', 'space-60')))
  assert.deepEqual(theme.settings.spacing.spacingSizes.map(({ slug }) => slug), ['60'])
  assert.equal(theme.styles.spacing.blockGap, 'var:preset|spacing|60')

  applyTokens(theme, parseTokens(''))
  assert.deepEqual(theme.settings.layout, { allowEditing: false })
  assert.deepEqual(theme.settings.spacing, { defaultSpacingSizes: false })
  assert.equal(theme.settings.appearanceTools, true)
  assert.equal(theme.styles.spacing.blockGap, undefined)
  assert.equal(theme.styles.typography.fontFamily, undefined)
  assert.deepEqual(theme.styles.blocks, { 'core/group': { color: { text: '#101010' } } })
})

test('watches both sources and ignores change events for its own writes', (t) => {
  const root = fs.mkdtempSync(path.join(tmpdir(), 'forage-theme-json-'))
  t.after(() => fs.rmSync(root, { recursive: true, force: true }))
  const output = path.join(root, 'theme.json')
  const tokens = path.join(root, 'tokens.css')
  fs.writeFileSync(output, '{"version":3,"settings":{"appearanceTools":true}}')
  fs.writeFileSync(tokens, ':root { --space-40: 1rem; }')

  const watcher = new EventEmitter()
  watcher.add = () => {}
  const reloads = []
  const plugin = themeJson({ tokens: 'tokens.css' })
  plugin.configResolved({ root })
  plugin.configureServer({ watcher, ws: { send: (message) => reloads.push(message) } })
  const original = fs.readFileSync(output, 'utf8')
  const modified = fs.statSync(output).mtimeMs
  plugin.buildStart()
  assert.equal(fs.statSync(output).mtimeMs, modified)
  watcher.emit('change', output)
  assert.equal(reloads.length, 0)

  fs.writeFileSync(tokens, ':root { --space-40: 2rem; }')
  watcher.emit('change', tokens)
  watcher.emit('change', output)
  assert.equal(reloads.length, 1)
  assert.match(fs.readFileSync(output, 'utf8'), /"size": "2rem"/)

  fs.writeFileSync(output, original.replace('"appearanceTools": true', '"appearanceTools": false'))
  watcher.emit('change', output)
  watcher.emit('change', output)
  assert.equal(reloads.length, 2)
  assert.equal(JSON.parse(fs.readFileSync(output)).settings.appearanceTools, false)
  assert.match(fs.readFileSync(output, 'utf8'), /"size": "2rem"/)
})
