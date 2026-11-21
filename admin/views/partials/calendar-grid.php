<?php
/**
 * Admin: Calendar grid partial.
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!isset($year)) { $year = intval(current_time('Y')); }
if (!isset($month)) { $month = intval(current_time('m')); }
if (!isset($availability)) { $availability = $this->plugin->availability->get_month_availability($year, $month); }

$first_day_of_week = intval($settings['first_day_of_week'] ?? 0);
$day_names = array(
    __('Sun', 'simple-booking-calendar'),
    __('Mon', 'simple-booking-calendar'),
    __('Tue', 'simple-booking-calendar'),
    __('Wed', 'simple-booking-calendar'),
    __('Thu', 'simple-booking-calendar'),
    __('Fri', 'simple-booking-calendar'),
    __('Sat', 'simple-booking-calendar'),
);
// Reorder days.
$ordered_days = array();
for ($i = 0; $i < 7; $i++) {
    $ordered_days[] = $day_names[($first_day_of_week + $i) % 7];
}

$first_day_ts = strtotime("$year-$month-01");
$total_days = intval(date('t', $first_day_ts));
$start_wday = intval(date('w', $first_day_ts));
// Adjust for first day of week.
$leading_blanks = ($start_wday - $first_day_of_week + 7) % 7;

$today = current_time('Y-m-d');
?>
<table class="sbc-admin-cal-grid">
    <thead>
        <tr>
            <?php foreach ($ordered_days as $d) : ?>
                <th><?php echo esc_html($d); ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php
        $day_count = 0;
        $total_cells = $leading_blanks + $total_days;
        $rows = ceil($total_cells / 7);

        for ($r = 0; $r < $rows; $r++) : ?>
            <tr>
            <?php for ($c = 0; $c < 7; $c++) :
                $cell_idx = $r * 7 + $c;
                if ($cell_idx < $leading_blanks) : ?>
                    <td class="sbc-day sbc-day-empty"></td>
                <?php elseif ($day_count < $total_days) :
                    $day_count++;
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $day_count);
                    $info = $availability[$date];
                    $classes = array('sbc-day');
                    if ($info['status'] === 'past') $classes[] = 'sbc-day-past';
                    if ($info['status'] === 'available') $classes[] = 'sbc-day-available';
                    if ($info['status'] === 'pending') $classes[] = 'sbc-day-pending';
                    if ($info['status'] === 'approved') $classes[] = 'sbc-day-approved';
                    if ($info['status'] === 'blocked') $classes[] = 'sbc-day-blocked';
                    if ($info['status'] === 'unavailable') $classes[] = 'sbc-day-unavailable';
                    if ($date === $today) $classes[] = 'sbc-day-today';
                ?>
                    <td class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-date="<?php echo esc_attr($date); ?>">
                        <span class="sbc-day-num"><?php echo esc_html($day_count); ?></span>
                        <?php if ($info['booking_id']) : ?>
                            <span class="sbc-day-badge" title="<?php
                                foreach ($bookings as $b) {
                                    if ($b->id == $info['booking_id']) {
                                        echo esc_attr($b->guest_name . ' (' . $b->booking_reference . ')');
                                        break;
                                    }
                                }
                            ?>">B</span>
                        <?php endif; ?>
                        <?php if ($info['block_id']) : ?>
                            <span class="sbc-day-block-badge">X</span>
                        <?php endif; ?>
                    </td>
                <?php else : ?>
                    <td class="sbc-day sbc-day-empty"></td>
                <?php endif; ?>
            <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>
