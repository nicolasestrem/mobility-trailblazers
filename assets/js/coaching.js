/**
 * Coaching Dashboard JavaScript
 * 
 * @package MobilityTrailblazers
 * @since 2.2.29
 */
(function($) {
    'use strict';
    const MTCoaching = {
        /**
         * Initialize coaching functionality
         */
        init: function() {
            this.bindEvents();
        },
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Refresh statistics
            $('#refresh-stats').on('click', this.refreshStats.bind(this));
        },
        /**
         * Refresh statistics
         */
        refreshStats: function(e) {
            e.preventDefault();
            const $button = $(e.currentTarget);
            this.setButtonLoading($button, true);
            // Reload the page to refresh stats
            location.reload();
        },
        /**
         * Set button loading state
         */
        setButtonLoading: function($button, loading) {
            if (loading) {
                $button.addClass('loading').prop('disabled', true);
                $button.data('original-text', $button.html());
                $button.html('<span class="spinner is-active"></span> Loading...');
            } else {
                $button.removeClass('loading').prop('disabled', false);
                if ($button.data('original-text')) {
                    $button.html($button.data('original-text'));
                }
            }
        },
        /**
         * Show notice message
         */
        showNotice: function(message, type) {
            // Remove existing notices
            $('.mt-coaching-notice').remove();
            const notice = $('<div/>', {
                class: 'notice notice-' + type + ' is-dismissible mt-coaching-notice',
                html: '<p>' + message + '</p>'
            });
            // Add dismiss button
            const dismissBtn = $('<button/>', {
                type: 'button',
                class: 'notice-dismiss',
                html: '<span class="screen-reader-text">Dismiss this notice.</span>'
            });
            dismissBtn.on('click', function() {
                notice.fadeOut(200, function() {
                    $(this).remove();
                });
            });
            notice.append(dismissBtn);
            // Insert after heading
            $('.wrap h1').first().after(notice);
            // Auto-dismiss success notices after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    notice.find('.notice-dismiss').click();
                }, 5000);
            }
        }
    };
    // Initialize on document ready
    $(document).ready(function() {
        MTCoaching.init();
    });
})(jQuery);
