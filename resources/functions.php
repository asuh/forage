<?php // phpcs:ignore PSR1.Files.SideEffects.FoundWithSymbols

define('FORAGE_VERSION', '0.1.4');
define('FORAGE_ROOT', str_replace(ABSPATH, '/', dirname(__DIR__, 1)));
define('FORAGE_PATH', dirname(__DIR__, 1));
define('FORAGE_URI', site_url(FORAGE_ROOT));
define('FORAGE_HMR_HOST', 'http://localhost:5173');
define('FORAGE_HMR_URI', FORAGE_HMR_HOST . FORAGE_ROOT);
define('FORAGE_DIST_PATH', FORAGE_PATH . '/dist');
define('FORAGE_DIST_URI', FORAGE_URI . '/dist');

require_once FORAGE_PATH . '/inc/bootstrap.php';
