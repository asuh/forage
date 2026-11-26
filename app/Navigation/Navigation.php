<?php

namespace Forage\Navigation;

class Navigation
{
    /**
     * Register navigation menus
     * @link https://developer.wordpress.org/reference/functions/register_nav_menus/
     *
     * @action after_setup_theme
     */
    public function addNavMenus(): void
    {
        register_nav_menus(
            [
                'primary_navigation' => __('Primary Navigation', 'forage'),
            /** 'secondary_navigation' => __('Secondary Navigation') */
            ]
        );
    }

    /**
     * Add disclosure buttons to primary navigation items that have submenus.
     *
     * @filter walker_nav_menu_start_el
     */
    public function addSubmenuToggle(string $output, object $item, int $depth, ?object $args): string
    {
        $maxDepth = (int) ($args->depth ?? 0);

        if (
            'primary_navigation' !== ($args->theme_location ?? '') ||
            ! in_array('menu-item-has-children', (array) $item->classes, true) ||
            $maxDepth < 0 ||
            ($maxDepth > 0 && $depth + 1 >= $maxDepth)
        ) {
            return $output;
        }

        $chevron = '<svg width="10" height="6" viewBox="0 0 10 6" focusable="false" aria-hidden="true">' .
            '<path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.5"/>' .
        '</svg>';

        $title = apply_filters('the_title', $item->title, $item->ID);
        $title = apply_filters('nav_menu_item_title', $title, $item, $args, $depth);

        if (in_array(trim((string) $item->url), ['', '#'], true)) {
            return $args->before .
                '<button class="submenu-toggle" type="button" aria-expanded="true">' .
                    $args->link_before . $title . $args->link_after . $chevron .
                '</button>' .
                $args->after;
        }

        $label = sprintf(
            /* translators: %s: Parent menu item title. */
            __('%s submenu', 'forage'),
            wp_strip_all_tags($title),
        );

        return $output . sprintf(
            '<button class="submenu-toggle" type="button" aria-expanded="true" hidden>' .
                '<span class="visuallyhidden">%s</span>%s' .
            '</button>',
            esc_html($label),
            $chevron,
        );
    }
}
