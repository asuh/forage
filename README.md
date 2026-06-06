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
* **Prettify** — self-contained WordPress output cleanup, nice search URLs, and optional relative URLs (no third-party plugins required)
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
* [Yarn](https://yarnpkg.com/getting-started/install)

## Theme structure

```shell
themes/your-theme-name/   # → Root of your theme
├── app/                  # → Theme PHP
│   ├── Assets/           # → Loader and Resolver files
│   ├── Comments/         # → Comment_Walker
│   ├── Core/             # → Core files
│   ├── Integrations/     # → Various integrations
│   ├── Prettify/         # → WordPress output cleanup modules
│   ├── Templates/        # → Render and Compile Templates files
│   └── config/           # → Theme configuration files
├── composer.json         # → Autoloading for `vendor/` files
├── composer.lock         # → Composer lock file (never edit)
├── dist/                 # → Built theme assets (never edit)
├── node_modules/         # → Node.js packages (never edit)
├── package.json          # → Node.js dependencies and scripts
├── vite.config.js        # → Vite config file
├── yarn.lock             # → Yarn lock file (never edit)
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

Multiple features of this theme require the environment type to be set.

* Hot Module Reload
* Versioning

To enable these features, add or adjust `WP_ENVIRONMENT_TYPE` in `wp-config.php` located in the root of your WordPress installation. The value needs to be either `development` or `local`.

`define( 'WP_ENVIRONMENT_TYPE', 'development' );`

## Theme installation

Install Forage using Composer from the theme directory:

```shell
# @ wp-content/themes/
$ composer install
```

## Theme development

* Run `yarn` from the theme directory to install dependencies

### Build commands

* `yarn run build` — Compile and optimize the files in your assets directory
* `yarn run dev` — Compile assets when file changes are made using Vite's hot module reload
* `yarn run format` — Auto-format JS and CSS with Biome, PHP with PHPCS
* `yarn run lint` — Lint JS and CSS with Biome, PHP with PHPCS

### Network access

`yarn dev` exposes the Vite dev server on all network interfaces. When accessing the WordPress site from another device on the same network (phone, tablet, etc.), HMR connects automatically — no configuration needed. The dev server URL is printed on startup alongside the localhost URL.

To override the HMR host manually (e.g. behind a reverse proxy), define `FORAGE_HMR_HOST` in `wp-config.php` before the theme loads:

```php
define('FORAGE_HMR_HOST', 'http://192.168.1.42:5173');
```

### Lightning CSS

Lightning CSS handles CSS minification during `yarn build` by default (no install required — it ships with Vite 8). Using it as a full CSS transformer is optional:

To enable the transformer, uncomment `transformer: 'lightningcss'` in `vite.config.js`. For more details, see [Vite's Lightning CSS documentation](https://vite.dev/guide/features.html#lightning-css).

## Prettify

Forage includes a built-in Prettify layer that handles WordPress output cleanup without requiring the [Roots Soil](https://github.com/roots/soil) plugin. It is configured in `app/config/prettify.php`.

### Clean Up (enabled by default)

* Removes generator tags, wlwmanifest, RSD, oEmbed discovery, and shortlink from `<head>`
* Disables WordPress emoji scripts and styles
* Dequeues Gutenberg block library CSS, extra RSS feeds, recent comments CSS, and gallery CSS
* Cleans up `<script>` and `<link>` tag attributes (removes `type`, redundant `id`, and `media="all"`)
* Strips verbose classes and IDs from nav menu `<li>` items; normalises `current-menu-item` to `active`

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

If you haven't already installed any of the above and want to test the waters, you can start with the IndieWeb Plugin. Generally, everything should just work out of the box.

If you have more than one WordPress user on your site, to use the correct person for the h-card, go to `wp-admin/admin.php?page=iw_general_options` of your WordPress instance and make sure the Default Author is correct.

## Libraries, Frameworks, and Packages

While this theme purposely doesn't include any external dependencies for projects, anything can be added to the workflow. But, before you do this, a couple of things to keep in mind.

* `.jsx`, `.tsx`, `.vue` and more are provided natively in Vite. With this support, you likely don't need an additional dependency for React or Vue.
* `.scss`, `.less`, `.styl` and more are provided natively in Vite. The only additional thing to do is add the pre-processor itself, such as:

Scss

```
yarn add -D sass
```

* PostCSS is provided natively in Vite. Considering this, you can write Sass syntax in `.css` files already. If you want to support Sass specific language features like Mixins or Functions, add [postcss-scss](https://github.com/postcss/postcss-scss) in `devDependencies` and you'll get most everything you need from the Sass language processed by PostCSS.

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
