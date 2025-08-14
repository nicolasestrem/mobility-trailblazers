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
            // Send reminder to incomplete
            $('#send-reminder-incomplete').on('click', this.sendReminderIncomplete.bind(this));
            
            // Send reminder about drafts
            $('#send-reminder-drafts').on('click', this.sendReminderDrafts.bind(this));
            
            // Send individual reminder
            $(document).on('click', '.send-reminder-single', this.sendReminderSingle.bind(this));
            
            // Refresh statistics
            $('#refresh-stats').on('click', this.refreshStats.bind(this));
        },
        
        /**
         * Send reminders to jury members with incomplete evaluations
         */
        sendReminderIncomplete: function(e) {
            e.preventDefault();
            
            if (!confirm(mt_coaching.i18n.confirm_reminder)) {
                return;
            }
            
            const $button = $(e.currentTarget);
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: mt_coaching.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_send_coaching_reminder',
                    nonce: mt_coaching.nonce,
                    type: 'incomplete'
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                    } else {
                        this.showNotice(response.data.message || mt_coaching.i18n.error, 'error');
                    }
                },
                error: () => {
                    this.showNotice(mt_coaching.i18n.error, 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Send reminders about draft evaluations
         */
        sendReminderDrafts: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: mt_coaching.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_send_coaching_reminder',
                    nonce: mt_coaching.nonce,
                    type: 'drafts'
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(response.data.message, 'success');
                    } else {
                        this.showNotice(response.data.message || mt_coaching.i18n.error, 'error');
                    }
                },
                error: () => {
                    this.showNotice(mt_coaching.i18n.error, 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
        },
        
        /**
         * Send reminder to individual jury member
         */
        sendReminderSingle: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const juryName = $button.data('jury-name');
            
            if (!confirm(`Send reminder to ${juryName}?`)) {
                return;
            }
            
            this.setButtonLoading($button, true);
            
            $.ajax({
                url: mt_coaching.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_send_coaching_reminder',
                    nonce: mt_coaching.nonce,
                    type: 'single',
                    jury_id: $button.data('jury-id')
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotice(`Reminder sent to ${juryName}`, 'success');
                        $button.text('Reminder Sent').prop('disabled', true);
                    } else {
                        this.showNotice(response.data.message || mt_coaching.i18n.error, 'error');
                    }
                },
                error: () => {
                    this.showNotice(mt_coaching.i18n.error, 'error');
                },
                complete: () => {
                    this.setButtonLoading($button, false);
                }
            });
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
                $button.html('<span class="spinner is-active"></span> ' + mt_coaching.i18n.sending);
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