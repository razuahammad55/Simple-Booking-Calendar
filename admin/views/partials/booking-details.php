<?php
/**
 * Admin: Booking details modal content.
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!isset($booking)) {
    return;
}

$date_format = $settings['date_format'] ?? 'F j, Y';
?>
<h3><?php echo esc_html(sprintf(__('Booking %s', 'simple-booking-calendar'), $booking->booking_reference)); ?></h3>
<table class="widefat striped">
    <tr>
        <th style="width:150px;"><?php esc_html_e('Reference', 'simple-booking-calendar'); ?></th>
        <td><?php echo esc_html($booking->booking_reference); ?></td>
    </tr>
    <tr>
        <th><?php esc_html_e('Status', 'simple-booking-calendar'); ?></th>
        <td><span class="sbc-status-badge sbc-status-<?php echo esc_attr($booking->status); ?>"><?php echo esc_html(ucfirst($booking->status)); ?></span></td>
    </tr>
    <tr>
        <th><?php esc_html_e('Guest Name', 'simple-booking-calendar'); ?></th>
        <td><?php echo esc_html($booking->guest_name); ?></td>
    </tr>
    <tr>
        <th><?php esc_html_e('Email', 'simple-booking-calendar'); ?></th>
        <td><a href="mailto:<?php echo esc_attr($booking->guest_email); ?>"><?php echo esc_html($booking->guest_email); ?></a></td>
    </tr>
    <tr>
        <th><?php esc_html_e('Phone', 'simple-booking-calendar'); ?></th>
        <td><?php echo esc_html($booking->guest_phone); ?></td>
    </tr>
    <tr>
        <th><?php esc_html_e('Check-in', 'simple-booking-calendar'); ?></th>
        <td><?php echo esc_html(date_i18n($date_format, strtotime($booking->check_in))); ?></td>
    </tr>
    <tr>
        <th><?php esc_html_e('Check-out', 'simple-booking-calendar'); ?></th>
        <td><?php echo esc_html(date_i18n($date_format, strtotime($booking->check_out))); ?></td>
    </tr>
    <tr>
        <th><?php esc_html_e('Nights', 'simple-booking-calendar'); ?></th>
        <td><?php
            $d1 = new DateTime($booking->check_in);
            $d2 = new DateTime($booking->check_out);
            echo esc_html($d1->diff($d2)->days);
        ?></td>
    </tr>
    <tr>
        <th><?php esc_html_e('Number of Guests', 'simple-booking-calendar'); ?></th>
        <td><?php echo esc_html($booking->guest_count); ?></td>
    </tr>
    <?php if (!empty($booking->guest_notes)) : ?>
    <tr>
        <th><?php esc_html_e('Notes / Requests', 'simple-booking-calendar'); ?></th>
        <td><?php echo nl2br(esc_html($booking->guest_notes)); ?></td>
    </tr>
    <?php endif; ?>
    <tr>
        <th><?php esc_html_e('Submitted', 'simple-booking-calendar'); ?></th>
        <td><?php echo esc_html(date_i18n('F j, Y g:i A', strtotime($booking->created_at))); ?></td>
    </tr>
    <tr>
        <th><?php esc_html_e('Last Updated', 'simple-booking-calendar'); ?></th>
        <td><?php echo esc_html(date_i18n('F j, Y g:i A', strtotime($booking->updated_at))); ?></td>
    </tr>
</table>
<div class="sbc-modal-actions" style="margin-top:16px;">
    <?php if ($booking->status === 'pending') : ?>
        <button type="button" class="button button-primary sbc-btn-approve" data-id="<?php echo esc_attr($booking->id); ?>"><?php esc_html_e('Approve', 'simple-booking-calendar'); ?></button>
        <button type="button" class="button sbc-btn-reject" data-id="<?php echo esc_attr($booking->id); ?>"><?php esc_html_e('Reject', 'simple-booking-calendar'); ?></button>
    <?php endif; ?>
    <?php if ($booking->status === 'approved') : ?>
        <button type="button" class="button sbc-btn-cancel" data-id="<?php echo esc_attr($booking->id); ?>"><?php esc_html_e('Cancel', 'simple-booking-calendar'); ?></button>
    <?php endif; ?>
    <button type="button" class="button sbc-modal-cancel"><?php esc_html_e('Close', 'simple-booking-calendar'); ?></button>
</div>
