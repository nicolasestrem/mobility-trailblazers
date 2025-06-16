/**
 * Mobility Trailblazers - Jury Evaluation JavaScript
 * File: /wp-content/plugins/mobility-trailblazers/assets/jury-evaluation.js
 * 
 * Handles all interactions for the jury evaluation interface
 */

(function($) {
    'use strict';
    
    // ===================================
    // State Management
    // ===================================
    const JuryDashboard = {
        state: {
            currentFilter: 'all',
            currentCandidateId: null,
            evaluationData: {},
            isSubmitting: false,
            autoSaveTimer: null
        },
        
        config: {
            autoSaveDelay: 30000, // 30 seconds
            notificationDuration: 5000,
            animationDuration: 300,
            debounceDelay: 300
        },
        
        // ===================================
        // Initialization
        // ===================================
        init: function() {
            this.bindEvents();
            this.initializeSliders();
            this.setupKeyboardShortcuts();
            this.checkForDrafts();
            this.animateProgressOnLoad();
        },
        
        // ===================================
        // Event Binding
        // ===================================
        bindEvents: function() {
            // Filter buttons
            $('.mt-action-button').on('click', this.handleFilterClick.bind(this));
            
            // Search functionality
            $('#mt-candidate-search').on('keyup', this.debounce(this.handleSearch.bind(this), this.config.debounceDelay));
            
            // Evaluation buttons
            $(document).on('click', '.mt-evaluate-button', this.handleEvaluateClick.bind(this));
            
            // Modal controls
            $('#mt-close-modal, .mt-modal-backdrop').on('click', this.closeEvaluationModal.bind(this));
            $('.mt-modal-content').on('click', function(e) { e.stopPropagation(); });
            
            // Form submission
            $('#mt-evaluation-form').on('submit', this.handleFormSubmit.bind(this));
            
            // Save draft
            $('#mt-save-draft').on('click', this.saveDraft.bind(this));
            
            // Export
            $('#mt-export-evaluations').on('click', this.exportEvaluations.bind(this));
            
            // Slider updates
            $('.mt-score-slider').on('input', this.handleSliderChange.bind(this));
            
            // Auto-save on input
            $('#mt-comments').on('input', this.triggerAutoSave.bind(this));
        },
        
        // ===================================
        // Filter Functionality
        // ===================================
        handleFilterClick: function(e) {
            const $button = $(e.currentTarget);
            const filterId = $button.attr('id');
            
            // Update active state
            $('.mt-action-button').removeClass('active');
            $button.addClass('active');
            
            // Store current filter
            this.state.currentFilter = filterId.replace('mt-filter-', '');
            
            // Apply filter
            this.applyFilter();
            
            // Animate cards
            this.animateVisibleCards();
        },
        
        applyFilter: function() {
            const filter = this.state.currentFilter;
            const $cards = $('.mt-candidate-card');
            
            if (filter === 'all') {
                $cards.show();
            } else if (filter === 'pending') {
                $cards.hide();
                $cards.filter('.pending').show();
            } else if (filter === 'evaluated') {
                $cards.hide();
                $cards.filter('.evaluated').show();
            }
            
            // Update count
            this.updateFilterCount();
        },
        
        updateFilterCount: function() {
            const visibleCount = $('.mt-candidate-card:visible').length;
            const totalCount = $('.mt-candidate-card').length;
            
            // Could add a count display to the UI here
            console.log(`Showing ${visibleCount} of ${totalCount} candidates`);
        },
        
        animateVisibleCards: function() {
            $('.mt-candidate-card:visible').each(function(index) {
                $(this).css({
                    'animation': 'none',
                    'opacity': '0',
                    'transform': 'translateY(20px)'
                });
                
                setTimeout(() => {
                    $(this).css({
                        'animation': 'fadeInUp 0.5s ease forwards',
                        'opacity': '1',
                        'transform': 'translateY(0)'
                    });
                }, index * 50);
            });
        },
        
        // ===================================
        // Search Functionality
        // ===================================
        handleSearch: function(e) {
            const searchTerm = $(e.target).val().toLowerCase();
            
            $('.mt-candidate-card').each(function() {
                const $card = $(this);
                const candidateName = $card.data('candidate-name').toLowerCase();
                const isVisible = candidateName.includes(searchTerm);
                
                if (isVisible && $card.is(':hidden')) {
                    $card.fadeIn(300);
                } else if (!isVisible && $card.is(':visible')) {
                    $card.fadeOut(300);
                }
            });
            
            // Update count after search
            setTimeout(() => this.updateFilterCount(), 350);
        },
        
        // ===================================
        // Evaluation Modal
        // ===================================
        handleEvaluateClick: function(e) {
            e.preventDefault();
            const candidateId = $(e.currentTarget).data('candidate-id');
            this.openEvaluationModal(candidateId);
        },
        
        openEvaluationModal: function(candidateId) {
            this.state.currentCandidateId = candidateId;
            
            // Find candidate card and extract info
            const $card = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
            const candidateName = $card.find('h3').text();
            const position = $card.find('.mt-candidate-position').text();
            const company = $card.find('.mt-candidate-company').text();
            const location = $card.find('.mt-candidate-location').text();
            
            // Update modal with candidate info
            $('#mt-candidate-id').val(candidateId);
            $('#mt-modal-candidate-info').html(`
                <h3>${this.escapeHtml(candidateName)}</h3>
                ${position ? `<p>${this.escapeHtml(position)}</p>` : ''}
                ${company ? `<p>${this.escapeHtml(company)}</p>` : ''}
                ${location ? `<p>📍 ${this.escapeHtml(location)}</p>` : ''}
            `);
            
            // Load existing evaluation if any
            if ($card.hasClass('evaluated')) {
                this.loadExistingEvaluation(candidateId);
            } else {
                this.resetEvaluationForm();
            }
            
            // Show modal with animation
            $('#mt-evaluation-modal').addClass('show');
            $('body').css('overflow', 'hidden');
            
            // Focus first slider
            setTimeout(() => {
                $('#mt-courage').focus();
            }, 300);
        },
        
        closeEvaluationModal: function() {
            const $modal = $('#mt-evaluation-modal');
            
            // Check for unsaved changes
            if (this.hasUnsavedChanges()) {
                if (!confirm(mt_jury_dashboard.i18n.unsaved_changes)) {
                    return;
                }
            }
            
            // Hide modal
            $modal.removeClass('show');
            $('body').css('overflow', '');
            
            // Clear auto-save timer
            if (this.state.autoSaveTimer) {
                clearTimeout(this.state.autoSaveTimer);
            }
            
            // Reset form after animation
            setTimeout(() => {
                this.resetEvaluationForm();
            }, 300);
        },
        
        // ===================================
        // Evaluation Form
        // ===================================
        loadExistingEvaluation: function(candidateId) {
            this.showNotification(mt_jury_dashboard.i18n.loading_evaluation, 'info');
            
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_evaluation',
                    candidate_id: candidateId,
                    nonce: mt_jury_dashboard.nonce
                },
                success: (response) => {
                    if (response.success && response.data && response.data.evaluation) {
                        const evaluation = response.data.evaluation;
                        
                        // Set slider values - handle both database field names and draft field names
                        $('#mt-courage').val(evaluation.courage_score || evaluation.courage || 5).trigger('input');
                        $('#mt-innovation').val(evaluation.innovation_score || evaluation.innovation || 5).trigger('input');
                        $('#mt-implementation').val(evaluation.implementation_score || evaluation.implementation || 5).trigger('input');
                        $('#mt-relevance').val(evaluation.relevance_score || evaluation.relevance || 5).trigger('input');
                        $('#mt-visibility').val(evaluation.visibility_score || evaluation.visibility || 5).trigger('input');
                        
                        // Set comments
                        $('#mt-comments').val(evaluation.comments || '');
                        
                        this.showNotification(mt_jury_dashboard.i18n.evaluation_loaded, 'success');
                    }
                },
                error: () => {
                    this.showNotification(mt_jury_dashboard.i18n.error_loading, 'error');
                }
            });
        },
        
        resetEvaluationForm: function() {
            $('#mt-evaluation-form')[0].reset();
            $('.mt-score-slider').val(5).trigger('input');
            $('#mt-comments').val('');
        },
        
        handleSliderChange: function(e) {
            const $slider = $(e.target);
            const value = parseFloat($slider.val());
            const displayId = $slider.attr('id') + '-display';
            
            // Update display value
            $('#' + displayId).text(value.toFixed(1));
            
            // Update slider gradient
            const percentage = (value / 10) * 100;
            $slider.css('background', `linear-gradient(to right, var(--mt-accent-500) 0%, var(--mt-accent-500) ${percentage}%, var(--mt-gray-300) ${percentage}%, var(--mt-gray-300) 100%)`);
            
            // Update total score
            this.updateTotalScore();
            
            // Trigger auto-save
            this.triggerAutoSave();
        },
        
        updateTotalScore: function() {
            let total = 0;
            $('.mt-score-slider').each(function() {
                total += parseFloat($(this).val());
            });
            
            const $totalDisplay = $('#mt-total-score');
            $totalDisplay.text(total.toFixed(1));
            
            // Add animation
            $totalDisplay.addClass('score-update');
            setTimeout(() => $totalDisplay.removeClass('score-update'), 300);
            
            // Add color coding based on score
            $totalDisplay.removeClass('low medium high');
            if (total < 20) {
                $totalDisplay.addClass('low');
            } else if (total < 35) {
                $totalDisplay.addClass('medium');
            } else {
                $totalDisplay.addClass('high');
            }
        },
        
        // ===================================
        // Form Submission
        // ===================================
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            if (this.state.isSubmitting) {
                return;
            }
            
            const $form = $('#mt-evaluation-form');
            const $submitBtn = $('#mt-submit-evaluation');
            
            // Validate form
            if (!this.validateForm()) {
                return;
            }
            
            // Set submitting state
            this.state.isSubmitting = true;
            
            // Update button state
            $submitBtn.prop('disabled', true).html(`
                <svg class="mt-spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" opacity="0.3"></circle>
                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                </svg>
                ${mt_jury_dashboard.i18n.submitting}
            `);
            
            // Prepare form data
            const formData = new FormData($form[0]);
            formData.append('action', 'mt_submit_evaluation');
            
            // Submit via AJAX
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        this.handleSubmitSuccess();
                    } else {
                        this.showNotification(response.data || mt_jury_dashboard.i18n.error_submitting, 'error');
                    }
                },
                error: () => {
                    this.showNotification(mt_jury_dashboard.i18n.network_error, 'error');
                },
                complete: () => {
                    this.state.isSubmitting = false;
                    
                    // Reset button
                    $submitBtn.prop('disabled', false).html(`
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        ${mt_jury_dashboard.i18n.submit_evaluation}
                    `);
                }
            });
        },
        
        handleSubmitSuccess: function() {
            this.showNotification(mt_jury_dashboard.i18n.evaluation_submitted, 'success');
            
            // Update candidate card
            const $card = $(`.mt-candidate-card[data-candidate-id="${this.state.currentCandidateId}"]`);
            
            // Calculate total score
            let totalScore = 0;
            $('.mt-score-slider').each(function() {
                totalScore += parseFloat($(this).val());
            });
            
            // Update card status
            $card.removeClass('pending').addClass('evaluated');
            
            // Update status badge
            $card.find('.mt-candidate-status').html(`
                <span class="mt-status-badge mt-status-evaluated">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    ${mt_jury_dashboard.i18n.evaluated}
                </span>
                <span class="mt-score-badge">${totalScore.toFixed(1)}/50</span>
            `);
            
            // Update progress
            this.updateProgress();
            
            // Close modal after delay
            setTimeout(() => {
                this.closeEvaluationModal();
            }, 1500);
        },
        
        validateForm: function() {
            // Check if all sliders have been moved from default
            let allDefault = true;
            $('.mt-score-slider').each(function() {
                if ($(this).val() !== '5') {
                    allDefault = false;
                    return false;
                }
            });
            
            if (allDefault) {
                this.showNotification(mt_jury_dashboard.i18n.please_rate_all, 'error');
                return false;
            }
            
            return true;
        },
        
        // ===================================
        // Save Draft
        // ===================================
        saveDraft: function() {
            const $saveBtn = $('#mt-save-draft');
            
            // Show saving state
            $saveBtn.prop('disabled', true).html(`
                <svg class="mt-spinner" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10" opacity="0.3"></circle>
                    <path d="M12 2a10 10 0 0 1 10 10" stroke-linecap="round"></path>
                </svg>
                ${mt_jury_dashboard.i18n.saving}
            `);
            
            // Prepare data
            const data = {
                action: 'mt_save_draft',
                candidate_id: $('#mt-candidate-id').val(),
                courage: $('#mt-courage').val(),
                innovation: $('#mt-innovation').val(),
                implementation: $('#mt-implementation').val(),
                relevance: $('#mt-relevance').val(),
                visibility: $('#mt-visibility').val(),
                comments: $('#mt-comments').val(),
                nonce: mt_jury_dashboard.nonce
            };
            
            // Save via AJAX
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.showNotification(mt_jury_dashboard.i18n.draft_saved, 'success');
                        
                        // Mark as draft in UI
                        this.markAsDraft(this.state.currentCandidateId);
                    } else {
                        this.showNotification(response.data || mt_jury_dashboard.i18n.error_saving, 'error');
                    }
                },
                error: () => {
                    this.showNotification(mt_jury_dashboard.i18n.network_error, 'error');
                },
                complete: () => {
                    // Reset button
                    $saveBtn.prop('disabled', false).html(`
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        ${mt_jury_dashboard.i18n.save_draft}
                    `);
                }
            });
        },
        
        markAsDraft: function(candidateId) {
            const $card = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
            
            // Add draft indicator if not already evaluated
            if (!$card.hasClass('evaluated')) {
                $card.addClass('has-draft');
                // Could add a draft badge here
            }
        },
        
        // ===================================
        // Auto-save
        // ===================================
        triggerAutoSave: function() {
            // Clear existing timer
            if (this.state.autoSaveTimer) {
                clearTimeout(this.state.autoSaveTimer);
            }
            
            // Set new timer
            this.state.autoSaveTimer = setTimeout(() => {
                if (this.hasUnsavedChanges()) {
                    this.saveDraft();
                }
            }, this.config.autoSaveDelay);
        },
        
        hasUnsavedChanges: function() {
            // Check if any slider has been moved or comments added
            let hasChanges = false;
            
            $('.mt-score-slider').each(function() {
                if ($(this).val() !== '5') {
                    hasChanges = true;
                    return false;
                }
            });
            
            if ($('#mt-comments').val().trim() !== '') {
                hasChanges = true;
            }
            
            return hasChanges;
        },
        
        checkForDrafts: function() {
            // Check for saved drafts on page load
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_drafts',
                    nonce: mt_jury_dashboard.nonce
                },
                success: (response) => {
                    if (response.success && response.data) {
                        response.data.forEach(candidateId => {
                            this.markAsDraft(candidateId);
                        });
                    }
                }
            });
        },
        
        // ===================================
        // Progress Update
        // ===================================
        updateProgress: function() {
            const totalCards = $('.mt-candidate-card').length;
            const evaluatedCards = $('.mt-candidate-card.evaluated').length;
            const percentage = totalCards > 0 ? Math.round((evaluatedCards / totalCards) * 100) : 0;
            
            // Update progress display
            $('.mt-progress-percentage').text(percentage + '%');
            $('.mt-progress-fill').css('width', percentage + '%');
            
            // Update stats
            $('.mt-stat-number').eq(0).text(evaluatedCards);
            $('.mt-stat-number').eq(1).text(totalCards - evaluatedCards);
            
            // Add celebration if complete
            if (percentage === 100) {
                this.celebrateCompletion();
            }
        },
        
        celebrateCompletion: function() {
            $('.mt-jury-progress-card').addClass('complete');
            this.showNotification(mt_jury_dashboard.i18n.all_complete, 'success');
            
            // Trigger confetti or other celebration effect
            if (typeof confetti !== 'undefined') {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });
            }
        },
        
        animateProgressOnLoad: function() {
            // Animate progress bar on page load
            const $progressFill = $('.mt-progress-fill');
            const targetWidth = $progressFill.css('width');
            
            $progressFill.css('width', '0');
            setTimeout(() => {
                $progressFill.css('width', targetWidth);
            }, 100);
        },
        
        // ===================================
        // Export Functionality
        // ===================================
        exportEvaluations: function() {
            this.showNotification(mt_jury_dashboard.i18n.preparing_export, 'info');
            
            // Request export
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
                success: (blob) => {
                    // Create download link
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `evaluations-${new Date().toISOString().split('T')[0]}.csv`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    this.showNotification(mt_jury_dashboard.i18n.export_complete, 'success');
                },
                error: () => {
                    this.showNotification(mt_jury_dashboard.i18n.export_error, 'error');
                }
            });
        },
        
        // ===================================
        // Notifications
        // ===================================
        showNotification: function(message, type = 'info') {
            const icons = {
                success: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                error: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
                info: '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
            };
            
            const $notification = $(`
                <div class="mt-notification ${type}">
                    ${icons[type]}
                    <span>${message}</span>
                </div>
            `);
            
            $('#mt-notification-container').append($notification);
            
            // Auto remove
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, this.config.notificationDuration);
        },
        
        // ===================================
        // Keyboard Shortcuts
        // ===================================
        setupKeyboardShortcuts: function() {
            $(document).on('keydown', (e) => {
                // ESC to close modal
                if (e.key === 'Escape' && $('#mt-evaluation-modal').hasClass('show')) {
                    this.closeEvaluationModal();
                }
                
                // Ctrl+S to save draft
                if ((e.ctrlKey || e.metaKey) && e.key === 's' && $('#mt-evaluation-modal').hasClass('show')) {
                    e.preventDefault();
                    this.saveDraft();
                }
                
                // Ctrl+Enter to submit
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && $('#mt-evaluation-modal').hasClass('show')) {
                    e.preventDefault();
                    $('#mt-evaluation-form').submit();
                }
            });
        },
        
        // ===================================
        // Helper Functions
        // ===================================
        initializeSliders: function() {
            $('.mt-score-slider').each(function() {
                $(this).trigger('input');
            });
        },
        
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },
        
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }
    };
    
    // ===================================
    // Initialize on DOM Ready
    // ===================================
    $(document).ready(function() {
        // Check if we're on the jury dashboard page
        if ($('.mt-jury-dashboard-container').length) {
            JuryDashboard.init();
            
            // Make available globally for debugging
            window.MTJuryDashboard = JuryDashboard;
        }
    });
    
})(jQuery);