<?php

namespace Forage\Integrations;

class Vite
{
    /**
     * @action wp_head 1
     */
    public function client(): void
    {
        // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript
        printf(
            '<script type="module" src="%s"></script>',
            esc_attr(forage()->config()->get('hmr.client')),
        );
    }

    /**
     * @action enqueue_block_editor_assets
     */
    public function editorStyles(): void
    {
        wp_enqueue_script(
            'forage-editor-live-css',
            forage()->assets()->resolve('scripts/editor-live-css.js'),
            ['wp-editor'],
            null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
            true
        );
    }

    /**
     * @filter forage_assets_resolver_resolve_url 1 2
     */
    public function url(string $current, string $path): string
    {
        return forage()->config()->get('hmr.resources') . "/{$path}";
    }

    /**
     * @filter forage_assets_resolver_resolve_path 1 2
     */
    public function path(string $current, string $path): string
    {
        return forage()->config()->get('resources.path') . "/{$path}";
    }
}
