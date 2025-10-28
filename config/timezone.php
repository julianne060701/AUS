<?php
/**
 * Timezone Configuration for Philippine Time
 * This file ensures all timestamps are handled in Asia/Manila timezone
 */

// Set PHP timezone to Philippine time
date_default_timezone_set('Asia/Manila');

/**
 * Get current Philippine time
 * @param string $format PHP date format (default: 'Y-m-d H:i:s')
 * @return string Formatted Philippine time
 */
function getPhilippineTime($format = 'Y-m-d H:i:s') {
    return date($format);
}

/**
 * Convert UTC timestamp to Philippine time
 * @param string $utcTimestamp UTC timestamp
 * @param string $format PHP date format (default: 'Y-m-d H:i:s')
 * @return string Formatted Philippine time
 */
function convertToPhilippineTime($utcTimestamp, $format = 'Y-m-d H:i:s') {
    $utc = new DateTime($utcTimestamp, new DateTimeZone('UTC'));
    $utc->setTimezone(new DateTimeZone('Asia/Manila'));
    return $utc->format($format);
}

/**
 * Convert Philippine time to UTC for database storage
 * @param string $philippineTime Philippine time timestamp
 * @return string UTC timestamp
 */
function convertToUTC($philippineTime) {
    $ph = new DateTime($philippineTime, new DateTimeZone('Asia/Manila'));
    $ph->setTimezone(new DateTimeZone('UTC'));
    return $ph->format('Y-m-d H:i:s');
}

/**
 * Format timestamp for display in Philippine time
 * @param string $timestamp Database timestamp
 * @param string $format Display format (default: 'M j, Y g:i A')
 * @return string Formatted time for display
 */
function formatPhilippineTime($timestamp, $format = 'M j, Y g:i A') {
    if (empty($timestamp)) return 'N/A';
    
    $dt = new DateTime($timestamp);
    $dt->setTimezone(new DateTimeZone('Asia/Manila'));
    return $dt->format($format);
}

/**
 * Get Philippine timezone offset
 * @return string Timezone offset (+08:00)
 */
function getPhilippineTimezoneOffset() {
    return '+08:00';
}

/**
 * Get Philippine timezone name
 * @return string Timezone name (Asia/Manila)
 */
function getPhilippineTimezoneName() {
    return 'Asia/Manila';
}

// Display current Philippine time for debugging
if (isset($_GET['debug_timezone'])) {
    echo "<h3>Timezone Debug Information</h3>";
    echo "<p><strong>PHP Timezone:</strong> " . date_default_timezone_get() . "</p>";
    echo "<p><strong>Current Philippine Time:</strong> " . getPhilippineTime() . "</p>";
    echo "<p><strong>Current UTC Time:</strong> " . gmdate('Y-m-d H:i:s') . "</p>";
    echo "<p><strong>Timezone Offset:</strong> " . getPhilippineTimezoneOffset() . "</p>";
}
?>
