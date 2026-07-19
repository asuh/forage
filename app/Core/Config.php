<?php

namespace Forage\Core;

class Config
{
    private array $config = [];

    private string $hmrHost = '';

    private bool $hmrActive = false;

    public function __construct()
    {
        $hmr = $this->resolveHmr();
        $this->hmrHost = $hmr['host'];
        $this->hmrActive = $hmr['active'];

        $this->config = [
            'version' => $this->isLocalEnvironment() ? time() : FORAGE_VERSION,
            'env' => [
                'type' => wp_get_environment_type(),
                'mode' =>
                    false ===
                    strpos(FORAGE_PATH, ABSPATH . 'wp-content/plugins')
                        ? 'theme'
                        : 'plugin',
            ],
            'hmr' => [
                'uri' => $this->hmrHost,
                'client' => $this->hmrHost . FORAGE_ROOT . '/@vite/client',
                'resources' => $this->hmrHost . FORAGE_ROOT . '/resources',
                'active' => $this->hmrActive,
            ],
            'manifest' => [
                'path' => FORAGE_DIST_PATH . '/manifest.json',
            ],
            'cache' => [
                'path' => wp_upload_dir()['basedir'] . '/cache/forage',
            ],
            'dist' => [
                'path' => FORAGE_DIST_PATH,
                'uri' => FORAGE_DIST_URI,
            ],
            'resources' => [
                'path' => FORAGE_PATH . '/resources',
            ],
            'views' => [
                'path' => FORAGE_PATH . '/resources/views',
            ],
        ];
    }

    public function get(string $key): mixed
    {
        $value = $this->config;

        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return null;
            }
        }

        return $value;
    }

    public function isTheme(): bool
    {
        return 'theme' === $this->get('env.mode');
    }

    public function isPlugin(): bool
    {
        return 'plugin' === $this->get('env.mode');
    }

    /**
     * Determines if the current environment is set to 'local' or 'development'.
     *
     * @return bool True if the environment is 'local' or 'development', false otherwise.
     */
    public function isLocalEnvironment(): bool
    {
        return in_array(wp_get_environment_type(), ['local', 'development'], true);
    }

    public function prettify(): array
    {
        $path = FORAGE_PATH . '/app/config/prettify.php';

        if (! file_exists($path)) {
            return [];
        }

        $data = require $path;

        return is_array($data) ? $data : [];
    }

    private function resolveHmr(): array
    {
        $hosts = $this->hmrHosts();
        $fallback = $hosts[0] ?? 'http://localhost:5173';

        if (! defined('FORAGE_HMR_HOST') && ! $this->isLocalEnvironment()) {
            return [
                'host' => $fallback,
                'active' => false,
            ];
        }

        foreach ($hosts as $host) {
            $response = wp_remote_get($host . FORAGE_ROOT . '/@vite/client', [
                'timeout' => 0.25,
                'redirection' => 0,
            ]);
            $status = wp_remote_retrieve_response_code($response);

            if (
                ! is_wp_error($response) &&
                $status >= 200 &&
                $status < 300
            ) {
                return [
                    'host' => $host,
                    'active' => true,
                ];
            }
        }

        return [
            'host' => $fallback,
            'active' => false,
        ];
    }

    private function hmrHosts(): array
    {
        if (defined('FORAGE_HMR_HOST')) {
            $host = untrailingslashit(
                esc_url_raw((string) FORAGE_HMR_HOST)
            );
            $parts = wp_parse_url($host);

            if (
                isset($parts['scheme'], $parts['host']) &&
                in_array($parts['scheme'], ['http', 'https'], true)
            ) {
                return [$host];
            }

            return [];
        }

        return ['http://localhost:5173'];
    }
}
