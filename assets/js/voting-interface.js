/**
 * Mobility Trailblazers - Complete Voting Interface
 * File: assets/js/voting-interface.js
 * Version: 2.0.0 - Production Ready
 */

(function($) {
    'use strict';

    // Global configuration
    const MT_CONFIG = {
        AUTO_SAVE_INTERVAL: 60000, // 60 seconds
        NOTIFICATION_TIMEOUT: 5000,
        MAX_RETRIES: 3,
        RETRY_DELAY: 1000,
        DEBOUNCE_DELAY: 300
    };

    /**
     * Main Voting Interface Class
     * Handles all jury voting functionality
     */
    class MobilityTrailblazersVoting {
        constructor(settings = {}) {
            this.settings = {
                layout: 'grid',
                show_progress: true,
                show_deadline: true,
                auto_save: true,
                ...settings
            };
            
            this.currentUser = mtVotingData?.currentUser || 0;
            this.apiUrl = mtVotingData?.restUrl || '/wp-json/mt/v1/';
            this.nonce = mtVotingData?.nonce || '';
            this.userCan = mtVotingData?.userCan || {};
            
            // State management
            this.votes = new Map();
            this.candidates = [];
            this.activePhase = null;
            this.isInitialized = false;
            this.autoSaveTimer = null;
            this.changeTracker = new Set();
            
            // API retry system
            this.retryQueue = new Map();
            
            this.init();
        }
        
        /**
         * Initialize the voting interface
         */
        init() {
            if (this.isInitialized) return;
            
            console.log('🚀 Initializing Mobility Trailblazers Voting Interface v2.0');
            
            if (!this.validateEnvironment()) {
                this.showError('Voting interface configuration error. Please refresh the page.');
                return;
            }
            
            this.setupEventHandlers();
            this.loadVotingData();
            
            if (this.settings.auto_save) {
                this.startAutoSave();
            }
            
            this.isInitialized = true;
        }
        
        /**
         * Validate that required data is available
         */
        validateEnvironment() {
            if (!window.mtVotingData) {
                console.error('❌ mtVotingData not found');
                return false;
            }
            
            if (!this.userCan.vote) {
                console.warn('⚠️ User does not have voting permissions');
                return false;
            }
            
            if (!this.nonce) {
                console.error('❌ Security nonce not found');
                return false;
            }
            
            return true;
        }
        
        /**
         * Setup all event handlers
         */
        setupEventHandlers() {
            // Score slider changes
            $(document).on('input change', '.mt-score-slider', this.debounce((e) => {
                this.handleScoreChange(e);
            }, MT_CONFIG.DEBOUNCE_DELAY));
            
            // Comments changes
            $(document).on('input', '.mt-comments-textarea', this.debounce((e) => {
                this.handleCommentsChange(e);
            }, MT_CONFIG.DEBOUNCE_DELAY));
            
            // Action button handlers
            $(document).on('click', '[data-action="save-draft"]', (e) => {
                this.saveDraft(e.target.dataset.candidateId);
            });
            
            $(document).on('click', '[data-action="save-vote"]', (e) => {
                this.saveVote(e.target.dataset.candidateId);
            });
            
            $(document).on('click', '[data-action="final-submit"]', (e) => {
                this.finalSubmit(e.target.dataset.candidateId);
            });
            
            // Candidate grid vote buttons
            $(document).on('click', '.mt-vote-btn[data-candidate-id]', (e) => {
                this.handleVoteButtonClick(e);
            });
            
            // Page visibility change (pause auto-save when tab is hidden)
            $(document).on('visibilitychange', () => {
                if (document.hidden) {
                    this.pauseAutoSave();
                } else {
                    this.resumeAutoSave();
                }
            });
            
            // Warn before leaving with unsaved changes
            $(window).on('beforeunload', (e) => {
                if (this.hasUnsavedChanges()) {
                    e.preventDefault();
                    return 'You have unsaved voting changes. Are you sure you want to leave?';
                }
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', (e) => {
                this.handleKeyboardShortcuts(e);
            });
        }
        
        /**
         * Load voting data from API
         */
        async loadVotingData() {
            this.showLoading('Loading your assigned candidates...');
            
            try {
                const response = await this.apiCall('my-candidates', 'GET');
                
                this.activePhase = response.phase;
                this.candidates = response.candidates || [];
                
                this.renderCandidates();
                this.renderProgress(response);
                this.initializeVoteStates();
                
                this.hideLoading();
                this.showSuccess(`Loaded ${this.candidates.length} candidates for evaluation`);
                
            } catch (error) {
                this.hideLoading();
                this.showError('Failed to load voting data: ' + error.message);
                console.error('❌ Load voting data error:', error);
            }
        }
        
        /**
         * Render candidates list
         */
        renderCandidates() {
            const container = $('#mtCandidatesContainer');
            
            if (this.candidates.length === 0) {
                container.html(this.renderEmptyState());
                return;
            }
            
            const candidatesHtml = this.candidates.map(candidate => {
                return this.renderCandidateCard(candidate);
            }).join('');
            
            container.html(`
                <div class="mt-candidates-wrapper">
                    ${candidatesHtml}
                </div>
            `);
            
            // Add smooth scroll behavior
            this.addSmoothScrolling();
        }
        
        /**
         * Render individual candidate card
         */
        renderCandidateCard(candidate) {
            const voteStatus = this.getVoteStatus(candidate);
            const currentVote = candidate.current_vote || {};
            const isLocked = candidate.is_final;
            
            return `
                <div class="mt-candidate-card ${candidate.is_voted ? 'voted' : ''} ${isLocked ? 'locked' : ''}" 
                     data-candidate-id="${candidate.id}">
                     
                    ${this.renderCandidateHeader(candidate, voteStatus)}
                    ${this.renderCandidateInfo(candidate)}
                    ${this.renderVotingCriteria(candidate.id, currentVote, isLocked)}
                    ${this.renderCommentsSection(candidate.id, currentVote.comments || '', isLocked)}
                    ${this.renderCandidateActions(candidate.id, isLocked)}
                </div>
            `;
        }
        
        /**
         * Render candidate header with status
         */
        renderCandidateHeader(candidate, voteStatus) {
            return `
                <div class="mt-candidate-header">
                    <div class="mt-candidate-info">
                        <h3 class="mt-candidate-name">${this.escapeHtml(candidate.title)}</h3>
                        <div class="mt-candidate-meta">
                            ${candidate.company ? `<span class="mt-company">${this.escapeHtml(candidate.company)}</span>` : ''}
                            ${candidate.position ? `<span class="mt-position">${this.escapeHtml(candidate.position)}</span>` : ''}
                        </div>
                        ${this.renderCandidateCategories(candidate.category)}
                    </div>
                    <div class="mt-vote-status ${voteStatus.class}" title="${voteStatus.tooltip}">
                        ${voteStatus.text}
                    </div>
                </div>
            `;
        }
        
        /**
         * Render candidate information section
         */
        renderCandidateInfo(candidate) {
            if (!candidate.achievements && !candidate.innovation) {
                return '';
            }
            
            return `
                <div class="mt-candidate-details">
                    ${candidate.achievements ? `
                        <div class="mt-detail-section">
                            <h4>Key Achievements</h4>
                            <p>${this.escapeHtml(candidate.achievements)}</p>
                        </div>
                    ` : ''}
                    ${candidate.innovation ? `
                        <div class="mt-detail-section">
                            <h4>Innovation Description</h4>
                            <p>${this.escapeHtml(candidate.innovation)}</p>
                        </div>
                    ` : ''}
                </div>
            `;
        }
        
        /**
         * Render voting criteria sliders
         */
        renderVotingCriteria(candidateId, currentVote, isLocked) {
            const criteria = [
                { key: 'pioneer_spirit', label: 'Pioneer Spirit & Courage', weight: 25, description: 'Willingness to take risks and challenge the status quo' },
                { key: 'innovation_degree', label: 'Degree of Innovation', weight: 30, description: 'Technological or business model breakthrough' },
                { key: 'implementation_power', label: 'Implementation Power & Effect', weight: 25, description: 'Ability to execute and scale solutions' },
                { key: 'role_model_function', label: 'Role Model Function & Visibility', weight: 20, description: 'Inspiring others and industry leadership' }
            ];
            
            const criteriaHtml = criteria.map(criterion => {
                const value = currentVote[criterion.key] || 5;
                return `
                    <div class="mt-criterion">
                        <div class="mt-criterion-header">
                            <div class="mt-criterion-info">
                                <span class="mt-criterion-label">${criterion.label}</span>
                                <span class="mt-criterion-weight">(${criterion.weight}% weight)</span>
                                <small class="mt-criterion-description">${criterion.description}</small>
                            </div>
                            <span class="mt-criterion-score" id="score-${candidateId}-${criterion.key}">${value}</span>
                        </div>
                        <div class="mt-slider-container">
                            <input type="range" class="mt-score-slider" 
                                   min="1" max="10" value="${value}" step="1"
                                   data-candidate-id="${candidateId}"
                                   data-criterion="${criterion.key}"
                                   ${isLocked ? 'disabled' : ''}
                                   aria-label="${criterion.label} score">
                            <div class="mt-score-indicators">
                                ${Array.from({length: 10}, (_, i) => `<span>${i + 1}</span>`).join('')}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            return `
                <div class="mt-voting-criteria">
                    <h4>Evaluation Criteria</h4>
                    ${criteriaHtml}
                </div>
            `;
        }
        
        /**
         * Render comments section
         */
        renderCommentsSection(candidateId, comments, isLocked) {
            return `
                <div class="mt-comments-section">
                    <label for="comments-${candidateId}">
                        Comments & Justification
                        <small>Share your reasoning for the scores (optional but recommended)</small>
                    </label>
                    <textarea class="mt-comments-textarea" 
                              id="comments-${candidateId}"
                              data-candidate-id="${candidateId}"
                              placeholder="Explain your evaluation, highlight key factors that influenced your scoring..."
                              maxlength="1000"
                              ${isLocked ? 'readonly' : ''}>${this.escapeHtml(comments)}</textarea>
                    <div class="mt-comments-counter">
                        <span id="comments-count-${candidateId}">${comments.length}</span>/1000 characters
                    </div>
                </div>
            `;
        }
        
        /**
         * Render candidate action buttons
         */
        renderCandidateActions(candidateId, isLocked) {
            const vote = this.votes.get(candidateId) || {};
            const totalScore = this.calculateTotal(vote);
            const hasChanges = this.changeTracker.has(candidateId);
            
            return `
                <div class="mt-candidate-actions">
                    <div class="mt-action-buttons">
                        ${!isLocked ? `
                            <button class="mt-btn mt-btn-secondary" 
                                    data-action="save-draft" 
                                    data-candidate-id="${candidateId}"
                                    ${hasChanges ? '' : 'disabled'}>
                                💾 Save Draft
                            </button>
                            <button class="mt-btn mt-btn-primary" 
                                    data-action="save-vote" 
                                    data-candidate-id="${candidateId}">
                                📝 Save Vote
                            </button>
                            <button class="mt-btn mt-btn-success" 
                                    data-action="final-submit" 
                                    data-candidate-id="${candidateId}">
                                ✅ Final Submit
                            </button>
                        ` : `
                            <div class="mt-locked-notice">
                                🔒 Vote has been finalized and cannot be changed
                            </div>
                        `}
                    </div>
                    <div class="mt-total-score">
                        <span class="mt-score-label">Weighted Total:</span>
                        <span class="mt-score-value" id="total-${candidateId}">${totalScore}</span>
                        <span class="mt-score-max">/10</span>
                        <div class="mt-score-breakdown">
                            <small>Calculated from weighted criteria scores</small>
                        </div>
                    </div>
                </div>
            `;
        }
        
        /**
         * Render candidate categories
         */
        renderCandidateCategories(categories) {
            if (!categories || categories.length === 0) return '';
            
            const categoryTags = categories.map(cat => 
                `<span class="mt-category-tag">${this.escapeHtml(cat.name)}</span>`
            ).join('');
            
            return `<div class="mt-candidate-categories">${categoryTags}</div>`;
        }
        
        /**
         * Render empty state when no candidates
         */
        renderEmptyState() {
            return `
                <div class="mt-no-candidates">
                    <div class="mt-empty-icon">📋</div>
                    <h4>No candidates assigned</h4>
                    <p>You don't have any candidates assigned for voting in the current phase.</p>
                    <p>Contact the administrator if you believe this is an error.</p>
                </div>
            `;
        }
        
        /**
         * Render progress information
         */
        renderProgress(data) {
            if (!this.settings.show_progress) return;
            
            const progressContainer = $('#mtVotingProgress');
            if (progressContainer.length === 0) return;
            
            const progressHtml = `
                <div class="mt-progress-header">
                    <h2>Mobility Trailblazers 2025 - Jury Evaluation</h2>
                    <p>Your expertise helps shape the future of mobility innovation</p>
                </div>
                <div class="mt-progress-stats">
                    <div class="mt-progress-stat">
                        <span class="number">${this.getPhaseDisplayName(data.phase)}</span>
                        <span class="label">Current Phase</span>
                    </div>
                    <div class="mt-progress-stat">
                        <span class="number">${data.total_assigned || 0}</span>
                        <span class="label">Assigned Candidates</span>
                    </div>
                    <div class="mt-progress-stat">
                        <span class="number">${data.total_voted || 0}</span>
                        <span class="label">Evaluations Completed</span>
                    </div>
                    <div class="mt-progress-stat">
                        <span class="number">${this.calculateCompletionRate(data)}%</span>
                        <span class="label">Progress</span>
                    </div>
                </div>
            `;
            
            progressContainer.html(progressHtml);
            
            // Show deadline warning if needed
            if (this.settings.show_deadline && data.phase) {
                this.showDeadlineWarning(data.phase);
            }
        }
        
        /**
         * Handle score slider changes
         */
        handleScoreChange(e) {
            const slider = e.target;
            const candidateId = slider.dataset.candidateId;
            const criterion = slider.dataset.criterion;
            const value = parseInt(slider.value);
            
            // Update display
            $(`#score-${candidateId}-${criterion}`).text(value);
            
            // Store in memory
            if (!this.votes.has(candidateId)) {
                this.votes.set(candidateId, {});
            }
            
            this.votes.get(candidateId)[criterion] = value;
            
            // Update total score
            this.updateTotalScore(candidateId);
            
            // Mark as changed
            this.markAsChanged(candidateId);
            
            // Provide haptic feedback on mobile
            if (navigator.vibrate) {
                navigator.vibrate(10);
            }
        }
        
        /**
         * Handle comments textarea changes
         */
        handleCommentsChange(e) {
            const candidateId = e.target.dataset.candidateId;
            const comments = e.target.value;
            
            if (!this.votes.has(candidateId)) {
                this.votes.set(candidateId, {});
            }
            
            this.votes.get(candidateId).comments = comments;
            
            // Update character counter
            $(`#comments-count-${candidateId}`).text(comments.length);
            
            this.markAsChanged(candidateId);
        }
        
        /**
         * Handle vote button click from candidate grid
         */
        handleVoteButtonClick(e) {
            e.preventDefault();
            const candidateId = e.target.dataset.candidateId;
            
            if (candidateId) {
                this.scrollToCandidate(candidateId);
            }
        }
        
        /**
         * Handle keyboard shortcuts
         */
        handleKeyboardShortcuts(e) {
            // Ctrl/Cmd + S: Save all changed votes as drafts
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                this.saveAllChanges();
            }
            
            // Escape: Clear selection
            if (e.key === 'Escape') {
                $('.mt-candidate-card.selected').removeClass('selected');
            }
        }
        
        /**
         * Update total score display
         */
        updateTotalScore(candidateId) {
            const vote = this.votes.get(candidateId) || {};
            const total = this.calculateTotal(vote);
            
            const totalElement = $(`#total-${candidateId}`);
            totalElement.text(total);
            
            // Add visual feedback for score changes
            totalElement.addClass('updated');
            setTimeout(() => totalElement.removeClass('updated'), 500);
        }
        
        /**
         * Calculate weighted total score
         */
        calculateTotal(vote) {
            const weights = {
                pioneer_spirit: 0.25,
                innovation_degree: 0.30,
                implementation_power: 0.25,
                role_model_function: 0.20
            };
            
            let total = 0;
            Object.keys(weights).forEach(key => {
                total += (vote[key] || 5) * weights[key];
            });
            
            return total.toFixed(2);
        }
        
        /**
         * Save vote as draft
         */
        async saveDraft(candidateId) {
            return this.submitVote(candidateId, false, false);
        }
        
        /**
         * Save vote (not final)
         */
        async saveVote(candidateId) {
            return this.submitVote(candidateId, false, true);
        }
        
        /**
         * Submit final vote
         */
        async finalSubmit(candidateId) {
            const confirmed = await this.showConfirmation(
                'Final Submission', 
                'Are you sure you want to make this a final submission? You won\'t be able to change it afterwards.',
                'Submit Final Vote',
                'Cancel'
            );
            
            if (confirmed) {
                return this.submitVote(candidateId, true, true);
            }
        }
        
        /**
         * Submit vote to API
         */
        async submitVote(candidateId, isFinal = false, showSuccess = false) {
            const vote = this.votes.get(candidateId) || {};
            
            // Validate required fields
            const requiredCriteria = ['pioneer_spirit', 'innovation_degree', 'implementation_power', 'role_model_function'];
            const missingCriteria = requiredCriteria.filter(key => !vote[key]);
            
            if (missingCriteria.length > 0) {
                this.showError('Please provide scores for all criteria before saving.');
                return false;
            }
            
            const data = {
                candidate_id: parseInt(candidateId),
                pioneer_spirit: vote.pioneer_spirit,
                innovation_degree: vote.innovation_degree,
                implementation_power: vote.implementation_power,
                role_model_function: vote.role_model_function,
                comments: vote.comments || '',
                is_final: isFinal
            };
            
            try {
                this.showLoading(`${isFinal ? 'Submitting final vote' : 'Saving vote'}...`);
                
                const response = await this.apiCall('vote', 'POST', data);
                
                this.hideLoading();
                
                if (showSuccess) {
                    this.showSuccess(isFinal ? 'Final vote submitted successfully!' : 'Vote saved successfully!');
                }
                
                // Update UI
                this.updateVoteStatus(candidateId, isFinal);
                this.clearChanged(candidateId);
                
                if (isFinal) {
                    this.lockCandidate(candidateId);
                }
                
                return true;
                
            } catch (error) {
                this.hideLoading();
                this.showError(`Failed to save vote: ${error.message}`);
                console.error('❌ Submit vote error:', error);
                return false;
            }
        }
        
        /**
         * Save all changed votes as drafts
         */
        async saveAllChanges() {
            const changedCandidates = Array.from(this.changeTracker);
            
            if (changedCandidates.length === 0) {
                this.showInfo('No changes to save.');
                return;
            }
            
            this.showLoading(`Saving ${changedCandidates.length} vote(s)...`);
            
            let successCount = 0;
            let errorCount = 0;
            
            for (const candidateId of changedCandidates) {
                try {
                    const success = await this.submitVote(candidateId, false, false);
                    if (success) {
                        successCount++;
                    } else {
                        errorCount++;
                    }
                } catch (error) {
                    errorCount++;
                    console.error(`❌ Failed to save vote for candidate ${candidateId}:`, error);
                }
            }
            
            this.hideLoading();
            
            if (successCount > 0) {
                this.showSuccess(`Saved ${successCount} vote(s) successfully!`);
            }
            
            if (errorCount > 0) {
                this.showError(`Failed to save ${errorCount} vote(s). Please try again.`);
            }
        }
        
        /**
         * Update vote status in UI
         */
        updateVoteStatus(candidateId, isFinal) {
            const card = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
            const statusElement = card.find('.mt-vote-status');
            
            if (isFinal) {
                statusElement.removeClass('draft saved').addClass('final').text('✅ Final');
                card.addClass('voted');
            } else {
                statusElement.removeClass('draft final').addClass('saved').text('💾 Saved');
            }
        }
        
        /**
         * Lock candidate after final submission
         */
        lockCandidate(candidateId) {
            const card = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
            
            // Disable all inputs
            card.find('.mt-score-slider, .mt-comments-textarea').prop('disabled', true);
            
            // Hide action buttons and show locked notice
            card.find('.mt-action-buttons').html(`
                <div class="mt-locked-notice">
                    🔒 Vote has been finalized and cannot be changed
                </div>
            `);
            
            card.addClass('locked');
        }
        
        /**
         * Mark candidate as changed
         */
        markAsChanged(candidateId) {
            this.changeTracker.add(candidateId);
            
            const card = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
            card.addClass('mt-changed');
            
            // Enable save draft button
            card.find('[data-action="save-draft"]').prop('disabled', false);
        }
        
        /**
         * Clear changed status
         */
        clearChanged(candidateId) {
            this.changeTracker.delete(candidateId);
            
            const card = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
            card.removeClass('mt-changed');
            
            // Disable save draft button
            card.find('[data-action="save-draft"]').prop('disabled', true);
        }
        
        /**
         * Check if there are unsaved changes
         */
        hasUnsavedChanges() {
            return this.changeTracker.size > 0;
        }
        
        /**
         * Initialize vote states from loaded data
         */
        initializeVoteStates() {
            this.candidates.forEach(candidate => {
                if (candidate.current_vote) {
                    this.votes.set(candidate.id, { ...candidate.current_vote });
                }
            });
        }
        
        /**
         * Get vote status display info
         */
        getVoteStatus(candidate) {
            if (candidate.is_final) {
                return { 
                    class: 'final', 
                    text: '✅ Final', 
                    tooltip: 'Vote has been finalized' 
                };
            } else if (candidate.is_voted) {
                return { 
                    class: 'saved', 
                    text: '💾 Saved', 
                    tooltip: 'Vote saved as draft' 
                };
            } else {
                return { 
                    class: 'draft', 
                    text: '⏳ Not Voted', 
                    tooltip: 'No vote submitted yet' 
                };
            }
        }
        
        /**
         * Auto-save functionality
         */
        startAutoSave() {
            if (this.autoSaveTimer) {
                clearInterval(this.autoSaveTimer);
            }
            
            this.autoSaveTimer = setInterval(() => {
                if (this.hasUnsavedChanges() && !document.hidden) {
                    console.log('🔄 Auto-saving changes...');
                    this.saveAllChanges();
                }
            }, MT_CONFIG.AUTO_SAVE_INTERVAL);
        }
        
        pauseAutoSave() {
            if (this.autoSaveTimer) {
                clearInterval(this.autoSaveTimer);
                this.autoSaveTimer = null;
            }
        }
        
        resumeAutoSave() {
            if (this.settings.auto_save && !this.autoSaveTimer) {
                this.startAutoSave();
            }
        }
        
        /**
         * Scroll to specific candidate
         */
        scrollToCandidate(candidateId) {
            const candidateCard = $(`.mt-candidate-card[data-candidate-id="${candidateId}"]`);
            
            if (candidateCard.length) {
                // Remove previous selection
                $('.mt-candidate-card.selected').removeClass('selected');
                
                // Highlight target
                candidateCard.addClass('selected');
                
                // Smooth scroll
                candidateCard[0].scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
                
                // Remove highlight after animation
                setTimeout(() => {
                    candidateCard.removeClass('selected');
                }, 3000);
            }
        }
        
        /**
         * Add smooth scrolling to all candidates
         */
        addSmoothScrolling() {
            $('.mt-candidate-card').each(function() {
                $(this).on('focusin', function() {
                    $(this).addClass('focused');
                }).on('focusout', function() {
                    $(this).removeClass('focused');
                });
            });
        }
        
        /**
         * Calculate completion rate
         */
        calculateCompletionRate(data) {
            if (!data.total_assigned || data.total_assigned === 0) return 0;
            return Math.round((data.total_voted / data.total_assigned) * 100);
        }
        
        /**
         * Get display name for phase
         */
        getPhaseDisplayName(phase) {
            if (!phase) return 'N/A';
            
            const phaseNames = {
                'shortlist': 'Shortlist',
                'semifinal': 'Semi-Final',
                'final': 'Final'
            };
            
            return phaseNames[phase.stage] || phase.stage.charAt(0).toUpperCase() + phase.stage.slice(1);
        }
        
        /**
         * Show deadline warning
         */
        showDeadlineWarning(phase) {
            const warningContainer = $('#mtDeadlineWarning');
            if (warningContainer.length === 0) return;
            
            const endDate = new Date(phase.end_date);
            const now = new Date();
            const daysLeft = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));
            
            if (daysLeft <= 7 && daysLeft > 0) {
                warningContainer.html(`
                    <div class="mt-deadline-content">
                        <strong>⚠️ Voting deadline approaching!</strong>
                        <p>Only ${daysLeft} day${daysLeft !== 1 ? 's' : ''} remaining until ${endDate.toLocaleDateString()}.</p>
                        <p>Please complete all your evaluations.</p>
                    </div>
                `).addClass('warning').show();
            } else if (daysLeft <= 0) {
                warningContainer.html(`
                    <div class="mt-deadline-content">
                        <strong>🔴 Voting period has ended!</strong>
                        <p>The deadline was ${endDate.toLocaleDateString()}.</p>
                    </div>
                `).addClass('expired').show();
            }
        }
        
        /**
         * API call wrapper with retry logic
         */
        async apiCall(endpoint, method = 'GET', data = null, retryCount = 0) {
            const url = this.apiUrl + endpoint;
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
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                const result = await response.json();
                
                // Clear any retry queue for this endpoint
                this.retryQueue.delete(endpoint);
                
                return result;
                
            } catch (error) {
                console.error(`❌ API call failed: ${method} ${endpoint}`, error);
                
                // Retry logic for network errors
                if (retryCount < MT_CONFIG.MAX_RETRIES && this.isRetryableError(error)) {
                    console.log(`🔄 Retrying API call (${retryCount + 1}/${MT_CONFIG.MAX_RETRIES})`);
                    
                    await this.sleep(MT_CONFIG.RETRY_DELAY * (retryCount + 1));
                    
                    return this.apiCall(endpoint, method, data, retryCount + 1);
                }
                
                throw error;
            }
        }
        
        /**
         * Check if error is retryable
         */
        isRetryableError(error) {
            // Retry on network errors, timeouts, and 5xx server errors
            return error.name === 'TypeError' || 
                   error.message.includes('NetworkError') ||
                   error.message.includes('5');
        }
        
        /**
         * Sleep utility for retries
         */
        sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
        
        /**
         * Debounce utility
         */
        debounce(func, wait) {
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
         * HTML escape utility
         */
        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        /**
         * UI Feedback Methods
         */
        showLoading(message = 'Loading...') {
            this.hideAllNotifications();
            
            const loadingHtml = `
                <div class="mt-loading-overlay" id="mtLoadingOverlay">
                    <div class="mt-loading">
                        <div class="mt-spinner"></div>
                        <p>${message}</p>
                    </div>
                </div>
            `;
            
            $('body').append(loadingHtml);
        }
        
        hideLoading() {
            $('#mtLoadingOverlay').remove();
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
            const $notification = $(`
                <div id="${notificationId}" class="mt-notification mt-notification-${type}">
                    <div class="mt-notification-content">
                        <span class="mt-notification-icon">${this.getNotificationIcon(type)}</span>
                        <span class="mt-notification-message">${message}</span>
                    </div>
                    <button class="mt-notification-close" aria-label="Close notification">&times;</button>
                </div>
            `);
            
            $('body').append($notification);
            
            // Animate in
            setTimeout(() => $notification.addClass('show'), 10);
            
            // Auto-remove
            setTimeout(() => {
                this.removeNotification(notificationId);
            }, MT_CONFIG.NOTIFICATION_TIMEOUT);
            
            // Close button
            $notification.find('.mt-notification-close').on('click', () => {
                this.removeNotification(notificationId);
            });
        }
        
        removeNotification(notificationId) {
            const $notification = $(`#${notificationId}`);
            $notification.removeClass('show');
            setTimeout(() => $notification.remove(), 300);
        }
        
        hideAllNotifications() {
            $('.mt-notification').removeClass('show');
            setTimeout(() => $('.mt-notification').remove(), 300);
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
         * Show confirmation dialog
         */
        showConfirmation(title, message, confirmText = 'Confirm', cancelText = 'Cancel') {
            return new Promise((resolve) => {
                const modalId = 'mt-confirm-' + Date.now();
                const modalHtml = `
                    <div id="${modalId}" class="mt-modal mt-confirm-modal">
                        <div class="mt-modal-content">
                            <div class="mt-modal-header">
                                <h3>${title}</h3>
                            </div>
                            <div class="mt-modal-body">
                                <p>${message}</p>
                            </div>
                            <div class="mt-modal-footer">
                                <button class="mt-btn mt-btn-secondary" data-action="cancel">${cancelText}</button>
                                <button class="mt-btn mt-btn-primary" data-action="confirm">${confirmText}</button>
                            </div>
                        </div>
                    </div>
                `;
                
                $('body').append(modalHtml);
                
                const $modal = $(`#${modalId}`);
                
                // Show modal
                setTimeout(() => $modal.addClass('show'), 10);
                
                // Handle actions
                $modal.on('click', '[data-action="confirm"]', () => {
                    $modal.remove();
                    resolve(true);
                });
                
                $modal.on('click', '[data-action="cancel"]', () => {
                    $modal.remove();
                    resolve(false);
                });
                
                // Close on backdrop click
                $modal.on('click', (e) => {
                    if (e.target === $modal[0]) {
                        $modal.remove();
                        resolve(false);
                    }
                });
            });
        }
        
        /**
         * Cleanup when component is destroyed
         */
        destroy() {
            this.pauseAutoSave();
            this.hideAllNotifications();
            $(document).off('.mt-voting');
            $(window).off('.mt-voting');
        }
    }
    
    /**
     * Progress Widget Class
     * Displays voting progress for admins
     */
    class ProgressWidget {
        constructor(element, settings = {}) {
            this.element = $(element);
            this.settings = {
                auto_refresh: 30, // seconds
                show_phase_info: true,
                show_deadline: true,
                widget_type: 'dashboard',
                admin_only: false,
                ...settings
            };
            
            this.refreshTimer = null;
            this.init();
        }
        
        init() {
            if (this.settings.admin_only && !mtVotingData?.userCan?.manageVoting) {
                this.element.html('<div class="mt-access-denied">This widget is restricted to administrators only.</div>');
                return;
            }
            
            this.loadProgressData();
            
            if (this.settings.auto_refresh > 0) {
                this.startAutoRefresh();
            }
        }
        
        async loadProgressData() {
            try {
                const response = await fetch(mtVotingData.restUrl + 'admin/voting-progress', {
                    headers: { 'X-WP-Nonce': mtVotingData.nonce }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    this.renderProgress(data);
                } else {
                    throw new Error('Failed to load progress data');
                }
                
            } catch (error) {
                console.error('❌ Progress widget error:', error);
                this.renderError();
            }
        }
        
        renderProgress(data) {
            const stats = data.overall_stats || {};
            
            if (this.settings.widget_type === 'mini_widget') {
                this.renderMiniWidget(stats);
            } else {
                this.renderDashboardWidget(data);
            }
        }
        
        renderMiniWidget(stats) {
            const html = `
                <div class="mt-mini-progress-widget">
                    <div class="mt-mini-content">
                        <div class="mt-mini-icon">📊</div>
                        <div class="mt-mini-stats">
                            <div class="mt-mini-number">${stats.total_votes || 0}</div>
                            <div class="mt-mini-label">Votes Completed</div>
                        </div>
                        <div class="mt-mini-percentage">${stats.completion_rate || 0}%</div>
                    </div>
                </div>
            `;
            this.element.html(html);
        }
        
        renderDashboardWidget(data) {
            const stats = data.overall_stats || {};
            const phase = data.phase || {};
            
            const html = `
                <div class="mt-progress-dashboard">
                    ${this.settings.show_phase_info ? `
                        <div class="mt-phase-info">
                            <h3>Current Phase: ${phase.phase_name || 'Not Set'}</h3>
                            <p>Stage: ${phase.stage || 'Unknown'}</p>
                        </div>
                    ` : ''}
                    
                    <div class="mt-progress-grid">
                        <div class="mt-progress-item">
                            <div class="mt-progress-number">${stats.total_assignments || 0}</div>
                            <div class="mt-progress-label">Total Assignments</div>
                        </div>
                        <div class="mt-progress-item">
                            <div class="mt-progress-number">${stats.total_votes || 0}</div>
                            <div class="mt-progress-label">Votes Cast</div>
                        </div>
                        <div class="mt-progress-item">
                            <div class="mt-progress-number">${stats.final_votes || 0}</div>
                            <div class="mt-progress-label">Final Votes</div>
                        </div>
                        <div class="mt-progress-item">
                            <div class="mt-progress-number">${stats.completion_rate || 0}%</div>
                            <div class="mt-progress-label">Completion Rate</div>
                            <div class="mt-progress-bar">
                                <div class="mt-progress-fill" style="width: ${stats.completion_rate || 0}%"></div>
                            </div>
                        </div>
                    </div>
                    
                    ${this.settings.show_deadline ? this.renderDeadlineInfo(phase) : ''}
                </div>
            `;
            
            this.element.html(html);
        }
        
        renderDeadlineInfo(phase) {
            if (!phase.end_date) return '';
            
            const endDate = new Date(phase.end_date);
            const now = new Date();
            const daysLeft = Math.ceil((endDate - now) / (1000 * 60 * 60 * 24));
            
            let deadlineClass = 'info';
            let deadlineText = `${daysLeft} days remaining`;
            
            if (daysLeft <= 0) {
                deadlineClass = 'expired';
                deadlineText = 'Voting period ended';
            } else if (daysLeft <= 3) {
                deadlineClass = 'urgent';
                deadlineText = `${daysLeft} days left - Urgent!`;
            } else if (daysLeft <= 7) {
                deadlineClass = 'warning';
                deadlineText = `${daysLeft} days remaining`;
            }
            
            return `
                <div class="mt-deadline-info ${deadlineClass}">
                    <strong>Deadline: ${endDate.toLocaleDateString()}</strong>
                    <p>${deadlineText}</p>
                </div>
            `;
        }
        
        renderError() {
            this.element.html(`
                <div class="mt-progress-error">
                    <p>❌ Failed to load voting progress</p>
                    <button class="mt-btn mt-btn-small" onclick="location.reload()">Retry</button>
                </div>
            `);
        }
        
        startAutoRefresh() {
            this.refreshTimer = setInterval(() => {
                this.loadProgressData();
            }, this.settings.auto_refresh * 1000);
        }
        
        destroy() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
            }
        }
    }
    
    /**
     * Global namespace and initialization
     */
    window.MobilityTrailblazers = {
        VotingInterface: MobilityTrailblazersVoting,
        ProgressWidget: ProgressWidget,
        instances: new Map()
    };
    
    /**
     * Auto-initialize on document ready
     */
    $(document).ready(function() {
        console.log('🎯 Mobility Trailblazers: Document ready, initializing components...');
        
        // Initialize voting interface
        if ($('.mt-voting-interface').length && mtVotingData?.userCan?.vote) {
            console.log('📝 Initializing Voting Interface');
            const votingInterface = new MobilityTrailblazersVoting();
            window.MobilityTrailblazers.instances.set('voting', votingInterface);
        }
        
        // Initialize progress widgets
        $('.mt-voting-progress-widget').each(function(index) {
            const settings = $(this).data('settings') || {};
            const widget = new ProgressWidget(this, settings);
            window.MobilityTrailblazers.instances.set(`progress-${index}`, widget);
        });
        
        // Initialize candidate grid interactions
        $('.mt-candidate-grid .mt-vote-btn').on('click', function(e) {
            e.preventDefault();
            const candidateId = $(this).data('candidate-id');
            
            if (candidateId && window.MobilityTrailblazers.instances.has('voting')) {
                const votingInterface = window.MobilityTrailblazers.instances.get('voting');
                votingInterface.scrollToCandidate(candidateId);
            } else {
                alert('Voting interface not available. Please refresh the page.');
            }
        });
        
        console.log('✅ Mobility Trailblazers: All components initialized successfully');
    });
    
    /**
     * Cleanup on page unload
     */
    $(window).on('beforeunload', function() {
        window.MobilityTrailblazers.instances.forEach(instance => {
            if (instance.destroy) {
                instance.destroy();
            }
        });
    });
    
})(jQuery);