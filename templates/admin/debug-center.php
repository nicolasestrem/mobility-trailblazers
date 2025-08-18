<?php
/**
 * Debug Center - Unified Interface
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
}

// Initialize services
$debug_manager = new \MobilityTrailblazers\Admin\MT_Debug_Manager();
$diagnostic_service = \MobilityTrailblazers\Services\MT_Diagnostic_Service::get_instance();
$maintenance_tools = new \MobilityTrailblazers\Admin\MT_Maintenance_Tools();

// Get active tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'diagnostics';

// Get environment
$environment = $debug_manager->get_environment();
$is_production = $debug_manager->is_production();
?>

<div class="wrap mt-debug-center">
    <h1>
        <?php _e('Developer Tools', 'mobility-trailblazers'); ?>
        <span class="environment-badge <?php echo esc_attr($environment); ?>">
            <?php echo esc_html(ucfirst($environment)); ?>
        </span>
    </h1>
    
    <?php if ($is_production): ?>
    <div class="notice notice-warning">
        <p>
            <strong><?php _e('⚠️ Production Environment', 'mobility-trailblazers'); ?></strong><br>
            <?php _e('Debug features are limited and dangerous operations require additional confirmation.', 'mobility-trailblazers'); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <nav class="nav-tab-wrapper">
        <a href="?page=mt-debug-center&tab=diagnostics" 
           class="nav-tab <?php echo $active_tab === 'diagnostics' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-dashboard"></span>
            <?php _e('System Diagnostics', 'mobility-trailblazers'); ?>
        </a>
        
        <a href="?page=mt-debug-center&tab=database" 
           class="nav-tab <?php echo $active_tab === 'database' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-database"></span>
            <?php _e('Database Tools', 'mobility-trailblazers'); ?>
        </a>
        
        <a href="?page=mt-debug-center&tab=scripts" 
           class="nav-tab <?php echo $active_tab === 'scripts' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-editor-code"></span>
            <?php _e('Debug Scripts', 'mobility-trailblazers'); ?>
        </a>
        
        <a href="?page=mt-debug-center&tab=tools" 
           class="nav-tab <?php echo $active_tab === 'tools' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('Maintenance Tools', 'mobility-trailblazers'); ?>
        </a>
        
        <a href="?page=mt-debug-center&tab=info" 
           class="nav-tab <?php echo $active_tab === 'info' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-info"></span>
            <?php _e('System Info', 'mobility-trailblazers'); ?>
        </a>
    </nav>
    
    <div class="tab-content">
        <?php
        // Include appropriate tab template
        $tab_file = MT_PLUGIN_DIR . 'templates/admin/debug-center/tab-' . $active_tab . '.php';
        
        if (file_exists($tab_file)) {
            include $tab_file;
        } else {
            // Fallback for missing tab
            echo '<div class="notice notice-error"><p>';
            printf(
                __('Tab template not found: %s', 'mobility-trailblazers'),
                esc_html($active_tab)
            );
            echo '</p></div>';
        }
        ?>
    </div>
    
    <div class="mt-debug-footer">
        <p class="description">
            <?php 
            printf(
                __('Plugin Version: %s | Database Version: %s | PHP: %s | WordPress: %s', 'mobility-trailblazers'),
                MT_VERSION,
                get_option('mt_db_version', 'Not set'),
                PHP_VERSION,
                get_bloginfo('version')
            );
            ?>
        </p>
    </div>
</div>

<style>
/* Basic inline styles - will be moved to separate CSS file */
.mt-debug-center .environment-badge {
    display: inline-block;
    padding: 3px 8px;
    margin-left: 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: normal;
    text-transform: uppercase;
}

.mt-debug-center .environment-badge.development {
    background: #4caf50;
    color: white;
}

.mt-debug-center .environment-badge.staging {
    background: #ff9800;
    color: white;
}

.mt-debug-center .environment-badge.production {
    background: #f44336;
    color: white;
}

.mt-debug-center .nav-tab .dashicons {
    margin-right: 5px;
    vertical-align: text-bottom;
}

.mt-debug-center .tab-content {
    background: white;
    padding: 20px;
    margin-top: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-debug-footer {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.button-danger {
    background: #dc3232 !important;
    border-color: #ba2020 !important;
    color: #fff !important;
    box-shadow: 0 1px 0 #ba2020 !important;
    text-shadow: 0 -1px 1px #ba2020, 1px 0 1px #ba2020, 0 1px 1px #ba2020, -1px 0 1px #ba2020 !important;
}

.button-danger:hover,
.button-danger:focus {
    background: #ba2020 !important;
    border-color: #a01515 !important;
    color: #fff !important;
}

.button-danger:active {
    background: #a01515 !important;
    border-color: #a01515 !important;
    box-shadow: inset 0 2px 0 #a01515 !important;
}
</style>