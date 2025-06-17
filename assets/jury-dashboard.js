/**
 * Mobility Trailblazers - Jury Dashboard JavaScript
 * Enhanced version with full functionality
 */

(function($) {
    'use strict';

    window.MTJuryDashboard = {
        evaluationData: {},
        currentCandidate: null,
        
        init: function() {
            this.bindEvents();
            this.initializeSliders();
            this.loadDashboardData();
            this.initializeAnimations();
        },
        
        bindEvents: function() {
            const self = this;
            
            // Search functionality
            $('#mt-candidate-search').on('keyup', function() {
                self.filterCandidates($(this).val());
            });
            
            // Filter buttons
            $('.filter-btn').on('click', function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                self.filterByStatus($(this).data('filter'));
            });
            
            // Candidate selection
            $(document).on('click', '.candidate-card', function() {
                $('.candidate-card').removeClass('active');
                $(this).addClass('active');
                self.loadCandidateEvaluation($(this).data('candidate-id'));
            });
            
            // Score sliders
            $('.score-slider').on('input', function() {
                self.updateScore($(this));
            });
            
            // Save draft
            $('#save-draft').on('click', function() {
                self.saveEvaluation('draft');
            });
            
            // Submit evaluation
            $('#submit-evaluation').on('click', function() {
                self.submitEvaluation();
            });
            
            // Modal close
            $('.mt-modal-close, .mt-modal-overlay').on('click', function() {
                self.closeModal();
            });
            
            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeModal();
                }
                if (e.ctrlKey && e.key === 's') {
                    e.preventDefault();
                    self.saveEvaluation('draft');
                }
            });
        },
        
        initializeSliders: function() {
            const self = this;
            
            $('.score-slider').each(function() {
                const $slider = $(this);
                const $display = $slider.siblings('label').find('.score-display');
                
                // Set initial display
                $display.text($slider.val());
                
                // Add tick marks
                const $ticksContainer = $('<div class="slider-ticks"></div>');
                for (let i = 0; i <= 10; i++) {
                    $ticksContainer.append(`<span class="tick" style="left: ${i * 10}%">${i}</span>`);
                }
                $slider.after($ticksContainer);
                
                // Color gradient based on score
                $slider.on('input', function() {
                    const value = parseInt($(this).val());
                    const percentage = (value - 1) * 11.11;
                    const hue = percentage * 1.2; // 0 to 120 (red to green)
                    $(this).css('background', `linear-gradient(to right, #ddd 0%, #ddd ${percentage}%, #f0f0f1 ${percentage}%, #f0f0f1 100%)`);
                });
            });
        },
        
        loadDashboardData: function() {
            const self = this;
            
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_jury_dashboard_data',
                    nonce: mt_jury_dashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateDashboard(response.data);
                    }
                }
            });
        },
        
        updateDashboard: function(data) {
            // Update statistics
            $('#assigned-count').text(data.assigned_count);
            $('#evaluated-count').text(data.evaluated_count);
            $('#completion-rate').text(data.completion_rate + '%');
            
            // Update progress bar
            $('.mt-progress-fill').css('width', data.completion_rate + '%');
            $('#progress-text').text(data.evaluated_count + ' / ' + data.assigned_count);
            
            // Update candidate cards
            this.renderCandidateCards(data.candidates);
            
            // Animate counters
            this.animateCounters();
        },
        
        renderCandidateCards: function(candidates) {
            const $container = $('.mt-candidates-grid');
            $container.empty();
            
            candidates.forEach(candidate => {
                const statusClass = candidate.evaluation_status;
                const statusText = this.getStatusText(candidate.evaluation_status);
                const scoreDisplay = candidate.total_score ? `${candidate.total_score}/50` : '';
                
                const card = `
                    <div class="candidate-card ${candidate.evaluation_status ? 'has-evaluation' : ''}" 
                         data-candidate-id="${candidate.id}"
                         data-status="${statusClass}">
                        <div class="candidate-header">
                            <div>
                                <h3 class="candidate-name">${candidate.name}</h3>
                                <p class="candidate-position">${candidate.position || ''}</p>
                                <p class="candidate-company">${candidate.company || ''}</p>
                            </div>
                            ${statusText ? `<span class="evaluation-status ${statusClass}">${statusText}</span>` : ''}
                        </div>
                        <div class="candidate-categories">
                            ${candidate.categories.map(cat => `<span class="category-tag">${cat}</span>`).join('')}
                        </div>
                        ${scoreDisplay ? `<div class="evaluation-score">${scoreDisplay}</div>` : ''}
                        <div class="card-hover-effect"></div>
                    </div>
                `;
                
                $container.append(card);
            });
            
            // Add entrance animation
            $('.candidate-card').each(function(index) {
                $(this).css('animation-delay', `${index * 0.1}s`);
            });
        },
        
        getStatusText: function(status) {
            const statusMap = {
                'completed': mt_jury_dashboard.i18n.completed,
                'draft': mt_jury_dashboard.i18n.draft,
                'pending': ''
            };
            return statusMap[status] || '';
        },
        
        filterCandidates: function(searchTerm) {
            const term = searchTerm.toLowerCase();
            
            $('.candidate-card').each(function() {
                const $card = $(this);
                const name = $card.find('.candidate-name').text().toLowerCase();
                const company = $card.find('.candidate-company').text().toLowerCase();
                const position = $card.find('.candidate-position').text().toLowerCase();
                
                if (name.includes(term) || company.includes(term) || position.includes(term)) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
            
            this.updateNoResultsMessage();
        },
        
        filterByStatus: function(status) {
            if (status === 'all') {
                $('.candidate-card').show();
            } else {
                $('.candidate-card').each(function() {
                    const $card = $(this);
                    if ($card.data('status') === status || (status === 'pending' && !$card.data('status'))) {
                        $card.show();
                    } else {
                        $card.hide();
                    }
                });
            }
            
            this.updateNoResultsMessage();
        },
        
        updateNoResultsMessage: function() {
            const visibleCards = $('.candidate-card:visible').length;
            
            if (visibleCards === 0) {
                if (!$('.no-results-message').length) {
                    $('.mt-candidates-grid').append(`
                        <div class="no-results-message">
                            <p>${mt_jury_dashboard.i18n.no_candidates_found}</p>
                        </div>
                    `);
                }
            } else {
                $('.no-results-message').remove();
            }
        },
        
        loadCandidateEvaluation: function(candidateId) {
            const self = this;
            this.currentCandidate = candidateId;
            
            // Show loading state
            this.showEvaluationModal();
            
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_candidate_evaluation',
                    candidate_id: candidateId,
                    nonce: mt_jury_dashboard.nonce
                },
                beforeSend: function() {
                    $('#evaluation-form').addClass('loading');
                },
                success: function(response) {
                    if (response.success) {
                        self.populateEvaluationForm(response.data);
                    } else {
                        self.showNotification('error', response.data.message);
                    }
                },
                complete: function() {
                    $('#evaluation-form').removeClass('loading');
                }
            });
        },
        
        populateEvaluationForm: function(data) {
            // Update candidate info
            $('#eval-candidate-name').text(data.candidate.name);
            $('#eval-candidate-position').text(data.candidate.position);
            $('#eval-candidate-company').text(data.candidate.company);
            
            // Set evaluation scores
            if (data.evaluation) {
                $('#courage-score').val(data.evaluation.courage || 5);
                $('#innovation-score').val(data.evaluation.innovation || 5);
                $('#implementation-score').val(data.evaluation.implementation || 5);
                $('#relevance-score').val(data.evaluation.relevance || 5);
                $('#visibility-score').val(data.evaluation.visibility || 5);
                $('#evaluation-comments').val(data.evaluation.comments || '');
            } else {
                // Reset to defaults
                $('.score-slider').val(5);
                $('#evaluation-comments').val('');
            }
            
            // Update all displays
            $('.score-slider').trigger('input');
            this.updateTotalScore();
        },
        
        updateScore: function($slider) {
            const value = $slider.val();
            const $display = $slider.siblings('label').find('.score-display');
            
            $display.text(value);
            
            // Animate the change
            $display.addClass('score-updated');
            setTimeout(() => $display.removeClass('score-updated'), 300);
            
            this.updateTotalScore();
        },
        
        updateTotalScore: function() {
            let total = 0;
            
            $('.score-slider').each(function() {
                total += parseInt($(this).val());
            });
            
            $('#total-score').text(total);
            
            // Update score indicator
            const $indicator = $('#score-indicator');
            $indicator.removeClass('low medium high excellent');
            
            if (total <= 15) {
                $indicator.addClass('low');
            } else if (total <= 25) {
                $indicator.addClass('medium');
            } else if (total <= 35) {
                $indicator.addClass('high');
            } else {
                $indicator.addClass('excellent');
            }
            
            // Animate score change
            $('#total-score').addClass('score-updated');
            setTimeout(() => $('#total-score').removeClass('score-updated'), 300);
        },
        
        saveEvaluation: function(status) {
            const self = this;
            
            const evaluationData = {
                candidate_id: this.currentCandidate,
                courage: $('#courage-score').val(),
                innovation: $('#innovation-score').val(),
                implementation: $('#implementation-score').val(),
                relevance: $('#relevance-score').val(),
                visibility: $('#visibility-score').val(),
                comments: $('#evaluation-comments').val(),
                status: status
            };
            
            $.ajax({
                url: mt_jury_dashboard.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_save_evaluation',
                    evaluation: evaluationData,
                    nonce: mt_jury_dashboard.nonce
                },
                beforeSend: function() {
                    $('#save-draft').prop('disabled', true).text(mt_jury_dashboard.i18n.saving);
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotification('success', response.data.message);
                        
                        // Update candidate card status
                        const $card = $(`.candidate-card[data-candidate-id="${self.currentCandidate}"]`);
                        $card.attr('data-status', status);
                        
                        if (status === 'draft') {
                            $card.find('.evaluation-status').remove();
                            $card.find('.candidate-header > div').after(`<span class="evaluation-status draft">${mt_jury_dashboard.i18n.draft}</span>`);
                        }
                        
                        // Update progress if needed
                        if (status === 'completed') {
                            self.loadDashboardData();
                        }
                    } else {
                        self.showNotification('error', response.data.message);
                    }
                },
                complete: function() {
                    $('#save-draft').prop('disabled', false).text(mt_jury_dashboard.i18n.save_draft);
                }
            });
        },
        
        submitEvaluation: function() {
            const self = this;
            
            // Validate all scores
            let valid = true;
            $('.score-slider').each(function() {
                if (!$(this).val() || parseInt($(this).val()) < 1 || parseInt($(this).val()) > 10) {
                    valid = false;
                    $(this).addClass('error');
                }
            });
            
            if (!valid) {
                this.showNotification('error', mt_jury_dashboard.i18n.please_complete_scores);
                return;
            }
            
            // Confirm submission
            if (!confirm(mt_jury_dashboard.i18n.confirm_submit)) {
                return;
            }
            
            this.saveEvaluation('completed');
            
            // Close modal after submission
            setTimeout(() => {
                this.closeModal();
            }, 1500);
        },
        
        showEvaluationModal: function() {
            $('#evaluation-modal').addClass('active');
            $('body').addClass('modal-open');
        },
        
        closeModal: function() {
            $('.mt-modal').removeClass('active');
            $('body').removeClass('modal-open');
        },
        
        showNotification: function(type, message) {
            const $notification = $(`
                <div class="mt-notification mt-notification-${type}">
                    <span class="notification-icon"></span>
                    <span class="notification-message">${message}</span>
                    <button class="notification-close">&times;</button>
                </div>
            `);
            
            $('body').append($notification);
            
            // Entrance animation
            setTimeout(() => $notification.addClass('show'), 10);
            
            // Auto dismiss
            setTimeout(() => {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            }, 5000);
            
            // Manual dismiss
            $notification.find('.notification-close').on('click', function() {
                $notification.removeClass('show');
                setTimeout(() => $notification.remove(), 300);
            });
        },
        
        animateCounters: function() {
            $('.mt-stat-number').each(function() {
                const $this = $(this);
                const target = parseInt($this.text());
                
                $this.prop('Counter', 0).animate({
                    Counter: target
                }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function(now) {
                        $this.text(Math.ceil(now));
                    }
                });
            });
        },
        
        initializeAnimations: function() {
            // Add intersection observer for scroll animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, {
                threshold: 0.1
            });
            
            // Observe elements
            document.querySelectorAll('.mt-stat-box, .candidate-card').forEach(el => {
                observer.observe(el);
            });
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        if ($('.mt-jury-dashboard').length) {
            MTJuryDashboard.init();
        }
    });
    
})(jQuery);