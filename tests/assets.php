<?php

// Exercise the real resolver with isolated WordPress API doubles.
require dirname(__DIR__) . '/vendor/autoload.php';

$settings = [
    'hmr.active' => false,
    'hmr.uri' => 'http://localhost:5173',
    'dist.uri' => 'https://example.test/dist',
    'version' => '1.0',
];
$scripts = [];
$styles = [];
$assets = new Forage\Assets\Assets();

function forage(): object
{
    return new class {
        public function config(): object
        {
            return new class {
                public function get(string $key): mixed
                {
                    return $GLOBALS['settings'][$key] ?? null;
                }
            };
        }

        public function assets(): Forage\Assets\Assets
        {
            return $GLOBALS['assets'];
        }
    };
}

function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
{
    return $value;
}

function wp_enqueue_script($handle, $src, $deps, $version, $args): void
{
    $GLOBALS['scripts'][$handle] = compact('src', 'deps', 'version', 'args');
}

function wp_enqueue_style($handle, $src, $deps, $version, $media): void
{
    $GLOBALS['styles'][$handle] = compact('src', 'deps', 'version', 'media');
}

function wp_set_script_translations(string $handle, string $domain): void {}

function esc_url(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function esc_attr(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES);
}

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function output(callable $callback): string
{
    ob_start();
    $callback();
    return ob_get_clean();
}

$manifest = [
    'resources/scripts/scripts.js' => [
        'file' => 'app-123.js',
        'css' => ['app-123.css'],
        'imports' => ['_shared.js', '_other.js'],
        'dynamicImports' => ['_lazy.js'],
    ],
    'resources/scripts/blocks.js' => [
        'file' => 'blocks-123.js',
        'imports' => ['_shared.js'],
    ],
    'resources/styles/styles.css' => ['file' => 'styles-123.css'],
    '_shared.js' => [
        'file' => 'shared-123.js',
        'css' => ['shared-123.css'],
        'imports' => ['_nested.js'],
    ],
    '_nested.js' => ['file' => 'nested-123.js', 'css' => ['nested-123.css']],
    '_other.js' => ['file' => 'other-123.js', 'imports' => ['_nested.js']],
    '_lazy.js' => ['file' => 'lazy-123.js', 'css' => ['lazy-123.css']],
];
$property = new ReflectionProperty($assets, 'manifest');
$property->setValue($assets, $manifest);

$assets->enqueue('scripts/scripts.js', ['handle' => 'app', 'deps' => ['existing-script']]);
$assets->enqueue('scripts/blocks.js', ['handle' => 'blocks']);
$assets->enqueue('styles/styles.css', ['handle' => 'style']);

check($scripts['app']['deps'] === ['existing-script'], 'CSS must not become a script dependency.');
check(count($scripts) === 2, 'ESM imports must not be enqueued as additional script entries.');
check(count($styles) === 4, 'Entry and nested CSS must load once, without eager dynamic CSS.');
check(array_column($styles, 'src') === [
    'https://example.test/dist/app-123.css',
    'https://example.test/dist/nested-123.css',
    'https://example.test/dist/shared-123.css',
    'https://example.test/dist/styles-123.css',
], 'CSS traversal must preserve order and deduplicate shared imports.');
foreach ([...array_values($scripts), ...array_values($styles)] as $asset) {
    check($asset['version'] === null, 'Manifest assets must use their hashed URL without a version query.');
}

$preload = output([$assets, 'preload']);
check(str_contains($preload, 'href="' . $styles['style']['src'] . '"'), 'Stylesheet preload must match its enqueue URL.');
check(! str_contains($preload, 'crossorigin'), 'CSS preload must match the stylesheet request mode.');
$modules = output([$assets, 'modulepreload']);
foreach (['app'] as $handle) {
    check(str_contains($modules, 'href="' . $scripts[$handle]['src'] . '"'), 'Module preload must match its enqueue URL.');
}
check(! str_contains($modules, 'blocks-123.js'), 'Optional block entries must not be preloaded globally.');
check(substr_count($modules, 'shared-123.js') === 1, 'Shared module preloads must be deduplicated.');
check(str_contains($modules, 'nested-123.js'), 'Nested static imports must be preloaded.');
check(! str_contains($modules, 'lazy-123'), 'Dynamic imports must remain lazy.');

foreach ([null, false, 'custom'] as $version) {
    $assets->enqueue('scripts/blocks.js', ['handle' => 'explicit', 'version' => $version]);
    check($scripts['explicit']['version'] === $version, 'Explicit version overrides must be preserved.');
}
$assets->enqueue('https://cdn.example.test/external.js', ['handle' => 'external']);
check($scripts['external']['version'] === '1.0', 'External assets must retain the default version.');

$manifest['_nested.js']['imports'] = ['_shared.js'];
$property->setValue($assets, $manifest);
check(count($assets->dependencies('scripts/scripts.js')['styles']) === 3, 'Circular imports must terminate and deduplicate CSS.');
check(count($assets->imports('scripts/scripts.js')) === 3, 'Circular module imports must terminate.');

$settings['hmr.active'] = true;
$assets->enqueue('scripts/scripts.js', ['handle' => 'dev']);
check($scripts['dev']['version'] === '1.0', 'HMR must retain development versioning.');
check($assets->dependencies('scripts/scripts.js') === ['scripts' => [], 'styles' => []], 'Vite must manage HMR dependencies.');
check(output([$assets, 'preload']) === '', 'Production CSS preloads must be disabled in HMR.');
check(output([$assets, 'modulepreload']) === '', 'Production module preloads must be disabled in HMR.');

echo "Asset resolver regression checks passed.\n";
