/* Mobility Trailblazers Enhanced Assignment System JavaScript */

(function($) {
    'use strict';

    // Global data storage
    let allCandidates = [];
    let allJuryMembers = [];

    // Configuration
    const CONFIG = {
        animationDuration: 300,
        debounceDelay: 300,
        toastDuration: 5000,
        refreshInterval: 60000, // 1 minute
        maxSelectableItems: 50,
        smoothScrollOffset: 100
    };

    // State Management
    const state = {
        selectedCandidates: new Set(),
        selectedJuryMember: null,
        filters: {
            stage: '',
            category: '',
            assignment: '',
            candidateSearch: '',
            jurySearch: ''
        },
        isLoading: false,
        lastUpdate: new Date()
    };

    // Cache DOM elements
    const elements = {
        candidatesList: null,
        juryList: null,
        statsElements: {},
        modals: {},
        buttons: {}
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        // Debug data availability
        console.log('MT Assignment Ajax object:', typeof mt_assignment_ajax !== 'undefined' ? 'Available' : 'Not found');
        if (typeof mt_assignment_ajax !== 'undefined') {
            console.log('Candidates:', mt_assignment_ajax.candidates);
            console.log('Jury members:', mt_assignment_ajax.jury_members);
        }
        
        initializeInterface();
        bindEventHandlers();
        loadInitialData();
        startAutoRefresh();
        showWelcomeAnimation();
    });

    // Initialize Interface
    function initializeInterface() {
        // Cache DOM elements
        elements.candidatesList = $('#mt-candidates-list');
        elements.juryList = $('#mt-jury-list');
        elements.statsElements = {
            totalCandidates: $('.mt-stat-total-candidates'),
            totalJury: $('.mt-stat-total-jury'),
            assignedCount: $('.mt-stat-assigned-count'),
            completionRate: $('.mt-stat-completion-rate'),
            avgPerJury: $('.mt-stat-avg-per-jury')
        };
        elements.modals = {
            autoAssign: $('#mt-auto-assign-modal')
        };
        elements.buttons = {
            autoAssign: $('#mt-auto-assign-btn'),
            manualAssign: $('#mt-manual-assign-btn'),
            export: $('#mt-export-btn'),
            refresh: $('#mt-refresh-btn')
        };

        // Initialize tooltips
        initializeTooltips();

        // Initialize sortable lists
        initializeSortable();

        // Add ripple effect to buttons
        addRippleEffect();
    }

    // Bind Event Handlers
    function bindEventHandlers() {
        // Button clicks
        elements.buttons.autoAssign.on('click', openAutoAssignModal);
        elements.buttons.manualAssign.on('click', performManualAssignment);
        elements.buttons.export.on('click', exportData);
        elements.buttons.refresh.on('click', refreshData);

        // Candidate selection
        $(document).on('click', '.mt-candidate-item', function(e) {
            if (!e.ctrlKey && !e.metaKey && !e.shiftKey) {
                handleCandidateClick($(this));
            }
        });

        // Multi-select with Shift/Ctrl
        $(document).on('click', '.mt-candidate-item', function(e) {
            if (e.shiftKey) {
                handleShiftSelect($(this));
            } else if (e.ctrlKey || e.metaKey) {
                toggleCandidateSelection($(this));
            }
        });

        // Jury member selection
        $(document).on('click', '.mt-jury-item', handleJuryClick);

        // Search with debounce
        $('#mt-candidates-search').on('input', debounce(handleCandidateSearch, CONFIG.debounceDelay));
        $('#mt-jury-search').on('input', debounce(handleJurySearch, CONFIG.debounceDelay));

        // Filters
        $('#mt-stage-filter, #mt-category-filter, #mt-assignment-filter').on('change', applyFilters);

        // Category tags
        $(document).on('click', '.mt-tag', function() {
            $('.mt-tag').removeClass('mt-tag-active');
            $(this).addClass('mt-tag-active');
            state.filters.category = $(this).data('category');
            applyFilters();
        });

        // Select all/clear
        $('#mt-select-all-candidates').on('click', selectAllVisibleCandidates);
        $('#mt-clear-selection').on('click', clearSelection);

        // Modal controls
        $('.mt-modal-close, .mt-modal-backdrop').on('click', closeModal);
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });

        // Algorithm selection
        $(document).on('click', '.mt-algorithm-card', function() {
            $('.mt-algorithm-card').removeClass('selected');
            $(this).addClass('selected');
            animateSelection($(this));
        });

        // Execute auto-assignment
        $('#mt-execute-auto-assign').on('click', executeAutoAssignment);

        // Data management buttons
        $('#mt-export-assignments-btn').on('click', exportAssignments);
        $('#mt-sync-system-btn').on('click', syncSystem);
        $('#mt-view-progress-btn').on('click', viewProgress);
        $('#mt-reset-assignments-btn').on('click', resetAssignments);

        // Keyboard shortcuts
        $(document).on('keydown', handleKeyboardShortcuts);

        // Window resize handling
        $(window).on('resize', debounce(handleResize, 300));
    }

    // Load Initial Data
    function loadInitialData() {
        showLoadingState();

        // Check if WordPress data is available and properly populated
        if (typeof mt_assignment_ajax !== 'undefined' && 
            mt_assignment_ajax.candidates && 
            mt_assignment_ajax.jury_members &&
            mt_assignment_ajax.candidates.length > 0) {
            
            console.log('Using WordPress data:', mt_assignment_ajax.candidates.length + ' candidates');
            
            // Store the data globally for other functions
            allCandidates = mt_assignment_ajax.candidates;
            allJuryMembers = mt_assignment_ajax.jury_members;
            
            renderCandidates(allCandidates);
            renderJuryMembers(allJuryMembers);
            
            // Calculate statistics
            const stats = {
                totalCandidates: allCandidates.length,
                totalJury: allJuryMembers.length,
                assignedCount: allCandidates.filter(c => c.assigned).length,
                completionRate: '0%',
                avgPerJury: '0'
            };
            
            if (stats.totalCandidates > 0) {
                stats.completionRate = ((stats.assignedCount / stats.totalCandidates) * 100).toFixed(1) + '%';
            }
            
            if (stats.totalJury > 0) {
                stats.avgPerJury = (stats.assignedCount / stats.totalJury).toFixed(1);
            }
            
            updateStatistics(stats);
            hideLoadingState();
            showNotification('Data loaded successfully', 'success');
            
        } else {
            console.warn('WordPress data not available, falling back to AJAX load');
            
            // Try to load via AJAX
            if (typeof mt_assignment_ajax !== 'undefined' && mt_assignment_ajax.ajax_url) {
                $.ajax({
                    url: mt_assignment_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mt_get_assignment_data',
                        nonce: mt_assignment_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data) {
                            allCandidates = response.data.candidates || [];
                            allJuryMembers = response.data.jury_members || [];
                            
                            renderCandidates(allCandidates);
                            renderJuryMembers(allJuryMembers);
                            updateStatistics(response.data.statistics);
                            hideLoadingState();
                            showNotification('Data loaded via AJAX', 'success');
                        } else {
                            console.error('Failed to load data:', response);
                            // As a last resort, show empty state
                            hideLoadingState();
                            showNotification('No data available. Please add candidates and jury members.', 'warning');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', status, error);
                        hideLoadingState();
                        showNotification('Failed to load data. Please refresh the page.', 'error');
                    }
                });
            } else {
                console.error('AJAX configuration not available');
                hideLoadingState();
                showNotification('Configuration error. Please refresh the page.', 'error');
            }
        }
    }

    // Render Candidates
    function renderCandidates(candidates) {
        elements.candidatesList.empty();

        if (candidates.length === 0) {
            elements.candidatesList.html(`
                <div class="mt-empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path d="M9 13H15M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z"/>
                    </svg>
                    <p>No candidates found</p>
                </div>
            `);
            return;
        }

        candidates.forEach((candidate, index) => {
            const candidateEl = createCandidateElement(candidate);
            elements.candidatesList.append(candidateEl);
            
            // Animate appearance
            setTimeout(() => {
                candidateEl.addClass('animate-in');
            }, index * 50);
        });

        updateSelectionInfo();
    }

    // Create Candidate Element
    function createCandidateElement(candidate) {
        const isSelected = state.selectedCandidates.has(candidate.id);
        const categoryClass = `category-${candidate.category.toLowerCase().replace(/\s+/g, '-')}`;
        
        return $(`
            <div class="mt-candidate-item ${isSelected ? 'selected' : ''}" 
                 data-candidate-id="${candidate.id}"
                 data-category="${candidate.category}">
                <div class="mt-candidate-header">
                    <div class="mt-candidate-avatar">
                        ${candidate.avatar ? 
                            `<img src="${candidate.avatar}" alt="${candidate.name}">` :
                            `<div class="mt-avatar-placeholder">${getInitials(candidate.name)}</div>`
                        }
                    </div>
                    <div class="mt-candidate-info">
                        <h4 class="mt-candidate-name">${escapeHtml(candidate.name)}</h4>
                        <p class="mt-candidate-position">${escapeHtml(candidate.position)}</p>
                        <p class="mt-candidate-company">${escapeHtml(candidate.company)}</p>
                    </div>
                </div>
                <div class="mt-candidate-footer">
                    <span class="mt-category-badge ${categoryClass}">
                        ${escapeHtml(candidate.category)}
                    </span>
                    <span class="mt-assignment-indicator ${candidate.assigned ? 'assigned' : 'unassigned'}">
                        ${candidate.assigned ? 
                            `<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <path d="M13.5 4.5L6 12L2.5 8.5" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Assigned` : 
                            `<svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                <circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="2"/>
                            </svg>
                            Unassigned`
                        }
                    </span>
                </div>
            </div>
        `);
    }

    // Render Jury Members
    function renderJuryMembers(juryMembers) {
        elements.juryList.empty();

        juryMembers.forEach((jury, index) => {
            const juryEl = createJuryElement(jury);
            elements.juryList.append(juryEl);
            
            // Animate appearance
            setTimeout(() => {
                juryEl.addClass('animate-in');
            }, index * 50);
        });
    }

    // Create Jury Element
    function createJuryElement(jury) {
        const isSelected = state.selectedJuryMember === jury.id;
        const progressPercent = (jury.assignments / jury.maxAssignments) * 100;
        const progressClass = progressPercent > 80 ? 'high' : progressPercent > 50 ? 'medium' : 'low';
        
        return $(`
            <div class="mt-jury-item ${isSelected ? 'active' : ''}" 
                 data-jury-id="${jury.id}">
                <div class="mt-jury-header">
                    <div class="mt-jury-avatar">
                        ${jury.avatar ? 
                            `<img src="${jury.avatar}" alt="${jury.name}">` :
                            `<div class="mt-avatar-placeholder">${getInitials(jury.name)}</div>`
                        }
                    </div>
                    <div class="mt-jury-info">
                        <h4 class="mt-jury-name">
                            ${escapeHtml(jury.name)}
                            ${jury.role ? `<span class="mt-role-badge ${jury.role}">${jury.role}</span>` : ''}
                        </h4>
                        <p class="mt-jury-position">${escapeHtml(jury.position)}</p>
                        <p class="mt-jury-expertise">${escapeHtml(jury.expertise)}</p>
                    </div>
                </div>
                <div class="mt-jury-stats">
                    <div class="mt-stats-row">
                        <span class="mt-stats-label">Assignments:</span>
                        <span class="mt-stats-value">${jury.assignments} / ${jury.maxAssignments}</span>
                    </div>
                    <div class="mt-progress-wrapper">
                        <div class="mt-progress-bar">
                            <div class="mt-progress-fill ${progressClass}" 
                                 style="width: ${Math.min(progressPercent, 100)}%"></div>
                        </div>
                    </div>
                    ${jury.lastActive ? 
                        `<p class="mt-last-active">Last active: ${formatRelativeTime(jury.lastActive)}</p>` : 
                        ''
                    }
                </div>
            </div>
        `);
    }

    // Handle Candidate Click
    function handleCandidateClick($element) {
        const candidateId = $element.data('candidate-id');
        toggleCandidateSelection($element);
        updateSelectionInfo();
    }

    // Toggle Candidate Selection
    function toggleCandidateSelection($element) {
        const candidateId = $element.data('candidate-id').toString();
        
        if (state.selectedCandidates.has(candidateId)) {
            state.selectedCandidates.delete(candidateId);
            $element.removeClass('selected');
            animateDeselection($element);
        } else {
            if (state.selectedCandidates.size >= CONFIG.maxSelectableItems) {
                showNotification(`Maximum ${CONFIG.maxSelectableItems} items can be selected`, 'warning');
                return;
            }
            state.selectedCandidates.add(candidateId);
            $element.addClass('selected');
            animateSelection($element);
        }
        
        updateSelectionInfo();
    }

    // Handle Jury Click
    function handleJuryClick() {
        const $element = $(this);
        const juryId = $element.data('jury-id');
        
        if (state.selectedJuryMember === juryId) {
            state.selectedJuryMember = null;
            $element.removeClass('active');
        } else {
            $('.mt-jury-item').removeClass('active');
            state.selectedJuryMember = juryId;
            $element.addClass('active');
            animateSelection($element);
        }
        
        updateSelectionInfo();
    }

    // Update Selection Info
    function updateSelectionInfo() {
        const count = state.selectedCandidates.size;
        $('.mt-selected-candidates-count').text(count);
        
        const juryName = state.selectedJuryMember ? 
            $(`.mt-jury-item[data-jury-id="${state.selectedJuryMember}"]`).find('.mt-jury-name').text() : 
            'None';
        $('.mt-selected-jury-name').text(juryName);
        
        // Enable/disable manual assign button
        elements.buttons.manualAssign.prop('disabled', count === 0 || !state.selectedJuryMember);
        
        // Update button appearance
        if (count > 0 && state.selectedJuryMember) {
            elements.buttons.manualAssign.addClass('ready');
        } else {
            elements.buttons.manualAssign.removeClass('ready');
        }
    }

    // Apply Filters
    function applyFilters() {
        state.filters.stage = $('#mt-stage-filter').val();
        state.filters.category = $('.mt-tag-active').data('category') || $('#mt-category-filter').val();
        state.filters.assignment = $('#mt-assignment-filter').val();
        
        filterCandidates();
    }

    // Filter Candidates
    function filterCandidates() {
        let visibleCount = 0;
        
        $('.mt-candidate-item').each(function() {
            const $item = $(this);
            const category = $item.data('category');
            const isAssigned = $item.find('.mt-assignment-indicator').hasClass('assigned');
            const searchText = $item.text().toLowerCase();
            
            let show = true;
            
            // Category filter
            if (state.filters.category && category !== state.filters.category) {
                show = false;
            }
            
            // Assignment filter
            if (state.filters.assignment) {
                if (state.filters.assignment === 'assigned' && !isAssigned) show = false;
                if (state.filters.assignment === 'unassigned' && isAssigned) show = false;
            }
            
            // Search filter
            if (state.filters.candidateSearch && !searchText.includes(state.filters.candidateSearch.toLowerCase())) {
                show = false;
            }
            
            if (show) {
                $item.show();
                visibleCount++;
            } else {
                $item.hide();
            }
        });
        
        // Update count
        $('.mt-candidates-count').text(visibleCount);
        
        // Show empty state if needed
        if (visibleCount === 0) {
            if (!elements.candidatesList.find('.mt-empty-state').length) {
                elements.candidatesList.append(`
                    <div class="mt-empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z"/>
                        </svg>
                        <p>No candidates match your filters</p>
                        <button class="mt-btn mt-btn-sm" onclick="clearFilters()">Clear Filters</button>
                    </div>
                `);
            }
        } else {
            elements.candidatesList.find('.mt-empty-state').remove();
        }
    }

    // Handle Search
    function handleCandidateSearch() {
        state.filters.candidateSearch = $('#mt-candidates-search').val();
        filterCandidates();
    }

    function handleJurySearch() {
        const searchTerm = $('#mt-jury-search').val().toLowerCase();
        
        $('.mt-jury-item').each(function() {
            const $item = $(this);
            const text = $item.text().toLowerCase();
            
            if (text.includes(searchTerm)) {
                $item.show();
            } else {
                $item.hide();
            }
        });
    }

    // Select All Visible Candidates
    function selectAllVisibleCandidates() {
        state.selectedCandidates.clear();
        
        $('.mt-candidate-item:visible').each(function() {
            const candidateId = $(this).data('candidate-id').toString();
            if (state.selectedCandidates.size < CONFIG.maxSelectableItems) {
                state.selectedCandidates.add(candidateId);
                $(this).addClass('selected');
            }
        });
        
        updateSelectionInfo();
        showNotification(`Selected ${state.selectedCandidates.size} candidates`, 'info');
    }

    // Clear Selection
    function clearSelection() {
        state.selectedCandidates.clear();
        state.selectedJuryMember = null;
        
        $('.mt-candidate-item').removeClass('selected');
        $('.mt-jury-item').removeClass('active');
        
        updateSelectionInfo();
    }

    // Open Auto-Assign Modal
    function openAutoAssignModal() {
        elements.modals.autoAssign.addClass('show');
        $('body').addClass('modal-open');
        
        // Focus first input
        setTimeout(() => {
            $('#mt-candidates-per-jury').focus();
        }, CONFIG.animationDuration);
    }

    // Close Modal
    function closeModal() {
        $('.mt-modal').removeClass('show');
        $('body').removeClass('modal-open');
    }

    // Execute Auto Assignment
    function executeAutoAssignment() {
        const candidatesPerJury = parseInt($('#mt-candidates-per-jury').val());
        const algorithm = $('.mt-algorithm-card.selected').data('algorithm');
        const options = {
            balanceCategories: $('#mt-balance-categories').is(':checked'),
            matchExpertise: $('#mt-match-expertise').is(':checked'),
            clearExisting: $('#mt-clear-existing').is(':checked')
        };
        
        if (!candidatesPerJury || candidatesPerJury < 1) {
            showNotification('Please enter valid candidates per jury', 'error');
            return;
        }
        
        // Show loading state
        $('#mt-assignment-loading').addClass('show');
        $('#mt-execute-auto-assign').prop('disabled', true);
        
        // Simulate progress
        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += Math.random() * 20;
            if (progress > 100) progress = 100;
            
            $('#mt-assignment-loading .mt-progress-fill').css('width', progress + '%');
            
            if (progress >= 100) {
                clearInterval(progressInterval);
                completeAutoAssignment();
            }
        }, 300);
    }

    // Complete Auto Assignment
    function completeAutoAssignment() {
        setTimeout(() => {
            $('#mt-assignment-loading').removeClass('show');
            $('#mt-execute-auto-assign').prop('disabled', false);
            closeModal();
            
            showNotification('Auto-assignment completed successfully!', 'success');
            
            // Refresh data
            refreshData();
        }, 500);
    }

    // Perform Manual Assignment
    function performManualAssignment() {
        if (state.selectedCandidates.size === 0 || !state.selectedJuryMember) {
            showNotification('Please select candidates and a jury member', 'error');
            return;
        }
        
        const candidateIds = Array.from(state.selectedCandidates);
        const juryMemberId = state.selectedJuryMember;
        
        // Show confirmation
        const confirmMessage = `Assign ${candidateIds.length} candidate(s) to the selected jury member?`;
        if (!confirm(confirmMessage)) return;
        
        // Show loading state
        elements.buttons.manualAssign.prop('disabled', true).html(`
            <span class="mt-spinner-inline"></span>
            Assigning...
        `);
        
        // Simulate assignment (replace with actual AJAX)
        setTimeout(() => {
            elements.buttons.manualAssign.prop('disabled', false).html(`
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M6 8L8 10L12 6" stroke="currentColor" stroke-width="2"/>
                </svg>
                Manual Assign
            `);
            
            showNotification('Assignment completed successfully!', 'success');
            clearSelection();
            refreshData();
        }, 1500);
    }

    // Export Data
    function exportData() {
        const exportOptions = [
            { value: 'csv', label: 'CSV Format', icon: '📊' },
            { value: 'json', label: 'JSON Format', icon: '📋' },
            { value: 'pdf', label: 'PDF Report', icon: '📄' }
        ];
        
        // Create export modal
        const modalHtml = `
            <div class="mt-export-modal mt-modal show">
                <div class="mt-modal-backdrop"></div>
                <div class="mt-modal-container">
                    <div class="mt-modal-content" style="max-width: 400px;">
                        <div class="mt-modal-header">
                            <h3 class="mt-modal-title">Export Data</h3>
                            <button class="mt-modal-close">&times;</button>
                        </div>
                        <div class="mt-modal-body">
                            <p>Select export format:</p>
                            <div class="mt-export-options">
                                ${exportOptions.map(opt => `
                                    <button class="mt-export-option" data-format="${opt.value}">
                                        <span class="mt-export-icon">${opt.icon}</span>
                                        <span>${opt.label}</span>
                                    </button>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Handle export option click
        $('.mt-export-option').on('click', function() {
            const format = $(this).data('format');
            performExport(format);
            $('.mt-export-modal').remove();
        });
        
        // Close modal
        $('.mt-export-modal .mt-modal-close, .mt-export-modal .mt-modal-backdrop').on('click', function() {
            $('.mt-export-modal').remove();
        });
    }

    // Perform Export
    function performExport(format) {
        showNotification(`Exporting data as ${format.toUpperCase()}...`, 'info');
        
        // Simulate export
        setTimeout(() => {
            showNotification(`Export completed! File downloaded.`, 'success');
        }, 1000);
    }

    // Refresh Data
    function refreshData() {
        showNotification('Refreshing data...', 'info');
        
        elements.buttons.refresh.html(`
            <svg class="spin" width="16" height="16" viewBox="0 0 16 16" fill="none">
                <path d="M14 8C14 11.3137 11.3137 14 8 14C5.68629 14 3.72708 12.6176 2.78549 10.6479" stroke="currentColor" stroke-width="2"/>
                <path d="M2 8C2 4.68629 4.68629 2 8 2C10.2958 2 12.2729 3.38235 13.2145 5.35206" stroke="currentColor" stroke-width="2"/>
            </svg>
            Refreshing...
        `);
        
        // Don't reload mock data - fetch real data
        loadInitialData(); // This will now use the real data flow
        
        // Reset button after loading
        setTimeout(() => {
            elements.buttons.refresh.html(`
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M14 8C14 11.3137 11.3137 14 8 14C5.68629 14 3.72708 12.6176 2.78549 10.6479" stroke="currentColor" stroke-width="2"/>
                    <path d="M2 8C2 4.68629 4.68629 2 8 2C10.2958 2 12.2729 3.38235 13.2145 5.35206" stroke="currentColor" stroke-width="2"/>
                </svg>
                Refresh
            `);
            state.lastUpdate = new Date();
        }, 1000);
    }

    // Update Statistics
    function updateStatistics(stats) {
        // Animate number changes
        Object.keys(stats).forEach(key => {
            const element = elements.statsElements[key];
            if (element && element.length) {
                const currentValue = parseInt(element.text()) || 0;
                const newValue = stats[key];
                
                animateNumber(element, currentValue, newValue);
            }
        });
    }

    // Animate Number
    function animateNumber($element, from, to) {
        const duration = 1000;
        const start = performance.now();
        
        function update(currentTime) {
            const elapsed = currentTime - start;
            const progress = Math.min(elapsed / duration, 1);
            
            const value = Math.floor(from + (to - from) * easeOutQuad(progress));
            
            if (typeof to === 'number') {
                $element.text(value);
            } else {
                $element.text(to); // For percentage strings
            }
            
            if (progress < 1) {
                requestAnimationFrame(update);
            }
        }
        
        requestAnimationFrame(update);
    }

    // Easing Function
    function easeOutQuad(t) {
        return t * (2 - t);
    }

    // Show Notification
    function showNotification(message, type = 'info') {
        const toastId = 'toast-' + Date.now();
        const iconMap = {
            success: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" stroke="currentColor" stroke-width="2"/><path d="M7 10L9 12L13 8" stroke="currentColor" stroke-width="2"/></svg>',
            error: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" stroke="currentColor" stroke-width="2"/><path d="M13 7L7 13M7 7L13 13" stroke="currentColor" stroke-width="2"/></svg>',
            warning: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M8.57 3.214C9.171 2.026 10.829 2.026 11.43 3.214L17.523 14.111C18.099 15.252 17.287 16.625 15.993 16.625H3.807C2.513 16.625 1.701 15.252 2.277 14.111L8.57 3.214Z" stroke="currentColor" stroke-width="2"/><path d="M10 7V11M10 14H10.01" stroke="currentColor" stroke-width="2"/></svg>',
            info: '<svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 18C14.4183 18 18 14.4183 18 10C18 5.58172 14.4183 2 10 2C5.58172 2 2 5.58172 2 10C2 14.4183 5.58172 18 10 18Z" stroke="currentColor" stroke-width="2"/><path d="M10 14V10M10 6H10.01" stroke="currentColor" stroke-width="2"/></svg>'
        };
        
        const toast = $(`
            <div id="${toastId}" class="mt-toast mt-toast-${type}">
                <span class="mt-toast-icon">${iconMap[type]}</span>
                <span class="mt-toast-message">${escapeHtml(message)}</span>
                <button class="mt-toast-close">&times;</button>
            </div>
        `);
        
        $('#mt-toast-container').append(toast);
        
        // Animate in
        setTimeout(() => toast.addClass('show'), 10);
        
        // Auto remove
        const timeout = setTimeout(() => removeToast(toastId), CONFIG.toastDuration);
        
        // Manual close
        toast.find('.mt-toast-close').on('click', () => {
            clearTimeout(timeout);
            removeToast(toastId);
        });
    }

    // Remove Toast
    function removeToast(toastId) {
        const toast = $('#' + toastId);
        toast.removeClass('show');
        setTimeout(() => toast.remove(), CONFIG.animationDuration);
    }

    // Animation Functions
    function animateSelection($element) {
        $element.addClass('pulse');
        setTimeout(() => $element.removeClass('pulse'), 600);
    }

    function animateDeselection($element) {
        $element.addClass('shake');
        setTimeout(() => $element.removeClass('shake'), 300);
    }

    // Show Welcome Animation
    function showWelcomeAnimation() {
        $('.mt-assignment-header').addClass('animate-in');
        $('.mt-stat-card').each(function(index) {
            setTimeout(() => $(this).addClass('animate-in'), index * 100);
        });
    }

    // Loading States
    function showLoadingState() {
        state.isLoading = true;
        elements.candidatesList.html('<div class="mt-loading-skeleton"></div>');
        elements.juryList.html('<div class="mt-loading-skeleton"></div>');
    }

    function hideLoadingState() {
        state.isLoading = false;
    }

    // Keyboard Shortcuts
    function handleKeyboardShortcuts(e) {
        // Ctrl/Cmd + A: Select all
        if ((e.ctrlKey || e.metaKey) && e.key === 'a' && $(e.target).is('body')) {
            e.preventDefault();
            selectAllVisibleCandidates();
        }
        
        // Ctrl/Cmd + D: Deselect all
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            clearSelection();
        }
        
        // Ctrl/Cmd + E: Export
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            exportData();
        }
        
        // Ctrl/Cmd + R: Refresh
        if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
            e.preventDefault();
            refreshData();
        }
        
        // Delete: Clear selection
        if (e.key === 'Delete' && !$(e.target).is('input, textarea')) {
            clearSelection();
        }
    }

    // Handle Window Resize
    function handleResize() {
        // Adjust grid columns based on screen size
        const width = $(window).width();
        if (width < 768) {
            $('.mt-assignment-main-grid').addClass('mobile');
        } else {
            $('.mt-assignment-main-grid').removeClass('mobile');
        }
    }

    // Data Management Functions
    function exportAssignments() {
        const btn = $('#mt-export-assignments-btn');
        btn.prop('disabled', true).html(`
            <span class="mt-spinner-inline"></span>
            Exporting...
        `);

        // Simulate export
        setTimeout(() => {
            const data = generateExportData();
            downloadFile('assignments-export.csv', convertToCSV(data));
            
            btn.prop('disabled', false).html(`
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M10 1V11M10 11L7 8M10 11L13 8" stroke="currentColor" stroke-width="2"/>
                </svg>
                Export Assignments
            `);
            
            showNotification('Assignments exported successfully!', 'success');
        }, 1500);
    }

    function syncSystem() {
        const btn = $('#mt-sync-system-btn');
        
        if (!confirm('This will synchronize all assignment data. Continue?')) {
            return;
        }
        
        btn.prop('disabled', true).html(`
            <span class="mt-spinner-inline"></span>
            Syncing...
        `);

        // Simulate sync
        setTimeout(() => {
            btn.prop('disabled', false).html(`
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                    <path d="M4 10C4 13.3137 6.68629 16 10 16" stroke="currentColor" stroke-width="2"/>
                </svg>
                Sync System
            `);
            
            showNotification('System synchronized successfully!', 'success');
            refreshData();
        }, 2000);
    }

    function viewProgress() {
        // Create progress modal
        const modalHtml = `
            <div class="mt-progress-modal mt-modal show">
                <div class="mt-modal-backdrop"></div>
                <div class="mt-modal-container">
                    <div class="mt-modal-content" style="max-width: 800px;">
                        <div class="mt-modal-header">
                            <h3 class="mt-modal-title">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M9 11L12 14L22 4" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Assignment Progress Overview
                            </h3>
                            <button class="mt-modal-close">&times;</button>
                        </div>
                        <div class="mt-modal-body">
                            <div class="mt-progress-charts">
                                <div class="mt-chart-container">
                                    <h4>Assignment Distribution</h4>
                                    <canvas id="mt-distribution-chart"></canvas>
                                </div>
                                <div class="mt-chart-container">
                                    <h4>Category Breakdown</h4>
                                    <canvas id="mt-category-chart"></canvas>
                                </div>
                            </div>
                            <div class="mt-progress-stats">
                                <div class="mt-progress-stat">
                                    <span class="mt-progress-label">Average Load:</span>
                                    <span class="mt-progress-value">8.5 candidates/jury</span>
                                </div>
                                <div class="mt-progress-stat">
                                    <span class="mt-progress-label">Completion Rate:</span>
                                    <span class="mt-progress-value">75%</span>
                                </div>
                                <div class="mt-progress-stat">
                                    <span class="mt-progress-label">Time Remaining:</span>
                                    <span class="mt-progress-value">14 days</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-modal-footer">
                            <button class="mt-btn mt-btn-primary mt-download-report">
                                Download Full Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Initialize charts (placeholder)
        initializeProgressCharts();
        
        // Handle close
        $('.mt-progress-modal .mt-modal-close, .mt-progress-modal .mt-modal-backdrop').on('click', function() {
            $('.mt-progress-modal').remove();
        });
        
        // Handle download
        $('.mt-download-report').on('click', function() {
            showNotification('Downloading progress report...', 'info');
            setTimeout(() => {
                showNotification('Report downloaded successfully!', 'success');
            }, 1000);
        });
    }

    function resetAssignments() {
        // Show confirmation modal
        const modalHtml = `
            <div class="mt-confirm-modal mt-modal show">
                <div class="mt-modal-backdrop"></div>
                <div class="mt-modal-container">
                    <div class="mt-modal-content" style="max-width: 400px;">
                        <div class="mt-modal-header">
                            <h3 class="mt-modal-title" style="color: var(--mt-error);">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 8V12M12 16H12.01" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                Reset All Assignments
                            </h3>
                            <button class="mt-modal-close">&times;</button>
                        </div>
                        <div class="mt-modal-body">
                            <p><strong>Warning:</strong> This action will permanently remove ALL current assignments.</p>
                            <p>Are you absolutely sure you want to continue?</p>
                            <div class="mt-confirm-input">
                                <label>Type "RESET" to confirm:</label>
                                <input type="text" id="mt-reset-confirm" placeholder="Type RESET" style="text-transform: uppercase;">
                            </div>
                        </div>
                        <div class="mt-modal-footer">
                            <button class="mt-btn mt-btn-ghost mt-modal-close">Cancel</button>
                            <button class="mt-btn mt-btn-danger" id="mt-confirm-reset" disabled>
                                Reset All Assignments
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Enable confirm button when user types RESET
        $('#mt-reset-confirm').on('input', function() {
            const value = $(this).val().toUpperCase(); // Convert to uppercase for comparison
            $('#mt-confirm-reset').prop('disabled', value !== 'RESET');
        });
        
        // Handle confirm
        $('#mt-confirm-reset').on('click', function() {
            $(this).prop('disabled', true).html(`
                <span class="mt-spinner-inline"></span>
                Resetting...
            `);
            
            setTimeout(() => {
                $('.mt-confirm-modal').remove();
                showNotification('All assignments have been reset', 'success');
                clearSelection();
                refreshData();
            }, 2000);
        });
        
        // Handle close
        $('.mt-confirm-modal .mt-modal-close, .mt-confirm-modal .mt-modal-backdrop').on('click', function() {
            $('.mt-confirm-modal').remove();
        });
    }

    // Helper Functions
    function generateMockData() {
        const categories = ['Established Companies', 'Start-ups & New Makers', 'Infrastructure/Politics/Public'];
        const candidates = [];
        const juryMembers = [];
        
        // Generate candidates
        for (let i = 1; i <= 50; i++) {
            candidates.push({
                id: i,
                name: `Candidate ${i}`,
                position: `Position ${i}`,
                company: `Company ${i}`,
                category: categories[Math.floor(Math.random() * categories.length)],
                assigned: Math.random() > 0.5,
                avatar: null
            });
        }
        
        // Generate jury members
        for (let i = 1; i <= 10; i++) {
            juryMembers.push({
                id: i,
                name: `Jury Member ${i}`,
                position: `Expert ${i}`,
                expertise: categories[Math.floor(Math.random() * categories.length)],
                assignments: Math.floor(Math.random() * 10),
                maxAssignments: 10,
                role: i === 1 ? 'president' : i === 2 ? 'vice-president' : null,
                lastActive: new Date(Date.now() - Math.random() * 7 * 24 * 60 * 60 * 1000),
                avatar: null
            });
        }
        
        return {
            candidates,
            juryMembers,
            statistics: {
                totalCandidates: 50,
                totalJury: 10,
                assignedCount: 25,
                completionRate: '50.0%',
                avgPerJury: '5.0'
            }
        };
    }

    function generateExportData() {
        return [
            ['Candidate Name', 'Company', 'Category', 'Assigned To', 'Status'],
            ['John Doe', 'Tesla', 'Established Companies', 'Jane Smith', 'Assigned'],
            ['Jane Doe', 'Startup X', 'Start-ups', 'John Smith', 'Assigned'],
            // ... more data
        ];
    }

    function convertToCSV(data) {
        return data.map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    }

    function downloadFile(filename, content) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = filename;
        link.click();
        URL.revokeObjectURL(link.href);
    }

    function getInitials(name) {
        return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2);
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    function formatRelativeTime(date) {
        const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
        const diff = Date.now() - date.getTime();
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        
        if (days === 0) return 'today';
        if (days === 1) return 'yesterday';
        if (days < 7) return rtf.format(-days, 'day');
        if (days < 30) return rtf.format(-Math.floor(days / 7), 'week');
        return rtf.format(-Math.floor(days / 30), 'month');
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Auto-refresh functionality
    function startAutoRefresh() {
        setInterval(() => {
            if (!state.isLoading && document.visibilityState === 'visible') {
                refreshData();
            }
        }, CONFIG.refreshInterval);
    }

    // Sortable initialization
    function initializeSortable() {
        // Make lists sortable if jQuery UI is available
        if ($.fn.sortable) {
            elements.candidatesList.sortable({
                items: '.mt-candidate-item',
                placeholder: 'mt-sortable-placeholder',
                handle: '.mt-candidate-header',
                tolerance: 'pointer',
                cursor: 'move',
                opacity: 0.8,
                revert: 200
            });
            
            elements.juryList.sortable({
                items: '.mt-jury-item',
                placeholder: 'mt-sortable-placeholder',
                handle: '.mt-jury-header',
                tolerance: 'pointer',
                cursor: 'move',
                opacity: 0.8,
                revert: 200
            });
        }
    }

    // Tooltip initialization
    function initializeTooltips() {
        // Simple tooltip implementation
        $(document).on('mouseenter', '[data-tooltip]', function() {
            const $this = $(this);
            const text = $this.data('tooltip');
            const tooltip = $(`<div class="mt-tooltip">${text}</div>`);
            
            $('body').append(tooltip);
            
            const offset = $this.offset();
            tooltip.css({
                top: offset.top - tooltip.outerHeight() - 10,
                left: offset.left + ($this.outerWidth() / 2) - (tooltip.outerWidth() / 2)
            });
            
            setTimeout(() => tooltip.addClass('show'), 10);
        }).on('mouseleave', '[data-tooltip]', function() {
            $('.mt-tooltip').remove();
        });
    }

    // Ripple effect for buttons
    function addRippleEffect() {
        $(document).on('click', '.mt-btn, .mt-action-btn, .mt-data-btn', function(e) {
            const $btn = $(this);
            const offset = $btn.offset();
            const x = e.pageX - offset.left;
            const y = e.pageY - offset.top;
            
            const ripple = $('<span class="mt-ripple"></span>');
            ripple.css({
                left: x + 'px',
                top: y + 'px'
            });
            
            $btn.append(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    }

    // Initialize progress charts (placeholder)
    function initializeProgressCharts() {
        // This would use Chart.js or similar library
        // For now, just show placeholder
        $('#mt-distribution-chart').html('<div style="height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">Chart Placeholder</div>');
        $('#mt-category-chart').html('<div style="height: 200px; background: #f0f0f0; display: flex; align-items: center; justify-content: center;">Chart Placeholder</div>');
    }

    // Multi-select with shift key
    function handleShiftSelect($element) {
        const $items = $('.mt-candidate-item:visible');
        const currentIndex = $items.index($element);
        const lastSelectedIndex = $items.index($('.mt-candidate-item.selected').last());
        
        if (lastSelectedIndex !== -1 && currentIndex !== lastSelectedIndex) {
            const start = Math.min(currentIndex, lastSelectedIndex);
            const end = Math.max(currentIndex, lastSelectedIndex);
            
            for (let i = start; i <= end; i++) {
                const $item = $items.eq(i);
                const candidateId = $item.data('candidate-id').toString();
                
                if (!state.selectedCandidates.has(candidateId)) {
                    state.selectedCandidates.add(candidateId);
                    $item.addClass('selected');
                }
            }
            
            updateSelectionInfo();
        }
    }

    // Public API for external use
    window.MTAssignment = {
        refresh: refreshData,
        clearSelection: clearSelection,
        exportData: exportData,
        showNotification: showNotification,
        getSelectedCandidates: () => Array.from(state.selectedCandidates),
        getSelectedJuryMember: () => state.selectedJuryMember
    };

})(jQuery);