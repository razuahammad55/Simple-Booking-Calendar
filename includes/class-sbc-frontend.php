<?php
/**
 * Frontend class.
 *
 * @package Simple_Booking_Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

class SBC_Frontend {

    /**
     * Plugin instance.
     */
    private $plugin;

    /**
     * Track if shortcode is being rendered (to load assets conditionally).
     */
    private $shortcode_rendered = false;

    /**
     * Rate limiting for submissions (simple IP-based).
     */
    const RATE_LIMIT_WINDOW = 60; // seconds.
    const RATE_LIMIT_MAX = 5; // submissions per window.

    /**
     * Constructor.
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
        add_shortcode('simple_booking_calendar', array($this, 'render_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_footer', array($this, 'maybe_print_assets'));
    }

    /**
     * Enqueue assets - but only load when shortcode is present (we use footer print method).
     */
    public function enqueue_frontend_assets() {
        // Register but don't enqueue yet.
        wp_register_style(
            'sbc-frontend-css',
            SBC_PLUGIN_URL . 'public/css/frontend.css',
            array(),
            SBC_VERSION
        );

        wp_register_script(
            'sbc-frontend-js',
            SBC_PLUGIN_URL . 'public/js/frontend.js',
            array('jquery'),
            SBC_VERSION,
            true
        );

        $settings = get_option('sbc_settings');

        wp_localize_script('sbc-frontend-js', 'sbc_frontend', array(
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('sbc_public_nonce'),
            'settings'    => array(
                'first_day'     => intval($settings['first_day_of_week'] ?? 0),
                'min_nights'    => intval($settings['min_stay_nights'] ?? 1),
                'date_format'   => $this->js_date_format($settings['date_format'] ?? 'F j, Y'),
                'colors'        => array(
                    'available'   => $settings['color_available'] ?? '#ffffff',
                    'unavailable' => $settings['color_unavailable'] ?? '#f0f0f0',
                    'selected'    => $settings['color_selected'] ?? '#2271b1',
                    'pending'     => $settings['color_pending'] ?? '#ffd700',
                    'approved'    => $settings['color_approved'] ?? '#46b450',
                    'blocked'     => $settings['color_blocked'] ?? '#dc3232',
                ),
            ),
            'i18n' => array(
                'select_checkin'   => __('Select your check-in date', 'simple-booking-calendar'),
                'select_checkout'  => __('Select your check-out date', 'simple-booking-calendar'),
                'loading'          => __('Loading...', 'simple-booking-calendar'),
                'invalid_range'    => __('Please select a valid date range.', 'simple-booking-calendar'),
                'min_stay'         => sprintf(__('Minimum stay is %d night(s).', 'simple-booking-calendar'), intval($settings['min_stay_nights'] ?? 1)),
                'submitting'       => __('Submitting...', 'simple-booking-calendar'),
                'error'            => __('An error occurred. Please try again.', 'simple-booking-calendar'),
                'nights'           => __('night(s)', 'simple-booking-calendar'),
                'check_in'         => __('Check-in', 'simple-booking-calendar'),
                'check_out'        => __('Check-out', 'simple-booking-calendar'),
                'submit'           => __('Submit Reservation Request', 'simple-booking-calendar'),
                'full_name'        => __('Full Name', 'simple-booking-calendar'),
                'email'            => __('Email Address', 'simple-booking-calendar'),
                'phone'            => __('Phone Number', 'simple-booking-calendar'),
                'guests'           => __('Number of Guests', 'simple-booking-calendar'),
                'notes'            => __('Additional Notes / Special Requests', 'simple-booking-calendar'),
                'required'         => __('*', 'simple-booking-calendar'),
                'dates_unavailable'=> __('The selected dates are not available.', 'simple-booking-calendar'),
                'rate_limited'     => __('Too many submission attempts. Please try again later.', 'simple-booking-calendar'),
            ),
        ));
    }

    /**
     * Convert PHP date format to JS date-fns-like format (simple subset).
     */
    private function js_date_format($php_format) {
        $map = array(
            'F' => 'MM',
            'M' => 'M',
            'm' => 'MM',
            'n' => 'M',
            'j' => 'D',
            'd' => 'DD',
            'Y' => 'YYYY',
            'y' => 'YY',
        );

        return str_replace(array_keys($map), array_values($map), $php_format);
    }

    /**
     * Render the booking calendar shortcode.
     */
    public function render_shortcode($atts) {
        $this->shortcode_rendered = true;

        $settings = get_option('sbc_settings');
        $title = !empty($settings['calendar_title']) ? $settings['calendar_title'] : __('Book Your Stay', 'simple-booking-calendar');

        // Enqueue assets now that shortcode is present.
        wp_enqueue_style('sbc-frontend-css');
        wp_enqueue_script('sbc-frontend-js');

        ob_start();
        include SBC_PLUGIN_DIR . 'public/views/booking-calendar.php';
        return ob_get_clean();
    }

    /**
     * Fallback to print assets in footer if not already enqueued.
     */
    public function maybe_print_assets() {
        if ($this->shortcode_rendered) {
            return;
        }
    }

    /**
     * Simple rate limiting check.
     */
    private function check_rate_limit() {
        $ip = $this->get_client_ip();
        $transient_key = 'sbc_rate_' . md5($ip);
        $attempts = get_transient($transient_key);

        if ($attempts === false) {
            $attempts = 0;
        }

        if ($attempts >= self::RATE_LIMIT_MAX) {
            return false;
        }

        set_transient($transient_key, $attempts + 1, self::RATE_LIMIT_WINDOW);
        return true;
    }

    /**
     * Get client IP address.
     */
    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])));
            return trim($ip[0]);
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        return 'unknown';
    }

    /**
     * AJAX: Get calendar HTML for a specific month.
     */
    public function ajax_get_calendar() {
        check_ajax_referer('sbc_public_nonce', 'nonce');

        $year = isset($_POST['year']) ? intval($_POST['year']) : intval(current_time('Y'));
        $month = isset($_POST['month']) ? intval($_POST['month']) : intval(current_time('m'));

        if ($month < 1) { $month = 12; $year--; }
        if ($month > 12) { $month = 1; $year++; }

        $availability = $this->plugin->availability->get_month_availability($year, $month);
        $settings = get_option('sbc_settings');

        ob_start();
        include SBC_PLUGIN_DIR . 'public/views/partials/calendar-month.php';
        $html = ob_get_clean();

        wp_send_json_success(array(
            'html'  => $html,
            'year'  => $year,
            'month' => $month,
        ));
    }

    /**
     * AJAX: Check availability for a date range.
     */
    public function ajax_check_availability() {
        check_ajax_referer('sbc_public_nonce', 'nonce');

        $check_in = isset($_POST['check_in']) ? $this->plugin->bookings->sanitize_date(sanitize_text_field(wp_unslash($_POST['check_in']))) : '';
        $check_out = isset($_POST['check_out']) ? $this->plugin->bookings->sanitize_date(sanitize_text_field(wp_unslash($_POST['check_out']))) : '';

        if (!$check_in || !$check_out) {
            wp_send_json_error(array('message' => __('Invalid dates.', 'simple-booking-calendar')));
        }

        $validation = $this->plugin->bookings->validate_date_range($check_in, $check_out);
        if (is_wp_error($validation)) {
            wp_send_json_error(array('message' => $validation->get_error_message()));
        }

        if ($this->plugin->availability->is_range_available($check_in, $check_out)) {
            wp_send_json_error(array('message' => __('The selected dates are no longer available.', 'simple-booking-calendar')));
        }

        $settings = get_option('sbc_settings');
        $date_format = !empty($settings['date_format']) ? $settings['date_format'] : 'F j, Y';

        wp_send_json_success(array(
            'check_in'  => date_i18n($date_format, strtotime($check_in)),
            'check_out' => date_i18n($date_format, strtotime($check_out)),
            'nights'    => $this->plugin->bookings->calculate_nights($check_in, $check_out),
        ));
    }

    /**
     * AJAX: Submit a booking.
     */
    public function ajax_submit_booking() {
        check_ajax_referer('sbc_public_nonce', 'nonce');

        // Rate limiting.
        if (!$this->check_rate_limit()) {
            wp_send_json_error(array('message' => __('Too many submission attempts. Please try again later.', 'simple-booking-calendar')));
            return;
        }

        // Honeypot anti-spam (hidden field).
        if (!empty($_POST['sbc_hp'])) {
            wp_send_json_error(array('message' => __('Spam detected.', 'simple-booking-calendar')));
            return;
        }

        $data = array(
            'guest_name'  => isset($_POST['guest_name']) ? sanitize_text_field(wp_unslash($_POST['guest_name'])) : '',
            'guest_email' => isset($_POST['guest_email']) ? sanitize_email(wp_unslash($_POST['guest_email'])) : '',
            'guest_phone' => isset($_POST['guest_phone']) ? sanitize_text_field(wp_unslash($_POST['guest_phone'])) : '',
            'guest_count' => isset($_POST['guest_count']) ? intval($_POST['guest_count']) : 1,
            'check_in'    => isset($_POST['check_in']) ? sanitize_text_field(wp_unslash($_POST['check_in'])) : '',
            'check_out'   => isset($_POST['check_out']) ? sanitize_text_field(wp_unslash($_POST['check_out'])) : '',
            'guest_notes' => isset($_POST['guest_notes']) ? sanitize_textarea_field(wp_unslash($_POST['guest_notes'])) : '',
        );

        $result = $this->plugin->bookings->create_booking($data);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
