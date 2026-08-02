<?php // phpcs:ignore PSR1.Files.SideEffects.FoundWithSymbols

define('FORAGE_VERSION', '1.0.0');
define('FORAGE_PATH', __DIR__);
define('FORAGE_ROOT', str_replace(ABSPATH, '/', FORAGE_PATH));
define('FORAGE_URI', get_template_directory_uri());

define('FORAGE_DIST_PATH', FORAGE_PATH . '/dist');
define('FORAGE_DIST_URI', FORAGE_URI . '/dist');

require_once FORAGE_PATH . '/inc/bootstrap.php';
