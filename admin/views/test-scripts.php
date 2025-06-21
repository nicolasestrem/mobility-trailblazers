<?php
/**
 * Test Scripts Page Template
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('Test Scripts', 'mobility-trailblazers'); ?></h1>
    
    <div class="notice notice-info">
        <p><strong><?php _e('Development Tools', 'mobility-trailblazers'); ?></strong></p>
        <p><?php _e('These test scripts are available for debugging and development purposes. They are only accessible when WP_DEBUG is enabled.', 'mobility-trailblazers'); ?></p>
    </div>
    
    <div class="mt-test-scripts-container">
        <div class="mt-test-script-card">
            <h2><?php _e('Jury Dashboard Test', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Comprehensive test script to debug jury dashboard functionality, check assignments, and test AJAX handlers.', 'mobility-trailblazers'); ?></p>
            <div class="mt-test-script-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mt-test-scripts&action=run_jury_dashboard_test')); ?>" class="button button-primary">
                    <?php _e('Run Jury Dashboard Test', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo esc_url(plugin_dir_url(MT_PLUGIN_FILE) . 'debug-jury-dashboard.php'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('View Raw Script', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
        
        <div class="mt-test-script-card">
            <h2><?php _e('Jury AJAX Test', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Test script to verify AJAX functionality for jury dashboard data loading and error handling.', 'mobility-trailblazers'); ?></p>
            <div class="mt-test-script-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mt-test-scripts&action=run_jury_ajax_test')); ?>" class="button button-primary">
                    <?php _e('Run Jury AJAX Test', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo esc_url(plugin_dir_url(MT_PLUGIN_FILE) . 'test-jury-ajax.php'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('View Raw Script', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
        
        <div class="mt-test-script-card">
            <h2><?php _e('Assignment Test', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Test script to check assignment functionality and create test assignments if needed.', 'mobility-trailblazers'); ?></p>
            <div class="mt-test-script-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mt-test-scripts&action=run_assignment_test')); ?>" class="button button-primary">
                    <?php _e('Run Assignment Test', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo esc_url(plugin_dir_url(MT_PLUGIN_FILE) . 'debug-jury-dashboard.php'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('View Raw Script', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
        
        <div class="mt-test-script-card">
            <h2><?php _e('Fix Jury Dashboard', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Comprehensive fix script to diagnose and resolve jury dashboard issues.', 'mobility-trailblazers'); ?></p>
            <div class="mt-test-script-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mt-test-scripts&action=run_fix_script')); ?>" class="button button-primary">
                    <?php _e('Run Fix Script', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo esc_url(plugin_dir_url(MT_PLUGIN_FILE) . 'fix-jury-dashboard.php'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('View Raw Script', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
        
        <div class="mt-test-script-card">
            <h2><?php _e('Elementor Compatibility Debug', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Debug script to check Elementor compatibility and identify JavaScript conflicts.', 'mobility-trailblazers'); ?></p>
            <div class="mt-test-script-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mt-test-scripts&action=run_elementor_debug')); ?>" class="button button-primary">
                    <?php _e('Run Elementor Debug', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo esc_url(plugin_dir_url(MT_PLUGIN_FILE) . 'debug-elementor.php'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('View Raw Script', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
        
        <div class="mt-test-script-card">
            <h2><?php _e('Fix Elementor Database', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Fix script to resolve Elementor database initialization issues and JavaScript errors.', 'mobility-trailblazers'); ?></p>
            <div class="mt-test-script-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mt-test-scripts&action=run_elementor_db_fix')); ?>" class="button button-primary">
                    <?php _e('Run Elementor DB Fix', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo esc_url(plugin_dir_url(MT_PLUGIN_FILE) . 'fix-elementor-database.php'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('View Raw Script', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
        
        <div class="mt-test-script-card">
            <h2><?php _e('Verify MU Plugin', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Verification script to check if the Elementor REST fix mu-plugin is properly installed and working.', 'mobility-trailblazers'); ?></p>
            <div class="mt-test-script-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mt-test-scripts&action=run_mu_plugin_verify')); ?>" class="button button-primary">
                    <?php _e('Verify MU Plugin', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo esc_url(plugin_dir_url(MT_PLUGIN_FILE) . 'verify-mu-plugin.php'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('View Raw Script', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
        
        <div class="mt-test-script-card">
            <h2><?php _e('Fix Elementor Webpack', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Comprehensive fix for Elementor webpack module loading issues and JavaScript errors.', 'mobility-trailblazers'); ?></p>
            <div class="mt-test-script-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=mt-test-scripts&action=run_elementor_webpack_fix')); ?>" class="button button-primary">
                    <?php _e('Fix Elementor Webpack', 'mobility-trailblazers'); ?>
                </a>
                <a href="<?php echo esc_url(plugin_dir_url(MT_PLUGIN_FILE) . 'fix-elementor-webpack.php'); ?>" class="button button-secondary" target="_blank">
                    <?php _e('View Raw Script', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <?php
    // Handle script execution
    if (isset($_GET['action'])) {
        $action = sanitize_text_field($_GET['action']);
        
        echo '<div class="mt-test-results">';
        echo '<h2>' . __('Test Results', 'mobility-trailblazers') . '</h2>';
        echo '<div class="mt-test-output">';
        
        switch ($action) {
            case 'run_jury_dashboard_test':
                echo '<h3>' . __('Jury Dashboard Test Results', 'mobility-trailblazers') . '</h3>';
                if (file_exists(MT_PLUGIN_DIR . 'debug-jury-dashboard.php')) {
                    ob_start();
                    include MT_PLUGIN_DIR . 'debug-jury-dashboard.php';
                    $output = ob_get_clean();
                    echo '<pre>' . esc_html($output) . '</pre>';
                } else {
                    echo '<p class="error">' . __('Test script file not found.', 'mobility-trailblazers') . '</p>';
                }
                break;
                
            case 'run_jury_ajax_test':
                echo '<h3>' . __('Jury AJAX Test Results', 'mobility-trailblazers') . '</h3>';
                if (file_exists(MT_PLUGIN_DIR . 'test-jury-ajax.php')) {
                    ob_start();
                    include MT_PLUGIN_DIR . 'test-jury-ajax.php';
                    $output = ob_get_clean();
                    echo '<pre>' . esc_html($output) . '</pre>';
                } else {
                    echo '<p class="error">' . __('Test script file not found.', 'mobility-trailblazers') . '</p>';
                }
                break;
                
            case 'run_assignment_test':
                echo '<h3>' . __('Assignment Test Results', 'mobility-trailblazers') . '</h3>';
                if (file_exists(MT_PLUGIN_DIR . 'debug-jury-dashboard.php')) {
                    ob_start();
                    include MT_PLUGIN_DIR . 'debug-jury-dashboard.php';
                    $output = ob_get_clean();
                    echo '<pre>' . esc_html($output) . '</pre>';
                } else {
                    echo '<p class="error">' . __('Test script file not found.', 'mobility-trailblazers') . '</p>';
                }
                break;
                
            case 'run_fix_script':
                echo '<h3>' . __('Fix Script Results', 'mobility-trailblazers') . '</h3>';
                if (file_exists(MT_PLUGIN_DIR . 'fix-jury-dashboard.php')) {
                    ob_start();
                    include MT_PLUGIN_DIR . 'fix-jury-dashboard.php';
                    $output = ob_get_clean();
                    echo '<pre>' . esc_html($output) . '</pre>';
                } else {
                    echo '<p class="error">' . __('Fix script file not found.', 'mobility-trailblazers') . '</p>';
                }
                break;
                
            case 'run_elementor_debug':
                echo '<h3>' . __('Elementor Compatibility Debug Results', 'mobility-trailblazers') . '</h3>';
                if (file_exists(MT_PLUGIN_DIR . 'debug-elementor.php')) {
                    ob_start();
                    include MT_PLUGIN_DIR . 'debug-elementor.php';
                    $output = ob_get_clean();
                    echo '<pre>' . esc_html($output) . '</pre>';
                } else {
                    echo '<p class="error">' . __('Elementor debug script file not found.', 'mobility-trailblazers') . '</p>';
                }
                break;
                
            case 'run_elementor_db_fix':
                echo '<h3>' . __('Elementor Database Fix Results', 'mobility-trailblazers') . '</h3>';
                if (file_exists(MT_PLUGIN_DIR . 'fix-elementor-database.php')) {
                    ob_start();
                    include MT_PLUGIN_DIR . 'fix-elementor-database.php';
                    $output = ob_get_clean();
                    echo '<pre>' . esc_html($output) . '</pre>';
                } else {
                    echo '<p class="error">' . __('Elementor database fix script file not found.', 'mobility-trailblazers') . '</p>';
                }
                break;
                
            case 'run_mu_plugin_verify':
                echo '<h3>' . __('MU Plugin Verification Results', 'mobility-trailblazers') . '</h3>';
                if (file_exists(MT_PLUGIN_DIR . 'verify-mu-plugin.php')) {
                    ob_start();
                    include MT_PLUGIN_DIR . 'verify-mu-plugin.php';
                    $output = ob_get_clean();
                    echo '<pre>' . esc_html($output) . '</pre>';
                } else {
                    echo '<p class="error">' . __('MU plugin verification script file not found.', 'mobility-trailblazers') . '</p>';
                }
                break;
                
            case 'run_elementor_webpack_fix':
                echo '<h3>' . __('Elementor Webpack Fix Results', 'mobility-trailblazers') . '</h3>';
                if (file_exists(MT_PLUGIN_DIR . 'fix-elementor-webpack.php')) {
                    ob_start();
                    include MT_PLUGIN_DIR . 'fix-elementor-webpack.php';
                    $output = ob_get_clean();
                    echo '<pre>' . esc_html($output) . '</pre>';
                } else {
                    echo '<p class="error">' . __('Elementor webpack fix script file not found.', 'mobility-trailblazers') . '</p>';
                }
                break;
                
            default:
                echo '<p class="error">' . __('Invalid test action.', 'mobility-trailblazers') . '</p>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    ?>
</div>

<style>
.mt-test-scripts-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.mt-test-script-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-test-script-card h2 {
    margin-top: 0;
    color: #23282d;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.mt-test-script-card p {
    color: #666;
    margin-bottom: 15px;
}

.mt-test-script-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.mt-test-results {
    margin-top: 30px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.mt-test-output {
    background: #f1f1f1;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    max-height: 500px;
    overflow-y: auto;
}

.mt-test-output pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.4;
}

.error {
    color: #dc3232;
    font-weight: bold;
}
</style> 