<?php

namespace Vilare\Optimizations;

class Concatenator
{
    private ?string $cache_dir = null;
    private ?string $cache_url = null;
    private ?string $concat_url = null;
    private bool $preload_printed = false;

    /**
     * @action wp_head 1
     */
    public function concatenate(): void
    {
        if (is_admin()) {
            return;
        }

        // Check if CSS concatenation is enabled
        $optimizations = vilare()->config()->optimizations();
        if (empty($optimizations['css-concatenation']['enabled'])) {
            return;
        }

        // Skip if HMR is active (development mode)
        if (vilare()->config()->get('hmr.active')) {
            return;
        }

        // Initialize paths on first use
        $this->initPaths();

        global $wp_styles;

        $excluded = $config['css-concatenation']['exclude'] ?? [];
        $styles_to_concat = [];

        // Collect local styles
        foreach ($wp_styles->queue as $handle) {
            if (!isset($wp_styles->registered[$handle])) {
                continue;
            }

            // Skip excluded handles
            if (in_array($handle, $excluded, true)) {
                continue;
            }

            $style = $wp_styles->registered[$handle];
            $src = $style->src;

            // Skip if empty or external
            if (!$this->isLocal($src)) {
                continue;
            }

            // Skip if from HMR/Vite dev server
            if (strpos($src, 'localhost:5173') !== false) {
                continue;
            }

            $styles_to_concat[$handle] = [
                'src' => $src,
                'media' => $style->args,
                'deps' => $style->deps,
            ];
        }

        if (empty($styles_to_concat)) {
            return;
        }

        // Generate cache key from file modification times
        $cache_key = $this->getCacheKey($styles_to_concat);
        $concat_file = "{$this->cache_dir}/concat-{$cache_key}.css";
        $concat_url = "{$this->cache_url}/concat-{$cache_key}.css";

        // Create concatenated file if it doesn't exist
        if (!file_exists($concat_file)) {
            $this->clearOldCache();
            $combined = $this->combineCSS($styles_to_concat);

            if (empty($combined)) {
                return;
            }

            file_put_contents($concat_file, $combined);
        }

        // Store the concat URL
        $this->concat_url = $concat_url;

        // Output preload immediately (before wp_print_styles at priority 8)
        printf(
            '<link rel="preload" href="%s" as="style" type="text/css" />',
            esc_attr($concat_url)
        );
        echo "\n";
        $this->preload_printed = true;

        // Dequeue original styles
        foreach (array_keys($styles_to_concat) as $handle) {
            wp_dequeue_style($handle);
            wp_deregister_style($handle);
        }

        // Enqueue concatenated file
        wp_enqueue_style(
            'forage-concatenated',
            $concat_url,
            [],
            null,
            'all'
        );
    }

    /**
     * @filter vilare_assets_preload 10
     */
    public function adjustPreload(array $preloads): array
    {
        // If we already printed the preload, remove all style preloads
        if ($this->preload_printed) {
            $dist_uri = vilare()->config()->get('dist.uri');

            return array_filter($preloads, function($item) use ($dist_uri) {
                $href = $item['href'] ?? '';
                $is_style = ($item['as'] ?? '') === 'style';
                $is_from_dist = strpos($href, $dist_uri) !== false;

                // Remove style preloads from dist
                return !($is_style && $is_from_dist);
            });
        }

        return $preloads;
    }

    /**
     * Check if concatenation will be active
     */
    public function isActive(): bool
    {
        return !is_admin() && !vilare()->config()->get('hmr.active');
    }

    private function initPaths(): void
    {
        if ($this->cache_dir !== null) {
            return;
        }

        $upload_dir = wp_upload_dir();
        $this->cache_dir = vilare()->config()->get('cache.path') . '/css';
        $this->cache_url = $upload_dir['baseurl'] . '/cache/vilare/css';

        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }
    }

    private function isLocal(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $home_url = home_url();
        $abs_url = $url;

        // Convert protocol-relative URLs
        if (strpos($url, '//') === 0) {
            $abs_url = 'https:' . $url;
        }

        // Convert relative URLs
        if (strpos($url, '/') === 0) {
            $abs_url = $home_url . $url;
        }

        return strpos($abs_url, $home_url) === 0;
    }

    private function getFilePath(string $url): ?string
    {
        $url = strtok($url, '?'); // Remove query strings

        if (strpos($url, home_url()) === 0) {
            $path = str_replace(home_url(), ABSPATH, $url);
        } elseif (strpos($url, '/') === 0) {
            $path = ABSPATH . ltrim($url, '/');
        } else {
            return null;
        }

        return file_exists($path) ? $path : null;
    }

    private function combineCSS(array $styles): string
    {
        $combined = '';

        foreach ($styles as $handle => $data) {
            $file_path = $this->getFilePath($data['src']);

            if (!$file_path) {
                continue;
            }

            $css = file_get_contents($file_path);

            // Fix relative URLs in CSS
            $css = $this->fixRelativeUrls($css, $data['src']);

            $combined .= "/* {$handle} */\n{$css}\n\n";
        }

        return $combined;
    }

    private function fixRelativeUrls(string $css, string $original_url): string
    {
        $base_url = dirname($original_url);

        return preg_replace_callback(
            '/url\(["\']?(?!(?:https?:|\/\/|data:))([^"\')]+)["\']?\)/i',
            function ($matches) use ($base_url) {
                $path = trim($matches[1]);
                return "url('{$base_url}/{$path}')";
            },
            $css
        );
    }

    private function getCacheKey(array $styles): string
    {
        $mtimes = [];

        foreach ($styles as $data) {
            $file_path = $this->getFilePath($data['src']);
            if ($file_path && file_exists($file_path)) {
                $mtimes[] = filemtime($file_path);
            }
        }

        return md5(implode('-', $mtimes));
    }

    private function clearOldCache(): void
    {
        $files = glob("{$this->cache_dir}/concat-*.css");

        if (!empty($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
