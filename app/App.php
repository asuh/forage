<?php

namespace Vilare;

use Vilare\Assets\Assets;
use Vilare\Comments\Comments;
use Vilare\Core\Config;
use Vilare\Core\Hooks;
use Vilare\Setup;
use Vilare\Integrations\Integrations;
use Vilare\Blade\Templating;
use Vilare\Templates\Templates;
use Illuminate\Filesystem\Filesystem;
use Vilare\Prettify\CleanUpModule;
use Vilare\Prettify\NiceSearchModule;
use Vilare\Prettify\RelativeUrlsModule;

class App
{
    private Assets $assets;

    private Comments $comments;

    private Config $config;

    private Filesystem $filesystem;

    private Integrations $integrations;

    private Setup $setup;

    private Templates $templates;

    private Templating $templating;

    private CleanUpModule $cleanUpModule;

    private NiceSearchModule $niceSearchModule;

    private RelativeUrlsModule $relativeUrlsModule;

    private static ?App $instance = null;

    private function __construct()
    {
        // Ensure singleton is set early to avoid recursive App::get() during init
        self::$instance = $this;

        $this->assets = self::init(new Assets());
        $this->comments = self::init(new Comments());
        $this->config = self::init(new Config());
        $this->filesystem = new Filesystem();
        $this->integrations = self::init(new Integrations());
        $this->setup = self::init(new Setup());
        $this->templates = self::init(new Templates());
        $this->templating = self::init(new Templating());
        $this->widgets = self::init(new Widgets());
        $prettifyConfig = collect($this->config->prettify());
        $this->cleanUpModule = self::init(new CleanUpModule($this, $prettifyConfig));
        $this->niceSearchModule = self::init(new NiceSearchModule($this, $prettifyConfig));
        $this->relativeUrlsModule = self::init(new RelativeUrlsModule($this, $prettifyConfig));
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

    public function templating(): Templating
    {
        return $this->templating;
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
