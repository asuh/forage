<?php

namespace Vilare\Optimizations;

class JsConcatenator
{
    private ?string $cache_dir = null;
    private ?string $cache_url = null;
    private ?string $concat_url = null;
    private bool $modulepreload_printed = false;

    /**
     * @action wp_footer 1
     */
    public function concatenate(): void
    {
        if (is_admin()) {
            return;
        }

        // Check if JS concatenation is enabled
        $optimizations = vilare()->config()->optimizations();
        if (empty($optimizations['js-concatenation']['enabled'])) {
            return;
        }

        // Skip if HMR is active (development mode)
        if (vilare()->config()->get('hmr.active')) {
            return;
        }

        // Initialize paths on first use
        $this->initPaths();

        global $wp_scripts;

        $excluded = $optimizations['js-concatenation']['exclude'] ?? [];
        $scripts_to_concat = [];

        // Collect local scripts that are in the footer
        foreach ($wp_scripts->queue as $handle) {
            if (!isset($wp_scripts->registered[$handle])) {
                continue;
            }

            $script = $wp_scripts->registered[$handle];
            $src = $script->src;

            // Skip excluded handles
            if (in_array($handle, $excluded, true)) {
                continue;
            }

            // Skip if empty or external
            if (!$this->isLocal($src)) {
                continue;
            }

            // Skip if from HMR/Vite dev server
            if (strpos($src, 'localhost:5173') !== false) {
                continue;
            }

            // Only concatenate footer scripts
            $extra = $wp_scripts->registered[$handle]->extra;
            if (empty($extra['group']) || $extra['group'] != 1) {
                continue;
            }

            $scripts_to_concat[$handle] = [
                'src' => $src,
                'deps' => $script->deps,
            ];
        }

        if (empty($scripts_to_concat)) {
            return;
        }

        // Generate cache key from file modification times
        $cache_key = $this->getCacheKey($scripts_to_concat);
        $concat_file = "{$this->cache_dir}/concat-{$cache_key}.js";
        $concat_url = "{$this->cache_url}/concat-{$cache_key}.js";

        // Create concatenated file if it doesn't exist
        if (!file_exists($concat_file)) {
            $this->clearOldCache();
            $combined = $this->combineJS($scripts_to_concat);

            if (empty($combined)) {
                return;
            }

            file_put_contents($concat_file, $combined);
        }

        // Store the concat URL
        $this->concat_url = $concat_url;

        // Dequeue original scripts
        foreach (array_keys($scripts_to_concat) as $handle) {
            wp_dequeue_script($handle);
            wp_deregister_script($handle);
        }

        // Enqueue concatenated file
        wp_enqueue_script(
            'forage-concatenated-js',
            $concat_url,
            [],
            null,
            ['in_footer' => true]
        );
    }

    /**
     * @action wp_head 1
     */
    public function outputModulePreload(): void
    {
        if (is_admin()) {
            return;
        }

        // Check if JS concatenation is enabled
        $optimizations = vilare()->config()->optimizations();
        if (empty($optimizations['js-concatenation']['enabled'])) {
            return;
        }

        // Skip if HMR is active
        if (vilare()->config()->get('hmr.active')) {
            return;
        }

        // Calculate what the concat URL will be
        $this->initPaths();

        global $wp_scripts;

        $excluded = $optimizations['js-concatenation']['exclude'] ?? [];
        $scripts_to_concat = [];

        foreach ($wp_scripts->queue as $handle) {
            if (!isset($wp_scripts->registered[$handle])) {
                continue;
            }

            $script = $wp_scripts->registered[$handle];
            $src = $script->src;

            if (in_array($handle, $excluded, true)) {
                continue;
            }

            if (!$this->isLocal($src)) {
                continue;
            }

            if (strpos($src, 'localhost:5173') !== false) {
                continue;
            }

            $extra = $wp_scripts->registered[$handle]->extra;
            if (empty($extra['group']) || $extra['group'] != 1) {
                continue;
            }

            $scripts_to_concat[$handle] = [
                'src' => $src,
                'deps' => $script->deps,
            ];
        }

        if (empty($scripts_to_concat)) {
            return;
        }

        $cache_key = $this->getCacheKey($scripts_to_concat);
        $concat_url = "{$this->cache_url}/concat-{$cache_key}.js";

        // Output modulepreload
        printf(
            '<link rel="modulepreload" href="%s" />',
            esc_attr($concat_url)
        );
        echo "\n";
        $this->modulepreload_printed = true;
    }

    /**
     * @filter vilare_assets_module_preload 10
     */
    public function adjustModulePreload(array $preloads): array
    {
        // If we're concatenating, remove preloads from dist
        if ($this->modulepreload_printed) {
            $dist_uri = vilare()->config()->get('dist.uri');

            return array_filter($preloads, function($item) use ($dist_uri) {
                $href = $item['href'] ?? '';
                return strpos($href, $dist_uri) === false;
            });
        }

        return $preloads;
    }

    /**
     * Check if concatenation will be active
     */
    public function isActive(): bool
    {
        $optimizations = vilare()->config()->optimizations();
        return !is_admin()
            && !vilare()->config()->get('hmr.active')
            && !empty($optimizations['js-concatenation']['enabled']);
    }

    private function initPaths(): void
    {
        if ($this->cache_dir !== null) {
            return;
        }

        $upload_dir = wp_upload_dir();
        $this->cache_dir = vilare()->config()->get('cache.path') . '/js';
        $this->cache_url = $upload_dir['baseurl'] . '/cache/vilare/js';

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

    private function combineJS(array $scripts): string
    {
        $combined = '';

        foreach ($scripts as $handle => $data) {
            $file_path = $this->getFilePath($data['src']);

            if (!$file_path) {
                continue;
            }

            $js = file_get_contents($file_path);

            // Add semicolon safety and handle comment
            $combined .= "/* {$handle} */\n{$js}\n;\n\n";
        }

        return $combined;
    }

    private function getCacheKey(array $scripts): string
    {
        $mtimes = [];

        foreach ($scripts as $data) {
            $file_path = $this->getFilePath($data['src']);
            if ($file_path && file_exists($file_path)) {
                $mtimes[] = filemtime($file_path);
            }
        }

        return md5(implode('-', $mtimes));
    }

    private function clearOldCache(): void
    {
        $files = glob("{$this->cache_dir}/concat-*.js");

        if (!empty($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
}
