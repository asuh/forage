# Integrations

## Prettify

Forage includes a self-contained Prettify layer for WordPress output cleanup without requiring Roots Soil. Configure it in `app/config/prettify.php`.

### Clean Up

Enabled by default, Clean Up:

* Removes generator tags, wlwmanifest, RSD, oEmbed discovery, and shortlink output
* Disables WordPress emoji scripts and styles
* Dequeues Gutenberg block-library CSS when the rendered query has no block content
* Removes redundant script and stylesheet attributes while preserving `type="module"`
* Simplifies navigation menu classes and normalizes the current item to `active`

Forage leaves Global Styles and `theme.json` output under WordPress control so presets, custom properties, and current on-demand behavior remain intact.

### Nice Search

Enabled by default, Nice Search redirects `/?s=query` to `/search/query/`. It is compatible with Yoast SEO.

### Relative URLs

Disabled by default, Relative URLs converts absolute URLs across a configurable list of WordPress hooks. Enable `relative-urls.enabled` in `app/config/prettify.php`.

## Contact Form 7

When Contact Form 7 is active, Forage avoids loading its frontend CSS, JavaScript, and challenge-provider assets on requests whose queried posts do not contain a detectable form.

Detection recognizes:

* Contact Form 7 shortcodes
* The Contact Form 7 block
* The `_cf7form` post meta used by supported integrations

A form rendered dynamically from a widget, template, custom field, or another plugin may not be detectable from post content. Force the assets to remain available for those requests with:

```php
add_filter('forage_cf7_assets_required', '__return_true');
```

The integration has no effect when Contact Form 7 is not installed.
