<p align="center"><img src="/resources/images/Forage.png" alt="Forage" /></p>

# Forage

Forage is a classic WordPress starter theme adapted from [Vilare](https://github.com/pragmatedev/vilare) and informed by [Roots Sage](https://roots.io/sage/). [The project background and implementation decisions are covered in more detail on my blog](https://asuh.com/forage/).

## Features

* [Blade](https://laravel.com/docs/master/blade) templating
* [Vite](https://vite.dev/) asset compilation, bundling, and hot module replacement
* [Biome](https://biomejs.dev/) CSS and JavaScript linting and formatting
* Modern CSS and JavaScript with no frontend runtime framework or preprocessor
* [DocHooks](https://pragmate.dev/wordpress/dochooks/) for registering class methods with the WordPress hooks API
* CSS-driven generation of native WordPress `theme.json` presets
* Built-in WordPress output cleanup and performance-oriented asset loading
* [IndieWeb](https://indieweb.org/), [Microformats2](https://microformats.org/wiki/microformats2), and [Schema.org](https://schema.org/) support

> [!NOTE]
>
> Forage is in active development and may introduce changes that require attention when updating.

## Requirements

* [WordPress](https://wordpress.org/) >= 6.8
* [PHP](https://www.php.net/manual/en/install.php) >= 8.3 with the DOM extension
* [Composer](https://getcomposer.org/download/)
* [Node.js](https://nodejs.org/) >= 24.0.0

If PHP OPcache is enabled, `opcache.save_comments` must also be enabled because Forage uses DocBlock annotations to register WordPress hooks.

## Theme Structure

```text
themes/your-theme-name/
├── .vite/                # Vite plugins and build tests
├── app/                  # Theme PHP
├── docs/                 # Detailed project documentation
├── functions.php         # WordPress theme bootstrap
├── inc/                  # Bootstrap support
├── index.php             # WordPress template fallback
├── resources/            # Assets and Blade views
├── screenshot.png        # WordPress theme screenshot
├── style.css             # WordPress theme metadata
├── template-custom.php   # Example custom-template registration
├── theme.base.json       # Hand-edited theme.json source
├── theme.json            # Generated and tracked WordPress settings
└── vite.config.js        # Vite configuration
```

## Quick Start

Install Forage under `wp-content/themes`, then run these commands from the theme directory:

```shell
composer install
npm ci
npm run build
```

Activate Forage in WordPress after the initial build. The generated `dist/manifest.json` is required whenever the Vite development server is not running.

For local HMR detection, set the WordPress environment type and start Vite:

```php
define('WP_ENVIRONMENT_TYPE', 'local');
```

```shell
npm run dev
```

## Commands

* `npm run dev`: start Vite with hot module replacement
* `npm run build`: generate `theme.json` and compile production assets
* `npm run format`: format CSS and JavaScript with Biome and PHP with PHPCBF
* `npm run lint`: lint CSS and JavaScript with Biome and PHP with PHPCS
* `npm test`: run build utility regression tests

## Deployment

Built assets in `dist/` are generated and not committed. A production deployment must install dependencies and build the theme:

```shell
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

Forage intentionally stops with an `npm run build` message when HMR is inactive and `dist/manifest.json` is missing.

## Documentation

* [Development and asset workflow](docs/development.md)
* [Editor styles, design tokens, and theme.json](docs/editor.md)
* [Prettify and Contact Form 7](docs/integrations.md)
* [Upgrade notes and breaking changes](docs/upgrading.md)

## IndieWeb

Forage includes [Microformats2](https://microformats.org/wiki/microformats2) and [Schema.org](https://schema.org/) markup intended to work with the WordPress [IndieWeb](https://indieweb.org/) ecosystem. Common companion plugins include:

* IndieWeb
* Webmention
* Syndication Links
* IndieAuth
* Post Kinds
* Micropub

[IndieBlocks](https://wordpress.org/plugins/indieblocks/) is another Gutenberg-oriented option that overlaps with several of these plugins.

### h-card

The starter [h-card](https://microformats.org/wiki/h-card) displays a real author avatar when available and otherwise uses a local inline SVG instead of requesting Gravatar's generic image. Gravatar availability results are cached to avoid repeated server-side checks.

Comment avatars continue through WordPress's `get_avatar()` API so avatar and Webmention plugins can manage them.

When the IndieWeb plugin is active and a site has multiple users, select the appropriate Default Author under the IndieWeb settings. The h-card uses the current post author on singular content and the configured default elsewhere.

## Background

Forage keeps the classic WordPress template model and the parts of the Sage and Vilare architecture that remain useful without adopting their larger framework layers. It provides Blade templates, modern asset tooling, and selective WordPress editor integration while staying suitable as a relatively blank project starting point.

## Contributing

Contributions are welcome from everyone.

## License

Forage is licensed under the [GNU General Public License, version 3](LICENSE).
