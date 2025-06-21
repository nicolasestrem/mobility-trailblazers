<?php
/**
 * Debug Jury Dashboard Shortcode
 * 
 * This script analyzes the jury dashboard shortcode functionality
 * to identify why it's not displaying anything.
 */

echo "<h1>Jury Dashboard Shortcode Analysis</h1>\n";

// Check if we're in the right directory
echo "<h2>1. File Structure Check</h2>\n";
$plugin_dir = __DIR__;
echo "Current directory: " . $plugin_dir . "\n";

// Check main plugin file
$main_file = $plugin_dir . '/mobility-trailblazers.php';
if (file_exists($main_file)) {
    echo "✅ Main plugin file exists: mobility-trailblazers.php\n";
} else {
    echo "❌ Main plugin file missing: mobility-trailblazers.php\n";
}

// Check shortcode class file
$shortcode_file = $plugin_dir . '/includes/class-mt-shortcodes.php';
if (file_exists($shortcode_file)) {
    echo "✅ Shortcode class file exists: includes/class-mt-shortcodes.php\n";
} else {
    echo "❌ Shortcode class file missing: includes/class-mt-shortcodes.php\n";
}

// Check template file
$template_file = $plugin_dir . '/templates/shortcodes/jury-dashboard.php';
if (file_exists($template_file)) {
    echo "✅ Template file exists: templates/shortcodes/jury-dashboard.php\n";
} else {
    echo "❌ Template file missing: templates/shortcodes/jury-dashboard.php\n";
}

// Check utility functions
$utility_file = $plugin_dir . '/includes/mt-utility-functions.php';
if (file_exists($utility_file)) {
    echo "✅ Utility functions file exists: includes/mt-utility-functions.php\n";
} else {
    echo "❌ Utility functions file missing: includes/mt-utility-functions.php\n";
}

// Analyze shortcode class
echo "<h2>2. Shortcode Class Analysis</h2>\n";
if (file_exists($shortcode_file)) {
    $shortcode_content = file_get_contents($shortcode_file);
    
    // Check if jury_dashboard method exists
    if (strpos($shortcode_content, 'public function jury_dashboard') !== false) {
        echo "✅ jury_dashboard method found in shortcode class\n";
    } else {
        echo "❌ jury_dashboard method not found in shortcode class\n";
    }
    
    // Check for authentication checks
    if (strpos($shortcode_content, 'is_user_logged_in') !== false) {
        echo "✅ User authentication check found\n";
    } else {
        echo "❌ User authentication check missing\n";
    }
    
    if (strpos($shortcode_content, 'mt_is_jury_member') !== false) {
        echo "✅ Jury member check found\n";
    } else {
        echo "❌ Jury member check missing\n";
    }
    
    // Check for script enqueuing
    if (strpos($shortcode_content, 'enqueue_jury_dashboard_scripts') !== false) {
        echo "✅ Script enqueuing found\n";
    } else {
        echo "❌ Script enqueuing missing\n";
    }
    
    // Check for template inclusion
    if (strpos($shortcode_content, 'include MT_PLUGIN_DIR') !== false) {
        echo "✅ Template inclusion found\n";
    } else {
        echo "❌ Template inclusion missing\n";
    }
} else {
    echo "❌ Cannot analyze shortcode class - file not found\n";
}

// Analyze template file
echo "<h2>3. Template File Analysis</h2>\n";
if (file_exists($template_file)) {
    $template_content = file_get_contents($template_file);
    
    // Check for basic structure
    if (strpos($template_content, '<div class="mt-jury-dashboard">') !== false) {
        echo "✅ Main dashboard container found\n";
    } else {
        echo "❌ Main dashboard container missing\n";
    }
    
    if (strpos($template_content, 'candidates-grid') !== false) {
        echo "✅ Candidates grid found\n";
    } else {
        echo "❌ Candidates grid missing\n";
    }
    
    if (strpos($template_content, 'evaluation-modal') !== false) {
        echo "✅ Evaluation modal found\n";
    } else {
        echo "❌ Evaluation modal missing\n";
    }
    
    // Check for JavaScript dependencies
    if (strpos($template_content, 'candidates-loading') !== false) {
        echo "✅ Loading state found\n";
    } else {
        echo "❌ Loading state missing\n";
    }
} else {
    echo "❌ Cannot analyze template file - file not found\n";
}

