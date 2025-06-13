/* 
 * Mobility Trailblazers Assignment System JavaScript - FIXED VERSION
 * File: assets/assignment.js
 */

jQuery(document).ready(function($) {
    console.log('Assignment system initializing...');
    
    // Global variables
    let selectedCandidates = [];
    let selectedJuryMember = null;
    let allCandidates = [];
    let allJuryMembers = [];
    let currentFilters = {
        stage: '',
        category: '',
        assignment: '',
        search: ''
    };

    // Initialize the assignment interface
    function initAssignmentInterface() {
        console.log('Initializing assignment interface...');
        
        // Check if data is available
        if (typeof mt_assignment_ajax === 'undefined') {
            console.error('mt_assignment_ajax not found. WordPress localization failed.');
            showNotification('Failed to load assignment data. Please refresh the page.', 'error');
            return;
        }
        
        // Load data from WordPress
        allCandidates = mt_assignment_ajax.candidates || [];
        allJuryMembers = mt_assignment_ajax.jury_members || [];
        
        console.log('Loaded data:', {
            candidates: allCandidates.length,
            jury_members: allJuryMembers.length
        });
        
        // Validate data
        if (allCandidates.length === 0) {
            showNotification('No candidates found. Please ensure candidates are properly loaded.', 'warning');
        }
        if (allJuryMembers.length === 0) {
            showNotification('No jury members found. Please ensure jury members are properly loaded.', 'warning');
        }
        
        // Initialize interface
        renderCandidatesList();
        renderJuryMembersList();
        updateStatistics();
        bindEventHandlers();
        
        console.log('Assignment interface initialized successfully');
    }

    // Render candidates list with proper selection handling
    function renderCandidatesList() {
        // Try multiple possible container IDs for backwards compatibility
        let container = $('#mt-candidates-list');
        if (!container.length) {
            container = $('#mt-candidates-panel .mt-panel-content #mt-candidates-list');
        }
        
        if (!container.length) {
            console.error('Candidates container not found. Checked selectors: #mt-candidates-list');
            showNotification('Candidates container not found in HTML template', 'error');
            return;
        }

        console.log('Rendering candidates list...');
        container.empty();

        let filteredCandidates = filterCandidates(allCandidates);
        
        if (filteredCandidates.length === 0) {
            container.append('<div class="mt-no-results">No candidates found matching your criteria.</div>');
            return;
        }

        filteredCandidates.forEach(candidate => {
            const isSelected = selectedCandidates.includes(candidate.id);
            const juryMember = candidate.jury_member_id ? 
                allJuryMembers.find(j => j.id == candidate.jury_member_id) : null;
            
            const candidateHtml = `
                <div class="mt-candidate-item ${isSelected ? 'selected' : ''}" data-candidate-id="${candidate.id}">
                    <div class="mt-candidate-checkbox">
                        <input type="checkbox" 
                               id="candidate-${candidate.id}" 
                               ${isSelected ? 'checked' : ''}
                               data-candidate-id="${candidate.id}">
                    </div>
                    <div class="mt-candidate-info">
                        <h4>${candidate.name}</h4>
                        <p class="company">${candidate.company}</p>
                        <div class="mt-candidate-meta">
                            <span class="category">${candidate.category}</span>
                            <span class="stage">${candidate.stage}</span>
                        </div>
                        ${candidate.assigned ? 
                            `<div class="mt-assigned-to">
                                <strong>Assigned to:</strong> ${juryMember ? juryMember.name : 'Unknown'}
                            </div>` : 
                            '<div class="mt-unassigned">Not assigned</div>'
                        }
                    </div>
                </div>
            `;
            
            container.append(candidateHtml);
        });
        
        // Update selection count
        updateSelectionCount();
    }

    // Render jury members list with proper selection handling
    function renderJuryMembersList() {
        // Try multiple possible container IDs for backwards compatibility
        let container = $('#mt-jury-members-list');
        if (!container.length) {
            container = $('#mt-jury-list');
        }
        
        if (!container.length) {
            console.error('Jury members container not found. Checked IDs: #mt-jury-members-list, #mt-jury-list');
            showNotification('Jury members container not found in HTML template', 'error');
            return;
        }

        console.log('Rendering jury members list...');
        container.empty();

        allJuryMembers.forEach(jury => {
            const isSelected = selectedJuryMember === jury.id;
            const utilizationPercentage = jury.max_assignments > 0 ? 
                (jury.assignments / jury.max_assignments) * 100 : 0;
            
            const juryHtml = `
                <div class="mt-jury-item ${isSelected ? 'selected' : ''}" data-jury-id="${jury.id}">
                    <div class="mt-jury-header">
                        <h4>${jury.name}</h4>
                        <p class="position">${jury.position}</p>
                        <p class="company">${jury.company}</p>
                    </div>
                    <div class="mt-jury-stats">
                        <div class="assignments-count">
                            <strong>Assignments:</strong> ${jury.assignments}/${jury.max_assignments}
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${utilizationPercentage}%"></div>
                        </div>
                        <div class="available-slots">
                            <strong>Available:</strong> ${jury.available_slots}
                        </div>
                    </div>
                </div>
            `;
            
            container.append(juryHtml);
        });
    }

    // Filter candidates based on current filters
    function filterCandidates(candidates) {
        return candidates.filter(candidate => {
            // Stage filter
            if (currentFilters.stage && candidate.stage !== currentFilters.stage) {
                return false;
            }
            
            // Category filter
            if (currentFilters.category && candidate.category !== currentFilters.category) {
                return false;
            }
            
            // Assignment filter
            if (currentFilters.assignment === 'assigned' && !candidate.assigned) {
                return false;
            }
            if (currentFilters.assignment === 'unassigned' && candidate.assigned) {
                return false;
            }
            
            // Search filter
            if (currentFilters.search) {
                const searchTerm = currentFilters.search.toLowerCase();
                const searchableText = `${candidate.name} ${candidate.company}`.toLowerCase();
                if (!searchableText.includes(searchTerm)) {
                    return false;
                }
            }
            
            return true;
        });
    }

    // Bind event handlers
    function bindEventHandlers() {
        console.log('Binding event handlers...');
        
        // Candidate selection
        $(document).on('change', '.mt-candidate-item input[type="checkbox"]', function() {
            const candidateId = parseInt($(this).data('candidate-id'));
            toggleCandidateSelection(candidateId);
        });
        
        // Candidate item click (alternative to checkbox)
        $(document).on('click', '.mt-candidate-item', function(e) {
            if (!$(e.target).is('input[type="checkbox"]')) {
                const checkbox = $(this).find('input[type="checkbox"]');
                checkbox.prop('checked', !checkbox.prop('checked')).trigger('change');
            }
        });
        
        // Jury member selection
        $(document).on('click', '.mt-jury-item', function() {
            const juryId = parseInt($(this).data('jury-id'));
            selectJuryMember(juryId);
        });
        
        // Filter controls
        $('#mt-stage-filter').on('change', function() {
            currentFilters.stage = $(this).val();
            renderCandidatesList();
        });
        
        $('#mt-category-filter').on('change', function() {
            currentFilters.category = $(this).val();
            renderCandidatesList();
        });
        
        $('#mt-assignment-filter').on('change', function() {
            currentFilters.assignment = $(this).val();
            renderCandidatesList();
        });
        
        $('#mt-search-input').on('input', function() {
            currentFilters.search = $(this).val();
            renderCandidatesList();
        });
        
        // Action buttons
        $('#mt-select-all-btn').on('click', selectAllCandidates);
        $('#mt-clear-selection-btn').on('click', clearSelection);
        $('#mt-manual-assign-btn').on('click', performManualAssignment);
        $('#mt-auto-assign-btn').on('click', showAutoAssignModal);
        $('#mt-clear-all-btn').on('click', clearAllAssignments);
        
        console.log('Event handlers bound successfully');
    }

    // Toggle candidate selection
    function toggleCandidateSelection(candidateId) {
        const index = selectedCandidates.indexOf(candidateId);
        if (index > -1) {
            selectedCandidates.splice(index, 1);
        } else {
            selectedCandidates.push(candidateId);
        }
        
        // Update visual selection
        const candidateElement = $(`.mt-candidate-item[data-candidate-id="${candidateId}"]`);
        candidateElement.toggleClass('selected');
        
        // Update selection count and button states
        updateSelectionCount();
        updateActionButtons();
        
        console.log('Selected candidates:', selectedCandidates);
    }

    // Select jury member
    function selectJuryMember(juryId) {
        selectedJuryMember = selectedJuryMember === juryId ? null : juryId;
        
        // Update visual selection
        $('.mt-jury-item').removeClass('selected');
        if (selectedJuryMember) {
            $(`.mt-jury-item[data-jury-id="${selectedJuryMember}"]`).addClass('selected');
        }
        
        updateActionButtons();
        
        console.log('Selected jury member:', selectedJuryMember);
    }

    // Update selection count display
    function updateSelectionCount() {
        const count = selectedCandidates.length;
        $('.mt-selected-count').text(count);
        
        // Update candidate counter in header
        if ($('#mt-candidates-header .count').length) {
            $('#mt-candidates-header .count').text(`(${count} selected)`);
        }
    }

    // Update action button states
    function updateActionButtons() {
        const hasSelectedCandidates = selectedCandidates.length > 0;
        const hasSelectedJury = selectedJuryMember !== null;
        
        // Enable/disable manual assign button
        $('#mt-manual-assign-btn').prop('disabled', !hasSelectedCandidates || !hasSelectedJury);
        
        // Enable/disable clear selection button
        $('#mt-clear-selection-btn').prop('disabled', !hasSelectedCandidates && !hasSelectedJury);
    }

    // Select all visible candidates
    function selectAllCandidates() {
        const visibleCandidates = $('.mt-candidate-item:visible');
        selectedCandidates = [];
        
        visibleCandidates.each(function() {
            const candidateId = parseInt($(this).data('candidate-id'));
            selectedCandidates.push(candidateId);
            $(this).addClass('selected');
            $(this).find('input[type="checkbox"]').prop('checked', true);
        });
        
        updateSelectionCount();
        updateActionButtons();
        
        showNotification(`Selected ${selectedCandidates.length} candidates`, 'success');
    }

    // Clear all selections
    function clearSelection() {
        selectedCandidates = [];
        selectedJuryMember = null;
        
        $('.mt-candidate-item').removeClass('selected');
        $('.mt-candidate-item input[type="checkbox"]').prop('checked', false);
        $('.mt-jury-item').removeClass('selected');
        
        updateSelectionCount();
        updateActionButtons();
        
        showNotification('Selection cleared', 'info');
    }

    // Perform manual assignment
    function performManualAssignment() {
        if (selectedCandidates.length === 0) {
            showNotification('Please select at least one candidate', 'error');
            return;
        }
        
        if (!selectedJuryMember) {
            showNotification('Please select a jury member', 'error');
            return;
        }
        
        const juryMember = allJuryMembers.find(j => j.id === selectedJuryMember);
        if (!juryMember) {
            showNotification('Invalid jury member selected', 'error');
            return;
        }
        
        // Check capacity
        const newTotal = juryMember.assignments + selectedCandidates.length;
        if (newTotal > juryMember.max_assignments) {
            const confirmMessage = `This assignment will give ${juryMember.name} ${newTotal} candidates, ` +
                `exceeding their limit of ${juryMember.max_assignments}. Continue anyway?`;
            
            if (!confirm(confirmMessage)) {
                return;
            }
        }
        
        // Prepare AJAX data
        const data = {
            action: 'mt_assign_candidates',
            candidate_ids: selectedCandidates,
            jury_member_id: selectedJuryMember,
            nonce: mt_assignment_ajax.nonce
        };
        
        // Show loading state
        const assignButton = $('#mt-manual-assign-btn');
        const originalText = assignButton.text();
        assignButton.prop('disabled', true).text('Assigning...');
        
        // Send AJAX request
        $.post(mt_assignment_ajax.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Update local data
                    selectedCandidates.forEach(candidateId => {
                        const candidate = allCandidates.find(c => c.id === candidateId);
                        if (candidate) {
                            candidate.assigned = true;
                            candidate.jury_member_id = selectedJuryMember;
                        }
                    });
                    
                    // Update jury member assignment count
                    juryMember.assignments += selectedCandidates.length;
                    juryMember.available_slots = Math.max(0, juryMember.max_assignments - juryMember.assignments);
                    
                    // Refresh displays
                    clearSelection();
                    renderCandidatesList();
                    renderJuryMembersList();
                    updateStatistics();
                } else {
                    showNotification(response.data.message || 'Assignment failed', 'error');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                showNotification('Network error. Please try again.', 'error');
            })
            .always(function() {
                // Restore button state
                assignButton.prop('disabled', false).text(originalText);
                updateActionButtons();
            });
    }

    // Clear all assignments
    function clearAllAssignments() {
        if (!confirm('Are you sure you want to clear ALL assignments? This cannot be undone.')) {
            return;
        }
        
        const data = {
            action: 'mt_clear_all_assignments',
            nonce: mt_assignment_ajax.nonce
        };
        
        // Show loading state
        const clearButton = $('#mt-clear-all-btn');
        const originalText = clearButton.text();
        clearButton.prop('disabled', true).text('Clearing...');
        
        $.post(mt_assignment_ajax.ajax_url, data)
            .done(function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Update local data
                    allCandidates.forEach(candidate => {
                        candidate.assigned = false;
                        candidate.jury_member_id = null;
                    });
                    
                    allJuryMembers.forEach(jury => {
                        jury.assignments = 0;
                        jury.available_slots = jury.max_assignments;
                    });
                    
                    // Refresh displays
                    clearSelection();
                    renderCandidatesList();
                    renderJuryMembersList();
                    updateStatistics();
                } else {
                    showNotification(response.data.message || 'Failed to clear assignments', 'error');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('AJAX error:', status, error);
                showNotification('Network error. Please try again.', 'error');
            })
            .always(function() {
                clearButton.prop('disabled', false).text(originalText);
            });
    }

    // Show auto-assign modal
    function showAutoAssignModal() {
        // Implementation for auto-assign modal
        showNotification('Auto-assign feature coming soon!', 'info');
    }

    // Update statistics display
    function updateStatistics() {
        const totalCandidates = allCandidates.length;
        const assignedCandidates = allCandidates.filter(c => c.assigned).length;
        const totalJury = allJuryMembers.length;
        const completionRate = totalCandidates > 0 ? (assignedCandidates / totalCandidates) * 100 : 0;
        const avgPerJury = totalJury > 0 ? assignedCandidates / totalJury : 0;
        
        // Update statistics display
        $('.mt-stat-total-candidates').text(totalCandidates);
        $('.mt-stat-assigned-candidates').text(assignedCandidates);
        $('.mt-stat-completion-rate').text(completionRate.toFixed(1) + '%');
        $('.mt-stat-avg-per-jury').text(avgPerJury.toFixed(1));
    }

    // Show notification
    function showNotification(message, type = 'info') {
        console.log(`[${type.toUpperCase()}] ${message}`);
        
        // Create notification element
        const notification = $(`
            <div class="mt-notification mt-notification-${type}">
                <span class="message">${message}</span>
                <button class="close-btn">&times;</button>
            </div>
        `);
        
        // Add to notification container or create one
        let container = $('#mt-notifications');
        if (!container.length) {
            container = $('<div id="mt-notifications"></div>');
            $('body').append(container);
        }
        
        container.append(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.fadeOut(() => notification.remove());
        }, 5000);
        
        // Manual close
        notification.find('.close-btn').on('click', () => {
            notification.fadeOut(() => notification.remove());
        });
    }

    // Initialize when DOM is ready
    initAssignmentInterface();
    
    // Make functions available globally for debugging
    window.mtAssignment = {
        selectedCandidates: () => selectedCandidates,
        selectedJuryMember: () => selectedJuryMember,
        allCandidates: () => allCandidates,
        allJuryMembers: () => allJuryMembers,
        refresh: () => {
            renderCandidatesList();
            renderJuryMembersList();
            updateStatistics();
        }
    };
    
    console.log('Assignment system ready');
});