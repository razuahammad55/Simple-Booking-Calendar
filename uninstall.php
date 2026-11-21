<?php
/**
 * Uninstall handler for Simple Booking Calendar.
 *
 * Fired when the plugin is deleted from the WordPress dashboard.
 * Removes all plugin data if the user has chosen to delete all data.
 *
 * @package Simple_Booking_Calendar
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options.
delete_option('sbc_settings');
delete_option('sbc_db_version');

// Drop custom database tables.
$table_bookings = $wpdb->prefix . 'sbc_bookings';
$table_blocked = $wpdb->prefix . 'sbc_blocked_dates';

$wpdb->query("DROP TABLE IF EXISTS $table_bookings");
$wpdb->query("DROP TABLE IF EXISTS $table_blocked");

// Clean up transients.
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_sbc_rate_%'");
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_timeout_sbc_rate_%'");
