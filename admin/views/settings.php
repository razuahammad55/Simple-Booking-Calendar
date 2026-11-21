<?php
/**
 * Admin: Settings page.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap sbc-admin-wrap">
    <h1><?php echo esc_html__('Simple Booking Calendar - Settings', 'simple-booking-calendar'); ?></h1>

    <?php if (!empty($message)) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html($message); ?></p></div>
    <?php endif; ?>

    <form method="post" action="" class="sbc-settings-form">
        <?php wp_nonce_field('sbc_save_settings', 'sbc_nonce'); ?>

        <h2 class="title"><?php esc_html_e('General Settings', 'simple-booking-calendar'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="admin_email"><?php esc_html_e('Admin Notification Email', 'simple-booking-calendar'); ?></label></th>
                <td><input type="email" id="admin_email" name="admin_email" value="<?php echo esc_attr($settings['admin_email'] ?? get_option('admin_email')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="calendar_title"><?php esc_html_e('Booking Calendar Title', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="calendar_title" name="calendar_title" value="<?php echo esc_attr($settings['calendar_title'] ?? 'Book Your Stay'); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="min_advance_days"><?php esc_html_e('Minimum Advance Booking (days)', 'simple-booking-calendar'); ?></label></th>
                <td><input type="number" id="min_advance_days" name="min_advance_days" min="0" value="<?php echo esc_attr(intval($settings['min_advance_days'] ?? 0)); ?>" class="small-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="max_advance_days"><?php esc_html_e('Maximum Advance Booking (days)', 'simple-booking-calendar'); ?></label></th>
                <td><input type="number" id="max_advance_days" name="max_advance_days" min="1" value="<?php echo esc_attr(intval($settings['max_advance_days'] ?? 365)); ?>" class="small-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="min_stay_nights"><?php esc_html_e('Minimum Stay (nights)', 'simple-booking-calendar'); ?></label></th>
                <td><input type="number" id="min_stay_nights" name="min_stay_nights" min="1" value="<?php echo esc_attr(intval($settings['min_stay_nights'] ?? 1)); ?>" class="small-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="max_stay_nights"><?php esc_html_e('Maximum Stay (nights)', 'simple-booking-calendar'); ?></label></th>
                <td><input type="number" id="max_stay_nights" name="max_stay_nights" min="1" value="<?php echo esc_attr(intval($settings['max_stay_nights'] ?? 30)); ?>" class="small-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="pending_expiration_hours"><?php esc_html_e('Pending Reservation Expiration (hours)', 'simple-booking-calendar'); ?></label></th>
                <td><input type="number" id="pending_expiration_hours" name="pending_expiration_hours" min="1" value="<?php echo esc_attr(intval($settings['pending_expiration_hours'] ?? 48)); ?>" class="small-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="date_format"><?php esc_html_e('Date Format', 'simple-booking-calendar'); ?></label></th>
                <td>
                    <select id="date_format" name="date_format">
                        <option value="F j, Y" <?php selected($settings['date_format'] ?? 'F j, Y', 'F j, Y'); ?>>December 31, 2026</option>
                        <option value="j F Y" <?php selected($settings['date_format'] ?? '', 'j F Y'); ?>>31 December 2026</option>
                        <option value="M j, Y" <?php selected($settings['date_format'] ?? '', 'M j, Y'); ?>>Dec 31, 2026</option>
                        <option value="d/m/Y" <?php selected($settings['date_format'] ?? '', 'd/m/Y'); ?>>31/12/2026</option>
                        <option value="m/d/Y" <?php selected($settings['date_format'] ?? '', 'm/d/Y'); ?>>12/31/2026</option>
                        <option value="Y-m-d" <?php selected($settings['date_format'] ?? '', 'Y-m-d'); ?>>2026-12-31</option>
                    </select>
                </td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e('Calendar Settings', 'simple-booking-calendar'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="first_day_of_week"><?php esc_html_e('First Day of the Week', 'simple-booking-calendar'); ?></label></th>
                <td>
                    <select id="first_day_of_week" name="first_day_of_week">
                        <option value="0" <?php selected(intval($settings['first_day_of_week'] ?? 0), 0); ?>><?php esc_html_e('Sunday', 'simple-booking-calendar'); ?></option>
                        <option value="1" <?php selected(intval($settings['first_day_of_week'] ?? 0), 1); ?>><?php esc_html_e('Monday', 'simple-booking-calendar'); ?></option>
                        <option value="6" <?php selected(intval($settings['first_day_of_week'] ?? 0), 6); ?>><?php esc_html_e('Saturday', 'simple-booking-calendar'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="calendar_start_month"><?php esc_html_e('Calendar Start Month', 'simple-booking-calendar'); ?></label></th>
                <td>
                    <select id="calendar_start_month" name="calendar_start_month">
                        <option value="current" <?php selected($settings['calendar_start_month'] ?? 'current', 'current'); ?>><?php esc_html_e('Current Month', 'simple-booking-calendar'); ?></option>
                        <option value="next" <?php selected($settings['calendar_start_month'] ?? 'current', 'next'); ?>><?php esc_html_e('Next Month', 'simple-booking-calendar'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="color_available"><?php esc_html_e('Available Date Color', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="color_available" name="color_available" value="<?php echo esc_attr($settings['color_available'] ?? '#ffffff'); ?>" class="sbc-color-field"></td>
            </tr>
            <tr>
                <th scope="row"><label for="color_unavailable"><?php esc_html_e('Unavailable Date Color', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="color_unavailable" name="color_unavailable" value="<?php echo esc_attr($settings['color_unavailable'] ?? '#f0f0f0'); ?>" class="sbc-color-field"></td>
            </tr>
            <tr>
                <th scope="row"><label for="color_selected"><?php esc_html_e('Selected Date Color', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="color_selected" name="color_selected" value="<?php echo esc_attr($settings['color_selected'] ?? '#2271b1'); ?>" class="sbc-color-field"></td>
            </tr>
            <tr>
                <th scope="row"><label for="color_pending"><?php esc_html_e('Pending Booking Color', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="color_pending" name="color_pending" value="<?php echo esc_attr($settings['color_pending'] ?? '#ffd700'); ?>" class="sbc-color-field"></td>
            </tr>
            <tr>
                <th scope="row"><label for="color_approved"><?php esc_html_e('Approved Booking Color', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="color_approved" name="color_approved" value="<?php echo esc_attr($settings['color_approved'] ?? '#46b450'); ?>" class="sbc-color-field"></td>
            </tr>
            <tr>
                <th scope="row"><label for="color_blocked"><?php esc_html_e('Blocked Date Color', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="color_blocked" name="color_blocked" value="<?php echo esc_attr($settings['color_blocked'] ?? '#dc3232'); ?>" class="sbc-color-field"></td>
            </tr>
        </table>

        <h2 class="title"><?php esc_html_e('Email Settings', 'simple-booking-calendar'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Email Notifications', 'simple-booking-calendar'); ?></th>
                <td>
                    <label><input type="checkbox" name="enable_admin_notifications" value="1" <?php checked(!empty($settings['enable_admin_notifications'])); ?>> <?php esc_html_e('Notify administrator of new bookings', 'simple-booking-calendar'); ?></label><br>
                    <label><input type="checkbox" name="enable_guest_notifications" value="1" <?php checked(!empty($settings['enable_guest_notifications'])); ?>> <?php esc_html_e('Send confirmation to guests after submission', 'simple-booking-calendar'); ?></label><br>
                    <label><input type="checkbox" name="enable_status_notifications" value="1" <?php checked(!empty($settings['enable_status_notifications'])); ?>> <?php esc_html_e('Notify guests when booking status changes', 'simple-booking-calendar'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="email_sender_name"><?php esc_html_e('Email Sender Name', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="email_sender_name" name="email_sender_name" value="<?php echo esc_attr($settings['email_sender_name'] ?? get_bloginfo('name')); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="email_sender_address"><?php esc_html_e('Email Sender Address', 'simple-booking-calendar'); ?></label></th>
                <td><input type="email" id="email_sender_address" name="email_sender_address" value="<?php echo esc_attr($settings['email_sender_address'] ?? 'noreply@' . parse_url(get_bloginfo('url'), PHP_URL_HOST)); ?>" class="regular-text"></td>
            </tr>
            <tr><td colspan="2"><hr><p class="description"><?php esc_html_e('Email subjects (use {reference} for booking reference number):', 'simple-booking-calendar'); ?></p></td></tr>
            <tr>
                <th scope="row"><label for="subject_admin_new"><?php esc_html_e('Admin - New Booking', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="subject_admin_new" name="subject_admin_new" value="<?php echo esc_attr($settings['subject_admin_new'] ?? 'New Booking Request - {reference}'); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="subject_guest_confirmation"><?php esc_html_e('Guest - Confirmation', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="subject_guest_confirmation" name="subject_guest_confirmation" value="<?php echo esc_attr($settings['subject_guest_confirmation'] ?? 'Your Booking Request - {reference}'); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="subject_guest_approved"><?php esc_html_e('Guest - Approved', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="subject_guest_approved" name="subject_guest_approved" value="<?php echo esc_attr($settings['subject_guest_approved'] ?? 'Your Booking is Approved - {reference}'); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="subject_guest_rejected"><?php esc_html_e('Guest - Rejected', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="subject_guest_rejected" name="subject_guest_rejected" value="<?php echo esc_attr($settings['subject_guest_rejected'] ?? 'Your Booking Request Update - {reference}'); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label for="subject_guest_cancelled"><?php esc_html_e('Guest - Cancelled', 'simple-booking-calendar'); ?></label></th>
                <td><input type="text" id="subject_guest_cancelled" name="subject_guest_cancelled" value="<?php echo esc_attr($settings['subject_guest_cancelled'] ?? 'Your Booking is Cancelled - {reference}'); ?>" class="regular-text"></td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', 'simple-booking-calendar'), 'primary', 'sbc_save_settings'); ?>
    </form>

    <h2 class="title"><?php esc_html_e('Shortcode', 'simple-booking-calendar'); ?></h2>
    <p><?php esc_html_e('Use the following shortcode on any page or post to display the booking calendar:', 'simple-booking-calendar'); ?></p>
    <code>[simple_booking_calendar]</code>
    <p class="description"><?php esc_html_e('This shortcode is also compatible with Elementor\'s Shortcode widget.', 'simple-booking-calendar'); ?></p>
</div>
