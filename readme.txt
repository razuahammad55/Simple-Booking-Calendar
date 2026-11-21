=== Simple Booking Calendar ===
Contributors: simplebooking
Tags: booking, calendar, reservation, availability, appointments
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A lightweight, secure, and user-friendly booking calendar plugin for reservation requests.

== Description ==

Simple Booking Calendar is a complete reservation-request system that allows website visitors to check availability, select dates, and submit booking requests. Administrators can review, approve, reject, or cancel bookings, manually block unavailable dates, and customize calendar appearance and email notifications.

**This is a request-based system — no online payments required.** Bookings are created with "Pending" status until an administrator approves them.

**Features include:**

* Responsive monthly calendar with month navigation
* Date range selection (check-in / check-out)
* Clear visual distinction between available, pending, approved, and blocked dates
* Back-to-back booking support (check-out date = next guest's check-in date)
* Guest booking form with name, email, phone, guest count, and notes
* Server-side validation prevents double-bookings
* Unique booking reference numbers (format: SBC-YYYYMMDD-NNNN)
* Admin dashboard with complete booking management
* Admin calendar view for managing blocked dates
* Email notifications to admin and guests (HTML templates)
* Status change notifications
* Customizable calendar colors and settings
* Rate limiting and honeypot anti-spam protection
* Works with standard WordPress pages and Elementor's Shortcode widget
* Lightweight: no external libraries, assets load only when needed

== Installation ==

1. Upload the `simple-booking-calendar` folder to the `/wp-content/plugins/` directory, or upload the ZIP file through the WordPress Plugins screen.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Booking Calendar → Settings** to configure your options.
4. Place the `[simple_booking_calendar]` shortcode on any page or post.

== Shortcode ==

Place the booking calendar on any page or post using:

`[simple_booking_calendar]`

This shortcode is fully compatible with Elementor's Shortcode widget.

== Usage ==

1. After activation, go to **Booking Calendar** in your WordPress dashboard.
2. Configure your settings under **Settings** — set admin email, min/max advance booking periods, stay durations, calendar colors, and email options.
3. Use the **Calendar** page to manually block unavailable dates (e.g., holidays, maintenance).
4. Visit the page containing the shortcode to see the booking calendar.
5. Guests select check-in and check-out dates, fill in their details, and submit. You will receive an email notification; pending bookings must be approved before they become confirmed.
6. Use **All Bookings** to view, approve, reject, cancel, or delete reservations.

== Status Descriptions ==

* **Pending** — New booking request awaiting administrator review. Pending bookings also hold dates to prevent double-bookings while you review.
* **Approved** — Booking confirmed. The dates are fully blocked for other guests.
* **Rejected** — Request declined. Dates are released back to available.
* **Cancelled** — Previously approved booking has been cancelled. Dates are released back to available.

== Frequently Asked Questions ==

= Does this plugin support online payments? =

No. Simple Booking Calendar is a reservation request system only. There are no payment gateways, credit card forms, or paid subscriptions. Administrators handle payment arrangements separately.

= Can guests book single-day stays? =

By default the minimum stay is 1 night (which requires selecting two dates: check-in and check-out the next day). Set minimum stay to 0 nights in a future update if you need same-day bookings.

= Can I block dates manually? =

Yes. Go to **Booking Calendar → Calendar**, click **Block Dates**, and select the date range with an optional note.

= Will pending bookings hold dates? =

Yes. When a guest submits a request, the dates are held as pending to prevent overlapping bookings while the administrator reviews. This prevents double-bookings during the review window. You can configure the pending expiration period in settings.

= Are email notifications customizable? =

Yes. You can customize sender name/address, email subjects, and enable/disable each notification type from the Settings page.

= Is the plugin compatible with Elementor? =

Yes. The `[simple_booking_calendar]` shortcode works in Elementor's Shortcode widget.

== Screenshots ==

1. Frontend booking calendar with date selection
2. Booking form after dates are selected
3. Admin bookings management table
4. Admin calendar management view
5. Settings page
6. Blocked dates management

== Changelog ==

= 1.0.0 =
* Initial release.
