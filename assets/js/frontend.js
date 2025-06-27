/**
 * Mobility Trailblazers Frontend JavaScript
 * Version: 2.0.1
 */

(function($) {
    'use strict';

    // Global error handler for uncaught JavaScript errors
    window.addEventListener('error', function(e) {
        if (window.MTErrorHandler) {
            MTErrorHandler.logError('JavaScript Error', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno,
                stack: e.error ? e.error.stack : 'No stack trace available'
            });
        }
    });

    // Initialize error handler
    window.MTErrorHandler = {
        /**
         * Log error to console and potentially send to server
         */
        logError: function(message, details) {
            var errorData = {
                message: message,
                details: details || {},
                timestamp: new Date().toISOString(),
                url: window.location.href,
                userAgent: navigator.userAgent
            };

            // Log to console in debug mode
            if (window.console && console.error) {
                console.error('MT Error:', errorData);
            }
        },

        /**
         * Show user-friendly error message
         */
        showUserError: function(message, type) {
            type = type || 'error';
            var alertClass = type === 'warning' ? 'mt-alert-warning' : 'mt-alert-error';

            var $alert = $('<div class="mt-alert ' + alertClass + '">' +
                '<span class="mt-alert-message">' + message + '</span>' +
                '<button class="mt-alert-close" type="button">&times;</button>' +
                '</div>');

            // Remove existing alerts
            $('.mt-alert').remove();

            // Add new alert
            $('body').prepend($alert);

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $alert.fadeOut(function() {
                    $alert.remove();
                });
            }, 5000);

            // Manual close
            $alert.find('.mt-alert-close').on('click', function() {
                $alert.fadeOut(function() {
                    $alert.remove();
                });
            });
        },

        /**
         * Handle AJAX errors with user-friendly messages
         */
        handleAjaxError: function(xhr, status, error, context) {
            var errorMessage = 'An error occurred. Please try again.';
            var logDetails = {
                status: status,
                error: error,
                responseText: xhr.responseText,
                context: context || 'Unknown'
            };

            // Try to extract meaningful error message
            try {
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please check your connection and try again.';
                } else if (status === 'abort') {
                    errorMessage = 'Request was cancelled.';
                } else if (xhr.status === 403) {
                    errorMessage = 'You do not have permission to perform this action.';
                } else if (xhr.status === 404) {
                    errorMessage = 'The requested resource was not found.';
                } else if (xhr.status >= 500) {
                    errorMessage = 'Server error. Please try again later.';
                }
            } catch (e) {
                logDetails.parseError = e.message;
            }

            this.logError('AJAX Error in ' + context, logDetails);
            this.showUserError(errorMessage);

            return errorMessage;
        }
    };

    // Jury Dashboard
    var MTJuryDashboard = {
        init: function() {
            this.bindEvents();
            this.initEvaluationForm();
            this.updateTotalScore();
            this.initCharacterCount();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Evaluation form submission
            $(document).on('submit', '#mt-evaluation-form', function(e) {
                self.submitEvaluation.call(self, e);
            });
            
            // Save draft
            $(document).on('click', '.mt-save-draft', function(e) {
                self.saveDraft.call(self, e);
            });
            
            // Score slider updates
            $(document).on('input', '.mt-score-slider', function() {
                self.updateScoreDisplay.call(this);
            });
            
            // Score mark clicks
            $(document).on('click', '.mt-score-mark', function(e) {
                self.setScoreFromMark.call(this, e);
            });
            
            // Load evaluation modal
            $(document).on('click', '.mt-evaluate-btn', function(e) {
                self.loadEvaluation.call(self, e);
            });
            
            // Character count
            $(document).on('input', '#mt-comments', function() {
                self.updateCharCount.call(this);
            });
        },
        
        initEvaluationForm: function() {
            // Check if we're on evaluation page
            var urlParams = new URLSearchParams(window.location.search);
            var candidateId = urlParams.get('evaluate');
            
            if (candidateId) {
                this.loadEvaluationForm(candidateId);
            }
        },
        
        loadEvaluation: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var candidateId = $btn.data('candidate-id');

            if (candidateId) {
                this.loadEvaluationForm(candidateId);
            } else {
                this.showError('Invalid candidate ID.');
            }
        },
        
        loadEvaluationForm: function(candidateId) {
            var self = this;

            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.nonce) {
                MTErrorHandler.logError('mt_ajax configuration missing', {
                    mt_ajax_defined: typeof mt_ajax !== 'undefined',
                    nonce_present: mt_ajax && mt_ajax.nonce ? true : false
                });
                MTErrorHandler.showUserError('Security configuration error. Please refresh the page and try again.');
                return;
            }
            
            // Show loading state
            var $container = $('.mt-jury-dashboard');
            $container.html('<div class="mt-loading">Loading evaluation form...</div>');
            
            // Load candidate details
            $.post(mt_ajax.url, {
                action: 'mt_get_candidate_details',
                candidate_id: candidateId,
                nonce: mt_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    // The candidate data is nested under response.data.data
                    var candidateData = response.data.data || response.data;
                    self.displayEvaluationForm(candidateData);
                    self.loadExistingEvaluation(candidateId);
                } else {
                    self.showError(response.data.message);
                }
            })
            .fail(function(xhr, status, error) {
                MTErrorHandler.handleAjaxError(xhr, status, error, 'loadEvaluationForm - candidate details');
            });
        },
        
        displayEvaluationForm: function(candidate) {
            
            var formHtml = `
                <div class="mt-evaluation-wrapper">
                    <div class="mt-candidate-details">
                        <h2>${candidate.name}</h2>
                        <p class="mt-candidate-org">${candidate.organization || ''}</p>
                        ${candidate.photo_url ? `<img src="${candidate.photo_url}" alt="${candidate.name}" class="mt-candidate-photo-eval">` : ''}
                        <div class="mt-candidate-bio">${candidate.bio}</div>
                    </div>
                    
                    <form id="mt-evaluation-form" class="mt-evaluation-form">
                        <input type="hidden" name="candidate_id" value="${candidate.id}">
                        
                        <div class="mt-criteria-section">
                            <h3>Evaluation Criteria</h3>
                            
                            <div class="mt-criterion-card" data-criterion="courage">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-superhero"></span>
                                    <h4 class="mt-criterion-label">Mut & Pioniergeist</h4>
                                </div>
                                <p class="mt-criterion-description">Courage & Pioneer Spirit</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="courage_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            
                            <div class="mt-criterion-card" data-criterion="innovation">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-lightbulb"></span>
                                    <h4 class="mt-criterion-label">Innovationsgrad</h4>
                                </div>
                                <p class="mt-criterion-description">Innovation Degree</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="innovation_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            
                            <div class="mt-criterion-card" data-criterion="implementation">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-hammer"></span>
                                    <h4 class="mt-criterion-label">Umsetzungskraft & Wirkung</h4>
                                </div>
                                <p class="mt-criterion-description">Implementation & Impact</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="implementation_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            
                            <div class="mt-criterion-card" data-criterion="relevance">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-location-alt"></span>
                                    <h4 class="mt-criterion-label">Relevanz für Mobilitätswende</h4>
                                </div>
                                <p class="mt-criterion-description">Mobility Transformation Relevance</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="relevance_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            
                            <div class="mt-criterion-card" data-criterion="visibility">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-visibility"></span>
                                    <h4 class="mt-criterion-label">Vorbildfunktion & Sichtbarkeit</h4>
                                </div>
                                <p class="mt-criterion-description">Role Model & Visibility</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="visibility_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-comments-section">
                            <label for="mt-comments" class="mt-comments-label">Additional Comments (Optional)</label>
                            <textarea name="comments" id="mt-comments" class="mt-comments-textarea" rows="5"></textarea>
                            <div class="mt-char-count">
                                <span id="mt-char-current">0</span> / 1000 characters
                            </div>
                        </div>
                        
                        <div class="mt-form-actions">
                            <button type="button" class="mt-btn mt-btn-secondary mt-save-draft">Save as Draft</button>
                            <button type="submit" class="mt-btn mt-btn-primary">Submit Evaluation</button>
                            <a href="${window.location.pathname}" class="mt-btn mt-btn-secondary">Back to Dashboard</a>
                        </div>
                    </form>
                </div>
            `;
            
            $('.mt-jury-dashboard').html(formHtml);
            
            // Verify the hidden input was created successfully
        },
        
        loadExistingEvaluation: function(candidateId) {
            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.nonce) {
                MTJuryDashboard.showError('Security configuration error. Please refresh the page and try again.');
                return;
            }
            
            $.post(mt_ajax.url, {
                action: 'mt_get_evaluation',
                candidate_id: candidateId,
                nonce: mt_ajax.nonce
            })
            .done(function(response) {
                if (response.success && response.data.exists) {
                    var evaluation = response.data.evaluation;
                    
                    // Populate form with existing values
                    $('input[name="courage_score"]').val(evaluation.courage_score).trigger('input');
                    $('input[name="innovation_score"]').val(evaluation.innovation_score).trigger('input');
                    $('input[name="implementation_score"]').val(evaluation.implementation_score).trigger('input');
                    $('input[name="relevance_score"]').val(evaluation.relevance_score).trigger('input');
                    $('input[name="visibility_score"]').val(evaluation.visibility_score).trigger('input');
                    $('textarea[name="comments"]').val(evaluation.comments);
                    
                    // Show status
                    if (evaluation.status === 'completed') {
                        $('.mt-form-actions').prepend('<div class="mt-notice mt-notice-success">This evaluation has been submitted. You can still edit and resubmit.</div>');
                    }
                }
            });
        },
        
        updateScoreDisplay: function() {
            var $input = $(this);
            var value = $input.val();
            var $card = $input.closest('.mt-criterion-card');
            
            // Update the score display
            $card.find('.mt-score-value').text(value);
            
            // Update slider background gradient
            var percentage = (value / 10) * 100;
            $input.css('background', 'linear-gradient(to right, #667eea 0%, #667eea ' + percentage + '%, #e5e7eb ' + percentage + '%, #e5e7eb 100%)');
            
            // Update total score
            var self = MTJuryDashboard;
            self.updateTotalScore();
        },
        
        setScoreFromMark: function(e) {
            e.preventDefault();
            var $mark = $(this);
            var value = $mark.data('value');
            var $slider = $mark.closest('.mt-criterion-card').find('.mt-score-slider');
            
            $slider.val(value).trigger('input');
        },
        
        updateTotalScore: function() {
            var total = 0;
            var count = 0;
            
            $('.mt-score-slider').each(function() {
                total += parseInt($(this).val()) || 0;
                count++;
            });
            
            var average = count > 0 ? (total / count).toFixed(1) : 0;
            $('#mt-total-score').text(average);
        },
        
        updateCharCount: function() {
            var $textarea = $(this);
            var length = $textarea.val().length;
            var maxLength = 1000;
            
            $('#mt-char-current').text(length);
            
            if (length > maxLength) {
                $textarea.val($textarea.val().substring(0, maxLength));
                $('#mt-char-current').text(maxLength);
            }
        },
        
        initCharacterCount: function() {
            var $textarea = $('#mt-comments');
            if ($textarea.length) {
                $('#mt-char-current').text($textarea.val().length);
            }
        },
        
        submitEvaluation: function(e) {
            e.preventDefault();
            
            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.nonce) {
                MTJuryDashboard.showError('Security configuration error. Please refresh the page and try again.');
                return;
            }
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            
            // Validate form selection
            
            // Validate scores
            var isValid = true;
            $('.mt-score-slider').each(function() {
                var value = parseInt($(this).val());
                if (isNaN(value) || value < 0 || value > 10) {
                    isValid = false;
                    return false;
                }
            });
            
            if (!isValid) {
                MTJuryDashboard.showError('Please ensure all scores are between 0 and 10.');
                return;
            }
            
            // Disable button and show loading
            $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update mt-spin"></span> Submitting...');
            
            // Get form data including all fields
            var formData = {};
            
            // Try multiple selectors to find the form
            var $targetForm = $('#mt-evaluation-form');
            if ($targetForm.length === 0) {
                $targetForm = $('.mt-evaluation-form');
            }
            if ($targetForm.length === 0) {
                $targetForm = $form;
            }
            
            // Add all form fields
            $targetForm.find('input, textarea, select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                
                if (name && value !== undefined) {
                    formData[name] = value;
                }
            });
            
            // Add required AJAX fields
            formData.action = 'mt_submit_evaluation';
            formData.nonce = mt_ajax.nonce;
            formData.status = 'completed';
            
            $.post(mt_ajax.url, formData)
                .done(function(response) {
                    if (response.success) {
                        // Show success message
                        $('.mt-evaluation-header').after('<div class="mt-notice mt-notice-success">' + response.data.message + '</div>');
                        
                        // Update status badge
                        var $statusBadge = $('.mt-evaluation-title .mt-status-badge');
                        if ($statusBadge.length) {
                            $statusBadge.removeClass('mt-status-draft').addClass('mt-status-completed').text('Evaluation Submitted');
                        } else {
                            $('.mt-evaluation-title').append('<span class="mt-status-badge mt-status-completed">Evaluation Submitted</span>');
                        }
                        
                        // Scroll to top
                        $('html, body').animate({ scrollTop: 0 }, 300);
                        
                        // Re-enable button
                        $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Submit Evaluation');
                        
                        // Redirect after 3 seconds
                        setTimeout(function() {
                            window.location.href = window.location.pathname;
                        }, 3000);
                    } else {
                        MTJuryDashboard.showError(response.data.message);
                        $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Submit Evaluation');
                    }
                })
                .fail(function() {
                    MTJuryDashboard.showError('An error occurred. Please try again.');
                    $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Submit Evaluation');
                });
        },
        
        saveDraft: function(e) {
            e.preventDefault();
            
            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.nonce) {
                MTJuryDashboard.showError('Security configuration error. Please refresh the page and try again.');
                return;
            }
            
            var $btn = $(this);
            var $form = $('#mt-evaluation-form');
            
            // Try multiple selectors to find the form
            if ($form.length === 0) {
                $form = $('.mt-evaluation-form');
            }
            

            
            // Disable button and show loading
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update mt-spin"></span> Saving...');
            
            // Get form data including all fields
            var formData = {};
            
            // Add all form fields
            $form.find('input, textarea, select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                
                if (name && value !== undefined) {
                    formData[name] = value;
                }
            });
            
            // Add required AJAX fields
            formData.action = 'mt_submit_evaluation';
            formData.nonce = mt_ajax.nonce;
            formData.status = 'draft';
            
            $.post(mt_ajax.url, formData)
                .done(function(response) {
                    if (response.success) {
                        // Show success message temporarily
                        $btn.html('<span class="dashicons dashicons-saved"></span> Draft Saved!');
                        
                        // Update or add status badge
                        var $statusBadge = $('.mt-evaluation-title .mt-status-badge');
                        if ($statusBadge.length) {
                            $statusBadge.removeClass('mt-status-completed').addClass('mt-status-draft').text('Draft Saved');
                        } else {
                            $('.mt-evaluation-title').append('<span class="mt-status-badge mt-status-draft">Draft Saved</span>');
                        }
                        
                        setTimeout(function() {
                            $btn.prop('disabled', false).html('<span class="dashicons dashicons-edit"></span> Save as Draft');
                        }, 2000);
                    } else {
                        MTJuryDashboard.showError(response.data.message);
                        $btn.prop('disabled', false).html('<span class="dashicons dashicons-edit"></span> Save as Draft');
                    }
                })
                .fail(function() {
                    MTJuryDashboard.showError('Failed to save draft.');
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-edit"></span> Save as Draft');
                });
        },
        
        showError: function(message) {
            var errorHtml = '<div class="mt-notice mt-notice-error">' + message + '</div>';
            $('.mt-evaluation-form').before(errorHtml);
            $('html, body').animate({ scrollTop: 0 }, 300);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        MTJuryDashboard.init();
    });
    
    // Star rating functionality
    if ($('.mt-star-rating').length) {
        $('.mt-star-rating').each(function() {
            var $rating = $(this);
            var $input = $rating.find('input[type="hidden"]');
            var $stars = $rating.find('.mt-star');
            
            $stars.on('click', function() {
                var value = $(this).data('value');
                $input.val(value).trigger('change');
                updateStars($rating, value);
            });
            
            $stars.on('mouseenter', function() {
                var value = $(this).data('value');
                updateStars($rating, value);
            });
            
            $rating.on('mouseleave', function() {
                var value = $input.val() || 0;
                updateStars($rating, value);
            });
        });
        
        function updateStars($rating, value) {
            $rating.find('.mt-star').each(function() {
                var starValue = $(this).data('value');
                $(this).toggleClass('filled', starValue <= value);
            });
        }
    }

    // Star rating functionality
    $(document).on('click', '.mt-star-rating .dashicons', function() {
        const $star = $(this);
        const value = $star.data('value');
        const $rating = $star.parent();
        
        $rating.find('.dashicons').removeClass('active');
        $rating.find('.dashicons').each(function() {
            if ($(this).data('value') <= value) {
                $(this).addClass('active');
            }
        });
        
        $rating.find('input[type="hidden"]').val(value);
        updateScoreDisplay($star.closest('.mt-criterion-card'), value);
    });

    // Button scoring functionality
    $(document).on('click', '.mt-score-button', function() {
        const $button = $(this);
        const value = $button.data('value');
        const $group = $button.parent();
        
        $group.find('.mt-score-button').removeClass('active');
        $button.addClass('active');
        
        $group.find('input[type="hidden"]').val(value);
        updateScoreDisplay($button.closest('.mt-criterion-card'), value);
    });

    // Numeric input functionality
    $(document).on('input', '.mt-score-input', function() {
        const value = Math.min(10, Math.max(0, $(this).val()));
        $(this).val(value);
        updateScoreDisplay($(this).closest('.mt-criterion-card'), value);
    });

    // Update score display
    function updateScoreDisplay($criterion, value) {
        $criterion.find('.mt-score-value').text(value);
    }

    // Rankings update functionality
    jQuery(document).ready(function($) {
        // Auto-refresh rankings after evaluation submission
        $(document).on('mt:evaluation:submitted', function() {
            refreshRankings();
        });
        
        // Refresh rankings function
        function refreshRankings() {
            $.ajax({
                url: mt_ajax.url,
                type: 'POST',
                data: {
                    action: 'mt_get_jury_rankings',
                    nonce: mt_ajax.nonce,
                    limit: 10
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $('#mt-rankings-container').html(response.data.html);
                        
                        // Add animation
                        $('.mt-ranking-item').each(function(index) {
                            $(this).css('opacity', '0').delay(index * 50).animate({
                                opacity: 1
                            }, 300);
                        });
                    }
                }
            });
        }
        
        // Optional: Refresh rankings periodically
        setInterval(refreshRankings, 60000); // Every minute
    });

    // Enhanced Rankings Interactivity
    jQuery(document).ready(function($) {
        // Animate score rings on page load
        function animateScoreRings() {
            $('.mt-score-ring-progress').each(function() {
                const $this = $(this);
                const offset = $this.css('stroke-dashoffset');
                $this.css('stroke-dashoffset', '100');
                
                setTimeout(() => {
                    $this.css('stroke-dashoffset', offset);
                }, 100);
            });
        }
        
        // Initialize animations
        if ($('.mt-rankings-section').length) {
            animateScoreRings();
        }
        
        // Hover effects for ranking cards
        $('.mt-ranking-item').on('mouseenter', function() {
            $(this).find('.mt-score-ring-progress').css('stroke', '#764ba2');
        }).on('mouseleave', function() {
            $(this).find('.mt-score-ring-progress').css('stroke', '#667eea');
        });
        
        // Click feedback
        $('.mt-ranking-item').on('click', function() {
            $(this).css('transform', 'scale(0.98)');
            setTimeout(() => {
                $(this).css('transform', '');
            }, 150);
        });
    });

    // Inline Evaluation Controls
    jQuery(document).ready(function($) {
        initializeInlineEvaluations();
        
        function initializeInlineEvaluations() {
            // Initialize all score rings on page load
            $('.mt-score-ring-mini').each(function() {
                const score = $(this).data('score');
                updateMiniScoreRing($(this), score);
            });
            
            // Score adjustment buttons
            $(document).on('click', '.mt-score-adjust', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $input = $button.siblings('.mt-score-input');
                const action = $button.data('action');
                const currentValue = parseFloat($input.val()) || 0;
                let newValue = currentValue;
                
                if (action === 'increase' && currentValue < 10) {
                    newValue = Math.min(10, currentValue + 0.5);
                } else if (action === 'decrease' && currentValue > 0) {
                    newValue = Math.max(0, currentValue - 0.5);
                }
                
                $input.val(newValue).trigger('change');
            });
            
            // Score input change handler
            $(document).on('change', '.mt-score-input', function() {
                const $input = $(this);
                const value = parseFloat($input.val()) || 0;
                
                // Validate and constrain value
                const constrainedValue = Math.max(0, Math.min(10, value));
                if (value !== constrainedValue) {
                    $input.val(constrainedValue);
                }
                
                // Update mini ring
                const $ring = $input.closest('.mt-criterion-inline').find('.mt-score-ring-mini');
                updateMiniScoreRing($ring, constrainedValue);
                
                // Update total score preview
                updateTotalScorePreview($input.closest('.mt-ranking-item'));
            });
            
            // Save inline evaluation
            $(document).on('click', '.mt-btn-save-inline', function(e) {
                e.preventDefault();
                
                const $button = $(this);
                const $form = $button.closest('.mt-inline-evaluation-form');
                const $rankingItem = $button.closest('.mt-ranking-item');
                const candidateId = $form.data('candidate-id');
                
                // Prevent double submission
                if ($button.hasClass('saving')) {
                    return;
                }
                
                // Collect scores
                const scores = {};
                $form.find('.mt-score-input').each(function() {
                    const criterion = $(this).attr('name');
                    const value = $(this).val();
                    if (criterion && value) {
                        scores[criterion] = value;
                    }
                });
                
                // Add loading state
                $button.addClass('saving');
                $rankingItem.addClass('updating');
                
                // Prepare form data
                const formData = {
                    action: 'mt_save_inline_evaluation',
                    nonce: mt_ajax.nonce,
                    candidate_id: candidateId,
                    scores: scores
                };
                

                
                // Save via AJAX
                $.ajax({
                    url: mt_ajax.url,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        
                        if (response.success) {
                            // Show success animation
                            $rankingItem.removeClass('updating').addClass('success');
                            
                            // Update total score display
                            const totalScore = response.data.total_score;
                            const $scoreValue = $rankingItem.find('.mt-total-score-display .score-value');
                            $scoreValue.text(totalScore.toFixed(1) + '/10');
                            $scoreValue.data('score', totalScore);
                            
                            // Update score color
                            updateScoreColor($scoreValue, totalScore);
                            
                            // Trigger rankings refresh after a delay
                            setTimeout(function() {
                                refreshRankings();
                            }, 1500);
                            
                            // Remove success class after animation
                            setTimeout(function() {
                                $rankingItem.removeClass('success');
                            }, 2000);
                        } else {
                            console.error('Save failed:', response.data);
                            alert(response.data || 'Error saving evaluation');
                            $rankingItem.removeClass('updating');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', {
                            status: status,
                            error: error,
                            responseText: xhr.responseText,
                            responseJSON: xhr.responseJSON
                        });
                        
                        // Try to parse error message
                        let errorMessage = 'Network error. Please try again.';
                        try {
                            if (xhr.responseJSON && xhr.responseJSON.data) {
                                errorMessage = xhr.responseJSON.data;
                            }
                        } catch (e) {
                            // Use default message
                        }
                        
                        alert(errorMessage);
                        $rankingItem.removeClass('updating');
                    },
                    complete: function() {
                        $button.removeClass('saving');
                    }
                });
            });
        }
        
        function updateMiniScoreRing($ring, score) {
            const $progress = $ring.find('.mt-ring-progress');
            
            // Update ring
            const dashArray = (score * 10) + ', 100';
            $progress.attr('stroke-dasharray', dashArray);
            $ring.attr('data-score', score);
            
            // Update color based on score
            if (score >= 8) {
                $progress.css('stroke', '#22c55e');
            } else if (score >= 6) {
                $progress.css('stroke', '#667eea');
            } else if (score >= 4) {
                $progress.css('stroke', '#f59e0b');
            } else {
                $progress.css('stroke', '#ef4444');
            }
        }
        
        function updateTotalScorePreview($rankingItem) {
            const scores = [];
            $rankingItem.find('.mt-score-input').each(function() {
                const value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    scores.push(value);
                }
            });
            
            if (scores.length > 0) {
                const average = scores.reduce((a, b) => a + b, 0) / scores.length;
                const $scoreDisplay = $rankingItem.find('.mt-total-score-display .score-value');
                $scoreDisplay.text(average.toFixed(1) + '/10');
                updateScoreColor($scoreDisplay, average);
            }
        }
        
        function updateScoreColor($element, score) {
            if (score >= 8) {
                $element.css('color', '#22c55e');
            } else if (score >= 6) {
                $element.css('color', '#667eea');
            } else if (score >= 4) {
                $element.css('color', '#f59e0b');
            } else {
                $element.css('color', '#ef4444');
            }
        }
        
        function refreshRankings() {
            const $container = $('#mt-rankings-container');
            
            $.ajax({
                url: mt_ajax.url,
                type: 'POST',
                data: {
                    action: 'mt_get_jury_rankings',
                    nonce: mt_ajax.nonce,
                    limit: 10
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Store current form values before refresh
                        const currentValues = {};
                        $('.mt-inline-evaluation-form').each(function() {
                            const candidateId = $(this).data('candidate-id');
                            currentValues[candidateId] = {};
                            $(this).find('.mt-score-input').each(function() {
                                const criterion = $(this).attr('name');
                                currentValues[candidateId][criterion] = $(this).val();
                            });
                        });
                        
                        // Update the container
                        $container.fadeOut(300, function() {
                            $(this).html(response.data.html).fadeIn(300, function() {
                                // Reinitialize everything
                                initializeInlineEvaluations();
                            });
                        });
                    }
                },
                error: function() {
                    // Rankings refresh failed - fail silently in production
                }
            });
        }
        
        // Auto-refresh rankings every 30 seconds if on dashboard
        if ($('.mt-rankings-section').length > 0) {
            setInterval(refreshRankings, 30000);
        }
    });

})(jQuery); 