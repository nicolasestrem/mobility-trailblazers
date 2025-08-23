/**
 * Mobility Trailblazers - Mobile Jury Dashboard JavaScript
 * Version: 1.0.0
 * Created: 2025-01-23
 * 
 * Description: Touch interactions and mobile optimizations for Jury Dashboard
 * Dependencies: jQuery
 */

(function($) {
    'use strict';
    
    // Mobile detection
    const isMobile = {
        Android: function() {
            return navigator.userAgent.match(/Android/i);
        },
        iOS: function() {
            return navigator.userAgent.match(/iPhone|iPad|iPod/i);
        },
        Opera: function() {
            return navigator.userAgent.match(/Opera Mini/i);
        },
        Windows: function() {
            return navigator.userAgent.match(/IEMobile/i) || navigator.userAgent.match(/WPDesktop/i);
        },
        any: function() {
            return (isMobile.Android() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows());
        }
    };
    
    // Touch event handling
    const touchHandler = {
        startX: 0,
        startY: 0,
        endX: 0,
        endY: 0,
        
        handleStart: function(e) {
            const touch = e.touches[0];
            this.startX = touch.clientX;
            this.startY = touch.clientY;
        },
        
        handleEnd: function(e, callback) {
            const touch = e.changedTouches[0];
            this.endX = touch.clientX;
            this.endY = touch.clientY;
            
            const diffX = this.endX - this.startX;
            const diffY = this.endY - this.startY;
            
            // Detect swipe direction
            if (Math.abs(diffX) > Math.abs(diffY)) {
                if (Math.abs(diffX) > 50) {
                    if (diffX > 0) {
                        callback('right');
                    } else {
                        callback('left');
                    }
                }
            }
        }
    };
    
    // Initialize mobile features
    function initMobileFeatures() {
        // Add mobile class to body
        if (isMobile.any()) {
            $('body').addClass('mt-is-mobile');
        }
        
        // Viewport height fix for mobile browsers
        setViewportHeight();
        $(window).on('resize orientationchange', setViewportHeight);
        
        // Touch feedback for buttons
        addTouchFeedback();
        
        // Swipe navigation for candidate cards
        initSwipeNavigation();
        
        // Optimize table scrolling
        optimizeTableScroll();
        
        // Pull to refresh
        initPullToRefresh();
        
        // Mobile menu enhancements
        enhanceMobileFilters();
        
        // iOS specific fixes
        if (isMobile.iOS()) {
            fixIOSInputZoom();
        }
        
        // Lazy loading for images
        initLazyLoading();
        
        // Smooth scroll for anchor links
        initSmoothScroll();
    }
    
    // Fix viewport height on mobile
    function setViewportHeight() {
        const vh = window.innerHeight * 0.01;
        document.documentElement.style.setProperty('--vh', `${vh}px`);
    }
    
    // Add touch feedback to interactive elements
    function addTouchFeedback() {
        const touchElements = '.mt-evaluate-btn, .mt-stat-card, .mt-candidate-card, .mt-ranking-item';
        
        $(touchElements).on('touchstart', function() {
            $(this).addClass('mt-touch-active');
        }).on('touchend touchcancel', function() {
            $(this).removeClass('mt-touch-active');
        });
    }
    
    // Initialize swipe navigation for candidate cards
    function initSwipeNavigation() {
        let currentCardIndex = 0;
        const $cards = $('.mt-candidate-card:visible');
        
        if ($cards.length === 0) return;
        
        $cards.each(function(index) {
            const $card = $(this);
            
            $card.on('touchstart', function(e) {
                touchHandler.handleStart(e.originalEvent);
            });
            
            $card.on('touchend', function(e) {
                touchHandler.handleEnd(e.originalEvent, function(direction) {
                    if (direction === 'left') {
                        // Show next card details
                        showCardDetails($card);
                    } else if (direction === 'right') {
                        // Hide card details
                        hideCardDetails($card);
                    }
                });
            });
        });
    }
    
    // Show card details with animation
    function showCardDetails($card) {
        $card.addClass('mt-card-expanded');
        
        // Add close button if not exists
        if (!$card.find('.mt-card-close').length) {
            $card.prepend('<button class="mt-card-close" aria-label="' + mt_frontend.i18n.close + '">×</button>');
        }
        
        // Close button handler
        $card.find('.mt-card-close').on('click', function(e) {
            e.stopPropagation();
            hideCardDetails($card);
        });
    }
    
    // Hide card details
    function hideCardDetails($card) {
        $card.removeClass('mt-card-expanded');
    }
    
    // Optimize table horizontal scrolling
    function optimizeTableScroll() {
        const $tableWrap = $('.mt-evaluation-table-wrap');
        
        if ($tableWrap.length === 0) return;
        
        // Add scroll indicators
        $tableWrap.each(function() {
            const $wrap = $(this);
            const $table = $wrap.find('.mt-evaluation-table');
            
            // Check if table needs horizontal scroll
            if ($table.width() > $wrap.width()) {
                $wrap.addClass('mt-has-scroll');
                
                // Add scroll hint
                if (!$wrap.find('.mt-scroll-hint').length) {
                    $wrap.append('<div class="mt-scroll-hint">' + 
                        '<span class="dashicons dashicons-arrow-right-alt"></span> ' +
                        mt_frontend.i18n.scroll_for_more + 
                        '</div>');
                }
                
                // Remove hint on scroll
                $wrap.on('scroll', function() {
                    if ($(this).scrollLeft() > 10) {
                        $(this).find('.mt-scroll-hint').fadeOut();
                    }
                });
            }
        });
    }
    
    // Pull to refresh functionality
    function initPullToRefresh() {
        if (!isMobile.any()) return;
        
        let pullStartY = 0;
        let pullMoveY = 0;
        const pullThreshold = 80;
        
        const $refreshIndicator = $('<div class="mt-pull-refresh">' +
            '<span class="dashicons dashicons-update"></span>' +
            '</div>').appendTo('body');
        
        $(document).on('touchstart', function(e) {
            if ($(window).scrollTop() === 0) {
                pullStartY = e.originalEvent.touches[0].clientY;
            }
        });
        
        $(document).on('touchmove', function(e) {
            if (pullStartY > 0) {
                pullMoveY = e.originalEvent.touches[0].clientY;
                const pullDistance = pullMoveY - pullStartY;
                
                if (pullDistance > 0 && pullDistance < pullThreshold * 2) {
                    $refreshIndicator.css({
                        'transform': `translateY(${pullDistance}px)`,
                        'opacity': pullDistance / pullThreshold
                    });
                    
                    if (pullDistance > pullThreshold) {
                        $refreshIndicator.addClass('mt-pull-ready');
                    } else {
                        $refreshIndicator.removeClass('mt-pull-ready');
                    }
                }
            }
        });
        
        $(document).on('touchend', function() {
            if (pullStartY > 0) {
                const pullDistance = pullMoveY - pullStartY;
                
                if (pullDistance > pullThreshold) {
                    // Trigger refresh
                    $refreshIndicator.addClass('mt-refreshing');
                    location.reload();
                } else {
                    // Reset
                    $refreshIndicator.css({
                        'transform': 'translateY(0)',
                        'opacity': '0'
                    }).removeClass('mt-pull-ready');
                }
                
                pullStartY = 0;
                pullMoveY = 0;
            }
        });
    }
    
    // Enhance mobile filter experience
    function enhanceMobileFilters() {
        const $filters = $('.mt-search-filters');
        
        if ($filters.length === 0) return;
        
        // Add filter toggle button for mobile
        if ($(window).width() < 768) {
            const $toggleBtn = $('<button class="mt-filter-toggle">' +
                '<span class="dashicons dashicons-filter"></span> ' +
                mt_frontend.i18n.filter_candidates +
                '</button>');
            
            $filters.before($toggleBtn);
            $filters.addClass('mt-filters-collapsed');
            
            $toggleBtn.on('click', function() {
                $filters.toggleClass('mt-filters-collapsed mt-filters-expanded');
                $(this).toggleClass('active');
                
                // Update button text
                if ($filters.hasClass('mt-filters-expanded')) {
                    $(this).html('<span class="dashicons dashicons-no-alt"></span> ' + 
                        mt_frontend.i18n.hide_filters);
                } else {
                    $(this).html('<span class="dashicons dashicons-filter"></span> ' + 
                        mt_frontend.i18n.filter_candidates);
                }
            });
        }
        
        // Add clear filters button
        const $clearBtn = $('<button class="mt-clear-filters">' + 
            mt_frontend.i18n.clear_filters + 
            '</button>');
        
        $('.mt-search-box').append($clearBtn);
        
        $clearBtn.on('click', function() {
            $('#mt-candidate-search').val('');
            $('#mt-status-filter').val('');
            $('#mt-category-filter').val('all');
            
            // Trigger filter update
            $('#mt-candidate-search').trigger('input');
        });
    }
    
    // Fix iOS input zoom issue
    function fixIOSInputZoom() {
        const $inputs = $('input[type="text"], input[type="number"], select, textarea');
        
        $inputs.each(function() {
            const $input = $(this);
            const fontSize = parseInt($input.css('font-size'));
            
            if (fontSize < 16) {
                $input.css('font-size', '16px');
            }
        });
    }
    
    // Lazy loading for images
    function initLazyLoading() {
        const images = document.querySelectorAll('img[data-src]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            images.forEach(function(img) {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for older browsers
            images.forEach(function(img) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
            });
        }
    }
    
    // Smooth scroll for anchor links
    function initSmoothScroll() {
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            
            if (target.length) {
                e.preventDefault();
                
                $('html, body').animate({
                    scrollTop: target.offset().top - 60
                }, 500);
            }
        });
    }
    
    // Debounce function for performance
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
    
    // Optimize scroll performance
    function optimizeScrollPerformance() {
        let ticking = false;
        
        function updateScrollPosition() {
            // Add/remove classes based on scroll position
            if ($(window).scrollTop() > 100) {
                $('.mt-dashboard-header').addClass('mt-header-compact');
            } else {
                $('.mt-dashboard-header').removeClass('mt-header-compact');
            }
            
            ticking = false;
        }
        
        function requestTick() {
            if (!ticking) {
                window.requestAnimationFrame(updateScrollPosition);
                ticking = true;
            }
        }
        
        $(window).on('scroll', requestTick);
    }
    
    // Handle orientation change
    function handleOrientationChange() {
        // Recalculate viewport height
        setViewportHeight();
        
        // Re-initialize table scroll indicators
        optimizeTableScroll();
        
        // Adjust filter visibility
        if ($(window).width() >= 768) {
            $('.mt-search-filters').removeClass('mt-filters-collapsed mt-filters-expanded');
            $('.mt-filter-toggle').hide();
        } else {
            $('.mt-filter-toggle').show();
        }
    }
    
    // Performance monitoring
    function monitorPerformance() {
        if ('performance' in window && 'measure' in window.performance) {
            // Measure interaction timing
            $('.mt-evaluate-btn').on('click', function() {
                performance.mark('evaluate-btn-click');
            });
            
            // Log performance metrics
            window.addEventListener('load', function() {
                const perfData = window.performance.timing;
                const loadTime = perfData.loadEventEnd - perfData.navigationStart;
                console.log('Page load time:', loadTime + 'ms');
            });
        }
    }
    
    // Initialize everything when DOM is ready
    $(document).ready(function() {
        console.log('MT Mobile Dashboard - Initializing');
        
        // Initialize mobile features
        initMobileFeatures();
        
        // Optimize scroll performance
        optimizeScrollPerformance();
        
        // Handle orientation changes
        $(window).on('orientationchange', debounce(handleOrientationChange, 250));
        
        // Monitor performance
        monitorPerformance();
        
        // Add loaded class for animations
        setTimeout(function() {
            $('.mt-jury-dashboard').addClass('mt-loaded');
        }, 100);
        
        console.log('MT Mobile Dashboard - Initialized');
    });
    
})(jQuery);