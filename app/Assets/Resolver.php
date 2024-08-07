<?php

namespace FM\Assets;

trait Resolver
{
    private array $manifest = [];

    /**
     * @action wp_enqueue_scripts 1
     */
    public function load(): void
    {
        $path = fm()->config()->get('manifest.path');

        if (empty($path) || ! file_exists($path)) {
            wp_die(wp_kses_post(__('Run <code>npm run build</code> in your application root!', 'fm')));
        }

        $data = fm()->filesystem()->get($path);

        if (! empty($data)) {
            $this->manifest = json_decode($data, true);
        }
    }

    /**
     * @filter script_loader_tag 1 3
     */
    public function module(string $tag, string $handle, string $url): string
    {
        if ((false !== strpos($url, FM_HMR_HOST)) || (false !== strpos($url, FM_ASSETS_URI))) {
            $tag = str_replace('<script ', '<script type="module" ', $tag);
        }

        return $tag;
    }

    public function resolve(string $path): string
    {
        $url = '';

        if (! empty($this->manifest["resources/{$path}"])) {
            $url = FM_ASSETS_URI . "/{$this->manifest["resources/{$path}"]['file']}";
        }

        return apply_filters('fm_assets_resolver_url', $url, $path);
    }
}
