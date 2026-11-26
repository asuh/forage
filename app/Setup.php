<?php

namespace Forage;

class Setup
{
    /**
     * Loads default theme supports
     *
     * @action after_setup_theme
     */
    public function addThemeSupport(): void
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
         * Enable HTML5 markup support.
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#html5
         */
        add_theme_support(
            'html5',
            [
                'caption',
                'comment-form',
                'comment-list',
                'gallery',
                'search-form',
                'script',
                'style',
            ]
        );

        /**
         * Enable plugins to manage the document title.
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/#title-tag
         */
        add_theme_support('title-tag');

        /**
         * Enable responsive embed support.
         * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#responsive-embedded-content
         */
        add_theme_support('responsive-embeds');

        /**
         * Enable editor styles for the block editor and Classic Editor.
         * @link https://developer.wordpress.org/block-editor/how-to-guides/themes/theme-support/#editor-styles
         */
        add_theme_support('editor-styles');

        /**
         * Enable selective refresh for widgets in customizer.
         * @link https://developer.wordpress.org/reference/functions/add_theme_support/
         */
        add_theme_support('customize-selective-refresh-widgets');

        /**
         * Remove the largest WP image sizes
         * They often cause more issues than fix
         */
        /**
         * remove_image_size( '1536x1536' );
         * remove_image_size( '2048x2048' );
         */

        /**
         * Custom image sizes
         */
        /**
         * add_image_size( '424x424', 424, 424, true );
         * add_image_size( '1920', 1920, 9999 );
         */
    }

    /**
     * Register the theme sidebars.
     *
     * @action widgets_init
     */
    public function register_sidebars(): void
    {
        $config = [
            'before_widget' => '<section class="widget %1$s %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h3>',
            'after_title' => '</h3>',
        ];

        register_sidebar(
            [
                'name' => __('Primary', 'forage'),
                'id' => 'sidebar-primary',
            ] + $config,
        );

        register_sidebar(
            [
                'name' => __('Footer', 'forage'),
                'id' => 'sidebar-footer',
            ] + $config,
        );
    }

    /**
     * Adds custom classes to the array of post classes.
     * @filter post_class
     *
     * @param array $classes Classes for the body element.
     * @return array
     */
    public function postClasses($classes): array
    {
        $classes = array_diff($classes, ['hentry']);
        if (! is_singular()) {
            if ('venue' === get_post_type()) {
                $classes[] = 'h-card';
            } elseif ('page' !== get_post_type()) {
                // Adds a class for microformats v2.
                $classes[] = 'h-entry';
                // add hentry to the same tag as h-entry.
                $classes[] = 'hentry';
            }
        }
        return $classes;
    }

    /**
     * Add <body> classes
     *
     * @filter body_class
     */
    public function addBodyClasses(array $classes): array
    {
        /** Clean up class names for custom templates */
        $classes = array_map(
            function ($classname) {
                return preg_replace(
                    ['/-blade(-php)?$/', '/^page-template-views/'],
                    '',
                    $classname,
                );
            },
            $classes
        );

        /** Add page slug if it doesn't exist */
        if ((is_single() || is_page()) && ! is_front_page()) {
            $slug = basename(get_permalink());

            if (! in_array($slug, $classes, true)) {
                $classes[] = $slug;
            }
        }

        return array_values(array_filter($classes, static fn ($name) => '' !== $name));
    }
}
