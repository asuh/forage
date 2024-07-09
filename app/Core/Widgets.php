<?php

namespace FM\Core;

class Widgets
{
    /**
     * Register the theme sidebars.
     *
     * @action widgets_init
     */
    public function register_sidebars(): void
    {
        $config = [
            'before_widget' => '<section class="widget %1$s %2$s">',
            'after_widget' => '</section>',
            'before_title' => '<h3>',
            'after_title' => '</h3>'
        ];

        register_sidebar([
            'name' => __('Primary'),
            'id' => 'sidebar-primary'
        ] + $config);

        register_sidebar([
            'name' => __('Footer'),
            'id' => 'sidebar-footer'
        ] + $config);
    }

    /**
     * @action get_sidebar
     */
    public function addLinks(): void
    {
        // Widget code goes here.
    }
}
