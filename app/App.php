<?php

namespace Vilare;

use Vilare\Assets\Assets;
use Vilare\Comments\Comments;
use Vilare\Core\Config;
use Vilare\Core\Hooks;
use Vilare\Setup;
use Vilare\Integrations\Integrations;
use Vilare\Templates\Templates;
use Illuminate\Filesystem\Filesystem;

class App
{
    private Assets $assets;

    private Comments $comments;

    private Config $config;

    private Filesystem $filesystem;

    private Integrations $integrations;

    private Setup $setup;

    private Templates $templates;

    private static ?App $instance = null;

    private function __construct()
    {
        $this->assets = self::init(new Assets());
        $this->comments = self::init(new Comments());
        $this->config = self::init(new Config());
        $this->filesystem = new Filesystem();
        $this->integrations = self::init(new Integrations());
        $this->setup = self::init(new Setup());
        $this->templates = self::init(new Templates());
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

    public function setup(): Setup
    {
        return $this->setup;
    }

    public function templates(): Templates
    {
        return $this->templates;
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
