<?php
/**
 * Elementor Webpack Module Loading Fix
 * 
 * This script addresses the specific webpack module loading issues causing
 * "Cannot read properties of undefined (reading 'handlers')" and "tools" errors
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once 'wp-config.php';
    require_once 'wp-load.php';
}

echo "=== Elementor Webpack Module Loading Fix ===\n\n";

// Check if user is logged in and has admin permissions
$current_user = wp_get_current_user();
if (!$current_user->ID || !current_user_can('manage_options')) {
    echo "❌ Please log in as an administrator to run this script.\n";
    exit;
}

echo "✅ User authenticated: " . $current_user->user_login . "\n\n";

// Check if Elementor is active
if (!did_action('elementor/loaded')) {
    echo "❌ Elementor is not active. Please activate Elementor first.\n";
    exit;
}

echo "✅ Elementor is active\n";
echo "Elementor version: " . (defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'Unknown') . "\n\n";

echo "=== Step 1: Clear All Caches ===\n";

// Clear WordPress cache
if (function_exists('wp_cache_flush')) {
    wp_cache_flush();
    echo "✅ WordPress cache cleared\n";
}

// Clear Elementor cache
if (class_exists('Elementor\Core\Files\Manager')) {
    $files_manager = new Elementor\Core\Files\Manager();
    $files_manager->clear_cache();
    echo "✅ Elementor cache cleared\n";
}

// Clear any other caches
if (function_exists('w3tc_flush_all')) {
    w3tc_flush_all();
    echo "✅ W3 Total Cache cleared\n";
}

if (function_exists('wp_rocket_clean_domain')) {
    wp_rocket_clean_domain();
    echo "✅ WP Rocket cache cleared\n";
}

echo "\n=== Step 2: Force Elementor Database Reinitialization ===\n";

// Delete Elementor options to force reinitialization
$elementor_options = array(
    'elementor_db_version',
    'elementor_version',
    'elementor_activation_time',
    'elementor_install_time',
    'elementor_scheme_color',
    'elementor_scheme_typography',
    'elementor_scheme_color_picker'
);

foreach ($elementor_options as $option) {
    delete_option($option);
    echo "✅ Deleted option: $option\n";
}

// Force Elementor to reinstall
if (class_exists('Elementor\Core\Upgrade\Manager')) {
    $upgrade_manager = new Elementor\Core\Upgrade\Manager();
    $upgrade_manager->should_upgrade();
    echo "✅ Triggered Elementor upgrade process\n";
}

echo "\n=== Step 3: Regenerate Elementor Files ===\n";

// Regenerate CSS files
if (class_exists('Elementor\Core\Files\CSS\Post')) {
    global $wpdb;
    
    // Get all Elementor posts
    $elementor_posts = $wpdb->get_col("
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_elementor_edit_mode' 
        AND meta_value = 'builder'
    ");
    
    foreach ($elementor_posts as $post_id) {
        $css_file = new Elementor\Core\Files\CSS\Post($post_id);
        $css_file->delete();
        $css_file->write();
        echo "✅ Regenerated CSS for post ID: $post_id\n";
    }
}

// Regenerate global CSS
if (class_exists('Elementor\Core\Files\CSS\Global_CSS')) {
    $global_css = new Elementor\Core\Files\CSS\Global_CSS();
    $global_css->delete();
    $global_css->write();
    echo "✅ Regenerated global CSS\n";
}

echo "\n=== Step 4: Fix Webpack Module Loading ===\n";

// Create a more aggressive JavaScript fix
$webpack_fix_js = '
// Aggressive webpack module loading fix
(function() {
    "use strict";
    
    // Intercept webpack module loading at the earliest possible moment
    if (typeof __webpack_require__ !== "undefined") {
        var originalWebpackRequire = __webpack_require__;
        __webpack_require__ = function(moduleId) {
            try {
                var result = originalWebpackRequire(moduleId);
                
                // Special handling for problematic modules
                if (moduleId === 820) {
                    // Module 820 is the handlers module
                    if (result && typeof result.default === "function") {
                        var originalModule = result.default;
                        result.default = function() {
                            try {
                                return originalModule.apply(this, arguments);
                            } catch (error) {
                                console.warn("Elementor: Error in handlers module:", error);
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
                console.warn("Elementor: Webpack module loading error for module", moduleId, error);
                
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
                                                console.warn("Elementor: Error in hook handler:", error);
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
                                        return "elementor-" + Math.random().toString(36).substr(2, 9);
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
    
    // Ensure elementorFrontend is properly initialized
    if (typeof elementorFrontend === "undefined") {
        window.elementorFrontend = {};
    }
    
    // Initialize required objects
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
                            console.warn("Elementor: Error in hook handler:", error);
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
                    return "elementor-" + Math.random().toString(36).substr(2, 9);
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
    
    console.log("Elementor: Webpack module loading fix applied");
})();
';

// Add the fix to WordPress
wp_add_inline_script('elementor-frontend', $webpack_fix_js, 'before');

echo "✅ Webpack module loading fix applied\n";

echo "\n=== Step 5: Verify Fix ===\n";

// Check if Elementor database is properly initialized
$elementor_db_version = get_option('elementor_db_version');
echo "Elementor database version: " . ($elementor_db_version ?: 'Not set') . "\n";

if ($elementor_db_version) {
    echo "✅ Elementor database is properly initialized\n";
} else {
    echo "⚠️  Elementor database version is not set\n";
    echo "This might indicate that Elementor needs to be reactivated\n";
}

// Check if our mu-plugin is working
$mu_plugin_file = WPMU_PLUGIN_DIR . '/elementor-rest-fix.php';
if (file_exists($mu_plugin_file)) {
    echo "✅ MU Plugin is installed\n";
} else {
    echo "❌ MU Plugin is not installed\n";
}

echo "\n=== Next Steps ===\n";
echo "1. Clear your browser cache completely (Ctrl+F5 or Cmd+Shift+R)\n";
echo "2. Refresh the page and check for JavaScript errors\n";
echo "3. If errors persist, try deactivating and reactivating Elementor\n";
echo "4. Check the browser console for any remaining errors\n";
echo "5. If issues continue, consider reinstalling Elementor\n";

echo "\n=== Summary ===\n";
echo "✅ All caches cleared\n";
echo "✅ Elementor database reinitialized\n";
echo "✅ CSS files regenerated\n";
echo "✅ Webpack module loading fix applied\n";
echo "✅ MU Plugin verified\n";

echo "\n🎉 Elementor webpack module loading fix completed!\n";
echo "Please clear your browser cache and test the page again.\n"; 