/* Simple Booking Calendar - Admin JS */
jQuery(document).ready(function($) {
    'use strict';

    const ajaxUrl = sbc_admin.ajax_url;
    const nonce = sbc_admin.nonce;
    const i18n = sbc_admin.i18n;

    // === Booking actions ===

    // View booking details
    $(document).on('click', '.sbc-btn-view', function() {
        const id = $(this).data('id');
        const $modal = $('#sbc-booking-modal');
        const $body = $('#sbc-modal-body');

        $body.html('<p>' + i18n.loading + '</p>');
        $modal.show();

        $.post(ajaxUrl, {
            action: 'sbc_get_booking_details',
            nonce: nonce,
            booking_id: id
        }, function(res) {
            if (res.success) {
                $body.html(res.data.html);
            } else {
                $body.html('<p>' + (res.data.message || i18n.error) + '</p>');
            }
        }).fail(function() {
            $body.html('<p>' + i18n.error + '</p>');
        });
    });

    // Close modal
    $(document).on('click', '.sbc-modal-close, .sbc-modal-cancel, .sbc-modal-overlay', function() {
        $(this).closest('.sbc-modal').hide();
    });

    // Approve
    $(document).on('click', '.sbc-btn-approve', function() {
        if (!confirm(i18n.confirm_approve)) return;
        updateStatus($(this).data('id'), 'approved');
    });

    // Reject
    $(document).on('click', '.sbc-btn-reject', function() {
        if (!confirm(i18n.confirm_reject)) return;
        updateStatus($(this).data('id'), 'rejected');
    });

    // Cancel
    $(document).on('click', '.sbc-btn-cancel', function() {
        if (!confirm(i18n.confirm_cancel)) return;
        updateStatus($(this).data('id'), 'cancelled');
    });

    // Delete
    $(document).on('click', '.sbc-btn-delete', function() {
        if (!confirm(i18n.confirm_delete)) return;
        const $row = $(this).closest('tr');
        const id = $(this).data('id');

        $.post(ajaxUrl, {
            action: 'sbc_delete_booking',
            nonce: nonce,
            booking_id: id
        }, function(res) {
            if (res.success) {
                $row.fadeOut(300, function() { $(this).remove(); });
            } else {
                alert(res.data.message || i18n.error);
            }
        }).fail(function() {
            alert(i18n.error);
        });
    });

    function updateStatus(id, status) {
        $.post(ajaxUrl, {
            action: 'sbc_update_booking_status',
            nonce: nonce,
            booking_id: id,
            status: status
        }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data.message || i18n.error);
            }
        }).fail(function() {
            alert(i18n.error);
        });
    }

    // === Calendar navigation ===

    $(document).on('click', '.sbc-nav-btn', function(e) {
        e.preventDefault();
        const href = $(this).attr('href');
        // Use AJAX to avoid full page reload when possible.
        if ($('#sbc-admin-calendar-wrap').length) {
            const url = new URL(href, window.location.origin);
            const year = parseInt(url.searchParams.get('year'));
            const month = parseInt(url.searchParams.get('month'));
            loadAdminCalendar(year, month);
            // Update URL without reload.
            window.history.pushState({year: year, month: month}, '', href);
        } else {
            window.location.href = href;
        }
    });

    function loadAdminCalendar(year, month) {
        const $wrap = $('#sbc-admin-calendar-wrap');
        $wrap.addClass('sbc-loading');

        $.post(ajaxUrl, {
            action: 'sbc_admin_get_calendar',
            nonce: nonce,
            year: year,
            month: month
        }, function(res) {
            if (res.success) {
                $wrap.html(res.data.html).data('year', res.data.year).data('month', res.data.month);
                const title = new Date(res.data.year, res.data.month - 1, 1).toLocaleDateString(sbc_locale(), { year: 'numeric', month: 'long' });
                $('#sbc-calendar-title').text(title);
            }
        }).always(function() {
            $wrap.removeClass('sbc-loading');
        });
    }

    function sbc_locale() {
        return (typeof document.documentElement.lang !== 'undefined' && document.documentElement.lang) ? document.documentElement.lang.replace('_', '-') : 'en-US';
    }

    // === Block dates modal ===

    $(document).on('click', '#sbc-block-trigger', function() {
        $('#sbc-block-modal').show();
    });

    $('#sbc-block-form').on('submit', function(e) {
        e.preventDefault();

        const start = $('#sbc-block-start').val();
        const end = $('#sbc-block-end').val();
        const reason = $('#sbc-block-reason').val();

        if (!start || !end) {
            alert('Please select start and end dates.');
            return;
        }

        if (end < start) {
            alert('End date must be on or after start date.');
            return;
        }

        if (!confirm(i18n.confirm_block)) return;

        $.post(ajaxUrl, {
            action: 'sbc_save_blocked_dates',
            nonce: nonce,
            start_date: start,
            end_date: end,
            reason: reason
        }, function(res) {
            if (res.success) {
                if (res.data.overlaps > 0) {
                    alert(i18n.warning_overlap + '\n\n' + res.data.message);
                } else {
                    alert(res.data.message);
                }
                location.reload();
            } else {
                alert(res.data.message || i18n.error);
            }
        }).fail(function() {
            alert(i18n.error);
        });
    });

    // Unblock date
    $(document).on('click', '.sbc-btn-unblock', function() {
        if (!confirm(i18n.confirm_unblock)) return;
        const id = $(this).data('id');
        const $row = $(this).closest('tr');

        $.post(ajaxUrl, {
            action: 'sbc_delete_blocked_date',
            nonce: nonce,
            block_id: id
        }, function(res) {
            if (res.success) {
                $row.fadeOut(300, function() { $(this).remove(); });
                // Reload calendar if visible
                const $cal = $('#sbc-admin-calendar-wrap');
                if ($cal.length) {
                    loadAdminCalendar(parseInt($cal.data('year')), parseInt($cal.data('month')));
                }
            } else {
                alert(res.data.message || i18n.error);
            }
        }).fail(function() {
            alert(i18n.error);
        });
    });

    // Initialize color pickers
    if (typeof $.fn.wpColorPicker === 'function') {
        $('.sbc-color-field').wpColorPicker();
    }
});
