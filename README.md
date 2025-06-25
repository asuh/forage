<p align="center"><img src="/resources/images/Forage.png" alt="Forage" /></p>

# Forage
This theme combines two starter themes: FootMATE + Sage. [I go into more detail on my blog post about everything](https://asuh.com/forage/).

## [FootMATE](https://github.com/przemekhernik/footmate.pro/tree/develop)
FootMATE is a WordPress theme created as [a demo for the author's course](https://pragmate.dev/wordpress/how-to-build-solid-wordpress-applications/),  built to teach modern WordPress development.

## [Sage](https://roots.io/sage/)
Sage is a WordPress starter theme with a modern development workflow.

## Features

* [Blade](https://laravel.com/docs/master/blade) as a templating engine
* [Vite](https://vite.dev/) for compiling assets, concatenating, and minifying files
* [Biome](https://biomejs.dev/) for linting and formatting both CSS and JS
* Modern CSS & JavaScript - No preprocessors, libraries, or frameworks
* [DocHooks](https://pragmate.dev/wordpress/dochooks/) provide new functionality of class method DocBlock as hooks into WordPress API
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

### Lightning CSS (optional)

Lightning CSS is a fast CSS parser that can be used as a great tool to manage the project's CSS. The primary benefit is in its speed and transpiling over PostCSS/ESBuild.

To enable Lightning CSS, run `yarn add --dev lightningcss` to add Lightning CSS as a new package referenced in `devDependencies` in `package.json`.

In `vite.config.js`, uncomment the two lines containing references to Lightning CSS and it'll be active by default.

For more info on whether or not to add this package, you can check [Vite's official documentation for Lightning CSS](https://vite.dev/guide/features.html#lightning-css).

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

I found FootMATE in spring of 2024 looking for an alternative to Sage. It purposely follows enough of a paradigm similar to Sage that it feels like a younger cousin. The author also decided to integrate Vite, a direction that Sage has taken in 2025. Vite is a win for productivity and DX and many projects are moving to using Vite.

The combination of the two themes satisfies my desire for good file architecture and modern tooling without much bloat or dependencies. It just works.

WordPress' direction towards full-site editing and Gutenberg leaves much to be desired for developers working on WordPress. This theme, while starting from a basis of the classic theme structure, has a lot of flexibility between the two worlds of classic and modern WordPress theme development.

I wrote more on the theme in [a blog post on my site](https://asuh.com/forage/).

## Contributing

Contributions are welcome from everyone.
