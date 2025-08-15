<?php
/**
 * Uninstall plugin
 *
 * @package AI_Order_Tracker
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
global $wpdb;

// Delete all plugin options
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'aiot_%'");

// Drop custom tables
$tables = array(
    $wpdb->prefix . 'aiot_orders',
    $wpdb->prefix . 'aiot_zones',
    $wpdb->prefix . 'aiot_couriers',
    $wpdb->prefix . 'aiot_tracking_events'
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS {$table}");
}

// Clear scheduled cron jobs
wp_clear_scheduled_hook('aiot_daily_cleanup');
wp_clear_scheduled_hook('aiot_update_order_status');

// Clear transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_aiot_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_aiot_%'");

// Clear any cached data
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
}