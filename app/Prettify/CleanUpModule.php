<?php

namespace Forage\Prettify;

use Forage\Prettify\Document;
use Illuminate\Support\Str;

class CleanUpModule extends AbstractModule
{
    /**
     * Handle the module.
     */
    public function handle(): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this
            ->handleObscurity()
            ->handleCleanHtmlMarkup()
            ->handleDisableEmojis()
            ->handleDisableGutenbergBlockCss()
            ->handleDisableExtraRss()
            ->handleDisableRecentCommentsCss()
            ->handleDisableGalleryCss();
    }

    /**
     * Obscure and suppress WordPress information.
     */
    protected function handleObscurity(): self
    {
        if (! $this->config->get('obscurity')) {
            return $this;
        }

        foreach ([
            'adjacent_posts_rel_link_wp_head',
            'rest_output_link_wp_head',
            'rsd_link',
            'wlwmanifest_link',
            'wp_generator',
            'wp_oembed_add_discovery_links',
            'wp_oembed_add_host_js',
            'wp_shortlink_wp_head',
        ] as $hook) {
            remove_filter('wp_head', $hook);
        }

        add_filter('get_bloginfo_rss', fn ($value) => ! Str::is($value, __('Just another WordPress site', 'forage')) ? $value : '');
        add_filter('the_generator', '__return_false');

        return $this;
    }

    /**
     * Clean HTML5 markup.
     */
    protected function handleCleanHtmlMarkup(): self
    {
        if (! $this->config->get('clean-html5-markup')) {
            return $this;
        }

        $this
            ->filter('body_class', 'bodyClass')
            ->filter('language_attributes', 'languageAttributes')
            ->filter('style_loader_tag', 'cleanStylesheetLinks')
            ->filter('script_loader_tag', 'cleanScriptTags')
            ->filters([
                'get_avatar',
                'comment_id_fields',
                'post_thumbnail_html',
            ], 'removeSelfClosingTags');

        add_filter('site_icon_meta_tags', fn ($tags) => array_map([$this, 'removeSelfClosingTags'], $tags), 20);

        add_filter('nav_menu_item_id', '__return_null');
        add_filter('nav_menu_css_class', [$this, 'cleanNavMenuClasses'], 10, 2);

        return $this;
    }

    /**
     * Disable WordPress emojis.
     */
    protected function handleDisableEmojis(): self
    {
        if (! $this->config->get('disable-emojis')) {
            return $this;
        }

        add_filter('emoji_svg_url', '__return_false');
        remove_filter('wp_head', 'print_emoji_detection_script', 7);

        foreach ([
            'admin_print_scripts' => 'print_emoji_detection_script',
            'wp_print_styles' => 'print_emoji_styles',
            'admin_print_styles' => 'print_emoji_styles',
            'the_content_feed' => 'wp_staticize_emoji',
            'comment_text_rss' => 'wp_staticize_emoji',
            'wp_mail' => 'wp_staticize_emoji_for_email',
        ] as $hook => $function) {
            remove_filter($hook, $function);
        }

        return $this;
    }

    /**
     * Disable Gutenberg block library CSS.
     */
    protected function handleDisableGutenbergBlockCss(): self
    {
        if (! $this->config->get('disable-gutenberg-block-css')) {
            return $this;
        }

        add_action('wp_enqueue_scripts', function () {
            if (! $this->queryHasBlocks()) {
                wp_dequeue_style('wp-block-library');
            }
        }, 200);

        return $this;
    }

    /**
     * Determine if the current rendered query includes block content.
     */
    protected function queryHasBlocks(): bool
    {
        if (is_singular()) {
            return has_blocks();
        }

        global $wp_query;

        if (! $wp_query instanceof \WP_Query || empty($wp_query->posts)) {
            return false;
        }

        foreach ($wp_query->posts as $post) {
            if (has_blocks($post)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Disable extra RSS feeds.
     */
    protected function handleDisableExtraRss(): self
    {
        if (! $this->config->get('disable-extra-rss')) {
            return $this;
        }

        add_filter('feed_links_show_comments_feed', '__return_false');
        remove_filter('wp_head', 'feed_links_extra', 3);

        return $this;
    }

    /**
     * Disable recent comments CSS.
     */
    protected function handleDisableRecentCommentsCss(): self
    {
        if (! $this->config->get('disable-recent-comments-css')) {
            return $this;
        }

        add_filter('show_recent_comments_widget_style', '__return_false');

        return $this;
    }

    /**
     * Disable gallery CSS.
     */
    protected function handleDisableGalleryCss(): self
    {
        if (! $this->config->get('disable-gallery-css')) {
            return $this;
        }

        add_filter('use_default_gallery_style', '__return_false');

        return $this;
    }

    /**
     * Clean up output of stylesheet <link> tags.
     */
    public function cleanStylesheetLinks(string $html): string
    {
        return Document::make($html)->each(static function ($link) {
            $link->removeAttribute('type');
            $link->removeAttribute('id');

            $media = $link->getAttribute('media');

            if ($media && 'all' !== $media) {
                return;
            }

            $link->removeAttribute('media');
        })->html();
    }

    /**
     * Clean up the output of <script> tags.
     */
    public function cleanScriptTags(string $html): string
    {
        return Document::make($html)->each(static function ($script) {
            // Forage enqueues Vite ESM through WordPress, so preserve module scripts.
            if ('module' !== $script->getAttribute('type')) {
                $script->removeAttribute('type');
            }

            $script->removeAttribute('id');
        })->html();
    }

    /**
     * Add and remove body_class() classes.
     */
    public function bodyClass(array $classes, array $disallowedClasses = ['page-template-default']): array
    {
        if (is_single() || (is_page() && ! is_front_page())) {
            $slug = basename(get_permalink());

            if (! in_array($slug, $classes, true)) {
                $classes[] = $slug;
            }
        }

        if (is_front_page()) {
            $disallowedClasses[] = 'page-id-' . get_option('page_on_front');
        }

        return \Illuminate\Support\Collection::make($classes)
            ->diff($disallowedClasses)
            ->values()
            ->all();
    }

    /**
     * Clean up language_attributes() used in <html> tag.
     */
    public function languageAttributes(): string
    {
        $attributes = [];

        if (is_rtl()) {
            $attributes[] = 'dir="rtl"';
        }

        $lang = esc_attr(get_bloginfo('language'));

        if ($lang) {
            $attributes[] = "lang=\"{$lang}\"";
        }

        return implode(' ', $attributes);
    }

    /**
     * Remove self-closing tags.
     */
    public function removeSelfClosingTags(string|array $html): string|array
    {
        return str_replace(' />', '>', $html);
    }

    /**
     * Clean nav menu item classes: strip verbose core classes, normalise
     * current-* states to `active`, and add a slug-based `menu-{slug}` class.
     */
    public function cleanNavMenuClasses(array $classes, object $item): array
    {
        $slug = sanitize_title($item->title);

        $classes = preg_replace('/(current(-menu-|[-_]page[-_])(item|parent|ancestor))/', 'active', $classes);
        $classes = preg_replace('/^((menu|page)[-_\w+]+)+/', '', $classes);
        $classes = array_filter(array_unique(array_map('trim', $classes)));
        $classes[] = 'menu-item';
        $classes[] = 'menu-' . $slug;

        return array_values($classes);
    }
}
