<?php

namespace Forage\Core;

class Config
{
    private array $config = [];

    private string $hmrHost = '';

    public function __construct()
    {
        $this->hmrHost = $this->resolveHmrHost();

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
                'active' => $this->isHmrActive(),
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

    private function isHmrActive(): bool
    {
        if (empty($this->hmrHost)) {
            return false;
        }

        $response = wp_remote_get($this->hmrHost . FORAGE_ROOT, [
            'timeout' => 0.25,
        ]);

        return ! is_wp_error($response);
    }

    private function resolveHmrHost(): string
    {
        foreach ($this->hmrHosts() as $host) {
            $response = wp_remote_get($host . FORAGE_ROOT, [
                'timeout' => 0.25,
            ]);

            if (! is_wp_error($response)) {
                return $host;
            }
        }

        return $this->hmrHosts()[0];
    }

    private function hmrHosts(): array
    {
        if (defined('FORAGE_HMR_HOST')) {
            return [untrailingslashit(FORAGE_HMR_HOST)];
        }

        $httpHost = sanitize_text_field(
            wp_unslash($_SERVER['HTTP_HOST'] ?? 'localhost')
        );
        $httpHost = strtok($httpHost, ':');

        return array_values(
            array_unique(
                [
                    'http://localhost:5173',
                    "http://{$httpHost}:5173",
                ]
            )
        );
    }
}
