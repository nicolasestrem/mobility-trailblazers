/* Mobility Trailblazers Assignment System JavaScript */

jQuery(document).ready(function($) {
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
        
        // Load data from WordPress localized script
        if (typeof mt_assignment_ajax !== 'undefined') {
            console.log('mt_assignment_ajax found:', mt_assignment_ajax);
            allCandidates = mt_assignment_ajax.candidates || [];
            allJuryMembers = mt_assignment_ajax.jury_members || [];
            
            console.log('Loaded candidates from WordPress:', allCandidates.length);
            console.log('Loaded jury members from WordPress:', allJuryMembers.length);
            
            // Validate data structure
            if (allCandidates.length === 0) {
                showNotification('No candidates found. Please ensure candidates are properly loaded in WordPress.', 'warning');
            }
            if (allJuryMembers.length === 0) {
                showNotification('No jury members found. Please ensure jury members are properly loaded in WordPress.', 'warning');
            }
        } else {
            console.error('mt_assignment_ajax not found. Check if WordPress localization is working.');
            showNotification('Failed to load assignment data. Please refresh the page.', 'error');
            return;
        }

        // Check if containers exist
        const candidatesContainer = $('#mt-candidates-list');
        const juryContainer = $('#mt-jury-list');
        
        console.log('Candidates container exists:', candidatesContainer.length > 0);
        console.log('Jury container exists:', juryContainer.length > 0);

        renderCandidates();
        renderJuryMembers();
        updateStatistics();
        bindEventHandlers();
    }

    // Render candidates list
    function renderCandidates() {
        console.log('Rendering candidates...');
        const container = $('#mt-candidates-list');
        if (!container.length) {
            console.error('Candidates container not found!');
            return;
        }

        container.empty();

        let filteredCandidates = filterCandidates(allCandidates);
        console.log('Filtered candidates:', filteredCandidates.length);
        
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
                    <div class="mt-candidate-name">${escapeHtml(candidate.name)}</div>
                    <div class="mt-candidate-company">${escapeHtml(candidate.company || '')}</div>
                    <div class="mt-candidate-position">${escapeHtml(candidate.position || '')}</div>
                    <div class="mt-candidate-category">${formatCategory(candidate.category)}</div>
                    <span class="mt-assignment-indicator ${candidate.assigned ? 'assigned' : 'unassigned'}">
                        ${candidate.assigned ? `Assigned${juryMember ? ' to ' + juryMember.name : ''}` : 'Unassigned'}
                    </span>
                </div>
            `;
            
            container.append(candidateHtml);
        });

        // Update candidates count
        $('.mt-candidates-count').text(filteredCandidates.length);
        console.log('Candidates rendered successfully');
    }

    // Render jury members list
    function renderJuryMembers() {
        console.log('Rendering jury members...');
        const container = $('#mt-jury-list');
        if (!container.length) {
            console.error('Jury container not found!');
            return;
        }

        container.empty();

        allJuryMembers.forEach(jury => {
            const isSelected = selectedJuryMember === jury.id;
            const progressPercent = (jury.assignments / jury.max_assignments) * 100;
            
            const juryHtml = `
                <div class="mt-jury-item ${isSelected ? 'active' : ''}" data-jury-id="${jury.id}">
                    <div class="mt-jury-name">${escapeHtml(jury.name)}</div>
                    <div class="mt-jury-position">${escapeHtml(jury.position || '')}</div>
                    <div class="mt-jury-expertise">${escapeHtml(jury.expertise || '')}</div>
                    ${jury.role === 'president' ? '<span class="mt-jury-role president">President</span>' : ''}
                    ${jury.role === 'vice_president' ? '<span class="mt-jury-role vice-president">Vice President</span>' : ''}
                    <div class="mt-jury-stats">
                        Assignments: <strong>${jury.assignments}/${jury.max_assignments}</strong>
                        <div class="mt-progress-bar">
                            <div class="mt-progress-fill" style="width: ${Math.min(progressPercent, 100)}%"></div>
                        </div>
                    </div>
                </div>
            `;
            
            container.append(juryHtml);
        });
        console.log('Jury members rendered successfully');
    }

    // Filter candidates based on current filters
    function filterCandidates(candidates) {
        return candidates.filter(candidate => {
            // Stage filter (if implemented)
            if (currentFilters.stage && candidate.stage !== currentFilters.stage) {
                return false;
            }
            
            // Category filter
            if (currentFilters.category && candidate.category !== currentFilters.category) {
                return false;
            }
            
            // Assignment status filter
            if (currentFilters.assignment) {
                if (currentFilters.assignment === 'assigned' && !candidate.assigned) {
                    return false;
                }
                if (currentFilters.assignment === 'unassigned' && candidate.assigned) {
                    return false;
                }
            }
            
            // Search filter
            if (currentFilters.search) {
                const searchTerm = currentFilters.search.toLowerCase();
                const searchableText = `${candidate.name} ${candidate.company} ${candidate.position}`.toLowerCase();
                if (!searchableText.includes(searchTerm)) {
                    return false;
                }
            }
            
            return true;
        });
    }

    // Update statistics
    function updateStatistics() {
        const totalCandidates = allCandidates.length;
        const totalJury = allJuryMembers.length;
        const assignedCandidates = allCandidates.filter(c => c.assigned).length;
        const completionRate = totalCandidates > 0 ? (assignedCandidates / totalCandidates * 100).toFixed(1) : 0;
        const avgPerJury = totalJury > 0 ? (assignedCandidates / totalJury).toFixed(1) : 0;

        $('.mt-stat-total-candidates').text(totalCandidates);
        $('.mt-stat-total-jury').text(totalJury);
        $('.mt-stat-assigned-count').text(assignedCandidates);
        $('.mt-stat-completion-rate').text(completionRate + '%');
        $('.mt-stat-avg-per-jury').text(avgPerJury);
    }

    // Bind event handlers
    function bindEventHandlers() {
        // Candidate selection
        $(document).on('click', '.mt-candidate-item', function() {
            const candidateId = parseInt($(this).data('candidate-id'));
            toggleCandidateSelection(candidateId);
        });

        // Jury member selection
        $(document).on('click', '.mt-jury-item', function() {
            const juryId = parseInt($(this).data('jury-id'));
            selectJuryMember(juryId);
        });

        // Search functionality
        $('#mt-candidates-search').on('input', function() {
            currentFilters.search = $(this).val();
            renderCandidates();
        });

        $('#mt-jury-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('.mt-jury-item').each(function() {
                const name = $(this).find('.mt-jury-name').text().toLowerCase();
                $(this).toggle(name.includes(searchTerm));
            });
        });

        // Filter controls
        $('#mt-stage-filter').on('change', function() {
            currentFilters.stage = $(this).val();
            renderCandidates();
        });

        $('#mt-category-filter').on('change', function() {
            currentFilters.category = $(this).val();
            renderCandidates();
        });

        $('#mt-assignment-filter').on('change', function() {
            currentFilters.assignment = $(this).val();
            renderCandidates();
        });

        // Filter tags
        $(document).on('click', '.mt-filter-tag', function() {
            $('.mt-filter-tag').removeClass('active');
            $(this).addClass('active');
            
            const category = $(this).data('category') || '';
            currentFilters.category = category;
            renderCandidates();
        });

        // Button actions
        $('#mt-select-all-candidates').on('click', selectAllCandidates);
        $('#mt-clear-selection').on('click', clearSelection);
        $('#mt-auto-assign-btn').on('click', openAutoAssignModal);
        $('#mt-manual-assign-btn').on('click', performManualAssignment);
        $('#mt-export-btn').on('click', exportAssignments);

        // Modal controls
        $('#mt-auto-assign-modal .mt-close-btn').on('click', closeAutoAssignModal);
        $('#mt-execute-auto-assign').on('click', executeAutoAssignment);

        // Algorithm selection
        $(document).on('click', '.mt-algorithm-option', function() {
            $('.mt-algorithm-option').removeClass('selected');
            $(this).addClass('selected');
        });

        // Close modal on outside click
        $('#mt-auto-assign-modal').on('click', function(e) {
            if (e.target === this) {
                closeAutoAssignModal();
            }
        });
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
        $(`.mt-candidate-item[data-candidate-id="${candidateId}"]`).toggleClass('selected');
        
        // Update selection count
        updateSelectionInfo();
    }

    // Select jury member
    function selectJuryMember(juryId) {
        selectedJuryMember = selectedJuryMember === juryId ? null : juryId;
        
        // Update visual selection
        $('.mt-jury-item').removeClass('active');
        if (selectedJuryMember) {
            $(`.mt-jury-item[data-jury-id="${selectedJuryMember}"]`).addClass('active');
        }
        
        updateSelectionInfo();
    }

    // Update selection information
    function updateSelectionInfo() {
        const selectedCount = selectedCandidates.length;
        const selectedJury = selectedJuryMember ? 
            allJuryMembers.find(j => j.id === selectedJuryMember) : null;
        
        $('.mt-selected-candidates-count').text(selectedCount);
        $('.mt-selected-jury-name').text(selectedJury ? selectedJury.name : 'None');
        
        // Enable/disable manual assign button
        $('#mt-manual-assign-btn').prop('disabled', selectedCount === 0 || !selectedJuryMember);
    }

    // Select all candidates
    function selectAllCandidates() {
        const visibleCandidates = $('.mt-candidate-item:visible');
        selectedCandidates = [];
        
        visibleCandidates.each(function() {
            const candidateId = parseInt($(this).data('candidate-id'));
            selectedCandidates.push(candidateId);
            $(this).addClass('selected');
        });
        
        updateSelectionInfo();
    }

    // Clear selection
    function clearSelection() {
        selectedCandidates = [];
        selectedJuryMember = null;
        $('.mt-candidate-item').removeClass('selected');
        $('.mt-jury-item').removeClass('active');
        updateSelectionInfo();
    }

    // Open auto-assign modal
    function openAutoAssignModal() {
        $('#mt-auto-assign-modal').addClass('show');
    }

    // Close auto-assign modal
    function closeAutoAssignModal() {
        $('#mt-auto-assign-modal').removeClass('show');
    }

    // Perform manual assignment
    function performManualAssignment() {
        if (selectedCandidates.length === 0 || !selectedJuryMember) {
            showNotification('Please select candidates and a jury member.', 'error');
            return;
        }

        const juryMember = allJuryMembers.find(j => j.id === selectedJuryMember);
        if (!juryMember) {
            showNotification('Invalid jury member selected.', 'error');
            return;
        }

        // Check if jury member would exceed their limit
        const currentAssignments = juryMember.assignments;
        const newAssignments = selectedCandidates.length;
        if (currentAssignments + newAssignments > juryMember.max_assignments) {
            const confirm = window.confirm(
                `This assignment would give ${juryMember.name} ${currentAssignments + newAssignments} candidates, ` +
                `exceeding their limit of ${juryMember.max_assignments}. Continue anyway?`
            );
            if (!confirm) return;
        }

        // Prepare data for AJAX request
        const assignmentData = {
            action: 'mt_assign_candidates',
            candidate_ids: selectedCandidates,
            jury_member_id: selectedJuryMember,
            nonce: mt_assignment_ajax.nonce
        };

        // Show loading state
        $('#mt-manual-assign-btn').prop('disabled', true).text('Assigning...');

        // Send AJAX request
        $.post(mt_assignment_ajax.ajax_url, assignmentData)
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
                    
                    // Refresh displays
                    clearSelection();
                    renderCandidates();
                    renderJuryMembers();
                    updateStatistics();
                } else {
                    showNotification(response.data.message || 'Assignment failed.', 'error');
                }
            })
            .fail(function() {
                showNotification('Network error. Please try again.', 'error');
            })
            .always(function() {
                $('#mt-manual-assign-btn').prop('disabled', false).text('Assign Selected');
            });
    }

    // Execute auto assignment
    function executeAutoAssignment() {
        const candidatesPerJury = parseInt($('#mt-candidates-per-jury').val()) || 10;
        const algorithm = $('.mt-algorithm-option.selected').data('algorithm') || 'balanced';
        const balanceCategories = $('#mt-balance-categories').is(':checked');
        const matchExpertise = $('#mt-match-expertise').is(':checked');
        const clearExisting = $('#mt-clear-existing').is(':checked');

        // Validate input
        if (candidatesPerJury < 1 || candidatesPerJury > 50) {
            showNotification('Please enter a valid number of candidates per jury member (1-50).', 'error');
            return;
        }

        // Show loading state
        $('#mt-assignment-loading').addClass('show');
        $('#mt-execute-auto-assign').prop('disabled', true);

        // Prepare data for AJAX request
        const assignmentData = {
            action: 'mt_auto_assign',
            candidates_per_jury: candidatesPerJury,
            algorithm: algorithm,
            balance_categories: balanceCategories,
            match_expertise: matchExpertise,
            clear_existing: clearExisting,
            nonce: mt_assignment_ajax.nonce
        };

        // Send AJAX request
        $.post(mt_assignment_ajax.ajax_url, assignmentData)
            .done(function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Reload data from server or simulate update
                    if (clearExisting) {
                        // Reset all assignments
                        allCandidates.forEach(candidate => {
                            candidate.assigned = false;
                            candidate.jury_member_id = null;
                        });
                        allJuryMembers.forEach(jury => {
                            jury.assignments = 0;
                        });
                    }
                    
                    // Simulate assignment for demo (replace with actual data reload)
                    simulateAutoAssignment(candidatesPerJury, algorithm);
                    
                    // Refresh displays
                    clearSelection();
                    renderCandidates();
                    renderJuryMembers();
                    updateStatistics();
                    closeAutoAssignModal();
                } else {
                    showNotification(response.data.message || 'Auto-assignment failed.', 'error');
                }
            })
            .fail(function() {
                showNotification('Network error. Please try again.', 'error');
            })
            .always(function() {
                $('#mt-assignment-loading').removeClass('show');
                $('#mt-execute-auto-assign').prop('disabled', false);
            });
    }

    // Simulate auto assignment for demo purposes
    function simulateAutoAssignment(candidatesPerJury, algorithm) {
        const unassignedCandidates = allCandidates.filter(c => !c.assigned);
        let juryIndex = 0;
        
        unassignedCandidates.forEach((candidate, index) => {
            const jury = allJuryMembers[juryIndex];
            
            if (jury.assignments < candidatesPerJury) {
                candidate.assigned = true;
                candidate.jury_member_id = jury.id;
                jury.assignments++;
            }
            
            // Move to next jury member when current one is full
            if (jury.assignments >= candidatesPerJury) {
                juryIndex = (juryIndex + 1) % allJuryMembers.length;
            }
        });
    }

    // Export assignments
    function exportAssignments() {
        const assignmentData = {
            candidates: allCandidates,
            juryMembers: allJuryMembers,
            assignments: allCandidates.filter(c => c.assigned).map(c => ({
                candidateId: c.id,
                candidateName: c.name,
                juryMemberId: c.jury_member_id,
                juryMemberName: allJuryMembers.find(j => j.id === c.jury_member_id)?.name
            })),
            exportDate: new Date().toISOString(),
            statistics: {
                totalCandidates: allCandidates.length,
                assignedCandidates: allCandidates.filter(c => c.assigned).length,
                totalJuryMembers: allJuryMembers.length
            }
        };
        
        const blob = new Blob([JSON.stringify(assignmentData, null, 2)], {
            type: 'application/json'
        });
        
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `mobility-trailblazers-assignments-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showNotification('Assignment data exported successfully!', 'success');
    }

    // Utility functions
    function formatCategory(category) {
        const categoryMap = {
            'established-companies': 'Established Companies',
            'startups-new-makers': 'Start-ups & New Makers',
            'infrastructure-politics-public': 'Infrastructure/Politics/Public'
        };
        return categoryMap[category] || category;
    }

    // Helper function to escape HTML
    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Helper function to show notifications
    function showNotification(message, type = 'info') {
        console.log('Notification:', type, message);
        // Implementation of notification system
    }

    // Initialize the interface
    initAssignmentInterface();

    // Data Management functionality
    console.log('Data Management buttons initialization...');
    
    // Export Assignments button
    $('#mt-export-assignments-btn').on('click', function(e) {
        e.preventDefault();
        console.log('Export Assignments clicked');
        
        // Show loading state
        $(this).prop('disabled', true).text('Exporting...');
        
        $.ajax({
            url: mt_assignment_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_export_assignments',
                nonce: mt_assignment_ajax.nonce
            },
            success: function(response) {
                // Create download link
                const blob = new Blob([response], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'mobility-trailblazers-assignments-' + new Date().toISOString().split('T')[0] + '.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                
                $('#mt-export-assignments-btn').prop('disabled', false).text('Export Assignments');
                showNotification('Assignments exported successfully!', 'success');
            },
            error: function(xhr, status, error) {
                console.error('Export error:', error);
                $('#mt-export-assignments-btn').prop('disabled', false).text('Export Assignments');
                showNotification('Export failed: ' + error, 'error');
            }
        });
    });
    
    // Sync System button
    $('#mt-sync-system-btn').on('click', function(e) {
        e.preventDefault();
        console.log('Sync System clicked');
        
        if (!confirm('This will synchronize all assignment data. Continue?')) {
            return;
        }
        
        $(this).prop('disabled', true).text('Syncing...');
        
        $.ajax({
            url: mt_assignment_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_sync_system',
                nonce: mt_assignment_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('System synchronized successfully!', 'success');
                    // Reload page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Sync failed: ' + (response.data.message || 'Unknown error'), 'error');
                    $('#mt-sync-system-btn').prop('disabled', false).text('Sync System');
                }
            },
            error: function(xhr, status, error) {
                console.error('Sync error:', error);
                $('#mt-sync-system-btn').prop('disabled', false).text('Sync System');
                showNotification('Sync failed: ' + error, 'error');
            }
        });
    });
    
    // View Progress Data button
    $('#mt-view-progress-btn').on('click', function(e) {
        e.preventDefault();
        console.log('View Progress Data clicked');
        
        // Create modal for progress data
        const modalHtml = `
            <div id="mt-progress-modal" class="mt-assignment-modal" style="display: block;">
                <div class="mt-modal-content" style="max-width: 800px;">
                    <div class="mt-modal-header">
                        <h3 class="mt-modal-title">📊 Assignment Progress Data</h3>
                        <button class="mt-close-btn" onclick="jQuery('#mt-progress-modal').remove();">&times;</button>
                    </div>
                    <div class="mt-modal-body" style="padding: 20px;">
                        <div id="mt-progress-loading" style="text-align: center;">
                            <div class="mt-spinner"></div>
                            <p>Loading progress data...</p>
                        </div>
                        <div id="mt-progress-content" style="display: none;"></div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Fetch progress data
        $.ajax({
            url: mt_assignment_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_get_progress_data',
                nonce: mt_assignment_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#mt-progress-loading').hide();
                    $('#mt-progress-content').html(response.data.html).show();
                } else {
                    $('#mt-progress-loading').html('<p style="color: red;">Error loading progress data</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Progress data error:', error);
                $('#mt-progress-loading').html('<p style="color: red;">Failed to load progress data</p>');
            }
        });
    });
    
    // Reset All Assignments button
    $('#mt-reset-assignments-btn').on('click', function(e) {
        e.preventDefault();
        console.log('Reset All Assignments clicked');
        
        // Double confirmation for dangerous action
        if (!confirm('⚠️ WARNING: This will remove ALL current assignments. This action cannot be undone. Continue?')) {
            return;
        }
        
        if (!confirm('Are you absolutely sure? All jury assignments will be permanently deleted.')) {
            return;
        }
        
        $(this).prop('disabled', true).text('Resetting...');
        
        $.ajax({
            url: mt_assignment_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_clear_assignments',
                nonce: mt_assignment_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification('All assignments have been reset!', 'success');
                    // Reload page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification('Reset failed: ' + (response.data.message || 'Unknown error'), 'error');
                    $('#mt-reset-assignments-btn').prop('disabled', false).text('Reset All Assignments');
                }
            },
            error: function(xhr, status, error) {
                console.error('Reset error:', error);
                $('#mt-reset-assignments-btn').prop('disabled', false).text('Reset All Assignments');
                showNotification('Reset failed: ' + error, 'error');
            }
        });
    });
    
    // Enhanced notification function
    function showNotification(message, type = 'info') {
        console.log('Notification:', type, message);
        
        const container = $('#mt-notification-container');
        if (container.length === 0) {
            $('body').append('<div id="mt-notification-container" style="position: fixed; top: 20px; right: 20px; z-index: 100001;"></div>');
        }
        
        const notification = $(`
            <div class="mt-notification mt-notification-${type}" style="
                background: ${type === 'success' ? '#38a169' : type === 'error' ? '#e53e3e' : '#3182ce'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                margin-bottom: 10px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                animation: slideIn 0.3s ease-out;
            ">
                ${message}
            </div>
        `);
        
        $('#mt-notification-container').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    // Add CSS for modal and notifications if not already present
    if ($('#mt-modal-styles').length === 0) {
        $('head').append(`
            <style id="mt-modal-styles">
                .mt-assignment-modal {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0,0,0,0.5);
                    display: none;
                    z-index: 100000;
                    align-items: center;
                    justify-content: center;
                }
                .mt-modal-content {
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                    max-width: 600px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    margin: auto;
                    position: relative;
                    top: 50%;
                    transform: translateY(-50%);
                }
                .mt-modal-header {
                    padding: 20px;
                    border-bottom: 1px solid #e2e8f0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                .mt-modal-title {
                    margin: 0;
                    font-size: 1.5rem;
                    color: #2d3748;
                }
                .mt-close-btn {
                    background: none;
                    border: none;
                    font-size: 24px;
                    cursor: pointer;
                    color: #718096;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 4px;
                    transition: all 0.2s;
                }
                .mt-close-btn:hover {
                    background: #f7fafc;
                    color: #2d3748;
                }
                .mt-spinner {
                    border: 3px solid #f3f3f3;
                    border-top: 3px solid #3498db;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: spin 1s linear infinite;
                    margin: 20px auto;
                }
                @keyframes spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
            </style>
        `);
    }
});