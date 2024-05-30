# sage-footmate
This theme combines Footmate + Sage 9

## [Sage](https://roots.io/sage/)
Sage is a WordPress starter theme with a modern development workflow.

## Features

* Modern CSS
* Modern JavaScript
* [Blade](https://laravel.com/docs/10.x/blade) as a templating engine
* [Vite](https://laravel.com/docs/10.x/blade) for compiling assets, concatenating, and minifying files

## Requirements

Make sure all dependencies have been installed before moving on:

* [WordPress](https://wordpress.org/) >= 6.5.3
* [PHP](https://www.php.net/manual/en/install.php) >= 8.2.x
* [Composer](https://getcomposer.org/download/)
* [Node.js](http://nodejs.org/) >= 20.0.0
* [Yarn](https://yarnpkg.com/getting-started/install)

## Theme installation

Install Sage FootMATE using Composer from your WordPress themes directory:

```shell
# @ wp-content/themes/
$ composer install
```

## Theme structure

```shell
themes/your-theme-name/   # → Root of your theme
├── app/                  # → Theme PHP
│   ├── Assets/           # → Loader and Resolver files
│   ├── Comments/         # → Comment_Walker
│   ├── Core/             # → Core files
│   ├── Integrations/     # → Various integrations
│   ├── Posts/            # → Posts
│   ├── Teams/            # → Example Teams
│   └── Templates/        # → Render and Compile Templates files
├── composer.json         # → Autoloading for `app/` files
├── composer.lock         # → Composer lock file (never edit)
├── dist/                 # → Built theme assets (never edit)
├── node_modules/         # → Node.js packages (never edit)
├── package.json          # → Node.js dependencies and scripts
├── vite.config.js        # → Vite Config file
├── yarn.lock             # → Yarn lock file (never edit)
├── resources/            # → Theme assets and templates
│   ├── fonts/            # → Theme fonts
│   ├── images/           # → Theme images
│   ├── scripts/          # → Theme JS
│   ├── styles/           # → Theme stylesheets
│   ├── functions.php     # → Composer autoloader, theme includes
│   ├── index.php         # → Never manually edit
│   ├── screenshot.png    # → Theme screenshot for WP admin
│   ├── style.css         # → Theme meta information
│   └── views/            # → Theme templates
│       └── partials/     # → Partial templates
└── vendor/               # → Composer packages (never edit)
```

## Theme development

* Run `yarn` from the theme directory to install dependencies

### Build commands

* `yarn dev` — Compile assets when file changes are made, start Browsersync session
* `yarn build` — Compile and optimize the files in your assets directory

## Contributing

Contributions are welcome from everyone.


