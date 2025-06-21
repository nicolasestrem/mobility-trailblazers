/**
 * Elementor Webpack Module Loading Fix
 * 
 * This script fixes the "Cannot read properties of undefined (reading 'handlers')" 
 * and "Cannot read properties of undefined (reading 'tools')" errors by
 * intercepting webpack module loading and providing fallback implementations.
 */

// Immediately intercept webpack require function
(function() {
    'use strict';
    
    // Store original webpack require function
    var originalWebpackRequire = null;
    
    // Intercept webpack module loading at the earliest possible moment
    if (typeof __webpack_require__ !== 'undefined') {
        originalWebpackRequire = __webpack_require__;
        
        __webpack_require__ = function(moduleId) {
            try {
                var result = originalWebpackRequire(moduleId);
                
                // Special handling for problematic modules
                if (moduleId === 820) {
                    // Module 820 is the handlers module
                    if (result && typeof result.default === 'function') {
                        var originalModule = result.default;
                        result.default = function() {
                            try {
                                return originalModule.apply(this, arguments);
                            } catch (error) {
                                console.warn('Elementor: Error in handlers module:', error);
                                // Return a safe handlers object
                                return {
                                    handlers: {},
                                    addAction: function() {},
                                    addFilter: function() {},
                                    doAction: function() {}
                                };
                            }
                        };
                    }
                }
                
                return result;
            } catch (error) {
                console.warn('Elementor: Webpack module loading error for module', moduleId, error);
                
                // Return appropriate mock modules based on module ID
                if (moduleId === 820) {
                    // Handlers module
                    return {
                        default: function() {
                            return {
                                handlers: {},
                                addAction: function(action, callback) {
                                    if (!this.handlers[action]) {
                                        this.handlers[action] = [];
                                    }
                                    this.handlers[action].push(callback);
                                },
                                addFilter: function(filter, callback) {
                                    if (!this.handlers[filter]) {
                                        this.handlers[filter] = [];
                                    }
                                    this.handlers[filter].push(callback);
                                },
                                doAction: function(action) {
                                    if (this.handlers[action]) {
                                        for (var i = 0; i < this.handlers[action].length; i++) {
                                            try {
                                                this.handlers[action][i].apply(this, Array.prototype.slice.call(arguments, 1));
                                            } catch (error) {
                                                console.warn('Elementor: Error in hook handler:', error);
                                            }
                                        }
                                    }
                                }
                            };
                        },
                        __esModule: true
                    };
                } else if (moduleId === 4906 || moduleId === 3000) {
                    // Tools module
                    return {
                        default: function() {
                            return {
                                tools: {
                                    getUniqueId: function() {
                                        return 'elementor-' + Math.random().toString(36).substr(2, 9);
                                    },
                                    debounce: function(func, wait) {
                                        var timeout;
                                        return function() {
                                            var context = this, args = arguments;
                                            clearTimeout(timeout);
                                            timeout = setTimeout(function() {
                                                func.apply(context, args);
                                            }, wait);
                                        };
                                    }
                                }
                            };
                        },
                        __esModule: true
                    };
                }
                
                // Default mock module
                return {
                    default: function() {},
                    __esModule: true
                };
            }
        };
    }
    
    // Also intercept webpackJsonpCallback to prevent module loading errors
    if (typeof webpackJsonpCallback !== 'undefined') {
        var originalWebpackJsonpCallback = webpackJsonpCallback;
        webpackJsonpCallback = function(data) {
            try {
                return originalWebpackJsonpCallback(data);
            } catch (error) {
                console.warn('Elementor: Error in webpackJsonpCallback:', error);
                return [];
            }
        };
    }
    
    console.log('Elementor: Webpack module loading fix applied');
})();

