<?php

namespace Forage\Integrations;

class Gravatar
{
    private const HOST = 'secure.gravatar.com';

    public function hasAvatar(int $userId, int $size = 96): bool
    {
        $data = get_avatar_data($userId, [
            'default' => '404',
            'size' => $size,
        ]);
        $url = $data['url'] ?? false;

        if (! is_string($url) || ! wp_http_validate_url($url)) {
            return false;
        }

        if (self::HOST !== wp_parse_url($url, PHP_URL_HOST)) {
            return true;
        }

        $cacheKey = 'forage_gravatar_' . hash('sha256', $url);
        $cached = get_transient($cacheKey);

        if ('available' === $cached) {
            return true;
        }

        if ('missing' === $cached) {
            return false;
        }

        $response = wp_safe_remote_head($url, [
            'redirection' => 2,
            'timeout' => 1,
        ]);

        if (is_wp_error($response)) {
            set_transient($cacheKey, 'missing', 15 * MINUTE_IN_SECONDS);
            return false;
        }

        $status = wp_remote_retrieve_response_code($response);
        $available = $status >= 200 && $status < 300;

        set_transient(
            $cacheKey,
            $available ? 'available' : 'missing',
            $available ? WEEK_IN_SECONDS : 6 * HOUR_IN_SECONDS,
        );

        return $available;
    }
}
