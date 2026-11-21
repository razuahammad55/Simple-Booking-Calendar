<?php
/**
 * Plugin activator class.
 *
 * @package Simple_Booking_Calendar
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class SBC_Activator {

    /**
     * Activate the plugin.
     */
    public static function activate() {
        self::create_tables();
        self::set_default_options();

        // Set database version.
        update_option('sbc_db_version', SBC_VERSION);

        // Flush rewrite rules.
        flush_rewrite_rules();
    }

    /**
     * Create database tables.
     */
    private static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        // Bookings table.
        $table_bookings = $wpdb->prefix . 'sbc_bookings';
        $sql_bookings = "CREATE TABLE $table_bookings (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            booking_reference varchar(30) NOT NULL,
            guest_name varchar(255) NOT NULL,
            guest_email varchar(255) NOT NULL,
            guest_phone varchar(50) NOT NULL DEFAULT '',
            guest_count int(11) NOT NULL DEFAULT 1,
            check_in date NOT NULL,
            check_out date NOT NULL,
            guest_notes text NOT NULL DEFAULT '',
            status varchar(20) NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY booking_reference (booking_reference),
            KEY check_in (check_in),
            KEY check_out (check_out),
            KEY status (status),
            KEY guest_email (guest_email)
        ) $charset_collate;";

        // Blocked dates table.
        $table_blocked = $wpdb->prefix . 'sbc_blocked_dates';
        $sql_blocked = "CREATE TABLE $table_blocked (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            start_date date NOT NULL,
            end_date date NOT NULL,
            reason text NOT NULL DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY start_date (start_date),
            KEY end_date (end_date)
        ) $charset_collate;";

        dbDelta($sql_bookings);
        dbDelta($sql_blocked);
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $defaults = array(
            'admin_email' => get_option('admin_email'),
            'calendar_title' => __('Book Your Stay', 'simple-booking-calendar'),
            'min_advance_days' => 0,
            'max_advance_days' => 365,
            'min_stay_nights' => 1,
            'max_stay_nights' => 30,
            'pending_expiration_hours' => 48,
            'date_format' => 'F j, Y',
            'first_day_of_week' => 0,
            'calendar_start_month' => 'current',
            'color_available' => '#ffffff',
            'color_unavailable' => '#f0f0f0',
            'color_selected' => '#2271b1',
            'color_pending' => '#ffd700',
            'color_approved' => '#46b450',
            'color_blocked' => '#dc3232',
            'enable_admin_notifications' => 1,
            'enable_guest_notifications' => 1,
            'enable_status_notifications' => 1,
            'email_sender_name' => get_bloginfo('name'),
            'email_sender_address' => 'noreply@' . parse_url(get_bloginfo('url'), PHP_URL_HOST),
            'subject_admin_new' => __('New Booking Request - {reference}', 'simple-booking-calendar'),
            'subject_guest_confirmation' => __('Your Booking Request - {reference}', 'simple-booking-calendar'),
            'subject_guest_approved' => __('Your Booking is Approved - {reference}', 'simple-booking-calendar'),
            'subject_guest_rejected' => __('Your Booking Request Update - {reference}', 'simple-booking-calendar'),
            'subject_guest_cancelled' => __('Your Booking is Cancelled - {reference}', 'simple-booking-calendar'),
        );

        add_option('sbc_settings', $defaults);
    }
}
