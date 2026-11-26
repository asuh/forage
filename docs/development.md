# Development

## Full Theme Structure

Forage keeps WordPress-facing theme files at the project root while application code, source assets, and Blade views remain grouped by responsibility:

```text
forage/
|-- .vite/                  # Vite plugins and build utility tests
|   |-- copy.js             # Copies static theme files into build output
|   |-- wordpress-proxy.js  # Optional Local/WordPress network preview
|   |-- theme-json.js       # Syncs theme.json presets and defaults from CSS
|   `-- theme-json.test.js  # Theme JSON generator regression tests
|-- app/                    # Theme PHP application code
|   |-- Assets/             # Asset registration, resolution, and preload hints
|   |-- Blade/              # Blade provider, resolver, directives, and runtime
|   |-- Comments/           # Comment walker and namespaced helpers
|   |-- Core/               # Cache, configuration, and DocBlock hooks
|   |-- Editor/             # Block Editor and Classic Editor styles
|   |-- Integrations/       # Vite and Gravatar integrations
|   |-- Navigation/         # Menu registration and submenu disclosure buttons
|   |-- Prettify/           # WordPress output cleanup modules
|   |-- config/             # Theme-specific PHP configuration
|   |-- App.php             # Application service initialization
|   `-- Setup.php           # WordPress theme setup and front-end hooks
|-- docs/                   # Extended project documentation
|-- inc/
|   |-- bootstrap.php       # Composer and application bootstrap
|   `-- render-template.php # Renders the selected Blade page when WordPress includes it
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
|-- theme.json              # WordPress settings with CSS-owned fields synced at build time
|-- vite.config.js          # Development server and production build
|-- composer.json           # PHP dependencies and autoloading
|-- package.json            # Front-end dependencies and npm commands
|-- phpcs.xml.dist          # WordPress PHP coding standards
`-- biome.json              # JavaScript and CSS lint configuration
```

Generated directories should not be edited or committed. Edit `theme.json` for WordPress options and `resources/styles/tokens.css` for presets, layout widths, and style defaults. See [Editor Styling And Theme JSON](editor.md) for the complete workflow.

## Starting A Project

Forage can run under any theme directory name without changing its internal namespace. At minimum, update the metadata in `style.css` and replace the starter logo and screenshot.

For a complete project rename, update these values consistently:

* Theme name, author, description, and text domain in `style.css`
* `forage` text-domain usages, PHP namespace, constants, asset handles, and cache directory name
* Composer and npm package names
* Starter images and project links

The PHP namespace and `FORAGE_*` constants can remain unchanged when they are treated as starter-theme internals rather than project branding.

Generated dependency lock files should be committed whenever dependencies change, but should not be edited manually.

## Node Version Managers

