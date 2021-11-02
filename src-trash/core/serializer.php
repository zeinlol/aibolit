<?php

/**
 * Seconds to readable text
 * @param int $seconds
 * @return string
 */
function normalize_seconds(int $seconds): string
{
    $r = '';
    $_seconds = floor($seconds);
    $ms = $seconds - $_seconds;
    $seconds = $_seconds;
    if ($hours = floor($seconds / 3600)) {
        $r .= $hours . (isCli() ? ' h ' : ' час ');
        $seconds = $seconds % 3600;
    }

    if ($minutes = floor($seconds / 60)) {
        $r .= $minutes . (isCli() ? ' m ' : ' мин ');
        $seconds = $seconds % 60;
    }

    if ($minutes < 3) $r .= ' ' . ($seconds + ($ms > 0 ? round($ms) : 0)) . (isCli() ? ' s' : ' сек');

    return $r;
}


/**
 * Format bytes to readable text
 * @param int $bites
 * @return string
 */
function normalize_bytes(int $bites): string
{
    if ($bites < 1024) {
        return $bites . ' b';
    } elseif (($kb = $bites / 1024) < 1024) {
        return number_format($kb, 2) . ' Kb';
    } elseif (($mb = $kb / 1024) < 1024) {
        return number_format($mb, 2) . ' Mb';
    } elseif (($gb = $mb / 1024) < 1024) {
        return number_format($gb, 2) . ' Gb';
    } else {
        return number_format($gb / 1024, 2) . 'Tb';
    }
}
