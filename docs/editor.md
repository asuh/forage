# Editor And Theme JSON

## Sources Of Truth

Forage generates the root `theme.json` from two hand-edited sources during `npm run build` and while `npm run dev` is running:

* `theme.base.json`: block editor defaults and supports
* `resources/styles/tokens.css`: color, font-family, font-size, and spacing presets
* `theme.json`: generated and tracked output loaded natively by WordPress

Do not hand-edit `theme.json`; the next build will overwrite it. Commit the regenerated file whenever either source changes so WordPress settings remain available before someone runs the frontend toolchain.

## Design Tokens

`tokens.css` can remain mostly blank until a project defines its design system. Tokens use these naming conventions:

```css
:root {
  --color-primary: #1d4ed8;
  --font-family-heading: "Example Sans", sans-serif;
  --font-size-large: clamp(1.5rem, 1.25rem + 1vw, 2rem);
  --space-40: 1rem;
}
```

The generator converts them to native `theme.json` preset arrays. WordPress then creates variables such as `--wp--preset--color--primary` and exposes the presets through controls supported by the selected block.

If `theme.base.json` references a preset such as `var(--wp--preset--color--primary)` or `var:preset|font-family|heading`, add the matching token to `tokens.css`. Missing preset references produce a development/build warning without stopping the build.

### Runtime Custom Properties

The generator reads `tokens.css`, but Vite does not automatically include that file in a browser stylesheet. WordPress exposes generated presets as `--wp--preset--*` custom properties through `theme.json`; the original properties such as `--color-primary` only exist at runtime when `tokens.css` is imported by a CSS entry.

Import it where the original properties are needed:

```css
/* resources/styles/styles.css */
@import "tokens.css";

/* resources/styles/editor.css */
@import "tokens.css";
```

Import it into both entries when frontend and authored editor content use the original properties. No import is necessary when project CSS only uses the WordPress-generated `--wp--preset--*` properties.

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
* `resources/styles/tokens.css`: source for presets exposed in supported editor controls
* `theme.base.json`: source for block defaults and editor supports

The block editor canvas may use an iframe. CSS loaded in `admin.css` remains in the parent administration document and does not cross into that canvas. Use `editor.css` for authored content presentation.

CSS alone does not create inspector controls. Use tokens, `theme.base.json`, or block style registration when a setting must appear in the editor UI.
