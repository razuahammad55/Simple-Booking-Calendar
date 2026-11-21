<?php
/**
 * Admin: All Bookings page.
 */
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap sbc-admin-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('All Bookings', 'simple-booking-calendar'); ?></h1>
    <hr class="wp-header-end">

    <?php
    $statuses = array(
        ''          => __('All Statuses', 'simple-booking-calendar'),
        'pending'   => __('Pending', 'simple-booking-calendar'),
        'approved'  => __('Approved', 'simple-booking-calendar'),
        'rejected'  => __('Rejected', 'simple-booking-calendar'),
        'cancelled' => __('Cancelled', 'simple-booking-calendar'),
    );
    $current_status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
    $current_search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    ?>

    <div class="sbc-filters">
        <form method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
            <input type="hidden" name="page" value="sbc-bookings">
            <div class="sbc-filter-row">
                <input type="text" name="s" placeholder="<?php esc_attr_e('Search name, email, reference, phone...', 'simple-booking-calendar'); ?>" value="<?php echo esc_attr($current_search); ?>" class="regular-text">
                <select name="status">
                    <?php foreach ($statuses as $s_val => $s_label) : ?>
                        <option value="<?php echo esc_attr($s_val); ?>" <?php selected($current_status, $s_val); ?>><?php echo esc_html($s_label); ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="date_from" placeholder="<?php esc_attr_e('Check-in from (YYYY-MM-DD)', 'simple-booking-calendar'); ?>" value="<?php echo esc_attr($args['date_from']); ?>" class="sbc-date-input">
                <input type="text" name="date_to" placeholder="<?php esc_attr_e('Check-in to (YYYY-MM-DD)', 'simple-booking-calendar'); ?>" value="<?php echo esc_attr($args['date_to']); ?>" class="sbc-date-input">
                <?php submit_button(__('Filter', 'simple-booking-calendar'), 'button', 'submit', false); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=sbc-bookings')); ?>" class="button"><?php esc_html_e('Reset', 'simple-booking-calendar'); ?></a>
            </div>
        </form>
    </div>

    <table class="wp-list-table widefat fixed striped sbc-bookings-table">
        <thead>
            <tr>
                <th class="manage-column column-reference"><?php esc_html_e('Reference', 'simple-booking-calendar'); ?></th>
                <th class="manage-column column-name"><?php esc_html_e('Guest Name', 'simple-booking-calendar'); ?></th>
                <th class="manage-column column-email"><?php esc_html_e('Email', 'simple-booking-calendar'); ?></th>
                <th class="manage-column column-phone"><?php esc_html_e('Phone', 'simple-booking-calendar'); ?></th>
                <th class="manage-column column-dates"><?php esc_html_e('Check-in / Check-out', 'simple-booking-calendar'); ?></th>
                <th class="manage-column column-guests"><?php esc_html_e('Guests', 'simple-booking-calendar'); ?></th>
                <th class="manage-column column-status"><?php esc_html_e('Status', 'simple-booking-calendar'); ?></th>
                <th class="manage-column column-date"><?php esc_html_e('Submitted', 'simple-booking-calendar'); ?></th>
                <th class="manage-column column-actions"><?php esc_html_e('Actions', 'simple-booking-calendar'); ?></th>
            </tr>
        </thead>
        <tbody id="sbc-bookings-list">
        <?php if (empty($result['bookings'])) : ?>
            <tr>
                <td colspan="9" class="sbc-empty-state"><?php esc_html_e('No bookings found.', 'simple-booking-calendar'); ?></td>
            </tr>
        <?php else : ?>
            <?php foreach ($result['bookings'] as $b) :
                $status_class = 'sbc-status-' . esc_attr($b->status);
                $date_fmt = $settings['date_format'] ?? 'F j, Y';
            ?>
            <tr data-id="<?php echo esc_attr($b->id); ?>" class="sbc-booking-row">
                <td class="column-reference"><strong><?php echo esc_html($b->booking_reference); ?></strong></td>
                <td class="column-name"><?php echo esc_html($b->guest_name); ?></td>
                <td class="column-email"><a href="mailto:<?php echo esc_attr($b->guest_email); ?>"><?php echo esc_html($b->guest_email); ?></a></td>
                <td class="column-phone"><?php echo esc_html($b->guest_phone); ?></td>
                <td class="column-dates">
                    <?php echo esc_html(date_i18n($date_fmt, strtotime($b->check_in))); ?><br>
                    <small><?php echo esc_html__('to', 'simple-booking-calendar'); ?> <?php echo esc_html(date_i18n($date_fmt, strtotime($b->check_out))); ?></small>
                </td>
                <td class="column-guests"><?php echo esc_html($b->guest_count); ?></td>
                <td class="column-status"><span class="sbc-status-badge <?php echo esc_attr($status_class); ?>"><?php echo esc_html(ucfirst($b->status)); ?></span></td>
                <td class="column-date"><?php echo esc_html(date_i18n('M j, Y g:i A', strtotime($b->created_at))); ?></td>
                <td class="column-actions sbc-actions-cell">
                    <button type="button" class="button button-small sbc-btn-view" data-id="<?php echo esc_attr($b->id); ?>"><?php esc_html_e('View', 'simple-booking-calendar'); ?></button>
                    <?php if ($b->status === 'pending') : ?>
                        <button type="button" class="button button-small button-primary sbc-btn-approve" data-id="<?php echo esc_attr($b->id); ?>"><?php esc_html_e('Approve', 'simple-booking-calendar'); ?></button>
                        <button type="button" class="button button-small sbc-btn-reject" data-id="<?php echo esc_attr($b->id); ?>"><?php esc_html_e('Reject', 'simple-booking-calendar'); ?></button>
                    <?php endif; ?>
                    <?php if ($b->status === 'approved') : ?>
                        <button type="button" class="button button-small sbc-btn-cancel" data-id="<?php echo esc_attr($b->id); ?>"><?php esc_html_e('Cancel', 'simple-booking-calendar'); ?></button>
                    <?php endif; ?>
                    <?php if ($b->status === 'rejected' || $b->status === 'cancelled') : ?>
                        <button type="button" class="button button-small button-primary sbc-btn-approve" data-id="<?php echo esc_attr($b->id); ?>"><?php esc_html_e('Reinstate', 'simple-booking-calendar'); ?></button>
                    <?php endif; ?>
                    <button type="button" class="button button-small button-link-delete sbc-btn-delete" data-id="<?php echo esc_attr($b->id); ?>"><?php esc_html_e('Delete', 'simple-booking-calendar'); ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($result['pages'] > 1) :
        $paged = $args['paged'];
        $base = add_query_arg(array(
            'page'      => 'sbc-bookings',
            'status'    => $current_status,
            's'         => urlencode($current_search),
            'date_from' => $args['date_from'],
            'date_to'   => $args['date_to'],
        ), admin_url('admin.php'));
    ?>
    <div class="sbc-pagination">
        <?php
        echo wp_kses_post(paginate_links(array(
            'base'      => $base . '&paged=%#%',
            'format'    => '',
            'current'   => $paged,
            'total'     => $result['pages'],
            'prev_text' => __('&laquo; Previous', 'simple-booking-calendar'),
            'next_text' => __('Next &raquo;', 'simple-booking-calendar'),
        )));
        ?>
    </div>
    <?php endif; ?>
</div>

<!-- Booking details modal -->
<div id="sbc-booking-modal" class="sbc-modal" style="display:none;">
    <div class="sbc-modal-overlay"></div>
    <div class="sbc-modal-content">
        <span class="sbc-modal-close">&times;</span>
        <div id="sbc-modal-body"></div>
    </div>
</div>
