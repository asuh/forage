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

        add_editor_style($stylesheet);
    }
}
