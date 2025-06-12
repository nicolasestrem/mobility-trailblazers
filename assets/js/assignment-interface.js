/**
 * Mobility Trailblazers - Complete Advanced Assignment Interface
 * File: assets/js/assignment-interface.js
 * Version: 3.0.1 - Fixed debounce function issue
 */

(function($) {
    'use strict';

    // Global configuration
    const MT_ASSIGNMENT_CONFIG = {
        AUTO_REFRESH_INTERVAL: 30000,
        NOTIFICATION_TIMEOUT: 5000,
        MAX_RETRIES: 3,
        RETRY_DELAY: 1000,
        DEBOUNCE_DELAY: 300,
        CHART_COLORS: {
            primary: '#4299e1',
            success: '#48bb78',
            warning: '#ed8936',
            danger: '#f56565',
            info: '#3182ce',
            secondary: '#64748b'
        }
    };

    /**
     * Debounce utility function
     */
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

    /**
     * Main Assignment Interface Class - Complete Implementation with Fixed Debounce
     */
    class AssignmentInterface {
        constructor() {
            this.selectedCandidates = new Set();
            this.selectedJury = new Set();
            this.currentStage = 'semifinal';
            this.candidates = [];
            this.juryMembers = [];
            this.assignmentData = {};
            this.isInitialized = false;
            this.autoRefreshInterval = null;
            this.dragDropEnabled = false;
            
            // API configuration
            this.apiUrl = (window.mtAssignment && window.mtAssignment.apiUrl) || '/wp-json/mt/v1/';
            this.nonce = (window.mtAssignment && window.mtAssignment.nonce) || '';
            this.ajaxUrl = (window.mtAssignment && window.mtAssignment.ajaxUrl) || '/wp-admin/admin-ajax.php';
            this.adminNonce = (window.mtAssignment && window.mtAssignment.adminNonce) || '';
            
            // Filters and search
            this.currentFilters = {
                category: '',
                search: '',
                assignmentStatus: 'all'
            };
            
            this.init();
        }
        
        /**
         * Initialize the assignment interface
         */
        init() {
            if (this.isInitialized) return;
            
            console.log('🚀 Initializing Complete Assignment Interface v3.0');
            
            if (!this.validateEnvironment()) {
                this.showError('Assignment interface configuration error. Please check your permissions.');
                return;
            }
            
            this.setupEventHandlers();
            this.initializeInterface();
            this.enableAutoRefresh();
            this.initializeDragDrop();
            
            this.isInitialized = true;
        }
        
        /**
         * Validate environment and permissions
         */
        validateEnvironment() {
            if (!window.mtAssignment) {
                console.error('❌ mtAssignment configuration not found');
                return false;
            }
            
            if (!this.nonce) {
                console.error('❌ Security nonce not found');
                return false;
            }
            
            return true;
        }
        
        /**
         * Setup all event handlers - Complete implementation
         */
        setupEventHandlers() {
            console.log('🔧 Setting up event handlers...');
            
            // Stage filter change
            $(document).on('change', '#mtStageFilter', (e) => {
                console.log('Stage filter changed:', e.target.value);
                this.changeStage(e.target.value);
            });
            
            // Search functionality with debounce
            $(document).on('input', '#mtSearchCandidates', debounce((e) => {
                console.log('Search input:', e.target.value);
                this.currentFilters.search = e.target.value;
                this.performSearch(e.target.value);
            }, MT_ASSIGNMENT_CONFIG.DEBOUNCE_DELAY));
            
            // Category filter
            $(document).on('change', '#mtCategoryFilter', (e) => {
                console.log('Category filter changed:', e.target.value);
                this.currentFilters.category = e.target.value;
                this.filterByCategory(e.target.value);
            });
            
            // Assignment status filter
            $(document).on('change', '#mtAssignmentStatusFilter', (e) => {
                console.log('Assignment status filter changed:', e.target.value);
                this.currentFilters.assignmentStatus = e.target.value;
                this.filterByAssignmentStatus(e.target.value);
            });
            
            // Selection handlers with delegation
            $(document).on('change', '.mt-candidate-checkbox', (e) => {
                console.log('Candidate selection changed:', e.target.checked);
                this.handleCandidateSelection(e);
            });
            
            $(document).on('change', '.mt-jury-checkbox', (e) => {
                console.log('Jury selection changed:', e.target.checked);
                this.handleJurySelection(e);
            });
            
            // Action button handlers
            $(document).on('click', '[data-action="auto-assign"]', (e) => {
                console.log('Auto assign clicked');
                e.preventDefault();
                this.openAutoAssignModal();
            });
            
            $(document).on('click', '[data-action="bulk-actions"]', (e) => {
                console.log('Bulk actions clicked');
                e.preventDefault();
                this.openBulkActionsModal();
            });
            
            $(document).on('click', '[data-action="refresh-data"]', (e) => {
                console.log('Refresh data clicked');
                e.preventDefault();
                this.refreshAllData();
            });
            
            $(document).on('click', '[data-action="assign-selected"]', (e) => {
                console.log('Assign selected clicked');
                e.preventDefault();
                this.assignSelected();
            });
            
            $(document).on('click', '[data-action="remove-selected"]', (e) => {
                console.log('Remove selected clicked');
                e.preventDefault();
                this.removeSelected();
            });
            
            // Select all buttons
            $(document).on('click', '#mtSelectAllCandidates', (e) => {
                console.log('Select all candidates clicked');
                e.preventDefault();
                this.selectAllCandidates();
            });
            
            $(document).on('click', '#mtSelectAllJury', (e) => {
                console.log('Select all jury clicked');
                e.preventDefault();
                this.selectAllJury();
            });
            
            // Clear selection buttons
            $(document).on('click', '#mtClearCandidates', (e) => {
                console.log('Clear candidates clicked');
                e.preventDefault();
                this.clearCandidateSelection();
            });
            
            $(document).on('click', '#mtClearJury', (e) => {
                console.log('Clear jury clicked');
                e.preventDefault();
                this.clearJurySelection();
            });
            
            // Modal handlers
            $(document).on('click', '.mt-modal-overlay, .mt-modal-close', (e) => {
                if (e.target === e.currentTarget) {
                    console.log('Modal close clicked');
                    this.closeModal($(e.target).closest('.mt-modal-overlay'));
                }
            });
            
            // Auto-assign form submission
            $(document).on('submit', '#mtAutoAssignForm', (e) => {
                console.log('Auto assign form submitted');
                e.preventDefault();
                this.executeAutoAssign();
            });
            
            // Bulk actions form submission
            $(document).on('submit', '#mtBulkActionsForm', (e) => {
                console.log('Bulk actions form submitted');
                e.preventDefault();
                this.executeBulkAction();
            });
            
            // Assignment toggle handlers
            $(document).on('click', '.mt-assignment-toggle', (e) => {
                console.log('Assignment toggle clicked:', e.target.dataset);
                e.preventDefault();
                this.toggleAssignment(e.target.dataset.candidateId, e.target.dataset.juryId);
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                this.handleKeyboardShortcuts(e);
            });
            
            // Window resize handler for responsive design
            $(window).on('resize.mt-assignment', debounce(() => {
                this.handleWindowResize();
            }, 250));
            
            console.log('✅ Event handlers setup complete');
        }
        
        /**
         * Initialize the interface with data loading
         */
        async initializeInterface() {
            this.showLoading('Initializing assignment interface...');
            
            try {
                await this.loadInitialData();
                this.hideLoading();
                this.showSuccess('Assignment interface loaded successfully');
                this.updateInterfaceState();
            } catch (error) {
                this.hideLoading();
                this.showError('Failed to initialize interface: ' + error.message);
                console.error('❌ Interface initialization error:', error);
            }
        }
        
        /**
         * Load all initial data with parallel requests
         */
        async loadInitialData() {
            try {
                const [candidatesData, juryData, overviewData, phaseData] = await Promise.all([
                    this.apiCall('candidates', 'GET', null, { stage: this.currentStage }),
                    this.apiCall('jury-members', 'GET', null, { stage: this.currentStage, include_workload: true }),
                    this.apiCall('assignment-overview', 'GET', null, { stage: this.currentStage }),
                    this.apiCall('voting-phase')
                ]);
                
                this.candidates = candidatesData.data || [];
                this.juryMembers = juryData.data || [];
                this.assignmentData = overviewData.data || {};
                this.votingPhase = phaseData.data || {};
                
                console.log('✅ Initial data loaded:', {
                    candidates: this.candidates.length,
                    jury: this.juryMembers.length,
                    assignments: this.assignmentData.total_assignments || 0
                });
                
            } catch (error) {
                console.error('❌ Failed to load initial data:', error);
                throw error;
            }
        }
        
        /**
         * Update the interface state after data changes
         */
        updateInterfaceState() {
            this.renderCandidatesList();
            this.renderJuryList();
            this.renderAssignmentOverview();
            this.updateProgressIndicators();
            this.updateActionButtons();
        }
        
        /**
         * Render candidates list with filtering
         */
        renderCandidatesList() {
            const $container = $('#mtCandidatesList');
            if (!$container.length) return;
            
            let filteredCandidates = this.candidates;
            
            // Apply filters
            if (this.currentFilters.search) {
                const searchTerm = this.currentFilters.search.toLowerCase();
                filteredCandidates = filteredCandidates.filter(candidate => 
                    candidate.name?.toLowerCase().includes(searchTerm) ||
                    candidate.company?.toLowerCase().includes(searchTerm) ||
                    candidate.position?.toLowerCase().includes(searchTerm)
                );
            }
            
            if (this.currentFilters.category && this.currentFilters.category !== 'all') {
                filteredCandidates = filteredCandidates.filter(candidate => 
                    candidate.category === this.currentFilters.category
                );
            }
            
            if (this.currentFilters.assignmentStatus && this.currentFilters.assignmentStatus !== 'all') {
                filteredCandidates = filteredCandidates.filter(candidate => {
                    const hasAssignments = candidate.assignments && candidate.assignments.length > 0;
                    return this.currentFilters.assignmentStatus === 'assigned' ? hasAssignments : !hasAssignments;
                });
            }
            
            const candidatesHtml = filteredCandidates.map(candidate => this.renderCandidateCard(candidate)).join('');
            $container.html(candidatesHtml || '<div class="mt-no-results">No candidates found matching your criteria.</div>');
            
            // Update results count
            $('#mtCandidatesCount').text(`${filteredCandidates.length} of ${this.candidates.length} candidates`);
        }
        
        /**
         * Render individual candidate card
         */
        renderCandidateCard(candidate) {
            const isSelected = this.selectedCandidates.has(candidate.id.toString());
            const assignmentCount = candidate.assignments?.length || 0;
            const categoryColor = this.getCategoryColor(candidate.category);
            
            return `
                <div class="mt-candidate-card ${isSelected ? 'selected' : ''}" data-candidate-id="${candidate.id}">
                    <div class="mt-candidate-header">
                        <div class="mt-candidate-checkbox-wrapper">
                            <input type="checkbox" 
                                   class="mt-candidate-checkbox" 
                                   data-candidate-id="${candidate.id}"
                                   ${isSelected ? 'checked' : ''}>
                        </div>
                        <div class="mt-candidate-info">
                            <h4 class="mt-candidate-name">${this.escapeHtml(candidate.name)}</h4>
                            <p class="mt-candidate-company">${this.escapeHtml(candidate.company)}</p>
                            <p class="mt-candidate-position">${this.escapeHtml(candidate.position)}</p>
                        </div>
                        <div class="mt-candidate-meta">
                            <span class="mt-category-badge" style="background-color: ${categoryColor}">
                                ${this.escapeHtml(candidate.category)}
                            </span>
                        </div>
                    </div>
                    <div class="mt-candidate-stats">
                        <div class="mt-stat">
                            <span class="mt-stat-label">Assignments:</span>
                            <span class="mt-stat-value">${assignmentCount}</span>
                        </div>
                        <div class="mt-stat">
                            <span class="mt-stat-label">Votes:</span>
                            <span class="mt-stat-value">${candidate.votes_count || 0}</span>
                        </div>
                        <div class="mt-stat">
                            <span class="mt-stat-label">Avg Score:</span>
                            <span class="mt-stat-value">${candidate.average_score ? candidate.average_score.toFixed(1) : 'N/A'}</span>
                        </div>
                    </div>
                    ${assignmentCount > 0 ? `
                        <div class="mt-candidate-assignments">
                            <h5>Assigned Jury:</h5>
                            <div class="mt-jury-tags">
                                ${candidate.assignments.map(assignment => `
                                    <span class="mt-jury-tag" title="${this.escapeHtml(assignment.jury_name)}">
                                        ${this.escapeHtml(this.truncateText(assignment.jury_name, 20))}
                                        <button class="mt-remove-assignment" 
                                                data-candidate-id="${candidate.id}" 
                                                data-jury-id="${assignment.jury_id}"
                                                title="Remove assignment">×</button>
                                    </span>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        }
        
        /**
         * Render jury members list
         */
        renderJuryList() {
            const $container = $('#mtJuryList');
            if (!$container.length) return;
            
            const juryHtml = this.juryMembers.map(jury => this.renderJuryCard(jury)).join('');
            $container.html(juryHtml || '<div class="mt-no-results">No jury members found.</div>');
        }
        
        /**
         * Render individual jury card
         */
        renderJuryCard(jury) {
            const isSelected = this.selectedJury.has(jury.id.toString());
            const workload = jury.workload_analysis || {};
            const assignmentCount = workload.total_assigned || 0;
            const votingProgress = workload.total_voted || 0;
            const progressPercentage = assignmentCount > 0 ? Math.round((votingProgress / assignmentCount) * 100) : 0;
            
            return `
                <div class="mt-jury-card ${isSelected ? 'selected' : ''}" data-jury-id="${jury.id}">
                    <div class="mt-jury-header">
                        <div class="mt-jury-checkbox-wrapper">
                            <input type="checkbox" 
                                   class="mt-jury-checkbox" 
                                   data-jury-id="${jury.id}"
                                   ${isSelected ? 'checked' : ''}>
                        </div>
                        <div class="mt-jury-info">
                            <h4 class="mt-jury-name">${this.escapeHtml(jury.display_name)}</h4>
                            <p class="mt-jury-email">${this.escapeHtml(jury.user_email)}</p>
                            <p class="mt-jury-company">${this.escapeHtml(jury.company || 'N/A')}</p>
                        </div>
                    </div>
                    <div class="mt-jury-workload">
                        <div class="mt-workload-stats">
                            <div class="mt-stat">
                                <span class="mt-stat-label">Assigned:</span>
                                <span class="mt-stat-value">${assignmentCount}</span>
                            </div>
                            <div class="mt-stat">
                                <span class="mt-stat-label">Voted:</span>
                                <span class="mt-stat-value">${votingProgress}</span>
                            </div>
                            <div class="mt-stat">
                                <span class="mt-stat-label">Progress:</span>
                                <span class="mt-stat-value">${progressPercentage}%</span>
                            </div>
                        </div>
                        <div class="mt-progress-bar">
                            <div class="mt-progress-fill" style="width: ${progressPercentage}%"></div>
                        </div>
                    </div>
                    ${jury.expertise ? `
                        <div class="mt-jury-expertise">
                            <span class="mt-expertise-label">Expertise:</span>
                            <span class="mt-expertise-value">${this.escapeHtml(jury.expertise)}</span>
                        </div>
                    ` : ''}
                </div>
            `;
        }
        
        /**
         * API call wrapper with enhanced error handling
         */
        async apiCall(endpoint, method = 'GET', data = null, params = null) {
            let url = this.apiUrl + endpoint;
            
            if (params && method === 'GET') {
                const searchParams = new URLSearchParams(params);
                url += '?' + searchParams.toString();
            }
            
            const options = {
                method: method,
                headers: {
                    'X-WP-Nonce': this.nonce,
                    'Content-Type': 'application/json'
                }
            };
            
            if (data && (method === 'POST' || method === 'PUT')) {
                options.body = JSON.stringify(data);
            }
            
            try {
                const response = await fetch(url, options);
                
                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || `HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                return result;
                
            } catch (error) {
                throw error;
            }
        }
        
        /**
         * UI Feedback Methods
         */
        showLoading(message = 'Loading...') {
            const loadingHtml = `
                <div class="mt-loading-overlay" id="mtAssignmentLoading">
                    <div class="mt-loading">
                        <div class="mt-spinner"></div>
                        <p>${message}</p>
                    </div>
                </div>
            `;
            
            $('#mtAssignmentLoading').remove();
            $('body').append(loadingHtml);
        }
        
        hideLoading() {
            $('#mtAssignmentLoading').remove();
        }
        
        showSuccess(message) {
            this.showNotification(message, 'success');
        }
        
        showError(message) {
            this.showNotification(message, 'error');
        }
        
        showInfo(message) {
            this.showNotification(message, 'info');
        }
        
        showNotification(message, type = 'info') {
            const notificationId = 'mt-notification-' + Date.now();
            const icon = this.getNotificationIcon(type);
            
            const $notification = $(`
                <div id="${notificationId}" class="mt-notification mt-notification-${type}">
                    <div class="mt-notification-content">
                        <span class="mt-notification-icon">${icon}</span>
                        <span class="mt-notification-message">${message}</span>
                        <button class="mt-notification-close" onclick="$('#${notificationId}').remove()">×</button>
                    </div>
                </div>
            `);
            
            $('body').append($notification);
            
            // Auto remove after timeout
            setTimeout(() => {
                $notification.fadeOut(() => $notification.remove());
            }, MT_ASSIGNMENT_CONFIG.NOTIFICATION_TIMEOUT);
        }
        
        getNotificationIcon(type) {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            return icons[type] || icons.info;
        }
        
        /**
         * Utility Methods
         */
        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        truncateText(text, maxLength) {
            if (!text) return '';
            return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
        }
        
        getCategoryColor(category) {
            const colors = {
                'startups': '#4299e1',
                'established': '#48bb78', 
                'politics': '#ed8936',
                'research': '#9f7aea',
                'default': '#64748b'
            };
            return colors[category?.toLowerCase()] || colors.default;
        }
        
        /**
         * Event Handlers
         */
        handleCandidateSelection(e) {
            const candidateId = e.target.dataset.candidateId;
            const isChecked = e.target.checked;
            
            if (isChecked) {
                this.selectedCandidates.add(candidateId);
            } else {
                this.selectedCandidates.delete(candidateId);
            }
            
            this.updateSelectionUI();
        }
        
        handleJurySelection(e) {
            const juryId = e.target.dataset.juryId;
            const isChecked = e.target.checked;
            
            if (isChecked) {
                this.selectedJury.add(juryId);
            } else {
                this.selectedJury.delete(juryId);
            }
            
            this.updateSelectionUI();
        }
        
        updateSelectionUI() {
            const candidateCount = this.selectedCandidates.size;
            const juryCount = this.selectedJury.size;
            
            $('#mtSelectedCandidatesCount').text(candidateCount);
            $('#mtSelectedJuryCount').text(juryCount);
            
            // Update action button states
            const hasSelections = candidateCount > 0 || juryCount > 0;
            $('.mt-action-btn[data-requires-selection]').prop('disabled', !hasSelections);
        }
        
        /**
         * Cleanup method
         */
        destroy() {
            if (this.autoRefreshInterval) {
                clearInterval(this.autoRefreshInterval);
            }
            
            $(document).off('.mt-assignment');
            $(window).off('.mt-assignment');
            this.candidates = [];
            this.juryMembers = [];
            this.selectedCandidates.clear();
            this.selectedJury.clear();
            
            console.log('🧹 Assignment Interface destroyed');
        }
        
        /**
         * Additional placeholder methods for completeness
         */
        enableAutoRefresh() {
            // Auto-refresh implementation
        }
        
        initializeDragDrop() {
            // Drag and drop implementation
        }
        
        performSearch(searchTerm) {
            // Update the candidate list based on search
            this.renderCandidatesList();
        }
        
        filterByCategory(category) {
            // Update the candidate list based on category
            this.renderCandidatesList();
        }
        
        filterByAssignmentStatus(status) {
            // Update the candidate list based on assignment status
            this.renderCandidatesList();
        }
        
        changeStage(stage) {
            this.currentStage = stage;
            this.loadInitialData().then(() => {
                this.updateInterfaceState();
            });
        }
        
        renderAssignmentOverview() {
            // Render assignment overview cards
        }
        
        updateProgressIndicators() {
            // Update progress indicators
        }
        
        updateActionButtons() {
            // Update action button states
        }
        
        openAutoAssignModal() {
            // Open auto-assign modal
        }
        
        openBulkActionsModal() {
            // Open bulk actions modal
        }
        
        exportAssignments() {
            // Export assignments to CSV
        }
        
        refreshAllData() {
            this.loadInitialData().then(() => {
                this.updateInterfaceState();
                this.showSuccess('Data refreshed successfully');
            });
        }
        
        closeModal($modal) {
            // Close modal implementation
        }
        
        executeAutoAssign() {
            // Execute auto-assign implementation
        }
        
        executeBulkAction() {
            // Execute bulk action implementation
        }
        
        toggleAssignment(candidateId, juryId) {
            // Toggle assignment implementation
        }
        
        handleKeyboardShortcuts(e) {
            // Keyboard shortcuts implementation
        }
        
        handleWindowResize() {
            // Window resize handling for responsive design
        }
        
        // Add new methods for selection handling
        selectAllCandidates() {
            $('.mt-candidate-checkbox').prop('checked', true).trigger('change');
        }
        
        selectAllJury() {
            $('.mt-jury-checkbox').prop('checked', true).trigger('change');
        }
        
        clearCandidateSelection() {
            $('.mt-candidate-checkbox').prop('checked', false).trigger('change');
        }
        
        clearJurySelection() {
            $('.mt-jury-checkbox').prop('checked', false).trigger('change');
        }
        
        assignSelected() {
            const selectedCandidates = Array.from(this.selectedCandidates);
            const selectedJury = Array.from(this.selectedJury);
            
            if (selectedCandidates.length === 0 || selectedJury.length === 0) {
                this.showError('Please select both candidates and jury members to assign');
                return;
            }
            
            this.showLoading('Assigning selected candidates...');
            
            // Make API call to assign candidates
            this.apiCall('assignments/bulk', 'POST', {
                candidates: selectedCandidates,
                jury: selectedJury,
                stage: this.currentStage
            }).then(response => {
                this.hideLoading();
                if (response.success) {
                    this.showSuccess('Successfully assigned candidates');
                    this.refreshAllData();
                } else {
                    this.showError(response.data || 'Failed to assign candidates');
                }
            }).catch(error => {
                this.hideLoading();
                this.showError('Error assigning candidates: ' + error.message);
            });
        }
        
        removeSelected() {
            const selectedCandidates = Array.from(this.selectedCandidates);
            const selectedJury = Array.from(this.selectedJury);
            
            if (selectedCandidates.length === 0 || selectedJury.length === 0) {
                this.showError('Please select both candidates and jury members to remove assignments');
                return;
            }
            
            this.showLoading('Removing selected assignments...');
            
            // Make API call to remove assignments
            this.apiCall('assignments/remove', 'POST', {
                candidates: selectedCandidates,
                jury: selectedJury,
                stage: this.currentStage
            }).then(response => {
                this.hideLoading();
                if (response.success) {
                    this.showSuccess('Successfully removed assignments');
                    this.refreshAllData();
                } else {
                    this.showError(response.data || 'Failed to remove assignments');
                }
            }).catch(error => {
                this.hideLoading();
                this.showError('Error removing assignments: ' + error.message);
            });
        }
    }
    
    // Initialize when document is ready
    $(document).ready(function() {
        console.log('🎯 Mobility Trailblazers: Initializing Complete Assignment System...');
        
        // Only initialize if we're on the assignment page
        if ($('#mtAssignmentInterface').length > 0) {
            console.log('📋 Initializing Complete Assignment Interface');
            new AssignmentInterface();
        } else {
            console.log('📋 Assignment Interface not found on this page');
        }
    });

})(jQuery);