<?php

namespace Forage\Editor;

class Editor
{
    /**
     * Register theme-level content styles with the WordPress editors.
     *
     * @action after_setup_theme 20
     */
    public function styles(): void
    {
        $stylesheet = forage()->assets()->resolve('styles/editor.css');

        if (empty($stylesheet)) {
            return;
        }

        // WordPress reads theme-relative styles from disk and uses their URL
        // to resolve assets. Development server URLs still load over HTTP.
        $distUrl = FORAGE_DIST_URI . '/';
        if (str_starts_with($stylesheet, $distUrl)) {
            $stylesheet = 'dist/' . substr($stylesheet, strlen($distUrl));
        } elseif (forage()->config()->get('hmr.active')) {
            // WordPress fetches the stylesheet as text, without a CSS Accept header.
            $stylesheet = add_query_arg('direct', '1', $stylesheet);
        }

        add_editor_style($stylesheet);
    }
}
