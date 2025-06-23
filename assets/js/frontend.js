/**
 * Mobility Trailblazers Frontend JavaScript
 * Version: 2.0.1
 */

(function($) {
    'use strict';
    
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
            
            console.log('MT JS - Button clicked, candidate ID from data:', candidateId);
            
            if (candidateId) {
                this.loadEvaluationForm(candidateId);
            } else {
                this.showError('Invalid candidate ID.');
            }
        },
        
        loadEvaluationForm: function(candidateId) {
            var self = this;
            
            console.log('MT JS - Loading evaluation form for candidate ID:', candidateId);
            
            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.nonce) {
                self.showError('Security configuration error. Please refresh the page and try again.');
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
                console.log('MT JS - Candidate details response:', response);
                if (response.success) {
                    console.log('MT JS - Candidate data received:', response.data);
                    // The candidate data is nested under response.data.data
                    var candidateData = response.data.data || response.data;
                    console.log('MT JS - Extracted candidate data:', candidateData);
                    self.displayEvaluationForm(candidateData);
                    self.loadExistingEvaluation(candidateId);
                } else {
                    self.showError(response.data.message);
                }
            })
            .fail(function() {
                self.showError('Failed to load candidate details.');
            });
        },
        
        displayEvaluationForm: function(candidate) {
            console.log('MT JS - Displaying evaluation form for candidate:', candidate);
            console.log('MT JS - Candidate ID being set in form:', candidate.id);
            
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
            
            // Debug: Check the hidden input after form is created
            var hiddenInput = $('input[name="candidate_id"]');
            console.log('MT JS - Hidden input created with value:', hiddenInput.val());
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
            
            // Debug: Check form selection
            console.log('MT JS - Form element:', $form);
            console.log('MT JS - Form ID:', $form.attr('id'));
            console.log('MT JS - Form class:', $form.attr('class'));
            
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
            
            console.log('MT JS - Target form found:', $targetForm.length);
            
            // Debug: Check what fields are found
            var allFields = $targetForm.find('input, textarea, select');
            console.log('MT JS - Found form fields:', allFields.length);
            allFields.each(function(index) {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                console.log('MT JS - Field ' + index + ':', name, '=', value);
            });
            
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
            
            // Debug: Log form data being sent
            console.log('MT JS - Form data being sent:', formData);
            console.log('MT JS - Candidate ID in form data:', formData.candidate_id);
            
            // Debug: Manually check the hidden input value
            var hiddenInput = $('input[name="candidate_id"]');
            console.log('MT JS - Hidden input value before submission:', hiddenInput.val());
            console.log('MT JS - Hidden input exists:', hiddenInput.length > 0);
            
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
            
            console.log('MT JS - Save draft - Target form found:', $form.length);
            
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
    
})(jQuery); 