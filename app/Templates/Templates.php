<?php

namespace Vilare\Templates;

class Templates
{
    private array $templates = [];

    public function has(string $template): bool
    {
        return isset($this->templates[$template]);
    }

    public function get(string $template): Template
    {
        return $this->templates[$template];
    }

    /**
     * @filter theme_templates
     */
    public function add(array $templates): array
    {
        if (empty($this->templates)) {
            return $templates;
        }

        foreach ($this->templates as $template) {
            $templates[$template->getId()] = $template->getTitle();
        }

        asort($templates);

        return $templates;
    }
}
