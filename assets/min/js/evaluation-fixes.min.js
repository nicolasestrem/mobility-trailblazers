/**
 * Critical JavaScript Fixes for Evaluation Form
 * Created: 2025-08-17
 * Purpose: Fix broken evaluation form functionality
 */
jQuery(document).ready(function($) {
    'use strict';
    // ========================================
    // FIX 1: RESTORE SLIDER FUNCTIONALITY
    // ========================================
    function initializeSliders() {
        // Hide button groups if they exist
        $('.mt-button-group').each(function() {
            var $buttonGroup = $(this);
            var criterion = $buttonGroup.data('criterion');
            var currentValue = $buttonGroup.find('input[type="hidden"]').val() || 5;
            // Create slider HTML
            var sliderHTML = `
                <div class="mt-score-slider-container">
                    <input type="range" 
                           name="${criterion}_score" 
                           class="mt-score-slider" 
                           min="0" 
                           max="10" 
                           step="0.5"
                           value="${currentValue}"
                           data-criterion="${criterion}">
                    <div class="mt-slider-value">${currentValue}</div>
                </div>
            `;
            // Replace button group with slider
            $buttonGroup.replaceWith(sliderHTML);
        });
        // Initialize slider events
        $('.mt-score-slider').on('input change', function() {
            var $slider = $(this);
            var value = $slider.val();
            var $display = $slider.siblings('.mt-slider-value');
            // Update display
            $display.text(value);
            // Update score display if exists
            var $card = $slider.closest('.mt-criterion-card');
            $card.find('.mt-score-value').text(value);
            // Update total score
            updateTotalScore();
        });
    }
    // ========================================
    // FIX 2: CALCULATE TOTAL SCORE
    // ========================================
    function updateTotalScore() {
        var total = 0;
        var count = 0;
        $('.mt-score-slider').each(function() {
            var value = parseFloat($(this).val());
            if (!isNaN(value)) {
                total += value;
                count++;
            }
        });
        var average = count > 0 ? (total / count).toFixed(1) : 0;
        $('#mt-total-score').text(average);
        // Update visual indicator
        updateScoreIndicator(average);
    }
    // ========================================
    // FIX 3: VISUAL SCORE INDICATOR
    // ========================================
    function updateScoreIndicator(score) {
        var $indicator = $('.mt-total-score-display');
        // Remove existing classes
        $indicator.removeClass('score-low score-medium score-high');
        // Add appropriate class
        if (score < 4) {
            $indicator.addClass('score-low');
        } else if (score < 7) {
            $indicator.addClass('score-medium');
        } else {
            $indicator.addClass('score-high');
        }
    }
    // ========================================
    // FIX 4: ENSURE BIOGRAPHY IS VISIBLE
    // ========================================
    function fixBiographyDisplay() {
        var $bioContent = $('.mt-bio-content');
        if ($bioContent.length && $bioContent.text().trim() === '') {
            // Try to fetch from hidden fields or data attributes
            var bioText = $bioContent.data('biography') || 
                         $('#candidate-biography').val() ||
                         $('.mt-candidate-bio-hidden').text();
            if (bioText && bioText.trim() !== '') {
                $bioContent.html('<p>' + bioText + '</p>');
            } else {
                $bioContent.html('<p class="no-content">No biography available for this candidate.</p>');
            }
        }
    }
    // ========================================
    // FIX 5: FORM SUBMISSION HANDLING
    // ========================================
    var isSubmitting = false; // Double-submission protection
    
    function fixFormSubmission() {
        $('#mt-evaluation-form').on('submit', function(e) {
            e.preventDefault();
            
            // Prevent double submission
            if (isSubmitting) {
                console.log('Form submission already in progress');
                return false;
            }
            
            isSubmitting = true;
            var $form = $(this);
            var formData = new FormData(this);
            // Add action
            formData.append('action', 'mt_save_evaluation');
            // Ensure all scores are included
            $('.mt-score-slider').each(function() {
                var name = $(this).attr('name');
                var value = $(this).val();
                formData.set(name, value);
            });
            // Show loading state
            var $submitBtn = $form.find('button[type="submit"]');
            var originalText = $submitBtn.html();
            var submittingText = window.getI18nText ? window.getI18nText('submitting', 'Submitting...') 
                                : (mt_frontend && mt_frontend.i18n && mt_frontend.i18n.submitting) 
                                ? mt_frontend.i18n.submitting 
                                : 'Submitting...';
            $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + submittingText);
            // Submit via AJAX
            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        var successMessage = response.data.message 
                                           || (window.getI18nText ? window.getI18nText('evaluation_submitted', 'Thank you for submitting your evaluation!') 
                                           : (mt_frontend && mt_frontend.i18n && mt_frontend.i18n.evaluation_submitted_full) 
                                           ? mt_frontend.i18n.evaluation_submitted_full 
                                           : (mt_frontend && mt_frontend.i18n && mt_frontend.i18n.evaluation_submitted) 
                                           ? mt_frontend.i18n.evaluation_submitted
                                           : 'Thank you for submitting your evaluation!');
                        showNotification('success', successMessage);
                        
                        // Update status badge
                        updateStatusBadge('completed');
                        // Redirect after delay
                        setTimeout(function() {
                            window.location.href = response.data.redirect || window.location.href.split('?')[0];
                        }, 2000);
                    } else {
                        showNotification('error', response.data || (window.getI18nText ? window.getI18nText('error', 'An error occurred. Please try again.') : (mt_frontend && mt_frontend.i18n && mt_frontend.i18n.error_try_again ? mt_frontend.i18n.error_try_again : 'An error occurred. Please try again.')));
                        $submitBtn.prop('disabled', false).html(originalText);
                        isSubmitting = false; // Reset submission flag
                    }
                },
                error: function() {
                    showNotification('error', window.getI18nText ? window.getI18nText('network_error', 'Network error. Please check your connection and try again.') : (mt_frontend && mt_frontend.i18n && mt_frontend.i18n.network_error ? mt_frontend.i18n.network_error : 'Network error. Please check your connection and try again.'));
                    $submitBtn.prop('disabled', false).html(originalText);
                    isSubmitting = false; // Reset submission flag
                }
            });
        });
    }
    // ========================================
    // FIX 6: NOTIFICATION SYSTEM
    // ========================================
    function showNotification(type, message) {
        // Remove existing notifications
        $('.mt-notification').remove();
        // Create notification
        var $notification = $('<div class="mt-notification mt-notification-' + type + '">' +
                              '<span class="dashicons dashicons-' + 
                              (type === 'success' ? 'yes' : 'warning') + '"></span> ' +
                              message + '</div>');
        // Add to page
        $('body').append($notification);
        // Animate in
        setTimeout(function() {
            $notification.addClass('show');
        }, 100);
        // Auto remove after 5 seconds
        setTimeout(function() {
            $notification.removeClass('show');
            setTimeout(function() {
                $notification.remove();
            }, 300);
        }, 5000);
    }
    // ========================================
    // FIX 7: STATUS BADGE UPDATE
    // ========================================
    function updateStatusBadge(status) {
        var $badge = $('.mt-status-badge');
        if ($badge.length === 0) {
            // Create badge if doesn't exist
            $badge = $('<span class="mt-status-badge"></span>');
            $('.mt-evaluation-title').append($badge);
        }
        // Update badge
        $badge.removeClass('mt-status-draft mt-status-completed')
              .addClass('mt-status-' + status);
        if (status === 'draft') {
            $badge.text('Draft Saved');
        } else if (status === 'completed') {
            var submittedText = (mt_frontend && mt_frontend.i18n && mt_frontend.i18n.evaluation_submitted_status) 
                              ? mt_frontend.i18n.evaluation_submitted_status 
                              : 'Evaluation Submitted';
            $badge.text(submittedText);
        }
    }
    // ========================================
    // FIX 8: CHARACTER COUNT
    // ========================================
    function initCharacterCount() {
        var $textarea = $('#mt-comments');
        var $counter = $('#mt-char-current');
        var maxLength = 1000;
        function updateCount() {
            var length = $textarea.val().length;
            $counter.text(length);
            if (length > maxLength) {
                $counter.parent().addClass('over-limit');
            } else {
                $counter.parent().removeClass('over-limit');
            }
        }
        $textarea.on('input keyup', updateCount);
        updateCount(); // Initial count
    }
    // ========================================
    // FIX 9: ADD MISSING STYLES
    // ========================================
    function addMissingStyles() {
        if ($('#mt-critical-fixes-styles').length === 0) {
            var styles = `
                <style id="mt-critical-fixes-styles">
                    /* Slider Styles */
                    .mt-score-slider-container {
                        position: relative;
                        margin: 20px 0;
                    }
                    .mt-score-slider {
                        width: 100%;
                        height: 6px;
                        -webkit-appearance: none;
                        appearance: none;
                        background: #ddd;
                        border-radius: 3px;
                        outline: none;
                    }
                    .mt-score-slider::-webkit-slider-thumb {
                        -webkit-appearance: none;
                        appearance: none;
                        width: 20px;
                        height: 20px;
                        background: #4CAF50;
                        border-radius: 50%;
                        cursor: pointer;
                    }
                    .mt-score-slider::-moz-range-thumb {
                        width: 20px;
                        height: 20px;
                        background: #4CAF50;
                        border-radius: 50%;
                        cursor: pointer;
                        border: none;
                    }
                    .mt-slider-value {
                        position: absolute;
                        top: -25px;
                        left: 50%;
                        transform: translateX(-50%);
                        background: #333;
                        color: white;
                        padding: 2px 8px;
                        border-radius: 3px;
                        font-size: 12px;
                    }
                    /* Score Display */
                    .mt-total-score-display {
                        float: right;
                        font-size: 1.2em;
                        font-weight: bold;
                        padding: 5px 10px;
                        border-radius: 5px;
                        background: #f0f0f0;
                    }
                    .mt-total-score-display.score-low {
                        background: #ffebee;
                        color: #c62828;
                    }
                    .mt-total-score-display.score-medium {
                        background: #fff3e0;
                        color: #ef6c00;
                    }
                    .mt-total-score-display.score-high {
                        background: #e8f5e9;
                        color: #2e7d32;
                    }
                    /* Notification Styles */
                    .mt-notification {
                        position: fixed;
                        top: 50px;
                        right: -300px;
                        background: white;
                        padding: 15px 20px;
                        border-radius: 4px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        z-index: 9999;
                        transition: right 0.3s ease;
                        max-width: 300px;
                    }
                    .mt-notification.show {
                        right: 20px;
                    }
                    .mt-notification-success {
                        border-left: 4px solid #4CAF50;
                        color: #2e7d32;
                    }
                    .mt-notification-error {
                        border-left: 4px solid #f44336;
                        color: #c62828;
                    }
                    /* Character Count */
                    .mt-char-count {
                        text-align: right;
                        font-size: 0.9em;
                        color: #666;
                        margin-top: 5px;
                    }
                    .mt-char-count.over-limit {
                        color: #f44336;
                        font-weight: bold;
                    }
                    /* Loading Spinner */
                    .dashicons.spin {
                        animation: spin 1s linear infinite;
                    }
                    @keyframes spin {
                        from { transform: rotate(0deg); }
                        to { transform: rotate(360deg); }
                    }
                    /* Fix Bio Content */
                    .mt-bio-content:empty::before {
                        content: "Biography information not available.";
                        color: #999;
                        font-style: italic;
                    }
                    .no-content {
                        color: #999;
                        font-style: italic;
                    }
                </style>
            `;
            $('head').append(styles);
        }
    }
    // ========================================
    // INITIALIZE ALL FIXES
    // ========================================
    function initializeFixes() {
        // Initializing evaluation form fixes
        // Add missing styles first
        addMissingStyles();
        // Initialize components
        initializeSliders();
        updateTotalScore();
        fixBiographyDisplay();
        fixFormSubmission();
        initCharacterCount();
        // Evaluation form fixes applied
    }
    // Run fixes when page is ready
    if ($('.mt-evaluation-form, #mt-evaluation-form').length > 0) {
        initializeFixes();
    }
    // Also run on AJAX complete in case form is loaded dynamically
    $(document).ajaxComplete(function(event, xhr, settings) {
        if (settings.url && settings.url.includes('evaluate')) {
            setTimeout(initializeFixes, 100);
        }
    });
});
