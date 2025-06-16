/* Complete Mobility Trailblazers Assignment System JavaScript */

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
        console.log('Initializing enhanced assignment interface...');
        
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

        renderCandidates();
        renderJuryMembers();
        updateStatistics();
        bindEventHandlers();
        bindEnhancedEventHandlers();
    }

    // Render candidates list with enhanced features
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
            
            const assignmentStatus = candidate.assigned ? 
                `Assigned to ${juryMember ? juryMember.name : 'Unknown'}` : 
                'Unassigned';
            
            const candidateHtml = `
                <div class="mt-candidate-item ${isSelected ? 'selected' : ''} ${candidate.assigned ? 'assigned' : ''}" 
                     data-candidate-id="${candidate.id}"
                     data-category="${candidate.category}"
                     data-stage="${candidate.stage}">
                    <div class="mt-candidate-header">
                        <div class="mt-candidate-info">
                            <div class="mt-candidate-name">${escapeHtml(candidate.name)}</div>
                            <div class="mt-candidate-company">${escapeHtml(candidate.company || '')}</div>
                            ${candidate.position ? `<div class="mt-candidate-position">${escapeHtml(candidate.position)}</div>` : ''}
                        </div>
                        <span class="mt-assignment-indicator ${candidate.assigned ? 'assigned' : 'unassigned'}">
                            ${assignmentStatus}
                        </span>
                    </div>
                    <div class="mt-candidate-footer">
                        <span class="mt-candidate-category">${formatCategory(candidate.category)}</span>
                        ${candidate.stage ? `<span class="mt-candidate-stage">${formatStage(candidate.stage)}</span>` : ''}
                    </div>
                </div>
            `;
            
            container.append(candidateHtml);
        });

        // Update candidates count
        $('.mt-candidates-count').text(filteredCandidates.length);
        console.log('Candidates rendered successfully');
    }

    // Render jury members list with enhanced features
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
            const isOverloaded = jury.assignments > jury.max_assignments;
            
            const juryHtml = `
                <div class="mt-jury-item ${isSelected ? 'active' : ''} ${isOverloaded ? 'overloaded' : ''}" 
                     data-jury-id="${jury.id}">
                    <div class="mt-jury-header">
                        <div class="mt-jury-info">
                            <div class="mt-jury-name">${escapeHtml(jury.name)}</div>
                            ${jury.position ? `<div class="mt-jury-position">${escapeHtml(jury.position)}</div>` : ''}
                            ${jury.company ? `<div class="mt-jury-company">${escapeHtml(jury.company)}</div>` : ''}
                        </div>
                        ${jury.role === 'president' ? '<span class="mt-jury-role president">President</span>' : ''}
                        ${jury.role === 'vice_president' ? '<span class="mt-jury-role vice-president">Vice President</span>' : ''}
                    </div>
                    <div class="mt-jury-expertise">
                        ${jury.expertise ? `<span class="mt-expertise-tag">${escapeHtml(jury.expertise)}</span>` : ''}
                    </div>
                    <div class="mt-jury-stats">
                        <div class="mt-assignments-info">
                            Assignments: <strong class="${isOverloaded ? 'overloaded' : ''}">${jury.assignments}/${jury.max_assignments}</strong>
                        </div>
                        <div class="mt-progress-bar">
                            <div class="mt-progress-fill ${isOverloaded ? 'overloaded' : ''}" 
                                 style="width: ${Math.min(progressPercent, 100)}%"></div>
                        </div>
                        ${isOverloaded ? '<div class="mt-overload-warning">⚠️ Overloaded</div>' : ''}
                    </div>
                    <div class="mt-jury-actions">
                        <button class="mt-btn-small mt-view-assignments" data-jury-id="${jury.id}">
                            View Assignments
                        </button>
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
            // Stage filter
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
                const searchableText = `${candidate.name} ${candidate.company} ${candidate.position || ''}`.toLowerCase();
                if (!searchableText.includes(searchTerm)) {
                    return false;
                }
            }
            
            return true;
        });
    }

    // Update statistics with animations
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

    // Bind standard event handlers
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

    // Bind enhanced event handlers
    function bindEnhancedEventHandlers() {
        // Quick actions
        $('.mt-quick-action').on('click', function() {
            const action = $(this).data('action');
            handleQuickAction(action);
        });

        // Matrix view
        $('#mt-matrix-view-btn').on('click', openMatrixView);
        $('.mt-close-matrix-btn').on('click', closeMatrixView);

        // Health check
        $('#mt-health-check-btn').on('click', performHealthCheck);
        $('.mt-close-health-btn').on('click', closeHealthCheck);

        // View jury assignments
        $(document).on('click', '.mt-view-assignments', function() {
            const juryId = $(this).data('jury-id');
            viewJuryAssignments(juryId);
        });

        // Preview assignments
        $('#mt-preview-assignments').on('click', previewAutoAssignments);

        // Refresh data
        $('#mt-refresh-btn').on('click', refreshData);

        // Import data
        $('#mt-import-btn').on('click', openImportDialog);

        // Data Management buttons
        $('#mt-export-assignments-btn').on('click', exportAssignmentsToCSV);
        $('#mt-sync-system-btn').on('click', syncSystem);
        $('#mt-view-progress-btn').on('click', viewProgressData);
        $('#mt-reset-assignments-btn').on('click', resetAllAssignments);
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
    function simulateAutoAssignment(candidatesPerJury, algorithm, previewOnly = false) {
        const unassignedCandidates = allCandidates.filter(c => !c.assigned);
        const preview = [];
        let juryIndex = 0;
        
        if (algorithm === 'balanced') {
            const tempAssignments = {};
            
            unassignedCandidates.forEach((candidate, index) => {
                const jury = allJuryMembers[juryIndex];
                
                if (!tempAssignments[jury.id]) {
                    tempAssignments[jury.id] = {
                        jury: jury.name,
                        count: jury.assignments
                    };
                }
                
                if (tempAssignments[jury.id].count < candidatesPerJury) {
                    if (!previewOnly) {
                        candidate.assigned = true;
                        candidate.jury_member_id = jury.id;
                        jury.assignments++;
                    }
                    tempAssignments[jury.id].count++;
                }
                
                // Move to next jury member when current one is full
                if (tempAssignments[jury.id].count >= candidatesPerJury) {
                    juryIndex = (juryIndex + 1) % allJuryMembers.length;
                }
            });
            
            return Object.values(tempAssignments);
        }
        
        return preview;
    }

    // Export assignments
    function exportAssignments() {
        window.location.href = mt_assignment_ajax.ajax_url + '?action=mt_export_assignments&nonce=' + mt_assignment_ajax.nonce;
    }

    // Handle quick actions
    function handleQuickAction(action) {
        switch(action) {
            case 'assign-unassigned':
                assignAllUnassigned();
                break;
            case 'balance-assignments':
                balanceAssignments();
                break;
            case 'generate-report':
                generateAssignmentReport();
                break;
            case 'email-jury':
                openEmailJuryDialog();
                break;
            default:
                showNotification('Unknown action: ' + action, 'error');
        }
    }

    // Quick action implementations
    function assignAllUnassigned() {
        const unassigned = allCandidates.filter(c => !c.assigned);
        if (unassigned.length === 0) {
            showNotification('All candidates are already assigned.', 'info');
            return;
        }
        
        const message = `This will automatically assign ${unassigned.length} unassigned candidates to jury members. Continue?`;
        if (confirm(message)) {
            $('#mt-auto-assign-btn').click();
        }
    }

    function balanceAssignments() {
        showNotification('Balancing assignments... (Feature coming soon)', 'info');
    }

    function generateAssignmentReport() {
        showNotification('Generating report... (Feature coming soon)', 'info');
    }

    function openEmailJuryDialog() {
        showNotification('Email jury feature coming soon', 'info');
    }

    // Matrix view functions
    function openMatrixView() {
        $('#mt-matrix-view-modal').addClass('show');
        loadMatrixView();
    }

    function closeMatrixView() {
        $('#mt-matrix-view-modal').removeClass('show');
    }

    function loadMatrixView() {
        const container = $('#mt-matrix-container');
        container.html('<div class="mt-loading"><div class="mt-spinner"></div><p>Loading matrix view...</p></div>');
        
        setTimeout(() => {
            let matrixHtml = '<table class="mt-matrix-table">';
            matrixHtml += '<thead><tr><th>Jury Member</th>';
            
            const categories = [...new Set(allCandidates.map(c => c.category))].filter(Boolean);
            categories.forEach(category => {
                matrixHtml += `<th>${formatCategory(category)}</th>`;
            });
            matrixHtml += '<th>Total</th></tr></thead><tbody>';
            
            allJuryMembers.forEach(jury => {
                matrixHtml += `<tr><td>${escapeHtml(jury.name)}</td>`;
                
                categories.forEach(category => {
                    const count = allCandidates.filter(c => 
                        c.jury_member_id == jury.id && c.category === category
                    ).length;
                    matrixHtml += `<td class="mt-matrix-cell">${count}</td>`;
                });
                
                matrixHtml += `<td class="mt-matrix-total">${jury.assignments}</td></tr>`;
            });
            
            matrixHtml += '</tbody></table>';
            container.html(matrixHtml);
        }, 500);
    }

    // Health check functions
    function performHealthCheck() {
        $('#mt-health-check-modal').addClass('show');
        
        const container = $('#mt-health-check-results');
        container.html('<div class="mt-loading"><div class="mt-spinner"></div><p>Performing health check...</p></div>');
        
        setTimeout(() => {
            const issues = [];
            
            const overloadedJury = allJuryMembers.filter(j => j.assignments > j.max_assignments);
            if (overloadedJury.length > 0) {
                issues.push({
                    type: 'error',
                    message: `${overloadedJury.length} jury members are overloaded`,
                    details: overloadedJury.map(j => `${j.name}: ${j.assignments}/${j.max_assignments}`).join(', ')
                });
            }
            
            const unassignedCount = allCandidates.filter(c => !c.assigned).length;
            if (unassignedCount > 0) {
                issues.push({
                    type: 'warning',
                    message: `${unassignedCount} candidates are unassigned`
                });
            }
            
            const idleJury = allJuryMembers.filter(j => j.assignments === 0);
            if (idleJury.length > 0) {
                issues.push({
                    type: 'warning',
                    message: `${idleJury.length} jury members have no assignments`,
                    details: idleJury.map(j => j.name).join(', ')
                });
            }
            
            let resultsHtml = '<h4>Health Check Results</h4>';
            
            if (issues.length === 0) {
                resultsHtml += '<div class="mt-health-success">✅ All systems healthy!</div>';
            } else {
                resultsHtml += '<div class="mt-health-issues">';
                issues.forEach(issue => {
                    resultsHtml += `
                        <div class="mt-health-issue mt-health-${issue.type}">
                            <strong>${issue.message}</strong>
                            ${issue.details ? `<div class="mt-issue-details">${issue.details}</div>` : ''}
                        </div>
                    `;
                });
                resultsHtml += '</div>';
            }
            
            container.html(resultsHtml);
        }, 1000);
    }

    function closeHealthCheck() {
        $('#mt-health-check-modal').removeClass('show');
    }

    // View jury assignments
    function viewJuryAssignments(juryId) {
        const jury = allJuryMembers.find(j => j.id == juryId);
        if (!jury) return;
        
        const assignments = allCandidates.filter(c => c.jury_member_id == juryId);
        
        let html = `<h3>Assignments for ${escapeHtml(jury.name)}</h3>`;
        html += '<div class="mt-jury-assignments-list">';
        
        if (assignments.length === 0) {
            html += '<p>No assignments yet.</p>';
        } else {
            assignments.forEach(candidate => {
                html += `
                    <div class="mt-assignment-item">
                        <strong>${escapeHtml(candidate.name)}</strong>
                        <span>${escapeHtml(candidate.company || '')}</span>
                        <span class="mt-category-badge">${formatCategory(candidate.category)}</span>
                    </div>
                `;
            });
        }
        
        html += '</div>';
        
        showModal('Jury Assignments', html);
    }

    // Preview auto assignments
    function previewAutoAssignments() {
        const candidatesPerJury = parseInt($('#mt-candidates-per-jury').val()) || 10;
        const algorithm = $('.mt-algorithm-option.selected').data('algorithm') || 'balanced';
        
        const preview = simulateAutoAssignment(candidatesPerJury, algorithm, true);
        
        let previewHtml = '<div class="mt-preview-results">';
        previewHtml += `<p><strong>Algorithm:</strong> ${algorithm}</p>`;
        previewHtml += `<p><strong>Candidates per jury:</strong> ${candidatesPerJury}</p>`;
        previewHtml += '<h4>Distribution Preview:</h4>';
        
        preview.forEach(item => {
            previewHtml += `<div>${item.jury}: ${item.count} candidates</div>`;
        });
        
        previewHtml += '</div>';
        
        $('#mt-preview-content').html(previewHtml);
    }

    // Data management functions
    function exportAssignmentsToCSV() {
        window.location.href = mt_assignment_ajax.ajax_url + '?action=mt_export_assignments&nonce=' + mt_assignment_ajax.nonce;
    }

    function syncSystem() {
        if (!confirm('This will synchronize all assignment data. Continue?')) {
            return;
        }
        
        $('#mt-sync-system-btn').prop('disabled', true).text('Syncing...');
        
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
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Sync failed: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showNotification('Network error while syncing', 'error');
            },
            complete: function() {
                $('#mt-sync-system-btn').prop('disabled', false).text('Sync System');
            }
        });
    }

    function viewProgressData() {
        const modalHtml = `
            <div id="mt-progress-modal" class="mt-assignment-modal show">
                <div class="mt-modal-content" style="max-width: 800px;">
                    <div class="mt-modal-header">
                        <h3 class="mt-modal-title">📊 Assignment Progress Data</h3>
                        <button class="mt-close-btn" onclick="jQuery('#mt-progress-modal').remove();">&times;</button>
                    </div>
                    <div class="mt-modal-body">
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
            error: function() {
                $('#mt-progress-loading').html('<p style="color: red;">Failed to load progress data</p>');
            }
        });
    }

    function resetAllAssignments() {
        if (!confirm('⚠️ WARNING: This will remove ALL current assignments. This action cannot be undone. Continue?')) {
            return;
        }
        
        if (!confirm('Are you absolutely sure? All jury assignments will be permanently deleted.')) {
            return;
        }
        
        $('#mt-reset-assignments-btn').prop('disabled', true).text('Resetting...');
        
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
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showNotification('Reset failed: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showNotification('Network error while resetting', 'error');
            },
            complete: function() {
                $('#mt-reset-assignments-btn').prop('disabled', false).text('Reset All Assignments');
            }
        });
    }

    function refreshData() {
        const btn = $('#mt-refresh-btn');
        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spinning"></span> Refreshing...');
        
        // For now, just reload the page
        location.reload();
    }

    function openImportDialog() {
        showNotification('Import feature coming soon', 'info');
    }

    // Utility functions
    function formatCategory(category) {
        const categoryMap = {
            'established-companies': 'Established Companies',
            'startups-new-makers': 'Start-ups & New Makers',
            'infrastructure-politics-public': 'Infrastructure/Politics/Public'
        };
        return categoryMap[category] || category || 'Uncategorized';
    }

    function formatStage(stage) {
        const stageMap = {
            'longlist': 'Longlist',
            'shortlist': 'Shortlist',
            'finalist': 'Finalist'
        };
        return stageMap[stage] || stage || '';
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function showNotification(message, type = 'info', duration = 5000) {
        const container = $('#mt-notification-container');
        
        const notification = $(`
            <div class="mt-notification mt-notification-${type}" style="display: none;">
                <span class="mt-notification-message">${message}</span>
                <button class="mt-notification-close">&times;</button>
            </div>
        `);
        
        container.append(notification);
        notification.slideDown(300);
        
        notification.find('.mt-notification-close').on('click', function() {
            notification.slideUp(300, function() {
                $(this).remove();
            });
        });
        
        if (duration > 0) {
            setTimeout(function() {
                notification.slideUp(300, function() {
                    $(this).remove();
                });
            }, duration);
        }
    }

    function showModal(title, content, buttons = []) {
        const modal = $(`
            <div class="mt-assignment-modal show">
                <div class="mt-modal-content">
                    <div class="mt-modal-header">
                        <h3 class="mt-modal-title">${title}</h3>
                        <button class="mt-close-btn">&times;</button>
                    </div>
                    <div class="mt-modal-body">
                        ${content}
                    </div>
                    ${buttons.length > 0 ? '<div class="mt-modal-footer"></div>' : ''}
                </div>
            </div>
        `);
        
        if (buttons.length > 0) {
            const footer = modal.find('.mt-modal-footer');
            buttons.forEach(btn => {
                footer.append(`<button class="mt-btn mt-btn-${btn.type || 'secondary'}">${btn.text}</button>`);
            });
        }
        
        modal.find('.mt-close-btn').on('click', function() {
            modal.removeClass('show');
            setTimeout(() => modal.remove(), 300);
        });
        
        modal.on('click', function(e) {
            if (e.target === this) {
                modal.removeClass('show');
                setTimeout(() => modal.remove(), 300);
            }
        });
        
        $('body').append(modal);
    }

    // Initialize everything
    initAssignmentInterface();

    // Add CSS animation for spinning icon
    $('<style>')
        .text('@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } } .spinning { animation: spin 1s linear infinite; }')
        .appendTo('head');
});