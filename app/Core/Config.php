<?php

namespace FM\Core;

class Config
{
    private array $config = [];

    public function __construct()
    {
        $this->config = [
            'version' => isLocalEnvironment() ? time() : FM_VERSION,
            'env' => [
                'type' => wp_get_environment_type(),
                'mode' => false === strpos(FM_PATH, ABSPATH . 'wp-content/plugins') ? 'theme' : 'plugin',
            ],
            'hmr' => [
                'uri' => FM_HMR_HOST,
                'client' => FM_HMR_URI . '/@vite/client',
                'sources' => FM_HMR_URI . '/resources',
                'active' => isLocalEnvironment() && ! is_wp_error(wp_remote_get(FM_HMR_URI)),
            ],
            'manifest' => [
                'path' => FM_ASSETS_PATH . '/manifest.json',
            ],
            'cache' => [
                'path' => wp_upload_dir()['basedir'] . '/cache/fm',
            ],
            'resources' => [
                'path' => FM_PATH . '/resources',
            ],
            'views' => [
                'path' => FM_PATH . '/resources/views',
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
     * Checks the WP_ENVIRONMENT_TYPE constant and compares it to the current
     * environment type to identify if the environment is considered local.
     *
     * @return bool True if the environment is 'local' or 'development', false otherwise.
     */
    public function isLocalEnvironment(): bool
    {
        $env = wp_get_environment_type();
        return defined('WP_ENVIRONMENT_TYPE') && in_array($env, ['local', 'development'], true);
    }
}
