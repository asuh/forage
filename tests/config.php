<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$root = sys_get_temp_dir() . '/forage-config-' . bin2hex(random_bytes(8));
mkdir($root . '/.vite', 0777, true);
$hotFile = $root . '/.vite/hot';
register_shutdown_function(static function () use ($root, $hotFile) {
    if (file_exists($hotFile)) {
        unlink($hotFile);
    }
    rmdir($root . '/.vite');
    rmdir($root);
});
define('FORAGE_PATH', $root);
define('ABSPATH', '/wordpress/');
define('FORAGE_ROOT', '/wp-content/themes/forage');
define('FORAGE_VERSION', '1.0');
define('FORAGE_DIST_PATH', $root . '/dist');
define('FORAGE_DIST_URI', 'https://example.test/dist');
$environment = 'local';
function wp_get_environment_type(): string { return $GLOBALS['environment']; }
function wp_upload_dir(): array { return ['basedir' => '/uploads']; }
function wp_parse_url($url) { return parse_url($url); }
function untrailingslashit($url): string { return rtrim($url, '/'); }
function esc_url_raw($url): string { return $url; }
function wp_remote_get(...$args): never { throw new RuntimeException('HMR must not probe the server.'); }
function check($condition, $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
function config(): Forage\Core\Config
{
    clearstatcache();
    return new Forage\Core\Config();
}

check(! config()->get('hmr.active'), 'Missing hot files must use production assets.');
file_put_contents($hotFile, 'http://localhost:5180');
check(config()->get('hmr.active'), 'A fresh hot file must enable HMR.');
check(config()->get('hmr.client') === 'http://localhost:5180' . FORAGE_ROOT . '/@vite/client', 'HMR must use the actual Vite port.');
touch($hotFile, time() - 60);
check(! config()->get('hmr.active'), 'Stale hot files must fall back to production assets.');
file_put_contents($hotFile, 'javascript:alert(1)');
check(! config()->get('hmr.active'), 'Invalid hot URLs must be rejected.');
file_put_contents($hotFile, 'http://localhost:5180');
touch($hotFile);
$environment = 'production';
check(! config()->get('hmr.active'), 'Production must ignore hot files.');
$environment = 'development';
define('FORAGE_HMR_HOST', 'https://dev.example.test:5173/');
check(config()->get('hmr.uri') === 'https://dev.example.test:5173', 'Explicit hosts must override the published origin.');
unlink($hotFile);
check(! config()->get('hmr.active'), 'Explicit hosts must still require a running server status file.');
echo "HMR configuration regression checks passed.\n";
