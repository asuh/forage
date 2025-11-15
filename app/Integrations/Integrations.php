<?php

namespace Vilare\Integrations;

use Vilare\Integrations\Vite;

class Integrations
{
    /**
     * @action init
     */
    public function init(): void
    {
        if (vilare()->config()->get('hmr.active')) {
            \Vilare\App::init(new Vite());
        }
    }
}
