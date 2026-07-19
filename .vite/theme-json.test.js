import assert from 'node:assert/strict'
import test from 'node:test'
import { parseTokens } from './theme-json.js'

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
