import fs from 'node:fs'
import path from 'node:path'

/**
 * Derive a display name from a slug.
 * "base-2"  → "Base 2"
 * "x-large" → "X Large"
 */
function slugToName(slug) {
  return slug
    .split('-')
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(' ')
}

/**
 * Parse --color-*, --font-family-*, --font-size-*, and --space-* custom properties
 * from a CSS string. A single-line comment on the line immediately
 * above a variable becomes its display name; otherwise the name is
 * derived from the slug.
 */
function parseTokens(css) {
  const palette = []
  const fontFamilies = []
  const fontSizes = []
  const spacingSizes = []

  // Strip multi-line block comments (docblocks, section comments) so
  // their content isn't matched as tokens. Single-line /* Name */
  // comments are preserved — they become token display names.
  const stripped = css.replace(/\/\*[\s\S]*?\*\//g, (m) =>
    m.includes('\n') ? '' : m,
  )

  // Match: optional /* Name */ comment, then --category-slug: value;
  const re =
    /(?:\/\*\s*([^*\n]+?)\s*\*\/\s*\n\s*)?--(color|font-family|font-size|space)-([a-z0-9-]+)\s*:\s*([^;]+);/g

  for (const [, comment, category, slug, raw] of stripped.matchAll(re)) {
    const name = comment?.trim() ?? slugToName(slug)
    const value = raw.trim()

    if (category === 'color') {
      palette.push({ slug, color: value, name })
    } else if (category === 'font-family') {
      fontFamilies.push({ slug, fontFamily: value, name })
    } else if (category === 'font-size') {
      fontSizes.push({ slug, size: value, name, fluid: false })
    } else if (category === 'space') {
      spacingSizes.push({ slug, size: value, name })
    }
  }

  return { palette, fontFamilies, fontSizes, spacingSizes }
}

function presetSlugs(items) {
  if (Array.isArray(items)) {
    return items.map(({ slug }) => slug).filter(Boolean)
  }

  if (!items || typeof items !== 'object') {
    return []
  }

  return Object.values(items).flatMap(presetSlugs)
}

function collectPresetSlugs(themeJson) {
  return {
    color: new Set(presetSlugs(themeJson.settings?.color?.palette)),
    'font-family': new Set(
      presetSlugs(themeJson.settings?.typography?.fontFamilies),
    ),
    'font-size': new Set(presetSlugs(themeJson.settings?.typography?.fontSizes)),
    spacing: new Set(presetSlugs(themeJson.settings?.spacing?.spacingSizes)),
  }
}

function warnAboutMissingPresetReferences(themeJson) {
  const slugs = collectPresetSlugs(themeJson)
  const json = JSON.stringify(themeJson)
  const missing = new Set()
  const cssVariablePattern =
    /--wp--preset--(color|font-family|font-size|spacing)--([a-z0-9-]+)/g
  const presetReferencePattern =
    /var:preset\|(color|font-family|font-size|spacing)\|([a-z0-9-]+)/g

  for (const [, category, slug] of json.matchAll(cssVariablePattern)) {
    if (!slugs[category]?.has(slug)) {
      missing.add(`${category}:${slug}`)
    }
  }

  for (const [, category, slug] of json.matchAll(presetReferencePattern)) {
    if (!slugs[category]?.has(slug)) {
      missing.add(`${category}:${slug}`)
    }
  }

  if (missing.size === 0) {
    return
  }

  console.warn(
    `[theme-json] Missing token presets referenced in theme.base.json: ${Array.from(
      missing,
    ).join(', ')}`,
  )
}

function generate(tokensPath, basePath, outputPath) {
  const css = fs.readFileSync(tokensPath, 'utf-8')
  const base = JSON.parse(fs.readFileSync(basePath, 'utf-8'))
  const { palette, fontFamilies, fontSizes, spacingSizes } = parseTokens(css)

  if (palette.length) {
    base.settings ??= {}
    base.settings.color ??= {}
    base.settings.color.palette = { theme: palette }
  }

  if (fontFamilies.length) {
    base.settings ??= {}
    base.settings.typography ??= {}
    base.settings.typography.fontFamilies = { theme: fontFamilies }
  }

  if (fontSizes.length) {
    base.settings ??= {}
    base.settings.typography ??= {}
    base.settings.typography.fontSizes = { theme: fontSizes }
  }

  if (spacingSizes.length) {
    base.settings ??= {}
    base.settings.spacing ??= {}
    base.settings.spacing.spacingSizes = { theme: spacingSizes }
  }

  warnAboutMissingPresetReferences(base)
  fs.writeFileSync(outputPath, JSON.stringify(base, null, 2) + '\n')
  console.log('[theme-json] Generated theme.json from tokens.css')
}

export default function themeJson(options = {}) {
  let root = ''
  const rel = (p) => path.resolve(root, p)

  return {
    name: 'theme-json',

    configResolved(config) {
      root = config.root
    },

    buildStart() {
      const tokensPath = rel(options.tokens ?? 'resources/styles/tokens.css')
      const basePath = rel(options.base ?? 'theme.base.json')
      const outputPath = rel(options.output ?? 'theme.json')
      generate(tokensPath, basePath, outputPath)
    },

    configureServer(server) {
      const tokensPath = rel(options.tokens ?? 'resources/styles/tokens.css')
      const basePath = rel(options.base ?? 'theme.base.json')
      const outputPath = rel(options.output ?? 'theme.json')

      server.watcher.add([tokensPath, basePath])
      server.watcher.on('change', (file) => {
        if (file === tokensPath || file === basePath) {
          generate(tokensPath, basePath, outputPath)
          server.ws.send({ type: 'full-reload' })
        }
      })
    },
  }
}
