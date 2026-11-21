<?php
/**
 * Admin dashboard class.
 *
 * @package Simple_Booking_Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

class SBC_Admin {

    /**
     * Plugin instance.
     */
    private $plugin;

    /**
     * Constructor.
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
        add_action('admin_menu', array($this, 'register_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Register admin menu.
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Booking Calendar', 'simple-booking-calendar'),
            __('Booking Calendar', 'simple-booking-calendar'),
            'manage_options',
            'sbc-bookings',
            array($this, 'render_bookings_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'sbc-bookings',
            __('All Bookings', 'simple-booking-calendar'),
            __('All Bookings', 'simple-booking-calendar'),
            'manage_options',
            'sbc-bookings',
            array($this, 'render_bookings_page')
        );

        add_submenu_page(
            'sbc-bookings',
            __('Calendar', 'simple-booking-calendar'),
            __('Calendar', 'simple-booking-calendar'),
            'manage_options',
            'sbc-calendar',
            array($this, 'render_calendar_page')
        );

        add_submenu_page(
            'sbc-bookings',
            __('Blocked Dates', 'simple-booking-calendar'),
            __('Blocked Dates', 'simple-booking-calendar'),
            'manage_options',
            'sbc-blocked',
            array($this, 'render_blocked_page')
        );

        add_submenu_page(
            'sbc-bookings',
            __('Settings', 'simple-booking-calendar'),
            __('Settings', 'simple-booking-calendar'),
            'manage_options',
            'sbc-settings',
            array($this->plugin->settings, 'render_settings_page')
        );
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our plugin pages.
        if (strpos($hook, 'sbc-') === false) {
            return;
        }

        wp_enqueue_style(
            'sbc-admin-css',
            SBC_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            SBC_VERSION
        );

        wp_enqueue_script(
            'sbc-admin-js',
            SBC_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery', 'wp-color-picker'),
            SBC_VERSION,
            true
        );

        wp_localize_script('sbc-admin-js', 'sbc_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('sbc_admin_nonce'),
            'i18n'     => array(
                'confirm_approve'   => __('Are you sure you want to approve this booking?', 'simple-booking-calendar'),
                'confirm_reject'    => __('Are you sure you want to reject this booking?', 'simple-booking-calendar'),
                'confirm_cancel'    => __('Are you sure you want to cancel this booking?', 'simple-booking-calendar'),
                'confirm_delete'    => __('Are you sure you want to delete this booking? This cannot be undone.', 'simple-booking-calendar'),
                'confirm_block'     => __('Are you sure you want to block these dates?', 'simple-booking-calendar'),
                'confirm_unblock'   => __('Are you sure you want to unblock these dates?', 'simple-booking-calendar'),
                'warning_overlap'   => __('Warning: The selected date range overlaps with existing bookings. Are you sure you want to continue?', 'simple-booking-calendar'),
                'loading'           => __('Loading...', 'simple-booking-calendar'),
                'error'             => __('An error occurred. Please try again.', 'simple-booking-calendar'),
            ),
        ));

        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Render All Bookings page.
     */
    public function render_bookings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'simple-booking-calendar'));
        }

        $args = array(
            'status'    => isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '',
            'search'    => isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '',
            'date_from' => isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '',
            'date_to'   => isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '',
            'per_page'  => 20,
            'paged'     => isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1,
            'orderby'   => isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'created_at',
            'order'     => isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'DESC',
        );

        $result = $this->plugin->db->get_bookings($args);
        $settings = get_option('sbc_settings');

        include SBC_PLUGIN_DIR . 'admin/views/bookings.php';
    }

    /**
     * Render Calendar page.
     */
    public function render_calendar_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'simple-booking-calendar'));
        }

        $year = isset($_GET['year']) ? intval($_GET['year']) : intval(current_time('Y'));
        $month = isset($_GET['month']) ? intval($_GET['month']) : intval(current_time('m'));

        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1; $year++; }

        $availability = $this->plugin->availability->get_month_availability($year, $month);
        $bookings = $this->plugin->db->get_bookings_in_range(
            date('Y-m-01', strtotime("$year-$month-01")),
            date('Y-m-t', strtotime("$year-$month-01"))
        );
        $blocked = $this->plugin->db->get_blocked_dates(
            date('Y-m-01', strtotime("$year-$month-01")),
            date('Y-m-t', strtotime("$year-$month-01"))
        );
        $settings = get_option('sbc_settings');

        include SBC_PLUGIN_DIR . 'admin/views/calendar.php';
    }

    /**
     * Render Blocked Dates page.
     */
    public function render_blocked_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'simple-booking-calendar'));
        }

        $blocked_dates = $this->plugin->db->get_blocked_dates();

        include SBC_PLUGIN_DIR . 'admin/views/blocked.php';
    }

    /**
     * AJAX: Get admin calendar for a specific month.
     */
    public function ajax_get_admin_calendar() {
        check_ajax_referer('sbc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simple-booking-calendar')));
        }

        $year = isset($_POST['year']) ? intval($_POST['year']) : intval(current_time('Y'));
        $month = isset($_POST['month']) ? intval($_POST['month']) : intval(current_time('m'));

        $availability = $this->plugin->availability->get_month_availability($year, $month);
        $bookings = $this->plugin->db->get_bookings_in_range(
            date('Y-m-01', strtotime("$year-$month-01")),
            date('Y-m-t', strtotime("$year-$month-01"))
        );

        ob_start();
        $settings = get_option('sbc_settings');
        include SBC_PLUGIN_DIR . 'admin/views/partials/calendar-grid.php';
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html'    => $html,
            'year'    => $year,
            'month'   => $month,
        ));
    }

    /**
     * AJAX: Save blocked dates.
     */
    public function ajax_save_blocked_dates() {
        check_ajax_referer('sbc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simple-booking-calendar')));
        }

        $start_date = isset($_POST['start_date']) ? sanitize_text_field(wp_unslash($_POST['start_date'])) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field(wp_unslash($_POST['end_date'])) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field(wp_unslash($_POST['reason'])) : '';

        if (empty($start_date) || empty($end_date)) {
            wp_send_json_error(array('message' => __('Please select start and end dates.', 'simple-booking-calendar')));
        }

        $d1 = DateTime::createFromFormat('Y-m-d', $start_date);
        $d2 = DateTime::createFromFormat('Y-m-d', $end_date);
        if (!$d1 || !$d2 || $d1->format('Y-m-d') !== $start_date || $d2->format('Y-m-d') !== $end_date) {
            wp_send_json_error(array('message' => __('Invalid date format.', 'simple-booking-calendar')));
        }

        if ($end_date <= $start_date) {
            // Allow same-day blocking: treat single day as [start, start+1)
            $end_date = date('Y-m-d', strtotime($start_date . ' +1 day'));
        }

        // Check for overlaps with bookings for warnings.
        $overlaps = $this->plugin->db->get_overlapping_bookings($start_date, $end_date);

        $id = $this->plugin->db->insert_blocked_date(array(
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'reason'     => $reason,
        ));

        if (!$id) {
            wp_send_json_error(array('message' => __('Failed to save blocked dates.', 'simple-booking-calendar')));
        }

        wp_send_json_success(array(
            'message'      => __('Dates blocked successfully.', 'simple-booking-calendar'),
            'id'           => $id,
            'overlaps'     => count($overlaps),
            'overlap_data' => $overlaps,
        ));
    }

    /**
     * AJAX: Delete blocked date.
     */
    public function ajax_delete_blocked_date() {
        check_ajax_referer('sbc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simple-booking-calendar')));
        }

        $block_id = isset($_POST['block_id']) ? intval($_POST['block_id']) : 0;

        if (!$block_id) {
            wp_send_json_error(array('message' => __('Invalid blocked date ID.', 'simple-booking-calendar')));
        }

        $result = $this->plugin->db->delete_blocked_date($block_id);

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete blocked date.', 'simple-booking-calendar')));
        }

        wp_send_json_success(array(
            'message' => __('Blocked dates removed successfully.', 'simple-booking-calendar'),
        ));
    }

    /**
     * AJAX: Update booking status.
     */
    public function ajax_update_booking_status() {
        check_ajax_referer('sbc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simple-booking-calendar')));
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';

        if (!$booking_id || !$status) {
            wp_send_json_error(array('message' => __('Invalid parameters.', 'simple-booking-calendar')));
        }

        $result = $this->plugin->bookings->update_status($booking_id, $status);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Delete booking.
     */
    public function ajax_delete_booking() {
        check_ajax_referer('sbc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simple-booking-calendar')));
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;

        if (!$booking_id) {
            wp_send_json_error(array('message' => __('Invalid booking ID.', 'simple-booking-calendar')));
        }

        $result = $this->plugin->db->delete_booking($booking_id);

        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete booking.', 'simple-booking-calendar')));
        }

        wp_send_json_success(array(
            'message' => __('Booking deleted successfully.', 'simple-booking-calendar'),
        ));
    }

    /**
     * AJAX: Get booking details.
     */
    public function ajax_get_booking_details() {
        check_ajax_referer('sbc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simple-booking-calendar')));
        }

        $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
        $booking = $this->plugin->db->get_booking($booking_id);

        if (!$booking) {
            wp_send_json_error(array('message' => __('Booking not found.', 'simple-booking-calendar')));
        }

        $settings = get_option('sbc_settings');
        $date_format = !empty($settings['date_format']) ? $settings['date_format'] : 'F j, Y';

        ob_start();
        include SBC_PLUGIN_DIR . 'admin/views/partials/booking-details.php';
        $html = ob_get_clean();

        wp_send_json_success(array('html' => $html));
    }
}
