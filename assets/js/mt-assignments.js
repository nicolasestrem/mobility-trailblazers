/**
 * Mobility Trailblazers Assignments JavaScript
 * Handles assignment management functionality
 */

(function($) {
    'use strict';
    
    // Wait for DOM ready
    $(document).ready(function() {
        console.log('MT Assignments: Script loaded');
        
        // Initialize assignment functionality
        initAssignments();
    });
    
    function initAssignments() {
        // Check if we're on the assignments page
        if ($('#mt-auto-assign-btn').length === 0) {
            return;
        }
        
        console.log('MT Assignments: Initializing assignment handlers');
        
        // Auto-assign button handler
        $('#mt-auto-assign-btn').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('MT Assignments: Auto-assign button clicked');
            openAutoAssignModal();
        });
        
        // Manual assignment button handler
        $('#mt-manual-assign-btn').off('click').on('click', function(e) {
            e.preventDefault();
            console.log('MT Assignments: Manual assign button clicked');
            openManualAssignModal();
        });
        
        // Modal close button handler
        $('.mt-modal-close').off('click').on('click', function(e) {
            e.preventDefault();
            closeModal($(this).closest('.mt-modal'));
        });
        
        // Click outside modal to close
        $('.mt-modal').off('click').on('click', function(e) {
            if ($(e.target).hasClass('mt-modal')) {
                closeModal($(this));
            }
        });
        
        // Auto-assign form submission
        $('#mt-auto-assign-modal form').off('submit').on('submit', function(e) {
            e.preventDefault();
            submitAutoAssignment();
        });
        
        // Manual assignment form submission
        $('#mt-manual-assignment-form').off('submit').on('submit', function(e) {
            e.preventDefault();
            submitManualAssignment();
        });
        
        // Remove assignment button handler
        $(document).on('click', '.mt-remove-assignment', function(e) {
            e.preventDefault();
            removeAssignment($(this));
        });
        
        // Clear all button handler
        $('#mt-clear-all-btn').off('click').on('click', function(e) {
            e.preventDefault();
            clearAllAssignments();
        });
        
        // Export button handler
        $('#mt-export-btn').off('click').on('click', function(e) {
            e.preventDefault();
            exportAssignments();
        });
        
        // Bulk actions button handler
        $('#mt-bulk-actions-btn').off('click').on('click', function(e) {
            e.preventDefault();
            toggleBulkActions();
        });
    }
    
    function openAutoAssignModal() {
        $('#mt-auto-assign-modal').css('display', 'flex').hide().fadeIn(300);
    }
    
    function openManualAssignModal() {
        $('#mt-manual-assign-modal').css('display', 'flex').hide().fadeIn(300);
    }
    
    function closeModal($modal) {
        $modal.fadeOut(300);
    }
    
    function submitAutoAssignment() {
        var method = $('#assignment_method').val();
        var candidatesPerJury = $('#candidates_per_jury').val();
        var clearExisting = $('#clear_existing').is(':checked') ? 'true' : 'false';
        
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
            
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        
        console.log('MT Assignments: Submitting auto-assignment', {
            method: method,
            candidatesPerJury: candidatesPerJury,
            clearExisting: clearExisting
        });
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mt_auto_assign',
                nonce: nonce,
                method: method,
                candidates_per_jury: candidatesPerJury,
                clear_existing: clearExisting
            },
            beforeSend: function() {
                $('#mt-auto-assign-modal button[type="submit"]')
                    .prop('disabled', true)
                    .text('Processing...');
            },
            success: function(response) {
                console.log('MT Assignments: Auto-assign response', response);
                if (response.success) {
                    showNotification(response.data.message || 'Auto-assignment completed successfully!', 'success');
                    closeModal($('#mt-auto-assign-modal'));
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || 'An error occurred', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('MT Assignments: Auto-assign error', error);
                showNotification('Error: ' + error, 'error');
            },
            complete: function() {
                $('#mt-auto-assign-modal button[type="submit"]')
                    .prop('disabled', false)
                    .text('Run Auto-Assignment');
            }
        });
    }
    
    function submitManualAssignment() {
        var juryMemberId = $('#manual_jury_member').val();
        var candidateIds = [];
        
        $('input[name="candidate_ids[]"]:checked').each(function() {
            candidateIds.push($(this).val());
        });
        
        if (!juryMemberId || candidateIds.length === 0) {
            showNotification('Please select a jury member and at least one candidate.', 'warning');
            return;
        }
        
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
            
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        
        console.log('MT Assignments: Submitting manual assignment', {
            juryMemberId: juryMemberId,
            candidateIds: candidateIds
        });
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mt_manual_assign',
                nonce: nonce,
                jury_member_id: juryMemberId,
                candidate_ids: candidateIds
            },
            beforeSend: function() {
                $('#mt-manual-assignment-form button[type="submit"]')
                    .prop('disabled', true)
                    .text('Processing...');
            },
            success: function(response) {
                console.log('MT Assignments: Manual assign response', response);
                if (response.success) {
                    showNotification(response.data.message || 'Assignments created successfully!', 'success');
                    closeModal($('#mt-manual-assign-modal'));
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || 'An error occurred', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('MT Assignments: Manual assign error', error);
                showNotification('Error: ' + error, 'error');
            },
            complete: function() {
                $('#mt-manual-assignment-form button[type="submit"]')
                    .prop('disabled', false)
                    .text('Assign Selected');
            }
        });
    }
    
    function removeAssignment($button) {
        var assignmentId = $button.data('assignment-id');
        var juryName = $button.data('jury');
        var candidateName = $button.data('candidate');
        
        if (!confirm('Are you sure you want to remove this assignment?')) {
            return;
        }
        
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
            
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mt_remove_assignment',
                nonce: nonce,
                assignment_id: assignmentId
            },
            beforeSend: function() {
                $button.prop('disabled', true).text('Processing...');
            },
            success: function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut(400, function() {
                        $(this).remove();
                        // Check if table is empty
                        if ($('.mt-assignments-table tbody tr').length === 0) {
                            $('.mt-assignments-table tbody').html(
                                '<tr><td colspan="8" class="no-items">No assignments yet</td></tr>'
                            );
                        }
                    });
                    showNotification('Assignment removed successfully.', 'success');
                } else {
                    showNotification(response.data || 'An error occurred', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text('Remove');
            }
        });
    }
    
    function clearAllAssignments() {
        if (!confirm('Are you sure you want to clear ALL assignments? This cannot be undone.')) {
            return;
        }
        
        if (!confirm('This will remove ALL jury assignments. Are you absolutely sure?')) {
            return;
        }
        
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
            
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                action: 'mt_clear_all_assignments',
                nonce: nonce
            },
            beforeSend: function() {
                $('#mt-clear-all-btn').prop('disabled', true).text('Clearing...');
            },
            success: function(response) {
                if (response.success) {
                    showNotification('All assignments have been cleared.', 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data || 'An error occurred', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred', 'error');
            },
            complete: function() {
                $('#mt-clear-all-btn')
                    .prop('disabled', false)
                    .html('<span class="dashicons dashicons-trash"></span> Clear All');
            }
        });
    }
    
    function exportAssignments() {
        var ajaxUrl = (typeof mt_admin !== 'undefined' && mt_admin.ajax_url) 
            ? mt_admin.ajax_url 
            : ajaxurl;
            
        var nonce = (typeof mt_admin !== 'undefined' && mt_admin.nonce) 
            ? mt_admin.nonce 
            : $('#mt_admin_nonce').val();
        
        // Create a form to trigger download
        var form = $('<form/>', {
            action: ajaxUrl,
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
            value: nonce
        }));
        
        form.appendTo('body').submit().remove();
        
        showNotification('Export started. Download will begin shortly.', 'info');
    }
    
    function toggleBulkActions() {
        var $container = $('#mt-bulk-actions-container');
        var $checkboxColumn = $('.check-column');
        
        if ($container.is(':visible')) {
            $container.slideUp();
            $checkboxColumn.hide();
            $('.mt-assignment-checkbox').prop('checked', false);
            $('#mt-select-all-assignments').prop('checked', false);
        } else {
            $container.slideDown();
            $checkboxColumn.show();
        }
    }
    
    function showNotification(message, type) {
        type = type || 'info';
        
        // Remove any existing notifications
        $('.mt-notification').remove();
        
        // Map types to WordPress notice classes
        var typeMap = {
            'success': 'notice-success',
            'error': 'notice-error',
            'warning': 'notice-warning',
            'info': 'notice-info'
        };
        
        var noticeClass = typeMap[type] || 'notice-info';
        
        // Create notification HTML
        var notificationHtml = 
            '<div class="mt-notification notice ' + noticeClass + ' is-dismissible">' +
                '<p>' + message + '</p>' +
                '<button type="button" class="notice-dismiss">' +
                    '<span class="screen-reader-text">Dismiss this notice.</span>' +
                '</button>' +
            '</div>';
        
        // Add notification after the page title
        var $target = $('.wrap h1').first();
        if ($target.length) {
            $(notificationHtml).insertAfter($target);
        } else {
            // Fallback: add to beginning of .wrap
            $('.wrap').prepend(notificationHtml);
        }
        
        // Auto-dismiss after 5 seconds for success messages
        if (type === 'success') {
            setTimeout(function() {
                $('.mt-notification').fadeOut(400, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Handle dismiss button
        $('.mt-notification .notice-dismiss').on('click', function() {
            $(this).closest('.mt-notification').fadeOut(400, function() {
                $(this).remove();
            });
        });
    }
    
})(jQuery);