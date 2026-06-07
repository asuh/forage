<?php

namespace Forage;

use Forage\Assets\Assets;
use Forage\Comments\Comments;
use Forage\Core\Cache;
use Forage\Core\Config;
use Forage\Core\Hooks;
use Forage\Setup;
use Forage\Integrations\Integrations;
use Forage\Blade\Templating;
use Forage\Prettify\CleanUpModule;
use Forage\Prettify\NiceSearchModule;
use Forage\Prettify\RelativeUrlsModule;
use Illuminate\Filesystem\Filesystem;

class App
{
    private Assets $assets;

    private Comments $comments;

    private Cache $cache;

    private Config $config;

    private Filesystem $filesystem;

    private Integrations $integrations;

    private Setup $setup;

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
        $this->cache = self::init(new Cache());
        $this->integrations = self::init(new Integrations());
        $this->setup = self::init(new Setup());
        $this->templating = self::init(new Templating());

        $prettifyConfig = collect($this->config->prettify());
        $this->cleanUpModule = new CleanUpModule($this, $prettifyConfig);
        $this->niceSearchModule = new NiceSearchModule($this, $prettifyConfig);
        $this->relativeUrlsModule = new RelativeUrlsModule($this, $prettifyConfig);
    }

    public function assets(): Assets
    {
        return $this->assets;
    }

    public function comments(): Comments
    {
        return $this->comments;
    }

    public function cache(): Cache
    {
        return $this->cache;
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

    public function templating(): Templating
    {
        return $this->templating;
    }

    private function __clone() {}

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