// Check for potential issues
echo "<h2>4. Potential Issues Analysis</h2>\n";

// Check if MT_PLUGIN_DIR constant is defined
if (file_exists($main_file)) {
    $main_content = file_get_contents($main_file);
    if (strpos($main_content, 'define(\'MT_PLUGIN_DIR\'') !== false) {
        echo "✅ MT_PLUGIN_DIR constant definition found\n";
    } else {
        echo "❌ MT_PLUGIN_DIR constant definition missing\n";
    }
}

// Check for autoloader
$autoloader_file = $plugin_dir . '/includes/class-mt-autoloader.php';
if (file_exists($autoloader_file)) {
    echo "✅ Autoloader file exists\n";
} else {
    echo "❌ Autoloader file missing\n";
}

// Check for CSS/JS files
$css_file = $plugin_dir . '/assets/jury-dashboard.css';
$js_file = $plugin_dir . '/assets/jury-dashboard.js';

if (file_exists($css_file)) {
    echo "✅ Jury dashboard CSS exists\n";
} else {
    echo "❌ Jury dashboard CSS missing\n";
}

if (file_exists($js_file)) {
    echo "✅ Jury dashboard JS exists\n";
} else {
    echo "❌ Jury dashboard JS missing\n";
}

// Check for AJAX handlers
$ajax_file = $plugin_dir . '/includes/class-mt-ajax-handlers.php';
if (file_exists($ajax_file)) {
    echo "✅ AJAX handlers file exists\n";
} else {
    echo "❌ AJAX handlers file missing\n";
}

echo "<h2>5. Common Issues That Cause Empty Shortcode Output</h2>\n";
echo "<ul>\n";
echo "<li><strong>User not logged in:</strong> Shortcode returns login message</li>\n";
echo "<li><strong>User not a jury member:</strong> Shortcode returns permission error</li>\n";
echo "<li><strong>No jury member post:</strong> Shortcode returns profile not found error</li>\n";
echo "<li><strong>No assigned candidates:</strong> Template shows empty grid</li>\n";
echo "<li><strong>JavaScript errors:</strong> Prevents dynamic content loading</li>\n";
echo "<li><strong>CSS/JS not loading:</strong> Styling and functionality broken</li>\n";
echo "<li><strong>AJAX failures:</strong> Candidates not loading dynamically</li>\n";
echo "<li><strong>Template file missing:</strong> No output generated</li>\n";
echo "<li><strong>PHP fatal errors:</strong> Prevents shortcode execution</li>\n";
echo "<li><strong>Output buffering issues:</strong> Content not captured properly</li>\n";
echo "</ul>\n";

echo "<h2>6. Debugging Steps</h2>\n";
echo "<ol>\n";
echo "<li>Check if user is logged in and has jury member role</li>\n";
echo "<li>Verify jury member post exists and is published</li>\n";
echo "<li>Check if candidates are assigned to the jury member</li>\n";
echo "<li>Inspect browser console for JavaScript errors</li>\n";
echo "<li>Check if CSS/JS files are loading properly</li>\n";
echo "<li>Test AJAX endpoints directly</li>\n";
echo "<li>Enable WordPress debug logging</li>\n";
echo "<li>Check for PHP fatal errors in error log</li>\n";
echo "</ol>\n";

echo "<h2>7. Quick Test Commands</h2>\n";
echo "<p>Run these commands to test specific functionality:</p>\n";
echo "<ul>\n";
echo "<li><code>php create-test-assignments.php</code> - Create test data</li>\n";
echo "<li><code>php debug-jury-dashboard.php</code> - Test jury dashboard</li>\n";
echo "<li><code>php test-jury-ajax.php</code> - Test AJAX functionality</li>\n";
echo "</ul>\n";
?> 