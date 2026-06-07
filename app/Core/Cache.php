<?php

namespace Forage\Core;

class Cache
{
    /**
     * Ensure the theme cache directory exists.
     *
     * @action after_switch_theme
     */
    public function ensure(): void
    {
        forage()->filesystem()->ensureDirectoryExists(
            forage()->config()->get('cache.path')
        );
    }

    /**
     * Clear the theme cache directory when switching themes.
     *
     * @action switch_theme
     */
    public function clear(): void
    {
        forage()->filesystem()->deleteDirectory(
            forage()->config()->get('cache.path')
        );
    }
}
