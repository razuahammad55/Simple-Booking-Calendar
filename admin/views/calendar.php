<?php
/**
 * Admin: Calendar management page.
 */
if (!defined('ABSPATH')) {
    exit;
}

$month_name = date_i18n('F Y', strtotime("$year-$month-01"));
$prev_year = $month === 1 ? $year - 1 : $year;
$prev_month = $month === 1 ? 12 : $month - 1;
$next_year = $month === 12 ? $year + 1 : $year;
$next_month = $month === 12 ? 1 : $month + 1;
?>
<div class="wrap sbc-admin-wrap">
    <h1><?php echo esc_html__('Calendar Management', 'simple-booking-calendar'); ?></h1>

    <div class="sbc-calendar-toolbar">
        <a href="<?php echo esc_url(admin_url('admin.php?page=sbc-calendar&year=' . $prev_year . '&month=' . $prev_month)); ?>" class="button sbc-nav-btn" data-dir="prev">&laquo; <?php esc_html_e('Previous', 'simple-booking-calendar'); ?></a>
        <h2 class="sbc-calendar-title" id="sbc-calendar-title"><?php echo esc_html($month_name); ?></h2>
        <a href="<?php echo esc_url(admin_url('admin.php?page=sbc-calendar&year=' . $next_year . '&month=' . $next_month)); ?>" class="button sbc-nav-btn" data-dir="next"><?php esc_html_e('Next', 'simple-booking-calendar'); ?> &raquo;</a>
        <button type="button" class="button button-primary sbc-block-btn" id="sbc-block-trigger"><?php esc_html_e('Block Dates', 'simple-booking-calendar'); ?></button>
    </div>

    <div class="sbc-legend">
        <span class="sbc-legend-item"><span class="sbc-legend-color" style="background:<?php echo esc_attr($settings['color_available'] ?? '#ffffff'); ?>;border:1px solid #ddd;"></span> <?php esc_html_e('Available', 'simple-booking-calendar'); ?></span>
        <span class="sbc-legend-item"><span class="sbc-legend-color" style="background:<?php echo esc_attr($settings['color_pending'] ?? '#ffd700'); ?>;"></span> <?php esc_html_e('Pending', 'simple-booking-calendar'); ?></span>
        <span class="sbc-legend-item"><span class="sbc-legend-color" style="background:<?php echo esc_attr($settings['color_approved'] ?? '#46b450'); ?>;"></span> <?php esc_html_e('Approved', 'simple-booking-calendar'); ?></span>
        <span class="sbc-legend-item"><span class="sbc-legend-color" style="background:<?php echo esc_attr($settings['color_blocked'] ?? '#dc3232'); ?>;"></span> <?php esc_html_e('Blocked', 'simple-booking-calendar'); ?></span>
        <span class="sbc-legend-item"><span class="sbc-legend-color" style="background:<?php echo esc_attr($settings['color_unavailable'] ?? '#f0f0f0'); ?>;"></span> <?php esc_html_e('Past / Unavailable', 'simple-booking-calendar'); ?></span>
    </div>

    <div id="sbc-admin-calendar-wrap" class="sbc-admin-calendar" data-year="<?php echo esc_attr($year); ?>" data-month="<?php echo esc_attr($month); ?>">
        <?php include SBC_PLUGIN_DIR . 'admin/views/partials/calendar-grid.php'; ?>
    </div>

    <!-- Block dates modal -->
    <div id="sbc-block-modal" class="sbc-modal" style="display:none;">
        <div class="sbc-modal-overlay"></div>
        <div class="sbc-modal-content sbc-modal-sm">
            <span class="sbc-modal-close">&times;</span>
            <h3><?php esc_html_e('Block Date Range', 'simple-booking-calendar'); ?></h3>
            <form id="sbc-block-form">
                <div class="sbc-form-row">
                    <label for="sbc-block-start"><?php esc_html_e('Start Date', 'simple-booking-calendar'); ?></label>
                    <input type="date" id="sbc-block-start" required>
                </div>
                <div class="sbc-form-row">
                    <label for="sbc-block-end"><?php esc_html_e('End Date', 'simple-booking-calendar'); ?></label>
                    <input type="date" id="sbc-block-end" required>
                </div>
                <div class="sbc-form-row">
                    <label for="sbc-block-reason"><?php esc_html_e('Reason (internal note)', 'simple-booking-calendar'); ?></label>
                    <textarea id="sbc-block-reason" rows="3" placeholder="<?php esc_attr_e('e.g., Maintenance, Holiday...', 'simple-booking-calendar'); ?>"></textarea>
                </div>
                <div class="sbc-form-row sbc-modal-actions">
                    <button type="button" class="button sbc-modal-cancel"><?php esc_html_e('Cancel', 'simple-booking-calendar'); ?></button>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Block Dates', 'simple-booking-calendar'); ?></button>
                </div>
            </form>
            <p class="sbc-note"><?php esc_html_e('Note: Blocked dates prevent guests from selecting them. Guests with check-out on the same day as a block start are NOT considered overlapping (back-to-back logic).', 'simple-booking-calendar'); ?></p>
        </div>
    </div>
</div>
