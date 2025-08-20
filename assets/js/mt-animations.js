/**
 * Mobility Trailblazers Animation Controller
 * Version 2.5.29
 * 
 * @package MobilityTrailblazers
 * @since 2.5.29
 */
(function($) {
    'use strict';
    // Animation Controller Object
    const MTAnimations = {
        // Configuration
        config: {
            scrollOffset: 100,
            parallaxSpeed: 0.5,
            observerThreshold: 0.1,
            animationClasses: [
                'mtFadeIn', 'mtFadeInUp', 'mtFadeInDown', 'mtFadeInLeft', 'mtFadeInRight',
                'mtZoomIn', 'mtZoomInBounce', 'mtRotateIn', 'mtFlipInX', 'mtFlipInY'
            ]
        },
        // Initialize all animations
        init: function() {
            this.initScrollReveal();
            this.initParallax();
            this.initRippleEffect();
            this.initImageEffects();
            this.initStaggerAnimations();
            this.initDynamicAnimations();
            this.initPerformanceMonitor();
        },
        // Scroll-triggered reveal animations
        initScrollReveal: function() {
            const reveals = document.querySelectorAll('.mt-scroll-reveal');
            if (reveals.length === 0) return;
            const observerOptions = {
                threshold: this.config.observerThreshold,
                rootMargin: '0px 0px -50px 0px'
            };
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('mt-in-view');
                        // Trigger custom event
                        $(entry.target).trigger('mt:revealed');
                        // Optional: stop observing after reveal
                        if (entry.target.dataset.revealOnce === 'true') {
                            observer.unobserve(entry.target);
                        }
                    } else if (!entry.target.dataset.revealOnce) {
                        entry.target.classList.remove('mt-in-view');
                    }
                });
            }, observerOptions);
            reveals.forEach(element => observer.observe(element));
        },
        // Parallax scrolling effects
        initParallax: function() {
            const parallaxElements = document.querySelectorAll('.mt-scroll-parallax');
            if (parallaxElements.length === 0) return;
            let ticking = false;
            const updateParallax = () => {
                const scrolled = window.pageYOffset;
                parallaxElements.forEach(element => {
                    const speed = element.dataset.parallaxSpeed || this.config.parallaxSpeed;
                    const yPos = -(scrolled * speed);
                    element.style.setProperty('--parallax-offset', `${yPos}px`);
                });
                ticking = false;
            };
            const requestTick = () => {
                if (!ticking) {
                    requestAnimationFrame(updateParallax);
                    ticking = true;
                }
            };
            window.addEventListener('scroll', requestTick);
        },
        // Ripple effect for buttons
        initRippleEffect: function() {
            $(document).on('mousedown', '.mt-ripple', function(e) {
                const $this = $(this);
                const offset = $this.offset();
                const x = e.pageX - offset.left;
                const y = e.pageY - offset.top;
                $this.css({
                    '--ripple-x': x + 'px',
                    '--ripple-y': y + 'px'
                });
            });
        },
        // Image hover effects
        initImageEffects: function() {
            // Add dynamic image loading animation
            $('.mt-image-zoom img, .mt-image-blur-to-focus img, .mt-image-grayscale img').each(function() {
                const img = $(this);
                if (img[0].complete) {
                    img.addClass('loaded');
                } else {
                    img.on('load', function() {
                        $(this).addClass('loaded');
                    });
                }
            });
        },
        // Stagger animations for lists
        initStaggerAnimations: function() {
            $('.mt-stagger-children').each(function() {
                const $container = $(this);
                const children = $container.children();
                // Add stagger delay dynamically
                children.each(function(index) {
                    $(this).css('animation-delay', (index * 0.05) + 's');
                });
            });
        },
        // Dynamic animation assignment
        initDynamicAnimations: function() {
            // Random entrance animations
            $('.mt-random-entrance').each(function() {
                const animations = MTAnimations.config.animationClasses;
                const randomAnim = animations[Math.floor(Math.random() * animations.length)];
                $(this).addClass(randomAnim);
            });
            // Chain animations
            $('.mt-chain-animation').each(function() {
                const $element = $(this);
                const animations = ($element.data('animations') || 'mtFadeIn,mtPulse').split(',');
                let currentIndex = 0;
                const playNext = () => {
                    if (currentIndex >= animations.length) {
                        currentIndex = 0;
                    }
                    $element.removeClass(animations.join(' '));
                    $element.addClass(animations[currentIndex]);
                    currentIndex++;
                };
                playNext();
                setInterval(playNext, 2000);
            });
        },
        // Performance monitoring
        initPerformanceMonitor: function() {
            if (!window.performance || !window.performance.now) return;
            const animatedElements = document.querySelectorAll('[class*="mt-animate"], [class*="mt-anim"]');
            if (animatedElements.length > 50) {
            }
            // Monitor animation performance
            animatedElements.forEach(element => {
                element.addEventListener('animationstart', (e) => {
                    element.dataset.animStart = performance.now();
                });
                element.addEventListener('animationend', (e) => {
                    if (element.dataset.animStart) {
                        const duration = performance.now() - parseFloat(element.dataset.animStart);
                        if (duration > 1000) {
                        }
                        delete element.dataset.animStart;
                    }
                });
            });
        },
        // Public API Methods
        // Trigger animation on element
        animate: function(element, animationClass, options = {}) {
            const $element = $(element);
            const defaults = {
                duration: 'normal',
                delay: 0,
                callback: null
            };
            const settings = $.extend({}, defaults, options);
            // Remove any existing animation classes
            this.config.animationClasses.forEach(cls => {
                $element.removeClass(cls);
            });
            // Apply animation speed
            $element.addClass(`mt-anim-${settings.duration}`);
            // Apply delay if specified
            if (settings.delay > 0) {
                $element.css('animation-delay', settings.delay + 's');
            }
            // Add animation class
            $element.addClass(animationClass);
            // Handle callback
            if (typeof settings.callback === 'function') {
                $element.one('animationend', settings.callback);
            }
            return $element;
        },
        // Stop animation on element
        stopAnimation: function(element) {
            const $element = $(element);
            this.config.animationClasses.forEach(cls => {
                $element.removeClass(cls);
            });
            $element.css('animation', 'none');
            return $element;
        },
        // Check if element is animating
        isAnimating: function(element) {
            const $element = $(element);
            return this.config.animationClasses.some(cls => {
                return $element.hasClass(cls);
            });
        },
        // Replay animation
        replay: function(element) {
            const $element = $(element);
            const currentClasses = [];
            // Store current animation classes
            this.config.animationClasses.forEach(cls => {
                if ($element.hasClass(cls)) {
                    currentClasses.push(cls);
                }
            });
            // Remove and re-add to trigger replay
            $element.removeClass(currentClasses.join(' '));
            setTimeout(() => {
                $element.addClass(currentClasses.join(' '));
            }, 10);
            return $element;
        }
    };
    // jQuery Plugin
    $.fn.mtAnimate = function(method, ...args) {
        if (typeof method === 'string' && typeof MTAnimations[method] === 'function') {
            return MTAnimations[method](this, ...args);
        } else if (typeof method === 'string' && MTAnimations.config.animationClasses.includes(method)) {
            return MTAnimations.animate(this, method, args[0]);
        } else {
            // Error logging removed for production
            return this;
        }
    };
    // Initialize on document ready
    $(document).ready(function() {
        // Check if animations are enabled
        if ($('body').hasClass('mt-animations-enabled')) {
            MTAnimations.init();
            // Expose to global scope for debugging
            window.MTAnimations = MTAnimations;
        }
    });
    // Re-init on AJAX complete (for dynamically loaded content)
    $(document).ajaxComplete(function(event, xhr, settings) {
        if ($('body').hasClass('mt-animations-enabled')) {
            setTimeout(() => {
                MTAnimations.initScrollReveal();
                MTAnimations.initStaggerAnimations();
                MTAnimations.initDynamicAnimations();
            }, 100);
        }
    });
})(jQuery);
