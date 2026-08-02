# Upgrading Forage

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
