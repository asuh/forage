<?php

require dirname(__DIR__) . '/vendor/autoload.php';

function __($text, $domain = ''): string { return $text; }
function apply_filters($hook, $value, ...$args) { return 'nav_menu_item_title' === $hook && 'Team' === $value ? 'Équipe' : $value; }
function wp_strip_all_tags($text): string { return strip_tags($text); }
function esc_html($text): string { return htmlspecialchars($text, ENT_QUOTES); }
function check($condition, $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$navigation = new Forage\Navigation\Navigation();
$primary = (object) ['theme_location' => 'primary_navigation', 'before' => '', 'after' => '', 'link_before' => '', 'link_after' => ''];
$parent = (object) ['ID' => 1, 'title' => '<em>About</em> & Team', 'url' => '/about', 'classes' => ['menu-item', 'menu-item-has-children']];
$label = (object) ['ID' => 2, 'title' => 'Lab Work', 'url' => '#', 'classes' => ['menu-item', 'menu-item-has-children']];
$leaf = (object) ['ID' => 3, 'title' => 'Contact', 'url' => '/contact', 'classes' => ['menu-item']];

$html = $navigation->addSubmenuToggle('<a href="/about">About</a>', $parent, 0, $primary);
check(str_starts_with($html, '<a href="/about">About</a><button '), 'A parent with a URL must keep its link and add a toggle after it.');
check(str_contains($html, 'aria-expanded="true" hidden>'), 'The separate toggle must stay hidden until the script runs.');
check(str_contains($html, '>About &amp; Team submenu</span>'), 'The separate toggle must name its submenu with an escaped, plain-text title.');

$html = $navigation->addSubmenuToggle('<a href="#">Lab Work</a>', $label, 0, $primary);
check(! str_contains($html, '<a'), 'A "#" parent must not render as a link.');
check(str_starts_with($html, '<button class="submenu-toggle" type="button" aria-expanded="true">Lab Work<svg'), 'A "#" parent must become a visible button labelled by its title.');

check($navigation->addSubmenuToggle('<a>Contact</a>', $leaf, 0, $primary) === '<a>Contact</a>', 'Items without submenus must not get a toggle.');
check($navigation->addSubmenuToggle('<a>About</a>', $parent, 0, (object) ['theme_location' => 'footer']) === '<a>About</a>', 'Other menus must not get a toggle.');

$empty = (object) (['url' => ''] + (array) $label);
check(str_starts_with($navigation->addSubmenuToggle('<a>Lab Work</a>', $empty, 0, $primary), '<button '), 'A parent with an empty URL must become a button.');

$html = $navigation->addSubmenuToggle('<a href="/about">About</a>', $parent, 0, $primary);
check(str_ends_with($html, '</button>'), 'The toggle must end the item output so it sits right before the submenu.');

$limited = (object) (['depth' => 1] + (array) $primary);
check($navigation->addSubmenuToggle('<a href="#">Lab Work</a>', $label, 0, $limited) === '<a href="#">Lab Work</a>', 'Menus limited to one level must not get toggles for submenus they never render.');
check(str_contains($navigation->addSubmenuToggle('<a>About</a>', $parent, 0, (object) (['depth' => 2] + (array) $primary)), '<button '), 'Parents whose submenu renders within the depth limit must keep their toggle.');
check($navigation->addSubmenuToggle('<a href="#">Lab Work</a>', $label, 0, (object) (['depth' => -1] + (array) $primary)) === '<a href="#">Lab Work</a>', 'Flat menus must not get toggles.');
check($navigation->addSubmenuToggle('<a>About</a>', $parent, 0, null) === '<a>About</a>', 'Missing menu arguments must not break rendering.');

$team = (object) ['ID' => 4, 'title' => 'Team', 'url' => '/team', 'classes' => ['menu-item', 'menu-item-has-children']];
check(str_contains($navigation->addSubmenuToggle('<a>Équipe</a>', $team, 0, $primary), '>Équipe submenu</span>'), 'The toggle name must use the filtered title shown on the link.');

echo "Navigation regression checks passed.\n";
