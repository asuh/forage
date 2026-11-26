<?php

namespace Forage\Integrations;

class IndieWeb
{
    /**
     * Let the theme place Syndication Links and Simple Location output itself.
     *
     * @action init
     */
    public function removeContentFilters(): void
    {
        if (
            class_exists('Syn_Meta') &&
            has_filter('the_content', ['Syn_Config', 'the_content'])
        ) {
            remove_filter('the_content', ['Syn_Config', 'the_content'], 30);
        }
        if (
            class_exists('Geo_Data') &&
            has_filter('the_content', ['Geo_Data', 'location_content'])
        ) {
            remove_filter('the_content', ['Geo_Data', 'location_content'], 12);
        }
    }

    /**
     * Use Forage's comment walker over the Webmention plugin's.
     *
     * @filter wp_list_comments_args
     */
    public function overrideWebmentionsWalker($args): array
    {
        if (class_exists('Webmention\Comment_Walker')) {
            $args['walker'] = new \Forage\Comments\Comments();
        }
        return $args;
    }
}
