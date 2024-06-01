<?php

namespace FM;

use FM\Assets\Assets;
use FM\Comments\Comments;
use FM\Core\Config;
use FM\Core\Hooks;
use FM\Core\Widgets;
use FM\Integrations\Integrations;
use FM\Templates\Templates;
use Illuminate\Filesystem\Filesystem;

class App
{
    private Assets $assets;

    private Comments $comments;

    private Config $config;

    private Filesystem $filesystem;

    private Integrations $integrations;

    private Templates $templates;

    private Widgets $widgets;

    private static ?App $instance = null;

    private function __construct()
    {
        $this->assets = self::init(new Assets());
        $this->comments = self::init(new Comments());
        $this->config = self::init(new Config());
        $this->filesystem = new Filesystem();
        $this->integrations = self::init(new Integrations());
        $this->templates = self ::init(new Templates());
        $this->widgets = self::init(new Widgets());
    }

    public function assets(): Assets
    {
        return $this->assets;
    }

    public function comments(): Comments
    {
        return $this->comments;
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
