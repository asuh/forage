<?php

namespace Forage\Integrations;

class Gravatar
{
    private const HOST = 'secure.gravatar.com';

    private const REFRESH_HOOK = 'forage_refresh_gravatar';

    public function __construct()
    {
        add_action(self::REFRESH_HOOK, [$this, 'refresh']);
        add_action('switch_theme', [$this, 'clearScheduled']);
    }

    public function hasAvatar(int $userId, int $size = 96): bool
    {
        $data = get_avatar_data($userId, [
            'default' => '404',
            'size' => $size,
        ]);
        $url = $data['url'] ?? false;

        // URL validation here must not perform DNS or HTTP requests during rendering.
        if (
            ! is_string($url) ||
            ! filter_var($url, FILTER_VALIDATE_URL) ||
            ! in_array(wp_parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
        ) {
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

        $args = [$url];

        if (! wp_next_scheduled(self::REFRESH_HOOK, $args)) {
            wp_schedule_single_event(time(), self::REFRESH_HOOK, $args);
        }

        return false;
    }

    public function refresh(string $url): void
    {
        if (self::HOST !== wp_parse_url($url, PHP_URL_HOST)) {
            return;
        }

        $cacheKey = 'forage_gravatar_' . hash('sha256', $url);

        $response = wp_safe_remote_head($url, [
            'redirection' => 2,
            'timeout' => 1,
        ]);

        if (is_wp_error($response)) {
            set_transient($cacheKey, 'missing', 15 * MINUTE_IN_SECONDS);
            return;
        }

        $status = wp_remote_retrieve_response_code($response);
        $available = $status >= 200 && $status < 300;

        set_transient(
            $cacheKey,
            $available ? 'available' : 'missing',
            $available ? WEEK_IN_SECONDS : 6 * HOUR_IN_SECONDS,
        );
    }

    public function clearScheduled(): void
    {
        wp_unschedule_hook(self::REFRESH_HOOK);
    }
}
