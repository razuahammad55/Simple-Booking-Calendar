<?php
/**
 * Plugin Name: Simple Booking Calendar
 * Plugin URI: https://example.com/simple-booking-calendar
 * Description: A lightweight, secure, and user-friendly WordPress booking calendar plugin for reservation requests.
 * Version: 1.0.0
 * Author: Simple Booking Calendar
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simple-booking-calendar
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('SBC_VERSION', '1.0.0');
define('SBC_PLUGIN_FILE', __FILE__);
define('SBC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SBC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SBC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required classes.
require_once SBC_PLUGIN_DIR . 'includes/class-sbc-activator.php';
require_once SBC_PLUGIN_DIR . 'includes/class-sbc-deactivator.php';
require_once SBC_PLUGIN_DIR . 'includes/class-sbc-database.php';
require_once SBC_PLUGIN_DIR . 'includes/class-sbc-bookings.php';
require_once SBC_PLUGIN_DIR . 'includes/class-sbc-availability.php';
require_once SBC_PLUGIN_DIR . 'includes/class-sbc-notifications.php';
require_once SBC_PLUGIN_DIR . 'includes/class-sbc-settings.php';
require_once SBC_PLUGIN_DIR . 'includes/class-sbc-admin.php';
require_once SBC_PLUGIN_DIR . 'includes/class-sbc-frontend.php';

/**
 * Main Simple Booking Calendar class.
 */
final class Simple_Booking_Calendar {

    /**
     * Single instance.
     */
    private static $instance = null;

    /**
     * Database instance.
     */
    public $db;

    /**
     * Bookings instance.
     */
    public $bookings;

    /**
     * Availability instance.
     */
    public $availability;

    /**
     * Notifications instance.
     */
    public $notifications;

    /**
     * Settings instance.
     */
    public $settings;

    /**
     * Admin instance.
     */
    public $admin;

    /**
     * Frontend instance.
     */
    public $frontend;

    /**
     * Get single instance.
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        $this->init_hooks();
        $this->load_classes();
    }

    /**
     * Initialize hooks.
     */
    private function init_hooks() {
        register_activation_hook(SBC_PLUGIN_FILE, array('SBC_Activator', 'activate'));
        register_deactivation_hook(SBC_PLUGIN_FILE, array('SBC_Deactivator', 'deactivate'));

        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('init', array($this, 'register_ajax_handlers'));
    }

    /**
     * Load plugin classes.
     *
     * Both classes are always loaded because:
     * - AJAX requests go through wp-admin/admin-ajax.php (is_admin() = true)
     *   but still need frontend AJAX handlers (get_calendar, submit_booking, etc.)
     * - Admin class only hooks admin_menu/admin_enqueue_scripts which fire
     *   only in wp-admin, so it's safe to instantiate on frontend requests.
     * - Frontend class registers the shortcode and public AJAX handlers
     *   which must be available on every request including AJAX.
     */
    private function load_classes() {
        $this->db = new SBC_Database();
        $this->bookings = new SBC_Bookings($this);
        $this->availability = new SBC_Availability($this);
        $this->notifications = new SBC_Notifications($this);
        $this->settings = new SBC_Settings($this);
        $this->frontend = new SBC_Frontend($this);
        $this->admin = new SBC_Admin($this);
    }

    /**
     * Load text domain.
     */
    public function load_textdomain() {
        load_plugin_textdomain('simple-booking-calendar', false, dirname(SBC_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Register AJAX handlers.
     *
     * Both public and admin AJAX handlers are always registered:
     * - Public handlers handle nopriv (logged-out) and logged-in requests.
     * - Admin handlers check current_user_can('manage_options') internally.
     * - During AJAX via admin-ajax.php, is_admin() is true regardless of
     *   whether the request originated from frontend or backend, so we
     *   must register all handlers unconditionally.
     */
    public function register_ajax_handlers() {
        // Public AJAX handlers (both logged-in and guest users).
        add_action('wp_ajax_nopriv_sbc_get_calendar', array($this->frontend, 'ajax_get_calendar'));
        add_action('wp_ajax_sbc_get_calendar', array($this->frontend, 'ajax_get_calendar'));
        add_action('wp_ajax_nopriv_sbc_check_availability', array($this->frontend, 'ajax_check_availability'));
        add_action('wp_ajax_sbc_check_availability', array($this->frontend, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_sbc_submit_booking', array($this->frontend, 'ajax_submit_booking'));
        add_action('wp_ajax_sbc_submit_booking', array($this->frontend, 'ajax_submit_booking'));

        // Admin AJAX handlers (capability-checked inside each method).
        add_action('wp_ajax_sbc_admin_get_calendar', array($this->admin, 'ajax_get_admin_calendar'));
        add_action('wp_ajax_sbc_save_blocked_dates', array($this->admin, 'ajax_save_blocked_dates'));
        add_action('wp_ajax_sbc_delete_blocked_date', array($this->admin, 'ajax_delete_blocked_date'));
        add_action('wp_ajax_sbc_update_booking_status', array($this->admin, 'ajax_update_booking_status'));
        add_action('wp_ajax_sbc_delete_booking', array($this->admin, 'ajax_delete_booking'));
        add_action('wp_ajax_sbc_get_booking_details', array($this->admin, 'ajax_get_booking_details'));
    }
}

/**
 * Initialize plugin.
 */
function sbc() {
    return Simple_Booking_Calendar::get_instance();
}

sbc();
