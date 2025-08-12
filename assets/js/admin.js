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
            this.initBulkActions();
        },
        
        bindEvents: function() {
            // Auto-assign button
            $('#mt-auto-assign-btn').on('click', (e) => {
                e.preventDefault();
                this.showAutoAssignModal();
            });
            
            // Manual assignment button
            $('#mt-manual-assign-btn').on('click', (e) => {
                e.preventDefault();
                this.showManualAssignModal();
            });
            
            // Bulk actions button
            $('#mt-bulk-actions-btn').on('click', (e) => {
                e.preventDefault();
                this.toggleBulkActions();
            });
            
            // Export button
            $('#mt-export-btn').on('click', (e) => {
                e.preventDefault();
                this.exportAssignments();
            });
            
            // Clear all button
            $('#mt-clear-all-btn').on('click', (e) => {
                e.preventDefault();
                this.clearAllAssignments();
            });
            
            // Remove individual assignment
            $(document).on('click', '.mt-remove-assignment', (e) => {
                e.preventDefault();
                this.removeAssignment($(e.currentTarget));
            });
            
            // Modal close buttons
            $('.mt-modal-close').on('click', (e) => {
                e.preventDefault();
                $('.mt-modal').fadeOut(300);
            });
            
            // Manual assignment form submission
            $('#mt-manual-assignment-form').on('submit', (e) => {
                e.preventDefault();
                this.submitManualAssignment();
            });
            
            // Auto-assignment form submission
            $('#mt-auto-assign-modal form').on('submit', (e) => {
                e.preventDefault();
                this.submitAutoAssignment();
            });
            
            // Filter handlers
            $('#mt-filter-jury, #mt-filter-status').on('change', () => {
                this.applyFilters();
            });
            
            // Search handler
            $('#mt-assignment-search').on('keyup', function() {
                const searchTerm = $(this).val();
                MTAssignmentManager.filterAssignments(searchTerm);
            });
        },
        
        initBulkActions: function() {
            // Select all checkbox
            $('#mt-select-all-assignments').on('change', function() {
                $('.mt-assignment-checkbox').prop('checked', $(this).prop('checked'));
            });
            
            // Apply bulk action button
            $('#mt-apply-bulk-action').on('click', (e) => {
                e.preventDefault();
                this.applyBulkAction();
            });
            
            // Cancel bulk action button
            $('#mt-cancel-bulk-action').on('click', (e) => {
                e.preventDefault();
                this.toggleBulkActions();
            });
        },
        
        showAutoAssignModal: function() {
            $('#mt-auto-assign-modal').fadeIn(300);
        },
        
        showManualAssignModal: function() {
            $('#mt-manual-assign-modal').fadeIn(300);
        },
        
        submitAutoAssignment: function() {
            const method = $('#assignment_method').val();
            const candidatesPerJury = $('#candidates_per_jury').val();
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_auto_assign',
                    nonce: mt_admin.nonce,
                    method: method,
                    candidates_per_jury: candidatesPerJury
                },
                beforeSend: () => {
                    $('#mt-auto-assign-modal button[type="submit"]').prop('disabled', true).text(mt_admin.i18n.processing || 'Processing...');
                },
                success: (response) => {
                    if (response.success) {
                        alert(response.data.message || mt_admin.i18n.assignments_created);
                        location.reload();
                    } else {
                        alert(response.data || mt_admin.i18n.error_occurred);
                    }
                },
                error: () => {
                    alert(mt_admin.i18n.error_occurred);
                },
                complete: () => {
                    $('#mt-auto-assign-modal button[type="submit"]').prop('disabled', false).text('Run Auto-Assignment');
                }
            });
        },
        
        submitManualAssignment: function() {
            const juryMemberId = $('#manual_jury_member').val();
            const candidateIds = [];
            
            $('input[name="candidate_ids[]"]:checked').each(function() {
                candidateIds.push($(this).val());
            });
            
            if (!juryMemberId || candidateIds.length === 0) {
                alert(mt_admin.i18n.select_jury_and_candidates);
                return;
            }
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_manual_assign',
                    nonce: mt_admin.nonce,
                    jury_member_id: juryMemberId,
                    candidate_ids: candidateIds
                },
                beforeSend: () => {
                    $('#mt-manual-assignment-form button[type="submit"]').prop('disabled', true).text(mt_admin.i18n.processing || 'Processing...');
                },
                success: (response) => {
                    if (response.success) {
                        alert(response.data.message || mt_admin.i18n.assignments_created);
                        location.reload();
                    } else {
                        alert(response.data || mt_admin.i18n.error_occurred);
                    }
                },
                error: () => {
                    alert(mt_admin.i18n.error_occurred);
                },
                complete: () => {
                    $('#mt-manual-assignment-form button[type="submit"]').prop('disabled', false).text(mt_admin.i18n.assign_selected);
                }
            });
        },
        
        removeAssignment: function($button) {
            const assignmentId = $button.data('assignment-id');
            const juryName = $button.data('jury');
            const candidateName = $button.data('candidate');
            
            if (!confirm(mt_admin.i18n.confirm_remove_assignment)) {
                return;
            }
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_remove_assignment',
                    nonce: mt_admin.nonce,
                    assignment_id: assignmentId
                },
                beforeSend: () => {
                    $button.prop('disabled', true).text(mt_admin.i18n.processing || 'Processing...');
                },
                success: (response) => {
                    if (response.success) {
                        $button.closest('tr').fadeOut(400, function() {
                            $(this).remove();
                            // Check if table is empty
                            if ($('.mt-assignments-table tbody tr').length === 0) {
                                $('.mt-assignments-table tbody').html('<tr><td colspan="8" class="no-items">' + mt_admin.i18n.no_assignments + '</td></tr>');
                            }
                        });
                        alert(mt_admin.i18n.assignment_removed);
                    } else {
                        alert(response.data || mt_admin.i18n.error_occurred);
                    }
                },
                error: () => {
                    alert(mt_admin.i18n.error_occurred);
                },
                complete: () => {
                    $button.prop('disabled', false).text('Remove');
                }
            });
        },
        
        clearAllAssignments: function() {
            if (!confirm(mt_admin.i18n.confirm_clear_all)) {
                return;
            }
            
            if (!confirm(mt_admin.i18n.confirm_clear_all_second)) {
                return;
            }
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_clear_all_assignments',
                    nonce: mt_admin.nonce
                },
                beforeSend: () => {
                    $('#mt-clear-all-btn').prop('disabled', true).text(mt_admin.i18n.clearing || 'Clearing...');
                },
                success: (response) => {
                    if (response.success) {
                        alert(mt_admin.i18n.all_assignments_cleared);
                        location.reload();
                    } else {
                        alert(response.data || mt_admin.i18n.error_occurred);
                    }
                },
                error: () => {
                    alert(mt_admin.i18n.error_occurred);
                },
                complete: () => {
                    $('#mt-clear-all-btn').prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> ' + mt_admin.i18n.clear_all);
                }
            });
        },
        
        exportAssignments: function() {
            // Create a form to trigger download
            const form = $('<form/>', {
                action: mt_admin.ajax_url,
                method: 'POST'
            });
            
            form.append($('<input/>', {
                type: 'hidden',
                name: 'action',
                value: 'mt_export_assignments'
            }));
            
            form.append($('<input/>', {
                type: 'hidden',
                name: 'nonce',
                value: mt_admin.nonce
            }));
            
            form.appendTo('body').submit().remove();
            
            alert(mt_admin.i18n.export_started);
        },
        
        toggleBulkActions: function() {
            const $container = $('#mt-bulk-actions-container');
            const $checkboxColumn = $('.check-column');
            
            if ($container.is(':visible')) {
                $container.slideUp();
                $checkboxColumn.hide();
                $('.mt-assignment-checkbox').prop('checked', false);
                $('#mt-select-all-assignments').prop('checked', false);
            } else {
                $container.slideDown();
                $checkboxColumn.show();
            }
        },
        
        applyBulkAction: function() {
            const action = $('#mt-bulk-action-select').val();
            const selectedIds = [];
            
            $('.mt-assignment-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (!action) {
                alert('Please select a bulk action');
                return;
            }
            
            if (selectedIds.length === 0) {
                alert('Please select at least one assignment');
                return;
            }
            
            if (action === 'remove') {
                this.bulkRemoveAssignments(selectedIds);
            } else if (action === 'export') {
                this.bulkExportAssignments(selectedIds);
            } else if (action === 'reassign') {
                this.showReassignModal(selectedIds);
            }
        },
        
        bulkRemoveAssignments: function(assignmentIds) {
            if (!confirm('Are you sure you want to remove the selected assignments?')) {
                return;
            }
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_bulk_remove_assignments',
                    nonce: mt_admin.nonce,
                    assignment_ids: assignmentIds
                },
                beforeSend: () => {
                    $('#mt-apply-bulk-action').prop('disabled', true).text('Processing...');
                },
                success: (response) => {
                    if (response.success) {
                        alert(response.data.message || 'Assignments removed successfully');
                        location.reload();
                    } else {
                        alert(response.data || 'An error occurred');
                    }
                },
                error: () => {
                    alert('An error occurred');
                },
                complete: () => {
                    $('#mt-apply-bulk-action').prop('disabled', false).text('Apply');
                }
            });
        },
        
        bulkExportAssignments: function(assignmentIds) {
            const form = $('<form/>', {
                action: mt_admin.ajax_url,
                method: 'POST'
            });
            
            form.append($('<input/>', {
                type: 'hidden',
                name: 'action',
                value: 'mt_bulk_export_assignments'
            }));
            
            form.append($('<input/>', {
                type: 'hidden',
                name: 'nonce',
                value: mt_admin.nonce
            }));
            
            assignmentIds.forEach(id => {
                form.append($('<input/>', {
                    type: 'hidden',
                    name: 'assignment_ids[]',
                    value: id
                }));
            });
            
            form.appendTo('body').submit().remove();
        },
        
        filterAssignments: function(searchTerm) {
            const rows = $('.mt-assignments-table tbody tr');
            
            if (!searchTerm) {
                rows.show();
                return;
            }
            
            const term = searchTerm.toLowerCase();
            
            rows.each(function() {
                const text = $(this).text().toLowerCase();
                if (text.includes(term)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        applyFilters: function() {
            const juryFilter = $('#mt-filter-jury').val();
            const statusFilter = $('#mt-filter-status').val();
            const rows = $('.mt-assignments-table tbody tr');
            
            rows.each(function() {
                let show = true;
                const $row = $(this);
                
                if (juryFilter) {
                    const juryId = $row.find('.mt-assignment-checkbox').data('jury-id');
                    if (juryId != juryFilter) {
                        show = false;
                    }
                }
                
                if (statusFilter && show) {
                    const status = $row.find('.mt-status').text().toLowerCase();
                    if (status !== statusFilter) {
                        show = false;
                    }
                }
                
                if (show) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        },
        
        showReassignModal: function(assignmentIds) {
            // Store the assignment IDs for later use
            this.pendingReassignments = assignmentIds;
            
            // Check if modal exists, if not create it
            if ($('#mt-reassign-modal').length === 0) {
                this.createReassignModal();
            }
            
            // Show the modal
            $('#mt-reassign-modal').fadeIn(300);
        },
        
        createReassignModal: function() {
            // Get jury members from the filter dropdown as a quick solution
            const juryOptions = $('#mt-filter-jury option').clone();
            
            const modalHtml = `
                <div id="mt-reassign-modal" class="mt-modal" style="display: none;">
                    <div class="mt-modal-content">
                        <h2>${mt_admin.i18n.reassign_assignments || 'Reassign Assignments'}</h2>
                        <p>${mt_admin.i18n.reassign_description || 'Select a new jury member to reassign the selected assignments to:'}</p>
                        <form id="mt-reassign-form">
                            <div class="mt-form-group">
                                <label for="reassign_jury_member">${mt_admin.i18n.new_jury_member || 'New Jury Member'}</label>
                                <select name="new_jury_member_id" id="reassign_jury_member" class="widefat" required>
                                    <option value="">${mt_admin.i18n.select_jury_member || 'Select Jury Member'}</option>
                                </select>
                            </div>
                            <div class="mt-modal-actions">
                                <button type="submit" class="button button-primary">${mt_admin.i18n.reassign || 'Reassign'}</button>
                                <button type="button" class="button mt-modal-close">${mt_admin.i18n.cancel || 'Cancel'}</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            // Append modal to body
            $('body').append(modalHtml);
            
            // Populate jury options
            $('#reassign_jury_member').html(juryOptions);
            
            // Bind close event
            $('#mt-reassign-modal .mt-modal-close').on('click', (e) => {
                e.preventDefault();
                $('#mt-reassign-modal').fadeOut(300);
            });
            
            // Bind form submit
            $('#mt-reassign-form').on('submit', (e) => {
                e.preventDefault();
                this.submitReassignment();
            });
        },
        
        submitReassignment: function() {
            const newJuryMemberId = $('#reassign_jury_member').val();
            
            if (!newJuryMemberId) {
                alert(mt_admin.i18n.select_jury_member || 'Please select a jury member');
                return;
            }
            
            if (!this.pendingReassignments || this.pendingReassignments.length === 0) {
                alert(mt_admin.i18n.no_assignments_selected || 'No assignments selected');
                return;
            }
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_bulk_reassign_assignments',
                    nonce: mt_admin.nonce,
                    assignment_ids: this.pendingReassignments,
                    new_jury_member_id: newJuryMemberId
                },
                beforeSend: () => {
                    $('#mt-reassign-form button[type="submit"]').prop('disabled', true).text(mt_admin.i18n.processing || 'Processing...');
                },
                success: (response) => {
                    if (response.success) {
                        alert(response.data.message || 'Assignments reassigned successfully');
                        $('#mt-reassign-modal').fadeOut(300);
                        location.reload();
                    } else {
                        alert(response.data || 'An error occurred');
                    }
                },
                error: () => {
                    alert(mt_admin.i18n.error_occurred || 'An error occurred');
                },
                complete: () => {
                    $('#mt-reassign-form button[type="submit"]').prop('disabled', false).text(mt_admin.i18n.reassign || 'Reassign');
                }
            });
        }
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