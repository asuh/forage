<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$filters = [];
function add_filter($hook, $callback, ...$args): void { $GLOBALS['filters'][$hook] = $callback; }
function check($condition, $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$class = new ReflectionClass(Forage\Prettify\CleanUpModule::class);
$module = $class->newInstanceWithoutConstructor();
$config = $class->getProperty('config');
$register = $class->getMethod('handleCleanHtmlMarkup');
$defaults = require dirname(__DIR__) . '/app/config/prettify.php';
$config->setValue($module, collect($defaults['clean-up']));
$register->invoke($module);
check(! isset($filters['script_loader_tag']) && ! isset($filters['style_loader_tag']), 'Default cleanup must not parse asset tags.');
check(isset($filters['nav_menu_css_class'], $filters['body_class']), 'Navigation and body classes must remain supported.');

$defaults['clean-up']['clean-asset-tags'] = true;
$config->setValue($module, collect($defaults['clean-up']));
$register->invoke($module);
check(isset($filters['script_loader_tag'], $filters['style_loader_tag']), 'Opting in must restore asset tag cleanup.');
$script = $module->cleanScriptTags('<script type="module" id="app" src="/app.js"></script>');
check(str_contains($script, 'type="module"'), 'Opt-in cleanup must preserve module scripts.');
check(! str_contains($script, 'id="app"'), 'Opt-in cleanup must remove cosmetic IDs.');
check($module->cleanScriptTags('<script type="text/javascript" src="/a.js"></script>') === '<script src="/a.js"></script>', 'Opt-in cleanup must drop the default JavaScript type.');
foreach (['text/plain', 'application/json', 'application/ld+json', 'text/template', 'importmap'] as $type) {
    $html = "<script type=\"{$type}\" id=\"data\">{}</script>";
    check($module->cleanScriptTags($html) === $html, "Opt-in cleanup must keep the type and ID of {$type} blocks.");
}
check(str_contains($module->cleanScriptTags("<script type=\"\vtext/javascript\"></script>"), 'type='), 'Only HTML whitespace may be trimmed from script types.');
check($module->cleanScriptTags('<script type="" id="a"></script>') === '<script></script>', 'An empty type is classic JavaScript.');
check($module->cleanScriptTags('<script type=" Application/JavaScript " id="a"></script>') === '<script></script>', 'JavaScript types match case-insensitively.');
check($module->cleanScriptTags('<script type="  " id="a"></script>') === '<script type="  " id="a"></script>', 'A whitespace-only type is inert and must be kept.');
$scripts = $module->cleanScriptTags("<script src=\"/a.js\" id=\"a-js\"></script>\n<script id=\"a-js-after\">var x = \"<meta charset=windows-1252><p>é</p>\" && 1 < 2;</script>");
check($scripts === "<script src=\"/a.js\"></script>\n<script>var x = \"<meta charset=windows-1252><p>é</p>\" && 1 < 2;</script>", 'Opt-in cleanup must keep adjacent and inline scripts intact.');
$styles = $module->cleanStylesheetLinks('<link rel="stylesheet" id="x-css" href="/x.css?ver=1&amp;y=2" media="all" />');
check($styles === '<link rel="stylesheet" href="/x.css?ver=1&amp;y=2">', 'Opt-in cleanup must keep escaped stylesheet URLs.');

$actions = [];
$onDemand = false;
$sidebars = [];
$dequeued = [];
function add_action($hook, $callback, ...$args): void { $GLOBALS['actions'][$hook] = $callback; }
function wp_should_load_block_assets_on_demand(): bool { return $GLOBALS['onDemand']; }
function wp_get_sidebars_widgets(): array { return $GLOBALS['sidebars']; }
function is_singular(): bool { return true; }
function has_blocks(): bool { return false; }
function wp_dequeue_style($handle): void { $GLOBALS['dequeued'][] = $handle; }

$class->getMethod('handleDisableGutenbergBlockCss')->invoke($module);
$dequeueBlockCss = $actions['wp_enqueue_scripts'];

$dequeueBlockCss();
check($dequeued === ['wp-block-library'], 'Pages without blocks must drop block CSS before WordPress 6.9.');

$dequeued = [];
$sidebars = ['wp_inactive_widgets' => ['block-1'], 'sidebar-footer' => ['text-2']];
$dequeueBlockCss();
check($dequeued === ['wp-block-library'], 'Inactive block widgets must not keep block CSS.');

$dequeued = [];
$sidebars = ['sidebar-footer' => ['text-2', 'block-3']];
$dequeueBlockCss();
check($dequeued === [], 'Active block widgets must keep block CSS.');

$dequeued = [];
$sidebars = [];
$onDemand = true;
$dequeueBlockCss();
check($dequeued === [], 'On-demand block styles must stay under WordPress control.');

$filters = [];
$app = (new ReflectionClass(Forage\App::class))->newInstanceWithoutConstructor();
new Forage\Prettify\DisableRandomContentModule($app, collect($defaults));
check(($filters['wp_enable_random_content_redirect'] ?? null) === '__return_false', 'Random content redirects must be disabled by default, including in the admin.');
$filters = [];
new Forage\Prettify\DisableRandomContentModule($app, collect(['disable-random-content' => ['enabled' => false]]));
check(! isset($filters['wp_enable_random_content_redirect']), 'Random content redirects must stay available when the module is disabled.');

echo "Prettify regression checks passed.\n";
