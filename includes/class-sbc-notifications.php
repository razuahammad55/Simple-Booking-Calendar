<?php
/**
 * Email notifications class.
 *
 * @package Simple_Booking_Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

class SBC_Notifications {

    /**
     * Plugin instance.
     */
    private $plugin;

    /**
     * Track whether we are currently sending an SBC email (to scope filters).
     */
    private $sending = false;

    /**
     * Constructor.
     */
    public function __construct($plugin) {
        $this->plugin = $plugin;
        // Only hook phpmailer_init when we're sending our own emails (see send() method).
    }

    /**
     * Set HTML content type for emails (filter applied only during our sends).
     */
    public function set_html_content_type() {
        return 'text/html';
    }

    /**
     * Configure sender name/email via phpmailer (only during our sends).
     */
    public function config_sender($phpmailer) {
        if (!$this->sending) {
            return;
        }
        $settings = get_option('sbc_settings');
        if (!empty($settings['email_sender_name'])) {
            $phpmailer->FromName = $settings['email_sender_name'];
        }
        if (!empty($settings['email_sender_address']) && is_email($settings['email_sender_address'])) {
            $phpmailer->From = $settings['email_sender_address'];
        }
    }

    /**
     * Send an HTML email with proper content-type and sender config (scoped).
     *
     * @param string $to      Recipient email.
     * @param string $subject Email subject.
     * @param string $body    HTML email body.
     * @return bool Whether the email was sent successfully.
     */
    private function send($to, $subject, $body) {
        $this->sending = true;

        // Add filters only for this send.
        add_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
        add_action('phpmailer_init', array($this, 'config_sender'));

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $result = wp_mail($to, $subject, $body, $headers);

        // Remove filters so we don't affect other emails.
        remove_filter('wp_mail_content_type', array($this, 'set_html_content_type'));
        remove_action('phpmailer_init', array($this, 'config_sender'));

        $this->sending = false;

        return $result;
    }

    /**
     * Replace template variables in strings.
     */
    private function replace_vars($template, $vars) {
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    /**
     * Format a date for display.
     */
    private function format_date($date_str) {
        $settings = get_option('sbc_settings');
        $format = !empty($settings['date_format']) ? $settings['date_format'] : 'F j, Y';
        $ts = strtotime($date_str);
        return date_i18n($format, $ts);
    }

    /**
     * Build common email header/footer.
     */
    private function get_email_wrapper($content) {
        ob_start();
        ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #f4f4f4; color: #333;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px 0;">
<tr>
    <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); max-width: 100%;">
            <tr>
                <td style="background-color: #2271b1; padding: 24px 32px;">
                    <h1 style="margin:0; color: #ffffff; font-size: 22px; font-weight: 600;"><?php echo esc_html(wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES)); ?></h1>
                </td>
            </tr>
            <tr>
                <td style="padding: 32px;">
                    <?php echo $content; ?>
                </td>
            </tr>
            <tr>
                <td style="background-color: #f8f8f8; padding: 20px 32px; text-align: center; font-size: 13px; color: #777;">
                    <p style="margin:0;"><?php echo esc_html__('This email was sent by Simple Booking Calendar.', 'simple-booking-calendar'); ?></p>
                    <p style="margin:4px 0 0 0;"><?php echo esc_html(wp_specialchars_decode(get_bloginfo('url'), ENT_QUOTES)); ?></p>
                </td>
            </tr>
        </table>
    </td>
