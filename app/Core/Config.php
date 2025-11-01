<?php

namespace Vilare\Core;

class Config
{
    private array $config = [];

    public function __construct()
    {
        $this->config = [
            'version' => $this->isLocalEnvironment() ? time() : VILARE_VERSION,
            'env' => [
                'type' => wp_get_environment_type(),
                'mode' =>
                    false ===
                    strpos(VILARE_PATH, ABSPATH . 'wp-content/plugins')
                        ? 'theme'
                        : 'plugin',
            ],
            'hmr' => [
                'uri' => VILARE_HMR_HOST,
                'client' => VILARE_HMR_URI . '/@vite/client',
                'sources' => VILARE_HMR_URI . '/resources',
                'active' =>
                    $this->isLocalEnvironment() &&
                    ! is_wp_error(wp_remote_get(VILARE_HMR_URI)),
            ],
            'manifest' => [
                'path' => VILARE_DIST_PATH . '/manifest.json',
            ],
            'cache' => [
                'path' => wp_upload_dir()['basedir'] . '/cache/vilare',
            ],
            'dist' => [
                'path' => VILARE_DIST_PATH,
                'uri' => VILARE_DIST_URI,
            ],
            'resources' => [
                'path' => VILARE_PATH . '/resources',
            ],
            'views' => [
                'path' => VILARE_PATH . '/resources/views',
            ],
        ];
    }

    public function get(string $key): mixed
    {
        $value = $this->config;

        foreach (explode('.', $key) as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
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
        $env = wp_get_environment_type();
        return defined('WP_ENVIRONMENT_TYPE') &&
            in_array($env, ['local', 'development'], true);
    }
}
