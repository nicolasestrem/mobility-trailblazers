/**
 * Assignment Management JavaScript
 * 
 * @package MobilityTrailblazers
 */

(function($) {
    'use strict';

    // Assignment Manager Object
    window.MTAssignmentManager = {
        // Properties
        selectedCandidates: new Set(),
        selectedJuryMember: null,
        isDragging: false,
        draggedElement: null,
        assignments: {},
        
        // Initialize
        init: function() {
            this.bindEvents();
            this.loadAssignments();
            this.initializeDragDrop();
            this.updateStatistics();
        },
        
        // Bind events
        bindEvents: function() {
            var self = this;
            
            // Search functionality
            $('#candidate-search').on('input', function() {
                self.filterCandidates($(this).val());
            });
            
            $('#jury-search').on('input', function() {
                self.filterJuryMembers($(this).val());
            });
            
            // Category filter
            $('#category-filter').on('change', function() {
                self.filterByCategory($(this).val());
            });
            
            // Selection
            $(document).on('click', '.candidate-item', function(e) {
                if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
                    $('.candidate-item.selected').removeClass('selected');
                    self.selectedCandidates.clear();
                }
                $(this).toggleClass('selected');
                var candidateId = $(this).data('candidate-id');
                if ($(this).hasClass('selected')) {
                    self.selectedCandidates.add(candidateId);
                } else {
                    self.selectedCandidates.delete(candidateId);
                }
                self.updateSelectionInfo();
            });
            
            $(document).on('click', '.jury-item', function() {
                $('.jury-item.selected').removeClass('selected');
                $(this).addClass('selected');
                self.selectedJuryMember = $(this).data('jury-id');
                self.highlightAssignments(self.selectedJuryMember);
            });
            
            // Action buttons
            $('#assign-selected').on('click', function() {
                self.assignSelected();
            });
            
            $('#clear-selection').on('click', function() {
                self.clearSelection();
            });
            
            $('#auto-assign-btn').on('click', function() {
                self.showAutoAssignModal();
            });
            
            $('#export-assignments').on('click', function() {
                self.exportAssignments();
            });
            
            // Modal actions
            $('#confirm-auto-assign').on('click', function() {
                var algorithm = $('#assignment-algorithm').val();
                self.autoAssign(algorithm);
            });
            
            $('.modal-close, .modal-cancel').on('click', function() {
                $('.modal').hide();
            });
            
            // Manual assignment fallback
            $(document).on('change', '.manual-assignment-select', function() {
                var candidateId = $(this).data('candidate-id');
                var juryId = $(this).val();
                if (juryId) {
                    self.assignCandidate(candidateId, juryId);
                }
            });
        },
        
        // Initialize drag and drop
        initializeDragDrop: function() {
            var self = this;
            
            // Make candidates draggable
            $(document).on('mousedown', '.candidate-item', function(e) {
                if (e.which !== 1) return; // Only left click
                
                self.isDragging = true;
                self.draggedElement = $(this).clone();
                self.draggedElement.addClass('dragging');
                self.draggedElement.css({
                    position: 'absolute',
                    zIndex: 1000,
                    pointerEvents: 'none',
                    opacity: 0.8
                });
                $('body').append(self.draggedElement);
                
                // Store candidate data
                self.draggedElement.data('candidate-id', $(this).data('candidate-id'));
                
                // Update position
                self.updateDragPosition(e);
                
                // Prevent text selection
                e.preventDefault();
            });
            
            // Mouse move
            $(document).on('mousemove', function(e) {
                if (self.isDragging && self.draggedElement) {
                    self.updateDragPosition(e);
                    
                    // Check if over jury member
                    var juryItem = $(e.target).closest('.jury-item');
                    if (juryItem.length) {
                        $('.jury-item').removeClass('drop-hover');
                        juryItem.addClass('drop-hover');
                    } else {
                        $('.jury-item').removeClass('drop-hover');
                    }
                }
            });
            
            // Mouse up
            $(document).on('mouseup', function(e) {
                if (self.isDragging && self.draggedElement) {
                    var juryItem = $(e.target).closest('.jury-item');
                    if (juryItem.length) {
                        var candidateId = self.draggedElement.data('candidate-id');
                        var juryId = juryItem.data('jury-id');
                        self.assignCandidate(candidateId, juryId);
                    }
                    
                    // Clean up
                    self.draggedElement.remove();
                    self.draggedElement = null;
                    self.isDragging = false;
                    $('.jury-item').removeClass('drop-hover');
                }
            });
        },
        
        // Update drag position
        updateDragPosition: function(e) {
            if (this.draggedElement) {
                this.draggedElement.css({
                    left: e.pageX - 50,
                    top: e.pageY - 20
                });
            }
        },
        
        // Load assignments
        loadAssignments: function() {
            var self = this;
            
            $.ajax({
                url: mt_assignment.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_assignments',
                    nonce: mt_assignment.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.assignments = response.data.assignments;
                        self.updateAssignmentDisplay();
                        self.updateStatistics();
                    }
                }
            });
        },
        
        // Filter candidates
        filterCandidates: function(searchTerm) {
            var term = searchTerm.toLowerCase();
            $('.candidate-item').each(function() {
                var name = $(this).find('.candidate-name').text().toLowerCase();
                var company = $(this).find('.candidate-company').text().toLowerCase();
                if (name.includes(term) || company.includes(term)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Filter jury members
        filterJuryMembers: function(searchTerm) {
            var term = searchTerm.toLowerCase();
            $('.jury-item').each(function() {
                var name = $(this).find('.jury-name').text().toLowerCase();
                var expertise = $(this).find('.jury-expertise').text().toLowerCase();
                if (name.includes(term) || expertise.includes(term)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Filter by category
        filterByCategory: function(category) {
            if (!category) {
                $('.candidate-item').show();
                return;
            }
            
            $('.candidate-item').each(function() {
                var categories = $(this).data('categories') || '';
                if (categories.includes(category)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        },
        
        // Update selection info
        updateSelectionInfo: function() {
            var count = this.selectedCandidates.size;
            $('#selection-count').text(count);
            $('#selection-info').toggle(count > 0);
            $('#assign-selected').prop('disabled', count === 0 || !this.selectedJuryMember);
        },
        
        // Clear selection
        clearSelection: function() {
            this.selectedCandidates.clear();
            $('.candidate-item.selected').removeClass('selected');
            this.updateSelectionInfo();
        },
        
        // Assign selected candidates
        assignSelected: function() {
            var self = this;
            
            if (this.selectedCandidates.size === 0 || !this.selectedJuryMember) {
                alert(mt_assignment.i18n.select_candidates_jury);
                return;
            }
            
            var candidateIds = Array.from(this.selectedCandidates);
            
            $.ajax({
                url: mt_assignment.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_assign_candidates',
                    candidate_ids: candidateIds,
                    jury_member_id: this.selectedJuryMember,
                    nonce: mt_assignment.nonce
                },
                beforeSend: function() {
                    $('#assign-selected').prop('disabled', true).text(mt_assignment.i18n.assigning);
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(response.data.message, 'success');
                        self.clearSelection();
                        self.loadAssignments();
                    } else {
                        self.showNotification(response.data.message, 'error');
                    }
                },
                complete: function() {
                    $('#assign-selected').prop('disabled', false).text(mt_assignment.i18n.assign_selected);
                }
            });
        },
        
        // Assign single candidate
        assignCandidate: function(candidateId, juryId) {
            var self = this;
            
            $.ajax({
                url: mt_assignment.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_assign_candidates',
                    candidate_ids: [candidateId],
                    jury_member_id: juryId,
                    nonce: mt_assignment.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(response.data.message, 'success');
                        self.loadAssignments();
                    } else {
                        self.showNotification(response.data.message, 'error');
                    }
                }
            });
        },
        
        // Show auto-assign modal
        showAutoAssignModal: function() {
            $('#auto-assign-modal').show();
        },
        
        // Auto assign
        autoAssign: function(algorithm) {
            var self = this;
            
            $.ajax({
                url: mt_assignment.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_auto_assign',
                    algorithm: algorithm,
                    nonce: mt_assignment.nonce
                },
                beforeSend: function() {
                    $('#confirm-auto-assign').prop('disabled', true).text(mt_assignment.i18n.processing);
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification(response.data.message, 'success');
                        $('#auto-assign-modal').hide();
                        self.loadAssignments();
                    } else {
                        self.showNotification(response.data.message, 'error');
                    }
                },
                complete: function() {
                    $('#confirm-auto-assign').prop('disabled', false).text(mt_assignment.i18n.confirm);
                }
            });
        },
        
        // Update assignment display
        updateAssignmentDisplay: function() {
            var self = this;
            
            // Update candidate items
            $('.candidate-item').each(function() {
                var candidateId = $(this).data('candidate-id');
                var assignedTo = self.assignments[candidateId];
                
                if (assignedTo) {
                    $(this).addClass('assigned');
                    $(this).find('.assignment-info').html(
                        '<span class="assigned-to">Assigned to: ' + assignedTo.jury_name + '</span>'
                    );
                } else {
                    $(this).removeClass('assigned');
                    $(this).find('.assignment-info').empty();
                }
            });
            
            // Update jury member counts
            $('.jury-item').each(function() {
                var juryId = $(this).data('jury-id');
                var count = 0;
                
                for (var candidateId in self.assignments) {
                    if (self.assignments[candidateId].jury_id == juryId) {
                        count++;
                    }
                }
                
                $(this).find('.assignment-count').text(count);
            });
        },
        
        // Highlight assignments
        highlightAssignments: function(juryId) {
            $('.candidate-item').removeClass('highlighted');
            
            for (var candidateId in this.assignments) {
                if (this.assignments[candidateId].jury_id == juryId) {
                    $('.candidate-item[data-candidate-id="' + candidateId + '"]').addClass('highlighted');
                }
            }
        },
        
        // Update statistics
        updateStatistics: function() {
            var self = this;
            
            $.ajax({
                url: mt_assignment.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_assignment_stats',
                    nonce: mt_assignment.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var stats = response.data.stats;
                        $('#total-candidates').text(stats.total_candidates);
                        $('#assigned-candidates').text(stats.assigned_candidates);
                        $('#unassigned-candidates').text(stats.unassigned_candidates);
                        $('#avg-per-jury').text(stats.avg_per_jury.toFixed(1));
                    }
                }
            });
        },
        
        // Export assignments
        exportAssignments: function() {
            window.location.href = mt_assignment.ajax_url + '?action=mt_export_assignments&nonce=' + mt_assignment.nonce;
        },
        
        // Show notification
        showNotification: function(message, type) {
            var notification = $('<div class="mt-notification ' + type + '">' + message + '</div>');
            $('body').append(notification);
            
            setTimeout(function() {
                notification.addClass('show');
            }, 10);
            
            setTimeout(function() {
                notification.removeClass('show');
                setTimeout(function() {
                    notification.remove();
                }, 300);
            }, 3000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('#mt-assignment-manager').length) {
            MTAssignmentManager.init();
        }
    });
    
})(jQuery); 