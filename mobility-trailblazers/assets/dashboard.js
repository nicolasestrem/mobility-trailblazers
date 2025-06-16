/**
 * Jury Dashboard JavaScript
 * 
 * @package MobilityTrailblazers
 */

(function($) {
    'use strict';

    // Jury Dashboard Object
    window.MTJuryDashboard = {
        // Properties
        currentCandidate: null,
        isDirty: false,
        autoSaveTimer: null,
        
        // Initialize
        init: function() {
            this.bindEvents();
            this.initializeSliders();
            this.checkProgress();
            this.setupAutoSave();
        },
        
        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Candidate selection
            $(document).on('click', '.candidate-card', function(e) {
                if (!$(e.target).hasClass('evaluation-status')) {
                    var candidateId = $(this).data('candidate-id');
                    self.loadCandidate(candidateId);
                }
            });
            
            // Score sliders
            $('.score-slider').on('input', function() {
                var value = $(this).val();
                $(this).siblings('.score-value').text(value);
                self.updateTotalScore();
                self.isDirty = true;
            });
            
            // Comments
            $('#evaluation-comments').on('input', function() {
                self.isDirty = true;
            });
            
            // Submit evaluation
            $('#submit-evaluation').on('click', function() {
                self.submitEvaluation();
            });
            
            // Save draft
            $('#save-draft').on('click', function() {
                self.saveDraft();
            });
            
            // Export evaluations
            $('#export-evaluations').on('click', function() {
                self.exportEvaluations();
            });
            
            // Search
            $('#candidate-search').on('input', function() {
                self.filterCandidates($(this).val());
            });
            
            // Category filter
            $('#category-filter').on('change', function() {
                self.filterByCategory($(this).val());
            });
            
            // Status filter
            $('#status-filter').on('change', function() {
                self.filterByStatus($(this).val());
            });
            
            // Prevent accidental navigation
            $(window).on('beforeunload', function() {
                if (self.isDirty) {
                    return mt_jury_dashboard.i18n.unsaved_changes;
                }
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + S to save draft
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    self.saveDraft();
                }
                // Ctrl/Cmd + Enter to submit
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    self.submitEvaluation();
                }
            });
        },
        
        // Initialize sliders
        initializeSliders: function() {
            $('.score-slider').each(function() {
                var value = $(this).val();
                $(this).siblings('.score-value').text(value);
                
                // Add visual feedback
                $(this).on('input', function() {
                    var percent = (this.value - this.min) / (this.max - this.min) * 100;
                    $(this).css('background', 'linear-gradient(to right, #2271b1 0%, #2271b1 ' + percent + '%, #e0e0e0 ' + percent + '%, #e0e0e0 100%)');
                });
            });
        },
        
        // Load candidate
        loadCandidate: function(candidateId) {
            var self = this;
            
            if (this.isDirty) {
                if (!confirm(mt_jury_dashboard.i18n.unsaved_changes)) {
                    return;
                }
            }
            
            this.currentCandidate = candidateId;
            
            // Update UI
            $('.candidate-card').removeClass('active');
            $('.candidate-card[data-candidate-id="' + candidateId + '"]').addClass('active');
            
            // Show loading
            $('#evaluation-form').addClass('loading');
            
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_evaluation',
                    candidate_id: candidateId,
                    nonce: mt_jury_dashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.populateEvaluation(response.data);
                        $('#candidate-details').show();
                        $('#evaluation-form').show();
                        self.isDirty = false;
                    } else {
                        self.showNotification(response.data.message || mt_jury_dashboard.i18n.error_loading, 'error');
                    }
                },
                error: function() {
                    self.showNotification(mt_jury_dashboard.i18n.network_error, 'error');
                },
                complete: function() {
                    $('#evaluation-form').removeClass('loading');
                }
            });
        },
        
        // Populate evaluation form
        populateEvaluation: function(data) {
            // Update candidate details
            $('#candidate-name').text(data.candidate.name);
            $('#candidate-company').text(data.candidate.company);
            $('#candidate-position').text(data.candidate.position);
            $('#candidate-location').text(data.candidate.location);
            $('#candidate-categories').text(data.candidate.categories);
            $('#candidate-innovation').html(data.candidate.innovation_description);
            
            // Update media
            if (data.candidate.photo) {
                $('#candidate-photo').attr('src', data.candidate.photo).show();
            } else {
                $('#candidate-photo').hide();
            }
            
            // Populate scores if exists
            if (data.evaluation) {
                $('#courage-score').val(data.evaluation.courage_score || 5);
                $('#innovation-score').val(data.evaluation.innovation_score || 5);
                $('#implementation-score').val(data.evaluation.implementation_score || 5);
                $('#relevance-score').val(data.evaluation.relevance_score || 5);
                $('#visibility-score').val(data.evaluation.visibility_score || 5);
                $('#evaluation-comments').val(data.evaluation.comments || '');
                
                // Update visual
                $('.score-slider').trigger('input');
                
                // Update button state
                if (data.evaluation.is_draft) {
                    $('#submit-evaluation').text(mt_jury_dashboard.i18n.submit_evaluation);
                    $('#save-draft').show();
                } else {
                    $('#submit-evaluation').text(mt_jury_dashboard.i18n.update_evaluation);
                    $('#save-draft').hide();
                }
            } else {
                // Reset form
                this.resetForm();
            }
            
            this.updateTotalScore();
        },
        
        // Reset form
        resetForm: function() {
            $('.score-slider').val(5);
            $('.score-value').text('5');
            $('#evaluation-comments').val('');
            $('#submit-evaluation').text(mt_jury_dashboard.i18n.submit_evaluation);
            $('#save-draft').show();
            this.updateTotalScore();
        },
        
        // Update total score
        updateTotalScore: function() {
            var total = 0;
            $('.score-slider').each(function() {
                total += parseInt($(this).val());
            });
            
            $('#total-score').text(total);
            
            // Update visual indicator
            var percentage = (total / 50) * 100;
            $('#score-indicator').css('width', percentage + '%');
            
            // Color coding
            if (total >= 40) {
                $('#score-indicator').removeClass('medium low').addClass('high');
            } else if (total >= 25) {
                $('#score-indicator').removeClass('high low').addClass('medium');
            } else {
                $('#score-indicator').removeClass('high medium').addClass('low');
            }
        },
        
        // Submit evaluation
        submitEvaluation: function() {
            var self = this;
            
            if (!this.validateForm()) {
                return;
            }
            
            if (!confirm(mt_jury_dashboard.i18n.confirm_submit)) {
                return;
            }
            
            var data = this.getFormData();
            data.action = 'mt_submit_evaluation';
            data.nonce = mt_jury_dashboard.nonce;
            
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: data,
                beforeSend: function() {
                    $('#submit-evaluation').prop('disabled', true).text(mt_jury_dashboard.i18n.submitting);
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(mt_jury_dashboard.i18n.evaluation_submitted, 'success');
                        self.isDirty = false;
                        
                        // Update candidate status
                        $('.candidate-card[data-candidate-id="' + self.currentCandidate + '"]')
                            .find('.evaluation-status')
                            .removeClass('draft')
                            .addClass('completed')
                            .text(mt_jury_dashboard.i18n.evaluated);
                        
                        // Update button
                        $('#submit-evaluation').text(mt_jury_dashboard.i18n.update_evaluation);
                        $('#save-draft').hide();
                        
                        // Check progress
                        self.checkProgress();
                    } else {
                        self.showNotification(response.data.message || mt_jury_dashboard.i18n.error_submitting, 'error');
                    }
                },
                error: function() {
                    self.showNotification(mt_jury_dashboard.i18n.network_error, 'error');
                },
                complete: function() {
                    $('#submit-evaluation').prop('disabled', false);
                }
            });
        },
        
        // Save draft
        saveDraft: function() {
            var self = this;
            
            var data = this.getFormData();
            data.action = 'mt_save_draft';
            data.nonce = mt_jury_dashboard.nonce;
            
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: data,
                beforeSend: function() {
                    $('#save-draft').prop('disabled', true).text(mt_jury_dashboard.i18n.saving);
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(mt_jury_dashboard.i18n.draft_saved, 'success');
                        self.isDirty = false;
                        
                        // Update candidate status
                        $('.candidate-card[data-candidate-id="' + self.currentCandidate + '"]')
                            .find('.evaluation-status')
                            .addClass('draft')
                            .text(mt_jury_dashboard.i18n.draft);
                    } else {
                        self.showNotification(response.data.message || mt_jury_dashboard.i18n.error_saving, 'error');
                    }
                },
                error: function() {
                    self.showNotification(mt_jury_dashboard.i18n.network_error, 'error');
                },
                complete: function() {
                    $('#save-draft').prop('disabled', false).text(mt_jury_dashboard.i18n.save_draft);
                }
            });
        },
        
        // Get form data
        getFormData: function() {
            return {
                candidate_id: this.currentCandidate,
                courage: $('#courage-score').val(),
                innovation: $('#innovation-score').val(),
                implementation: $('#implementation-score').val(),
                relevance: $('#relevance-score').val(),
                visibility: $('#visibility-score').val(),
                comments: $('#evaluation-comments').val()
            };
        },
        
        // Validate form
        validateForm: function() {
            var isValid = true;
            
            $('.score-slider').each(function() {
                if (!$(this).val() || $(this).val() < 1 || $(this).val() > 10) {
                    isValid = false;
                    $(this).addClass('error');
                } else {
                    $(this).removeClass('error');
                }
            });
            
            if (!isValid) {
                this.showNotification(mt_jury_dashboard.i18n.please_rate_all, 'error');
            }
            
            return isValid;
        },
        
        // Setup auto-save
        setupAutoSave: function() {
            var self = this;
            
            // Auto-save every 30 seconds if dirty
            setInterval(function() {
                if (self.isDirty && self.currentCandidate) {
                    self.saveDraft();
                }
            }, 30000);
        },
        
        // Check progress
        checkProgress: function() {
            var total = $('.candidate-card').length;
            var completed = $('.evaluation-status.completed').length;
            var drafts = $('.evaluation-status.draft').length;
            
            $('#progress-total').text(total);
            $('#progress-completed').text(completed);
            $('#progress-drafts').text(drafts);
            $('#progress-remaining').text(total - completed - drafts);
            
            // Update progress bar
            var percentage = (completed / total) * 100;
            $('#progress-bar-fill').css('width', percentage + '%');
            $('#progress-percentage').text(Math.round(percentage) + '%');
            
            // Check if all completed
            if (completed === total) {
                this.showNotification(mt_jury_dashboard.i18n.all_complete, 'success');
                $('#completion-message').show();
            }
        },
        
        // Filter candidates
        filterCandidates: function(searchTerm) {
            var term = searchTerm.toLowerCase();
            
            $('.candidate-card').each(function() {
                var name = $(this).find('.candidate-name').text().toLowerCase();
                var company = $(this).find('.candidate-company').text().toLowerCase();
                
                if (name.includes(term) || company.includes(term)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Filter by category
        filterByCategory: function(category) {
            if (!category) {
                $('.candidate-card').show();
                return;
            }
            
            $('.candidate-card').each(function() {
                var categories = $(this).data('categories') || '';
                if (categories.includes(category)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Filter by status
        filterByStatus: function(status) {
            if (!status) {
                $('.candidate-card').show();
                return;
            }
            
            $('.candidate-card').each(function() {
                var hasStatus = false;
                
                if (status === 'completed') {
                    hasStatus = $(this).find('.evaluation-status.completed').length > 0;
                } else if (status === 'draft') {
                    hasStatus = $(this).find('.evaluation-status.draft').length > 0;
                } else if (status === 'pending') {
                    hasStatus = $(this).find('.evaluation-status').length === 0 || 
                               (!$(this).find('.evaluation-status.completed').length && 
                                !$(this).find('.evaluation-status.draft').length);
                }
                
                if (hasStatus) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Export evaluations
        exportEvaluations: function() {
            if (!confirm(mt_jury_dashboard.i18n.confirm_export)) {
                return;
            }
            
            this.showNotification(mt_jury_dashboard.i18n.preparing_export, 'info');
            
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_export_evaluations',
                    nonce: mt_jury_dashboard.nonce
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(blob) {
                    // Create download link
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'my-evaluations-' + new Date().toISOString().slice(0, 10) + '.csv';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    MTJuryDashboard.showNotification(mt_jury_dashboard.i18n.export_complete, 'success');
                },
                error: function() {
                    MTJuryDashboard.showNotification(mt_jury_dashboard.i18n.export_error, 'error');
                }
            });
        },
        
        // Show notification
        showNotification: function(message, type) {
            var notification = $('<div class="mt-notification ' + type + '">' + message + '</div>');
            $('body').append(notification);
            
            setTimeout(function() {
                notification.addClass('show');
            }, 10);
            
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('.mt-jury-dashboard').length || $('#mt-jury-dashboard').length) {
            MTJuryDashboard.init();
        }
    });
    
})(jQuery); 