</tr>
</table>
</body>
</html>
        <?php
        return ob_get_clean();
    }

    /**
     * Send admin notification for new booking.
     */
    public function send_admin_notification($booking) {
        $settings = get_option('sbc_settings');
        $to = !empty($settings['admin_email']) ? $settings['admin_email'] : get_option('admin_email');

        $vars = array(
            'reference' => $booking->booking_reference,
        );

        $subject = $this->replace_vars(
            $settings['subject_admin_new'] ?? 'New Booking Request - {reference}',
            $vars
        );

        $admin_url = admin_url('admin.php?page=sbc-bookings&booking_id=' . $booking->id);

        $content = '<h2 style="margin-top:0; color:#2271b1;">' . esc_html__('New Booking Request', 'simple-booking-calendar') . '</h2>';
        $content .= '<p>' . esc_html__('A new booking request has been submitted. Details below:', 'simple-booking-calendar') . '</p>';
        $content .= '<table style="width:100%; border-collapse: collapse; margin: 20px 0;">';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; width:140px; font-weight:600;">' . esc_html__('Reference:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($booking->booking_reference) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Guest Name:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($booking->guest_name) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Email:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($booking->guest_email) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Phone:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($booking->guest_phone) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Check-in:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($this->format_date($booking->check_in)) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Check-out:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($this->format_date($booking->check_out)) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Guests:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($booking->guest_count) . '</td></tr>';
        if (!empty($booking->guest_notes)) {
            $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600; vertical-align:top;">' . esc_html__('Notes:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . nl2br(esc_html($booking->guest_notes)) . '</td></tr>';
        }
        $content .= '</table>';
        $content .= '<p style="text-align:center; margin-top: 24px;">';
        $content .= '<a href="' . esc_url($admin_url) . '" style="display:inline-block; background:#2271b1; color:#ffffff; padding:12px 28px; text-decoration:none; border-radius:4px; font-weight:600;">' . esc_html__('View Booking in Dashboard', 'simple-booking-calendar') . '</a>';
        $content .= '</p>';

        $body = $this->get_email_wrapper($content);

        return $this->send($to, $subject, $body);
    }

    /**
     * Send guest confirmation email.
     */
    public function send_guest_confirmation($booking) {
        $settings = get_option('sbc_settings');
        $to = $booking->guest_email;

        $vars = array(
            'reference' => $booking->booking_reference,
        );

        $subject = $this->replace_vars(
            $settings['subject_guest_confirmation'] ?? 'Your Booking Request - {reference}',
            $vars
        );

        $content = '<h2 style="margin-top:0; color:#2271b1;">' . esc_html__('Thank You for Your Reservation Request!', 'simple-booking-calendar') . '</h2>';
        $content .= '<p>' . sprintf(
            /* translators: %s: guest name */
            esc_html__('Dear %s,', 'simple-booking-calendar'),
            esc_html($booking->guest_name)
        ) . '</p>';
        $content .= '<p>' . esc_html__('We have received your reservation request and it is now pending administrator approval. You will receive another email once your booking is reviewed.', 'simple-booking-calendar') . '</p>';
        $content .= '<h3 style="color:#2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 8px;">' . esc_html__('Booking Details', 'simple-booking-calendar') . '</h3>';
        $content .= '<table style="width:100%; border-collapse: collapse; margin: 20px 0;">';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; width:140px; font-weight:600;">' . esc_html__('Reference:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($booking->booking_reference) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Check-in:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($this->format_date($booking->check_in)) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Check-out:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($this->format_date($booking->check_out)) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Guests:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($booking->guest_count) . '</td></tr>';
        $content .= '</table>';
        $content .= '<p style="background:#f0f6fc; border-left:4px solid #2271b1; padding:12px 16px; margin:20px 0;">' . esc_html__('Please keep your booking reference number for future correspondence.', 'simple-booking-calendar') . '</p>';

        $body = $this->get_email_wrapper($content);

        return $this->send($to, $subject, $body);
    }

    /**
     * Send booking status notification to guest.
     */
    public function send_status_notification($booking_id, $status) {
        $booking = $this->plugin->db->get_booking($booking_id);
        if (!$booking) {
            return;
        }

        $settings = get_option('sbc_settings');
        $to = $booking->guest_email;

        $vars = array('reference' => $booking->booking_reference);

        switch ($status) {
            case 'approved':
                $subject = $this->replace_vars($settings['subject_guest_approved'] ?? 'Your Booking is Approved - {reference}', $vars);
                $content = '<h2 style="margin-top:0; color:#46b450;">' . esc_html__('Good News! Your Booking is Approved', 'simple-booking-calendar') . '</h2>';
                $content .= '<p>' . sprintf(esc_html__('Dear %s,', 'simple-booking-calendar'), esc_html($booking->guest_name)) . '</p>';
                $content .= '<p>' . esc_html__('We are happy to inform you that your reservation has been approved. We look forward to welcoming you!', 'simple-booking-calendar') . '</p>';
                $accent = '#46b450';
                break;
            case 'rejected':
                $subject = $this->replace_vars($settings['subject_guest_rejected'] ?? 'Your Booking Request Update - {reference}', $vars);
                $content = '<h2 style="margin-top:0; color:#dc3232;">' . esc_html__('Booking Request Update', 'simple-booking-calendar') . '</h2>';
                $content .= '<p>' . sprintf(esc_html__('Dear %s,', 'simple-booking-calendar'), esc_html($booking->guest_name)) . '</p>';
                $content .= '<p>' . esc_html__('We regret to inform you that we are unable to accommodate your reservation request for the selected dates. Please try different dates or contact us for alternative options.', 'simple-booking-calendar') . '</p>';
                $accent = '#dc3232';
                break;
            case 'cancelled':
                $subject = $this->replace_vars($settings['subject_guest_cancelled'] ?? 'Your Booking is Cancelled - {reference}', $vars);
                $content = '<h2 style="margin-top:0; color:#dc3232;">' . esc_html__('Booking Cancelled', 'simple-booking-calendar') . '</h2>';
                $content .= '<p>' . sprintf(esc_html__('Dear %s,', 'simple-booking-calendar'), esc_html($booking->guest_name)) . '</p>';
                $content .= '<p>' . esc_html__('This email confirms that your reservation has been cancelled.', 'simple-booking-calendar') . '</p>';
                $accent = '#dc3232';
                break;
            default:
                return;
        }

        $content .= '<h3 style="color:' . esc_attr($accent) . '; border-bottom: 2px solid ' . esc_attr($accent) . '; padding-bottom: 8px;">' . esc_html__('Booking Details', 'simple-booking-calendar') . '</h3>';
        $content .= '<table style="width:100%; border-collapse: collapse; margin: 20px 0;">';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; width:140px; font-weight:600;">' . esc_html__('Reference:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($booking->booking_reference) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Check-in:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($this->format_date($booking->check_in)) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Check-out:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html($this->format_date($booking->check_out)) . '</td></tr>';
        $content .= '<tr><td style="padding:8px 12px; border-bottom:1px solid #eee; font-weight:600;">' . esc_html__('Status:', 'simple-booking-calendar') . '</td><td style="padding:8px 12px; border-bottom:1px solid #eee;">' . esc_html(ucfirst($status)) . '</td></tr>';
        $content .= '</table>';

        $body = $this->get_email_wrapper($content);

        return $this->send($to, $subject, $body);
    }
}
