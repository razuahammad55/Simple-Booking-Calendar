<?php
/**
 * Settings class.
 *
 * @package Simple_Booking_Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

class SBC_Settings {

    /**
     * Plugin instance.
     */
    private $plugin;

    /**
     * Constructor.
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Get a setting value.
     */
    public function get($key, $default = '') {
        $settings = get_option('sbc_settings', array());
        return isset($settings[$key]) ? $settings[$key] : $default;
    }

    /**
     * Save all settings.
     */
    public function save_settings($input) {
        $defaults = array(
            'admin_email'              => get_option('admin_email'),
            'calendar_title'           => __('Book Your Stay', 'simple-booking-calendar'),
            'min_advance_days'         => 0,
            'max_advance_days'         => 365,
            'min_stay_nights'          => 1,
            'max_stay_nights'          => 30,
            'pending_expiration_hours' => 48,
            'date_format'              => 'F j, Y',
            'first_day_of_week'        => 0,
            'calendar_start_month'     => 'current',
            'color_available'          => '#ffffff',
            'color_unavailable'        => '#f0f0f0',
            'color_selected'           => '#2271b1',
            'color_pending'            => '#ffd700',
            'color_approved'           => '#46b450',
            'color_blocked'            => '#dc3232',
            'enable_admin_notifications' => 1,
            'enable_guest_notifications' => 1,
            'enable_status_notifications' => 1,
            'email_sender_name'        => get_bloginfo('name'),
            'email_sender_address'     => 'noreply@' . parse_url(get_bloginfo('url'), PHP_URL_HOST),
            'subject_admin_new'        => __('New Booking Request - {reference}', 'simple-booking-calendar'),
            'subject_guest_confirmation' => __('Your Booking Request - {reference}', 'simple-booking-calendar'),
            'subject_guest_approved'   => __('Your Booking is Approved - {reference}', 'simple-booking-calendar'),
            'subject_guest_rejected'   => __('Your Booking Request Update - {reference}', 'simple-booking-calendar'),
            'subject_guest_cancelled'  => __('Your Booking is Cancelled - {reference}', 'simple-booking-calendar'),
        );

        $sanitized = array();
        foreach ($defaults as $key => $default_val) {
            if (!isset($input[$key])) {
                $sanitized[$key] = $default_val;
                continue;
            }

            $value = $input[$key];

            switch ($key) {
                case 'admin_email':
                    $sanitized[$key] = sanitize_email($value);
                    break;
                case 'email_sender_address':
                    $sanitized[$key] = sanitize_email($value);
                    break;
                case 'calendar_title':
                case 'email_sender_name':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                case 'date_format':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                case 'subject_admin_new':
                case 'subject_guest_confirmation':
                case 'subject_guest_approved':
                case 'subject_guest_rejected':
                case 'subject_guest_cancelled':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
                case 'min_advance_days':
                case 'max_advance_days':
                case 'min_stay_nights':
                case 'max_stay_nights':
                case 'pending_expiration_hours':
                case 'first_day_of_week':
                    $sanitized[$key] = max(0, intval($value));
                    break;
                case 'calendar_start_month':
                    $sanitized[$key] = in_array($value, array('current', 'next'), true) ? $value : 'current';
                    break;
                case 'color_available':
                case 'color_unavailable':
                case 'color_selected':
                case 'color_pending':
                case 'color_approved':
                case 'color_blocked':
                    $sanitized[$key] = sanitize_hex_color($value);
                    if (empty($sanitized[$key])) {
                        $sanitized[$key] = $default_val;
                    }
                    break;
                case 'enable_admin_notifications':
                case 'enable_guest_notifications':
                case 'enable_status_notifications':
                    $sanitized[$key] = !empty($value) ? 1 : 0;
                    break;
                default:
                    $sanitized[$key] = sanitize_text_field($value);
                    break;
            }
        }

        // Validation: min_advance <= max_advance.
        if ($sanitized['min_advance_days'] > $sanitized['max_advance_days']) {
            $sanitized['max_advance_days'] = $sanitized['min_advance_days'];
        }

        // Validation: min_stay <= max_stay.
        if ($sanitized['min_stay_nights'] > $sanitized['max_stay_nights']) {
            $sanitized['max_stay_nights'] = $sanitized['min_stay_nights'];
        }

        update_option('sbc_settings', $sanitized);

        return $sanitized;
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'simple-booking-calendar'));
        }

        // Save settings if posted.
        $message = '';
        if (isset($_POST['sbc_save_settings']) && wp_verify_nonce($_POST['sbc_nonce'], 'sbc_save_settings')) {
            $this->save_settings(wp_unslash($_POST));
            $message = __('Settings saved successfully.', 'simple-booking-calendar');
        }

        $settings = get_option('sbc_settings');
        include SBC_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
