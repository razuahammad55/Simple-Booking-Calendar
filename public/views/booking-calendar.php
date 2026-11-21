<?php
/**
 * Frontend: Booking calendar main view (shortcode output).
 */
if (!defined('ABSPATH')) {
    exit;
}

$settings = get_option('sbc_settings');
$start_month = $settings['calendar_start_month'] ?? 'current';

$year = intval(current_time('Y'));
$month = intval(current_time('m'));
if ($start_month === 'next') {
    $next = strtotime('+1 month');
    $year = intval(date('Y', $next));
    $month = intval(date('m', $next));
}

$availability = $this->plugin->availability->get_month_availability($year, $month);
?>
<div class="sbc-wrapper" id="sbc-booking-calendar">
    <?php if (!empty($title)) : ?>
        <h3 class="sbc-title"><?php echo esc_html($title); ?></h3>
    <?php endif; ?>

    <div class="sbc-calendar-section">
        <div class="sbc-cal-header">
            <button type="button" class="sbc-cal-nav sbc-prev" aria-label="<?php esc_attr_e('Previous month', 'simple-booking-calendar'); ?>">&lsaquo;</button>
            <h4 class="sbc-cal-title" id="sbc-cal-title"><?php echo esc_html(date_i18n('F Y', strtotime("$year-$month-01"))); ?></h4>
            <button type="button" class="sbc-cal-nav sbc-next" aria-label="<?php esc_attr_e('Next month', 'simple-booking-calendar'); ?>">&rsaquo;</button>
        </div>

        <div class="sbc-legend">
            <span class="sbc-legend-item"><span class="sbc-dot sbc-dot-available"></span> <?php esc_html_e('Available', 'simple-booking-calendar'); ?></span>
            <span class="sbc-legend-item"><span class="sbc-dot sbc-dot-selected"></span> <?php esc_html_e('Selected', 'simple-booking-calendar'); ?></span>
            <span class="sbc-legend-item"><span class="sbc-dot sbc-dot-booked"></span> <?php esc_html_e('Booked', 'simple-booking-calendar'); ?></span>
            <span class="sbc-legend-item"><span class="sbc-dot sbc-dot-blocked"></span> <?php esc_html_e('Blocked', 'simple-booking-calendar'); ?></span>
        </div>

        <div class="sbc-prompt" id="sbc-prompt">
            <div class="sbc-prompt-step sbc-prompt-active" data-step="1">
                <span class="sbc-prompt-num">1</span>
                <span class="sbc-prompt-text"><?php esc_html_e('Click an available date to select your check-in', 'simple-booking-calendar'); ?></span>
            </div>
            <div class="sbc-prompt-step" data-step="2">
                <span class="sbc-prompt-num">2</span>
                <span class="sbc-prompt-text"><?php esc_html_e('Click a later date to select your check-out', 'simple-booking-calendar'); ?></span>
            </div>
            <div class="sbc-prompt-step" data-step="3">
                <span class="sbc-prompt-num">3</span>
                <span class="sbc-prompt-text"><?php esc_html_e('Fill in your details and submit your request', 'simple-booking-calendar'); ?></span>
            </div>
        </div>

        <div class="sbc-calendar-loading" id="sbc-calendar-loading" style="display:none;">
            <div class="sbc-spinner"></div>
        </div>

        <div class="sbc-calendar-container" id="sbc-calendar-container" data-year="<?php echo esc_attr($year); ?>" data-month="<?php echo esc_attr($month); ?>">
            <?php include SBC_PLUGIN_DIR . 'public/views/partials/calendar-month.php'; ?>
        </div>

        <div class="sbc-selection-info" id="sbc-selection-info" style="display:none;">
            <div class="sbc-sel-row">
                <div class="sbc-sel-block">
                    <label><?php esc_html_e('Check-in', 'simple-booking-calendar'); ?></label>
                    <span class="sbc-sel-date" id="sbc-sel-checkin">—</span>
                </div>
                <div class="sbc-sel-block">
                    <label><?php esc_html_e('Check-out', 'simple-booking-calendar'); ?></label>
                    <span class="sbc-sel-date" id="sbc-sel-checkout">—</span>
                </div>
                <div class="sbc-sel-block sbc-sel-nights">
                    <label><?php esc_html_e('Nights', 'simple-booking-calendar'); ?></label>
                    <span class="sbc-sel-date" id="sbc-sel-nights">0</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking form -->
    <div class="sbc-form-section" id="sbc-form-section" style="display:none;">
        <h4 class="sbc-form-title"><?php esc_html_e('Your Details', 'simple-booking-calendar'); ?></h4>

        <form id="sbc-booking-form" class="sbc-booking-form" novalidate>
            <!-- Honeypot -->
            <input type="text" name="sbc_hp" class="sbc-hp" value="" tabindex="-1" autocomplete="off" aria-hidden="true">

            <div class="sbc-form-row">
                <label for="sbc-guest-name"><?php esc_html_e('Full Name', 'simple-booking-calendar'); ?> <span class="sbc-required">*</span></label>
                <input type="text" id="sbc-guest-name" name="guest_name" required>
            </div>

            <div class="sbc-form-row">
                <label for="sbc-guest-email"><?php esc_html_e('Email Address', 'simple-booking-calendar'); ?> <span class="sbc-required">*</span></label>
                <input type="email" id="sbc-guest-email" name="guest_email" required>
            </div>

            <div class="sbc-form-row">
                <label for="sbc-guest-phone"><?php esc_html_e('Phone Number', 'simple-booking-calendar'); ?> <span class="sbc-required">*</span></label>
                <input type="tel" id="sbc-guest-phone" name="guest_phone" required>
            </div>

            <div class="sbc-form-row">
                <label for="sbc-guest-count"><?php esc_html_e('Number of Guests', 'simple-booking-calendar'); ?></label>
                <select id="sbc-guest-count" name="guest_count">
                    <?php for ($i = 1; $i <= 10; $i++) : ?>
                        <option value="<?php echo esc_attr($i); ?>"><?php echo esc_html($i); ?></option>
                    <?php endfor; ?>
                    <option value="11+"><?php esc_html_e('11+ (specify in notes)', 'simple-booking-calendar'); ?></option>
                </select>
            </div>

            <div class="sbc-form-row">
                <label for="sbc-guest-notes"><?php esc_html_e('Additional Notes / Special Requests', 'simple-booking-calendar'); ?></label>
                <textarea id="sbc-guest-notes" name="guest_notes" rows="4"></textarea>
            </div>

            <div class="sbc-dates-summary" id="sbc-dates-summary">
                <!-- Dates summary injected by JS -->
            </div>

            <input type="hidden" name="check_in" id="sbc-check-in-input" value="">
            <input type="hidden" name="check_out" id="sbc-check-out-input" value="">

            <div class="sbc-form-row">
                <button type="submit" class="sbc-submit-btn" id="sbc-submit-btn">
                    <?php esc_html_e('Submit Reservation Request', 'simple-booking-calendar'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Success/error messages -->
    <div class="sbc-message" id="sbc-message" style="display:none;"></div>
</div>
