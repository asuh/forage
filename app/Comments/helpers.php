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

    if ($timeDiff >= YEAR_IN_SECONDS) {
        /* translators: Date format for comments older than a year. See https://www.php.net/manual/datetime.format.php */
        return wp_date(_x('m/d/Y', 'comment date format', 'forage'), $timestampDate);
    }

    if ($timeDiff < MINUTE_IN_SECONDS) {
        return _x('now', 'comment time', 'forage');
    }

    $intervals = [
        /* translators: %d: Number of months, abbreviated. */
        MONTH_IN_SECONDS => _x('%dmo', 'comment time', 'forage'),
        /* translators: %d: Number of days, abbreviated. */
        DAY_IN_SECONDS => _x('%dd', 'comment time', 'forage'),
        /* translators: %d: Number of hours, abbreviated. */
        HOUR_IN_SECONDS => _x('%dh', 'comment time', 'forage'),
        /* translators: %d: Number of minutes, abbreviated. */
        MINUTE_IN_SECONDS => _x('%dm', 'comment time', 'forage'),
    ];

    foreach ($intervals as $seconds => $format) {
        if ($timeDiff >= $seconds) {
            return sprintf($format, intdiv($timeDiff, $seconds));
        }
    }

    return '';
}
