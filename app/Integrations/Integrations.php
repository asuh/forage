<?php

namespace FM\Integrations;

use FM\Integrations\Vite;

class Integrations
{
    /**
     * @action init
     */
    public function init(): void
    {
        if (fm()->config()->get('hmr.active')) {
            \FM\App::init(new Vite());
        }
    }
}
