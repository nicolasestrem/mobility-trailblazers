/**
 * Mobility Trailblazers - Jury Management JavaScript
 * File: assets/js/jury-management.js
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        
        // Initialize tabs
        if ($('#mt-jury-tabs').length) {
            $('#mt-jury-tabs').tabs();
        }
        
        // Initialize select all checkbox
        initSelectAll();
        
        // Initialize filters
        initFilters();
        
        // Initialize photo upload
        initPhotoUpload();
        
        // Initialize status toggle
        initStatusToggle();
        
        // Initialize bulk actions
        initBulkActions();
        
        // Initialize invitations
        initInvitations();
        
        // Initialize assignments
        initAssignments();
        
        // Initialize import/export
        initImportExport();
    });
    
    /**
     * Initialize select all checkbox functionality
     */
    function initSelectAll() {
        $('#cb-select-all').on('change', function() {
            $('input[name="jury_members[]"]').prop('checked', $(this).prop('checked'));
        });
        
        $('input[name="jury_members[]"]').on('change', function() {
            var allChecked = $('input[name="jury_members[]"]:checked').length === $('input[name="jury_members[]"]').length;
            $('#cb-select-all').prop('checked', allChecked);
        });
    }
    
    /**
     * Initialize filter functionality
     */
    function initFilters() {
        // Search filter
        $('#mt-jury-search').on('keyup', function() {
            var searchTerm = $(this).val().toLowerCase();
            filterJuryList();
        });
        
        // Status filter
        $('#mt-jury-filter-status, #mt-jury-filter-role').on('change', function() {
            filterJuryList();
        });
        
        // Apply filters button
        $('#mt-jury-apply-filters').on('click', function() {
            filterJuryList();
        });
        
        // Reset filters
        $('#mt-jury-reset-filters').on('click', function() {
            $('#mt-jury-search').val('');
            $('#mt-jury-filter-status').val('');
            $('#mt-jury-filter-role').val('');
            filterJuryList();
        });
    }
    
    /**
     * Filter jury list based on current filter values
     */
    function filterJuryList() {
        var searchTerm = $('#mt-jury-search').val().toLowerCase();
        var statusFilter = $('#mt-jury-filter-status').val();
        var roleFilter = $('#mt-jury-filter-role').val();
        
        $('.wp-list-table tbody tr').each(function() {
            var $row = $(this);
            var show = true;
            
            // Search filter
            if (searchTerm) {
                var text = $row.text().toLowerCase();
                if (text.indexOf(searchTerm) === -1) {
                    show = false;
                }
            }
            
            // Status filter
            if (statusFilter && show) {
                var status = $row.find('.mt-jury-status').attr('class').match(/mt-status-(\w+)/);
                if (status && status[1] !== statusFilter) {
                    show = false;
                }
            }
            
            // Role filter
            if (roleFilter && show) {
                var role = $row.find('.mt-jury-role').attr('class').match(/mt-role-(\w+)/);
                if (role && role[1] !== roleFilter) {
                    show = false;
                }
            }
            
            $row.toggle(show);
        });
    }
    
    /**
     * Initialize photo upload functionality
     */
    function initPhotoUpload() {
        var mediaUploader;
        
        $('#mt_upload_photo').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: mtJuryManagement.strings.select_photo || 'Select Profile Photo',
                button: {
                    text: mtJuryManagement.strings.use_photo || 'Use this photo'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#mt_photo_url').val(attachment.url);
                $('#mt_photo_preview').html('<img src="' + attachment.url + '" style="max-width: 150px; margin-top: 10px;">');
                $('#mt_remove_photo').show();
            });
            
            mediaUploader.open();
        });
        
        $('#mt_remove_photo').on('click', function(e) {
            e.preventDefault();
            $('#mt_photo_url').val('');
            $('#mt_photo_preview').empty();
            $(this).hide();
        });
    }
    
    /**
     * Initialize status toggle functionality
     */
    function initStatusToggle() {
        $(document).on('click', '.mt-toggle-status', function() {
            var $button = $(this);
            var juryId = $button.data('jury-id');
            var currentStatus = $button.data('current-status');
            
            $button.prop('disabled', true).addClass('mt-loading');
            
            $.ajax({
                url: mtJuryManagement.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_toggle_jury_status',
                    jury_id: juryId,
                    current_status: currentStatus,
                    nonce: mtJuryManagement.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update UI
                        var newStatus = response.data.new_status;
                        var $row = $button.closest('tr');
                        
                        // Update status badge
                        $row.find('.mt-jury-status')
                            .removeClass('mt-status-active mt-status-inactive')
                            .addClass('mt-status-' + newStatus)
                            .text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                        
                        // Update button
                        $button.data('current-status', newStatus)
                            .text(newStatus === 'active' ? 'Deactivate' : 'Activate');
                        
                        showMessage('success', response.data.message);
                    } else {
                        showMessage('error', response.data || mtJuryManagement.strings.error);
                    }
                },
                error: function() {
                    showMessage('error', mtJuryManagement.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('mt-loading');
                }
            });
        });
    }
    
    /**
     * Initialize bulk actions
     */
    function initBulkActions() {
        $('#mt-apply-bulk-action').on('click', function() {
            var action = $('#mt-jury-bulk-action').val();
            if (!action) {
                return;
            }
            
            var selectedIds = [];
            $('input[name="jury_members[]"]:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                showMessage('warning', 'Please select at least one jury member.');
                return;
            }
            
            if (action === 'delete' && !confirm(mtJuryManagement.strings.confirm_bulk_delete)) {
                return;
            }
            
            performBulkAction(action, selectedIds);
        });
    }
    
    /**
     * Perform bulk action
     */
    function performBulkAction(action, ids) {
        var $button = $('#mt-apply-bulk-action');
        $button.prop('disabled', true).addClass('mt-loading');
        
        $.ajax({
            url: mtJuryManagement.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_bulk_jury_action',
                bulk_action: action,
                jury_ids: ids,
                nonce: mtJuryManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    // Reload the list after 1 second
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    showMessage('error', response.data || mtJuryManagement.strings.error);
                }
            },
            error: function() {
                showMessage('error', mtJuryManagement.strings.error);
            },
            complete: function() {
                $button.prop('disabled', false).removeClass('mt-loading');
            }
        });
    }
    
    /**
     * Initialize invitation functionality
     */
    function initInvitations() {
        // Single invitation
        $(document).on('click', '.mt-send-invitation', function() {
            var $button = $(this);
            var juryId = $button.data('jury-id');
            
            $button.prop('disabled', true).text(mtJuryManagement.strings.sending);
            
            $.ajax({
                url: mtJuryManagement.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_send_jury_invitation',
                    jury_id: juryId,
                    nonce: mtJuryManagement.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                    } else {
                        showMessage('error', response.data || mtJuryManagement.strings.error);
                    }
                },
                error: function() {
                    showMessage('error', mtJuryManagement.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Send Invitation');
                }
            });
        });
        
        // Bulk invitations
        $('#mt-send-bulk-invitations').on('click', function() {
            var $button = $(this);
            var subject = $('#invitation-subject').val();
            var message = $('#invitation-message').val();
            
            if (!subject || !message) {
                showMessage('warning', 'Please fill in both subject and message fields.');
                return;
            }
            
            $button.prop('disabled', true).text(mtJuryManagement.strings.sending);
            
            $.ajax({
                url: mtJuryManagement.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_send_bulk_invitations',
                    subject: subject,
                    message: message,
                    nonce: mtJuryManagement.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                        // Reload invitation history
                        loadInvitationHistory();
                    } else {
                        showMessage('error', response.data || mtJuryManagement.strings.error);
                    }
                },
                error: function() {
                    showMessage('error', mtJuryManagement.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Send Invitations');
                }
            });
        });
    }
    
    /**
     * Initialize assignment functionality
     */
    function initAssignments() {
        // Auto-assign candidates
        $('#mt-auto-assign').on('click', function() {
            var $button = $(this);
            
            if (!confirm('This will automatically assign candidates to jury members. Continue?')) {
                return;
            }
            
            $button.prop('disabled', true).addClass('mt-loading');
            
            $.ajax({
                url: mtJuryManagement.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_auto_assign_candidates',
                    nonce: mtJuryManagement.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                        // Reload assignments grid
                        loadAssignmentsGrid();
                    } else {
                        showMessage('error', response.data || mtJuryManagement.strings.error);
                    }
                },
                error: function() {
                    showMessage('error', mtJuryManagement.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('mt-loading');
                }
            });
        });
        
        // Balance assignments
        $('#mt-balance-assignments').on('click', function() {
            var $button = $(this);
            
            if (!confirm('This will redistribute assignments to balance the workload. Continue?')) {
                return;
            }
            
            $button.prop('disabled', true).addClass('mt-loading');
            
            $.ajax({
                url: mtJuryManagement.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_balance_assignments',
                    nonce: mtJuryManagement.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                        loadAssignmentsGrid();
                    } else {
                        showMessage('error', response.data || mtJuryManagement.strings.error);
                    }
                },
                error: function() {
                    showMessage('error', mtJuryManagement.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('mt-loading');
                }
            });
        });
        
        // Clear assignments
        $('#mt-clear-assignments').on('click', function() {
            var $button = $(this);
            
            if (!confirm('This will remove ALL candidate assignments. This action cannot be undone. Continue?')) {
                return;
            }
            
            $button.prop('disabled', true).addClass('mt-loading');
            
            $.ajax({
                url: mtJuryManagement.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_clear_all_assignments',
                    nonce: mtJuryManagement.nonce
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                        loadAssignmentsGrid();
                    } else {
                        showMessage('error', response.data || mtJuryManagement.strings.error);
                    }
                },
                error: function() {
                    showMessage('error', mtJuryManagement.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).removeClass('mt-loading');
                }
            });
        });
        
        // Toggle individual assignment
        $(document).on('click', '.mt-assignment-cell', function() {
            var $cell = $(this);
            var candidateId = $cell.data('candidate-id');
            var juryId = $cell.data('jury-id');
            var isAssigned = $cell.hasClass('assigned');
            
            $cell.addClass('mt-loading');
            
            $.ajax({
                url: mtJuryManagement.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_toggle_assignment',
                    candidate_id: candidateId,
                    jury_id: juryId,
                    assign: !isAssigned,
                    nonce: mtJuryManagement.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (isAssigned) {
                            $cell.removeClass('assigned');
                        } else {
                            $cell.addClass('assigned');
                        }
                        updateAssignmentCounts();
                    } else {
                        showMessage('error', response.data || mtJuryManagement.strings.error);
                    }
                },
                error: function() {
                    showMessage('error', mtJuryManagement.strings.error);
                },
                complete: function() {
                    $cell.removeClass('mt-loading');
                }
            });
        });
    }
    
    /**
     * Initialize import/export functionality
     */
    function initImportExport() {
        // Export functionality
        $('#mt-export-jury-data').on('click', function() {
            var $button = $(this);
            var format = $('#mt-export-format').val();
            
            $button.prop('disabled', true).text(mtJuryManagement.strings.exporting);
            
            $.ajax({
                url: mtJuryManagement.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_export_jury_data',
                    format: format,
                    nonce: mtJuryManagement.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create download link
                        var blob = new Blob([response.data.content], { type: response.data.mime_type });
                        var url = window.URL.createObjectURL(blob);
                        var a = document.createElement('a');
                        a.href = url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                        
                        showMessage('success', 'Export completed successfully!');
                    } else {
                        showMessage('error', response.data || mtJuryManagement.strings.error);
                    }
                },
                error: function() {
                    showMessage('error', mtJuryManagement.strings.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text('Export Data');
                }
            });
        });
        
        // Import drag and drop
        var $dropzone = $('.mt-import-dropzone');
        if ($dropzone.length) {
            $dropzone.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('dragover');
            });
            
            $dropzone.on('dragleave dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
            });
            
            $dropzone.on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('dragover');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    handleFileImport(files[0]);
                }
            });
            
            $dropzone.on('click', function() {
                $('#mt-import-file').click();
            });
            
            $('#mt-import-file').on('change', function() {
                if (this.files.length > 0) {
                    handleFileImport(this.files[0]);
                }
            });
        }
    }
    
    /**
     * Handle file import
     */
    function handleFileImport(file) {
        var formData = new FormData();
        formData.append('action', 'mt_import_jury_data');
        formData.append('import_file', file);
        formData.append('nonce', mtJuryManagement.nonce);
        
        showMessage('info', mtJuryManagement.strings.importing);
        
        $.ajax({
            url: mtJuryManagement.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.data.message);
                    // Show import summary
                    if (response.data.summary) {
                        var summary = '<h4>Import Summary:</h4><ul>';
                        summary += '<li>Total Records: ' + response.data.summary.total + '</li>';
                        summary += '<li>Imported: ' + response.data.summary.imported + '</li>';
                        summary += '<li>Updated: ' + response.data.summary.updated + '</li>';
                        summary += '<li>Skipped: ' + response.data.summary.skipped + '</li>';
                        summary += '<li>Errors: ' + response.data.summary.errors + '</li>';
                        summary += '</ul>';
                        showModal('Import Complete', summary);
                    }
                } else {
                    showMessage('error', response.data || mtJuryManagement.strings.error);
                }
            },
            error: function() {
                showMessage('error', mtJuryManagement.strings.error);
            }
        });
    }
    
    /**
     * Load assignments grid via AJAX
     */
    function loadAssignmentsGrid() {
        $('#mt-jury-assignments-grid').addClass('mt-loading');
        
        $.ajax({
            url: mtJuryManagement.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_load_assignments_grid',
                nonce: mtJuryManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#mt-jury-assignments-grid').html(response.data.html);
                }
            },
            complete: function() {
                $('#mt-jury-assignments-grid').removeClass('mt-loading');
            }
        });
    }
    
    /**
     * Load invitation history
     */
    function loadInvitationHistory() {
        $('.mt-invitation-history').addClass('mt-loading');
        
        $.ajax({
            url: mtJuryManagement.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_load_invitation_history',
                nonce: mtJuryManagement.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.mt-invitation-history').html(response.data.html);
                }
            },
            complete: function() {
                $('.mt-invitation-history').removeClass('mt-loading');
            }
        });
    }
    
    /**
     * Update assignment counts
     */
    function updateAssignmentCounts() {
        $('.mt-assignments-count').each(function() {
            var $count = $(this);
            var juryId = $count.closest('tr').data('jury-id');
            var assigned = $('.mt-assignment-cell.assigned[data-jury-id="' + juryId + '"]').length;
            var max = parseInt($count.text().split('/')[1]);
            $count.text(assigned + ' / ' + max);
        });
    }
    
    /**
     * Show message
     */
    function showMessage(type, message) {
        var $message = $('<div class="mt-message ' + type + '">' + message + '</div>');
        $('.mt-jury-management-wrap').prepend($message);
        
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Show modal
     */
    function showModal(title, content) {
        var modal = '<div class="mt-modal"><div class="mt-modal-content">';
        modal += '<div class="mt-modal-header"><h3>' + title + '</h3>';
        modal += '<span class="mt-modal-close">&times;</span></div>';
        modal += '<div class="mt-modal-body">' + content + '</div>';
        modal += '</div></div>';
        
        var $modal = $(modal).appendTo('body');
        $modal.fadeIn();
        
        $modal.find('.mt-modal-close').on('click', function() {
            $modal.fadeOut(function() {
                $(this).remove();
            });
        });
        
        $modal.on('click', function(e) {
            if (e.target === this) {
                $modal.fadeOut(function() {
                    $(this).remove();
                });
            }
        });
    }
    
})(jQuery);