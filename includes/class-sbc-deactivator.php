<?php
/**
 * Plugin deactivator class.
 *
 * @package Simple_Booking_Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

class SBC_Deactivator {

    /**
     * Deactivate the plugin.
     *
     * We do NOT delete booking data on deactivation per requirements.
     */
    public static function deactivate() {
        // Nothing to do - data is preserved.
        // Users can delete data via uninstall if they choose.
        flush_rewrite_rules();
    }
}
