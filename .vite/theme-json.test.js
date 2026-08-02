import assert from 'node:assert/strict'
import test from 'node:test'
import { applyTokens, parseTokens } from './theme-json.js'

test('uses only an immediately preceding comment as a token name', () => {
  const tokens = parseTokens(`
    :root {
      /* Colors */

      --color-primary: #123456;
      /* Accent */
      --color-accent: #abcdef;
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