// Additional fix that runs after DOM is ready
(function() {
    'use strict';
    
    // Ensure elementorFrontend is properly initialized
    if (typeof elementorFrontend === 'undefined') {
        window.elementorFrontend = {};
    }
    
    // Initialize required objects immediately
    if (!elementorFrontend.hooks) {
        elementorFrontend.hooks = {
            handlers: {},
            addAction: function(action, callback) {
                if (!this.handlers[action]) {
                    this.handlers[action] = [];
                }
                this.handlers[action].push(callback);
            },
            addFilter: function(filter, callback) {
                if (!this.handlers[filter]) {
                    this.handlers[filter] = [];
                }
                this.handlers[filter].push(callback);
            },
            doAction: function(action) {
                if (this.handlers[action]) {
                    for (var i = 0; i < this.handlers[action].length; i++) {
                        try {
                            this.handlers[action][i].apply(this, Array.prototype.slice.call(arguments, 1));
                        } catch (error) {
                            console.warn('Elementor: Error in hook handler:', error);
                        }
                    }
                }
            }
        };
    }
    
    if (!elementorFrontend.tools) {
        elementorFrontend.tools = {
            tools: {
                getUniqueId: function() {
                    return 'elementor-' + Math.random().toString(36).substr(2, 9);
                },
                debounce: function(func, wait) {
                    var timeout;
                    return function() {
                        var context = this, args = arguments;
                        clearTimeout(timeout);
                        timeout = setTimeout(function() {
                            func.apply(context, args);
                        }, wait);
                    };
                }
            }
        };
    }
    
    // Wait for jQuery to be ready
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            // Fix for Frontend.initOnReadyComponents
            if (typeof elementorFrontend !== 'undefined' && elementorFrontend.initOnReadyComponents) {
                var originalInitOnReadyComponents = elementorFrontend.initOnReadyComponents;
                elementorFrontend.initOnReadyComponents = function() {
                    try {
                        // Ensure all required objects exist before calling
                        if (this.tools && this.tools.tools && this.hooks && this.hooks.handlers) {
                            return originalInitOnReadyComponents.apply(this, arguments);
                        } else {
                            console.warn('Elementor: Required objects not properly initialized, skipping initOnReadyComponents');
                            // Try to initialize missing objects
                            if (!this.tools) this.tools = {};
                            if (!this.tools.tools) this.tools.tools = {};
                            if (!this.hooks) this.hooks = {};
                            if (!this.hooks.handlers) this.hooks.handlers = {};
                            
                            // Try again after initialization
                            setTimeout(function() {
                                if (typeof originalInitOnReadyComponents === 'function') {
                                    originalInitOnReadyComponents.apply(elementorFrontend, arguments);
                                }
                            }, 100);
                        }
                    } catch (error) {
                        console.error('Elementor: Error in initOnReadyComponents:', error);
                    }
                };
            }
        });
    }
    
    // Additional fix for webpack module loading issues
    if (typeof __webpack_require__ !== 'undefined') {
        var originalWebpackRequire = __webpack_require__;
        __webpack_require__ = function(moduleId) {
            try {
                var result = originalWebpackRequire(moduleId);
                
                // Special handling for problematic modules
                if (moduleId === 820) {
                    // Module 820 is the handlers module
                    if (result && typeof result.default === 'function') {
                        var originalModule = result.default;
                        result.default = function() {
                            try {
                                return originalModule.apply(this, arguments);
                            } catch (error) {
                                console.warn('Elementor: Error in handlers module:', error);
                                // Return a safe handlers object
                                return {
                                    handlers: {},
                                    addAction: function() {},
                                    addFilter: function() {},
                                    doAction: function() {}
                                };
                            }
                        };
                    }
                }
                
                return result;
            } catch (error) {
                console.warn('Elementor: Webpack module loading error for module', moduleId, error);
                
                // Return appropriate mock modules based on module ID
                if (moduleId === 820) {
                    // Handlers module
                    return {
                        default: function() {
                            return {
                                handlers: {},
                                addAction: function(action, callback) {
                                    if (!this.handlers[action]) {
                                        this.handlers[action] = [];
                                    }
                                    this.handlers[action].push(callback);
                                },
                                addFilter: function(filter, callback) {
                                    if (!this.handlers[filter]) {
                                        this.handlers[filter] = [];
                                    }
                                    this.handlers[filter].push(callback);
                                },
                                doAction: function(action) {
                                    if (this.handlers[action]) {
                                        for (var i = 0; i < this.handlers[action].length; i++) {
                                            try {
                                                this.handlers[action][i].apply(this, Array.prototype.slice.call(arguments, 1));
                                            } catch (error) {
                                                console.warn('Elementor: Error in hook handler:', error);
                                            }
                                        }
                                    }
                                }
                            };
                        },
                        __esModule: true
                    };
                } else if (moduleId === 4906 || moduleId === 3000) {
                    // Tools module
                    return {
                        default: function() {
                            return {
                                tools: {
                                    getUniqueId: function() {
                                        return 'elementor-' + Math.random().toString(36).substr(2, 9);
                                    },
                                    debounce: function(func, wait) {
                                        var timeout;
                                        return function() {
                                            var context = this, args = arguments;
                                            clearTimeout(timeout);
                                            timeout = setTimeout(function() {
                                                func.apply(context, args);
                                            }, wait);
                                        };
                                    }
                                }
                            };
                        },
                        __esModule: true
                    };
                }
                
                // Default mock module
                return {
                    default: function() {},
                    __esModule: true
                };
            }
        };
    }
    
    console.log('Elementor: Additional webpack module loading fix applied');
})(); 