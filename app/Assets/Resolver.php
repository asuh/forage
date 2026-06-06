<?php

namespace Forage\Assets;

trait Resolver
{
    private array $manifest = [];

    /**
     * @action init
     */
    public function load(): void
    {
        $path = forage()->config()->get('manifest.path');

        // When HMR/dev server is active we may not have a manifest file yet.
        if (forage()->config()->get('hmr.active')) {
            return;
        }

        if (empty($path) || ! file_exists($path)) {
            wp_die('Run <code>yarn build</code> in your application root!');
        }

        $data = null;
        try {
            $data = forage()->filesystem()->get($path);
        } catch (\Exception $e) {
            $data = @file_get_contents($path);
        }

        if (! empty($data)) {
            $this->manifest = json_decode($data, true);
        }
    }

    /**
     * @filter script_loader_tag 1 3
     */
    public function module(string $tag, string $handle, string $url): string
    {
        if (
            false !== strpos($url, forage()->config()->get('hmr.uri')) ||
            false !== strpos($url, forage()->config()->get('dist.uri'))
        ) {
            $tag = str_replace('<script ', '<script type="module" ', $tag);
        }

        return $tag;
    }

    public function enqueue(string $path, array $config = []): string
    {
        $config = [
            'handle' => ! empty($config['handle'])
                ? $config['handle']
                : uniqid(),
            'src' => ! empty($path) ? $this->resolve($path) : $path,
            'deps' => ! empty($config['deps']) ? $config['deps'] : [],
            'version' => ! empty($config['version'])
                ? $config['version']
                : forage()->config()->get('version'),
            'args' => [
                'strategy' => ! empty($config['strategy'])
                    ? $config['strategy']
                    : null,
                'footer' => isset($config['footer'])
                    ? (bool) $config['footer']
                    : true,
                'media' => ! empty($config['media']) ? $config['media'] : 'all',
            ],
            'type' => ! empty($config['type']) ? $config['type'] : '',
        ];

        if (preg_match('/\.(css|scss)(\?.*)?$/', $path)) {
            $config['type'] = 'style';
        } elseif (preg_match('/\.js(\?.*)?$/', $path)) {
            $config['type'] = 'script';
        }

        if (! filter_var($path, FILTER_VALIDATE_URL)) {
            $dependencies = $this->dependencies($path);

            if (! empty($dependencies['scripts'])) {
                foreach ($dependencies['scripts'] as $index => $script) {
                    $config['deps'][] = $this->enqueue(
                        $script,
                        [
                            'handle' => "{$config["handle"]}-{$index}",
                        ]
                    );
                }
            }

            if (! empty($dependencies['styles'])) {
                foreach ($dependencies['styles'] as $index => $style) {
                    $config['deps'][] = $this->enqueue(
                        $style,
                        [
                            'handle' => "{$config["handle"]}-{$index}",
                        ]
                    );
                }
            }
        }

        switch ($config['type']) {
            case 'script':
                wp_enqueue_script(
                    $config['handle'],
                    $config['src'],
                    $config['deps'],
                    $config['version'],
                    [
                        'strategy' => $config['args']['strategy'],
                        'in_footer' => $config['args']['footer'],
                    ],
                );
                wp_set_script_translations($config['handle'], 'forage');
                break;

            case 'style':
                wp_enqueue_style(
                    $config['handle'],
                    $config['src'],
                    $config['deps'],
                    $config['version'],
                    $config['args']['media'],
                );
                break;
        }

        return $config['handle'];
    }

    public function resolve(string $path, string $type = 'url'): string
    {
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $data = $this->find($path);

        switch ($type) {
            case 'url':
                $url = ! empty($data['file'])
                    ? forage()->config()->get('dist.uri') . "/{$data["file"]}"
                    : '';

                return apply_filters(
                    'forage_assets_resolver_resolve_url',
                    $url,
                    $path,
                );

            case 'path':
                $fullpath = ! empty($data['file'])
                    ? forage()->config()->get('dist.path') . "/{$data["file"]}"
                    : '';

                return apply_filters(
                    'forage_assets_resolver_resolve_path',
                    $fullpath,
                    $path,
                );
        }

        return '';
    }

    public function manifest(): array
    {
        return $this->manifest;
    }

    public function entry(string $path): array
    {
        return $this->find($path);
    }

    public function dependencies(string $path): array
    {
        $assets = [
            'scripts' => [],
            'styles' => [],
        ];

        if (forage()->config()->get('hmr.active')) {
            return $assets;
        }

        if ($this->has($path)) {
            $assets['scripts'] = collect($this->find($path)['js'] ?? [])
                ->map(
                    fn($item) => forage()->config()->get('dist.uri') .
                        '/' .
                        $item,
                )
                ->all();

            $assets['styles'] = collect($this->find($path)['css'] ?? [])
                ->map(
                    fn($item) => forage()->config()->get('dist.uri') .
                        '/' .
                        $item,
                )
                ->all();
        }

        return $assets;
    }

    private function find(string $path): array
    {
        $path = ltrim($path, '/');

        if (! empty($this->manifest[$path])) {
            return $this->manifest[$path];
        }

        $resource_path = "resources/{$path}";

        return $this->manifest[$resource_path] ?? [];
    }

    private function has(string $path): bool
    {
        return ! empty($this->find($path));
    }
}
