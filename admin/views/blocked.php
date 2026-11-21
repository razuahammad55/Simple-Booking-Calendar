<?php
/**
 * Admin: Blocked Dates page.
 */
if (!defined('ABSPATH')) {
    exit;
}

$date_format = get_option('sbc_settings')['date_format'] ?? 'F j, Y';
?>
<div class="wrap sbc-admin-wrap">
    <h1><?php echo esc_html__('Blocked Dates', 'simple-booking-calendar'); ?></h1>
    <p><?php esc_html_e('Manage manually blocked (unavailable) dates. Blocked dates cannot be selected by guests.', 'simple-booking-calendar'); ?></p>

    <table class="wp-list-table widefat fixed striped sbc-blocked-table">
        <thead>
            <tr>
                <th class="manage-column" style="width:60px;"><?php esc_html_e('ID', 'simple-booking-calendar'); ?></th>
                <th class="manage-column"><?php esc_html_e('Start Date', 'simple-booking-calendar'); ?></th>
                <th class="manage-column"><?php esc_html_e('End Date', 'simple-booking-calendar'); ?></th>
                <th class="manage-column"><?php esc_html_e('Nights', 'simple-booking-calendar'); ?></th>
                <th class="manage-column"><?php esc_html_e('Reason', 'simple-booking-calendar'); ?></th>
                <th class="manage-column"><?php esc_html_e('Created', 'simple-booking-calendar'); ?></th>
                <th class="manage-column" style="width:100px;"><?php esc_html_e('Actions', 'simple-booking-calendar'); ?></th>
            </tr>
        </thead>
        <tbody id="sbc-blocked-list">
        <?php if (empty($blocked_dates)) : ?>
            <tr>
                <td colspan="7" class="sbc-empty-state"><?php esc_html_e('No blocked dates. Use the Calendar page to block dates.', 'simple-booking-calendar'); ?></td>
            </tr>
        <?php else : ?>
            <?php foreach ($blocked_dates as $block) :
                $d1 = new DateTime($block->start_date);
                $d2 = new DateTime($block->end_date);
                $nights = $d1->diff($d2)->days;
            ?>
            <tr data-id="<?php echo esc_attr($block->id); ?>">
                <td><?php echo esc_html($block->id); ?></td>
                <td><?php echo esc_html(date_i18n($date_format, strtotime($block->start_date))); ?></td>
                <td><?php echo esc_html(date_i18n($date_format, strtotime($block->end_date))); ?></td>
                <td><?php echo esc_html($nights); ?></td>
                <td><?php echo esc_html($block->reason ?: '—'); ?></td>
                <td><?php echo esc_html(date_i18n('M j, Y', strtotime($block->created_at))); ?></td>
                <td>
                    <button type="button" class="button button-small button-link-delete sbc-btn-unblock" data-id="<?php echo esc_attr($block->id); ?>"><?php esc_html_e('Remove', 'simple-booking-calendar'); ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>

    <p class="sbc-note">
        <a href="<?php echo esc_url(admin_url('admin.php?page=sbc-calendar')); ?>" class="button button-primary"><?php esc_html_e('Open Calendar to Block Dates', 'simple-booking-calendar'); ?></a>
    </p>
</div>
