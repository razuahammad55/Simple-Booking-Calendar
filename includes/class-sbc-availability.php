<?php
/**
 * Availability checking class.
 *
 * @package Simple_Booking_Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

class SBC_Availability {

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
     * Check if a date range is unavailable (returns true if conflict exists).
     *
     * Uses date range overlap logic:
     * - Two ranges (A,B] and (C,D] conflict if A < D AND C < B
     * - This allows checkout date = new check-in date (back-to-back)
     *
     * @param string $check_in  Check-in date Y-m-d.
     * @param string $check_out Check-out date Y-m-d.
     * @param int    $exclude_id Booking ID to exclude (when editing).
     * @return bool True if NOT available (conflict), false if available.
     */
    public function is_range_available($check_in, $check_out, $exclude_id = null) {
        // Check against existing bookings (pending and approved block dates).
        $overlapping = $this->plugin->db->get_overlapping_bookings(
            $check_in,
            $check_out,
            array('rejected', 'cancelled'),
            $exclude_id
        );

        if (!empty($overlapping)) {
            return true;
        }

        // Check against blocked dates.
        $blocked = $this->plugin->db->get_overlapping_blocked_dates($check_in, $check_out);
        if (!empty($blocked)) {
            return true;
        }

        return false;
    }

    /**
     * Get availability data for a calendar month.
     *
     * @param int $year  Year.
     * @param int $month Month (1-12).
     * @return array Array of dates with availability status.
     */
    public function get_month_availability($year, $month) {
        $settings = get_option('sbc_settings');
        $min_advance = !empty($settings['min_advance_days']) ? intval($settings['min_advance_days']) : 0;
        $max_advance = !empty($settings['max_advance_days']) ? intval($settings['max_advance_days']) : 365;

        $today = current_time('Y-m-d');
        $min_date = date('Y-m-d', strtotime($today . ' +' . $min_advance . ' days'));
        $max_date = date('Y-m-d', strtotime($today . ' +' . $max_advance . ' days'));

        $start_date = date('Y-m-01', strtotime("$year-$month-01"));
        $end_date = date('Y-m-t', strtotime("$year-$month-01"));

        // Get all bookings in this range plus adjacent.
        $range_start = date('Y-m-01', strtotime($start_date . ' -1 month'));
        $range_end = date('Y-m-t', strtotime($end_date . ' +1 month'));
        $bookings = $this->plugin->db->get_bookings_in_range($range_start, $range_end);
        $blocked = $this->plugin->db->get_blocked_dates($range_start, $range_end);

        $availability = array();
        $days_in_month = intval(date('t', strtotime($start_date)));

        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $availability[$date] = array(
                'date'      => $date,
                'day'       => $day,
                'available' => true,
                'past'      => false,
                'status'    => 'available', // available, past, blocked, pending, approved
                'booking_id' => null,
                'block_id'  => null,
            );

            // Check if date is past min advance.
            if ($date < $min_date) {
                $availability[$date]['available'] = false;
                $availability[$date]['past'] = true;
                $availability[$date]['status'] = 'past';
                continue;
            }

            // Check if beyond max advance.
            if ($date > $max_date) {
                $availability[$date]['available'] = false;
                $availability[$date]['status'] = 'unavailable';
                continue;
            }

            // Check blocked dates.
            foreach ($blocked as $block) {
                if ($date >= $block->start_date && $date < $block->end_date) {
                    $availability[$date]['available'] = false;
                    $availability[$date]['status'] = 'blocked';
                    $availability[$date]['block_id'] = $block->id;
                    break;
                }
            }

            if (!$availability[$date]['available']) {
                continue;
            }

            // Check bookings (using same overlap logic: a date is booked if any booking
            // covers it where check_in <= date < check_out, i.e., check_out date is free for next check-in).
            foreach ($bookings as $booking) {
                if ($date >= $booking->check_in && $date < $booking->check_out) {
                    $availability[$date]['available'] = false;
                    $availability[$date]['status'] = $booking->status;
                    $availability[$date]['booking_id'] = $booking->id;
                    break;
                }
            }
        }

        return $availability;
    }

    /**
     * Get available dates for datepicker within a range.
     */
    public function get_unavailable_dates_in_range($start_date, $end_date) {
        $availability = $this->get_month_availability(
            intval(date('Y', strtotime($start_date))),
            intval(date('m', strtotime($start_date)))
        );

        // Also get next month if range spans.
        $next_month = strtotime($start_date . ' +1 month');
        if (date('Y-m', strtotime($end_date)) !== date('Y-m', strtotime($start_date))) {
            $next_avail = $this->get_month_availability(
                intval(date('Y', $next_month)),
                intval(date('m', $next_month))
            );
            $availability = array_merge($availability, $next_avail);
        }

        $unavailable = array();
        foreach ($availability as $date => $info) {
            if (!$info['available'] && $info['status'] !== 'past') {
                $unavailable[] = $date;
            }
        }

        return $unavailable;
    }
}
