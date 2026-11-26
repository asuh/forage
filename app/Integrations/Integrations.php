<?php

namespace Forage\Integrations;

use Forage\Integrations\Vite;

class Integrations
{
    private Gravatar $gravatar;

    private IndieWeb $indieWeb;

    private ContactForm7 $contactForm7;

    public function __construct()
    {
        $this->gravatar = new Gravatar();
        $this->indieWeb = \Forage\App::init(new IndieWeb());
        $this->contactForm7 = \Forage\App::init(new ContactForm7());
    }

    public function gravatar(): Gravatar
    {
        return $this->gravatar;
    }

    public function indieWeb(): IndieWeb
    {
        return $this->indieWeb;
    }

    public function contactForm7(): ContactForm7
    {
        return $this->contactForm7;
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
