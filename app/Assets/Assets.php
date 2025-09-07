<?php

namespace FM\Assets;

use FM\Assets\Resolver;

class Assets
{
    use Resolver;

    /**
     * @action wp_enqueue_scripts
     */
    public function front(): void
    {
        wp_enqueue_style('theme', $this->resolve('styles/styles.css'), [], fm()->config()->get('version'));
        wp_enqueue_script('theme', $this->resolve('scripts/scripts.js'), [], fm()->config()->get('version'), true);
        wp_enqueue_script('blocks', $this->resolve('scripts/blocks.js'), [], fm()->config()->get('version'), true);

        if (is_singular() && comments_open() && get_option('thread_comments')) {
            wp_enqueue_script('comment-reply');
        }
    }
}
