/**
 * Mobility Trailblazers Frontend JavaScript
 * Version: 2.5.0
 */

(function($) {
    'use strict';

    // Main MT Frontend object
    window.MTFrontend = {
        
        // Initialize all frontend components
        init: function() {
            this.initVotingForm();
            this.initCandidateGrid();
            this.initJuryDashboard();
            this.initEvaluationForm();
            this.initAjaxHandlers();
            this.initUtilities();
        },

        // Initialize voting form functionality
        initVotingForm: function() {
            $('.mt-voting-form').on('submit', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const formData = $form.serialize();
                
                MTFrontend.submitVote(formData, $form);
            });

            // Score slider functionality
            $('.mt-score-slider').on('input', function() {
                const $slider = $(this);
                const value = $slider.val();
                const $criterion = $slider.closest('.mt-criterion');
                
                $criterion.find('.mt-criterion-score').text(value);
                MTFrontend.updateTotalScore();
            });
        },

        // Initialize candidate grid interactions
        initCandidateGrid: function() {
            // Filter functionality
            $('#mt-category-filter').on('change', function() {
                const category = $(this).val();
                MTFrontend.filterCandidates(category);
            });

            // Search functionality
            $('#mt-candidate-search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                MTFrontend.searchCandidates(searchTerm);
            });

            // Load more functionality
            $('#mt-load-more-candidates').on('click', function() {
                const $button = $(this);
                const page = $button.data('page') || 1;
                
                MTFrontend.loadMoreCandidates(page + 1, $button);
            });
        },

        // Initialize jury dashboard
        initJuryDashboard: function() {
            if (!$('.mt-jury-dashboard').length) return;

            // Auto-save draft functionality
            let autoSaveTimer;
            $('.mt-evaluation-form input, .mt-evaluation-form textarea').on('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(function() {
                    MTFrontend.saveDraft();
                }, 2000);
            });

            // Export evaluations
            $('#mt-export-evaluations').on('click', function(e) {
                e.preventDefault();
                MTFrontend.exportEvaluations();
            });

            // Load evaluation data if editing
            const candidateId = $('#mt-candidate-id').val();
            if (candidateId) {
                MTFrontend.loadEvaluation(candidateId);
            }
        },

        // Initialize evaluation form
        initEvaluationForm: function() {
            // Score button clicks
            $('.mt-score-button').on('click', function() {
                const $button = $(this);
                const $criterion = $button.closest('.mt-criterion-input');
                const score = $button.data('score');
                
                // Update UI
                $criterion.find('.mt-score-button').removeClass('selected');
                $button.addClass('selected');
                
                // Update hidden input
                $criterion.find('input[type="hidden"]').val(score);
                
                // Update total score
                MTFrontend.updateEvaluationScore();
            });

            // Submit evaluation
            $('#mt-submit-evaluation').on('click', function(e) {
                e.preventDefault();
                
                if (MTFrontend.validateEvaluation()) {
                    MTFrontend.submitEvaluation();
                }
            });

            // Save draft
            $('#mt-save-draft').on('click', function(e) {
                e.preventDefault();
                MTFrontend.saveDraft();
            });
        },

        // Initialize AJAX handlers
        initAjaxHandlers: function() {
            // Set up AJAX defaults
            $.ajaxSetup({
                beforeSend: function(xhr) {
                    // Add nonce to all AJAX requests
                    if (typeof mt_ajax !== 'undefined' && mt_ajax.nonce) {
                        xhr.setRequestHeader('X-WP-Nonce', mt_ajax.nonce);
                    }
                }
            });

            // Global AJAX error handler
            $(document).ajaxError(function(event, xhr, settings, error) {
                console.error('AJAX Error:', error);
                MTFrontend.showMessage('An error occurred. Please try again.', 'error');
            });
        },

        // Initialize utility functions
        initUtilities: function() {
            // Smooth scroll to anchors
            $('a[href^="#mt-"]').on('click', function(e) {
                e.preventDefault();
                const target = $($(this).attr('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 500);
                }
            });

            // Dismiss messages
            $(document).on('click', '.mt-message-dismiss', function() {
                $(this).closest('.mt-message').fadeOut();
            });
        },

        // Submit vote via AJAX
        submitVote: function(formData, $form) {
            const $submitButton = $form.find('button[type="submit"]');
            $submitButton.prop('disabled', true).text('Submitting...');

            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=mt_submit_vote',
                success: function(response) {
                    if (response.success) {
                        MTFrontend.showMessage('Vote submitted successfully!', 'success');
                        $form[0].reset();
                        MTFrontend.updateTotalScore();
                    } else {
                        MTFrontend.showMessage(response.data.message || 'Error submitting vote', 'error');
                    }
                },
                complete: function() {
                    $submitButton.prop('disabled', false).text('Submit Vote');
                }
            });
        },

        // Update total score display
        updateTotalScore: function() {
            let total = 0;
            $('.mt-score-slider').each(function() {
                total += parseInt($(this).val()) || 0;
            });
            
            $('#mt-total-score').text(total);
            
            // Update progress bar if exists
            const maxScore = $('.mt-score-slider').length * 10;
            const percentage = (total / maxScore) * 100;
            $('.mt-total-progress-fill').css('width', percentage + '%');
        },

        // Filter candidates by category
        filterCandidates: function(category) {
            const $candidates = $('.mt-candidate-card');
            
            if (category === 'all' || !category) {
                $candidates.fadeIn();
            } else {
                $candidates.each(function() {
                    const $card = $(this);
                    if ($card.data('category') === category) {
                        $card.fadeIn();
                    } else {
                        $card.fadeOut();
                    }
                });
            }
        },

        // Search candidates
        searchCandidates: function(searchTerm) {
            const $candidates = $('.mt-candidate-card');
            
            $candidates.each(function() {
                const $card = $(this);
                const text = $card.text().toLowerCase();
                
                if (text.includes(searchTerm)) {
                    $card.fadeIn();
                } else {
                    $card.fadeOut();
                }
            });
        },

        // Load more candidates
        loadMoreCandidates: function(page, $button) {
            $button.prop('disabled', true).text('Loading...');

            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_load_more_candidates',
                    page: page,
                    nonce: mt_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $('.mt-candidates-grid').append(response.data.html);
                        $button.data('page', page);
                        
                        if (!response.data.has_more) {
                            $button.hide();
                        }
                    }
                },
                complete: function() {
                    $button.prop('disabled', false).text('Load More');
                }
            });
        },

        // Load evaluation data
        loadEvaluation: function(candidateId) {
            MTFrontend.showLoading();

            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_evaluation',
                    candidate_id: candidateId,
                    nonce: mt_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        MTFrontend.populateEvaluation(response.data);
                    }
                },
                complete: function() {
                    MTFrontend.hideLoading();
                }
            });
        },

        // Populate evaluation form with data
        populateEvaluation: function(data) {
            // Set score buttons
            Object.keys(data).forEach(function(key) {
                if (key.endsWith('_score')) {
                    const score = data[key];
                    const criterion = key.replace('_score', '');
                    
                    $(`.mt-score-button[data-criterion="${criterion}"][data-score="${score}"]`).click();
                }
            });

            // Set comments
            if (data.comments) {
                $('#mt-evaluation-comments').val(data.comments);
            }

            // Update total score
            MTFrontend.updateEvaluationScore();
        },

        // Update evaluation total score
        updateEvaluationScore: function() {
            let total = 0;
            $('.mt-score-button.selected').each(function() {
                total += parseInt($(this).data('score')) || 0;
            });
            
            $('#mt-evaluation-total').text(total + '/50');
            
            // Enable/disable submit based on completion
            const criteriaCount = $('.mt-criterion-input').length;
            const selectedCount = $('.mt-score-button.selected').length;
            
            $('#mt-submit-evaluation').prop('disabled', selectedCount < criteriaCount);
        },

        // Validate evaluation form
        validateEvaluation: function() {
            const criteriaCount = $('.mt-criterion-input').length;
            const selectedCount = $('.mt-score-button.selected').length;
            
            if (selectedCount < criteriaCount) {
                MTFrontend.showMessage('Please rate all criteria before submitting.', 'warning');
                return false;
            }
            
            return true;
        },

        // Submit evaluation
        submitEvaluation: function() {
            const $form = $('#mt-evaluation-form');
            const formData = $form.serialize();
            
            MTFrontend.showLoading();
            $('#mt-submit-evaluation').prop('disabled', true);

            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=mt_submit_evaluation',
                success: function(response) {
                    if (response.success) {
                        MTFrontend.showMessage('Evaluation submitted successfully!', 'success');
                        
                        // Mark candidate as evaluated
                        const candidateId = $('#mt-candidate-id').val();
                        $(`.mt-assigned-candidate[data-id="${candidateId}"]`)
                            .addClass('evaluated')
                            .find('.mt-status-badge')
                            .removeClass('pending')
                            .addClass('completed')
                            .text('Completed');
                        
                        // Redirect after short delay
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url || mt_ajax.dashboard_url;
                        }, 2000);
                    } else {
                        MTFrontend.showMessage(response.data.message || 'Error submitting evaluation', 'error');
                        $('#mt-submit-evaluation').prop('disabled', false);
                    }
                },
                error: function() {
                    MTFrontend.showMessage('Network error. Please try again.', 'error');
                    $('#mt-submit-evaluation').prop('disabled', false);
                },
                complete: function() {
                    MTFrontend.hideLoading();
                }
            });
        },

        // Save draft evaluation
        saveDraft: function() {
            const $form = $('#mt-evaluation-form');
            const formData = $form.serialize();
            
            // Show saving indicator
            const $saveButton = $('#mt-save-draft');
            const originalText = $saveButton.text();
            $saveButton.text('Saving...').prop('disabled', true);

            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: formData + '&action=mt_save_draft',
                success: function(response) {
                    if (response.success) {
                        MTFrontend.showMessage('Draft saved successfully!', 'success', 2000);
                    }
                },
                complete: function() {
                    $saveButton.text(originalText).prop('disabled', false);
                }
            });
        },

        // Export evaluations
        exportEvaluations: function() {
            window.location.href = mt_ajax.ajax_url + '?action=mt_export_evaluations&nonce=' + mt_ajax.nonce;
        },

        // Show message
        showMessage: function(message, type, duration) {
            type = type || 'info';
            duration = duration || 5000;
            
            const $message = $('<div class="mt-message mt-message-' + type + '">' +
                '<span>' + message + '</span>' +
                '<button class="mt-message-dismiss">&times;</button>' +
                '</div>');
            
            $('#mt-messages').append($message);
            
            if (duration > 0) {
                setTimeout(function() {
                    $message.fadeOut(function() {
                        $(this).remove();
                    });
                }, duration);
            }
        },

        // Show loading overlay
        showLoading: function() {
            if (!$('#mt-loading-overlay').length) {
                $('body').append('<div id="mt-loading-overlay" class="mt-loading"><div class="mt-spinner"></div></div>');
            }
            $('#mt-loading-overlay').fadeIn();
        },

        // Hide loading overlay
        hideLoading: function() {
            $('#mt-loading-overlay').fadeOut();
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        MTFrontend.init();
    });

    // Export to global scope
    window.MTFrontend = MTFrontend;

})(jQuery);