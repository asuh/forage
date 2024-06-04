# Forage
This theme combines two starter themes: FootMATE + Sage

## [Sage](https://roots.io/sage/)
Sage is a WordPress starter theme with a modern development workflow.

## [FootMATE](https://github.com/przemekhernik/footmate.pro/tree/develop)
FootMATE is a modern WordPress starter theme

## Features

* [Blade](https://laravel.com/docs/10.x/blade) as a templating engine
* [Vite](https://vitejs.dev/) for compiling assets, concatenating, and minifying files
* Modern CSS & JavaScript - No preprocessors, libraries, or frameworks
* [DocHooks](https://tentyp.dev/blog/wordpress/dochooks-sugar-syntax-for-hooking-system/) provide new functionality of class method DocBlock as hooks into WordPress API

## Requirements

Make sure all dependencies have been installed before moving on:

* [WordPress](https://wordpress.org/) >= 6.5.x
* [PHP](https://www.php.net/manual/en/install.php) >= 8.2.x
* [Composer](https://getcomposer.org/download/)
* [Node.js](http://nodejs.org/) >= 20.0.0
* [Yarn](https://yarnpkg.com/getting-started/install)

## Theme structure

```shell
themes/your-theme-name/   # → Root of your theme
├── app/                  # → Theme PHP
│   ├── Assets/           # → Loader and Resolver files
│   ├── Comments/         # → Comment_Walker
│   ├── Core/             # → Core files
│   ├── Integrations/     # → Various integrations
│   └── Templates/        # → Render and Compile Templates files
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
│       └── partials/     # → Partial templates
└── vendor/               # → Composer packages (never edit)
```

## Environment Type

Multiple features of this theme require the environment type to be set.

* Hot Module Reload
* Versioning

To enable these features, add the following line to `wp-config.php` in the root of your WordPress installation.

`define( 'WP_ENVIRONMENT_TYPE', 'development' );`

## Theme installation

Install Forage using Composer from your WordPress themes directory:

```shell
# @ wp-content/themes/
$ composer install
```

## Theme development

* Run `yarn` from the theme directory to install dependencies

### Build commands

* `yarn dev` — Compile assets when file changes are made, start Browsersync session
* `yarn build` — Compile and optimize the files in your assets directory

## IndieWeb

This theme has many pieces that integrate with the IndieWeb ecosystem out of the box with provided [IndieWeb WordPress plugins](https://indieweb.org/WordPress/Plugins). Specifically, I recommend the following:

* IndieWeb Plugin
* Webmention
* Syndication Links
* IndieAuth
* Post Kinds
* Micropub

If you use Gutenberg, you can also try [IndieBlocks](https://wordpress.org/plugins/indieblocks/) instead of everything above but IndieAuth.

## Background

The Roots Sage project provided an excellent philosophy for a progressive WordPress theme, but after version 9 had too many interconnected pieces and legacy to keep up with. Additionally, returning to an old version of Sage left much to be desired, as well as plenty of broken services or packages.

I found FootMATE in spring of 2024 looking for an alternatives. Strangely, it provides enough of a similarity to Sage that it feels like a younger cousin. Also, the author decided to integrate Vite instead of Webpack, and that's a huge win for productivity and DX.

The combination of the two themes satisfies my desire for good file architecture and modern tooling without much bloat or dependencies. It just works.

WordPress' direction towards full-site editing leaves much to be desired for developers working on WordPress. This theme, while starting from a basis of the classic theme structure, has a lot of flexibility between the two worlds of classic and modern WordPress theme development. 

## Contributing

Contributions are welcome from everyone.
