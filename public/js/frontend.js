/* Simple Booking Calendar - Frontend JS */
jQuery(document).ready(function($) {
    'use strict';

    const $wrapper = $('#sbc-booking-calendar');
    if (!$wrapper.length) return;

    const ajaxUrl = sbc_frontend.ajax_url;
    const nonce = sbc_frontend.nonce;
    const i18n = sbc_frontend.i18n;
    const settings = sbc_frontend.settings;

    // State
    let selectionState = {
        checkIn: null,
        checkOut: null,
        selectingCheckout: false
    };

    // === Prompt management ===

    function setPromptStep(step) {
        const $steps = $wrapper.find('.sbc-prompt-step');
        $steps.each(function() {
            const $el = $(this);
            const s = parseInt($el.data('step'));
            $el.removeClass('sbc-prompt-active sbc-prompt-done');
            if (s < step) {
                $el.addClass('sbc-prompt-done');
            } else if (s === step) {
                $el.addClass('sbc-prompt-active');
            }
        });
    }

    // Start at step 1
    setPromptStep(1);

    // === Calendar navigation ===

    $(document).on('click', '.sbc-prev', function(e) {
        e.preventDefault();
        navigateMonth(-1);
    });

    $(document).on('click', '.sbc-next', function(e) {
        e.preventDefault();
        navigateMonth(1);
    });

    function navigateMonth(direction) {
        const $container = $('#sbc-calendar-container');
        let year = parseInt($container.data('year'));
        let month = parseInt($container.data('month'));

        month += direction;
        if (month < 1) { month = 12; year--; }
        if (month > 12) { month = 1; year++; }

        loadCalendar(year, month);
    }

    function loadCalendar(year, month) {
        const $container = $('#sbc-calendar-container');
        const $loading = $('#sbc-calendar-loading');

        $container.hide();
        $loading.show();

        $.post(ajaxUrl, {
            action: 'sbc_get_calendar',
            nonce: nonce,
            year: year,
            month: month
        }, function(res) {
            if (res.success) {
                $container.html(res.data.html)
                    .data('year', res.data.year)
                    .data('month', res.data.month);
                updateTitle(res.data.year, res.data.month);
                reapplySelection();
            } else {
                showMessage(i18n.error, 'error');
            }
        }).fail(function() {
            showMessage(i18n.error, 'error');
        }).always(function() {
            $loading.hide();
            $container.show();
        });
    }

    function updateTitle(year, month) {
        const date = new Date(year, month - 1, 1);
        const title = date.toLocaleDateString(document.documentElement.lang || 'en-US', {
            year: 'numeric',
            month: 'long'
        });
        $('#sbc-cal-title').text(title);
    }

    // === Date selection ===

    $(document).on('click', '.sbc-selectable', function(e) {
        e.preventDefault();
        const dateStr = $(this).data('date');
        if (!dateStr) return;

        hideMessage();

        if (!selectionState.selectingCheckout || !selectionState.checkIn) {
            // Step 1: Select check-in date
            selectionState.checkIn = dateStr;
            selectionState.checkOut = null;
            selectionState.selectingCheckout = true;
            clearSelectionStyles();
            clearHoverRange();
            $(this).addClass('sbc-day-selected sbc-checkin');
            setPromptStep(2);
            updateSelectionInfo();
            $('#sbc-selection-info').slideDown(200);
            // Hide form if previously shown
            $('#sbc-form-section').slideUp(200);
        } else {
            // Step 2: Select check-out date
            const checkInDate = new Date(selectionState.checkIn + 'T00:00:00');
            const clickedDate = new Date(dateStr + 'T00:00:00');

            if (clickedDate <= checkInDate) {
                // User clicked on or before check-in: restart selection
                selectionState.checkIn = dateStr;
                selectionState.checkOut = null;
                selectionState.selectingCheckout = true;
                clearSelectionStyles();
                clearHoverRange();
                $(this).addClass('sbc-day-selected sbc-checkin');
                setPromptStep(2);
                updateSelectionInfo();
                $('#sbc-form-section').slideUp(200);
                return;
            }

            selectionState.checkOut = dateStr;
            selectionState.selectingCheckout = false;

            // Verify availability server-side
            checkAvailability(selectionState.checkIn, selectionState.checkOut);
        }
    });

    function clearSelectionStyles() {
        $wrapper.find('.sbc-cal-day').removeClass('sbc-day-selected sbc-day-in-range sbc-checkin sbc-checkout sbc-day-hover-range');
    }

    function reapplySelection() {
        if (!selectionState.checkIn) return;

        const $checkIn = $wrapper.find('.sbc-cal-day[data-date="' + selectionState.checkIn + '"]');
        if ($checkIn.length) {
            $checkIn.addClass('sbc-day-selected sbc-checkin');
        }

        if (selectionState.checkOut) {
            const $checkOut = $wrapper.find('.sbc-cal-day[data-date="' + selectionState.checkOut + '"]');
            if ($checkOut.length) {
                $checkOut.addClass('sbc-day-selected sbc-checkout');
            }
            paintRange();
        }
    }

    function paintRange() {
        if (!selectionState.checkIn || !selectionState.checkOut) return;

        const start = new Date(selectionState.checkIn + 'T00:00:00');
        const end = new Date(selectionState.checkOut + 'T00:00:00');
        const current = new Date(start);
        current.setDate(current.getDate() + 1);

        while (current < end) {
            const dateStr = formatDate(current);
            const $day = $wrapper.find('.sbc-cal-day[data-date="' + dateStr + '"]');
            if ($day.length) {
                $day.addClass('sbc-day-in-range');
            }
            current.setDate(current.getDate() + 1);
        }
    }

    function checkAvailability(checkIn, checkOut) {
        const $submitBtn = $('#sbc-submit-btn');
        $submitBtn.prop('disabled', true);

        // Show loading state on calendar area briefly
        const $loading = $('#sbc-calendar-loading');
        $('#sbc-calendar-container').css('opacity', '0.5');

        $.post(ajaxUrl, {
            action: 'sbc_check_availability',
            nonce: nonce,
            check_in: checkIn,
            check_out: checkOut
        }, function(res) {
            if (res.success) {
                // Dates available - show form
                clearSelectionStyles();
                $wrapper.find('.sbc-cal-day[data-date="' + checkIn + '"]').addClass('sbc-day-selected sbc-checkin');
                $wrapper.find('.sbc-cal-day[data-date="' + checkOut + '"]').addClass('sbc-day-selected sbc-checkout');
                paintRange();

                // Update form hidden inputs and display
                $('#sbc-check-in-input').val(checkIn);
                $('#sbc-check-out-input').val(checkOut);

                $('#sbc-dates-summary').html(
                    '<strong>' + i18n.check_in + ':</strong> ' + res.data.check_in + ' &nbsp;|&nbsp; ' +
                    '<strong>' + i18n.check_out + ':</strong> ' + res.data.check_out + ' &nbsp;|&nbsp; ' +
                    '<strong>' + res.data.nights + '</strong> ' + i18n.nights
                );

                // Mark both steps as done
                setPromptStep(3);

                updateSelectionInfo();
                $('#sbc-form-section').slideDown(250);
                hideMessage();

                // Scroll to form
                $('html, body').animate({
                    scrollTop: $('#sbc-form-section').offset().top - 40
                }, 350);
            } else {
                // Not available
                showMessage(res.data.message || i18n.dates_unavailable, 'error');
                // Reset checkout selection, keep checkin
                selectionState.checkOut = null;
                selectionState.selectingCheckout = true;
                clearSelectionStyles();
                $wrapper.find('.sbc-cal-day[data-date="' + checkIn + '"]').addClass('sbc-day-selected sbc-checkin');
                setPromptStep(2);
                updateSelectionInfo();
            }
        }).fail(function() {
            showMessage(i18n.error, 'error');
            selectionState.checkOut = null;
            selectionState.selectingCheckout = true;
            setPromptStep(2);
            updateSelectionInfo();
        }).always(function() {
            $submitBtn.prop('disabled', false);
            $('#sbc-calendar-container').css('opacity', '1');
        });
    }

    function updateSelectionInfo() {
        const $info = $('#sbc-selection-info');
        const $ci = $('#sbc-sel-checkin');
        const $co = $('#sbc-sel-checkout');
        const $nights = $('#sbc-sel-nights');

        if (selectionState.checkIn) {
            $ci.text(formatDateDisplay(selectionState.checkIn));
        } else {
            $ci.text('—');
        }

        if (selectionState.checkOut) {
            $co.text(formatDateDisplay(selectionState.checkOut));
            const n = Math.round((new Date(selectionState.checkOut) - new Date(selectionState.checkIn)) / 86400000);
            $nights.text(n);
        } else {
            $co.text('—');
            $nights.text('0');
        }
    }

    function formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + d;
    }

    function formatDateDisplay(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString(document.documentElement.lang || 'en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    // === Form submission ===

    $('#sbc-booking-form').on('submit', function(e) {
        e.preventDefault();

        if (!selectionState.checkIn || !selectionState.checkOut) {
            showMessage(i18n.invalid_range, 'error');
            return;
        }

        // Basic client validation
        const name = $('#sbc-guest-name').val().trim();
        const email = $('#sbc-guest-email').val().trim();
        const phone = $('#sbc-guest-phone').val().trim();

        if (!name || !email || !phone) {
            showMessage('Please fill in all required fields (marked with *).', 'error');
            return;
        }

        if (!isValidEmail(email)) {
            showMessage('Please enter a valid email address.', 'error');
            return;
        }

        const $submitBtn = $('#sbc-submit-btn');
        $submitBtn.prop('disabled', true).text(i18n.submitting);
        hideMessage();

        const formData = $(this).serialize();

        $.post(ajaxUrl, formData + '&action=sbc_submit_booking&nonce=' + nonce, function(res) {
            if (res.success) {
                showMessage(res.data.message, 'success');
                $('#sbc-booking-form')[0].reset();
                $('#sbc-form-section').slideUp(200);
                $('#sbc-selection-info').slideUp(200);
                selectionState = { checkIn: null, checkOut: null, selectingCheckout: false };
                clearSelectionStyles();
                setPromptStep(1);
                // Reload calendar to show booked dates.
                const $container = $('#sbc-calendar-container');
                loadCalendar(parseInt($container.data('year')), parseInt($container.data('month')));
            } else {
                showMessage(res.data.message || i18n.error, 'error');
            }
        }).fail(function() {
            showMessage(i18n.error, 'error');
        }).always(function() {
            $submitBtn.prop('disabled', false).text(i18n.submit);
        });
    });

    function showMessage(msg, type) {
        const $msg = $('#sbc-message');
        $msg.removeClass('sbc-success sbc-error')
            .addClass(type === 'success' ? 'sbc-success' : 'sbc-error')
            .html(msg);

        if (!$msg.is(':visible')) {
            $msg.slideDown(200);
        }

        if (type === 'error') {
            $('html, body').animate({ scrollTop: $msg.offset().top - 40 }, 300);
        }
    }

    function hideMessage() {
        $('#sbc-message').hide();
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    // Hover effect for range preview
    $(document).on('mouseenter', '.sbc-selectable', function() {
        if (!selectionState.selectingCheckout || !selectionState.checkIn) return;

        const dateStr = $(this).data('date');
        const start = new Date(selectionState.checkIn + 'T00:00:00');
        const hover = new Date(dateStr + 'T00:00:00');

        if (hover <= start) return;

        clearHoverRange();

        const current = new Date(start);
        current.setDate(current.getDate() + 1);

        while (current < hover) {
            const ds = formatDate(current);
            $wrapper.find('.sbc-cal-day[data-date="' + ds + '"]').addClass('sbc-day-hover-range');
            current.setDate(current.getDate() + 1);
        }
    });

    $(document).on('mouseleave', '.sbc-selectable', function() {
        clearHoverRange();
    });

    function clearHoverRange() {
        $wrapper.find('.sbc-day-hover-range').removeClass('sbc-day-hover-range');
    }
});
