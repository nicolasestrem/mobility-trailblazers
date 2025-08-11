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

    console.log('Admin JS loading...');
    
    /**
     * Initialize tooltips
     */
    function initTooltips() {
        // Check if tooltip function exists before calling it
        if (typeof $.fn.tooltip === 'function') {
            $('.mt-tooltip').tooltip();
        } else {
            console.log('jQuery tooltip plugin not available, skipping tooltip initialization');
        }
    }
    
    /**
     * Initialize tabs
     */
    function initTabs() {
        $('.mt-tabs').on('click', '.mt-tab-link', function(e) {
            e.preventDefault();
            
            const $this = $(this);
            const target = $this.attr('href');
            const $tabContent = $(target);
            
            // Hide all tab contents
            $('.mt-tab-content').removeClass('active');
            
            // Remove active class from all tab links
            $('.mt-tab-link').removeClass('active');
            
            // Show target tab content
            $tabContent.addClass('active');
            
            // Add active class to clicked tab link
            $this.addClass('active');
        });
    }
    
    /**
     * Initialize modals
     */
    function initModals() {
        $('.mt-modal-trigger').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this).data('modal');
            const $modal = $('#' + target);
            
            if ($modal.length) {
                $modal.addClass('active');
            }
        });
        
        $('.mt-modal-close').on('click', function() {
            $(this).closest('.mt-modal').removeClass('active');
        });
        
        $('.mt-modal').on('click', function(e) {
            if ($(e.target).hasClass('mt-modal')) {
                $(this).removeClass('active');
            }
        });
    }
    
    /**
     * Initialize confirmations
     */
    function initConfirmations() {
        $('.mt-confirm').on('click', function(e) {
            const message = $(this).data('confirm') || (mt_admin.i18n.confirm_delete || 'Are you sure?');
            
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Initialize AJAX forms
     */
    function initAjaxForms() {
        $('.mt-ajax-form').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();
            
            // Disable submit button
            $submitBtn.prop('disabled', true).text(mt_admin.i18n.processing || 'Processing...');
            
            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $('<div class="notice notice-success"><p>' + response.data.message + '</p></div>')
                            .insertAfter($form)
                            .delay(3000)
                            .fadeOut();
                    } else {
                        // Show error message
                        $('<div class="notice notice-error"><p>' + response.data.message + '</p></div>')
                            .insertAfter($form)
                            .delay(3000)
                            .fadeOut();
                    }
                },
                error: function() {
                    // Show error message
                    $('<div class="notice notice-error"><p>' + (mt_admin.i18n.error_occurred || 'An error occurred. Please try again.') + '</p></div>')
                        .insertAfter($form)
                        .delay(3000)
                        .fadeOut();
                },
                complete: function() {
                    // Re-enable submit button
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    /**
     * Utility function to show notification
     */
    window.mtShowNotification = function(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap > h1').after($notification);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        $notification.on('click', '.notice-dismiss', function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        });
    };
    
    /**
     * Utility function to handle AJAX errors
     */
    window.mtHandleAjaxError = function(xhr, textStatus, errorThrown) {
        console.error('AJAX Error:', textStatus, errorThrown);
        
        var message = mt_admin.strings.error;
        
        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            message = xhr.responseJSON.data.message;
        } else if (xhr.responseText) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.data && response.data.message) {
                    message = response.data.message;
                }
            } catch (e) {
                // Use default message
            }
        }
        
        mtShowNotification(message, 'error');
    };
    
    /**
     * Utility function to serialize form data
     */
    window.mtSerializeForm = function($form) {
        var data = {};
        
        $form.find('input, select, textarea').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            var value = $field.val();
            
            if (!name) return;
            
            if ($field.is(':checkbox')) {
                if ($field.is(':checked')) {
                    if (name.endsWith('[]')) {
                        if (!data[name]) data[name] = [];
                        data[name].push(value);
                    } else {
                        data[name] = value;
                    }
                }
            } else if ($field.is(':radio')) {
                if ($field.is(':checked')) {
                    data[name] = value;
                }
            } else {
                data[name] = value;
            }
        });
        
        return data;
    };
    
    /**
     * Utility function to update URL parameters
     */
    window.mtUpdateUrlParam = function(key, value) {
        var url = new URL(window.location);
        
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        
        window.history.pushState({}, '', url);
    };
    
    /**
     * Utility function to get URL parameter
     */
    window.mtGetUrlParam = function(key) {
        var url = new URL(window.location);
        return url.searchParams.get(key);
    };
    
    /**
     * Utility function to format number
     */
    window.mtFormatNumber = function(number) {
        return new Intl.NumberFormat('de-DE').format(number);
    };
    
    /**
     * Utility function to debounce
     */
    window.mtDebounce = function(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    /**
     * Assignment Management Module - Encapsulated for Assignment Page Only
     */
    const MTAssignmentManager = {
        init: function() {
            console.log('MTAssignmentManager initializing...');
            console.log('mt_admin object:', mt_admin);
            console.log('Buttons found:', {
                autoAssign: $('#mt-auto-assign-btn').length,
                manualAssign: $('#mt-manual-assign-btn').length,
                clearAll: $('#mt-clear-all-btn').length,
                export: $('#mt-export-btn').length
            });
            
            this.bindEvents();
            this.initializeModals();
            this.initializeFilters();
            
            console.log('MTAssignmentManager initialized');
        },
        
        bindEvents: function() {
            console.log('Binding events...');
            
            // Auto-assign button with debugging
            $('#mt-auto-assign-btn').on('click', (e) => {
                console.log('Auto-assign button clicked');
                e.preventDefault();
                this.showAutoAssignModal();
            });
            
            // Test if jQuery is working
            $('#mt-auto-assign-btn').css('border', '2px solid red');
            
            // Manual assign button
            $('#mt-manual-assign-btn').on('click', () => this.showManualAssignModal());
            
            // Clear all button
            $('#mt-clear-all-btn').on('click', () => this.confirmClearAll());
            
            // Export button
            $('#mt-export-btn').on('click', () => this.exportAssignments());
            
            // Remove assignment buttons
            $(document).on('click', '.mt-remove-assignment', (e) => {
                e.preventDefault();
                this.removeAssignment($(e.currentTarget));
            });
            
            // View details buttons
            $(document).on('click', '.mt-view-details', (e) => {
                e.preventDefault();
                this.viewJuryDetails($(e.currentTarget).data('jury-id'));
            });
            
            // Add assignment buttons
            $(document).on('click', '.mt-add-assignment', (e) => {
                e.preventDefault();
                this.quickAddAssignment($(e.currentTarget).data('jury-id'));
            });
            
            // Manual assignment form
            $('#mt-manual-assignment-form').on('submit', (e) => {
                e.preventDefault();
                this.submitManualAssignment();
            });
            
            // Auto-assignment form submission
            $('#mt-auto-assign-modal form').on('submit', (e) => {
                console.log('Auto-assign form submitted');
                e.preventDefault();
                this.submitAutoAssignment();
            });
            
            // Modal close buttons
            $('.mt-modal-close').on('click', () => this.closeModals());
            
            // Click outside modal to close
            $('.mt-modal').on('click', (e) => {
                if ($(e.target).hasClass('mt-modal')) {
                    this.closeModals();
                }
            });
        },
        
        initializeModals: function() {
            console.log('Initializing modals...');
            // Initialize modal functionality
            this.modals = {
                autoAssign: $('#mt-auto-assign-modal'),
                manualAssign: $('#mt-manual-assign-modal')
            };
            console.log('Modals found:', {
                autoAssign: this.modals.autoAssign.length,
                manualAssign: this.modals.manualAssign.length
            });
        },
        
        initializeFilters: function() {
            const self = this;
            
            // Search functionality
            $('#mt-search-assignments').on('keyup', function() {
                self.filterAssignments($(this).val());
            });
            
            // Status filter
            $('#mt-filter-status').on('change', () => this.applyFilters());
            
            // Category filter
            $('#mt-filter-category').on('change', () => this.applyFilters());
        },
        
        showAutoAssignModal: function() {
            console.log('showAutoAssignModal called');
            console.log('Modal element:', $('#mt-auto-assign-modal'));
            console.log('Modal exists:', $('#mt-auto-assign-modal').length > 0);
            
            // Try multiple methods to show the modal
            $('#mt-auto-assign-modal').show();
            $('#mt-auto-assign-modal').css('display', 'block');
            $('#mt-auto-assign-modal').fadeIn(300);
            
            console.log('Modal display after show:', $('#mt-auto-assign-modal').css('display'));
        },
        
        showManualAssignModal: function() {
            $('#mt-manual-assign-modal').fadeIn(300);
        },
        
        closeModals: function() {
            $('.mt-modal').fadeOut(300);
        },
        
        removeAssignment: function($button) {
            // Try multiple ways to get the assignment ID
            let assignmentId = $button.data('assignment-id');
            
            // If not found, try from the parent item
            if (!assignmentId) {
                assignmentId = $button.closest('.mt-assignment-item').data('assignment-id');
            }
            
            // If still not found, try from the href attribute
            if (!assignmentId && $button.attr('href')) {
                const match = $button.attr('href').match(/assignment_id=(\d+)/);
                if (match) {
                    assignmentId = match[1];
                }
            }
            
            if (!assignmentId) {
                this.showNotification('Could not determine assignment ID', 'error');
                return;
            }
            
            const confirmMessage = (mt_admin.i18n && mt_admin.i18n.confirm_remove_assignment) 
                ? mt_admin.i18n.confirm_remove_assignment 
                : 'Are you sure you want to remove this assignment?';
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_delete_assignment',
                    nonce: mt_admin.nonce || $('#mt_admin_nonce').val() || '',
                    assignment_id: assignmentId
                },
                success: (response) => {
                    if (response.success) {
                        this.handleRemoveSuccess($button);
                        const message = (mt_admin.i18n && mt_admin.i18n.assignment_removed) 
                            ? mt_admin.i18n.assignment_removed 
                            : 'Assignment removed successfully.';
                        this.showNotification(message, 'success');
                    } else {
                        const message = response.data?.message || 
                            (mt_admin.i18n && mt_admin.i18n.error_occurred ? mt_admin.i18n.error_occurred : 'An error occurred. Please try again.');
                        this.showNotification(message, 'error');
                    }
                },
                error: () => {
                    const message = (mt_admin.i18n && mt_admin.i18n.error_occurred) 
                        ? mt_admin.i18n.error_occurred 
                        : 'An error occurred. Please try again.';
                    this.showNotification(message, 'error');
                },
                complete: () => {
                    $button.prop('disabled', false);
                }
            });
        },
        
        handleRemoveSuccess: function($button) {
            const $item = $button.closest('.mt-assignment-item');
            const $card = $button.closest('.mt-jury-card');
            
            $item.fadeOut(300, () => {
                $item.remove();
                this.updateJuryCardStats($card);
            });
        },
        
        updateJuryCardStats: function($card) {
            const assignmentCount = $card.find('.mt-assignment-item').length;
            $card.find('.mt-assignment-count').text(assignmentCount);
            
            if (assignmentCount === 0) {
                const noAssignmentsText = (mt_admin.i18n && mt_admin.i18n.no_assignments) 
                    ? mt_admin.i18n.no_assignments 
                    : 'No assignments yet';
                $card.find('.mt-assignment-list').html(
                    '<p class="mt-no-assignments">' + noAssignmentsText + '</p>'
                );
            }
            
            // Update progress bar
            const totalCandidates = parseInt($('.mt-stat-card:first .mt-stat-number').text()) || 1;
            const percentage = totalCandidates > 0 ? Math.round((assignmentCount / totalCandidates) * 100) : 0;
            $card.find('.mt-progress-fill').css('width', percentage + '%');
            $card.find('.mt-progress-text').text(percentage + '%');
        },
        
        submitManualAssignment: function() {
            const $form = $('#mt-manual-assignment-form');
            const juryMemberId = $('#manual_jury_member').val();
            const candidateIds = $('input[name="candidate_ids[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (!juryMemberId || candidateIds.length === 0) {
                const message = (mt_admin.i18n && mt_admin.i18n.select_jury_and_candidates) 
                    ? mt_admin.i18n.select_jury_and_candidates 
                    : 'Please select a jury member and at least one candidate.';
                this.showNotification(message, 'error');
                return;
            }
            
            const processingText = (mt_admin.i18n && mt_admin.i18n.processing) 
                ? mt_admin.i18n.processing 
                : 'Processing...';
            $form.find('button[type="submit"]').prop('disabled', true).text(processingText);
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_bulk_create_assignments',
                    nonce: mt_admin.nonce || $('#mt_admin_nonce').val() || '',
                    jury_member_id: juryMemberId,
                    candidate_ids: candidateIds
                },
                success: (response) => {
                    if (response.success) {
                        const message = (mt_admin.i18n && mt_admin.i18n.assignments_created) 
                            ? mt_admin.i18n.assignments_created 
                            : 'Assignments created successfully.';
                        this.showNotification(message, 'success');
                        this.closeModals();
                        // Reload page to show new assignments
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        const message = response.data?.message || 
                            (mt_admin.i18n && mt_admin.i18n.error_occurred ? mt_admin.i18n.error_occurred : 'An error occurred. Please try again.');
                        this.showNotification(message, 'error');
                    }
                },
                error: () => {
                    const message = (mt_admin.i18n && mt_admin.i18n.error_occurred) 
                        ? mt_admin.i18n.error_occurred 
                        : 'An error occurred. Please try again.';
                    this.showNotification(message, 'error');
                },
                complete: () => {
                    const assignText = (mt_admin.i18n && mt_admin.i18n.assign_selected) 
                        ? mt_admin.i18n.assign_selected 
                        : 'Assign Selected';
                    $form.find('button[type="submit"]').prop('disabled', false).text(assignText);
                }
            });
        },
        
        submitAutoAssignment: function() {
            const $form = $('#mt-auto-assign-modal form');
            
            // Get form values
            const method = $('#assignment_method').val();
            const candidatesPerJury = $('#candidates_per_jury').val();
            
            console.log('submitAutoAssignment called with:', {
                method: method,
                candidatesPerJury: candidatesPerJury,
                ajax_url: mt_admin.ajax_url,
                nonce: mt_admin.nonce
            });
            
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
                    console.log('AJAX request starting...');
                    $form.find('button[type="submit"]').prop('disabled', true).text(mt_admin.i18n.processing || 'Processing...');
                },
                success: (response) => {
                    console.log('AJAX Success Response:', response);
                    if (response.success) {
                        this.showNotification(response.data || 'Auto-assignment completed successfully.', 'success');
                        this.closeModals();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.showNotification(response.data || 'An error occurred.', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX Error Details:', {
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        error: error
                    });
                    this.showNotification('Connection error. Please check console for details.', 'error');
                },
                complete: () => {
                    console.log('AJAX request completed');
                    $form.find('button[type="submit"]').prop('disabled', false).text(mt_admin.i18n.run_auto_assignment || 'Run Auto-Assignment');
                }
            });
        },
        
        confirmClearAll: function() {
            const confirmMessage = (mt_admin.i18n && mt_admin.i18n.confirm_clear_all) 
                ? mt_admin.i18n.confirm_clear_all 
                : 'Are you sure you want to clear ALL assignments? This cannot be undone.';
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            // Double confirmation for safety
            const secondConfirmMessage = (mt_admin.i18n && mt_admin.i18n.confirm_clear_all_second) 
                ? mt_admin.i18n.confirm_clear_all_second 
                : 'This will remove ALL jury assignments. Are you absolutely sure?';
            
            if (!confirm(secondConfirmMessage)) {
                return;
            }
            
            this.clearAllAssignments();
        },
        
        clearAllAssignments: function() {
            const $button = $('#mt-clear-all-btn');
            const clearingText = (mt_admin.i18n && mt_admin.i18n.clearing) 
                ? mt_admin.i18n.clearing 
                : 'Clearing...';
            $button.prop('disabled', true).text(clearingText);
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_clear_all_assignments',
                    nonce: mt_admin.nonce || $('#mt_admin_nonce').val() || ''
                },
                success: (response) => {
                    if (response.success) {
                        const message = (mt_admin.i18n && mt_admin.i18n.all_assignments_cleared) 
                            ? mt_admin.i18n.all_assignments_cleared 
                            : 'All assignments have been cleared.';
                        this.showNotification(message, 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        const message = response.data?.message || 
                            (mt_admin.i18n && mt_admin.i18n.error_occurred ? mt_admin.i18n.error_occurred : 'An error occurred. Please try again.');
                        this.showNotification(message, 'error');
                    }
                },
                error: () => {
                    const message = (mt_admin.i18n && mt_admin.i18n.error_occurred) 
                        ? mt_admin.i18n.error_occurred 
                        : 'An error occurred. Please try again.';
                    this.showNotification(message, 'error');
                },
                complete: () => {
                    const clearText = (mt_admin.i18n && mt_admin.i18n.clear_all) 
                        ? mt_admin.i18n.clear_all 
                        : 'Clear All';
                    $button.prop('disabled', false).text(clearText);
                }
            });
        },
        
        exportAssignments: function() {
            const exportingText = (mt_admin.i18n && mt_admin.i18n.export_started) 
                ? mt_admin.i18n.export_started 
                : 'Export started. Download will begin shortly.';
            
            this.showNotification(exportingText, 'info');
            
            // Create a form to trigger the download
            const $form = $('<form>', {
                method: 'POST',
                action: mt_admin.ajax_url
            });
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'mt_export_assignments'
            }));
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: mt_admin.nonce || $('#mt_admin_nonce').val() || ''
            }));
            
            // Submit form to trigger download
            $form.appendTo('body').submit().remove();
        },
        
        filterAssignments: function(searchTerm) {
            const term = searchTerm.toLowerCase();
            
            $('.mt-jury-card').each(function() {
                const $card = $(this);
                const juryName = $card.find('h3').text().toLowerCase();
                const email = $card.find('.mt-jury-email').text().toLowerCase();
                const candidates = $card.find('.mt-candidate-link').map(function() {
                    return $(this).text().toLowerCase();
                }).get().join(' ');
                
                if (juryName.includes(term) || email.includes(term) || candidates.includes(term)) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
        },
        
        applyFilters: function() {
            const status = $('#mt-filter-status').val();
            const category = $('#mt-filter-category').val();
            
            $('.mt-jury-card').each(function() {
                const $card = $(this);
                let show = true;
                
                // Status filter
                if (status) {
                    const assignmentCount = parseInt($card.find('.mt-assignment-count').text());
                    const totalCandidates = parseInt($('.mt-stat-card:first .mt-stat-number').text());
                    
                    if (status === 'complete' && assignmentCount < totalCandidates) show = false;
                    if (status === 'partial' && (assignmentCount === 0 || assignmentCount >= totalCandidates)) show = false;
                    if (status === 'none' && assignmentCount > 0) show = false;
                }
                
                // Category filter would need additional data attributes
                
                $card.toggle(show);
            });
        },
        
        viewJuryDetails: function(juryId) {
            // Navigate to jury member detail page
            window.location.href = mt_admin.admin_url + 'post.php?post=' + juryId + '&action=edit';
        },
        
        quickAddAssignment: function(juryId) {
            // Pre-select jury member in manual assignment modal
            $('#manual_jury_member').val(juryId);
            this.showManualAssignModal();
        },
        
        showNotification: function(message, type = 'info') {
            const $notification = $('<div>', {
                class: 'notice notice-' + type + ' is-dismissible mt-notification',
                html: '<p>' + message + '</p>'
            });
            
            $('.wrap h1').after($notification);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Make dismissible
            $notification.on('click', '.notice-dismiss', function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    };
    
    /**
     * Bulk Operations Manager - For Assignment Page Tables
     */
    const MTBulkOperations = {
        selectedItems: [],
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Bulk actions button
            $('#mt-bulk-actions-btn').on('click', function() {
                self.toggleBulkMode();
            });
            
            // Cancel bulk action
            $('#mt-cancel-bulk-action').on('click', function() {
                self.exitBulkMode();
            });
            
            // Apply bulk action
            $('#mt-apply-bulk-action').on('click', function() {
                self.applyBulkAction();
            });
            
            // Select all checkbox
            $('#mt-select-all-assignments').on('change', function() {
                const isChecked = $(this).prop('checked');
                $('.mt-assignment-checkbox').prop('checked', isChecked);
                self.updateSelectedCount();
            });
            
            // Individual checkboxes
            $(document).on('change', '.mt-assignment-checkbox', function() {
                self.updateSelectedCount();
                
                // Update select all checkbox
                const totalCheckboxes = $('.mt-assignment-checkbox').length;
                const checkedCheckboxes = $('.mt-assignment-checkbox:checked').length;
                $('#mt-select-all-assignments').prop('checked', totalCheckboxes === checkedCheckboxes);
            });
        },
        
        toggleBulkMode: function() {
            const inBulkMode = $('.check-column').is(':visible');
            
            if (inBulkMode) {
                this.exitBulkMode();
            } else {
                this.enterBulkMode();
            }
        },
        
        enterBulkMode: function() {
            $('.check-column').show();
            $('#mt-bulk-actions-container').slideDown();
            $('#mt-bulk-actions-btn').addClass('active');
            this.updateSelectedCount();
        },
        
        exitBulkMode: function() {
            $('.check-column').hide();
            $('#mt-bulk-actions-container').slideUp();
            $('#mt-bulk-actions-btn').removeClass('active');
            $('.mt-assignment-checkbox').prop('checked', false);
            $('#mt-select-all-assignments').prop('checked', false);
        },
        
        updateSelectedCount: function() {
            const count = $('.mt-assignment-checkbox:checked').length;
            const text = count === 1 ? '1 item selected' : count + ' items selected';
            
            // Update UI to show count
            if ($('#mt-selected-count').length === 0) {
                $('#mt-bulk-actions-container').prepend('<span id="mt-selected-count" style="margin-right: 10px;">' + text + '</span>');
            } else {
                $('#mt-selected-count').text(text);
            }
        },
        
        applyBulkAction: function() {
            const action = $('#mt-bulk-action-select').val();
            const selectedIds = [];
            
            $('.mt-assignment-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (!action) {
                alert(mt_admin.i18n.select_bulk_action || 'Please select a bulk action');
                return;
            }
            
            if (selectedIds.length === 0) {
                alert(mt_admin.i18n.select_assignments || 'Please select at least one assignment');
                return;
            }
            
            switch (action) {
                case 'remove':
                    this.bulkRemove(selectedIds);
                    break;
                case 'reassign':
                    this.bulkReassign(selectedIds);
                    break;
                case 'export':
                    this.bulkExport(selectedIds);
                    break;
            }
        },
        
        bulkRemove: function(assignmentIds) {
            if (!confirm('Are you sure you want to remove ' + assignmentIds.length + ' assignments?')) {
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
                beforeSend: function() {
                    $('#mt-apply-bulk-action').prop('disabled', true).text(mt_admin.i18n.processing || 'Processing...');
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message || 'Assignments removed successfully');
                        location.reload();
                    } else {
                        alert(response.data || 'An error occurred');
                    }
                },
                error: function() {
                    alert(mt_admin.i18n.error_occurred || 'An error occurred. Please try again.');
                },
                complete: function() {
                    $('#mt-apply-bulk-action').prop('disabled', false).text(mt_admin.i18n.apply || 'Apply');
                }
            });
        },
        
        bulkReassign: function(assignmentIds) {
            // Show modal for selecting new jury member
            const juryOptions = $('#mt-filter-jury').html();
            const modalHtml = `
                <div id="mt-reassign-modal" class="mt-modal" style="display: block;">
                    <div class="mt-modal-content">
                        <div class="mt-modal-header">
                            <h2>Reassign Assignments</h2>
                            <button type="button" class="mt-modal-close">&times;</button>
                        </div>
                        <div class="mt-modal-body">
                            <p>Select a jury member to reassign ${assignmentIds.length} assignments to:</p>
                            <select id="mt-reassign-jury-select" class="widefat">
                                ${juryOptions}
                            </select>
                        </div>
                        <div class="mt-modal-footer">
                            <button type="button" id="mt-confirm-reassign" class="button button-primary">Reassign</button>
                            <button type="button" class="button mt-modal-close">Cancel</button>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            
            $('#mt-confirm-reassign').on('click', function() {
                const newJuryId = $('#mt-reassign-jury-select').val();
                if (!newJuryId) {
                    alert(mt_admin.i18n.select_jury_member || 'Please select a jury member');
                    return;
                }
                
                $.ajax({
                    url: mt_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mt_bulk_reassign_assignments',
                        nonce: mt_admin.nonce,
                        assignment_ids: assignmentIds,
                        new_jury_id: newJuryId
                    },
                    beforeSend: function() {
                        $('#mt-confirm-reassign').prop('disabled', true).text(mt_admin.i18n.processing || 'Processing...');
                    },
                    success: function(response) {
                        if (response.success) {
                            alert(response.data.message || 'Assignments reassigned successfully');
                            location.reload();
                        } else {
                            alert(response.data || 'An error occurred');
                        }
                    },
                    error: function() {
                        alert(mt_admin.i18n.error_occurred || 'An error occurred. Please try again.');
                    }
                });
            });
            
            $('.mt-modal-close').on('click', function() {
                $('#mt-reassign-modal').remove();
            });
        },
        
        bulkExport: function(assignmentIds) {
            // Create form for export
            const $form = $('<form>', {
                method: 'POST',
                action: mt_admin.ajax_url
            });
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'mt_bulk_export_assignments'
            }));
            
            $form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: mt_admin.nonce
            }));
            
            assignmentIds.forEach(function(id) {
                $form.append($('<input>', {
                    type: 'hidden',
                    name: 'assignment_ids[]',
                    value: id
                }));
            });
            
            $form.appendTo('body').submit().remove();
        }
    };
    
    /**
     * Evaluation Management Module - Encapsulated for Evaluations Page Only
     */
    const MTEvaluationManager = {
        init: function() {
            console.log('MTEvaluationManager initializing...');
            this.bindEvents();
            console.log('MTEvaluationManager initialized');
        },
        
        bindEvents: function() {
            console.log('Binding evaluation events...');
            
            // View details buttons
            $('.view-details').on('click', (e) => {
                e.preventDefault();
                const evaluationId = $(e.currentTarget).data('evaluation-id');
                this.viewEvaluationDetails(evaluationId);
            });
            
            // Select all checkboxes
            $('#cb-select-all-1, #cb-select-all-2').on('click', (e) => {
                this.handleSelectAll($(e.currentTarget));
            });
            
            // Individual checkbox click
            $('input[name="evaluation[]"]').on('click', () => {
                this.updateSelectAllCheckbox();
            });
            
            // Bulk actions
            $('#doaction, #doaction2').on('click', (e) => {
                e.preventDefault();
                this.handleBulkAction($(e.currentTarget));
            });
        },
        
        viewEvaluationDetails: function(evaluationId) {
            // TODO: Implement AJAX call to load evaluation details
            alert('View evaluation ' + evaluationId + ' details - To be implemented');
        },
        
        handleSelectAll: function($checkbox) {
            const checked = $checkbox.prop('checked');
            $('input[name="evaluation[]"]').prop('checked', checked);
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', checked);
        },
        
        updateSelectAllCheckbox: function() {
            const allChecked = $('input[name="evaluation[]"]').length === $('input[name="evaluation[]"]:checked').length;
            $('#cb-select-all-1, #cb-select-all-2').prop('checked', allChecked);
        },
        
        handleBulkAction: function($button) {
            const action = $button.prev('select').val();
            
            if (action === '-1') {
                alert('Please select a bulk action');
                return;
            }
            
            const selected = [];
            $('input[name="evaluation[]"]:checked').each(function() {
                selected.push($(this).val());
            });
            
            if (selected.length === 0) {
                alert('Please select at least one evaluation');
                return;
            }
            
            const confirmMessage = this.getConfirmMessage(action);
            
            if (!confirm(confirmMessage)) {
                return;
            }
            
            this.performBulkAction(action, selected, $button);
        },
        
        getConfirmMessage: function(action) {
            switch(action) {
                case 'approve':
                    return 'Are you sure you want to approve the selected evaluations?';
                case 'reject':
                    return 'Are you sure you want to reject the selected evaluations?';
                case 'reset-to-draft':
                    return 'Are you sure you want to reset the selected evaluations to draft?';
                case 'delete':
                    return 'Are you sure you want to delete the selected evaluations? This action cannot be undone.';
                default:
                    return 'Are you sure you want to perform this action?';
            }
        },
        
        performBulkAction: function(action, evaluationIds, $button) {
            $.ajax({
                url: mt_admin.url || mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_bulk_evaluation_action',
                    bulk_action: action,
                    evaluation_ids: evaluationIds,
                    nonce: mt_admin.nonce
                },
                beforeSend: () => {
                    $('#doaction, #doaction2').prop('disabled', true).val('Processing...');
                },
                success: (response) => {
                    if (response.success) {
                        alert(response.data.message || 'Bulk action completed successfully');
                        location.reload();
                    } else {
                        alert(response.data || 'An error occurred');
                    }
                },
                error: () => {
                    alert('An error occurred. Please try again.');
                },
                complete: () => {
                    $('#doaction, #doaction2').prop('disabled', false).val('Apply');
                }
            });
        }
    };

    /**
     * Initialize media upload functionality
     */
    function initMediaUpload() {
        // Media upload for header background
        $('#upload_header_image').on('click', function(e) {
            e.preventDefault();
            
            var mediaUploader = wp.media({
                title: 'Choose Header Background Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#header_image_url').val(attachment.url);
                $('#header_image_preview').attr('src', attachment.url).show();
            });
            
            mediaUploader.open();
        });
    }

    /**
     * Main initialization on document ready
     */
    $(document).ready(function() {
        console.log('Document ready - initializing general admin functions');
        
        // Initialize general utilities (run on all admin pages)
        initTooltips();
        initTabs();
        initModals();
        initConfirmations();
        initAjaxForms();
        
        // Initialize select2 if available
        if ($.fn.select2) {
            $('.mt-select2').select2();
        }
        
        // Initialize date pickers if available
        if ($.fn.datepicker) {
            $('.mt-datepicker').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        }
        
        // Initialize media upload for settings page
        initMediaUpload();
        
        // Check if we're on the Assignment Management page
        // Only initialize assignment-specific modules if we detect the page elements
        if ($('#mt-auto-assign-btn').length > 0 || 
            $('.mt-assignments-table').length > 0 ||
            $('.mt-assignment-management').length > 0 ||
            $('body').hasClass('mobility-trailblazers_page_mt-assignment-management') ||
            window.location.href.includes('mt-assignment-management')) {
            
            console.log('Assignment Management page detected, initializing assignment modules...');
            
            // Initialize Assignment Manager
            MTAssignmentManager.init();
            
            // Initialize Bulk Operations if table exists
            if ($('.mt-assignments-table').length > 0) {
                MTBulkOperations.init();
            }
        } else {
            console.log('Not on assignment page, skipping assignment-specific modules');
        }
        
        // Check if we're on the Evaluations page
        // Look for unique elements of the evaluations page
        if ($('.wrap h1:contains("Evaluations")').length > 0 && 
            $('.wp-list-table').length > 0 &&
            $('input[name="evaluation[]"]').length > 0) {
            
            console.log('Evaluations page detected, initializing evaluation module...');
            MTEvaluationManager.init();
        } else {
            console.log('Not on evaluations page, skipping evaluation-specific module');
        }
    });

})(jQuery);