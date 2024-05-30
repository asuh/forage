<?php

namespace FM;

use FM\Assets\Assets;
use FM\Core\Config;
use FM\Core\Hooks;
use FM\Core\Widgets;
use FM\Integrations\Integrations;
use FM\Posts\Posts;
use FM\Teams\Teams;
use FM\Templates\Templates;
use Illuminate\Filesystem\Filesystem;

class App
{
    private Assets $assets;

    private Config $config;

    private Filesystem $filesystem;

    private Integrations $integrations;

    private Posts $posts;

    private Teams $teams;

    private Templates $templates;

    private Widgets $widgets;

    private static ?App $instance = null;

    private function __construct()
    {
        $this->assets = self::init(new Assets());
        $this->config = self::init(new Config());
        $this->filesystem = new Filesystem();
        $this->integrations = self::init(new Integrations());
        $this->posts = self::init(new Posts());
        $this->teams = self::init(new Teams());
        $this->templates = self ::init(new Templates());
        $this->widgets = self::init(new Widgets());
    }

    public function assets(): Assets
    {
        return $this->assets;
    }

    public function config(): Config
    {
        return $this->config;
    }

    public function filesystem(): Filesystem
    {
        return $this->filesystem;
    }

    public function integrations(): Integrations
    {
        return $this->integrations;
    }

    public function posts(): Posts
    {
        return $this->posts;
    }

    public function teams(): Teams
    {
        return $this->teams;
    }

    public function templates(): Templates
    {
        return $this->templates;
    }

    public function widgets(): Widgets
    {
        return $this->widgets;
    }

    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new \Exception('Cannot unserialize a singleton.');
    }

    public static function get(): App
    {
        if (empty(self::$instance)) {
            self::$instance = new App();
        }

        return self::$instance;
    }

    public static function init(object $module): object
    {
        return Hooks::init($module);
    }
}
