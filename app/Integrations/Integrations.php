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
     * @action after_setup_theme 2
     */
    public function init(): void
    {
        if (forage()->config()->get('hmr.active')) {
            \Forage\App::init(new Vite());
        }
    }
}
