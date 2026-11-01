<?php

// Model WordPress's include lifecycle with real Blade rendering and PHP output buffers.
require dirname(__DIR__) . '/vendor/autoload.php';
define('FORAGE_PATH', dirname(__DIR__));

use Forage\Blade\Templating;
use Illuminate\Filesystem\Filesystem;

$root = sys_get_temp_dir() . '/forage-template-' . bin2hex(random_bytes(8));
$filesystem = new Filesystem();
$filesystem->ensureDirectoryExists($root . '/views/partials');
register_shutdown_function(static fn () => $filesystem->deleteDirectory($root));
$filesystem->put($root . '/views/page.blade.php', <<<'BLADE'
<!doctype html><html><head>@php(do_action('wp_head'))</head><body><main>@include('partials.content')</main>@php($comments = apply_filters('comments_template', '/fallback.php'))@php(include $comments)@php(do_action('wp_footer'))</body></html>
BLADE);
$filesystem->put($root . '/views/partials/content.blade.php', '<p>Page content</p>');
$filesystem->put($root . '/views/partials/comments.blade.php', '<section>Comments</section>');
$filesystem->put($root . '/views/custom.blade.php', '<main>Custom template</main>');
$filesystem->put($root . '/override.php', '<?php echo "Plugin template";');

$hooks = [];
$initializations = 0;
$isTheme = true;
$events = [];

function forage(): object
{
    return new class {
        public function filesystem(): Filesystem { return $GLOBALS['filesystem']; }
        public function templating(): Templating { return $GLOBALS['templating']; }
        public function config(): object
        {
            return new class {
                public function isTheme(): bool { return $GLOBALS['isTheme']; }
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
    };
}
function add_filter(string $name, callable $callback, int $priority = 10, int $args = 1): void
{
    $GLOBALS['hooks'][$name][$priority][] = [$callback, $args];
}
function add_action(string $name, callable $callback, int $priority = 10, int $args = 1): void
{
    add_filter($name, $callback, $priority, $args);
}
function apply_filters(string $name, mixed $value, mixed ...$args): mixed
{
    $priorities = $GLOBALS['hooks'][$name] ?? [];
    ksort($priorities);
    foreach ($priorities as $callbacks) {
        foreach ($callbacks as [$callback, $count]) {
            $value = $callback(...array_slice([$value, ...$args], 0, $count));
        }
    }
    return $value;
}
function do_action(string $name, mixed ...$args): void
{
    $priorities = $GLOBALS['hooks'][$name] ?? [];
    ksort($priorities);
    foreach ($priorities as $callbacks) {
        foreach ($callbacks as [$callback, $count]) {
            $callback(...array_slice($args, 0, $count));
        }
    }
}
function get_post(): object { return (object) ['ID' => 1]; }
function comments_open(): bool { return true; }
function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
function capture(callable $callback): string
{
    ob_start();
    $callback();
    return ob_get_clean();
}

add_action('forage_templating_provider_init', function () {
    $GLOBALS['initializations']++;
    $GLOBALS['events'][] = 'blade-init';
});
add_action('wp_head', function () {
    $GLOBALS['events'][] = 'head';
    echo '<title>Example</title>';
});
add_action('wp_footer', function () {
    $GLOBALS['events'][] = 'footer';
    echo '<style id="late-block">.block{color:red}</style>';
});
$templating = new Templating();

// WordPress 6.9+: finish template_include, start enhancement buffering, then include.
$bridge = null;
$earlyOutput = capture(function () use (&$bridge, $root) {
    $bridge = apply_filters('template_include', $root . '/views/page.blade.php');
});
check($earlyOutput === '' && $initializations === 0, 'Template selection must neither output HTML nor initialize Blade.');
check($bridge === FORAGE_PATH . '/inc/render-template.php', 'WordPress must receive an executable rendering bridge.');
check($bridge !== FORAGE_PATH . '/index.php', 'The page bridge must be separate from the blank comments fallback.');

add_action('wp_before_include_template', function () {
    $GLOBALS['events'][] = 'buffer-start';
    ob_start(function (string $html): string {
        // Stand in for core's late-style transformation; exercise the actual buffer boundary.
        $style = '<style id="late-block">.block{color:red}</style>';
        check(str_contains($html, '<!doctype html>') && str_contains($html, '</html>'), 'Enhancement buffering must receive the complete document.');
        check(str_contains($html, $style), 'Late styles must be inside the enhancement buffer.');
        return str_replace('</head>', $style . '</head>', str_replace($style, '', $html));
    });
}, 1000);

$html = capture(function () use ($bridge) {
    do_action('wp_before_include_template', $bridge);
    include $bridge;
    ob_end_flush();
});
check($events === ['buffer-start', 'blade-init', 'head', 'footer'], 'Blade and template hooks must execute after buffer startup.');
check(str_contains($html, '<style id="late-block">.block{color:red}</style></head>'), 'Buffered HTML must be available for late-style enhancement.');
check(substr_count($html, '<main>') === 1 && substr_count($html, '<section>Comments</section>') === 1, 'Nested comments must render once without repeating the page.');
check(capture(static function () use ($bridge) { include $bridge; }) === '', 'A consumed selection must not render a second time.');

// WordPress 6.8, or buffering disabled: the same bridge works with no new core hooks.
unset($hooks['wp_before_include_template']);
$bridge = apply_filters('template_include', $root . '/views/page.blade.php');
$html = capture(static function () use ($bridge) { include $bridge; });
check(str_contains($html, '<main><p>Page content</p></main>'), 'Rendering must work without enhancement buffering.');
check(substr_count($html, '<section>Comments</section>') === 1, 'Comments must also render once without buffering.');
check($initializations === 1, 'Repeated rendering must reuse the lazy Blade factory.');

$bridge = apply_filters('template_include', $root . '/views/custom.blade.php');
check($templating->generate('partials.content') === '<p>Page content</p>', 'Direct view rendering must remain available.');
check(capture(static function () use ($bridge) { include $bridge; }) === '<main>Custom template</main>', 'Direct view rendering must not replace the selected page template.');
check(apply_filters('template_include', '') === '', 'An empty template must remain empty.');
$isTheme = false;
check(apply_filters('template_include', '/plugin-template.php') === '/plugin-template.php', 'Plugin mode must preserve native template selection.');
$isTheme = true;

// A later WordPress filter can replace the bridge without Forage having emitted a page.
add_filter('template_include', fn () => $root . '/override.php', 100);
$html = capture(function () use ($root) {
    $template = apply_filters('template_include', $root . '/views/page.blade.php');
    include $template;
});
check($html === 'Plugin template', 'Later template overrides must not receive premature Blade output.');

echo "Template lifecycle regression checks passed.\n";
