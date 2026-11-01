<?php

// Exercise real Blade compilation and image output without a WordPress installation.
require dirname(__DIR__) . '/vendor/autoload.php';

use Forage\Blade\Directives;
use Forage\Blade\Provider;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\Filesystem\Filesystem;

$root = sys_get_temp_dir() . '/forage-blade-' . bin2hex(random_bytes(8));
$filesystem = new Filesystem();
$filesystem->ensureDirectoryExists($root . '/views/forms');
$filesystem->ensureDirectoryExists($root . '/extensions');
register_shutdown_function(static fn () => $filesystem->deleteDirectory($root));
$filesystem->put($root . '/views/page.blade.php', "Hello {{ \$name }} @uppercase('ready') @include('extensions::badge')");
$filesystem->put($root . '/extensions/badge.blade.php', '<strong>Nested view</strong>');
$filesystem->put($root . '/views/forms/search.blade.php', '<form>Search</form>');
$filesystem->put($root . '/image.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII='));

$initializations = 0;
$imageReads = 0;
$assetOrigin = 'https://example.test/dist';
$filters = [];

function forage(): object
{
    return new class {
        public function filesystem(): Filesystem { return $GLOBALS['filesystem']; }
        public function config(): object
        {
            return new class {
                public function get(string $key): string
                {
                    return match ($key) {
                        'views.path' => $GLOBALS['root'] . '/views',
                        'cache.path' => $GLOBALS['root'] . '/cache',
                        default => throw new RuntimeException('Unexpected configuration key.'),
                    };
                }
            };
        }
        public function assets(): object
        {
            return new class {
                public function resolve(string $name, string $type = 'url'): string
                {
                    return 'path' === $type
                        ? $GLOBALS['root'] . '/image.png'
                        : $GLOBALS['assetOrigin'] . '/image.png';
                }
            };
        }
    };
}

function add_action(...$args): never { throw new RuntimeException('Blade must not initialize on a WordPress lifecycle hook.'); }
function add_filter(string $hook, callable $callback): void { $GLOBALS['filters'][$hook] = $callback; }
function do_action(string $hook, ...$args): void
{
    if ('forage_templating_provider_init' !== $hook) {
        return;
    }

    $GLOBALS['initializations']++;
    check(Container::getInstance()->make(Factory::class) instanceof Factory, 'The factory must be bound before extensions run.');
    [$compiler, $finder] = $args;
    $compiler->directive('uppercase', fn ($exp) => "<?php echo strtoupper({$exp}); ?>");
    $finder->addNamespace('extensions', $GLOBALS['root'] . '/extensions');
}
function wp_getimagesize(string $path): array|false
{
    $GLOBALS['imageReads']++;
    return getimagesize($path);
}
function wp_get_image_mime(string $path): string|false { return getimagesize($path)['mime'] ?? false; }
function esc_attr(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_html(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function esc_url(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
function imageAttributes(array $attrs = []): DOMElement
{
    $document = new DOMDocument();
    $document->loadHTML(Directives::image('image.png', $attrs));
    return $document->getElementsByTagName('img')->item(0);
}

$provider = new Provider();
check($initializations === 0 && ! is_dir($root . '/cache'), 'Construction must not initialize or compile Blade.');
check($provider->templateExists('page'), 'Template discovery must still work before initialization.');
check($provider->filterCommentsTemplate('/fallback.php') === '/fallback.php', 'Missing views must preserve fallback behavior.');
check($initializations === 0, 'Discovery and missing-template fallbacks must not initialize Blade.');
$html = $provider->generate('page', ['name' => '<Reader>']);
check(str_contains($html, 'Hello &lt;Reader&gt; READY <strong>Nested view</strong>'), 'First render must support escaping, custom directives, and nested views.');
check($initializations === 1, 'First render must initialize Blade exactly once.');
check($provider->generate($root . '/views/page.blade.php', ['name' => 'Path']) === 'Hello Path READY <strong>Nested view</strong>', 'Absolute template paths must render.');
check(($filters['get_search_form'])('fallback') === '<form>Search</form>', 'Registered search filters must use the same factory.');
check($initializations === 1, 'Subsequent renders must reuse the initialized factory.');

$image = imageAttributes();
check($image->getAttribute('width') === '1' && $image->getAttribute('height') === '1', 'Images must include intrinsic dimensions.');
check($image->getAttribute('decoding') === 'async' && ! $image->hasAttribute('loading'), 'Default images must decode asynchronously without forcing lazy loading.');
check(imageAttributes(['width' => 48])->getAttribute('height') === '48', 'A width override must preserve the aspect ratio.');
check(imageAttributes(['height' => 24])->getAttribute('width') === '24', 'A height override must preserve the aspect ratio.');
$reads = $imageReads;
$attrs = [
    'alt' => 'A "quoted" <image> & caption',
    'class' => 'photo" onload="alert(1)',
    'width' => 640,
    'height' => 320,
    'srcset' => 'https://example.test/small.png 320w, https://example.test/large.png 640w',
    'sizes' => '(max-width: 640px) 100vw, 640px',
    'loading' => 'eager',
    'fetchpriority' => 'high',
    'decoding' => false,
    'onerror' => 'alert(1)',
    'src' => 'https://unwanted.test/image.png',
];
$image = imageAttributes($attrs);
foreach (['alt', 'class', 'width', 'height', 'srcset', 'sizes', 'loading', 'fetchpriority'] as $attribute) {
    check($image->getAttribute($attribute) === (string) $attrs[$attribute], "Image attribute {$attribute} must survive HTML escaping.");
}
check($imageReads === $reads, 'Explicit dimensions must avoid reading image metadata.');
check(! $image->hasAttribute('onload') && ! $image->hasAttribute('onerror'), 'Attributes must not inject event handlers.');
check(! $image->hasAttribute('decoding'), 'False must omit optional attributes.');
check($image->getAttribute('src') === $assetOrigin . '/image.png', 'The resolver must own the image URL.');
$assetOrigin = 'http://localhost:5173/resources/images';
check(imageAttributes(['loading' => 'lazy'])->getAttribute('src') === $assetOrigin . '/image.png', 'Image output must also accept development asset URLs.');

echo "Blade and image regression checks passed.\n";
