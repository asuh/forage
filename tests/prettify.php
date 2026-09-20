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
echo "Prettify regression checks passed.\n";
