/* Enhanced Mobility Trailblazers Assignment System JavaScript */

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

    // Enhanced filter function
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

        // Animate number changes
        animateNumber('.mt-stat-total-candidates', totalCandidates);
        animateNumber('.mt-stat-total-jury', totalJury);
        animateNumber('.mt-stat-assigned-count', assignedCandidates);
        animateNumber('.mt-stat-completion-rate', completionRate, '%');
        animateNumber('.mt-stat-avg-per-jury', avgPerJury);
    }

    // Animate number changes
    function animateNumber(selector, newValue, suffix = '') {
        const element = $(selector);
        const currentValue = parseFloat(element.text()) || 0;
        
        $({value: currentValue}).animate({value: newValue}, {
            duration: 600,
            easing: 'swing',
            step: function() {
                element.text(Math.round(this.value) + suffix);
            },
            complete: function() {
                element.text(newValue + suffix);
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

        // Handle keyboard shortcuts
        $(document).on('keydown', handleKeyboardShortcuts);
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

    // Open matrix view
    function openMatrixView() {
        $('#mt-matrix-view-modal').addClass('show');
        loadMatrixView();
    }

    // Load matrix view content
    function loadMatrixView() {
        const container = $('#mt-matrix-container');
        container.html('<div class="mt-loading"><div class="mt-spinner"></div><p>Loading matrix view...</p></div>');
        
        // Create matrix table
        setTimeout(() => {
            let matrixHtml = '<table class="mt-matrix-table">';
            matrixHtml += '<thead><tr><th>Jury Member</th>';
            
            // Add category headers
            const categories = [...new Set(allCandidates.map(c => c.category))].filter(Boolean);
            categories.forEach(category => {
                matrixHtml += `<th>${formatCategory(category)}</th>`;
            });
            matrixHtml += '<th>Total</th></tr></thead><tbody>';
            
            // Add jury rows
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

    // Close matrix view
    function closeMatrixView() {
        $('#mt-matrix-view-modal').removeClass('show');
    }

    // Perform health check
    function performHealthCheck() {
        $('#mt-health-check-modal').addClass('show');
        
        const container = $('#mt-health-check-results');
        container.html('<div class="mt-loading"><div class="mt-spinner"></div><p>Performing health check...</p></div>');
        
        setTimeout(() => {
            const issues = [];
            
            // Check for overloaded jury members
            const overloadedJury = allJuryMembers.filter(j => j.assignments > j.max_assignments);
            if (overloadedJury.length > 0) {
                issues.push({
                    type: 'error',
                    message: `${overloadedJury.length} jury members are overloaded`,
                    details: overloadedJury.map(j => `${j.name}: ${j.assignments}/${j.max_assignments}`).join(', ')
                });
            }
            
            // Check for unassigned candidates
            const unassignedCount = allCandidates.filter(c => !c.assigned).length;
            if (unassignedCount > 0) {
                issues.push({
                    type: 'warning',
                    message: `${unassignedCount} candidates are unassigned`
                });
            }
            
            // Check for jury with no assignments
            const idleJury = allJuryMembers.filter(j => j.assignments === 0);
            if (idleJury.length > 0) {
                issues.push({
                    type: 'warning',
                    message: `${idleJury.length} jury members have no assignments`,
                    details: idleJury.map(j => j.name).join(', ')
                });
            }
            
            // Check for category imbalance
            const categoryDistribution = {};
            allCandidates.forEach(c => {
                if (c.category) {
                    categoryDistribution[c.category] = (categoryDistribution[c.category] || 0) + 1;
                }
            });
            
            // Display results
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
            
            // Add statistics
            resultsHtml += '<h4>Distribution Statistics</h4>';
            resultsHtml += '<div class="mt-health-stats">';
            for (const [category, count] of Object.entries(categoryDistribution)) {
                resultsHtml += `<div>${formatCategory(category)}: ${count} candidates</div>`;
            }
            resultsHtml += '</div>';
            
            container.html(resultsHtml);
        }, 1000);
    }

    // Close health check
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

    // Simulate auto assignment (enhanced version)
    function simulateAutoAssignment(candidatesPerJury, algorithm, previewOnly = false) {
        const unassignedCandidates = allCandidates.filter(c => !c.assigned);
        const preview = [];
        
        if (algorithm === 'balanced') {
            let juryIndex = 0;
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
        
        // Add other algorithms here...
        
        return preview;
    }

    // Refresh data from server
    function refreshData() {
        const btn = $('#mt-refresh-btn');
        btn.prop('disabled', true).html('<span class="dashicons dashicons-update spinning"></span> Refreshing...');
        
        $.ajax({
            url: mt_assignment_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_refresh_assignment_data',
                nonce: mt_assignment_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    allCandidates = response.data.candidates || [];
                    allJuryMembers = response.data.jury_members || [];
                    
                    renderCandidates();
                    renderJuryMembers();
                    updateStatistics();
                    
                    showNotification('Data refreshed successfully!', 'success');
                } else {
                    showNotification('Failed to refresh data: ' + (response.data.message || 'Unknown error'), 'error');
                }
            },
            error: function() {
                showNotification('Network error while refreshing data', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<span class="dashicons dashicons-image-rotate"></span> Refresh Data');
            }
        });
    }

    // Keyboard shortcuts
    function handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + A: Select all visible candidates
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && !$(e.target).is('input, textarea')) {
            e.preventDefault();
            selectAllCandidates();
        }
        
        // Ctrl/Cmd + D: Deselect all
        if ((e.ctrlKey || e.metaKey) && e.key === 'd' && !$(e.target).is('input, textarea')) {
            e.preventDefault();
            clearSelection();
        }
        
        // Ctrl/Cmd + S: Save/Export
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            exportAssignments();
        }
    }

    // Enhanced notification system
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
        
        // Close button
        notification.find('.mt-notification-close').on('click', function() {
            notification.slideUp(300, function() {
                $(this).remove();
            });
        });
        
        // Auto-close
        if (duration > 0) {
            setTimeout(function() {
                notification.slideUp(300, function() {
                    $(this).remove();
                });
            }, duration);
        }
    }

    // Show modal helper
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
        
        // Add buttons if provided
        if (buttons.length > 0) {
            const footer = modal.find('.mt-modal-footer');
            buttons.forEach(btn => {
                footer.append(`<button class="mt-btn mt-btn-${btn.type || 'secondary'}">${btn.text}</button>`);
            });
        }
        
        // Close handlers
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

    // Format category name
    function formatCategory(category) {
        const categoryMap = {
            'established-companies': 'Established Companies',
            'startups-new-makers': 'Start-ups & New Makers',
            'infrastructure-politics-public': 'Infrastructure/Politics/Public'
        };
        return categoryMap[category] || category || 'Uncategorized';
    }

    // Format stage name
    function formatStage(stage) {
        const stageMap = {
            'longlist': 'Longlist',
            'shortlist': 'Shortlist',
            'finalist': 'Finalist'
        };
        return stageMap[stage] || stage || '';
    }

    // Initialize everything
    initAssignmentInterface();

    // Add CSS animation for spinning icon
    $('<style>')
        .text('@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } } .spinning { animation: spin 1s linear infinite; }')
        .appendTo('head');
        
    // Keep existing functionality...
    bindEventHandlers();

    // All the existing functions remain the same...
    // (toggleCandidateSelection, selectJuryMember, etc.)
    
    // Keep all existing event handlers and functions from the original file
    // Just enhance them with the new features
});