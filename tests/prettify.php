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

echo "Prettify regression checks passed.\n";
