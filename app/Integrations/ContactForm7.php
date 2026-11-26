<?php

namespace Forage\Integrations;

class ContactForm7
{
    private bool $assetsRequired = true;

    /**
     * Conditionally disable CF7 assets on pages without CF7 forms
     *
     * @action wp_enqueue_scripts 1
     */
    public function disableUnusedAssets(): void
    {
        if (! function_exists('wpcf7_load_js')) {
            return;
        }

        $this->assetsRequired = (bool) apply_filters(
            'forage_cf7_assets_required',
            $this->queryHasForm(),
        );

        if ($this->assetsRequired) {
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
    public function dequeueAssets(): void
    {
        if ($this->assetsRequired) {
            return;
        }

        wp_dequeue_script('contact-form-7');
        wp_dequeue_script('contact-form-7-html5-fallback');
        wp_dequeue_script('wpcf7-recaptcha');
        wp_dequeue_style('contact-form-7');
        wp_dequeue_style('contact-form-7-rtl');
    }

    private function queryHasForm(): bool
    {
        if (is_singular()) {
            $posts = [get_post()];
        } else {
            global $wp_query;

            $posts = $wp_query instanceof \WP_Query ? $wp_query->posts : [];
        }

        foreach ($posts as $post) {
            if ($post instanceof \WP_Post && $this->postHasForm($post)) {
                return true;
            }
        }

        return false;
    }

    private function postHasForm(\WP_Post $post): bool
    {
        return (bool) get_post_meta($post->ID, '_cf7form', true) ||
            has_shortcode($post->post_content, 'contact-form-7') ||
            has_shortcode($post->post_content, 'cf7form') ||
            has_block('contact-form-7/contact-form-selector', $post);
    }
}
