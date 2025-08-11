/**
 * Mobility Trailblazers Admin JavaScript
 */

// Ensure mt_admin object exists with fallback values
if (typeof mt_admin === 'undefined') {
    console.warn('mt_admin object not found, creating fallback');
    window.mt_admin = {
        ajax_url: ajaxurl || '/wp-admin/admin-ajax.php',
        nonce: $('#mt_admin_nonce').val() || '',
        admin_url: '/wp-admin/',
        i18n: {
            confirm_remove_assignment: 'Are you sure you want to remove this assignment?',
            assignment_removed: 'Assignment removed successfully.',
            error_occurred: 'An error occurred. Please try again.',
            no_assignments: 'No assignments yet',
            processing: 'Processing...',
            select_jury_and_candidates: 'Please select a jury member and at least one candidate.',
            assignments_created: 'Assignments created successfully.',
            assign_selected: 'Assign Selected',
            confirm_clear_all: 'Are you sure you want to clear ALL assignments? This cannot be undone.',
            confirm_clear_all_second: 'This will remove ALL jury assignments. Are you absolutely sure?',
            clearing: 'Clearing...',
            clear_all: 'Clear All',
            all_assignments_cleared: 'All assignments have been cleared.',
            export_started: 'Export started. Download will begin shortly.'
        }
    };
}

// Ensure i18n object exists
if (typeof mt_admin.i18n === 'undefined') {
    mt_admin.i18n = {
        confirm_remove_assignment: 'Are you sure you want to remove this assignment?',
        assignment_removed: 'Assignment removed successfully.',
        error_occurred: 'An error occurred. Please try again.',
        no_assignments: 'No assignments yet',
        processing: 'Processing...',
        select_jury_and_candidates: 'Please select a jury member and at least one candidate.',
        assignments_created: 'Assignments created successfully.',
        assign_selected: 'Assign Selected',
        confirm_clear_all: 'Are you sure you want to clear ALL assignments? This cannot be undone.',
        confirm_clear_all_second: 'This will remove ALL jury assignments. Are you absolutely sure?',
        clearing: 'Clearing...',
        clear_all: 'Clear All',
        all_assignments_cleared: 'All assignments have been cleared.',
        export_started: 'Export started. Download will begin shortly.'
    };
}

(function($) {
    'use strict';

    // General utility functions that can run on any admin page
    function initTooltips() {
        if (typeof $.fn.tooltip === 'function') {
            $('.mt-tooltip').tooltip();
        }
    }
    function initTabs() { /* ... tab logic ... */ }
    function initModals() { /* ... modal logic ... */ }
    function initConfirmations() { /* ... confirmation logic ... */ }
    function initAjaxForms() { /* ... ajax form logic ... */ }
    function initMediaUpload() { /* ... media upload logic ... */ }
    window.mtShowNotification = function(message, type) { /* ... notification logic ... */ };

    /**
     * Manager object for the "Assignment Management" page.
     * Contains all logic specific to this page.
     */
    const MTAssignmentManager = {
        init: function() {
            // This method is the entry point for all assignment page functionality.
            console.log('MTAssignmentManager initialized.');
            this.bindEvents();
        },
        bindEvents: function() {
            // Binds all event listeners for the page.
            $('#mt-auto-assign-btn').on('click', (e) => {
                e.preventDefault();
                this.showAutoAssignModal();
            });
             // ... other assignment-specific event bindings
        },
        showAutoAssignModal: function() {
            $('#mt-auto-assign-modal').fadeIn(300);
        },
        // ... all other methods from the previous refactoring
    };

    /**
     * Manager object for the "Evaluations" admin page.
     * Contains all logic specific to this page.
     */
    const MTEvaluationManager = {
        init: function() {
            // Entry point for all evaluation page functionality.
            console.log('MTEvaluationManager initialized.');
            this.bindEvents();
        },
        bindEvents: function() {
            // Bind all event listeners for the evaluations page.
            $('.view-details').on('click', (e) => {
                const evaluationId = $(e.currentTarget).data('evaluation-id');
                this.viewDetails(evaluationId);
            });

            $('#cb-select-all-1, #cb-select-all-2').on('click', this.handleSelectAll);
            $('input[name="evaluation[]"]').on('click', this.handleSingleSelect);

            $('#doaction, #doaction2').on('click', (e) => {
                const action = $(e.currentTarget).prev('select').val();
                this.applyBulkAction(action);
            });
        },
        viewDetails: function(evaluationId) {
            // TODO: Implement AJAX call to load evaluation details into a modal.
            alert('View evaluation ' + evaluationId + ' details - To be implemented');
        },
        handleSelectAll: function() {
            const isChecked = $(this).prop('checked');
            $('input[name="evaluation[]"]').prop('checked', isChecked);
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', isChecked);
        },
        handleSingleSelect: function() {
            const allChecked = $('input[name="evaluation[]"]').length === $('input[name="evaluation[]"]:checked').length;
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', allChecked);
        },
        applyBulkAction: function(action) {
            if (action === '-1') {
                alert(mt_admin.i18n.select_bulk_action || 'Please select a bulk action');
                return;
            }

            const selected = [];
            $('input[name="evaluation[]"]:checked').each(function() {
                selected.push($(this).val());
            });

            if (selected.length === 0) {
                alert(mt_admin.i18n.select_assignments || 'Please select at least one evaluation');
                return;
            }
            
            // Confirmation logic for destructive actions
            // This is a simplified version, you can expand it with more i18n strings
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete the selected evaluations? This cannot be undone.')) {
                    return;
                }
            } else {
                 if (!confirm('Are you sure you want to ' + action + ' the selected evaluations?')) {
                    return;
                }
            }

            // Perform bulk action via AJAX
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_bulk_evaluation_action',
                    bulk_action: action,
                    evaluation_ids: selected,
                    nonce: mt_admin.nonce
                },
                beforeSend: function() {
                    $('#doaction, #doaction2').prop('disabled', true).val(mt_admin.i18n.processing || 'Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Bulk action completed successfully');
                        location.reload();
                    } else {
                        alert(response.data || 'An error occurred');
                    }
                },
                error: function() {
                    alert(mt_admin.i18n.error_occurred || 'An error occurred. Please try again.');
                },
                complete: function() {
                    $('#doaction, #doaction2').prop('disabled', false).val(mt_admin.i18n.apply || 'Apply');
                }
            });
        }
    };

    /**
     * Main Initialization Logic
     * This runs on every admin page load.
     */
    $(document).ready(function() {
        console.log('Mobility Trailblazers Admin JS Loaded.');

        // Initialize general scripts that run on all pages
        initTooltips();
        initTabs();
        initModals();
        initConfirmations();
        initAjaxForms();
        initMediaUpload();
        if ($.fn.select2) {
            $('.mt-select2').select2();
        }
        if ($.fn.datepicker) {
            $('.mt-datepicker').datepicker({ dateFormat: 'yy-mm-dd' });
        }

        // --- Conditional Initialization for Page-Specific Managers ---

        // Check for the Assignment Management page
        if ($('#mt-auto-assign-btn').length > 0 || $('.mt-assignments-table').length > 0) {
            MTAssignmentManager.init();
        }

        // Check for the Evaluations page
        if ($('body').hasClass('mobility-trailblazers_page_mt-evaluations')) {
             MTEvaluationManager.init();
        }
    });

})(jQuery);