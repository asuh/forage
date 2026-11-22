# Editor And Theme JSON

## Source Files

Define presets, layout widths, and shared style defaults in `resources/styles/tokens.css`. Edit WordPress options such as `appearanceTools`, the list of allowed spacing units, and `defaultSpacingSizes` directly in the root `theme.json`.

Vite updates the CSS-owned fields in that same file during `npm run build` and `npm run dev`. The build preserves other fields, including handmade block settings and theme metadata. Commit `theme.json` with token changes so the WordPress configuration is ready to use after checkout.

## Design Tokens

Both the frontend and editor stylesheets import `tokens.css`. The generator automatically exports tokens with the `--color-*`, `--font-family-*`, `--font-size-*`, and `--space-*` prefixes. An optional comment immediately above a token sets its display name. Otherwise, the generator derives the name from the slug.

```css
:where(:root, .editor-styles-wrapper) {
  --content-measure: 46rem;
  /* Primary */
  --color-primary: #1d4ed8;
  --font-family-heading: "Example Sans", sans-serif;
  --font-size-large: clamp(1.5rem, 1.25rem + 1vw, 2rem);
  /* Small */
  --space-40: 1rem;
}
```

The part after the prefix is the preset slug. For example, `--color-deep-blue` has the slug `deep-blue` and gets the label **Deep Blue** when there is no label comment. The label can change while the slug stays the same.

WordPress creates the following CSS custom properties from the generated presets and offers the presets in supported block controls.

| Source token | WordPress CSS custom property |
| --- | --- |
| `--color-{slug}` | `--wp--preset--color--{slug}` |
| `--font-family-{slug}` | `--wp--preset--font-family--{slug}` |
| `--font-size-{slug}` | `--wp--preset--font-size--{slug}` |
| `--space-{slug}` | `--wp--preset--spacing--{slug}` |

The CSS imports make the original tokens available in both bundles, including values such as `--content-measure` that have no WordPress preset. Some generated presets also rely on those imports at runtime. Forage's fluid spacing presets, for example, use the shared progress variable from `tokens.css`.

### Block Defaults

Forage uses Small (`--space-40`) for its banner and navigation spacing. The spacing scale uses `progress()` to get a shared ratio between viewport widths of `20rem` and `80rem`. Each preset uses `calc()` to interpolate between its two `rem` endpoints. Browsers without `progress()` use the minimum below `50rem`, the midpoint from `50rem`, and the maximum from `80rem`.

When defining a preset with separate responsive overrides, declare its base value first and put overrides later in the file. WordPress then uses the CSS custom property with the base value as a fallback.

In addition to the four preset lists, the build owns these fields in `theme.json`.

| CSS token | WordPress field |
| --- | --- |
| `--content-measure` | `settings.layout.contentSize` |
| `--wide-content-measure` | `settings.layout.wideSize` |
| `--default-block-gap` | `styles.spacing.blockGap` |
| `--default-font-family` | `styles.typography.fontFamily` |

Forage sets `--default-block-gap: var(--space-50)` in CSS. The build emits `"var:preset|spacing|50"` so WordPress uses Medium as the default gap. The body font follows the same pattern. Add entries to `designFields` in `.vite/theme-json.js` for new defaults that map to WordPress style fields. Keep rules that depend on theme markup or component states in their stylesheets.

Both layout widths use `rem` so they respond to the root font size and stay consistent across elements with different font sizes, such as the editable title.

The build removes a generated field or preset when its source token is removed. It warns when `theme.json` references a preset absent from Forage's lists.

## Loading Fonts

A font-family token registers the family name and editor option, but it does not load font files.

Define `@font-face` rules in a shared stylesheet and import it from both frontend and editor entry points when the font must be available in both contexts:

```css
/* resources/styles/styles.css */
@import "common/fonts.css";

/* resources/styles/editor.css */
@import "common/fonts.css";
```

Vite resolves referenced files under `resources/fonts` during development and production builds.

## Admin And Editor Styles

Forage separates WordPress administration chrome, authored editor content, and editor controls:

* `resources/styles/admin.css`: loaded with `admin_enqueue_scripts` for wp-admin UI, panels, metaboxes, and plugin/theme administration screens
* `resources/styles/editor.css`: registered with `add_editor_style()` for the block editor and Classic Editor/TinyMCE
* `resources/styles/tokens.css`: source for shared layout widths, presets, and mapped block defaults
* `theme.json`: WordPress settings and synced design values

The block editor canvas may use an iframe. CSS loaded in `admin.css` remains in the parent administration document and does not cross into that canvas. Use `editor.css` for authored content presentation.

Enable inspector controls in `theme.json` and define their preset values in `tokens.css`.
