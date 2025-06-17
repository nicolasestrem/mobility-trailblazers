/**
 * Elementor Compatibility JavaScript
 * 
 * @package MobilityTrailblazers
 */

(function($) {
    'use strict';

    // Elementor Compatibility Manager
    window.MTElementor = {
        // Initialize
        init: function() {
            // Check if Elementor is present
            if (typeof elementorFrontend === 'undefined') {
                return;
            }
            
            // Bind to Elementor hooks
            elementorFrontend.hooks.addAction('frontend/element_ready/mt-jury-dashboard.default', this.initJuryDashboard);
            elementorFrontend.hooks.addAction('frontend/element_ready/mt-candidate-grid.default', this.initCandidateGrid);
            elementorFrontend.hooks.addAction('frontend/element_ready/mt-voting-form.default', this.initVotingForm);
            elementorFrontend.hooks.addAction('frontend/element_ready/mt-statistics.default', this.initStatistics);
            
            // Re-initialize on Elementor editor save
            if (elementor) {
                elementor.on('preview:loaded', function() {
                    MTElementor.reinitializeAll();
                });
            }
        },
        
        // Initialize Jury Dashboard widget
        initJuryDashboard: function($scope) {
            var $element = $scope.find('.mt-jury-dashboard');
            
            if ($element.length && typeof MTJuryDashboard !== 'undefined') {
                // Re-initialize dashboard functionality
                MTJuryDashboard.init();
                
                // Handle dynamic content updates
                $element.on('mt:content-updated', function() {
                    MTJuryDashboard.initializeSliders();
                    MTJuryDashboard.checkProgress();
                });
            }
        },
        
        // Initialize Candidate Grid widget
        initCandidateGrid: function($scope) {
            var $element = $scope.find('.mt-candidates-grid');
            
            if ($element.length) {
                // Initialize masonry if enabled
                var settings = $element.data('settings');
                
                if (settings && settings.enable_masonry) {
                    $element.masonry({
                        itemSelector: '.mt-candidate-card',
                        columnWidth: '.grid-sizer',
                        percentPosition: true,
                        gutter: settings.grid_gap || 20
                    });
                }
                
                // Initialize filtering
                if (settings && settings.enable_filtering) {
                    MTElementor.setupGridFiltering($element);
                }
                
                // Initialize load more
                if (settings && settings.enable_load_more) {
                    MTElementor.setupLoadMore($element);
                }
                
                // Initialize animations
                if (settings && settings.enable_animations) {
                    MTElementor.setupAnimations($element);
                }
            }
        },
        
        // Initialize Voting Form widget
        initVotingForm: function($scope) {
            var $element = $scope.find('.mt-voting-form');
            
            if ($element.length && typeof MTFrontend !== 'undefined') {
                // Re-initialize voting functionality
                MTFrontend.initializeVoting();
                
                // Setup form validation
                MTFrontend.setupFormValidation();
                
                // Handle AJAX form submission
                $element.on('submit', function(e) {
                    e.preventDefault();
                    MTFrontend.submitRegistration();
                });
            }
        },
        
        // Initialize Statistics widget
        initStatistics: function($scope) {
            var $element = $scope.find('.mt-statistics');
            
            if ($element.length) {
                // Animate counters
                MTElementor.animateCounters($element);
                
                // Initialize charts if present
                MTElementor.initializeCharts($element);
                
                // Setup auto-refresh if enabled
                var settings = $element.data('settings');
                if (settings && settings.auto_refresh) {
                    MTElementor.setupAutoRefresh($element, settings.refresh_interval || 30000);
                }
            }
        },
        
        // Setup grid filtering
        setupGridFiltering: function($grid) {
            var $filters = $grid.siblings('.mt-grid-filters');
            
            $filters.on('click', '.filter-button', function() {
                var filter = $(this).data('filter');
                
                // Update active state
                $filters.find('.filter-button').removeClass('active');
                $(this).addClass('active');
                
                // Filter items
                if (filter === '*') {
                    $grid.find('.mt-candidate-card').show();
                } else {
                    $grid.find('.mt-candidate-card').each(function() {
                        var categories = $(this).data('categories') || '';
                        if (categories.includes(filter)) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                }
                
                // Re-layout masonry
                if ($grid.data('masonry')) {
                    $grid.masonry('layout');
                }
            });
        },
        
        // Setup load more functionality
        setupLoadMore: function($grid) {
            var $button = $grid.siblings('.mt-load-more-button');
            var page = 1;
            var loading = false;
            
            $button.on('click', function() {
                if (loading) return;
                
                loading = true;
                var settings = $grid.data('settings');
                
                $.ajax({
                    url: mt_elementor.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mt_elementor_load_more',
                        page: ++page,
                        settings: settings,
                        nonce: mt_elementor.nonce
                    },
                    beforeSend: function() {
                        $button.addClass('loading');
                    },
                    success: function(response) {
                        if (response.success) {
                            var $newItems = $(response.data.html);
                            $grid.append($newItems);
                            
                            // Re-initialize masonry
                            if ($grid.data('masonry')) {
                                $grid.masonry('appended', $newItems);
                            }
                            
                            // Setup animations for new items
                            if (settings.enable_animations) {
                                MTElementor.setupAnimations($newItems);
                            }
                            
                            // Hide button if no more items
                            if (!response.data.has_more) {
                                $button.hide();
                            }
                        }
                    },
                    complete: function() {
                        $button.removeClass('loading');
                        loading = false;
                    }
                });
            });
        },
        
        // Setup animations
        setupAnimations: function($element) {
            var $items = $element.find('.mt-animate-item');
            
            if ('IntersectionObserver' in window) {
                var animationObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            $(entry.target).addClass('animated');
                            animationObserver.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1
                });
                
                $items.each(function() {
                    animationObserver.observe(this);
                });
            } else {
                // Fallback for older browsers
                $items.addClass('animated');
            }
        },
        
        // Animate counters
        animateCounters: function($element) {
            var $counters = $element.find('.mt-counter');
            
            $counters.each(function() {
                var $counter = $(this);
                var target = parseInt($counter.data('target'));
                var duration = parseInt($counter.data('duration')) || 2000;
                var start = 0;
                var increment = target / (duration / 16);
                
                var timer = setInterval(function() {
                    start += increment;
                    if (start >= target) {
                        start = target;
                        clearInterval(timer);
                    }
                    $counter.text(Math.floor(start).toLocaleString());
                }, 16);
            });
        },
        
        // Initialize charts
        initializeCharts: function($element) {
            var $charts = $element.find('.mt-chart');
            
            $charts.each(function() {
                var $chart = $(this);
                var type = $chart.data('type');
                var data = $chart.data('chart-data');
                
                if (typeof Chart !== 'undefined' && data) {
                    var ctx = $chart.find('canvas')[0].getContext('2d');
                    new Chart(ctx, {
                        type: type,
                        data: data,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 1000
                            }
                        }
                    });
                }
            });
        },
        
        // Setup auto-refresh
        setupAutoRefresh: function($element, interval) {
            setInterval(function() {
                var settings = $element.data('settings');
                
                $.ajax({
                    url: mt_elementor.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mt_elementor_refresh_statistics',
                        settings: settings,
                        nonce: mt_elementor.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update counters
                            $.each(response.data.statistics, function(key, value) {
                                var $counter = $element.find('[data-statistic="' + key + '"]');
                                if ($counter.length) {
                                    $counter.text(value.toLocaleString());
                                }
                            });
                            
                            // Trigger update event
                            $element.trigger('mt:statistics-updated', [response.data]);
                        }
                    }
                });
            }, interval);
        },
        
        // Re-initialize all widgets
        reinitializeAll: function() {
            // Re-initialize each widget type
            $('.mt-jury-dashboard').each(function() {
                MTElementor.initJuryDashboard($(this).closest('.elementor-widget'));
            });
            
            $('.mt-candidates-grid').each(function() {
                MTElementor.initCandidateGrid($(this).closest('.elementor-widget'));
            });
            
            $('.mt-voting-form').each(function() {
                MTElementor.initVotingForm($(this).closest('.elementor-widget'));
            });
            
            $('.mt-statistics').each(function() {
                MTElementor.initStatistics($(this).closest('.elementor-widget'));
            });
        },
        
        // Handle responsive breakpoints
        handleResponsive: function() {
            var breakpoints = elementorFrontend.config.breakpoints;
            
            $(window).on('resize', function() {
                var windowWidth = $(window).width();
                
                // Adjust grid columns
                $('.mt-candidates-grid').each(function() {
                    var $grid = $(this);
                    var settings = $grid.data('settings');
                    
                    if (settings && $grid.data('masonry')) {
                        var columns = 3; // Default
                        
                        if (windowWidth <= breakpoints.md) {
                            columns = settings.columns_mobile || 1;
                        } else if (windowWidth <= breakpoints.lg) {
                            columns = settings.columns_tablet || 2;
                        } else {
                            columns = settings.columns || 3;
                        }
                        
                        // Update masonry
                        $grid.masonry('option', {
                            columnWidth: $grid.width() / columns
                        });
                    }
                });
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        MTElementor.init();
        MTElementor.handleResponsive();
    });
    
    // Also initialize when Elementor frontend is ready
    $(window).on('elementor/frontend/init', function() {
        MTElementor.init();
    });
    
})(jQuery); 