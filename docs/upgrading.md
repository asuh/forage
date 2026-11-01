# Upgrading Forage

## Node And PHP Lint Tool Updates

Forage now requires Node 24.22.1+ within the 24.x LTS branch (`^24.22.1`), which includes the October 2026 security fixes for Brotli decompression and other Node APIs. The development proxy uses Node's Brotli decoder, so update local and build runtimes as well as the package metadata. With nvm, run `nvm install` and `nvm use` from the theme directory to use `.nvmrc`, then run `npm ci`. With mise, run `mise trust`, `mise install`, and `mise exec -- npm ci` to use `mise.toml`. Both files pin the same Node version. See [Node version managers](development.md#node-version-managers) and the [Node security release](https://nodejs.org/en/blog/release/v24.22.1).

PHP_CodeSniffer now stays on its 3.x branch with a minimum of 3.13.6, and WordPress Coding Standards requires 3.4.1 or later within 3.x. The lock file also includes a patched PHPCSUtils version. Run `composer install` to receive the updated development tools, then `composer lint`. These address earlier security advisories affecting linting; they are separate from PHP2Shell.

## PHP Requirements

Forage now requires PHP 8.4.27+ within the 8.4 branch, or PHP 8.5.12+ within the 8.5 branch. These versions include the upstream fixes for the October 2026 PHP2Shell vulnerabilities. Use the latest patched release in a supported branch; see the [PHP security advisory](https://www.php.net/archive/2026.php#2026-10-21-5).

Upgrade the CLI and web-serving PHP runtimes before installing dependencies. Composer's `config.platform.php` is set to `8.4.27` so dependency updates on newer PHP preserve the minimum supported version. It affects dependency resolution only; verify the real deployment runtime with `composer check-platform-reqs --lock`. PHP 8.3 and PHP 8.5.0–8.5.11 no longer satisfy the requirement, and future PHP branches require an explicit compatibility update.

## Deferred Page Rendering

Forage now selects its Blade page in `template_include` and returns `inc/render-template.php` for WordPress to include. HTML is rendered from that bridge, after WordPress 6.9+ starts its template enhancement buffer when enabled. WordPress 6.8 remains supported.

Include the new bridge file when deploying. Later `template_include` filters can replace the returned path before Forage outputs any HTML. Code that previously expected page output or rendering side effects during that filter must run during template rendering instead. The root `index.php` remains blank for the comments fallback; do not turn it into a page renderer. This update does not change block CSS loading policy.

## Lazy Blade Initialization And Asset Hints

Blade now initializes when the first view is requested rather than on `after_setup_theme`. Register compiler directives and view namespaces using `forage_templating_provider_init`; the hook runs before the first view is created. Code that assumed the view factory was already bound after theme setup should use the theme's templating API to request a view instead.

The `forage_assets_preload` filter and `Assets::preload()` method have been removed. Move any project-specific preload output to WordPress's `wp_head` action, escaping URLs and attributes. The main stylesheet still loads normally, and JavaScript module preloads and the `forage_assets_module_preload` filter are unchanged.

The footer load-time label is development-only and is removed by production builds. Theme images rendered with `@image` now include intrinsic dimensions and asynchronous decoding by default; explicit attributes can override or omit these values. See [Development](development.md) for the image API and repeatable performance checks.

## Comments View Data

The comments provider now passes only `post` and `comments_open`. Custom comments views should use WordPress's prepared comment loop through `have_comments()` and `wp_list_comments()` instead of the former `comments` array. The unused `comment_pages` value has also been removed.

Generate previous and next links inside the pagination condition with `get_previous_comments_link()` and `get_next_comments_link()`. These functions return complete anchor markup, not URLs; the starter view now renders that markup directly and omits unavailable links.

## Root Theme Layout

Forage previously kept `style.css`, `functions.php`, `index.php`, and `screenshot.png` under `resources/`. WordPress therefore identified an installation using a nested stylesheet slug such as `forage/resources`.

These files now live at the theme root, and WordPress identifies the same directory as `forage`. This also allows WordPress to load the root generated `theme.json` natively.

Fresh installations require no migration.

## Existing Installations

The stylesheet identity change may affect data WordPress associates with the old theme slug:

* Active `stylesheet` and `template` options
* Navigation menu location assignments
* Customizer theme mods and custom CSS
* Stored `_wp_page_template` values
* Pages assigned to the previous Blade template path

Use this upgrade sequence for an existing site:

1. Record navigation menu locations, Customizer settings, custom CSS, and custom page-template assignments.
2. Switch temporarily to another installed theme.
3. Replace the previous Forage files with the root-layout version.
4. Confirm `composer install` and `npm run build` have completed.
5. Activate the root-level Forage theme.
6. Restore or verify menu locations, theme mods, custom CSS, and page-template assignments.

Sites that need to preserve these values automatically should migrate the old theme-mod option and stored page-template values as part of deployment. That migration is site-specific and intentionally is not included in the starter theme.

## Custom Page Template Change

The example template previously stored its `Template Name` header in `resources/views/template-custom.blade.php`. WordPress could discover that file only because `resources/` acted as the stylesheet directory.

The root layout now uses:

```text
template-custom.php                         # WordPress registration marker
resources/views/template-custom.blade.php  # Rendered Blade view
```

Existing pages may retain the old template path and should be reassigned after activation.
