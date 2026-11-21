<?php
/**
 * Frontend: Calendar month partial.
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!isset($year)) { $year = intval(current_time('Y')); }
if (!isset($month)) { $month = intval(current_time('m')); }
if (!isset($availability)) { $availability = $this->plugin->availability->get_month_availability($year, $month); }

$settings = get_option('sbc_settings');
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

$ordered_days = array();
for ($i = 0; $i < 7; $i++) {
    $ordered_days[] = $day_names[($first_day_of_week + $i) % 7];
}

$first_day_ts = strtotime("$year-$month-01");
$total_days = intval(date('t', $first_day_ts));
$start_wday = intval(date('w', $first_day_ts));
$leading_blanks = ($start_wday - $first_day_of_week + 7) % 7;

$today = current_time('Y-m-d');
?>
<table class="sbc-cal-grid" role="grid">
    <thead>
        <tr>
            <?php foreach ($ordered_days as $d) : ?>
                <th scope="col"><?php echo esc_html($d); ?></th>
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
                    <td class="sbc-cal-day sbc-day-empty"></td>
                <?php elseif ($day_count < $total_days) :
                    $day_count++;
                    $date = sprintf('%04d-%02d-%02d', $year, $month, $day_count);
                    $info = $availability[$date];

                    $classes = array('sbc-cal-day');
                    if ($info['past'] || $info['status'] === 'past') {
                        $classes[] = 'sbc-day-past';
                    } else {
                        switch ($info['status']) {
                            case 'available':
                                $classes[] = 'sbc-day-available';
                                $classes[] = 'sbc-selectable';
                                break;
                            case 'pending':
                                $classes[] = 'sbc-day-pending';
                                break;
                            case 'approved':
                                $classes[] = 'sbc-day-approved';
                                break;
                            case 'blocked':
                                $classes[] = 'sbc-day-blocked';
                                break;
                            case 'unavailable':
                                $classes[] = 'sbc-day-unavailable';
                                break;
                        }
                    }
                    if ($date === $today) {
                        $classes[] = 'sbc-day-today';
                    }
                ?>
                    <td class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-date="<?php echo esc_attr($date); ?>">
                        <span class="sbc-day-num"><?php echo esc_html($day_count); ?></span>
                    </td>
                <?php else : ?>
                    <td class="sbc-cal-day sbc-day-empty"></td>
                <?php endif; ?>
            <?php endfor; ?>
            </tr>
        <?php endfor; ?>
    </tbody>
</table>
