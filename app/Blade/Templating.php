<?php

namespace Forage\Blade;

use Forage\Blade\Provider;
use Forage\Blade\Resolver;

class Templating
{
    private Provider $provider;

    private ?string $selectedTemplate = null;

    public function __construct()
    {
        \Forage\App::init(new Resolver());
        $this->provider = \Forage\App::init(new Provider());
    }

    public function selectTemplate(string $template): void
    {
        $this->selectedTemplate = $template;
    }

    public function renderSelectedTemplate(): void
    {
        if (null === $this->selectedTemplate) {
            return;
        }

        $template = $this->selectedTemplate;
        $this->selectedTemplate = null;

        $this->render($template);
    }

    public function render(string $template, array $data = []): void
    {
        $this->provider->render($template, $data);
    }

    public function generate(string $template, array $data = []): string
    {
        return $this->provider->generate($template, $data);
    }

    public function view(string $template, array $data = [])
    {
        return $this->provider->view($template, $data);
    }
}
