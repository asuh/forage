<?php

namespace Forage\Prettify;

class DisableRandomContentModule extends AbstractModule
{
    /**
     * Run in the admin too, so rewrite rules flushed there omit the random routes.
     */
    protected function enabled(): bool
    {
        return (bool) $this->config->get('enabled', false);
    }

    /**
     * Handle the module.
     */
    public function handle(): void
    {
        if (! $this->enabled()) {
            return;
        }

        add_filter('wp_enable_random_content_redirect', '__return_false');
    }
}
