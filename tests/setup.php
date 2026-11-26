<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$page = ['single' => false, 'page' => true, 'front' => false, 'slug' => 'learn-blade'];
function is_single(): bool { return $GLOBALS['page']['single']; }
function is_page(): bool { return $GLOBALS['page']['page']; }
function is_front_page(): bool { return $GLOBALS['page']['front']; }
function get_permalink(): string { return 'https://example.test/' . $GLOBALS['page']['slug'] . '/'; }
function check($condition, $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$setup = new Forage\Setup();
$classes = $setup->addBodyClasses(['page', 'page-template-viewstemplate-custom-blade-php']);
check(in_array('learn-blade', $classes, true), 'Slugs ending in -blade must survive template class cleanup.');
check(in_array('template-custom', $classes, true), 'Blade template classes must drop their views/ prefix and -blade suffix.');

$page['front'] = true;
check(! in_array('learn-blade', $setup->addBodyClasses(['home']), true), 'The front page must not get a slug class.');

$cleanUp = (new ReflectionClass(Forage\Prettify\CleanUpModule::class))->newInstanceWithoutConstructor();
function get_option($key): int { return 2; }
$page = ['single' => true, 'page' => false, 'front' => false, 'slug' => 'hello'];
check($cleanUp->bodyClass(['single', 'postid-1']) === ['single', 'postid-1'], 'Clean-up must not add slug classes; Setup owns them.');
check(in_array('hello', $setup->addBodyClasses(['single', 'postid-1']), true), 'Single posts must get a slug class.');
$page['slug'] = '0';
check(in_array('0', $setup->addBodyClasses(['single']), true), 'A "0" slug must keep its body class.');

echo "Setup regression checks passed.\n";
