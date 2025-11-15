<?php

namespace Vilare\Assets;

use Vilare\Assets\Resolver;

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
            $this->enqueue(
                'scripts/comment-reply.js',
                [
                    'handle' => 'comment-reply',
                ]
            );
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
            'vilare_assets_preload',
            [
                [
                    'href' => vilare()->assets()->resolve('styles/styles.css'),
                    'as' => 'style',
                    'type' => 'text/css',
                    'crossorigin' => true
                ],
                /* Example webfont preload
                [
                    'href' => vilare()->assets()->resolve('fonts/font_name.woff2'),
                    'as' => 'font',
                    'type' => 'font/woff2',
                    'crossorigin' => true,
                ], */
            ]
        );

        foreach ($preloads as $item) {
            if (
                empty($item['href']) ||
                empty($item['as']) ||
                empty($item['type'])
            ) {
                continue;
            }

            $crossorigin_attr = !empty($item['crossorigin']) ? ' crossorigin' : '';

            printf(
                '<link rel="preload" href="%s" as="%s" type="%s"%s />',
                esc_attr($item['href']),
                esc_attr($item['as']),
                esc_attr($item['type']),
                $crossorigin_attr,
            );
        }
    }

    /**
     * @action wp_head
     */
    public function modulepreload(): void
    {
        $preloads = apply_filters(
            'vilare_assets_module_preload',
            [
                ['href' => vilare()->assets()->resolve('scripts/scripts.js')],
                ['href' => vilare()->assets()->resolve('scripts/blocks.js')],
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
