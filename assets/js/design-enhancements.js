/**
 * Design Enhancements JavaScript
 * Version: 1.0.0
 * Purpose: Add interactive animations and enhancements to the platform
 */
jQuery(document).ready(function($) {
    'use strict';
    // ========================================
    // Smooth scroll for internal links
    // ========================================
    $('a[href^="#"]').on('click', function(e) {
        var target = $(this.hash);
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 100
            }, 500, 'swing');
        }
    });
    // ========================================
    // Add animation to cards on scroll
    // ========================================
    function animateOnScroll() {
        $('.mt-criterion-card, .mt-sidebar-card, .mt-overview-section').each(function() {
            var elementTop = $(this).offset().top;
            var elementBottom = elementTop + $(this).outerHeight();
            var viewportTop = $(window).scrollTop();
            var viewportBottom = viewportTop + $(window).height();
            if (elementBottom > viewportTop && elementTop < viewportBottom - 100) {
                $(this).addClass('animate-in');
            }
        });
    }
    // Trigger animation on scroll and initial load
    $(window).on('scroll', animateOnScroll);
    animateOnScroll(); // Initial check
    // ========================================
    // Enhance photo frames with hover effect
    // ========================================
    $('.mt-photo-frame').on('mouseenter', function() {
        $(this).addClass('hover-effect');
    }).on('mouseleave', function() {
        $(this).removeClass('hover-effect');
    });
    // ========================================
    // Parallax effect for hero section
    // ========================================
    if ($('.mt-hero-section').length) {
        $(window).on('scroll', function() {
            var scrollTop = $(window).scrollTop();
            var parallaxSpeed = 0.5;
            $('.mt-hero-pattern').css({
                'transform': 'translateY(' + (scrollTop * parallaxSpeed) + 'px)'
            });
        });
    }
    // ========================================
    // Enhance social links with ripple effect
    // ========================================
    $('.mt-social-link').on('click', function(e) {
        var $this = $(this);
        var ripple = $('<span class="ripple"></span>');
        $this.append(ripple);
        var x = e.pageX - $this.offset().left;
        var y = e.pageY - $this.offset().top;
        ripple.css({
            left: x + 'px',
            top: y + 'px'
        });
        setTimeout(function() {
            ripple.remove();
        }, 600);
    });
    // ========================================
    // Sticky sidebar enhancement
    // ========================================
    if ($('.mt-sidebar').length && $(window).width() > 968) {
        var sidebar = $('.mt-sidebar');
        var sidebarTop = sidebar.offset().top;
        var footerTop = $('footer').length ? $('footer').offset().top : $(document).height();
        $(window).on('scroll', function() {
            var scrollTop = $(window).scrollTop();
            var sidebarHeight = sidebar.outerHeight();
            var windowHeight = $(window).height();
            if (scrollTop > sidebarTop - 100) {
                if (scrollTop + sidebarHeight + 100 < footerTop) {
                    sidebar.css({
                        'position': 'fixed',
                        'top': '100px',
                        'width': sidebar.parent().width()
                    });
                } else {
                    sidebar.css({
                        'position': 'absolute',
                        'top': (footerTop - sidebarHeight - sidebarTop) + 'px',
                        'width': sidebar.parent().width()
                    });
                }
            } else {
                sidebar.css({
                    'position': 'static',
                    'width': 'auto'
                });
            }
        });
    }
    // ========================================
    // Image lazy loading enhancement
    // ========================================
    if ('IntersectionObserver' in window) {
        var imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                }
            });
        });
        $('img[data-src]').each(function() {
            imageObserver.observe(this);
        });
    }
    // ========================================
    // Tooltip enhancement for icons
    // ========================================
    // Check if tooltip function exists before using it
    if ($.fn.tooltip) {
        $('.mt-criterion-icon').each(function() {
            var $this = $(this);
            var title = $this.siblings('.mt-criterion-title').text();
            $this.attr('title', title).tooltip({
                position: { my: 'center bottom-10', at: 'center top' },
                classes: { 'ui-tooltip': 'mt-tooltip' }
            });
        });
    } else {
        // Fallback: just set title attribute for native browser tooltips
        $('.mt-criterion-icon').each(function() {
            var $this = $(this);
            var title = $this.siblings('.mt-criterion-title').text();
            $this.attr('title', title);
        });
    }
    // ========================================
    // Auto-save indicator for evaluation form
    // ========================================
    if ($('.mt-evaluation-form').length) {
        var saveIndicator = $('<div class="mt-save-indicator"><span class="dashicons dashicons-yes"></span> Saved</div>');
        $('body').append(saveIndicator);
        // Show save indicator when AJAX save completes
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (settings.url && settings.url.includes('mt_save_evaluation')) {
                saveIndicator.addClass('show');
                setTimeout(function() {
                    saveIndicator.removeClass('show');
                }, 2000);
            }
        });
    }
    // ========================================
    // Responsive table enhancement
    // ========================================
    $('.mt-data-table').each(function() {
        var $table = $(this);
        if (!$table.parent().hasClass('mt-table-wrapper')) {
            $table.wrap('<div class="mt-table-wrapper"></div>');
        }
    });
    // ========================================
    // Print optimization
    // ========================================
    window.addEventListener('beforeprint', function() {
        // Expand all collapsed sections
        $('.mt-collapsible.collapsed').removeClass('collapsed');
        // Remove animations
        $('*').css('animation', 'none');
        $('*').css('transition', 'none');
    });
    window.addEventListener('afterprint', function() {
        // Restore collapsed state
        $('.mt-collapsible[data-was-collapsed]').addClass('collapsed');
    });
    // ========================================
    // Accessibility enhancements
    // ========================================
    // Skip to content link
    if (!$('#skip-to-content').length) {
        $('body').prepend('<a href="#main-content" id="skip-to-content" class="sr-only">Skip to content</a>');
    }
    // Keyboard navigation for sliders
    $('.mt-score-slider').on('keydown', function(e) {
        var $this = $(this);
        var value = parseFloat($this.val());
        if (e.key === 'ArrowUp' || e.key === 'ArrowRight') {
            e.preventDefault();
            $this.val(Math.min(10, value + 0.5)).trigger('change');
        } else if (e.key === 'ArrowDown' || e.key === 'ArrowLeft') {
            e.preventDefault();
            $this.val(Math.max(0, value - 0.5)).trigger('change');
        }
    });
    // Focus management for modals
    $('.mt-modal').on('shown', function() {
        $(this).find(':focusable:first').focus();
    });
    // ========================================
    // Performance monitoring
    // ========================================
    if (window.performance && window.performance.timing) {
        window.addEventListener('load', function() {
            var timing = window.performance.timing;
            var loadTime = timing.loadEventEnd - timing.navigationStart;
            if (loadTime > 3000) {
                // Page load time exceeds 3 seconds - optimization may be needed
            }
        });
    }
});
// ========================================
// Add CSS for new elements
// ========================================
(function() {
    var style = document.createElement('style');
    style.textContent = `
        /* Save Indicator */
        .mt-save-indicator {
            position: fixed;
            top: 20px;
            right: -200px;
            background: #4caf50;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: right 0.3s ease;
            z-index: 9999;
        }
        .mt-save-indicator.show {
            right: 20px;
        }
        /* Ripple Effect */
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            transform: scale(0);
            animation: ripple-animation 0.6s ease-out;
            pointer-events: none;
        }
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        /* Skip to Content */
        #skip-to-content {
            position: absolute;
            left: -9999px;
            z-index: 999;
        }
        #skip-to-content:focus {
            position: fixed;
            top: 0;
            left: 0;
            background: #1f2937;
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            z-index: 10000;
        }
        /* Table Wrapper */
        .mt-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        /* Tooltip Styles */
        .mt-tooltip {
            background: #1f2937;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
    `;
    document.head.appendChild(style);
})();
