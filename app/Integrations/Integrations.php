<?php

namespace Forage\Integrations;

use Forage\Integrations\Vite;

class Integrations
{
    /**
     * @action init
     */
    public function init(): void
    {
        if (forage()->config()->get('hmr.active')) {
            \Forage\App::init(new Vite());
        }
    }
}
