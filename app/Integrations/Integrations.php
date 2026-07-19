<?php

namespace Forage\Integrations;

use Forage\Integrations\Vite;

class Integrations
{
    private Gravatar $gravatar;

    public function __construct()
    {
        $this->gravatar = new Gravatar();
    }

    public function gravatar(): Gravatar
    {
        return $this->gravatar;
    }

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
