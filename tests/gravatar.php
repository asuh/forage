<?php

require dirname(__DIR__) . '/vendor/autoload.php';

define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 3600);
define('WEEK_IN_SECONDS', 604800);

$url = 'https://secure.gravatar.com/avatar/test?d=404&s=96';
$cache = [];
$events = [];
$actions = [];
$requests = 0;
$response = ['response' => ['code' => 200]];

function add_action($hook, $callback): void { $GLOBALS['actions'][$hook] = $callback; }
function get_avatar_data($user, $args): array { return ['url' => $GLOBALS['url']]; }
function wp_parse_url($url, $component) { return parse_url($url, $component); }
function get_transient($key) { return $GLOBALS['cache'][$key]['value'] ?? false; }
function set_transient($key, $value, $ttl): void { $GLOBALS['cache'][$key] = compact('value', 'ttl'); }
function wp_next_scheduled($hook, $args) { return $GLOBALS['events'][$hook][serialize($args)] ?? false; }
function wp_schedule_single_event($timestamp, $hook, $args): bool
{
    $GLOBALS['events'][$hook][serialize($args)] = $timestamp;
    return true;
}
function wp_unschedule_hook($hook): void { unset($GLOBALS['events'][$hook]); }
function wp_safe_remote_head($url, $args)
{
    $GLOBALS['requests']++;
    return $GLOBALS['response'];
}
function is_wp_error($response): bool { return $response instanceof RuntimeException; }
function wp_remote_retrieve_response_code($response): int { return $response['response']['code']; }
function check($condition, $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$gravatar = new Forage\Integrations\Gravatar();
$key = 'forage_gravatar_' . hash('sha256', $url);
check(! $gravatar->hasAvatar(1), 'A cache miss must immediately use the placeholder.');
check(! $gravatar->hasAvatar(1), 'Repeated misses must keep using the placeholder.');
check($requests === 0, 'Page rendering must never send an HTTP request.');
check(count($events['forage_refresh_gravatar']) === 1, 'Refreshes must be deduplicated.');
$actions['forage_refresh_gravatar']($url);
check($requests === 1 && $gravatar->hasAvatar(1), 'The cron callback must cache availability.');
check($cache[$key]['ttl'] === WEEK_IN_SECONDS, 'Available avatars must retain their cache lifetime.');
check($requests === 1, 'Cache hits must not send requests.');

$response = ['response' => ['code' => 404]];
$gravatar->refresh($url);
check(! $gravatar->hasAvatar(1), 'Missing avatars must use the placeholder.');
check($cache[$key]['ttl'] === 6 * HOUR_IN_SECONDS, 'Missing avatars must be cached.');
$response = new RuntimeException('timeout');
$gravatar->refresh($url);
check($cache[$key]['ttl'] === 15 * MINUTE_IN_SECONDS, 'Network failures must use a shorter retry interval.');

$requestsBefore = $requests;
$url = 'https://example.test/local-avatar.png';
check($gravatar->hasAvatar(1), 'Custom avatar providers must remain supported.');
$gravatar->refresh($url);
check($requests === $requestsBefore, 'Custom avatar URLs must not be probed.');
$url = 'javascript:alert(1)';
check(! $gravatar->hasAvatar(1), 'Invalid avatar URLs must be rejected.');
$actions['switch_theme']();
check($events === [], 'Switching themes must remove pending avatar checks.');
echo "Gravatar regression checks passed.\n";
