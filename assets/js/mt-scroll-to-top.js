/**
 * Mobility Trailblazers - Scroll to Top Button
 * Professional scroll-to-top functionality with accessibility support
 */

(function() {
    'use strict';

    /**
     * Scroll to Top Button Class
     */
    class MTScrollToTop {
        constructor() {
            this.button = null;
            this.isVisible = false;
            this.scrollThreshold = 300;
            this.debounceDelay = 10;
            this.scrollTimer = null;
            
            this.init();
        }

        /**
         * Initialize the scroll to top button
         */
        init() {
            // Wait for DOM to be ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => this.createButton());
            } else {
                this.createButton();
            }
        }

        /**
         * Create and inject the scroll button
         */
        createButton() {
            // Remove any existing buttons first
            this.removeExistingButtons();
            
            // Create the button element
            this.button = document.createElement('button');
            this.button.id = 'mt-scroll-to-top';
            this.button.type = 'button';
            this.button.className = 'mt-scroll-button';
            this.button.setAttribute('aria-label', 'Scroll to top of page');
            this.button.setAttribute('title', 'Scroll to top');
            
            // FORCE STYLING WITH INLINE CSS TO BYPASS ALL THEME CONFLICTS
            this.button.style.cssText = `
                position: fixed !important;
                bottom: 20px !important;
                right: 20px !important;
                width: 72px !important;
                height: 67px !important;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                color: white !important;
                border: none !important;
                border-radius: 12px !important;
                cursor: pointer !important;
                z-index: 2147483647 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                opacity: 0 !important;
                visibility: hidden !important;
                transform: translateY(20px) scale(0.8) !important;
                transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94) !important;
                box-shadow: 0 8px 24px rgba(102, 126, 234, 0.25) !important;
                user-select: none !important;
                margin: 0 !important;
                padding: 0 !important;
                float: none !important;
                clear: none !important;
                top: auto !important;
                left: auto !important;
                transform-origin: center !important;
                box-sizing: border-box !important;
                outline: none !important;
                overflow: visible !important;
                text-align: center !important;
                vertical-align: baseline !important;
            `;
            
            // Add the chevron icon and screen reader text
            this.button.innerHTML = `
                <svg class="chevron-icon" viewBox="0 0 24 24" aria-hidden="true" style="width: 28px !important; height: 28px !important; fill: currentColor !important;">
                    <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/>
                </svg>
                <span class="mt-sr-only" style="position: absolute !important; width: 1px !important; height: 1px !important; padding: 0 !important; margin: -1px !important; overflow: hidden !important; clip: rect(0, 0, 0, 0) !important; white-space: nowrap !important; border: 0 !important;">Scroll to top of page</span>
            `;
            
            // Add directly to HTML element to bypass ALL containers
            document.documentElement.appendChild(this.button);
            
            // Bind events
            this.bindEvents();
            
            // Initial visibility check
            this.updateVisibility();
        }

        /**
         * Remove any existing scroll-to-top buttons
         */
        removeExistingButtons() {
            // Remove Happy Addons buttons
            const happyButtons = document.querySelectorAll(
                '.ha-scroll-to-top-wrap, .ha-scroll-to-top-button, #mt-scroll-to-top'
            );
            happyButtons.forEach(btn => btn.remove());
        }

        /**
         * Bind event listeners
         */
        bindEvents() {
            if (!this.button) return;

            // Scroll event with debouncing
            window.addEventListener('scroll', this.debounce(() => {
                this.updateVisibility();
            }, this.debounceDelay));

            // Click event for scrolling to top
            this.button.addEventListener('click', (e) => {
                e.preventDefault();
                this.scrollToTop();
            });

            // Keyboard support
            this.button.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.scrollToTop();
                }
            });

            // Handle resize events
            window.addEventListener('resize', this.debounce(() => {
                this.updateVisibility();
            }, 100));
        }

        /**
         * Update button visibility based on scroll position
         */
        updateVisibility() {
            if (!this.button) return;

            const shouldShow = window.scrollY > this.scrollThreshold;
            
            if (shouldShow && !this.isVisible) {
                this.showButton();
            } else if (!shouldShow && this.isVisible) {
                this.hideButton();
            }
        }

        /**
         * Show the scroll button
         */
        showButton() {
            if (!this.button) return;
            
            // Use inline styles for guaranteed visibility
            this.button.style.opacity = '1';
            this.button.style.visibility = 'visible';
            this.button.style.transform = 'translateY(0) scale(1)';
            this.button.classList.add('show');
            this.button.setAttribute('aria-hidden', 'false');
            this.isVisible = true;
        }

        /**
         * Hide the scroll button
         */
        hideButton() {
            if (!this.button) return;
            
            // Use inline styles for guaranteed hiding
            this.button.style.opacity = '0';
            this.button.style.visibility = 'hidden';
            this.button.style.transform = 'translateY(20px) scale(0.8)';
            this.button.classList.remove('show');
            this.button.setAttribute('aria-hidden', 'true');
            this.isVisible = false;
        }

        /**
         * Smooth scroll to top of page
         */
        scrollToTop() {
            // Check for smooth scroll support
            if ('scrollBehavior' in document.documentElement.style) {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {
                // Fallback for older browsers
                this.smoothScrollPolyfill();
            }
            
            // Focus management for accessibility
            setTimeout(() => {
                const skipLink = document.querySelector('#skip-to-content') || 
                               document.querySelector('a[href="#content"]') ||
                               document.querySelector('main') ||
                               document.querySelector('#main');
                               
                if (skipLink) {
                    skipLink.focus();
                }
            }, 500);
        }

        /**
         * Smooth scroll polyfill for older browsers
         */
        smoothScrollPolyfill() {
            const startPosition = window.scrollY;
            const distance = startPosition;
            const duration = 500;
            let start = null;

            const step = (timestamp) => {
                if (!start) start = timestamp;
                const progress = timestamp - start;
                const percentage = Math.min(progress / duration, 1);
                
                // Easing function
                const ease = this.easeInOutCubic(percentage);
                
                window.scrollTo(0, startPosition - (distance * ease));
                
                if (progress < duration) {
                    window.requestAnimationFrame(step);
                }
            };

            window.requestAnimationFrame(step);
        }

        /**
         * Easing function for smooth animation
         */
        easeInOutCubic(t) {
            return t < 0.5 ? 4 * t * t * t : (t - 1) * (2 * t - 2) * (2 * t - 2) + 1;
        }

        /**
         * Debounce function to limit event firing
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
         * Destroy the scroll button and clean up events
         */
        destroy() {
            if (this.button) {
                this.button.remove();
                this.button = null;
            }
            this.isVisible = false;
            
            // Clean up any timers
            if (this.scrollTimer) {
                clearTimeout(this.scrollTimer);
            }
        }
    }

    /**
     * Initialize when DOM is ready
     */
    let mtScrollToTop = null;

    function initScrollToTop() {
        // Only initialize once
        if (mtScrollToTop) {
            mtScrollToTop.destroy();
        }
        
        mtScrollToTop = new MTScrollToTop();
    }

    // Initialize immediately if DOM is already loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScrollToTop);
    } else {
        initScrollToTop();
    }

    // Re-initialize on Elementor frontend changes (if needed)
    if (typeof elementorFrontend !== 'undefined') {
        elementorFrontend.hooks.addAction('frontend/element_ready/global', initScrollToTop);
    }

    // Make available globally for debugging
    window.MTScrollToTop = MTScrollToTop;

})();