Forage supports both [nvm](https://github.com/nvm-sh/nvm) on macOS/Linux and [mise](https://mise.jdx.dev/installing-mise.html) on Windows, macOS, and Linux. Choose the manager you prefer, or install a compatible Node runtime directly. `.nvmrc` and `mise.toml` both select Node 24.22.1; keep these pins synchronized when updating the project runtime. The supported version range remains in `package.json` under `engines.node`.

With nvm, run these commands from the theme directory:

```shell
nvm install
nvm use
npm ci
npm run dev
```

For mise on Windows, install it with `winget install jdx.mise` in PowerShell. From the theme directory, the same project commands work on every platform:

```shell
mise trust
mise install
mise exec -- npm ci
mise exec -- npm run dev
```

`mise exec --` selects the project runtime for that command without requiring shell activation. Use it for other commands too, such as `mise exec -- npm test` and `mise exec -- npm run build`. The project mise configuration manages Node; PHP and Composer use the runtimes provided by your WordPress development environment or installation.

## Environment Type

Production builds work without a custom `WP_ENVIRONMENT_TYPE` value. Set it to `local` or `development` to enable automatic HMR detection and timestamp asset versions during development:

```php
define('WP_ENVIRONMENT_TYPE', 'local');
```

Forage only enables HMR in these development environments. Production ignores the development-server status file.

## HMR And Network Access

`npm run dev` exposes Vite on all network interfaces and writes its origin and actual port to `.vite/hot`. PHP reads this file without making an HTTP request. Vite refreshes its modification time every ten seconds and removes it on normal shutdown. If Vite crashes, the file expires after thirty seconds and Forage falls back to built assets. The file is ignored by Git and must not be included in deployments.

### WordPress Preview On A Phone

Create `.env.development.local` in the theme directory (it is ignored by Git):

```ini
WP_DEV_PROXY_TARGET=http://test.local
```

Use the working WordPress origin shown in Local, including its scheme and port if needed. Start the site in Local, then run `npm run dev` **from the theme directory used by that WordPress installation**. If you sync this repository into Local, also copy the ignored `.env.development.local` file or create it in the destination. Vite and PHP must share the same `.vite/hot` file.

Continue using `http://test.local` on desktop. On a phone on the same LAN, open the `WordPress preview` address printed by Vite, for example `http://192.168.50.214:5173/`. Use the computer's Wi-Fi or Ethernet address; loopback, VPN, and virtual-machine interfaces may also appear. The preview starts at `/`, without `/wp-content/themes/forage`. The port is Vite's actual listening port, including when 5173 is already occupied.

The development plugin forwards WordPress pages, uploads, REST, login, and admin requests to Local. Vite continues to handle theme source assets and its own WebSocket connection. CSS updates, JavaScript updates or reloads, and PHP-triggered page reloads use that connection. No manual production rebuild or changes to `WP_HOME` or `WP_SITEURL` are needed.

For proxied responses, the plugin rewrites the configured WordPress origin and the Vite origin published by the hot-file plugin to the address used by the browser. It covers absolute, protocol-relative, JSON slash-escaped, and URL-encoded forms in UTF-8/ASCII HTML, CSS, JavaScript, JSON, XML, SVG, and plain text. Redirects and relevant URL headers are rewritten too; cookies for the upstream domain become host-only preview cookies. External origins are left alone. Compressed text is decoded, transformed responses get fresh lengths, and preview responses use `Cache-Control: no-store`. Binary files, attachments, non-UTF-8 text, and partial responses pass through without body rewriting. Text rewriting has a 16 MiB limit; failures return a 502 with details in the Vite terminal.

Only the configured origins are rewritten. Set the target to WordPress's canonical origin to avoid HTTP/HTTPS redirects back into the same preview URL. Additional domains introduced by plugins or a CDN require their own configuration. Custom `FORAGE_HMR_HOST` overrides that differ from Vite's published origin are not automatically rewritten; remove that override for this workflow.

For an HTTPS upstream, Node must trust Local's certificate (for example, supply the issuing certificate using `NODE_EXTRA_CA_CERTS` when starting Vite). Certificate verification stays enabled. Secure cookies retain their `Secure`, `HttpOnly`, and `SameSite` attributes, so an HTTP LAN preview cannot provide an authenticated session that requires secure cookies. That case needs an HTTPS Vite preview with a certificate trusted by the phone. Verify login on the actual device before relying on it.

Remove the target setting and restart Vite to return to asset-only serving. The plugin does not run during production builds and adds no dependencies.

### Explicit Asset Host

For another browser-facing asset origin, define `FORAGE_HMR_HOST` in `wp-config.php` before the theme loads:

```php
define('FORAGE_HMR_HOST', 'http://192.168.1.42:5173');
```

The override changes only the browser-facing asset origin; it does **not** proxy the complete WordPress site. It still requires a fresh `.vite/hot` file and a `local` or `development` environment. Vite and PHP must share the theme directory. Remove `.vite/hot` to force an immediate fallback after an unclean shutdown. Run `npm run build` when the theme must work without an active Vite server.

## Production Assets

Production builds load the main stylesheet normally and add `modulepreload` hints for JavaScript entries and imported chunks. There is no default stylesheet preload. Manifest assets use their hashed filenames without an additional version query, so custom preloads and enqueued assets can request the same URL. Copied images use content hashes and keep their URLs across builds until their contents change.

The asset resolver enqueues CSS from entries and their static imports, including nested imports, and deduplicates shared styles. JavaScript imports load through native ES modules; CSS handles are kept out of WordPress's script dependency list. Dynamic imports remain lazy. Vite manages these dependencies during HMR, so Forage omits the production hints while the development server is active.

An explicit `version` passed to `enqueue()` is preserved, including `null` and `false`. If overriding a module-preloaded entry's version, update its preload URL through `forage_assets_module_preload` to match. External assets retain the configured theme version by default.

Use `forage_assets_module_preload` to customize or remove generated JavaScript module hints. For project-specific font or image preloads, output the appropriate `<link>` tags through WordPress's `wp_head` action. That hook provides the insertion point; it does not choose or optimize preloads automatically. Add hints only when measurements justify them, escape their URLs and attributes, and match the eventual resource request.

Run `npm test` for build utility and Vite lifecycle regressions and `composer test` for PHP regressions. The Vite test briefly starts a loopback development server. The PHP tests use isolated WordPress API doubles and require installed Composer dependencies, but do not require a WordPress installation.

Forage keeps `dist/` out of version control. Deployments must run:

```shell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

## Deployment Security

Use the latest patched versions in Forage's supported PHP and Node branches. PHP 8.4.27 and 8.5.12 include the October 2026 PHP2Shell fixes; Node 24.22.1 includes the October security fixes affecting Brotli, TLS, DNS, and HTTP handling. Raising requirements in the theme does not update an installed runtime. Node is needed for development and builds; deployments that ship prebuilt assets do not need a running Node process.

Check the CLI versions with `php --version` and `node --version`, and run `composer check-platform-reqs --lock` with the PHP binary used for deployment. Independently verify the PHP version and package build serving WordPress through the hosting panel or server configuration; CLI and web PHP can differ. After a PHP update, restart or recreate the relevant workers, services, or containers so requests use the patched runtime. See the [PHP advisory](https://www.php.net/archive/2026.php#2026-10-21-5) and [Node release notes](https://nodejs.org/en/blog/release/v24.22.1).

PHP's October 23 follow-up for CVE-2026-115560 concerns applications configured with `session.serialize_handler=php_binary` or `php_serialize` that switch to `php` using `ini_set()` before `session_start()`, with upload progress enabled. Forage does not use this pattern; review plugins and server configuration separately. For affected applications, PHP recommends `session.upload_progress.enabled=0` until the follow-up fix is available. Configure it in `php.ini` or the server's PHP configuration before request parsing, then restart workers; a theme-level `ini_set()` is too late. Follow the [session upload progress advisory](https://www.php.net/archive/2026.php#2026-10-23-1) for current guidance.

For Windows PHP distributions, use the October 26 package rebuilds (`8.4.27-1` or `8.5.12-1`), or newer patched builds, which also update the bundled Brotli decoder for CVE-2026-111497. The rebuild suffix does not change the PHP runtime version, so Composer cannot identify that package distinction. On other platforms, verify the host or distribution's Brotli/cURL package updates. See the [Windows build announcement](https://www.php.net/archive/2026.php#2026-10-26-1).

Run `composer audit --locked` and `npm audit` when updating dependencies. Production deployments should omit Composer development dependencies as shown above; this keeps PHP lint tools out of the deployed dependency set.

## Blade Cache

Compiled Blade views are stored in the WordPress uploads directory at `uploads/cache/forage`. Forage creates this directory when the theme is activated and removes it when the theme is switched off. The uploads directory must be writable by PHP.

The Blade compiler and view factory initialize on the first `view()`, `generate()`, or `render()` call. Requests that do not use Blade avoid that initialization. Register extensions through `forage_templating_provider_init`; it runs once per provider, before the first view is created, and receives the compiler and view finder.

## WordPress Template Lifecycle

The `template_include` filter selects the Blade page and returns `inc/render-template.php` without rendering HTML. WordPress then includes that PHP bridge, which renders the selected view once. This lets WordPress 6.9+ start its template enhancement output buffer before Blade renders, so core can process the resulting document when enhancements are enabled. The same bridge works on WordPress 6.8 and when buffering is disabled; Forage does not start or manage core's buffer itself.

The root `index.php` remains an empty fallback because the comments provider also returns it after rendering the comments partial. Keeping it separate from the page bridge prevents comments from triggering a second page render. Direct calls to the templating API still render immediately. Block CSS loading policy is independent of this lifecycle integration.

## Images In Blade

Use `@image` for theme-owned raster files under `resources/images`:

```blade
@image('hero.avif', [
    'alt' => 'A wooded trail',
    'loading' => 'eager',
    'fetchpriority' => 'high',
])
```

The helper resolves the production or development URL, escapes attributes, and includes the file's intrinsic width and height. Supplying one dimension calculates the other using the original aspect ratio; supplying both avoids reading image dimensions. It defaults to `decoding="async"` and does not force lazy loading, because an image may be the page's main visible content. Use `loading => 'lazy'` for images below the initial viewport. Reserve high fetch priority for an image identified as critical to the page.

Supported attributes are `alt`, `class`, `id`, `title`, `width`, `height`, `srcset`, `sizes`, `loading`, `decoding`, and `fetchpriority`. Pass `false` or `null` to omit an attribute. The `src` always comes from the asset resolver. Responsive variants are not generated automatically: supply a `srcset` of resolved URLs and a `sizes` value matching the layout when variants exist.

For WordPress media-library attachments, use `wp_get_attachment_image()` or `the_post_thumbnail()` so WordPress can supply responsive sources and its loading optimizations:

```blade
{!! wp_get_attachment_image($attachment_id, 'large', false, ['class' => 'article-image']) !!}
```

## Performance Checks

The footer load-time label is only included while Vite serves development assets. A production build removes its timing code. It reports the navigation load event, not Core Web Vitals; use the following process to assess a change:

1. Build with `npm run build`, stop Vite, and use a running WordPress installation with representative content and plugins. Check a home/archive page, a long article with images and comments, and a page with blocks or forms.
2. Record mobile Lighthouse runs before and after under the same browser, throttling, viewport, and login state. Run each page three times and compare medians. Keep browser-cache state consistent, and distinguish cold server caches from warmed page/Blade caches.
3. Track LCP (main content becoming visible), CLS (layout shifts), transferred CSS/JS/image bytes, and request count. Exercise navigation, search dialogs, and forms with a Performance recording; use field INP data when available to assess responsiveness beyond a lab run.
4. For PHP changes, profile uncached requests separately from page-cache hits. Compare response time, peak memory, and queries for frontend, admin, and REST requests. Confirm that requests without Blade output skip the provider initialization hook.
5. Check the actual resource waterfall before adding preload hints. Confirm that hero images are not lazy-loaded and responsive image requests match their displayed size. Preserve comparable reports outside the theme's production output.

Deployment also determines performance: verify page-cache behavior, compression, and long-lived cache headers for hashed assets on the actual server. Changes to these settings are separate from the theme build.

## Custom Page Templates

The root `template-custom.php` file registers the example page template with WordPress. Its rendered Blade view lives at `resources/views/template-custom.blade.php`.

Use the same marker-and-view pairing for conventional custom templates:

```text
template-example.php
resources/views/template-example.blade.php
```

The root PHP file owns the `Template Name` header. The Blade resolver maps the selected PHP template name to its view.

## Browser Targets

`vite.config.js` sets `build.target` to `'esnext'`, which assumes modern JavaScript support and performs minimal transpilation. CSS inherits this target, so Lightning CSS preserves modern syntax such as `light-dark()` while still minifying it. This setting does not define a specific browser support window.

To target minimum browser versions for both JavaScript and CSS, replace `build.target` with an array, for example:

```js
build: {
  target: ['chrome152', 'edge152', 'firefox155', 'safari26.5', 'ios26.5'],
  // Keep the other build options.
},
```

Choose versions for your project's browser support policy and update them manually as needed. To give CSS a separate target while keeping JavaScript at `'esnext'`, set `build.cssTarget` to a browser-version array instead. Rebuild with `npm run build` after changing either option.

See Vite's [`build.target`](https://vite.dev/config/build-options.html#build-target) and [`build.cssTarget`](https://vite.dev/config/build-options.html#build-csstarget) documentation for supported values and behavior.

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

* `forage_templating_provider_init`: receives the Blade compiler and view finder on first use, before the first view is created
* `forage_assets_module_preload`: filters production module preload entries
* `forage_cf7_assets_required`: overrides automatic Contact Form 7 asset detection
