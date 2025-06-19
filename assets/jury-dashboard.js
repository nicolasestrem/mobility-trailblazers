/**
 * Mobility Trailblazers Jury Dashboard
 * Complete evaluation system with real-time updates and auto-save
 * 
 * @package MobilityTrailblazers
 */

(function($) {
    'use strict';

    // Main dashboard object
    const MTJuryDashboard = {
        // Properties
        currentCandidate: null,
        formDirty: false,
        isSubmitting: false,
        autoSaveTimer: null,
        
        // Initialize
        init: function() {
            this.bindEvents();
            this.loadDashboardData();
            this.initKeyboardShortcuts();
            this.initLazyLoading();
            this.startAutoSave();
        },
        
        // Bind all events
        bindEvents: function() {
            // Search functionality
            $('#candidate-search').on('input', this.debounce(function() {
                MTJuryDashboard.filterCandidates($(this).val());
            }, 300));
            
            // Status filter buttons
            $('.filter-btn').on('click', function() {
                $('.filter-btn').removeClass('active');
                $(this).addClass('active');
                MTJuryDashboard.filterByStatus($(this).data('status'));
            });
            
            // Candidate card clicks
            $(document).on('click', '.candidate-card', function(e) {
                e.preventDefault();
                const candidateId = $(this).data('candidate-id');
                MTJuryDashboard.openEvaluationModal(candidateId);
            });
            
            // Modal close
            $('.modal-close, .modal-overlay').on('click', function() {
                MTJuryDashboard.closeModal();
            });
            
            // Prevent modal content clicks from closing
            $('.modal-content').on('click', function(e) {
                e.stopPropagation();
            });
            
            // Score sliders
            $(document).on('input change', '.score-slider', function() {
                MTJuryDashboard.updateScore($(this));
                MTJuryDashboard.formDirty = true;
            });
            
            // Comments field
            $(document).on('input', '#evaluation-comments', function() {
                MTJuryDashboard.formDirty = true;
            });
            
            // Save draft button
            $(document).on('click', '#save-draft', function() {
                MTJuryDashboard.saveEvaluation('draft');
            });
            
            // Submit evaluation button
            $(document).on('click', '#submit-evaluation', function() {
                if (MTJuryDashboard.validateForm()) {
                    if (confirm(mt_jury_ajax.i18n.confirm_submit)) {
                        MTJuryDashboard.saveEvaluation('final');
                    }
                }
            });
            
            // Progress animation on page load
            $(window).on('load', function() {
                MTJuryDashboard.animateProgress();
            });
        },
        
        // Load dashboard data via AJAX
        loadDashboardData: function() {
            $.ajax({
                url: mt_jury_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_jury_dashboard_data',
                    nonce: mt_jury_ajax.nonce
                },
                beforeSend: function() {
                    MTJuryDashboard.showLoader();
                },
                success: function(response) {
                    if (response.success) {
                        MTJuryDashboard.updateDashboard(response.data);
                    } else {
                        MTJuryDashboard.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    MTJuryDashboard.showNotification(mt_jury_ajax.i18n.error_loading, 'error');
                },
                complete: function() {
                    MTJuryDashboard.hideLoader();
                }
            });
        },
        
        // Update dashboard with loaded data
        updateDashboard: function(data) {
            // Update stats
            $('#assigned-count').text(data.stats.total_assigned);
            $('#completed-count').text(data.stats.completed);
            $('#draft-count').text(data.stats.drafts);
            $('#completion-percentage').text(data.stats.completion_rate + '%');
            
            // Update progress bar
            $('.progress-fill').css('width', data.stats.completion_rate + '%');
            
            // Update candidates grid
            this.renderCandidates(data.candidates);
            
            // Animate stats
            this.animateStats();
        },
        
        // Render candidates grid
        renderCandidates: function(candidates) {
            const grid = $('#candidates-grid');
            grid.empty();
            
            if (candidates.length === 0) {
                grid.html('<p class="no-candidates">' + mt_jury_ajax.i18n.no_candidates_found + '</p>');
                return;
            }
            
            candidates.forEach(function(candidate) {
                const statusClass = 'status-' + candidate.status;
                const statusText = MTJuryDashboard.getStatusText(candidate.status);
                const thumbnail = candidate.thumbnail || mt_jury_ajax.default_avatar;
                
                const card = `
                    <div class="candidate-card ${statusClass}" data-candidate-id="${candidate.id}" data-status="${candidate.status}">
                        <div class="candidate-header">
                            <img src="${thumbnail}" alt="${candidate.title}" class="candidate-image" loading="lazy">
                            <span class="candidate-status">${statusText}</span>
                        </div>
                        <div class="candidate-body">
                            <h3 class="candidate-name">${candidate.title}</h3>
                            ${candidate.company ? `<p class="candidate-company">${candidate.company}</p>` : ''}
                            ${candidate.category ? `<span class="candidate-category">${candidate.category}</span>` : ''}
                            <p class="candidate-excerpt">${candidate.excerpt}</p>
                        </div>
                        <div class="candidate-footer">
                            <button class="evaluate-btn">
                                ${candidate.status === 'completed' ? mt_jury_ajax.i18n.view_evaluation : mt_jury_ajax.i18n.evaluate}
                            </button>
                        </div>
                    </div>
                `;
                
                grid.append(card);
            });
        },
        
        // Open evaluation modal
        openEvaluationModal: function(candidateId) {
            this.currentCandidate = candidateId;
            
            $.ajax({
                url: mt_jury_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_candidate_evaluation',
                    candidate_id: candidateId,
                    nonce: mt_jury_ajax.nonce
                },
                beforeSend: function() {
                    MTJuryDashboard.showModalLoader();
                },
                success: function(response) {
                    if (response.success) {
                        MTJuryDashboard.populateModal(response.data);
                        $('#evaluation-modal').addClass('active');
                        $('body').addClass('modal-open');
                    } else {
                        MTJuryDashboard.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    MTJuryDashboard.showNotification(mt_jury_ajax.i18n.error_loading, 'error');
                },
                complete: function() {
                    MTJuryDashboard.hideModalLoader();
                }
            });
        },
        
        // Populate modal with candidate data
        populateModal: function(data) {
            const candidate = data.candidate;
            const evaluation = data.evaluation;
            const isReadonly = data.is_final;
            
            // Update candidate info
            $('#modal-candidate-name').text(candidate.title);
            $('#modal-candidate-company').text(candidate.company || '');
            $('#modal-candidate-position').text(candidate.position || '');
            $('#modal-candidate-content').html(candidate.content);
            
            // Show additional fields if available
            if (candidate.achievement) {
                $('#modal-candidate-achievement').html('<h4>' + mt_jury_ajax.i18n.achievement + '</h4>' + candidate.achievement).show();
            }
            if (candidate.impact) {
                $('#modal-candidate-impact').html('<h4>' + mt_jury_ajax.i18n.impact + '</h4>' + candidate.impact).show();
            }
            if (candidate.vision) {
                $('#modal-candidate-vision').html('<h4>' + mt_jury_ajax.i18n.vision + '</h4>' + candidate.vision).show();
            }
            
            // External links
            if (candidate.website) {
                $('#modal-candidate-website').attr('href', candidate.website).show();
            }
            if (candidate.linkedin) {
                $('#modal-candidate-linkedin').attr('href', candidate.linkedin).show();
            }
            
            // Populate evaluation form
            if (evaluation) {
                $('#courage-score').val(evaluation.courage);
                $('#innovation-score').val(evaluation.innovation);
                $('#implementation-score').val(evaluation.implementation);
                $('#relevance-score').val(evaluation.relevance);
                $('#visibility-score').val(evaluation.visibility);
                $('#evaluation-comments').val(evaluation.comments);
                
                // Update all score displays
                $('.score-slider').each(function() {
                    MTJuryDashboard.updateScore($(this));
                });
            } else {
                // Reset to defaults
                $('.score-slider').val(5);
                $('#evaluation-comments').val('');
                $('.score-slider').each(function() {
                    MTJuryDashboard.updateScore($(this));
                });
            }
            
            // Handle readonly state
            if (isReadonly) {
                $('.score-slider').prop('disabled', true);
                $('#evaluation-comments').prop('readonly', true);
                $('.evaluation-actions').hide();
                $('.evaluation-readonly-notice').show();
            } else {
                $('.score-slider').prop('disabled', false);
                $('#evaluation-comments').prop('readonly', false);
                $('.evaluation-actions').show();
                $('.evaluation-readonly-notice').hide();
            }
            
            // Reset form dirty state
            this.formDirty = false;
        },
        
        // Update score display and indicator
        updateScore: function($slider) {
            const score = parseInt($slider.val());
            const $display = $slider.siblings('label').find('.score-display');
            const $group = $slider.closest('.criteria-group');
            
            // Update display
            $display.text(score);
            
            // Update color indicator
            $group.removeClass('score-low score-medium score-good score-excellent');
            if (score <= 3) {
                $group.addClass('score-low');
            } else if (score <= 6) {
                $group.addClass('score-medium');
            } else if (score <= 8) {
                $group.addClass('score-good');
            } else {
                $group.addClass('score-excellent');
            }
            
            // Update total
            this.updateTotalScore();
        },
        
        // Calculate and update total score
        updateTotalScore: function() {
            let total = 0;
            $('.score-slider').each(function() {
                total += parseInt($(this).val());
            });
            
            $('#total-score').text(total);
            
            // Update score indicator
            const $indicator = $('#score-indicator');
            $indicator.removeClass('low medium good excellent');
            
            if (total <= 15) {
                $indicator.addClass('low');
            } else if (total <= 30) {
                $indicator.addClass('medium');
            } else if (total <= 40) {
                $indicator.addClass('good');
            } else {
                $indicator.addClass('excellent');
            }
        },
        
        // Validate form before submission
        validateForm: function() {
            let valid = true;
            let hasAllScores = true;
            
            $('.score-slider').each(function() {
                const score = parseInt($(this).val());
                if (score < 1 || score > 10) {
                    hasAllScores = false;
                }
            });
            
            if (!hasAllScores) {
                this.showNotification(mt_jury_ajax.i18n.please_complete_scores, 'error');
                valid = false;
            }
            
            return valid;
        },
        
        // Save evaluation (draft or final)
        saveEvaluation: function(status, silent = false) {
            if (this.isSubmitting) return;
            
            this.isSubmitting = true;
            const $button = status === 'draft' ? $('#save-draft') : $('#submit-evaluation');
            const originalText = $button.text();
            
            // Collect form data
            const formData = {
                action: 'mt_save_evaluation',
                candidate_id: this.currentCandidate,
                courage: $('#courage-score').val(),
                innovation: $('#innovation-score').val(),
                implementation: $('#implementation-score').val(),
                relevance: $('#relevance-score').val(),
                visibility: $('#visibility-score').val(),
                comments: $('#evaluation-comments').val(),
                status: status,
                nonce: $('#evaluation-form-fields input[name="mt_jury_evaluation_nonce"]').val()
            };
            
            $.ajax({
                url: mt_jury_ajax.ajax_url,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $button.text(mt_jury_ajax.i18n.saving).prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        if (!silent) {
                            MTJuryDashboard.showNotification(response.data.message, 'success');
                        }
                        
                        if (status === 'final') {
                            // Close modal and reload dashboard
                            setTimeout(function() {
                                MTJuryDashboard.closeModal();
                                MTJuryDashboard.loadDashboardData();
                            }, 1500);
                        } else {
                            // Update form dirty state
                            MTJuryDashboard.formDirty = false;
                        }
                    } else {
                        MTJuryDashboard.showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    MTJuryDashboard.showNotification(mt_jury_ajax.i18n.error_saving, 'error');
                },
                complete: function() {
                    $button.text(originalText).prop('disabled', false);
                    MTJuryDashboard.isSubmitting = false;
                }
            });
        },
        
        // Filter candidates by search term
        filterCandidates: function(searchTerm) {
            const term = searchTerm.toLowerCase();
            
            $('.candidate-card').each(function() {
                const $card = $(this);
                const name = $card.find('.candidate-name').text().toLowerCase();
                const company = $card.find('.candidate-company').text().toLowerCase();
                const category = $card.find('.candidate-category').text().toLowerCase();
                
                if (name.includes(term) || company.includes(term) || category.includes(term)) {
                    $card.show();
                } else {
                    $card.hide();
                }
            });
            
            // Update no results message
            const visibleCards = $('.candidate-card:visible').length;
            if (visibleCards === 0) {
                if (!$('.no-results').length) {
                    $('#candidates-grid').append('<p class="no-results">' + mt_jury_ajax.i18n.no_candidates_found + '</p>');
                }
            } else {
                $('.no-results').remove();
            }
        },
        
        // Filter by status
        filterByStatus: function(status) {
            if (status === 'all') {
                $('.candidate-card').show();
            } else {
                $('.candidate-card').hide();
                $('.candidate-card[data-status="' + status + '"]').show();
            }
            
            // Update no results message
            const visibleCards = $('.candidate-card:visible').length;
            if (visibleCards === 0) {
                if (!$('.no-results').length) {
                    $('#candidates-grid').append('<p class="no-results">' + mt_jury_ajax.i18n.no_candidates_found + '</p>');
                }
            } else {
                $('.no-results').remove();
            }
        },
        
        // Close modal
        closeModal: function() {
            // Check for unsaved changes
            if (this.formDirty) {
                if (!confirm(mt_jury_ajax.i18n.unsaved_changes)) {
                    return;
                }
            }
            
            $('#evaluation-modal').removeClass('active');
            $('body').removeClass('modal-open');
            this.currentCandidate = null;
            this.formDirty = false;
        },
        
        // Show notification
        showNotification: function(message, type = 'info') {
            const $notification = $('<div class="mt-notification ' + type + '">' + message + '</div>');
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.addClass('show');
            }, 100);
            
            setTimeout(function() {
                $notification.removeClass('show');
                setTimeout(function() {
                    $notification.remove();
                }, 300);
            }, 3000);
        },
        
        // Animate statistics
        animateStats: function() {
            $('.stat-value').each(function() {
                const $this = $(this);
                const target = parseInt($this.text());
                
                $({ count: 0 }).animate({ count: target }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function() {
                        $this.text(Math.floor(this.count));
                    },
                    complete: function() {
                        $this.text(target);
                    }
                });
            });
        },
        
        // Animate progress bar
        animateProgress: function() {
            const $progress = $('.progress-fill');
            const target = $progress.data('percentage') || $progress.width() / $progress.parent().width() * 100;
            
            $progress.css('width', 0);
            setTimeout(function() {
                $progress.css('width', target + '%');
            }, 100);
        },
        
        // Initialize keyboard shortcuts
        initKeyboardShortcuts: function() {
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + S: Save draft
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    if ($('#evaluation-modal').hasClass('active') && !$('.score-slider').prop('disabled')) {
                        MTJuryDashboard.saveEvaluation('draft');
                    }
                }
                
                // Ctrl/Cmd + Enter: Submit evaluation
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    if ($('#evaluation-modal').hasClass('active') && !$('.score-slider').prop('disabled')) {
                        $('#submit-evaluation').click();
                    }
                }
                
                // Escape: Close modal
                if (e.key === 'Escape') {
                    if ($('#evaluation-modal').hasClass('active')) {
                        MTJuryDashboard.closeModal();
                    }
                }
            });
        },
        
        // Initialize lazy loading
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src || img.src;
                            img.classList.add('loaded');
                            observer.unobserve(img);
                        }
                    });
                });
                
                document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        },
        
        // Start auto-save timer
        startAutoSave: function() {
            // Auto-save every 30 seconds if form is dirty
            this.autoSaveTimer = setInterval(function() {
                if (MTJuryDashboard.formDirty && !MTJuryDashboard.isSubmitting && $('#evaluation-modal').hasClass('active')) {
                    MTJuryDashboard.saveEvaluation('draft', true);
                }
            }, 30000);
        },
        
        // Helper functions
        showLoader: function() {
            $('.mt-jury-dashboard').addClass('loading');
        },
        
        hideLoader: function() {
            $('.mt-jury-dashboard').removeClass('loading');
        },
        
        showModalLoader: function() {
            $('#evaluation-modal').addClass('loading');
        },
        
        hideModalLoader: function() {
            $('#evaluation-modal').removeClass('loading');
        },
        
        getStatusText: function(status) {
            switch(status) {
                case 'completed':
                    return mt_jury_ajax.i18n.completed;
                case 'draft':
                    return mt_jury_ajax.i18n.draft;
                default:
                    return mt_jury_ajax.i18n.pending;
            }
        },
        
        // Debounce helper
        debounce: function(func, wait) {
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
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.mt-jury-dashboard').length) {
            MTJuryDashboard.init();
        }
    });
    
    // Export to global scope for debugging
    window.MTJuryDashboard = MTJuryDashboard;
    
})(jQuery);