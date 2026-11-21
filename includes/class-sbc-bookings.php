<?php
/**
 * Booking management class.
 *
 * @package Simple_Booking_Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

class SBC_Bookings {

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
     * Generate a unique booking reference.
     *
     * Format: SBC-YYYYMMDD-NNNN
     */
    public function generate_reference() {
        global $wpdb;

        $date = current_time('Ymd');
        $prefix = 'SBC-' . $date . '-';

        // Get the next sequence number for today.
        $table = $this->plugin->db->bookings_table;
        $like = $wpdb->esc_like($prefix) . '%';
        $last = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT booking_reference FROM $table WHERE booking_reference LIKE %s ORDER BY id DESC LIMIT 1",
                $like
            )
        );

        if ($last) {
            $seq = intval(substr($last, -4)) + 1;
        } else {
            $seq = 1;
        }

        $reference = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

        // Double-check uniqueness (concurrency safety).
        while ($this->plugin->db->booking_reference_exists($reference)) {
            $seq++;
            $reference = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
        }

        return $reference;
    }

    /**
     * Create a new booking from submitted data.
     *
     * @param array $data Form data.
     * @return array Result array with success status and message/data.
     */
    public function create_booking($data) {
        // Validate required fields.
        $required_fields = array('guest_name', 'guest_email', 'guest_phone', 'check_in', 'check_out');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return array(
                    'success' => false,
                    'message' => __('Please fill in all required fields.', 'simple-booking-calendar'),
                );
            }
        }

        if (!is_email($data['guest_email'])) {
            return array(
                'success' => false,
                'message' => __('Please enter a valid email address.', 'simple-booking-calendar'),
            );
        }

        // Sanitize dates.
        $check_in = $this->sanitize_date($data['check_in']);
        $check_out = $this->sanitize_date($data['check_out']);

        if (!$check_in || !$check_out) {
            return array(
                'success' => false,
                'message' => __('Invalid date format.', 'simple-booking-calendar'),
            );
        }

        // Validate date logic.
        $validation = $this->validate_date_range($check_in, $check_out);
        if (is_wp_error($validation)) {
            return array(
                'success' => false,
                'message' => $validation->get_error_message(),
            );
        }

        // Server-side availability check (critical).
        if ($this->plugin->availability->is_range_available($check_in, $check_out)) {
            return array(
                'success' => false,
                'message' => __('The selected dates are no longer available. Please choose different dates.', 'simple-booking-calendar'),
            );
        }

        // Build booking data.
        $booking_data = array(
            'booking_reference' => $this->generate_reference(),
            'guest_name'        => $data['guest_name'],
            'guest_email'       => $data['guest_email'],
            'guest_phone'       => $data['guest_phone'],
            'guest_count'       => isset($data['guest_count']) ? max(1, intval($data['guest_count'])) : 1,
            'check_in'          => $check_in,
            'check_out'         => $check_out,
            'guest_notes'       => isset($data['guest_notes']) ? $data['guest_notes'] : '',
        );

        // Insert booking.
        $booking_id = $this->plugin->db->insert_booking($booking_data);

        if (!$booking_id) {
            return array(
                'success' => false,
                'message' => __('An error occurred while saving your booking. Please try again.', 'simple-booking-calendar'),
            );
        }

        $booking = $this->plugin->db->get_booking($booking_id);

        // Send notifications.
        $settings = get_option('sbc_settings');
        if (!empty($settings['enable_admin_notifications'])) {
            $this->plugin->notifications->send_admin_notification($booking);
        }
        if (!empty($settings['enable_guest_notifications'])) {
            $this->plugin->notifications->send_guest_confirmation($booking);
        }

        return array(
            'success'   => true,
            'message'   => sprintf(
                /* translators: %s: booking reference number */
                __('Thank you! Your reservation request has been submitted successfully. Your booking reference is %s. You will receive a confirmation email shortly.', 'simple-booking-calendar'),
                '<strong>' . esc_html($booking->booking_reference) . '</strong>'
            ),
            'reference' => $booking->booking_reference,
            'booking'   => $booking,
        );
    }

    /**
     * Validate a date range.
     *
     * @return bool|WP_Error True if valid, WP_Error otherwise.
     */
    public function validate_date_range($check_in, $check_out) {
        $settings = get_option('sbc_settings');

        $today = current_time('Y-m-d');
        $min_advance = !empty($settings['min_advance_days']) ? intval($settings['min_advance_days']) : 0;
        $max_advance = !empty($settings['max_advance_days']) ? intval($settings['max_advance_days']) : 365;
        $min_nights = !empty($settings['min_stay_nights']) ? intval($settings['min_stay_nights']) : 1;
        $max_nights = !empty($settings['max_stay_nights']) ? intval($settings['max_stay_nights']) : 30;

        $min_date = date('Y-m-d', strtotime($today . ' +' . $min_advance . ' days'));
        $max_date = date('Y-m-d', strtotime($today . ' +' . $max_advance . ' days'));

        if ($check_in < $min_date) {
            return new WP_Error('early', sprintf(
                /* translators: %d: number of days */
                __('Bookings must be made at least %d day(s) in advance.', 'simple-booking-calendar'),
                $min_advance
            ));
        }

        if ($check_in > $max_date) {
            return new WP_Error('late', sprintf(
                /* translators: %d: number of days */
                __('Bookings cannot be made more than %d day(s) in advance.', 'simple-booking-calendar'),
                $max_advance
            ));
        }

        if ($check_out <= $check_in) {
            return new WP_Error('invalid_range', __('Check-out date must be after check-in date.', 'simple-booking-calendar'));
        }

        $nights = $this->calculate_nights($check_in, $check_out);
        if ($nights < $min_nights) {
            return new WP_Error('min_stay', sprintf(
                /* translators: %d: number of nights */
                __('Minimum stay is %d night(s).', 'simple-booking-calendar'),
                $min_nights
            ));
        }

        if ($nights > $max_nights) {
            return new WP_Error('max_stay', sprintf(
                /* translators: %d: number of nights */
                __('Maximum stay is %d night(s).', 'simple-booking-calendar'),
                $max_nights
            ));
        }

        return true;
    }

    /**
     * Calculate number of nights.
     */
    public function calculate_nights($check_in, $check_out) {
        $ci = new DateTime($check_in);
        $co = new DateTime($check_out);
        $diff = $ci->diff($co);
        return $diff->days;
    }

    /**
     * Sanitize and validate a date string (Y-m-d format).
     */
    public function sanitize_date($date) {
        $date = sanitize_text_field($date);
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if ($d && $d->format('Y-m-d') === $date) {
            return $date;
        }
        return false;
    }

    /**
     * Update booking status with notifications.
     */
    public function update_status($booking_id, $new_status) {
        $booking = $this->plugin->db->get_booking($booking_id);
        if (!$booking) {
            return array('success' => false, 'message' => __('Booking not found.', 'simple-booking-calendar'));
        }

        $old_status = $booking->status;

        if ($old_status === $new_status) {
            return array('success' => false, 'message' => __('Booking already has this status.', 'simple-booking-calendar'));
        }

        $result = $this->plugin->db->update_booking_status($booking_id, $new_status);

        if ($result === false) {
            return array('success' => false, 'message' => __('Failed to update booking status.', 'simple-booking-calendar'));
        }

        // Send status notification if enabled.
        $settings = get_option('sbc_settings');
        if (!empty($settings['enable_status_notifications'])) {
            $this->plugin->notifications->send_status_notification($booking_id, $new_status);
        }

        return array(
            'success' => true,
            'message' => sprintf(
                /* translators: 1: reference, 2: new status */
                __('Booking %1$s status updated to %2$s.', 'simple-booking-calendar'),
                $booking->booking_reference,
                ucfirst($new_status)
            ),
        );
    }
}
