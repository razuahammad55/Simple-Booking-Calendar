<?php
/**
 * Database operations class.
 *
 * @package Simple_Booking_Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

class SBC_Database {

    /**
     * Bookings table name.
     */
    public $bookings_table;

    /**
     * Blocked dates table name.
     */
    public $blocked_table;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->bookings_table = $wpdb->prefix . 'sbc_bookings';
        $this->blocked_table = $wpdb->prefix . 'sbc_blocked_dates';
    }

    /**
     * Insert a new booking.
     *
     * @param array $data Booking data.
     * @return int|false Booking ID on success, false on failure.
     */
    public function insert_booking($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->bookings_table,
            array(
                'booking_reference' => $data['booking_reference'],
                'guest_name'        => sanitize_text_field($data['guest_name']),
                'guest_email'       => sanitize_email($data['guest_email']),
                'guest_phone'       => sanitize_text_field($data['guest_phone']),
                'guest_count'       => intval($data['guest_count']),
                'check_in'          => sanitize_text_field($data['check_in']),
                'check_out'         => sanitize_text_field($data['check_out']),
                'guest_notes'       => sanitize_textarea_field($data['guest_notes']),
                'status'            => 'pending',
            ),
            array('%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get a booking by ID.
     */
    public function get_booking($booking_id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->bookings_table} WHERE id = %d",
                $booking_id
            )
        );
    }

    /**
     * Get a booking by reference.
     */
    public function get_booking_by_reference($reference) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->bookings_table} WHERE booking_reference = %s",
                $reference
            )
        );
    }

    /**
     * Update booking status.
     */
    public function update_booking_status($booking_id, $status) {
        global $wpdb;

        $valid_statuses = array('pending', 'approved', 'rejected', 'cancelled');
        if (!in_array($status, $valid_statuses, true)) {
            return false;
        }

        return $wpdb->update(
            $this->bookings_table,
            array('status' => $status),
            array('id' => intval($booking_id)),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Delete a booking.
     */
    public function delete_booking($booking_id) {
        global $wpdb;
        return $wpdb->delete(
            $this->bookings_table,
            array('id' => intval($booking_id)),
            array('%d')
        );
    }

    /**
     * Get bookings with optional filters.
     */
    public function get_bookings($args = array()) {
        global $wpdb;

        $defaults = array(
            'status'   => '',
            'search'   => '',
            'date_from' => '',
            'date_to'   => '',
            'per_page' => 20,
            'paged'    => 1,
            'orderby'  => 'created_at',
            'order'    => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');
        $values = array();

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $values[] = sanitize_text_field($args['status']);
        }

        if (!empty($args['search'])) {
            $where[] = '(guest_name LIKE %s OR guest_email LIKE %s OR booking_reference LIKE %s OR guest_phone LIKE %s)';
            $search = '%' . $wpdb->esc_like(sanitize_text_field($args['search'])) . '%';
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
        }

        if (!empty($args['date_from'])) {
            $where[] = 'check_in >= %s';
            $values[] = sanitize_text_field($args['date_from']);
        }

        if (!empty($args['date_to'])) {
            $where[] = 'check_out <= %s';
            $values[] = sanitize_text_field($args['date_to']);
        }

        $allowed_orderby = array('id', 'booking_reference', 'guest_name', 'check_in', 'check_out', 'status', 'created_at');
        $orderby = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        $offset = ($args['paged'] - 1) * $args['per_page'];

        $where_sql = implode(' AND ', $where);

        // Count.
        $count_sql = "SELECT COUNT(*) FROM {$this->bookings_table} WHERE $where_sql";
        if (!empty($values)) {
            $count_sql = $wpdb->prepare($count_sql, $values);
        }
        $total = $wpdb->get_var($count_sql);

        // Get rows.
        $sql = "SELECT * FROM {$this->bookings_table} WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $values[] = intval($args['per_page']);
        $values[] = intval($offset);

        $bookings = $wpdb->get_results($wpdb->prepare($sql, $values));

        return array(
            'bookings' => $bookings,
            'total'    => intval($total),
            'pages'    => ceil($total / $args['per_page']),
        );
    }

    /**
     * Get bookings that conflict with a date range.
     *
     * Date range overlap logic:
     * - Two ranges (A,B] and (C,D] overlap if A < D AND C < B
     * - This allows back-to-back bookings (checkout on same day as new check-in is OK)
     */
    public function get_overlapping_bookings($check_in, $check_out, $exclude_statuses = array('rejected', 'cancelled'), $exclude_id = null) {
        global $wpdb;

        $statuses_placeholder = implode(',', array_fill(0, count($exclude_statuses), '%s'));

        $sql = "SELECT * FROM {$this->bookings_table}
                WHERE status NOT IN ($statuses_placeholder)
                AND check_in < %s
                AND check_out > %s";
        $values = $exclude_statuses;
        $values[] = $check_out;
        $values[] = $check_in;

        if ($exclude_id) {
            $sql .= " AND id != %d";
            $values[] = intval($exclude_id);
        }

        return $wpdb->get_results($wpdb->prepare($sql, $values));
    }

    /**
     * Get blocked dates that overlap a range.
     */
    public function get_overlapping_blocked_dates($start_date, $end_date) {
        global $wpdb;

        $sql = "SELECT * FROM {$this->blocked_table}
                WHERE start_date < %s
                AND end_date > %s";
        $values = array($end_date, $start_date);

        return $wpdb->get_results($wpdb->prepare($sql, $values));
    }

    /**
     * Get all blocked dates for a date range.
     */
    public function get_blocked_dates($start_date = null, $end_date = null) {
        global $wpdb;

        $sql = "SELECT * FROM {$this->blocked_table} WHERE 1=1";
        $values = array();

        if ($start_date && $end_date) {
            $sql .= " AND start_date <= %s AND end_date >= %s";
            $values[] = $end_date;
            $values[] = $start_date;
        }

        $sql .= " ORDER BY start_date ASC";

        if (!empty($values)) {
            return $wpdb->get_results($wpdb->prepare($sql, $values));
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Insert a blocked date range.
     */
    public function insert_blocked_date($data) {
        global $wpdb;

        $result = $wpdb->insert(
            $this->blocked_table,
            array(
                'start_date' => sanitize_text_field($data['start_date']),
                'end_date'   => sanitize_text_field($data['end_date']),
                'reason'     => sanitize_textarea_field($data['reason']),
            ),
            array('%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Delete a blocked date entry.
     */
    public function delete_blocked_date($blocked_id) {
        global $wpdb;
        return $wpdb->delete(
            $this->blocked_table,
            array('id' => intval($blocked_id)),
            array('%d')
        );
    }

    /**
     * Get all bookings between two dates (for calendar display).
     */
    public function get_bookings_in_range($start_date, $end_date) {
        global $wpdb;

        $sql = "SELECT * FROM {$this->bookings_table}
                WHERE status IN ('pending', 'approved')
                AND check_in <= %s
                AND check_out >= %s
                ORDER BY check_in ASC";

        return $wpdb->get_results($wpdb->prepare($sql, $end_date, $start_date));
    }

    /**
     * Check if a booking reference exists.
     */
    public function booking_reference_exists($reference) {
        global $wpdb;
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->bookings_table} WHERE booking_reference = %s",
                $reference
            )
        );
        return !empty($exists);
    }

    /**
     * Drop all plugin tables (used on uninstall).
     */
    public function drop_tables() {
        global $wpdb;
        $wpdb->query("DROP TABLE IF EXISTS {$this->bookings_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->blocked_table}");
    }
}
