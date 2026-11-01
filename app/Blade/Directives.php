<?php

namespace Forage\Blade;

use Illuminate\View\Compilers\BladeCompiler;

class Directives
{
    public function register(BladeCompiler $compiler): void
    {
        $compiler->directive('svg', fn($exp) => "<?php echo \Forage\Blade\Directives::svg({$exp}) ?>");
        $compiler->directive('image', fn($exp) => "<?php echo \Forage\Blade\Directives::image({$exp}) ?>");
    }

    public static function svg(string $name, array $args = []): string
    {
        if (empty($name)) {
            throw new \Exception('Filename not resolved.');
        }

        $path = forage()->assets()->resolve("images/{$name}.svg", 'path');

        if (empty($path)) {
            throw new \Exception(esc_html("SVG {$name} not resolved."));
        }

        if (! forage()->filesystem()->exists($path)) {
            throw new \Exception(esc_html("{$path} file not found."));
        }

        if (forage()->filesystem()->guessExtension($path) !== 'svg') {
            throw new \Exception(esc_html("{$path} is not an svg file."));
        }

        $content = forage()->filesystem()->get($path);

        if (preg_match('/<svg[^>]*\b(width|height)="([^"]+)"[^>]*\b(width|height)="([^"]+)"[^>]*>/i', $content, $matches)) { // phpcs:ignore Generic.Files.LineLength.TooLong
            $width = 0;
            $height = 0;

            if (strtolower($matches[1]) === 'width') {
                $width = floatval($matches[2]);
                $height = floatval($matches[4]);
            } else {
                $height = floatval($matches[2]);
                $width = floatval($matches[4]);
            }

            if ($width > 0 && $height > 0) {
                if ($height > $width) {
                    $args['class'] = $args['class'] . ' -aspect-vertical';
                } elseif ($width > $height) {
                    $args['class'] = $args['class'] . ' -aspect-horizontal';
                } else {
                    $args['class'] = $args['class'] . ' -aspect-square';
                }
            }
        }

        if (! empty($args['class']) && is_string($args['class'])) {
            $args['class'] = sanitize_text_field($args['class']);

            if (preg_match('/\s*class="[^"]*"\s*/', $content)) {
                $content = preg_replace(
                    '/\s*class="[^"]*"\s*/',
                    " class=\"{$args['class']}\" ",
                    $content
                );
            } else {
                $content = preg_replace(
                    '/<svg\b/',
                    "<svg class=\"{$args['class']}\"",
                    $content
                );
            }
        }

        return $content;
    }

    public static function image(string $name, array $attrs = []): string
    {
        if (empty($name)) {
            throw new \Exception('Filename not resolved.');
        }

        $path = forage()->assets()->resolve("images/{$name}", 'path');

        if (empty($path)) {
            throw new \Exception(esc_html("Image {$name} not resolved."));
        }

        if (! forage()->filesystem()->exists($path)) {
            throw new \Exception(esc_html("{$path} file not found."));
        }

        if (! in_array(wp_get_image_mime($path), ['image/png', 'image/jpeg', 'image/webp', 'image/avif'], true)) {
            throw new \Exception(esc_html("{$path} image type is not allowed."));
        }

        if (! array_key_exists('width', $attrs) || ! array_key_exists('height', $attrs)) {
            $dimensions = wp_getimagesize($path);

            if ($dimensions && $dimensions[0] > 0 && $dimensions[1] > 0) {
                [$width, $height] = $dimensions;

                // Preserve the aspect ratio when only one dimension is supplied.
                if (isset($attrs['width']) && is_numeric($attrs['width']) && $attrs['width'] > 0) {
                    $height = max(1, (int) round($height * $attrs['width'] / $width));
                } elseif (isset($attrs['height']) && is_numeric($attrs['height']) && $attrs['height'] > 0) {
                    $width = max(1, (int) round($width * $attrs['height'] / $height));
                }

                $attrs += ['width' => $width, 'height' => $height];
            }
        }

        // Do not lazy-load by default: this may be the page's main image.
        $attrs += ['alt' => '', 'class' => '', 'decoding' => 'async'];
        $html = '<img src="' . esc_url(forage()->assets()->resolve("images/{$name}")) . '"';

        foreach (['alt', 'class', 'id', 'title', 'width', 'height', 'srcset', 'sizes', 'loading', 'decoding', 'fetchpriority'] as $attribute) {
            if (! isset($attrs[$attribute]) || false === $attrs[$attribute]) {
                continue;
            }

            $html .= ' ' . $attribute . '="' . esc_attr((string) $attrs[$attribute]) . '"';
        }

        return $html . ' />';
    }
}
