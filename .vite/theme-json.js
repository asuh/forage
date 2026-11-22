import fs from 'node:fs'
import path from 'node:path'

const presets = {
  color: { category: 'color', list: 'palette', valueKey: 'color', section: 'color' },
  'font-family': { category: 'font-family', list: 'fontFamilies', valueKey: 'fontFamily', section: 'typography' },
  'font-size': { category: 'font-size', list: 'fontSizes', valueKey: 'size', section: 'typography' },
  space: { category: 'spacing', list: 'spacingSizes', valueKey: 'size', section: 'spacing' },
}

const designFields = {
  '--content-measure': ['settings', 'layout', 'contentSize'],
  '--wide-content-measure': ['settings', 'layout', 'wideSize'],
  '--default-block-gap': ['styles', 'spacing', 'blockGap'],
  '--default-font-family': ['styles', 'typography', 'fontFamily'],
}

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
 * and the shared layout and style defaults from tokens.css. A single-line
 * comment immediately above a preset supplies its label. Otherwise the
 * label is derived from the slug. Declare each preset's fallback first
 * and put its responsive overrides later in the file.
 */
export function parseTokens(css) {
  const tokens = { palette: [], fontFamilies: [], fontSizes: [], spacingSizes: [], design: {} }
  const declarations = new Map()
  // Match an optional label and a custom property, or skip a whole comment.
  // Keep quoted strings and comments intact when locating the declaration's semicolon.
  const pattern =
    /(?:\/\*[ \t]*([^*\r\n]+?)[ \t]*\*\/[ \t]*\r?\n[ \t]*)?(--[a-z][a-z0-9-]*)\s*:\s*((?:\/\*[\s\S]*?\*\/|"(?:\\.|[^"\\])*"|'(?:\\.|[^'\\])*'|[^;"'{}])+);|\/\*[\s\S]*?\*\//g

  for (const [, comment, property, raw] of css.matchAll(pattern)) {
    if (!property) continue

    const existing = declarations.get(property)
    if (existing) {
      existing.hasOverride = true
    } else {
      declarations.set(property, { label: comment?.trim(), value: raw.trim() })
    }
  }

  for (const [property, { label, value, hasOverride }] of declarations) {
    const match = property.match(/^--(color|font-family|font-size|space)-([a-z0-9-]+)$/)
    if (match) {
      const [, prefix, slug] = match
      const { list, valueKey } = presets[prefix]
      tokens[list].push({
        slug,
        [valueKey]: hasOverride ? `var(${property}, ${value})` : value,
        name: label || slugToName(slug),
      })
    } else if (Object.hasOwn(designFields, property)) {
      const reference = value.match(/^var\(\s*--(color|font-family|font-size|space)-([a-z0-9-]+)\s*\)$/)
      const field = designFields[property]
      tokens.design[property] = reference && !hasOverride && field[0] === 'styles'
        ? `var:preset|${presets[reference[1]].category}|${reference[2]}`
        : hasOverride ? `var(${property}, ${value})` : value
    }
  }

  return tokens
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
    `[theme-json] Preset references without a matching Forage token in theme.json: ${Array.from(
      missing,
    ).join(', ')}`,
  )
}

function setGeneratedField(data, [key, ...rest], value) {
  if (!rest.length) {
    if (value === undefined) {
      delete data[key]
    } else {
      data[key] = value
    }
    return
  }

  if (data[key] || value !== undefined) {
    setGeneratedField(data[key] ??= {}, rest, value)
  }
}

export function applyTokens(theme, tokens) {
  for (const { section, list } of Object.values(presets)) {
    const values = tokens[list]
    setGeneratedField(theme, ['settings', section, list], values.length ? values : undefined)
  }

  for (const [property, field] of Object.entries(designFields)) {
    setGeneratedField(theme, field, tokens.design[property])
  }

  return theme
}

function generate(tokensPath, outputPath) {
  const css = fs.readFileSync(tokensPath, 'utf-8')
  const input = fs.readFileSync(outputPath, 'utf-8')
  const theme = JSON.parse(input)
  const before = JSON.stringify(theme, null, 2)
  applyTokens(theme, parseTokens(css))

  warnAboutMissingPresetReferences(theme)
  const after = JSON.stringify(theme, null, 2)
  if (before === after) return input

  // Replace the complete JSON document so WordPress never reads a partial write.
  const temporaryPath = `${outputPath}.${process.pid}.tmp`
  try {
    fs.writeFileSync(temporaryPath, after + '\n')
    fs.renameSync(temporaryPath, outputPath)
  } finally {
    fs.rmSync(temporaryPath, { force: true })
  }

  console.log('[theme-json] Synced CSS values into theme.json')
  return after + '\n'
}

export default function themeJson(options = {}) {
  let root = ''
  const rel = (p) => path.resolve(root, p)
  const tokensPath = () => rel(options.tokens ?? 'resources/styles/tokens.css')
  const outputPath = () => rel(options.output ?? 'theme.json')
  const sync = () => generate(tokensPath(), outputPath())

  return {
    name: 'theme-json',

    configResolved(config) {
      root = config.root
    },

    buildStart() {
      sync()
    },

    configureServer(server) {
      let lastTheme = sync()
      server.watcher.add([tokensPath(), outputPath()])
      server.watcher.on('change', (file) => {
        if (file !== tokensPath() && file !== outputPath()) return

        try {
          const currentTheme = sync()
          if (file === outputPath() && currentTheme === lastTheme) return

          lastTheme = currentTheme
          server.ws.send({ type: 'full-reload' })
        } catch (error) {
          server.config.logger.error(`[theme-json] ${error.message}`)
        }
      })
    },
  }
}
