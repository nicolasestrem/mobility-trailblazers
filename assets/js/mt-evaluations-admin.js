/**
 * Mobility Trailblazers - Evaluations Admin JavaScript
 * 
 * Handles evaluation management in the admin interface including:
 * - View Details modal
 * - Delete evaluations
 * - Bulk operations
 * 
 * @package MobilityTrailblazers
 * @since 2.5.38
 */

(function($) {
    'use strict';

    /**
     * HTML escape function to prevent XSS attacks
     * Escapes HTML special characters in user-provided content
     * @param {string} text - The text to escape
     * @returns {string} - The escaped text safe for HTML insertion
     */
    function escapeHtml(text) {
        if (typeof text !== 'string') {
            return '';
        }
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
            '/': '&#x2F;',
            '`': '&#x60;',
            '=': '&#x3D;'
        };
        return text.replace(/[&<>"'`=\/]/g, function(char) {
            return map[char];
        });
    }

    /**
     * MT Evaluations Admin Module
     */
    window.MTEvaluations = {
        
        /**
         * Initialize the module
         */
        init: function() {
            this.bindEvents();
            this.setupBulkActions();
            console.log('MT Evaluations Admin initialized');
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // View Details buttons
            $(document).on('click', '.view-details', function(e) {
                e.preventDefault();
                var evaluationId = $(this).data('evaluation-id');
                self.showDetails(evaluationId);
            });
            
            // Delete evaluation from modal
            $(document).on('click', '.mt-delete-evaluation', function(e) {
                e.preventDefault();
                var evaluationId = $(this).data('id');
                if (confirm(mt_evaluations_i18n.confirm_delete || 'Are you sure you want to delete this evaluation?')) {
                    self.deleteEvaluation(evaluationId);
                }
            });
            
            // Close modal handlers
            $(document).on('click', '.mt-modal-close, .mt-modal-overlay', function(e) {
                if ($(e.target).hasClass('mt-modal-close') || $(e.target).hasClass('mt-modal-overlay')) {
                    self.closeModal();
                }
            });
            
            // ESC key to close modal
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('#mt-evaluation-modal').length) {
                    self.closeModal();
                }
            });
        },
        
        /**
         * Setup bulk actions
         */
        setupBulkActions: function() {
            var self = this;
            
            $('#doaction, #doaction2').on('click', function(e) {
                var $button = $(this);
                var action = $button.prev('select').val();
                
                if (action === '-1') {
                    return;
                }
                
                e.preventDefault();
                
                var selected = [];
                $('input[name="evaluation[]"]:checked').each(function() {
                    selected.push($(this).val());
                });
                
                if (selected.length === 0) {
                    alert(mt_evaluations_i18n.no_selection || 'Please select at least one evaluation.');
                    return;
                }
                
                if (action === 'delete') {
                    if (!confirm(mt_evaluations_i18n.confirm_bulk_delete || 'Are you sure you want to delete the selected evaluations?')) {
                        return;
                    }
                }
                
                self.bulkAction(action, selected);
            });
            
            // Check all checkbox
            $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
                var isChecked = $(this).prop('checked');
                $('input[name="evaluation[]"]').prop('checked', isChecked);
                $('#cb-select-all-1, #cb-select-all-2').prop('checked', isChecked);
            });
        },
        
        /**
         * Show evaluation details in modal
         */
        showDetails: function(evaluationId) {
            var self = this;
            
            // Show loading state
            self.showLoading();
            
            // Fetch evaluation details via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mt_get_evaluation_details',
                    evaluation_id: evaluationId,
                    nonce: mt_evaluations_vars.nonce || $('#_wpnonce').val()
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.displayModal(response.data);
                    } else {
                        self.closeModal();
                        alert(response.data || 'Failed to load evaluation details.');
                    }
                },
                error: function() {
                    self.closeModal();
                    alert('Error loading evaluation details.');
                }
            });
        },
        
        /**
         * Display evaluation details modal
         */
        displayModal: function(data) {
            // Remove any existing modal
            $('#mt-evaluation-modal-wrapper').remove();
            
            // Build scores HTML
            var scoresHtml = '';
            if (data.scores) {
                $.each(data.scores, function(key, score) {
                    var percentage = (score.value / 10) * 100;
                    scoresHtml += `
                        <div class="mt-score-row">
                            <div class="mt-score-label">${escapeHtml(score.label)}</div>
                            <div class="mt-score-bar">
                                <div class="mt-score-fill" style="width: ${escapeHtml(String(percentage))}%"></div>
                            </div>
                            <div class="mt-score-value">${escapeHtml(score.value.toFixed(1))}</div>
                        </div>
                    `;
                });
            }
            
            // Build modal HTML
            var modalHtml = `
                <div id="mt-evaluation-modal-wrapper" class="mt-modal-wrapper">
                    <div class="mt-modal-overlay"></div>
                    <div id="mt-evaluation-modal" class="mt-modal">
                        <div class="mt-modal-header">
                            <h2>${escapeHtml(mt_evaluations_i18n.evaluation_details || 'Evaluation Details')} #${escapeHtml(String(data.id))}</h2>
                            <button type="button" class="mt-modal-close" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="mt-modal-body">
                            <div class="mt-evaluation-info">
                                <table class="widefat striped">
                                    <tbody>
                                        <tr>
                                            <th>${mt_evaluations_i18n.jury_member || 'Jury Member'}:</th>
                                            <td>${escapeHtml(data.jury_member)}</td>
                                        </tr>
                                        <tr>
                                            <th>${mt_evaluations_i18n.candidate || 'Candidate'}:</th>
                                            <td>${escapeHtml(data.candidate)}</td>
                                        </tr>
                                        ${data.organization ? `
                                        <tr>
                                            <th>${mt_evaluations_i18n.organization || 'Organization'}:</th>
                                            <td>${escapeHtml(data.organization)}</td>
                                        </tr>` : ''}
                                        ${data.categories ? `
                                        <tr>
                                            <th>${mt_evaluations_i18n.categories || 'Categories'}:</th>
                                            <td>${escapeHtml(data.categories)}</td>
                                        </tr>` : ''}
                                        <tr>
                                            <th>${mt_evaluations_i18n.status || 'Status'}:</th>
                                            <td><span class="mt-status mt-status-${escapeHtml(data.status)}">${escapeHtml(data.status)}</span></td>
                                        </tr>
                                        <tr>
                                            <th>${mt_evaluations_i18n.created || 'Created'}:</th>
                                            <td>${escapeHtml(data.created_at)}</td>
                                        </tr>
                                        <tr>
                                            <th>${mt_evaluations_i18n.updated || 'Last Updated'}:</th>
                                            <td>${escapeHtml(data.updated_at)}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-evaluation-scores">
                                <h3>${escapeHtml(mt_evaluations_i18n.scores || 'Evaluation Scores')}</h3>
                                ${scoresHtml}
                                <div class="mt-score-summary">
                                    <div class="mt-total-score">
                                        <strong>${escapeHtml(mt_evaluations_i18n.total_score || 'Total Score')}:</strong>
                                        <span class="mt-score-total">${escapeHtml(data.total_score.toFixed(1))} / 50</span>
                                    </div>
                                    <div class="mt-average-score">
                                        <strong>${escapeHtml(mt_evaluations_i18n.average_score || 'Average')}:</strong>
                                        <span class="mt-score-average">${escapeHtml(data.average_score.toFixed(2))}</span>
                                    </div>
                                </div>
                            </div>
                            
                            ${data.comments ? `
                            <div class="mt-evaluation-comments">
                                <h3>${escapeHtml(mt_evaluations_i18n.comments || 'Comments')}</h3>
                                <div class="mt-comments-text">${escapeHtml(data.comments)}</div>
                            </div>` : ''}
                        </div>
                        <div class="mt-modal-footer">
                            <button type="button" class="button button-primary mt-modal-close">
                                ${escapeHtml(mt_evaluations_i18n.close || 'Close')}
                            </button>
                            <button type="button" class="button button-link-delete mt-delete-evaluation" data-id="${data.id}">
                                ${mt_evaluations_i18n.delete || 'Delete Evaluation'}
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to body
            $('body').append(modalHtml);
            
            // Trigger custom event
            $(document).trigger('mt:evaluation:modal:opened', [data]);
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            var loadingHtml = `
                <div id="mt-evaluation-modal-wrapper" class="mt-modal-wrapper">
                    <div class="mt-modal-overlay"></div>
                    <div class="mt-modal-loading">
                        <div class="spinner is-active"></div>
                        <p>${mt_evaluations_i18n.loading || 'Loading...'}</p>
                    </div>
                </div>
            `;
            $('body').append(loadingHtml);
        },
        
        /**
         * Close modal
         */
        closeModal: function() {
            $('#mt-evaluation-modal-wrapper').fadeOut(200, function() {
                $(this).remove();
            });
            $(document).trigger('mt:evaluation:modal:closed');
        },
        
        /**
         * Delete single evaluation
         */
        deleteEvaluation: function(evaluationId) {
            var self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mt_delete_evaluation',
                    evaluation_id: evaluationId,
                    nonce: mt_evaluations_vars.nonce || $('#_wpnonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Close modal if open
                        self.closeModal();
                        
                        // Remove row from table
                        $('input[value="' + evaluationId + '"][name="evaluation[]"]')
                            .closest('tr')
                            .fadeOut(400, function() {
                                $(this).remove();
                                
                                // Check if table is empty
                                if ($('input[name="evaluation[]"]').length === 0) {
                                    location.reload();
                                }
                            });
                        
                        // Show success message
                        self.showNotice(response.data.message || 'Evaluation deleted successfully', 'success');
                    } else {
                        alert(response.data || 'Failed to delete evaluation');
                    }
                },
                error: function() {
                    alert('Error deleting evaluation');
                }
            });
        },
        
        /**
         * Perform bulk action
         */
        bulkAction: function(action, evaluationIds) {
            var self = this;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mt_bulk_evaluation_action',
                    bulk_action: action,
                    evaluation_ids: evaluationIds,
                    nonce: mt_evaluations_vars.nonce || $('#_wpnonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show updated data
                        location.reload();
                    } else {
                        alert(response.data || 'Failed to perform bulk action');
                    }
                },
                error: function() {
                    alert('Error performing bulk action');
                }
            });
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            var noticeHtml = `
                <div class="notice notice-${type} is-dismissible mt-admin-notice">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `;
            
            // Remove any existing notices
            $('.mt-admin-notice').remove();
            
            // Add new notice after page title
            $('.wrap > h1').after(noticeHtml);
            
            // Auto dismiss after 5 seconds
            setTimeout(function() {
                $('.mt-admin-notice').fadeOut(400, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Handle dismiss button
            $('.mt-admin-notice .notice-dismiss').on('click', function() {
                $(this).closest('.notice').fadeOut(400, function() {
                    $(this).remove();
                });
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Only initialize on evaluations page
        if ($('.view-details[data-evaluation-id]').length > 0 || 
            window.location.href.includes('page=mt-evaluations')) {
            MTEvaluations.init();
        }
    });
    
})(jQuery);