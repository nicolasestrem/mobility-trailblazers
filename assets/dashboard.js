jQuery(document).ready(function($) {
    'use strict';

    // Initialize dashboard components
    function initDashboard() {
        initProgressBar();
        initCandidateCards();
        initEvaluationForm();
        initStatistics();
    }

    // Animate progress bar on page load
    function initProgressBar() {
        const progressFill = $('.mt-progress-fill');
        if (progressFill.length) {
            const targetWidth = progressFill.data('width') || progressFill.css('width');
            progressFill.css('width', '0');
            setTimeout(() => {
                progressFill.css('width', targetWidth);
            }, 100);
        }
    }

    // Initialize candidate cards with hover effects and status indicators
    function initCandidateCards() {
        const cards = $('.mt-candidate-card');
        
        cards.each(function() {
            const card = $(this);
            const statusBadge = card.find('.status-badge');
            
            // Add hover effect
            card.hover(
                function() {
                    $(this).addClass('hover');
                },
                function() {
                    $(this).removeClass('hover');
                }
            );

            // Add click handler for evaluation button
            card.find('.button-primary').on('click', function(e) {
                if (!card.hasClass('evaluated')) {
                    // Add loading state
                    $(this).addClass('loading');
                }
            });
        });
    }

    // Initialize evaluation form enhancements
    function initEvaluationForm() {
        const form = $('.mt-evaluation-form form');
        if (!form.length) return;

        // Add score selection enhancement
        $('.mt-score-option').each(function() {
            const option = $(this);
            const input = option.find('input[type="radio"]');
            const span = option.find('span');

            // Add hover effect
            option.hover(
                function() {
                    if (!input.is(':checked')) {
                        span.addClass('hover');
                    }
                },
                function() {
                    span.removeClass('hover');
                }
            );

            // Add click effect
            input.on('change', function() {
                $('.mt-score-option span').removeClass('selected');
                if (this.checked) {
                    span.addClass('selected');
                }
            });
        });

        // Form submission handling
        form.on('submit', function(e) {
            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).addClass('loading');
        });
    }

    // Initialize statistics with animations
    function initStatistics() {
        const statNumbers = $('.mt-stat-number');
        
        statNumbers.each(function() {
            const element = $(this);
            const value = parseInt(element.text());
            
            // Animate number counting
            if (!isNaN(value)) {
                element.prop('Counter', 0).animate({
                    Counter: value
                }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function(now) {
                        element.text(Math.ceil(now));
                    }
                });
            }
        });
    }

    // Initialize AJAX functionality for candidate details
    function initAjaxCalls() {
        // Handle candidate details loading
        $('.mt-candidate-card .button-secondary').on('click', function(e) {
            e.preventDefault();
            const candidateId = $(this).data('candidate-id');
            
            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_candidate_details',
                    candidate_id: candidateId,
                    nonce: mt_ajax.nonce
                },
                beforeSend: function() {
                    // Show loading state
                },
                success: function(response) {
                    if (response.success) {
                        // Handle successful response
                        showCandidateDetails(response.data);
                    } else {
                        // Handle error
                        showError(response.data.message);
                    }
                },
                error: function() {
                    showError('Failed to load candidate details');
                }
            });
        });
    }

    // Utility function to show candidate details
    function showCandidateDetails(data) {
        // Implementation for showing candidate details modal/popup
    }

    // Utility function to show error messages
    function showError(message) {
        // Implementation for showing error messages
    }

    // Initialize all dashboard components
    initDashboard();
    initAjaxCalls();
}); 