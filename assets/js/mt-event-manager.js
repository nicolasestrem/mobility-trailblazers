/**
 * Mobility Trailblazers Event Manager
 * Centralized event handling with proper cleanup to prevent memory leaks
 * 
 * @since 2.5.34
 */

(function($) {
    'use strict';

    /**
     * Event Manager for Mobility Trailblazers
     * Handles all event bindings with proper namespacing and cleanup
     */
    window.MTEventManager = {
        namespace: 'mt',
        events: [],
        
        /**
         * Initialize event manager
         */
        init: function() {
            this.setupCleanup();
            this.trackEvents();
        },
        
        /**
         * Register an event with automatic cleanup
         * @param {string} eventType - Event type (click, change, etc)
         * @param {string} selector - jQuery selector
         * @param {function} handler - Event handler function
         * @param {string} context - Optional context (admin, frontend, etc)
         */
        on: function(eventType, selector, handler, context = 'global') {
            const namespacedEvent = eventType + '.' + this.namespace + '_' + context;
            
            // Remove any existing handler for this event/selector combo
            $(document).off(namespacedEvent, selector);
            
            // Add new handler
            $(document).on(namespacedEvent, selector, handler);
            
            // Track for cleanup
            this.events.push({
                event: namespacedEvent,
                selector: selector,
                context: context
            });
        },
        
        /**
         * Remove events by context
         * @param {string} context - Context to clean up
         */
        off: function(context = 'global') {
            this.events = this.events.filter(function(item) {
                if (item.context === context) {
                    $(document).off(item.event, item.selector);
                    return false;
                }
                return true;
            });
        },
        
        /**
         * Remove all MT events
         */
        offAll: function() {
            // Remove all namespaced events
            $(document).off('.' + this.namespace);
            $(document).off('.' + this.namespace + '_admin');
            $(document).off('.' + this.namespace + '_frontend');
            $(document).off('.' + this.namespace + '_global');
            this.events = [];
        },
        
        /**
         * Setup automatic cleanup on page unload
         */
        setupCleanup: function() {
            const self = this;
            
            // Clean up on page unload
            $(window).on('beforeunload.' + this.namespace, function() {
                self.offAll();
            });
            
            // Clean up on WordPress admin page change (if applicable)
            if (typeof wp !== 'undefined' && wp.heartbeat) {
                $(document).on('heartbeat-tick.' + this.namespace, function() {
                    // Check if we're still on the same page
                    if (self.pageChanged()) {
                        self.offAll();
                    }
                });
            }
        },
        
        /**
         * Track memory usage for debugging
         */
        trackEvents: function() {
            if (window.MT_DEBUG) {
                setInterval(function() {
                    console.log('MT Events registered:', MTEventManager.events.length);
                    if (performance && performance.memory) {
                        console.log('Memory used:', Math.round(performance.memory.usedJSHeapSize / 1048576) + 'MB');
                    }
                }, 30000); // Log every 30 seconds
            }
        },
        
        /**
         * Check if page has changed (for SPA-like behavior)
         */
        pageChanged: function() {
            const currentUrl = window.location.href;
            if (this.lastUrl && this.lastUrl !== currentUrl) {
                this.lastUrl = currentUrl;
                return true;
            }
            this.lastUrl = currentUrl;
            return false;
        },
        
        /**
         * Delegate handler with automatic cleanup
         * @param {jQuery} $container - Container element
         * @param {string} eventType - Event type
         * @param {string} selector - Child selector
         * @param {function} handler - Event handler
         */
        delegate: function($container, eventType, selector, handler) {
            const namespace = this.namespace + '_delegate_' + Date.now();
            $container.off(eventType + '.' + namespace, selector);
            $container.on(eventType + '.' + namespace, selector, handler);
        },
        
        /**
         * One-time event with automatic cleanup
         * @param {string} eventType - Event type
         * @param {string} selector - jQuery selector
         * @param {function} handler - Event handler function
         */
        once: function(eventType, selector, handler) {
            const self = this;
            const namespacedEvent = eventType + '.' + this.namespace + '_once';
            
            $(document).one(namespacedEvent, selector, function(e) {
                handler.call(this, e);
                // Remove from tracking after execution
                self.events = self.events.filter(function(item) {
                    return item.event !== namespacedEvent;
                });
            });
            
            this.events.push({
                event: namespacedEvent,
                selector: selector,
                context: 'once'
            });
        },
        
        /**
         * Throttled event handler
         * @param {string} eventType - Event type
         * @param {string} selector - jQuery selector
         * @param {function} handler - Event handler function
         * @param {number} delay - Throttle delay in ms
         */
        throttle: function(eventType, selector, handler, delay = 250) {
            let throttleTimer;
            this.on(eventType, selector, function(e) {
                if (!throttleTimer) {
                    handler.call(this, e);
                    throttleTimer = setTimeout(function() {
                        throttleTimer = null;
                    }, delay);
                }
            });
        },
        
        /**
         * Debounced event handler
         * @param {string} eventType - Event type
         * @param {string} selector - jQuery selector
         * @param {function} handler - Event handler function
         * @param {number} delay - Debounce delay in ms
         */
        debounce: function(eventType, selector, handler, delay = 250) {
            let debounceTimer;
            this.on(eventType, selector, function(e) {
                clearTimeout(debounceTimer);
                const context = this;
                const args = arguments;
                debounceTimer = setTimeout(function() {
                    handler.apply(context, args);
                }, delay);
            });
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        MTEventManager.init();
    });
    
})(jQuery);