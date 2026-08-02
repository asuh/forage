# Development

## Full Theme Structure

Forage keeps WordPress-facing theme files at the project root while application code, source assets, and Blade views remain grouped by responsibility:

```text
forage/
|-- .vite/                  # Vite plugins and build utility tests
|   |-- copy.js             # Copies static theme files into build output
|   |-- theme-json.js       # Generates theme.json presets from CSS tokens
|   `-- theme-json.test.js  # Theme JSON generator regression tests
|-- app/                    # Theme PHP application code
|   |-- Assets/             # Asset registration, resolution, and preload hints
|   |-- Blade/              # Blade provider, resolver, directives, and runtime
|   |-- Comments/           # Comment walker and namespaced helpers
|   |-- Core/               # Cache, configuration, and DocBlock hooks
|   |-- Editor/             # Block Editor and Classic Editor styles
|   |-- Integrations/       # Vite and Gravatar integrations
|   |-- Prettify/           # WordPress output cleanup modules
|   |-- config/             # Theme-specific PHP configuration
|   |-- App.php             # Application service initialization
|   `-- Setup.php           # WordPress theme setup and front-end hooks
|-- docs/                   # Extended project documentation
|-- inc/
|   `-- bootstrap.php       # Composer and application bootstrap
|-- resources/              # Uncompiled source assets and Blade views
|   |-- fonts/              # Project font files, when added
|   |-- images/             # Source images
|   |-- scripts/            # Front-end and editor JavaScript entries
|   |-- styles/             # Front-end, editor, admin, and token CSS
|   |   |-- common/         # Shared foundational CSS
|   |   `-- partials/       # Focused front-end style modules
|   `-- views/              # Blade templates
|       |-- components/     # Reusable view components
|       |-- forms/          # Form views
|       `-- partials/       # Template partials
|-- dist/                   # Generated production assets (ignored)
|-- node_modules/           # Generated npm dependencies (ignored)
|-- vendor/                 # Generated Composer dependencies (ignored)
|-- functions.php           # WordPress theme bootstrap
|-- index.php               # WordPress fallback template
|-- style.css               # Theme metadata and WordPress stylesheet
|-- screenshot.png          # WordPress theme screenshot
|-- template-custom.php     # Example custom page-template registration
|-- theme.base.json         # Stable editor and block defaults
|-- theme.json              # Generated WordPress theme configuration
|-- vite.config.js          # Development server and production build
|-- composer.json           # PHP dependencies and autoloading
|-- package.json            # Front-end dependencies and npm commands
|-- phpcs.xml.dist          # WordPress PHP coding standards
`-- biome.json              # JavaScript and CSS lint configuration
```

Generated directories should not be edited or committed. Edit `theme.base.json` for stable WordPress defaults and `resources/styles/tokens.css` for generated design presets; see [Editor Styling And Theme JSON](editor.md) for the complete workflow.

## Starting A Project

Forage can run under any theme directory name without changing its internal namespace. At minimum, update the metadata in `style.css` and replace the starter logo and screenshot.

For a complete project rename, update these values consistently:

* Theme name, author, description, and text domain in `style.css`
* `forage` text-domain usages, PHP namespace, constants, asset handles, and cache directory name
* Composer and npm package names
* Starter images and project links

The PHP namespace and `FORAGE_*` constants can remain unchanged when they are treated as starter-theme internals rather than project branding.

Generated dependency lock files should be committed whenever dependencies change, but should not be edited manually.

## Environment Type

Production builds work without a custom `WP_ENVIRONMENT_TYPE` value. Set it to `local` or `development` to enable automatic HMR detection and timestamp asset versions during development:

```php
define('WP_ENVIRONMENT_TYPE', 'local');
```

Forage only probes the Vite server in these development environments. This prevents production requests from performing unnecessary development-server checks.

## HMR And Network Access

`npm run dev` exposes Vite on all network interfaces. Forage first checks `http://localhost:5173`, which works for local applications where WordPress is served from another domain, such as `https://test.local`.

For same-network testing, a reverse proxy, or another explicit host, define `FORAGE_HMR_HOST` in `wp-config.php` before the theme loads:

```php
define('FORAGE_HMR_HOST', 'http://192.168.1.42:5173');
```

If the development server cannot be reached, Forage falls back to `dist/manifest.json` in every environment. Run `npm run build` when the theme must work without an active Vite server.

## Production Assets

Production builds preload the main stylesheet and add `modulepreload` hints for JavaScript entries and imported chunks. Vite manages these dependencies during HMR, so Forage omits the production hints while the development server is active.

Use these filters to customize or remove generated hints:

* `forage_assets_preload`: filters stylesheet and other standard preload entries
* `forage_assets_module_preload`: filters JavaScript module preload entries

Forage keeps `dist/` out of version control. Deployments must run:

```shell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## Blade Cache

Compiled Blade views are stored in the WordPress uploads directory at `uploads/cache/forage`. Forage creates this directory when the theme is activated and removes it when the theme is switched off. The uploads directory must be writable by PHP.

## Custom Page Templates

The root `template-custom.php` file registers the example page template with WordPress. Its rendered Blade view lives at `resources/views/template-custom.blade.php`.

Use the same marker-and-view pairing for conventional custom templates:

```text
template-example.php
resources/views/template-example.blade.php
```

The root PHP file owns the `Template Name` header. The Blade resolver maps the selected PHP template name to its view.

## Lightning CSS

Lightning CSS handles minification during `npm run build`. To use it as the full CSS transformer, add `transformer: 'lightningcss'` to `vite.config.js`:

```js
css: {
  transformer: 'lightningcss',
  devSourcemap: true,
},
```

See [Vite's Lightning CSS documentation](https://vite.dev/guide/features.html#lightning-css) for supported configuration.

## Adding Frontend Packages

Forage does not include a frontend runtime framework or UI library, but projects can add them when needed.

* Vite transforms `.jsx` and `.tsx`, but the corresponding framework and runtime packages are still required.
* Vue single-file components require Vue and `@vitejs/plugin-vue`.
* `.scss`, `.less`, and `.styl` files require their corresponding preprocessors.
* Standard CSS uses PostCSS when a PostCSS configuration is present.

For example, install Sass with:

```shell
npm install --save-dev sass
```

Imported npm dependencies do not need to be added to `vite.config.js`. Update Vite only when a dependency requires a plugin, introduces a build entry, or changes build behavior.

## Extension Points

Forage exposes a small set of WordPress hooks for project-specific behavior:

* `forage_templating_provider_init`: receives the Blade compiler and view finder after initialization
* `forage_assets_preload`: filters standard production preload entries
* `forage_assets_module_preload`: filters production module preload entries
* `forage_cf7_assets_required`: overrides automatic Contact Form 7 asset detection
