<p align="center"><img src="/resources/images/Forage.png" alt="Forage" /></p>

# Forage
This theme combines two starter themes: FootMATE + Sage. [I go into more detail on my blog post about everything](https://asuh.com/forage/).

## [Sage](https://roots.io/sage/)
Sage is a WordPress starter theme with a modern development workflow.

## [FootMATE](https://github.com/przemekhernik/footmate.pro/tree/develop)
FootMATE is a modern WordPress starter theme

## Features

* [Blade](https://laravel.com/docs/10.x/blade) as a templating engine
* [Vite](https://vitejs.dev/) for compiling assets, concatenating, and minifying files
* [Biome](https://biomejs.dev/) for linting and formatting both CSS and JS
* Modern CSS & JavaScript - No preprocessors, libraries, or frameworks
* [DocHooks](https://tentyp.dev/blog/wordpress/dochooks-sugar-syntax-for-hooking-system/) provide new functionality of class method DocBlock as hooks into WordPress API
* [IndieWeb](https://indieweb.org/) support with baked in Microformats2.

> [!NOTE]
> 
> Forage is in active development and might add or subtract features as it matures. It will introduce some updates that might need attention from time to time.


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

* `yarn build` — Compile and optimize the files in your assets directory
* `yarn dev` — Compile assets when file changes are made using Vite's hot module reload

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

I'm highlighting a feature called [h-card](https://microformats.org/wiki/h-card), which is a Microformats2 format for publishing people and a vital building block for the IndieWeb to work with WordPress.

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

[Vite's documentation](https://vitejs.dev/guide/features.html) provides a lot of great info to extend the Vite config file.

## Background

The Roots Sage project provided an excellent philosophy and approaches for a progressively developed WordPress theme, but after version 9, Sage had too many interconnected pieces, new dependencies, and legacy to keep up with. Additionally, having focused on so many other  returning to an old version of Sage left much to be desired, as well as plenty of broken services or packages.

I found FootMATE in spring of 2024 looking for an alternative. Strangely, it follows enough of a paradigm similar to Sage that it feels like a younger cousin. Also, the author decided to integrate Vite instead of Webpack, and that's a huge win for productivity and DX.

The combination of the two themes satisfies my desire for good file architecture and modern tooling without much bloat or dependencies. It just works.

WordPress' direction towards full-site editing and Gutenberg leaves much to be desired for developers working on WordPress. This theme, while starting from a basis of the classic theme structure, has a lot of flexibility between the two worlds of classic and modern WordPress theme development.

I wrote more on the theme in [a blog post on my site](https://asuh.com/forage/).

## Contributing

Contributions are welcome from everyone.
