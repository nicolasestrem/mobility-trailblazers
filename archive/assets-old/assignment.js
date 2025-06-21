/**
 * Assignment Management JavaScript - Fixed Version with Manual Assignment
 * 
 * @package MobilityTrailblazers
 */

(function($) {
    'use strict';

    // Assignment Manager Object
    window.MTAssignmentManager = {
        
        // Initialize
        init: function() {
            this.bindEvents();
            this.updateStatistics();
        },
        
        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Auto-Assign button
            $('#mt-auto-assign-btn').on('click', function(e) {
                e.preventDefault();
                self.showAutoAssignModal();
            });
            
            // Clear Assignments button
            $('#mt-clear-assignments-btn').on('click', function(e) {
                e.preventDefault();
                self.clearAllAssignments();
            });
            
            // Export Assignments button
            $('#mt-export-assignments-btn').on('click', function(e) {
                e.preventDefault();
                self.exportAssignments();
            });
            
            // Manual Assignment button
            $('#mt-manual-assignment-btn').on('click', function(e) {
                e.preventDefault();
                self.showManualAssignModal();
            });
            
            // Modal actions - Auto Assign
            $('#mt-confirm-auto-assign').on('click', function(e) {
                e.preventDefault();
                self.performAutoAssign();
            });
            
            // Manual Assignment Confirm button
            $('#mt-confirm-manual-assign').on('click', function(e) {
                e.preventDefault();
                self.performManualAssign();
            });
            
            // Modal close buttons
            $('.mt-modal-close').on('click', function(e) {
                e.preventDefault();
                $(this).closest('.mt-modal').hide();
            });
            
            // Close modal when clicking outside
            $('.mt-modal').on('click', function(e) {
                if ($(e.target).hasClass('mt-modal')) {
                    $(this).hide();
                }
            });
            
            // Search functionality
            $('#mt-candidate-search').on('input', function() {
                self.filterCandidates($(this).val());
            });
            
            // Category filter
            $('#mt-category-filter').on('change', function() {
                self.filterByCategory($(this).val());
            });
            
            // Remove assignment buttons
            $(document).on('click', '.mt-remove-assignment', function(e) {
                e.preventDefault();
                var candidateId = $(this).data('candidate-id');
                var juryId = $(this).data('jury-id');
                self.removeAssignment(candidateId, juryId, $(this));
            });
        },
        
        // Show Auto-Assign Modal
        showAutoAssignModal: function() {
            $('#mt-auto-assign-modal').fadeIn(300);
        },
        
        // Show Manual Assignment Modal
        showManualAssignModal: function() {
            $('#mt-manual-assign-modal').fadeIn(300);
        },
        
        // Perform Auto Assignment
        performAutoAssign: function() {
            var self = this;
            var algorithm = $('#mt-assignment-algorithm').val();
            var candidatesPerJury = $('#mt-candidates-per-jury').val();
            var preserveExisting = $('#mt-preserve-existing').is(':checked');
            
            // Disable button during processing
            $('#mt-confirm-auto-assign').prop('disabled', true).text('Processing...');
            
            $.ajax({
                url: mt_assignment_vars.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_auto_assign',
                    algorithm: algorithm,
                    candidates_per_jury: candidatesPerJury,
                    preserve_existing: preserveExisting,
                    nonce: mt_assignment_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(
                            (response.data && response.data.message) || response.message || 'An error occurred',
                            'success'
                        );
                        $('#mt-auto-assign-modal').hide();
                        self.updateStatistics();
                        // Reload the page to show updated assignments
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showNotification(
                            (response.data && response.data.message) || response.message || 'An error occurred',
                            'error'
                        );
                    }
                },
                error: function() {
                    self.showNotification('Connection error. Please try again.', 'error');
                },
                complete: function() {
                    $('#mt-confirm-auto-assign').prop('disabled', false).text('Start Auto-Assignment');
                }
            });
        },
        
        // Perform Manual Assignment
        performManualAssign: function() {
            var self = this;
            var candidateId = $('#mt-manual-candidate').val();
            var juryIds = $('#mt-manual-jury').val();
            
            // Validation
            if (!candidateId) {
                self.showNotification('Please select a candidate', 'error');
                return;
            }
            
            if (!juryIds || juryIds.length === 0) {
                self.showNotification('Please select at least one jury member', 'error');
                return;
            }
            
            // Disable button during processing
            $('#mt-confirm-manual-assign').prop('disabled', true).text('Assigning...');
            
            $.ajax({
                url: mt_assignment_vars.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_manual_assign',
                    candidateId: candidateId,
                    jury_ids: juryIds,
                    nonce: mt_assignment_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(
                            (response.data && response.data.message) || response.message || 'Assignment saved successfully',
                            'success'
                        );
                        $('#mt-manual-assign-modal').hide();
                        // Reset form
                        $('#mt-manual-candidate').val('');
                        $('#mt-manual-jury').val([]);
                        // Reload the page to show updated assignments
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showNotification(
                            (response.data && response.data.message) || response.message || 'Failed to save assignment',
                            'error'
                        );
                    }
                },
                error: function() {
                    self.showNotification('Connection error. Please try again.', 'error');
                },
                complete: function() {
                    $('#mt-confirm-manual-assign').prop('disabled', false).text('Assign');
                }
            });
        },
        
        // Clear All Assignments
        clearAllAssignments: function() {
            var self = this;
            
            if (!confirm('Are you sure you want to clear all assignments? This action cannot be undone.')) {
                return;
            }
            
            $.ajax({
                url: mt_assignment_vars.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_clear_assignments',
                    nonce: mt_assignment_vars.nonce
                },
                beforeSend: function() {
                    $('#mt-clear-assignments-btn').prop('disabled', true).text('Clearing...');
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(
                            (response.data && response.data.message) || response.message || 'Assignments cleared successfully',
                            'success'
                        );
                        self.updateStatistics();
                        // Reload the page to show updated state
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showNotification(
                            (response.data && response.data.message) || response.message || 'Error clearing assignments',
                            'error'
                        );
                    }
                },
                error: function() {
                    self.showNotification('Connection error. Please try again.', 'error');
                },
                complete: function() {
                    $('#mt-clear-assignments-btn').prop('disabled', false).html('<span class="dashicons dashicons-dismiss"></span> Clear All Assignments');
                }
            });
        },
        
        // Export Assignments
        exportAssignments: function() {
            var self = this;
            
            // Create a temporary form for file download
            var form = $('<form>', {
                method: 'POST',
                action: mt_assignment_vars.ajaxUrl
            });
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'action',
                value: 'mt_export_assignments'
            }));
            
            form.append($('<input>', {
                type: 'hidden',
                name: 'nonce',
                value: mt_assignment_vars.nonce
            }));
            
            // Append form to body and submit
            $('body').append(form);
            form.submit();
            form.remove();
            
            self.showNotification(
                (response.data && response.data.message) || response.message || 'Export started. Download will begin shortly.',
                'success'
            );
        },
        
        // Remove single assignment
        removeAssignment: function(candidateId, juryId, button) {
            var self = this;
            
            if (!confirm('Are you sure you want to remove this assignment?')) {
                return;
            }
            
            // Disable button during processing
            button.prop('disabled', true);
            
            $.ajax({
                url: mt_assignment_vars.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_remove_assignment',
                    candidateId: candidateId,
                    jury_member_id: juryId,
                    nonce: mt_assignment_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Remove the assignment from UI
                        button.closest('.mt-assigned-candidate').fadeOut(300, function() {
                            $(this).remove();
                        });
                        self.showNotification(
                            (response.data && response.data.message) || response.message || 'Assignment removed',
                            'success'
                        );
                        self.updateStatistics();
                    } else {
                        self.showNotification(
                            (response.data && response.data.message) || response.message || 'Error removing assignment',
                            'error'
                        );
                    }
                },
                error: function() {
                    self.showNotification('Connection error. Please try again.', 'error');
                },
                complete: function() {
                    button.prop('disabled', false);
                }
            });
        },
        
        // Filter candidates by search term
        filterCandidates: function(searchTerm) {
            searchTerm = searchTerm.toLowerCase();
            
            $('.mt-candidate-item').each(function() {
                var candidateName = $(this).find('.mt-candidate-name').text().toLowerCase();
                var companyName = $(this).find('.mt-company-name').text().toLowerCase();
                
                if (candidateName.indexOf(searchTerm) > -1 || companyName.indexOf(searchTerm) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Filter by category
        filterByCategory: function(categoryId) {
            if (!categoryId) {
                $('.mt-candidate-item').show();
                return;
            }
            
            $('.mt-candidate-item').each(function() {
                if ($(this).data('categories') && $(this).data('categories').indexOf(categoryId) > -1) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Update statistics
        updateStatistics: function() {
            $.ajax({
                url: mt_assignment_vars.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_get_assignment_stats',
                    nonce: mt_assignment_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update the statistics in the UI
                        if (response.data.total_candidates !== undefined) {
                            $('.mt-stat-candidates .mt-stat-number').text(response.data.total_candidates);
                        }
                        if (response.data.assigned_candidates !== undefined) {
                            $('.mt-stat-assigned .mt-stat-number').text(response.data.assigned_candidates);
                        }
                        if (response.data.unassigned_candidates !== undefined) {
                            $('.mt-stat-unassigned .mt-stat-number').text(response.data.unassigned_candidates);
                        }
                    }
                }
            });
        },
        
        // Show notification
        showNotification: function(message, type) {
            var notification = $('<div class="mt-notification ' + type + '">' + message + '</div>');
            
            // Add notification styles if not already present
            if (!$('#mt-notification-styles').length) {
                $('head').append(`
                    <style id="mt-notification-styles">
                        .mt-notification {
                            position: fixed;
                            top: 50px;
                            right: 20px;
                            padding: 15px 20px;
                            background: #fff;
                            border-left: 4px solid;
                            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                            z-index: 100001;
                            opacity: 0;
                            transform: translateX(100%);
                            transition: all 0.3s ease;
                        }
                        .mt-notification.show {
                            opacity: 1;
                            transform: translateX(0);
                        }
                        .mt-notification.success {
                            border-color: #46b450;
                        }
                        .mt-notification.error {
                            border-color: #dc3232;
                        }
                    </style>
                `);
            }
            
            $('body').append(notification);
            
            // Show notification
            setTimeout(function() {
                notification.addClass('show');
            }, 10);
            
            // Hide notification after 5 seconds
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 5000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        // Check if we're on the assignment management page
        if ($('.wrap h1').text().includes('Assignment Management') || 
            $('#mt-auto-assign-btn').length > 0) {
            MTAssignmentManager.init();
        }
    });
    
    // Also initialize on window load for compatibility
    $(window).on('load', function() {
        // Check if we're on the assignment management page
        if ($('.wrap h1').text().includes('Assignment Management') || 
            $('#mt-auto-assign-btn').length > 0) {
            MTAssignmentManager.init();
        }
    });
    
})(jQuery);