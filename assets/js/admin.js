/**
 * Mobility Trailblazers Admin JavaScript
 */

// Ensure mt_admin object exists with fallback values
if (typeof mt_admin === 'undefined') {
    window.mt_admin = {
        ajax_url: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
        nonce: '',
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

    // Wait for document ready
    $(document).ready(function() {
        
        // Initialize tooltips
        initTooltips();
        
        // Initialize tabs
        initTabs();
        
        // Initialize modals
        initModals();
        
        // Initialize confirmations
        initConfirmations();
        
        // Initialize AJAX forms
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
    });
    
    /**
     * Initialize tooltips
     */
    function initTooltips() {
        // Already handled by CSS, but we can add enhanced functionality here
        $('.mt-tooltip').on('mouseenter', function() {
            var $tooltip = $(this).find('.mt-tooltip-content');
            
            // Check if tooltip goes off screen
            var tooltipOffset = $tooltip.offset();
            if (tooltipOffset && tooltipOffset.left < 0) {
                $tooltip.css('left', '0');
                $tooltip.css('margin-left', '0');
            }
        });
    }
    
    /**
     * Initialize tabs
     */
    function initTabs() {
        $('.mt-tab-nav a').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var target = $this.attr('href');
            
            // Update active states
            $this.siblings().removeClass('active');
            $this.addClass('active');
            
            // Show target content
            $(target).siblings('.mt-tab-content').removeClass('active');
            $(target).addClass('active');
            
            // Save active tab to localStorage
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('mt_active_tab_' + window.location.pathname, target);
            }
        });
        
        // Restore active tab from localStorage
        if (typeof(Storage) !== "undefined") {
            var savedTab = localStorage.getItem('mt_active_tab_' + window.location.pathname);
            if (savedTab && $(savedTab).length) {
                $('.mt-tab-nav a[href="' + savedTab + '"]').trigger('click');
            }
        }
    }
    
    /**
     * Initialize modals
     */
    function initModals() {
        // Open modal
        $('[data-modal]').on('click', function(e) {
            e.preventDefault();
            var modalId = $(this).data('modal');
            $('#' + modalId).fadeIn();
        });
        
        // Close modal
        $('.mt-modal-close, .mt-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).closest('.mt-modal').fadeOut();
            }
        });
        
        // Close on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.mt-modal:visible').fadeOut();
            }
        });
    }
    
    /**
     * Initialize confirmations
     */
    function initConfirmations() {
        $('[data-confirm]').on('click', function(e) {
            var message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
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
            
            var $form = $(this);
            var $submit = $form.find('[type="submit"]');
            var originalText = $submit.text();
            
            // Disable submit button and show loading
            $submit.prop('disabled', true).html(originalText + ' <span class="mt-spinner"></span>');
            
            // Clear previous errors
            $form.find('.mt-form-error').remove();
            $form.find('.mt-alert').remove();
            
            // Prepare data
            var formData = new FormData(this);
            formData.append('action', $form.data('action'));
            formData.append('nonce', mt_admin.nonce);
            
            // Send AJAX request
            $.ajax({
                url: mt_admin.url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $form.prepend('<div class="mt-alert mt-alert-success">' + response.data.message + '</div>');
                        
                        // Reset form if specified
                        if ($form.data('reset-on-success')) {
                            $form[0].reset();
                        }
                        
                        // Trigger custom event
                        $form.trigger('mt:form:success', [response]);
                        
                        // Redirect if URL provided
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        // Show error message
                        $form.prepend('<div class="mt-alert mt-alert-danger">' + response.data.message + '</div>');
                        
                        // Show field errors if any
                        if (response.data.errors) {
                            $.each(response.data.errors, function(field, error) {
                                var $field = $form.find('[name="' + field + '"]');
                                $field.after('<span class="mt-form-error">' + error + '</span>');
                            });
                        }
                        
                        // Trigger custom event
                        $form.trigger('mt:form:error', [response]);
                    }
                },
                error: function() {
                    $form.prepend('<div class="mt-alert mt-alert-danger">' + mt_admin.strings.error + '</div>');
                },
                complete: function() {
                    // Re-enable submit button
                    $submit.prop('disabled', false).text(originalText);
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
     * Assignment Management Module
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
            // Auto-assign button
            $('#mt-auto-assign-btn').on('click', () => this.showAutoAssignModal());
            
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
            // Initialize modal functionality
            this.modals = {
                autoAssign: $('#mt-auto-assign-modal'),
                manualAssign: $('#mt-manual-assign-modal')
            };
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
            $('#mt-auto-assign-modal').fadeIn(300);
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
            const formData = $form.serialize();
            
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: formData + '&action=mt_auto_assign&nonce=' + mt_admin.nonce,
                beforeSend: () => {
                    $form.find('button[type="submit"]').prop('disabled', true).text('Processing...');
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification('Auto-assignment completed successfully.', 'success');
                        this.closeModals();
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        this.showNotification(response.data?.message || 'An error occurred.', 'error');
                    }
                },
                error: () => {
                    this.showNotification('Connection error. Please try again.', 'error');
                },
                complete: () => {
                    $form.find('button[type="submit"]').prop('disabled', false).text('Run Auto-Assignment');
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
    
    // Initialize Assignment Manager on document ready
    $(document).ready(function() {
        // Check if we're on the assignment management page
        if ($('#mt-auto-assign-btn').length > 0 || 
            $('.mt-assignment-management').length > 0 ||
            $('body').hasClass('mobility-trailblazers_page_mt-assignment-management')) {
            MTAssignmentManager.init();
        }
    });

})(jQuery); 