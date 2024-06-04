<?php

namespace FM\Core;

class Setup
{
    /**
     * Loads default theme supports
     * @action after_setup_theme
     */
    public function addThemeSupport()
    {
        /**
         * Enable post thumbnails
         * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
         */
        add_theme_support('post-thumbnails');

        /**
         * Add default posts and comments RSS feed links to head
         */
        add_theme_support('automatic-feed-links');

        /**
         * The following requires the Roots Soil plugin
         * https://github.com/roots/soil
         */
        add_theme_support('soil', [
          'clean-up', // Cleaner WordPress markup
          'nav-walker', // Clean up nav menu markup
          'nice-search', // Redirect /?s=query to /search/query
          'relative-urls', // Convert absolute URLs to relative URLs
        ]);

        /**
         * HTML5 support
         */
        add_theme_support('html5', ['caption', 'comment-form', 'comment-list', 'gallery', 'search-form']);
    }

    /**
     * Register navigation menus
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     */
    public function addNavMenus()
    {
        register_nav_menus([
            'primary_navigation' => __('Primary Navigation')
        ]);
    }

    /**
     * Wraps the_excerpt in p-summary
     * @filter the_excerpt
     */
    public function addMF2Wrapper(string $content)
    {
        if (is_feed()) {
            return $content;
        }

        $wrap = '<div class="entry-summary p-summary">';

        if (! empty($content)) {
            return $wrap . $content . '</div>';
        }

        return $content;
    }

    /**
     * Code that improves theme support for various plugins
     * @action init
     */
    public function initIndieWeb()
    {
        /**
         * Removes automated Syndication Links code
         * from applying to main content section
         * I'm manually adding the same in the entry meta file
         */
        if (class_exists('Syn_Meta') && has_filter('the_content', [ 'Syn_Config', 'the_content' ])) {
            remove_filter('the_content', [ 'Syn_Config', 'the_content' ], 30);
        }
        /**
         * Adds support for Simple Location
         */
        if (class_exists('Loc_View') && has_filter('the_content', [ 'Loc_View', 'location_content' ])) {
            remove_filter('the_content', [ 'Loc_View', 'location_content' ], 12);
        }
    }

    /**
     * Adds custom classes to the array of post classes.
     * @filter post_class
     *
     * @param array $classes Classes for the body element.
     * @return array
     */
    public function postClasses($classes)
    {
        $classes = array_diff($classes, [ 'hentry' ]);
        if (! is_singular()) {
            if ('venue' === get_post_type()) {
                $classes[] = 'h-card';
            } elseif ('page' !== get_post_type()) {
                // Adds a class for microformats v2
                $classes[] = 'h-entry';
                // add hentry to the same tag as h-entry
                $classes[] = 'hentry';
            }
        }
        return $classes;
    }

    /**
     * Add <body> classes
     * @filter body_class
     */
    public function addBodyClasses(array $classes) {
        /** Add page slug if it doesn't exist */
        if (is_single() || is_page() && !is_front_page()) {
            if (!in_array(basename(get_permalink()), $classes)) {
                $classes[] = basename(get_permalink());
            }
        }

        /** Clean up class names for custom templates */
        $classes = array_map(function ($class) {
            return preg_replace(['/-blade(-php)?$/', '/^page-template-views/'], '', $class);
        }, $classes);

        return array_filter($classes);
    }    
}
