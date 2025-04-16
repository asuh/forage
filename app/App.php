<?php

namespace FM;

use FM\Assets\Assets;
use FM\Comments\Comments;
use FM\Core\Config;
use FM\Core\Hooks;
use FM\Core\Widgets;
use FM\Setup;
use FM\Integrations\Integrations;
use FM\Templates\Templates;
use Illuminate\Filesystem\Filesystem;
use FM\Prettify\CleanUpModule;
use FM\Prettify\NiceSearchModule;
use FM\Prettify\RelativeUrlsModule;

class App
{
    private Assets $assets;

    private Comments $comments;

    private Config $config;

    private Filesystem $filesystem;

    private Integrations $integrations;

    private Setup $setup;

    private Templates $templates;

    private Widgets $widgets;

    private CleanUpModule $cleanUpModule;

    private NiceSearchModule $niceSearchModule;

    private RelativeUrlsModule $relativeUrlsModule;

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
        $this->widgets = self::init(new Widgets());
        $this->cleanUpModule = self::init(new CleanUpModule($this, collect()));
        $this->niceSearchModule = self::init(new NiceSearchModule($this, collect()));
        $this->relativeUrlsModule = self::init(new RelativeUrlsModule($this, collect()));
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

    public function widgets(): Widgets
    {
        return $this->widgets;
    }

    public function cleanUpModule(): CleanUpModule
    {
        return $this->cleanUpModule;
    }

    public function niceSearchModule(): NiceSearchModule
    {
        return $this->niceSearchModule;
    }

    public function relativeUrlsModule(): RelativeUrlsModule
    {
        return $this->relativeUrlsModule;
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
