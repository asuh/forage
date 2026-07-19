<?php

namespace Forage;

class Setup
{
    private bool $cf7AssetsRequired = true;

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
     * Register navigation menus
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     *
     * @action after_setup_theme
     */
    public function addNavMenus(): void
    {
        register_nav_menus(
            [
                'primary_navigation' => __('Primary Navigation', 'forage'),
            /** 'secondary_navigation' => __('Secondary Navigation') */
            ]
        );
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
     * Code that improves theme support for various plugins
     *
     * @action init
     */
    public function initIndieWeb(): void
    {
        /**
         * Removes automated Syndication Links code
         * from applying to main content section
         * I'm manually adding the same in the entry meta file
         */
        if (
            class_exists('Syn_Meta') &&
            has_filter('the_content', ['Syn_Config', 'the_content'])
        ) {
            remove_filter('the_content', ['Syn_Config', 'the_content'], 30);
        }
        /**
         * Adds support for Simple Location
         */
        if (
            class_exists('Loc_View') &&
            has_filter('the_content', ['Loc_View', 'location_content'])
        ) {
            remove_filter('the_content', ['Loc_View', 'location_content'], 12);
        }
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
        /** Add page slug if it doesn't exist */
        if ((is_single() || is_page()) && ! is_front_page()) {
            if (! in_array(basename(get_permalink()), $classes, true)) {
                $classes[] = basename(get_permalink());
            }
        }

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

        return array_filter($classes);
    }

    /**
     * Override Webmentions plugin Walker_Comment extended class
     *
     * @filter wp_list_comments_args
     */
    public function overrideWebmentionsWalker($args): array
    {
        if (class_exists('Webmention\Comment_Walker')) {
            $args['walker'] = new \Forage\Comments\Comments();
        }
        return $args;
    }

    /** Remove the following code if you're not using Contact Form 7 plugin */

    /**
     * Conditionally disable CF7 assets on pages without CF7 forms
     *
     * @action wp_enqueue_scripts 1
     */
    public function conditionallyDisableCf7(): void
    {
        if (! function_exists('wpcf7_load_js')) {
            return;
        }

        $this->cf7AssetsRequired = (bool) apply_filters(
            'forage_cf7_assets_required',
            $this->queryHasCf7Form(),
        );

        if ($this->cf7AssetsRequired) {
            return;
        }

        add_filter('wpcf7_load_js', '__return_false');
        add_filter('wpcf7_load_css', '__return_false');

        remove_action(
            'wp_enqueue_scripts',
            'wpcf7_recaptcha_enqueue_scripts',
            20,
        );
        remove_action(
            'wp_enqueue_scripts',
            'wpcf7_turnstile_enqueue_scripts',
            10,
        );
    }

    /**
     * Clean up any CF7 assets enqueued after the load filters were applied
     *
     * @action wp_print_scripts 99
     * @action wp_print_styles 99
     */
    public function dequeueCf7Assets(): void
    {
        if ($this->cf7AssetsRequired) {
            return;
        }

        wp_dequeue_script('contact-form-7');
        wp_dequeue_script('contact-form-7-html5-fallback');
        wp_dequeue_script('wpcf7-recaptcha');
        wp_dequeue_style('contact-form-7');
        wp_dequeue_style('contact-form-7-rtl');
    }

    private function queryHasCf7Form(): bool
    {
        if (is_singular()) {
            $posts = [get_post()];
        } else {
            global $wp_query;

            $posts = $wp_query instanceof \WP_Query ? $wp_query->posts : [];
        }

        foreach ($posts as $post) {
            if ($post instanceof \WP_Post && $this->postHasCf7Form($post)) {
                return true;
            }
        }

        return false;
    }

    private function postHasCf7Form(\WP_Post $post): bool
    {
        return (bool) get_post_meta($post->ID, '_cf7form', true) ||
            has_shortcode($post->post_content, 'contact-form-7') ||
            has_shortcode($post->post_content, 'cf7form') ||
            has_block('contact-form-7/contact-form-selector', $post);
    }
}
