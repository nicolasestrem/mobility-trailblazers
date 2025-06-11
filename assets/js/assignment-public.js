/**
 * Mobility Trailblazers - Public Assignment Interface
 * File: assets/js/assignment-public.js
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    class JuryDashboard {
        constructor() {
            this.apiUrl = window.mtAssignmentPublic?.apiUrl || '/wp-json/mt/v1';
            this.nonce = window.mtAssignmentPublic?.nonce || '';
            this.currentUser = (window.mtAssignmentPublic && window.mtAssignmentPublic.currentUser) || null;
            
            this.init();
        }

        async init() {
            try {
                if (!this.validateEnvironment()) {
                    return;
                }
                await this.loadDashboardData();
                this.setupEventHandlers();
            } catch (error) {
                console.error('Failed to initialize dashboard:', error);
                this.showError('Failed to initialize dashboard. Please try refreshing the page.');
            }
        }

        validateEnvironment() {
            if (!this.apiUrl || !this.nonce) {
                console.error('Missing required configuration:', {
                    apiUrl: this.apiUrl,
                    nonce: this.nonce
                });
                this.showError('Dashboard configuration is missing. Please contact support.');
                return false;
            }
            return true;
        }

        async loadDashboardData() {
            try {
                const [stats, activity] = await Promise.all([
                    this.apiCall('jury-stats'),
                    this.apiCall('recent-activity')
                ]);

                this.renderStats(stats);
                this.renderActivity(activity);
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
                this.showError('Failed to load dashboard data. Please try refreshing the page.');
            }
        }

        async apiCall(endpoint, method = 'GET', data = null) {
            try {
                const url = `${this.apiUrl}/${endpoint}`;
                console.log(`Making API call to: ${url}`);

                const options = {
                    method,
                    headers: {
                        'X-WP-Nonce': this.nonce,
                        'Content-Type': 'application/json'
                    },
                    credentials: 'same-origin'
                };

                if (data && (method === 'POST' || method === 'PUT')) {
                    options.body = JSON.stringify(data);
                }

                const response = await fetch(url, options);
                console.log(`API Response status: ${response.status}`);

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    console.error('API Error:', {
                        status: response.status,
                        statusText: response.statusText,
                        errorData
                    });
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await response.json();
                console.log(`API Response data:`, result);
                return result;
            } catch (error) {
                console.error(`API call failed for ${endpoint}:`, error);
                throw error;
            }
        }

        renderStats(stats) {
            const container = document.getElementById('jury-stats');
            if (!container) {
                console.error('Stats container not found');
                return;
            }

            container.innerHTML = `
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Candidates</h3>
                        <p>${stats.total_candidates}</p>
                    </div>
                    <div class="stat-card">
                        <h3>Assigned to You</h3>
                        <p>${stats.assigned_candidates}</p>
                    </div>
                    <div class="stat-card">
                        <h3>Voted</h3>
                        <p>${stats.voted_candidates}</p>
                    </div>
                    <div class="stat-card">
                        <h3>Remaining</h3>
                        <p>${stats.remaining_candidates}</p>
                    </div>
                </div>
            `;
        }

        renderActivity(activities) {
            const container = document.getElementById('recent-activity');
            if (!container) {
                console.error('Activity container not found');
                return;
            }

            if (!activities || activities.length === 0) {
                container.innerHTML = '<p>No recent activity</p>';
                return;
            }

            const activityList = activities.map(activity => `
                <div class="activity-item">
                    <span class="activity-type ${activity.type}">${activity.type}</span>
                    <span class="activity-title">${activity.candidate_title}</span>
                    <span class="activity-time">${this.formatDate(activity.timestamp)}</span>
                    ${activity.type === 'vote' ? `<span class="activity-score">Score: ${activity.score}</span>` : ''}
                </div>
            `).join('');

            container.innerHTML = `
                <h3>Recent Activity</h3>
                <div class="activity-list">
                    ${activityList}
                </div>
            `;
        }

        formatDate(timestamp) {
            return new Date(timestamp).toLocaleString();
        }

        showError(message) {
            const container = document.getElementById('jury-dashboard');
            if (container) {
                container.innerHTML = `
                    <div class="error-message">
                        <p>${message}</p>
                        <button onclick="window.location.reload()">Refresh Page</button>
                    </div>
                `;
            }
        }

        setupEventHandlers() {
            // Refresh button
            $(document).on('click', '#mtRefreshDashboard', () => {
                this.loadDashboardData();
            });

            // Continue voting button
            $(document).on('click', '#mtContinueVoting', () => {
                window.location.href = '#voting-interface';
            });
        }
    }

    // Initialize the dashboard when the document is ready
    $(document).ready(() => {
        new JuryDashboard();
    });

})(jQuery); 