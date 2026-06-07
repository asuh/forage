<?php

namespace Forage\Comments;

/**
 * Converts a timestamp to a compact relative time label.
 */
function timeAgo(string $timestamp): string
{
    $currentTime = time();
    $timestampDate = strtotime($timestamp);

    if (false === $timestampDate) {
        return '';
    }

    $timeDiff = $currentTime - $timestampDate;
    $intervals = [
        'mo' => 2592000,
        'd' => 86400,
        'h' => 3600,
        'm' => 60,
    ];

    if ($timeDiff >= 31536000) {
        return wp_date('m/d/Y', $timestampDate);
    }

    if ($timeDiff < 60) {
        return 'now';
    }

    foreach ($intervals as $interval => $seconds) {
        $diff = floor($timeDiff / $seconds);

        if ($diff < 1) {
            continue;
        }

        if ('mo' === $interval) {
            return floor($timeDiff / $intervals['mo']) . 'mo';
        }

        return $diff . $interval;
    }

    return '';
}
