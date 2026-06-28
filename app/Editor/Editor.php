<?php

namespace Forage\Editor;

class Editor
{
    /**
     * @action current_screen
     */
    public function classicEditor(\WP_Screen $screen): void
    {
        if ('post' !== $screen->base) {
            return;
        }

        if (
            method_exists($screen, 'is_block_editor') &&
            $screen->is_block_editor()
        ) {
            return;
        }

        $stylesheet = forage()->assets()->resolve('styles/editor.css');

        if (empty($stylesheet)) {
            return;
        }

        add_editor_style($stylesheet);
    }

    /**
     * @filter block_editor_settings_all
     */
    public function blockEditor(array $settings): array
    {
        $settings = $this->addEditorFontFamilies($settings);
        $stylesheet = forage()->assets()->resolve('styles/editor.css');

        if (empty($stylesheet)) {
            return $settings;
        }

        $settings['styles'][] = [
            'css' => sprintf(
                "@import url('%s');",
                esc_url($stylesheet)
            ),
        ];

        return $settings;
    }

    private function addEditorFontFamilies(array $settings): array
    {
        $fontFamilies = $this->themeFontFamilies();

        if (empty($fontFamilies)) {
            return $settings;
        }

        if (
            ! empty(
                $settings['__experimentalFeatures']['typography']['fontFamilies']['theme']
            )
        ) {
            return $settings;
        }

        $settings['__experimentalFeatures']['typography']['fontFamilies']['theme'] = $fontFamilies;

        return $settings;
    }

    private function themeFontFamilies(): array
    {
        $path = FORAGE_PATH . '/theme.json';

        if (! file_exists($path)) {
            return [];
        }

        $data = wp_json_file_decode($path, ['associative' => true]);

        if (! is_array($data)) {
            return [];
        }

        $fontFamilies = $data['settings']['typography']['fontFamilies'] ?? [];

        if (isset($fontFamilies['theme']) && is_array($fontFamilies['theme'])) {
            return $fontFamilies['theme'];
        }

        if (array_is_list($fontFamilies)) {
            return $fontFamilies;
        }

        return [];
    }
}
