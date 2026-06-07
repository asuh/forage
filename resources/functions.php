<?php // phpcs:ignore PSR1.Files.SideEffects.FoundWithSymbols

define('FORAGE_VERSION', '1.0.0');
define('FORAGE_ROOT', str_replace(ABSPATH, '/', dirname(__DIR__, 1)));
define('FORAGE_PATH', dirname(__DIR__, 1));
define('FORAGE_URI', home_url(FORAGE_ROOT));

if (! defined('FORAGE_HMR_HOST')) {
    $hmrHost = strtok($_SERVER['HTTP_HOST'] ?? 'localhost', ':');
    define('FORAGE_HMR_HOST', "http://{$hmrHost}:5173");
    unset($hmrHost);
}

define('FORAGE_HMR_URI', FORAGE_HMR_HOST . FORAGE_ROOT);
define('FORAGE_DIST_PATH', FORAGE_PATH . '/dist');
define('FORAGE_DIST_URI', FORAGE_URI . '/dist');

require_once FORAGE_PATH . '/inc/bootstrap.php';
