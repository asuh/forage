<?php

namespace Vilare\Templates;

use Vilare\Templates\Provider;
use Vilare\Templates\Resolver;

class Templates
{
    private Provider $provider;

    private Resolver $resolver;

    public function __construct()
    {
        $this->provider = \Vilare\App::init(new Provider());
        $this->resolver = \Vilare\App::init(new Resolver());
    }

    public function render(string $template, array $data = []): void
    {
        $this->provider->render($template, $data);
    }

    public function generate(string $template, array $data = []): string
    {
        return $this->provider->generate($template, $data);
    }
}
