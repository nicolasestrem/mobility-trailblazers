/**
 * Mobility Trailblazers Frontend JavaScript
 * Version: 2.2.3
 */
(function($) {
    'use strict';
    // Initialize mt_ajax object if not defined
    if (typeof mt_ajax === 'undefined') {
        // Fallback initialization for missing mt_ajax object
        window.mt_ajax = {
            ajax_url: '/wp-admin/admin-ajax.php',
            nonce: '',
            i18n: {
                error: 'An error occurred',
                success: 'Success',
                loading: 'Loading...',
                confirm: 'Are you sure?'
            }
        };
    }
    // Validate mt_ajax structure
    if (!mt_ajax.ajax_url) {
        mt_ajax.ajax_url = '/wp-admin/admin-ajax.php';
        // Using default AJAX URL
    }
    if (!mt_ajax.i18n) {
        mt_ajax.i18n = {};
        // Using empty i18n object
    }
    // Helper function to safely access i18n values from multiple sources
    // Make it globally accessible for all sections
    window.getI18nText = function(key, defaultValue) {
        var result = '';
        
        // First try mt_frontend_i18n.ui (from i18n handler)
        if (typeof mt_frontend_i18n !== 'undefined' && mt_frontend_i18n && mt_frontend_i18n.ui && mt_frontend_i18n.ui[key]) {
            result = mt_frontend_i18n.ui[key];
            return result;
        }
        
        // Then try mt_ajax.i18n
        if (typeof mt_ajax !== 'undefined' && mt_ajax && mt_ajax.i18n && mt_ajax.i18n[key]) {
            result = mt_ajax.i18n[key];
            return result;
        }
        
        // Then try mt_frontend.i18n
        if (typeof mt_frontend !== 'undefined' && mt_frontend && mt_frontend.i18n && mt_frontend.i18n[key]) {
            result = mt_frontend.i18n[key];
            return result;
        }
        
        // Fallback with special key mappings for backwards compatibility
        var keyMap = {
            'evaluation_submitted_full': 'evaluation_submitted'
        };
        
        if (keyMap[key]) {
            return window.getI18nText(keyMap[key], defaultValue);
        }
        
        return defaultValue || '';
    };
    // Create local reference for convenience
    var getI18nText = window.getI18nText;
    // Global error handler for uncaught JavaScript errors
    window.addEventListener('error', function(e) {
        if (window.MTErrorHandler) {
            MTErrorHandler.logError('JavaScript Error', {
                message: e.message,
                filename: e.filename,
                lineno: e.lineno,
                colno: e.colno,
                stack: e.error ? e.error.stack : 'No stack trace available'
            });
        }
    });
    
    // Initialize interval and timeout storage
    window.mtIntervals = window.mtIntervals || {};
    window.mtTimeouts = window.mtTimeouts || {};
    window.mtEventListeners = window.mtEventListeners || [];
    
    // Cleanup function for intervals and event listeners
    window.mtCleanup = function() {
        // Clear all intervals
        if (window.mtIntervals) {
            for (var key in window.mtIntervals) {
                if (window.mtIntervals.hasOwnProperty(key)) {
                    clearInterval(window.mtIntervals[key]);
                    window.mtIntervals[key] = null;
                }
            }
        }
        
        // Remove all tracked event listeners
        if (window.mtEventListeners && window.mtEventListeners.length > 0) {
            window.mtEventListeners.forEach(function(listener) {
                if (listener.element && listener.element.removeEventListener) {
                    listener.element.removeEventListener(listener.event, listener.handler);
                }
            });
            window.mtEventListeners = [];
        }
        
        // Stop all jQuery animations to prevent memory leaks
        if (typeof jQuery !== 'undefined') {
            jQuery('*').stop(true, true);
            // Clean up any tooltips that might be lingering
            jQuery('.mt-tooltip').remove();
            jQuery('.mt-alert').remove();
        }
        
        // Clear any timeouts that might be stored
        if (window.mtTimeouts) {
            for (var timeoutKey in window.mtTimeouts) {
                if (window.mtTimeouts.hasOwnProperty(timeoutKey)) {
                    clearTimeout(window.mtTimeouts[timeoutKey]);
                    window.mtTimeouts[timeoutKey] = null;
                }
            }
        }
    };
    
    // Page unload cleanup
    window.addEventListener('beforeunload', function() {
        window.mtCleanup();
    });
    
    // Page Visibility API to pause/resume intervals
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            // Page is hidden, pause all intervals
            if (window.mtIntervals) {
                window.mtPausedIntervals = {};
                for (var key in window.mtIntervals) {
                    if (window.mtIntervals.hasOwnProperty(key) && window.mtIntervals[key]) {
                        clearInterval(window.mtIntervals[key]);
                        window.mtPausedIntervals[key] = true;
                    }
                }
            }
        } else {
            // Page is visible again, resume intervals
            if (window.mtPausedIntervals) {
                // Restore rankings refresh interval (60 seconds)
                if (window.mtPausedIntervals.rankingsRefresh) {
                    // Create a closure to avoid potential memory leaks
                    (function() {
                        var refreshFn = function() {
                            if (typeof jQuery !== 'undefined' && jQuery('#mt-rankings-container').length > 0) {
                                jQuery.ajax({
                                    url: mt_ajax.ajax_url,
                                    type: 'POST',
                                    data: {
                                        action: 'mt_get_jury_rankings',
                                        nonce: mt_ajax.nonce,
                                        limit: 10
                                    },
                                    success: function(response) {
                                        if (response.success && response.data.html) {
                                            jQuery('#mt-rankings-container').html(response.data.html);
                                            // Limit animation to visible items only
                                            jQuery('.mt-ranking-item:visible').each(function(index) {
                                                if (index < 20) { // Limit animations to prevent performance issues
                                                    jQuery(this).css('opacity', '0').delay(index * 50).animate({
                                                        opacity: 1
                                                    }, 300);
                                                }
                                            });
                                        }
                                    },
                                    error: function() {
                                        // Fail silently, no logging needed
                                    }
                                });
                            }
                        };
                        window.mtIntervals.rankingsRefresh = setInterval(refreshFn, 60000);
                    })();
                }
                
                // Restore inline rankings refresh interval (30 seconds)
                if (window.mtPausedIntervals.inlineRankingsRefresh && jQuery('.mt-rankings-section').length > 0) {
                    (function() {
                        var inlineRefreshFn = function() {
                            if (typeof jQuery !== 'undefined' && jQuery('.mt-rankings-section').length > 0) {
                                // Check if we still have the container before refreshing
                                if (jQuery('#mt-rankings-container').length > 0) {
                                    jQuery.ajax({
                                        url: mt_ajax.ajax_url,
                                        type: 'POST',
                                        data: {
                                            action: 'mt_get_jury_rankings',
                                            nonce: mt_ajax.nonce,
                                            limit: 10
                                        },
                                        success: function(response) {
                                            if (response.success && response.data.html) {
                                                jQuery('#mt-rankings-container').html(response.data.html);
                                            }
                                        },
                                        error: function() {
                                            // Fail silently
                                        }
                                    });
                                }
                            }
                        };
                        window.mtIntervals.inlineRankingsRefresh = setInterval(inlineRefreshFn, 30000);
                    })();
                }
                
                window.mtPausedIntervals = {};
            }
        }
    });
    
    // Force refresh evaluation form when returning from editing
    // This ensures criteria grid content is updated after editing candidates
    if (window.location.href.includes('evaluate=')) {
        // Check if we're coming back from editing
        var referrer = document.referrer;
        if (referrer && (referrer.includes('action=edit') || referrer.includes('action=elementor'))) {
            // Store flag to prevent infinite refresh loop
            if (!sessionStorage.getItem('mt_evaluation_refreshed')) {
                sessionStorage.setItem('mt_evaluation_refreshed', 'true');
                // Force refresh to get updated content
                window.location.reload(true);
            }
        } else {
            // Clear refresh flag if not coming from edit
            sessionStorage.removeItem('mt_evaluation_refreshed');
        }
    }
    // Initialize error handler
    window.MTErrorHandler = {
        /**
         * Log error to console and potentially send to server
         */
        logError: function(message, details) {
            var errorData = {
                message: message,
                details: details || {},
                timestamp: new Date().toISOString(),
                url: window.location.href,
                userAgent: navigator.userAgent
            };
            // Log to console in debug mode
            if (window.console && console.error) {
                // // Error logging removed for production
            }
        },
        /**
         * Show user-friendly error message
         */
        showUserError: function(message, type) {
            type = type || 'error';
            var alertClass = type === 'warning' ? 'mt-alert-warning' : 'mt-alert-error';
            var $alert = $('<div class="mt-alert ' + alertClass + '">' +
                '<span class="mt-alert-message">' + message + '</span>' +
                '<button class="mt-alert-close" type="button">&times;</button>' +
                '</div>');
            // Remove existing alerts
            $('.mt-alert').remove();
            // Add new alert
            $('body').prepend($alert);
            // Auto-remove after 5 seconds (tracked)
            if (window.mtTimeouts.alertAutoRemove) {
                clearTimeout(window.mtTimeouts.alertAutoRemove);
            }
            window.mtTimeouts.alertAutoRemove = setTimeout(function() {
                $alert.fadeOut(function() {
                    $alert.remove();
                });
                delete window.mtTimeouts.alertAutoRemove;
            }, 5000);
            // Manual close
            $alert.find('.mt-alert-close').on('click', function() {
                $alert.fadeOut(function() {
                    $alert.remove();
                });
            });
        },
        /**
         * Handle AJAX errors with user-friendly messages
         */
        handleAjaxError: function(xhr, status, error, context) {
            var errorMessage = getI18nText('error', 'An error occurred. Please try again.');
            var logDetails = {
                status: status,
                error: error,
                responseText: xhr.responseText,
                context: context || 'Unknown'
            };
            // Try to extract meaningful error message
            try {
                if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (status === 'timeout') {
                    errorMessage = getI18nText('request_timeout', 'Request timed out. Please check your connection and try again.');
                } else if (status === 'abort') {
                    errorMessage = getI18nText('request_cancelled', 'Request was cancelled.');
                } else if (xhr.status === 403) {
                    errorMessage = getI18nText('permission_denied', 'You do not have permission to perform this action.');
                } else if (xhr.status === 404) {
                    errorMessage = getI18nText('resource_not_found', 'The requested resource was not found.');
                } else if (xhr.status >= 500) {
                    errorMessage = getI18nText('server_error', 'Server error. Please try again later.');
                }
            } catch (e) {
                logDetails.parseError = e.message;
            }
            this.logError('AJAX Error in ' + context, logDetails);
            this.showUserError(errorMessage);
            return errorMessage;
        }
    };
    // Jury Dashboard
    var MTJuryDashboard = {
        init: function() {
            this.bindEvents();
            this.initEvaluationForm();
            this.updateTotalScore();
            this.initCharacterCount();
        },
        bindEvents: function() {
            var self = this;
            
            // Helper function to add tracked event listener
            function addTrackedListener(element, event, handler) {
                $(element).on(event, handler);
                // Track delegated event listeners for cleanup if needed
                if (window.mtEventListeners) {
                    window.mtEventListeners.push({
                        element: document,
                        event: event,
                        selector: element,
                        handler: handler
                    });
                }
            }
            
            // Evaluation form submission
            addTrackedListener('#mt-evaluation-form', 'submit', function(e) {
                self.submitEvaluation.call(self, e);
            });
            // Score slider updates
            addTrackedListener('.mt-score-slider', 'input', function() {
                self.updateScoreDisplay.call(this);
            });
            // Score mark clicks
            addTrackedListener('.mt-score-mark', 'click', function(e) {
                self.setScoreFromMark.call(this, e);
            });
            // Load evaluation modal
            addTrackedListener('.mt-evaluate-btn', 'click', function(e) {
                self.loadEvaluation.call(self, e);
            });
            // Character count
            addTrackedListener('#mt-comments', 'input', function() {
                self.updateCharCount.call(this);
            });
        },
        initEvaluationForm: function() {
            // Check if we're on evaluation page
            var urlParams = new URLSearchParams(window.location.search);
            var candidateId = urlParams.get('evaluate');
            if (candidateId) {
                this.loadEvaluationForm(candidateId);
            }
        },
        loadEvaluation: function(e) {
            e.preventDefault();
            var $btn = $(this);
            var candidateId = $btn.data('candidate-id');
            if (candidateId) {
                this.loadEvaluationForm(candidateId);
            } else {
                this.showError(getI18nText('invalid_candidate', 'Invalid candidate ID.'));
            }
        },
        loadEvaluationForm: function(candidateId) {
            var self = this;
            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.nonce) {
                MTErrorHandler.logError('mt_ajax configuration missing', {
                    mt_ajax_defined: typeof mt_ajax !== 'undefined',
                    nonce_present: mt_ajax && mt_ajax.nonce ? true : false
                });
                MTErrorHandler.showUserError(getI18nText('security_error', 'Security configuration error. Please refresh the page and try again.'));
                return;
            }
            // Show loading state
            var $container = $('.mt-jury-dashboard');
            var loadingText = getI18nText('loading_evaluation', 'Loading evaluation form...');
            $container.html('<div class="mt-loading">' + loadingText + '</div>');
            // Load candidate details
            $.post(mt_ajax.ajax_url, {
                action: 'mt_get_candidate_details',
                candidate_id: candidateId,
                nonce: mt_ajax.nonce
            })
            .done(function(response) {
                if (response.success) {
                    // The candidate data is nested under response.data.data
                    var candidateData = response.data.data || response.data;
                    self.displayEvaluationForm(candidateData);
                    self.loadExistingEvaluation(candidateId);
                } else {
                    self.showError(response.data.message);
                }
            })
            .fail(function(xhr, status, error) {
                MTErrorHandler.handleAjaxError(xhr, status, error, 'loadEvaluationForm - candidate details');
            });
        },
        displayEvaluationForm: function(candidate) {
            var formHtml = `
                <div class="mt-evaluation-wrapper">
                    <div class="mt-candidate-details">
                        <h2>${candidate.name}</h2>
                        <p class="mt-candidate-org">${candidate.organization || ''}</p>
                        ${candidate.photo_url ? `<img src="${candidate.photo_url}" alt="${candidate.name}" class="mt-candidate-photo-eval">` : ''}
                        <div class="mt-candidate-bio">${candidate.bio}</div>
                    </div>
                    <form id="mt-evaluation-form" class="mt-evaluation-form">
                        <input type="hidden" name="candidate_id" value="${candidate.id}">
                        <div class="mt-criteria-section">
                            <h3>${mt_frontend.i18n.evaluation_criteria || 'Evaluation Criteria'}</h3>
                            <div class="mt-criterion-card" data-criterion="courage">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-superhero"></span>
                                    <h4 class="mt-criterion-label">${mt_frontend.i18n.mut_pioniergeist || 'Mut & Pioniergeist'}</h4>
                                </div>
                                <p class="mt-criterion-description">${mt_frontend.i18n.mut_description || 'Mut, Konventionen herauszufordern und neue Wege in der Mobilität zu beschreiten'}</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="courage_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            <div class="mt-criterion-card" data-criterion="innovation">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-lightbulb"></span>
                                    <h4 class="mt-criterion-label">${mt_frontend.i18n.innovationsgrad || 'Innovationsgrad'}</h4>
                                </div>
                                <p class="mt-criterion-description">${mt_frontend.i18n.innovation_description || 'Grad an Innovation und Kreativität bei der Lösung von Mobilitätsherausforderungen'}</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="innovation_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            <div class="mt-criterion-card" data-criterion="implementation">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-hammer"></span>
                                    <h4 class="mt-criterion-label">${mt_frontend.i18n.umsetzungskraft || 'Umsetzungskraft & Wirkung'}</h4>
                                </div>
                                <p class="mt-criterion-description">${mt_frontend.i18n.umsetzung_description || 'Fähigkeit zur Umsetzung und realer Einfluss der Initiativen'}</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="implementation_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            <div class="mt-criterion-card" data-criterion="relevance">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-location-alt"></span>
                                    <h4 class="mt-criterion-label">${mt_frontend.i18n.relevanz || 'Relevanz für die Mobilitätswende'}</h4>
                                </div>
                                <p class="mt-criterion-description">${mt_frontend.i18n.relevanz_description || 'Bedeutung und Beitrag zur Transformation der Mobilität'}</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="relevance_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                            <div class="mt-criterion-card" data-criterion="visibility">
                                <div class="mt-criterion-header">
                                    <span class="mt-criterion-icon dashicons dashicons-visibility"></span>
                                    <h4 class="mt-criterion-label">${mt_frontend.i18n.vorbildfunktion || 'Vorbildfunktion & Sichtbarkeit'}</h4>
                                </div>
                                <p class="mt-criterion-description">${mt_frontend.i18n.vorbild_description || 'Rolle als Vorbild und öffentliche Wahrnehmbarkeit im Mobilitätssektor'}</p>
                                <div class="mt-score-slider-wrapper">
                                    <input type="range" name="visibility_score" class="mt-score-slider" min="0" max="10" value="5">
                                    <div class="mt-score-marks">
                                        <span class="mt-score-mark" data-value="0">0</span>
                                        <span class="mt-score-mark" data-value="1">1</span>
                                        <span class="mt-score-mark" data-value="2">2</span>
                                        <span class="mt-score-mark" data-value="3">3</span>
                                        <span class="mt-score-mark" data-value="4">4</span>
                                        <span class="mt-score-mark" data-value="5">5</span>
                                        <span class="mt-score-mark" data-value="6">6</span>
                                        <span class="mt-score-mark" data-value="7">7</span>
                                        <span class="mt-score-mark" data-value="8">8</span>
                                        <span class="mt-score-mark" data-value="9">9</span>
                                        <span class="mt-score-mark" data-value="10">10</span>
                                    </div>
                                </div>
                                <div class="mt-score-display">
                                    <span class="mt-score-value">5</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-comments-section">
                            <label for="mt-comments" class="mt-comments-label">${getI18nText('additional_comments', 'Additional Comments (Optional)')}</label>
                            <textarea name="comments" id="mt-comments" class="mt-comments-textarea" rows="5"></textarea>
                            <div class="mt-char-count">
                                <span id="mt-char-current">0</span> / 1000 ${getI18nText('characters', 'characters')}
                            </div>
                        </div>
                        <div class="mt-form-actions">
                            <button type="submit" class="mt-btn mt-btn-primary">${getI18nText('submit_evaluation', 'Submit Evaluation')}</button>
                            <a href="${window.location.pathname}" class="mt-btn mt-btn-secondary">${getI18nText('back_to_dashboard', 'Back to Dashboard')}</a>
                        </div>
                    </form>
                </div>
            `;
            $('.mt-jury-dashboard').html(formHtml);
            // Verify the hidden input was created successfully
        },
        loadExistingEvaluation: function(candidateId) {
            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.nonce) {
                MTJuryDashboard.showError(getI18nText('security_error', 'Security configuration error. Please refresh the page and try again.'));
                return;
            }
            $.post(mt_ajax.ajax_url, {
                action: 'mt_get_evaluation',
                candidate_id: candidateId,
                nonce: mt_ajax.nonce
            })
            .done(function(response) {
                if (response.success && response.data.exists) {
                    var evaluation = response.data.evaluation;
                    // Populate form with existing values
                    $('input[name="courage_score"]').val(evaluation.courage_score).trigger('input');
                    $('input[name="innovation_score"]').val(evaluation.innovation_score).trigger('input');
                    $('input[name="implementation_score"]').val(evaluation.implementation_score).trigger('input');
                    $('input[name="relevance_score"]').val(evaluation.relevance_score).trigger('input');
                    $('input[name="visibility_score"]').val(evaluation.visibility_score).trigger('input');
                    $('textarea[name="comments"]').val(evaluation.comments);
                    // Show status
                    if (evaluation.status === 'completed') {
                        $('.mt-form-actions').prepend('<div class="mt-notice mt-notice-success">' + getI18nText('evaluation_submitted_editable', 'This evaluation has been submitted. You can still edit and resubmit.') + '</div>');
                    }
                }
            });
        },
        updateScoreDisplay: function() {
            var $input = $(this);
            var value = $input.val();
            var $card = $input.closest('.mt-criterion-card');
            // Update the score display
            $card.find('.mt-score-value').text(value);
            // Update slider background gradient
            var percentage = (value / 10) * 100;
            $input.css('background', 'linear-gradient(to right, #667eea 0%, #667eea ' + percentage + '%, #e5e7eb ' + percentage + '%, #e5e7eb 100%)');
            // Update total score
            var self = MTJuryDashboard;
            self.updateTotalScore();
        },
        setScoreFromMark: function(e) {
            e.preventDefault();
            var $mark = $(this);
            var value = $mark.data('value');
            var $slider = $mark.closest('.mt-criterion-card').find('.mt-score-slider');
            $slider.val(value).trigger('input');
        },
        updateTotalScore: function() {
            var total = 0;
            var count = 0;
            var nonZeroCount = 0;
            // Try multiple selectors to find score inputs
            var $sliders = $('.mt-score-slider');
            if ($sliders.length === 0) {
                // Try alternate selectors
                $sliders = $('input[type="range"][name*="_score"]');
            }
            if ($sliders.length === 0) {
                // Try numeric inputs
                $sliders = $('input[type="number"][name*="_score"]');
            }
            if ($sliders.length === 0) {
                // Try hidden inputs (used with button-style scoring)
                $sliders = $('input[type="hidden"][name*="_score"]');
            }
            $sliders.each(function() {
                var value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    total += value;
                    count++;
                    if (value > 0) {
                        nonZeroCount++;
                    }
                }
            });
            // Calculate average
            var average = count > 0 ? (total / count).toFixed(1) : '0.0';
            // Update the display
            var $totalScore = $('#mt-total-score');
            if ($totalScore.length) {
                $totalScore.text(average);
            }
            // Also update the evaluated count
            var $evaluatedCount = $('.mt-evaluated-count');
            if ($evaluatedCount.length) {
                var evaluatedText = '(' + nonZeroCount + '/5 ' + getI18nText('criteria_evaluated', 'criteria evaluated') + ')';
                $evaluatedCount.text(evaluatedText);
            }
        },
        updateCharCount: function() {
            var $textarea = $(this);
            var length = $textarea.val().length;
            var maxLength = 1000;
            $('#mt-char-current').text(length);
            if (length > maxLength) {
                $textarea.val($textarea.val().substring(0, maxLength));
                $('#mt-char-current').text(maxLength);
            }
        },
        initCharacterCount: function() {
            var $textarea = $('#mt-comments');
            if ($textarea.length) {
                $('#mt-char-current').text($textarea.val().length);
            }
        },
        /**
         * Refresh criteria content for candidate
         */
        refreshCriteriaContent: function(candidateId) {
            if ($('.mt-criteria-info-section').length > 0) {
                // Force page reload to get fresh content
                // This ensures all caches are bypassed
                var currentUrl = window.location.href;
                if (currentUrl.includes('?')) {
                    currentUrl += '&refresh=' + Date.now();
                } else {
                    currentUrl += '?refresh=' + Date.now();
                }
                window.location.href = currentUrl;
            }
        },
        
        isSubmittingEvaluation: false, // Double-submission protection
        
        submitEvaluation: function(e) {
            e.preventDefault();
            
            // Prevent double submission
            if (this.isSubmittingEvaluation) {
                console.log('Evaluation submission already in progress');
                return false;
            }
            
            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.nonce) {
                MTJuryDashboard.showError(getI18nText('security_error', 'Security configuration error. Please refresh the page and try again.'));
                return;
            }
            
            this.isSubmittingEvaluation = true;
            var self = this;
            var $form = $(e.target);
            var $submitBtn = $form.find('button[type="submit"]');
            // Validate form selection
            // Validate scores (check all types of score inputs)
            var isValid = true;
            var $scoreInputs = $('.mt-score-slider');
            if ($scoreInputs.length === 0) {
                $scoreInputs = $('input[name*="_score"]'); // This will match hidden, range, number inputs
            }
            $scoreInputs.each(function() {
                var value = parseFloat($(this).val());
                if (isNaN(value) || value < 0 || value > 10) {
                    isValid = false;
                    return false;
                }
            });
            if (!isValid) {
                MTJuryDashboard.showError(getI18nText('invalid_scores', 'Please ensure all scores are between 0 and 10.'));
                return;
            }
            // Disable button and show loading
            var submittingText = getI18nText('submitting', 'Submitting...');
            $submitBtn.prop('disabled', true).html('<span class="dashicons dashicons-update mt-spin"></span> ' + submittingText);
            // Get form data including all fields
            var formData = {};
            // Try multiple selectors to find the form
            var $targetForm = $('#mt-evaluation-form');
            if ($targetForm.length === 0) {
                $targetForm = $('.mt-evaluation-form');
            }
            if ($targetForm.length === 0) {
                $targetForm = $form;
            }
            // Add all form fields
            $targetForm.find('input, textarea, select').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                if (name && value !== undefined) {
                    formData[name] = value;
                }
            });
            // Add required AJAX fields
            formData.action = 'mt_submit_evaluation';
            formData.nonce = mt_ajax.nonce;
            formData.status = 'completed';
            // Submitting evaluation with data
            $.post(mt_ajax.ajax_url, formData)
                .done(function(response) {
                    // AJAX Response received
                    if (response.success) {
                        // Show success message - message is in response.data.message
                        var successMessage = response.data && response.data.message 
                                           ? response.data.message 
                                           : getI18nText('evaluation_submitted', 'Thank you for submitting your evaluation!');
                        $('.mt-evaluation-header').after('<div class="mt-notice mt-notice-success">' + successMessage + '</div>');
                        // Update status badge
                        var statusText = getI18nText('evaluation_submitted_status', 'Evaluation Submitted');
                        var $statusBadge = $('.mt-evaluation-title .mt-status-badge');
                        if ($statusBadge.length) {
                            $statusBadge.removeClass('mt-status-draft').addClass('mt-status-completed').text(statusText);
                        } else {
                            $('.mt-evaluation-title').append('<span class="mt-status-badge mt-status-completed">' + statusText + '</span>');
                        }
                        // Scroll to top
                        $('html, body').animate({ scrollTop: 0 }, 300);
                        // Re-enable button
                        $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> ' + getI18nText('submit_evaluation', 'Submit Evaluation'));
                        // Redirect after 3 seconds (tracked)
                        if (window.mtTimeouts.evaluationRedirect) {
                            clearTimeout(window.mtTimeouts.evaluationRedirect);
                        }
                        window.mtTimeouts.evaluationRedirect = setTimeout(function() {
                            window.location.href = window.location.pathname;
                            delete window.mtTimeouts.evaluationRedirect;
                        }, 3000);
                    } else {
                        // Error message is in response.data.message
                        var errorMessage = response.data && response.data.message ? response.data.message : getI18nText('error', 'An error occurred. Please try again.');
                        MTJuryDashboard.showError(errorMessage);
                        $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> ' + getI18nText('submit_evaluation', 'Submit Evaluation'));
                        self.isSubmittingEvaluation = false; // Reset submission flag
                    }
                })
                .fail(function() {
                    MTJuryDashboard.showError(getI18nText('error', 'An error occurred. Please try again.'));
                    $submitBtn.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> ' + getI18nText('submit_evaluation_btn', getI18nText('submit_evaluation', 'Submit Evaluation')));
                    self.isSubmittingEvaluation = false; // Reset submission flag
                });
        },
        showError: function(message) {
            var errorHtml = '<div class="mt-notice mt-notice-error">' + message + '</div>';
            $('.mt-evaluation-form').before(errorHtml);
            $('html, body').animate({ scrollTop: 0 }, 300);
        }
    };
    // Initialize on document ready
    $(document).ready(function() {
        MTJuryDashboard.init();
        // Fallback initialization after a short delay
        setTimeout(function() {
            MTJuryDashboard.updateTotalScore();
        }, 500);
        // Also try on window load
        $(window).on('load', function() {
            MTJuryDashboard.updateTotalScore();
        });
    });
    // Star rating functionality
    if ($('.mt-star-rating').length) {
        $('.mt-star-rating').each(function() {
            var $rating = $(this);
            var $input = $rating.find('input[type="hidden"]');
            var $stars = $rating.find('.mt-star');
            $stars.on('click', function() {
                var value = $(this).data('value');
                $input.val(value).trigger('change');
                updateStars($rating, value);
            });
            $stars.on('mouseenter', function() {
                var value = $(this).data('value');
                updateStars($rating, value);
            });
            $rating.on('mouseleave', function() {
                var value = $input.val() || 0;
                updateStars($rating, value);
            });
        });
        function updateStars($rating, value) {
            $rating.find('.mt-star').each(function() {
                var starValue = $(this).data('value');
                $(this).toggleClass('filled', starValue <= value);
            });
        }
    }
    // Star rating functionality
    $(document).on('click', '.mt-star-rating .dashicons', function() {
        const $star = $(this);
        const value = $star.data('value');
        const $rating = $star.parent();
        $rating.find('.dashicons').removeClass('active');
        $rating.find('.dashicons').each(function() {
            if ($(this).data('value') <= value) {
                $(this).addClass('active');
            }
        });
        $rating.find('input[type="hidden"]').val(value);
        updateScoreDisplay($star.closest('.mt-criterion-card'), value);
    });
    // Button scoring functionality - DISABLED: Fixed in evaluation-rating-fix.js for Issue #21
    // This global handler was causing only one rating to work across all criteria
    /*
    $(document).on('click', '.mt-score-button', function() {
        const $button = $(this);
        const value = $button.data('value');
        const $group = $button.parent();
        $group.find('.mt-score-button').removeClass('active');
        $button.addClass('active');
        $group.find('input[type="hidden"]').val(value);
        updateScoreDisplay($button.closest('.mt-criterion-card'), value);
        // Update total score when button is clicked
        MTJuryDashboard.updateTotalScore();
    });
    */
    // Numeric input functionality
    $(document).on('input', '.mt-score-input', function() {
        const value = Math.min(10, Math.max(0, $(this).val()));
        $(this).val(value);
        updateScoreDisplay($(this).closest('.mt-criterion-card'), value);
    });
    // Update score display
    function updateScoreDisplay($criterion, value) {
        $criterion.find('.mt-score-value').text(value);
    }
    // Rankings update functionality
    jQuery(document).ready(function($) {
        // Auto-refresh rankings after evaluation submission
        $(document).on('mt:evaluation:submitted', function() {
            refreshRankings();
        });
        // Refresh rankings function
        function refreshRankings() {
            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.ajax_url || !mt_ajax.nonce) {
                // 
                return;
            }
            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_jury_rankings',
                    nonce: mt_ajax.nonce,
                    limit: 10
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        $('#mt-rankings-container').html(response.data.html);
                        // Add animation
                        $('.mt-ranking-item').each(function(index) {
                            $(this).css('opacity', '0').delay(index * 50).animate({
                                opacity: 1
                            }, 300);
                        });
                    }
                }
            });
        }
        // Store interval ID for cleanup
        var rankingsRefreshInterval = null;
        
        // Optional: Refresh rankings periodically with cleanup
        if (!window.mtIntervals) {
            window.mtIntervals = {};
        }
        
        // Clear any existing interval before setting new one
        if (window.mtIntervals.rankingsRefresh) {
            clearInterval(window.mtIntervals.rankingsRefresh);
        }
        
        window.mtIntervals.rankingsRefresh = setInterval(refreshRankings, 60000); // Every minute
    });
    // Enhanced Rankings Interactivity
    jQuery(document).ready(function($) {
        // Animate score rings on page load
        function animateScoreRings() {
            $('.mt-score-ring-progress').each(function() {
                const $this = $(this);
                const offset = $this.css('stroke-dashoffset');
                $this.css('stroke-dashoffset', '100');
                setTimeout(() => {
                    $this.css('stroke-dashoffset', offset);
                }, 100);
            });
        }
        // Initialize animations
        if ($('.mt-rankings-section').length) {
            animateScoreRings();
        }
        // Hover effects for ranking cards
        $(document).on('mouseenter', '.mt-ranking-item', function() {
            $(this).find('.mt-score-ring-progress').css('stroke', '#764ba2');
        }).on('mouseleave', function() {
            $(this).find('.mt-score-ring-progress').css('stroke', '#667eea');
        });
        // Click feedback
        $(document).on('click', '.mt-ranking-item', function() {
            $(this).css('transform', 'scale(0.98)');
            setTimeout(() => {
                $(this).css('transform', '');
            }, 150);
        });
    });
    // Inline Evaluation Controls
    jQuery(document).ready(function($) {
        initializeInlineEvaluations();
        function initializeInlineEvaluations() {
            // Initialize all score rings on page load
            $('.mt-score-ring-mini').each(function() {
                const score = $(this).data('score');
                updateMiniScoreRing($(this), score);
            });
            // Score adjustment buttons
            $(document).on('click', '.mt-score-adjust', function(e) {
                e.preventDefault();
                const $button = $(this);
                const $input = $button.siblings('.mt-score-input');
                const action = $button.data('action');
                const currentValue = parseFloat($input.val()) || 0;
                let newValue = currentValue;
                if (action === 'increase' && currentValue < 10) {
                    newValue = Math.min(10, currentValue + 0.5);
                } else if (action === 'decrease' && currentValue > 0) {
                    newValue = Math.max(0, currentValue - 0.5);
                }
                $input.val(newValue).trigger('change');
            });
            // Score input change handler
            $(document).on('change', '.mt-score-input', function() {
                const $input = $(this);
                const value = parseFloat($input.val()) || 0;
                // Validate and constrain value
                const constrainedValue = Math.max(0, Math.min(10, value));
                if (value !== constrainedValue) {
                    $input.val(constrainedValue);
                }
                // Update mini ring
                const $ring = $input.closest('.mt-criterion-inline').find('.mt-score-ring-mini');
                updateMiniScoreRing($ring, constrainedValue);
                // Update total score preview
                updateTotalScorePreview($input.closest('.mt-ranking-item'));
            });
            // Save inline evaluation
            $(document).on('click', '.mt-btn-save-inline', function(e) {
                e.preventDefault();
                const $button = $(this);
                const $form = $button.closest('.mt-inline-evaluation-form');
                const $rankingItem = $button.closest('.mt-ranking-item');
                const candidateId = $form.data('candidate-id');
                // Prevent double submission
                if ($button.hasClass('saving')) {
                    return;
                }
                // Collect scores
                const scores = {};
                $form.find('.mt-score-input').each(function() {
                    const criterion = $(this).attr('name');
                    const value = $(this).val();
                    if (criterion && value) {
                        scores[criterion] = value;
                    }
                });
                // Add loading state
                $button.addClass('saving');
                $rankingItem.addClass('updating');
                // Prepare form data
                // Check if mt_ajax is available
                if (typeof mt_ajax === 'undefined' || !mt_ajax.nonce || !mt_ajax.ajax_url) {
                    // 
                    alert('Configuration error. Please refresh the page and try again.');
                    $rankingItem.removeClass('updating');
                    return;
                }
                const formData = {
                    action: 'mt_save_inline_evaluation',
                    nonce: mt_ajax.nonce,
                    candidate_id: candidateId,
                    scores: scores
                };
                // Save via AJAX
                $.ajax({
                    url: mt_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            // Show success animation
                            $rankingItem.removeClass('updating').addClass('success');
                            // Update total score display
                            const totalScore = response.data.total_score;
                            const $scoreValue = $rankingItem.find('.mt-total-score-display .score-value');
                            $scoreValue.text(totalScore.toFixed(1) + '/10');
                            $scoreValue.data('score', totalScore);
                            // Update score color
                            updateScoreColor($scoreValue, totalScore);
                            // Trigger rankings refresh after a delay
                            setTimeout(function() {
                                refreshRankings();
                            }, 1500);
                            // Remove success class after animation
                            setTimeout(function() {
                                $rankingItem.removeClass('success');
                            }, 2000);
                        } else {
                            // // Error logging removed for production
                            alert(response.data || 'Error saving evaluation');
                            $rankingItem.removeClass('updating');
                        }
                    },
                    error: function(xhr, status, error) {
                        // // Error logging removed for production
                        // Try to parse error message
                        let errorMessage = 'Network error. Please try again.';
                        try {
                            if (xhr.responseJSON && xhr.responseJSON.data) {
                                errorMessage = xhr.responseJSON.data;
                            }
                        } catch (e) {
                            // Use default message
                        }
                        alert(errorMessage);
                        $rankingItem.removeClass('updating');
                    },
                    complete: function() {
                        $button.removeClass('saving');
                    }
                });
            });
        }
        function updateMiniScoreRing($ring, score) {
            const $progress = $ring.find('.mt-ring-progress');
            // Update ring
            const dashArray = (score * 10) + ', 100';
            $progress.attr('stroke-dasharray', dashArray);
            $ring.attr('data-score', score);
            // Update color based on score
            if (score >= 8) {
                $progress.css('stroke', '#22c55e');
            } else if (score >= 6) {
                $progress.css('stroke', '#667eea');
            } else if (score >= 4) {
                $progress.css('stroke', '#f59e0b');
            } else {
                $progress.css('stroke', '#ef4444');
            }
        }
        function updateTotalScorePreview($rankingItem) {
            const scores = [];
            $rankingItem.find('.mt-score-input').each(function() {
                const value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    scores.push(value);
                }
            });
            if (scores.length > 0) {
                const average = scores.reduce((a, b) => a + b, 0) / scores.length;
                const $scoreDisplay = $rankingItem.find('.mt-total-score-display .score-value');
                $scoreDisplay.text(average.toFixed(1) + '/10');
                updateScoreColor($scoreDisplay, average);
            }
        }
        function updateScoreColor($element, score) {
            if (score >= 8) {
                $element.css('color', '#22c55e');
            } else if (score >= 6) {
                $element.css('color', '#667eea');
            } else if (score >= 4) {
                $element.css('color', '#f59e0b');
            } else {
                $element.css('color', '#ef4444');
            }
        }
        function refreshRankings() {
            // Check if mt_ajax is available
            if (typeof mt_ajax === 'undefined' || !mt_ajax.ajax_url || !mt_ajax.nonce) {
                // 
                return;
            }
            const $container = $('#mt-rankings-container');
            $.ajax({
                url: mt_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_get_jury_rankings',
                    nonce: mt_ajax.nonce,
                    limit: 10
                },
                success: function(response) {
                    if (response.success && response.data.html) {
                        // Store current form values before refresh
                        const currentValues = {};
                        $('.mt-inline-evaluation-form').each(function() {
                            const candidateId = $(this).data('candidate-id');
                            currentValues[candidateId] = {};
                            $(this).find('.mt-score-input').each(function() {
                                const criterion = $(this).attr('name');
                                currentValues[candidateId][criterion] = $(this).val();
                            });
                        });
                        // Update the container
                        $container.fadeOut(300, function() {
                            $(this).html(response.data.html).fadeIn(300, function() {
                                // Reinitialize everything
                                initializeInlineEvaluations();
                            });
                        });
                    }
                },
                error: function() {
                    // Rankings refresh failed - fail silently in production
                }
            });
        }
        // Auto-refresh rankings every 30 seconds if on dashboard
        if ($('.mt-rankings-section').length > 0) {
            // Store interval ID for cleanup
            if (!window.mtIntervals) {
                window.mtIntervals = {};
            }
            
            // Clear any existing interval before setting new one
            if (window.mtIntervals.inlineRankingsRefresh) {
                clearInterval(window.mtIntervals.inlineRankingsRefresh);
            }
            
            window.mtIntervals.inlineRankingsRefresh = setInterval(refreshRankings, 30000);
        }
    });
})(jQuery); 
// === Jury Rankings Table Interactivity ===
(function($) {
    // Only run if the rankings table exists
    $(document).ready(function() {
        var $table = $('.mt-evaluation-table');
        if (!$table.length) return;
        // --- 1. Live total score calculation and color coding ---
        function updateRowTotal($row) {
            var total = 0;
            var count = 0;
            $row.find('.mt-eval-score-input').each(function() {
                var val = parseFloat($(this).val());
                if (!isNaN(val)) {
                    total += val;
                    count++;
                }
            });
            var avg = count > 0 ? (total / count) : 0;
            $row.find('.mt-eval-total-value').text(avg.toFixed(1));
            // Color code total
            var $totalCell = $row.find('.mt-eval-total-score');
            $totalCell.removeClass('score-high score-low');
            if (avg >= 8) $totalCell.addClass('score-high');
            else if (avg <= 3) $totalCell.addClass('score-low');
        }
        function updateScoreColor($input) {
            var val = parseFloat($input.val());
            $input.removeClass('score-high score-low');
            if (val >= 8) $input.addClass('score-high');
            else if (val <= 3) $input.addClass('score-low');
        }
        // --- 2. Mark row as unsaved on change ---
        $table.on('input change', '.mt-eval-score-input', function() {
            var $input = $(this);
            var $row = $input.closest('tr');
            updateScoreColor($input);
            updateRowTotal($row);
            $row.addClass('unsaved').removeClass('saving');
            $row.find('.mt-btn-save-eval').addClass('unsaved').removeClass('saving');
        });
        // --- 3. Save button AJAX ---
        $table.on('click', '.mt-btn-save-eval', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var $row = $btn.closest('tr');
            if ($btn.hasClass('saving')) return; // Prevent double submit
            $btn.addClass('saving').removeClass('unsaved');
            $row.addClass('saving').removeClass('unsaved');
            $btn.html('<span class="mt-eval-spinner"></span> ' + getI18nText('saving', 'Saving...'));
            // Collect scores
            var candidateId = $btn.data('candidate-id');
            var scores = {};
            $row.find('.mt-eval-score-input').each(function() {
                var name = $(this).attr('name');
                var val = $(this).val();
                scores[name] = val;
            });
            // Debug logging
            // 
            // 
            // Prepare AJAX data
            var ajaxData = {
                action: 'mt_save_inline_evaluation',
                nonce: (typeof mt_ajax !== 'undefined' && mt_ajax.nonce) ? mt_ajax.nonce : '',
                candidate_id: candidateId,
                scores: scores,
                context: 'table' // Add context to indicate this is from the evaluation table
            };
            $.ajax({
                url: (typeof mt_ajax !== 'undefined' && mt_ajax.ajax_url) ? mt_ajax.ajax_url : '',
                type: 'POST',
                data: ajaxData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $row.removeClass('unsaved saving').addClass('saved');
                        $btn.removeClass('saving').html('<span class="dashicons dashicons-saved"></span> ' + getI18nText('saved', 'Saved'));
                        setTimeout(function() {
                            $btn.html('<span class="dashicons dashicons-saved"></span> ' + getI18nText('save', 'Save'));
                            $row.removeClass('saved');
                        }, 1200);
                        // Update total score if returned (this is the AVERAGE from database)
                        if (response.data && response.data.total_score !== undefined) {
                            var totalScore = parseFloat(response.data.total_score);
                            $row.find('.mt-eval-total-value').text(totalScore.toFixed(1));
                            // Update data attribute for sorting
                            $row.data('total-score', totalScore);
                            // Trigger re-ranking if available
                            if (typeof rerankTable === 'function') {
                                setTimeout(rerankTable, 300);
                            }
                        }
                    } else {
                        // Log detailed error information
                        // // Error logging removed for production
                        var errorMessage = '';
                        if (response && response.data) {
                            if (typeof response.data === 'string') {
                                errorMessage = response.data;
                            } else if (response.data.message) {
                                errorMessage = response.data.message;
                            } else if (response.data.errors) {
                                errorMessage = response.data.errors.join(', ');
                            }
                        }
                        errorMessage = errorMessage || getI18nText('error_saving_evaluation', 'Error saving evaluation');
                        $btn.removeClass('saving').addClass('unsaved').html('<span class="dashicons dashicons-warning"></span> ' + getI18nText('error', 'Error'));
                        $row.removeClass('saving').addClass('unsaved');
                        setTimeout(function() {
                            $btn.html('<span class="dashicons dashicons-saved"></span> ' + getI18nText('save', 'Save'));
                        }, 2000);
                        if (window.MTErrorHandler) {
                            MTErrorHandler.showUserError(errorMessage);
                        } else {
                            alert(errorMessage);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    // Log detailed error information
                    // // Error logging removed for production
                    $btn.removeClass('saving').addClass('unsaved').html('<span class="dashicons dashicons-warning"></span> ' + getI18nText('error', 'Error'));
                    $row.removeClass('saving').addClass('unsaved');
                    setTimeout(function() {
                        $btn.html('<span class="dashicons dashicons-saved"></span> ' + getI18nText('save', 'Save'));
                    }, 2000);
                    // Try to get a meaningful error message
                    var errorMessage = getI18nText('network_error', 'Network error. Please try again.');
                    if (xhr.responseJSON && xhr.responseJSON.data) {
                        errorMessage = xhr.responseJSON.data;
                    } else if (xhr.responseText) {
                        // Check if responseText contains an error message
                        try {
                            var parsed = JSON.parse(xhr.responseText);
                            if (parsed.data) {
                                errorMessage = parsed.data;
                            }
                        } catch(e) {
                            // Not JSON, use default message
                        }
                    }
                    if (window.MTErrorHandler) {
                        MTErrorHandler.handleAjaxError(xhr, status, error, 'jury-rankings-table');
                    } else {
                        alert(errorMessage);
                    }
                }
            });
        });
        // --- 4. Tooltips for headers (native title attribute is used, but enhance for accessibility) ---
        $table.find('th[title]').each(function() {
            var $th = $(this);
            $th.attr('tabindex', '0');
            $th.on('focus mouseenter', function() {
                var title = $th.attr('title');
                if (!title) return;
                var $tip = $('<div class="mt-tooltip"></div>').text(title).appendTo('body');
                var offset = $th.offset();
                $tip.css({
                    top: offset.top + $th.outerHeight() + 4,
                    left: offset.left + $th.outerWidth()/2 - $tip.outerWidth()/2
                });
                $th.data('mt-tooltip', $tip);
            });
            $th.on('blur mouseleave', function() {
                var $tip = $th.data('mt-tooltip');
                if ($tip) $tip.remove();
            });
        });
        // --- 5. Initial color coding and total calculation ---
        $table.find('tbody tr').each(function() {
            var $row = $(this);
            $row.find('.mt-eval-score-input').each(function() {
                updateScoreColor($(this));
            });
            updateRowTotal($row);
        });
    });
})(jQuery); 
