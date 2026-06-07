<?php

namespace Forage\Assets;

use Forage\Assets\Resolver;

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
        if (forage()->config()->get('hmr.active')) {
            return;
        }

        $preloads = apply_filters(
            'forage_assets_preload',
            [
                [
                    'href' => forage()->assets()->resolve('styles/styles.css'),
                    'as' => 'style',
                    'type' => 'text/css',
                    'crossorigin' => true,
                ],
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

            $crossorigin_attr = ! empty($item['crossorigin']) ? ' crossorigin' : '';

            printf(
                '<link rel="preload" href="%s" as="%s" type="%s"%s />',
                esc_url($item['href']),
                esc_attr($item['as']),
                esc_attr($item['type']),
                esc_attr($crossorigin_attr),
            );
        }
    }

    /**
     * @action wp_head
     */
    public function modulepreload(): void
    {
        if (forage()->config()->get('hmr.active')) {
            return;
        }

        $entries = [
            'scripts/scripts.js',
            'scripts/blocks.js',
        ];

        $default_preloads = [];

        foreach ($entries as $entry) {
            $default_preloads[] = [
                'href' => forage()->assets()->resolve($entry),
                'crossorigin' => true,
            ];

            foreach (forage()->assets()->imports($entry) as $import) {
                $default_preloads[] = [
                    'href' => $import,
                    'crossorigin' => true,
                ];
            }
        }

        $preloads = apply_filters(
            'forage_assets_module_preload',
            $default_preloads
        );

        $printed = [];

        foreach ($preloads as $item) {
            if (empty($item['href'])) {
                continue;
            }

            if (isset($printed[$item['href']])) {
                continue;
            }

            $printed[$item['href']] = true;
            $crossorigin_attr = ! empty($item['crossorigin']) ? ' crossorigin' : '';

            printf(
                '<link rel="modulepreload" href="%s"%s />',
                esc_url($item['href']),
                esc_attr($crossorigin_attr),
            );
        }
    }
}
