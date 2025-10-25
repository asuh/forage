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
        $this->enqueue('styles/styles.css', ['handle' => 'style']);
        $this->enqueue('scripts/scripts.js', ['handle' => 'script']);
        $this->enqueue('scripts/blocks.js', ['handle' => 'blocks']);

        if (is_singular() && comments_open() && get_option('thread_comments')) {
            $this->enqueue('scripts/comment-reply.js', ['handle' => 'comment-reply']);
        }
    }

    /**
     * @action admin_enqueue_scripts
     */
    public function admin(): void
    {
        $this->enqueue('styles/admin.css', ['handle' => 'admin']);
    }

    /**
     * @action wp_head
     */
    public function preload(): void
    {
        $preloads = apply_filters(
            'fm_assets_preload',
            [
                [
                    'href' => fm()->assets()->resolve('styles/styles.css'),
                    'as' => 'style',
                    'type' => 'text/css',
                ]
            ]
        );

        foreach ($preloads as $item) {
            if (empty($item['href']) || empty($item['as']) || empty($item['type'])) {
                continue;
            }

            printf(
                '<link rel="preload" href="%s" as="%s" type="%s" crossorigin="true" />',
                esc_attr($item['href']),
                esc_attr($item['as']),
                esc_attr($item['type']),
            );
        }
    }

    /**
     * @action wp_head
     */
    public function modulepreload(): void
    {
        $preloads = apply_filters(
            'fm_assets_module_preload',
            [
                ['href' => fm()->assets()->resolve('scripts/scripts.js')],
                ['href' => fm()->assets()->resolve('scripts/blocks.js')],
            ]
        );

        foreach ($preloads as $item) {
            if (empty($item['href'])) {
                continue;
            }

            printf(
                '<link rel="modulepreload" href="%s" />',
                esc_attr($item['href']),
            );
        }
    }
}
