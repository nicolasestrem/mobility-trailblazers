/**
 * Mobility Trailblazers Frontend JavaScript
 * Version: 2.0.0
 */

(function($) {
    'use strict';
    
    // Jury Dashboard
    var MTJuryDashboard = {
        init: function() {
            this.bindEvents();
            this.initEvaluationForm();
        },
        
        bindEvents: function() {
            // Evaluation form submission
            $(document).on('submit', '#mt-evaluation-form', this.submitEvaluation);
            
            // Save draft
            $(document).on('click', '.mt-save-draft', this.saveDraft);
            
            // Score slider updates
            $(document).on('input', '.mt-score-input', this.updateScoreDisplay);
            
            // Load evaluation modal
            $(document).on('click', '.mt-evaluate-btn', this.loadEvaluation);
        },
        
        initEvaluationForm: function() {
            // Check if we're on evaluation page
            var urlParams = new URLSearchParams(window.location.search);
            var candidateId = urlParams.get('evaluate');
            
            if (candidateId) {
                this.loadEvaluationForm(candidateId);
            }
        },
        
        loadEvaluationForm: function(candidateId) {
            var self = this;
            
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
                    self.displayEvaluationForm(response.data);
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
                            
                            <div class="mt-criterion">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-superhero"></span>
                                    <h4 class="mt-criterion-label">Mut & Pioniergeist</h4>
                                </div>
                                <p class="mt-criterion-description">Courage & Pioneer Spirit</p>
                                <div class="mt-score-slider">
                                    <input type="range" name="courage_score" class="mt-score-input" min="0" max="10" value="5">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            
                            <div class="mt-criterion">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-lightbulb"></span>
                                    <h4 class="mt-criterion-label">Innovationsgrad</h4>
                                </div>
                                <p class="mt-criterion-description">Innovation Degree</p>
                                <div class="mt-score-slider">
                                    <input type="range" name="innovation_score" class="mt-score-input" min="0" max="10" value="5">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            
                            <div class="mt-criterion">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-hammer"></span>
                                    <h4 class="mt-criterion-label">Umsetzungskraft & Wirkung</h4>
                                </div>
                                <p class="mt-criterion-description">Implementation & Impact</p>
                                <div class="mt-score-slider">
                                    <input type="range" name="implementation_score" class="mt-score-input" min="0" max="10" value="5">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            
                            <div class="mt-criterion">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-location-alt"></span>
                                    <h4 class="mt-criterion-label">Relevanz für Mobilitätswende</h4>
                                </div>
                                <p class="mt-criterion-description">Mobility Transformation Relevance</p>
                                <div class="mt-score-slider">
                                    <input type="range" name="relevance_score" class="mt-score-input" min="0" max="10" value="5">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            
                            <div class="mt-criterion">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-visibility"></span>
                                    <h4 class="mt-criterion-label">Vorbildfunktion & Sichtbarkeit</h4>
                                </div>
                                <p class="mt-criterion-description">Role Model & Visibility</p>
                                <div class="mt-score-slider">
                                    <input type="range" name="visibility_score" class="mt-score-input" min="0" max="10" value="5">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-comments-section">
                            <label for="comments" class="mt-comments-label">Additional Comments (Optional)</label>
                            <textarea name="comments" id="comments" class="mt-comments-textarea" rows="5"></textarea>
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
        },
        
        loadExistingEvaluation: function(candidateId) {
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
            $input.siblings('.mt-score-value').text(value);
        },
        
        submitEvaluation: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            
            // Disable button and show loading
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            var formData = $form.serializeArray();
            formData.push({ name: 'action', value: 'mt_submit_evaluation' });
            formData.push({ name: 'nonce', value: mt_ajax.nonce });
            
            $.post(mt_ajax.url, formData)
                .done(function(response) {
                    if (response.success) {
                        // Show success message
                        $form.before('<div class="mt-notice mt-notice-success">' + response.data.message + '</div>');
                        
                        // Scroll to top
                        $('html, body').animate({ scrollTop: 0 }, 300);
                        
                        // Redirect after 2 seconds
                        setTimeout(function() {
                            window.location.href = window.location.pathname;
                        }, 2000);
                    } else {
                        MTJuryDashboard.showError(response.data.message);
                        $submitBtn.prop('disabled', false).text('Submit Evaluation');
                    }
                })
                .fail(function() {
                    MTJuryDashboard.showError('An error occurred. Please try again.');
                    $submitBtn.prop('disabled', false).text('Submit Evaluation');
                });
        },
        
        saveDraft: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $form = $('#mt-evaluation-form');
            
            // Disable button and show loading
            $btn.prop('disabled', true).text('Saving...');
            
            var formData = $form.serializeArray();
            formData.push({ name: 'action', value: 'mt_save_draft' });
            formData.push({ name: 'nonce', value: mt_ajax.nonce });
            
            $.post(mt_ajax.url, formData)
                .done(function(response) {
                    if (response.success) {
                        // Show success message
                        $btn.text('Draft Saved!');
                        setTimeout(function() {
                            $btn.prop('disabled', false).text('Save as Draft');
                        }, 2000);
                    } else {
                        MTJuryDashboard.showError(response.data.message);
                        $btn.prop('disabled', false).text('Save as Draft');
                    }
                })
                .fail(function() {
                    MTJuryDashboard.showError('Failed to save draft.');
                    $btn.prop('disabled', false).text('Save as Draft');
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
    
})(jQuery); 