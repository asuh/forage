<p align="center"><img src="/resources/images/Forage.png" alt="Forage" /></p>

# Forage
This theme combines two starter themes: Vilare + Sage. [I go into more detail on my blog post about everything](https://asuh.com/forage/).

## [Vilare](https://github.com/pragmatedev/vilare)
Vilare is a WordPress theme created as [a demo for the author's course](https://pragmate.dev/wordpress/how-to-build-solid-wordpress-applications/),  built to teach modern WordPress development.

## [Sage](https://roots.io/sage/)
Sage is a WordPress starter theme with a modern development workflow.

## Features

* [Blade](https://laravel.com/docs/master/blade) as a templating engine
* [Vite](https://vite.dev/) for compiling assets, concatenating, and minifying files
* [Biome](https://biomejs.dev/) for linting and formatting both CSS and JS
* Modern CSS & JavaScript - No preprocessors, libraries, or frameworks
* [DocHooks](https://pragmate.dev/wordpress/dochooks/) provide new functionality of class method DocBlock as hooks into WordPress API
* **Prettify**: self-contained WordPress output cleanup, nice search URLs, and optional relative URLs (no third-party plugins required)
* [IndieWeb](https://indieweb.org/) support with baked in [Microformats2](https://microformats.org/wiki/microformats2) and [structured data](https://schema.org/)

> [!NOTE]
> 
> Forage is in active development and might add or subtract features as it matures. It will introduce some updates that might need attention from time to time.


## Requirements

Make sure all dependencies have been installed before moving on:

* [WordPress](https://wordpress.org/) >= 6.8.x
* [PHP](https://www.php.net/manual/en/install.php) >= 8.3.x
* [Composer](https://getcomposer.org/download/)
* [Node.js](http://nodejs.org/) >= 24.0.0

## Theme structure

```shell
themes/your-theme-name/   # → Root of your theme
├── app/                  # → Theme PHP
│   ├── Assets/           # → Loader and Resolver files
│   ├── Comments/         # → Comment_Walker
│   ├── Core/             # → Core files
│   ├── Editor/           # → Block editor and Classic Editor support
│   ├── Integrations/     # → Various integrations
│   ├── Prettify/         # → WordPress output cleanup modules
│   └── config/           # → Theme configuration files
├── composer.json         # → Autoloading for `vendor/` files
├── composer.lock         # → Composer lock file (never edit)
├── dist/                 # → Built theme assets (never edit)
├── node_modules/         # → Node.js packages (never edit)
├── package.json          # → Node.js dependencies and scripts
├── package-lock.json     # → Node.js dependency lock file (never edit)
├── vite.config.js        # → Vite config file
├── resources/            # → Theme assets and templates
│   ├── fonts/            # → Theme fonts
│   ├── images/           # → Theme images
│   ├── scripts/          # → Theme javascripts
│   ├── styles/           # → Theme stylesheets
│   ├── functions.php     # → Composer autoloader, theme includes
│   ├── index.php         # → Never manually edit
│   ├── screenshot.png    # → Theme screenshot for WP admin
│   ├── style.css         # → Theme meta information
│   └── views/            # → Theme templates
│       └── components/   # → Component templates
│       └── forms/        # → Forms templates
│       └── partials/     # → Partial templates
└── vendor/               # → Composer packages (never edit)
```

## Environment Type

Production builds work without a custom `WP_ENVIRONMENT_TYPE` value. Set it to `local` or `development` to enable automatic HMR detection and timestamp asset versions during development:

```php
define('WP_ENVIRONMENT_TYPE', 'local');
```

Forage only probes the local Vite server in these development environments. This keeps production requests from making unnecessary development-server checks.

## Theme installation

Install Forage using Composer from the theme directory:

```shell
# @ wp-content/themes/
$ composer install
```

## Theme development

Install dependencies from the theme directory:

```shell
$ npm install
```

### Build commands

* `npm run build`: Compile and optimize the files in your assets directory
* `npm run dev`: Compile assets when file changes are made using Vite's hot module reload
* `npm run format`: Auto-format JS and CSS with Biome, PHP with PHPCS
* `npm run lint`: Lint JS and CSS with Biome, PHP with PHPCS without writing changes
* `npm test`: Run the build utility regression tests

### Deployment

Built assets in `dist/` are generated and not committed. Production deploys need to install dependencies and build assets before the theme loads outside HMR:

```shell
$ composer install --no-dev --optimize-autoloader
$ npm ci
$ npm run build
```

Forage intentionally stops with an `npm run build` message when HMR is inactive and `dist/manifest.json` is missing.

### Theme JSON

Forage generates `theme.json` from `theme.base.json` and `resources/styles/tokens.css` during `npm run build` and while `npm run dev` is running.

* `theme.base.json`: stable source for block editor defaults and block styles
* `resources/styles/tokens.css`: source for color, font family, font size, and spacing presets
* `theme.json`: generated WordPress file; do not hand-edit unless you are intentionally syncing generated output

Forage is intentionally starter-friendly: `tokens.css` can stay mostly blank until a project defines its design system. If `theme.base.json` references a WordPress preset such as `var(--wp--preset--color--primary)` or `var:preset|font-family|heading`, add the matching token in `tokens.css`. The generator warns about missing preset references during dev/build without blocking the build.

### Admin And Editor Styles

Forage keeps admin chrome styles, editor canvas styles, and block editor controls separate:

* `resources/styles/admin.css`: loaded with `admin_enqueue_scripts`; use for wp-admin UI chrome such as admin pages, panels, metaboxes, and plugin/theme admin interfaces
* `resources/styles/editor.css`: injected into `block_editor_settings_all` for the block editor and registered with `add_editor_style()` for Classic Editor/TinyMCE; use for CSS that should affect authored content inside the editor canvas/iframe
* `resources/styles/tokens.css`: source for palette, font family, font size, and spacing presets that appear in editor controls
* `theme.base.json`: source for block editor defaults, supports, and block-level styles

CSS alone does not create Styles inspector controls. Use `tokens.css`, `theme.base.json`, or block style registration when the goal is to expose options in the block editor UI.

### Network access

`npm run dev` exposes the Vite dev server on all network interfaces. Forage first checks `http://localhost:5173`, which works for local apps such as Local where the WordPress site may run at an HTTPS domain like `https://test.local`.

For same-network testing, a reverse proxy, or another explicit development host, define `FORAGE_HMR_HOST` in `wp-config.php` before the theme loads:

```php
define('FORAGE_HMR_HOST', 'http://192.168.1.42:5173');
```

### Lightning CSS

Lightning CSS handles CSS minification during `npm run build` by default (no install required; it ships with Vite 8). Using it as a full CSS transformer is optional:

To enable the transformer, uncomment `transformer: 'lightningcss'` in `vite.config.js`. For more details, see [Vite's Lightning CSS documentation](https://vite.dev/guide/features.html#lightning-css).

## Prettify

Forage includes a built-in Prettify layer that handles WordPress output cleanup without requiring the [Roots Soil](https://github.com/roots/soil) plugin. It is configured in `app/config/prettify.php`.

### Clean Up (enabled by default)

* Removes generator tags, wlwmanifest, RSD, oEmbed discovery, and shortlink from `<head>`
* Disables WordPress emoji scripts and styles
* Dequeues Gutenberg block library CSS on pages with no block content; preserves it when blocks are present
* Cleans up `<script>` and `<link>` tag attributes (removes `type`, redundant `id`, and `media="all"`)
* Strips verbose classes and IDs from nav menu `<li>` items; normalises `current-menu-item` to `active`

Forage leaves Global Styles and `theme.json` output under WordPress control. This preserves presets, custom properties, and WordPress' current on-demand loading behavior.

### Nice Search (enabled by default)

Redirects `/?s=query` to `/search/query/`. Compatible with Yoast SEO.

### Relative URLs (disabled by default)

Converts absolute URLs to relative URLs across a configurable list of WordPress hooks. Enable in `app/config/prettify.php` by setting `relative-urls.enabled` to `true`.

## IndieWeb

This theme has many pieces that integrate with the IndieWeb ecosystem out of the box with provided [IndieWeb WordPress plugins](https://indieweb.org/WordPress/Plugins). Specifically, I recommend the following:

* IndieWeb Plugin
* Webmention
* Syndication Links
* IndieAuth
* Post Kinds
* Micropub

If you use Gutenberg, you can also try [IndieBlocks](https://wordpress.org/plugins/indieblocks/), which duplicates most of the plugins above except IndieWeb Plugin and IndieAuth.

### h-card

I'm highlighting a feature called [h-card](https://microformats.org/wiki/h-card), which is a Microformats2 format for publishing data about people and a vital building block for the IndieWeb to work with WordPress.

The starter h-card automatically displays a real author avatar when one is available and otherwise uses a decorative inline SVG instead of requesting Gravatar's generic image. Gravatar availability results are cached to avoid repeated server-side checks. Comment avatars continue to use WordPress' `get_avatar()` API so avatar and Webmention plugins can manage them.

If you haven't already installed any of the above and want to test the waters, you can start with the IndieWeb Plugin. Generally, everything should just work out of the box.

If you have more than one WordPress user on your site, to use the correct person for the h-card, go to `wp-admin/admin.php?page=iw_general_options` of your WordPress instance and make sure the Default Author is correct.

## Libraries, Frameworks, and Packages

While this theme purposely doesn't include any external dependencies for projects, anything can be added to the workflow. But, before you do this, a couple of things to keep in mind.

* Vite transforms `.jsx` and `.tsx`, but an application still needs the relevant framework and runtime packages. Vue single-file components require Vue and `@vitejs/plugin-vue`.
* Vite supports `.scss`, `.less`, and `.styl` after the corresponding preprocessor is installed. Use the matching file extension, such as `.scss`, after installing Sass:

Scss

```
npm install --save-dev sass
```

* Vite processes standard CSS with PostCSS when a PostCSS configuration is present. PostCSS does not make Sass syntax, mixins, or functions available in ordinary `.css` files.

With anything mentioned above, if you add additional dependencies to `package.json`, make sure to update `vite.config.js` to include the necessary watch files and syntax.

[Vite's documentation](https://vite.dev/guide/features.html) provides a lot of great info to extend the Vite config file.

## Background

The Roots Sage project provided an excellent philosophy and approaches for a progressively developed WordPress theme, but after version 9, Sage had too many interconnected pieces, new dependencies and abstractions, and increasingly difficult to keep up with. Additionally, having focused on so many other projects and returning to an old version of this theme, Sage left much to be desired, as well as plenty of broken packages and outdated dependencies.

I found FootMATE (Now Vilare) in spring of 2024 looking for an alternative to Sage. It purposely followed enough of a paradigm similar to Sage that it felt like a younger cousin. The author also decided to integrate Vite, a direction that Sage took in 2025. Vite is a win for productivity as well as better developer experience and many projects have moved to using Vite.

The combination of the two themes satisfies my desire for good file architecture and modern tooling without much bloat or dependencies. It just works.

WordPress' direction towards full-site editing and Gutenberg leaves much to be desired for developers working on WordPress. This theme, while starting from a basis of the classic theme structure, has a lot of flexibility between the two worlds of classic and modern WordPress theme development.

I wrote more on the theme in [a blog post on my site](https://asuh.com/forage/).

## Contributing

Contributions are welcome from everyone.
