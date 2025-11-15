<?php // phpcs:ignore PSR1.Files.SideEffects.FoundWithSymbols

define('VILARE_VERSION', '0.1.4');
define('VILARE_ROOT', str_replace(ABSPATH, '/', __DIR__));
define('VILARE_PATH', __DIR__);
define('VILARE_URI', site_url(VILARE_ROOT));
define('VILARE_HMR_HOST', 'http://localhost:5173');
define('VILARE_HMR_URI', VILARE_HMR_HOST . VILARE_ROOT);
define('VILARE_DIST_PATH', VILARE_PATH . '/dist');
define('VILARE_DIST_URI', VILARE_URI . '/dist');
