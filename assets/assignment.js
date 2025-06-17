/**
 * Assignment Management JavaScript - Fixed Version
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
                url: mt_assignment_vars.ajax_url,
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
                        self.showNotification(response.data.message, 'success');
                        $('#mt-auto-assign-modal').hide();
                        self.updateStatistics();
                        // Reload the page to show updated assignments
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showNotification(response.data.message || 'An error occurred', 'error');
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
        
        // Clear All Assignments
        clearAllAssignments: function() {
            var self = this;
            
            if (!confirm('Are you sure you want to clear all assignments? This action cannot be undone.')) {
                return;
            }
            
            $.ajax({
                url: mt_assignment_vars.ajax_url,
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
                        self.showNotification(response.data.message, 'success');
                        self.updateStatistics();
                        // Reload the page to show updated state
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showNotification(response.data.message || 'Error clearing assignments', 'error');
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
                action: mt_assignment_vars.ajax_url
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
            
            self.showNotification('Export started. Download will begin shortly.', 'success');
        },
        
        // Filter Candidates
        filterCandidates: function(searchTerm) {
            searchTerm = searchTerm.toLowerCase();
            
            $('.candidate-item').each(function() {
                var candidateName = $(this).find('.candidate-name').text().toLowerCase();
                var candidateCompany = $(this).find('.candidate-company').text().toLowerCase();
                
                if (candidateName.includes(searchTerm) || candidateCompany.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Filter by Category
        filterByCategory: function(categoryId) {
            if (!categoryId) {
                $('.candidate-item').show();
                return;
            }
            
            $('.candidate-item').each(function() {
                if ($(this).data('category-id') == categoryId) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Update Statistics
        updateStatistics: function() {
            var self = this;
            
            $.ajax({
                url: mt_assignment_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_assignment_stats',
                    nonce: mt_assignment_vars.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Update stat numbers
                        $('.mt-stat-box').eq(0).find('.mt-stat-number').text(response.data.total_candidates || 0);
                        $('.mt-stat-box').eq(1).find('.mt-stat-number').text(response.data.total_jury_members || 0);
                        $('.mt-stat-box').eq(2).find('.mt-stat-number').text(response.data.assigned_candidates || 0);
                        $('.mt-stat-box').eq(3).find('.mt-stat-number').text(response.data.unassigned_candidates || 0);
                    }
                }
            });
        },
        
        // Show Notification
        showNotification: function(message, type) {
            // Remove existing notifications
            $('.mt-notification').remove();
            
            var notification = $('<div class="mt-notification ' + type + '">' + message + '</div>');
            
            // Add notification styles if not exists
            if (!$('#mt-notification-styles').length) {
                $('head').append(`
                    <style id="mt-notification-styles">
                        .mt-notification {
                            position: fixed;
                            top: 32px;
                            right: 20px;
                            background: #fff;
                            padding: 12px 20px;
                            border-left: 4px solid;
                            box-shadow: 0 1px 4px rgba(0,0,0,0.15);
                            z-index: 100001;
                            max-width: 300px;
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
    
    // Also initialize on WordPress admin ready
    $(window).on('load', function() {
        if ($('#mt-auto-assign-btn').length > 0 && !window.MTAssignmentManagerInitialized) {
            window.MTAssignmentManagerInitialized = true;
            MTAssignmentManager.init();
        }
    });
    
})(jQuery);