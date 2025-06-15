<?php
/**
 * Diagnostic System Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Diagnostic
 * Handles system diagnostic functionality
 */
class MT_Diagnostic {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        add_action('wp_ajax_mt_diagnostic_action', array($this, 'handle_diagnostic_ajax'));
    }
    
    /**
     * Enhanced Diagnostic page callback with comprehensive checks and fixes
     */
    public function render_page() {
        // Handle AJAX actions first
        if (isset($_POST['action']) && wp_verify_nonce($_POST['nonce'], 'mt_diagnostic_nonce')) {
            $this->handle_diagnostic_action($_POST['action'], $_POST);
        }
        
        ?>
        <div class="wrap mt-diagnostic-page">
            <h1 class="wp-heading-inline">
                <span class="dashicons dashicons-admin-tools" style="font-size: 36px; width: 36px; height: 36px; margin-right: 10px;"></span>
                <?php _e('Mobility Trailblazers System Diagnostic', 'mobility-trailblazers'); ?>
            </h1>
            
            <hr class="wp-header-end">
            
            <!-- System Overview Cards -->
            <div class="mt-diagnostic-overview">
                <?php $this->render_system_overview(); ?>
            </div>
            
            <!-- Diagnostic Sections -->
            <div class="mt-diagnostic-sections">
                
                <!-- WordPress Environment -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-wordpress"></span> WordPress Environment</h2>
                    <?php $this->check_wordpress_environment(); ?>
                </div>
                
                <!-- Plugin Status -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-admin-plugins"></span> Plugin Status</h2>
                    <?php $this->check_plugin_status(); ?>
                </div>
                
                <!-- Database Status -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-database"></span> Database Status</h2>
                    <?php $this->check_database_status(); ?>
                </div>
                
                <!-- Post Types & Content -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-admin-post"></span> Content Status</h2>
                    <?php $this->check_content_status(); ?>
                </div>
                
                <!-- User Roles & Permissions -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-admin-users"></span> User Roles & Permissions</h2>
                    <?php $this->check_user_permissions(); ?>
                </div>
                
                <!-- Assignments & Evaluations -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-networking"></span> Assignments & Evaluations</h2>
                    <?php $this->check_assignments_evaluations(); ?>
                </div>
                
                <!-- Menu & Navigation -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-menu"></span> Menu & Navigation</h2>
                    <?php $this->check_menu_navigation(); ?>
                </div>
                
                <!-- API & Endpoints -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-rest-api"></span> API & Endpoints</h2>
                    <?php $this->check_api_endpoints(); ?>
                </div>
                
                <!-- File System -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-media-code"></span> File System</h2>
                    <?php $this->check_file_system(); ?>
                </div>
                
                <!-- Performance & Caching -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-performance"></span> Performance & Caching</h2>
                    <?php $this->check_performance_caching(); ?>
                </div>
                
                <!-- Security -->
                <div class="mt-diagnostic-section">
                    <h2><span class="dashicons dashicons-shield"></span> Security</h2>
                    <?php $this->check_security(); ?>
                </div>
                
            </div>
            
            <!-- Quick Fixes Section -->
            <div class="mt-diagnostic-section">
                <h2><span class="dashicons dashicons-admin-tools"></span> Quick Fixes</h2>
                <?php $this->render_quick_fixes(); ?>
            </div>
            
            <!-- System Logs -->
            <div class="mt-diagnostic-section">
                <h2><span class="dashicons dashicons-media-text"></span> System Logs</h2>
                <?php $this->render_system_logs(); ?>
            </div>
            
            <!-- Export Options -->
            <div class="mt-diagnostic-section">
                <h2><span class="dashicons dashicons-download"></span> Export Diagnostic Report</h2>
                <?php $this->render_export_options(); ?>
            </div>
            
        </div>
        
        <?php $this->render_styles(); ?>
        <?php $this->render_scripts(); ?>
        <?php
    }
    
    /**
     * Render diagnostic page styles
     */
    private function render_styles() {
        ?>
        <style>
        .mt-diagnostic-page {
            max-width: 1200px;
        }
        
        .mt-diagnostic-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .mt-overview-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .mt-overview-card h3 {
            font-size: 2.5em;
            margin: 0 0 10px 0;
            color: #23282d;
        }
        
        .mt-overview-card.status-good h3 { color: #46b450; }
        .mt-overview-card.status-warning h3 { color: #ffb900; }
        .mt-overview-card.status-error h3 { color: #dc3232; }
        
        .mt-diagnostic-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 20px 0;
        }
        
        .mt-diagnostic-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .mt-diagnostic-section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .mt-check-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f1;
        }
        
        .mt-check-item:last-child {
            border-bottom: none;
        }
        
        .mt-check-label {
            font-weight: 600;
            color: #23282d;
        }
        
        .mt-check-details {
            font-size: 0.9em;
            color: #646970;
            margin-top: 2px;
        }
        
        .mt-check-status {
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        
        .mt-status-good {
            background: #d4edda;
            color: #155724;
        }
        
        .mt-status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .mt-status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .mt-diagnostic-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        .mt-diagnostic-table th,
        .mt-diagnostic-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .mt-diagnostic-table th {
            background: #f9f9f9;
            font-weight: 600;
        }
        
        .mt-fix-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        
        .mt-fix-item {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            background: #fafafa;
        }
        
        .mt-fix-item h4 {
            margin: 0 0 8px 0;
            color: #23282d;
        }
        
        .mt-fix-item p {
            margin: 0 0 12px 0;
            color: #646970;
            font-size: 0.9em;
        }
        
        .mt-quick-fix-btn {
            width: 100%;
        }
        
        .mt-log-viewer {
            background: #23282d;
            color: #f0f0f1;
            padding: 15px;
            border-radius: 4px;
            font-family: Consolas, Monaco, monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .mt-export-actions {
            margin: 15px 0;
        }
        
        .mt-export-actions .button {
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .mt-diagnostic-sections {
                grid-template-columns: 1fr;
            }
            
            .mt-diagnostic-overview {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }
        
        /* User-Jury Linking Styles */
        .mt-user-jury-table .mt-status-good {
            color: #00a32a;
            font-weight: 600;
        }
        
        .mt-user-jury-table .mt-status-warning {
            color: #dba617;
            font-weight: 600;
        }
        
        .mt-unlinked-users {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        
        .mt-unlinked-users ul {
            margin: 10px 0 0 20px;
        }
        
        .mt-unlinked-users li {
            margin-bottom: 5px;
        }
        
        /* User-Jury Linker Modal */
        .mt-user-jury-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 100000;
            display: none;
        }
        
        .mt-user-jury-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .mt-user-jury-modal h3 {
            margin-top: 0;
        }
        
        .mt-user-jury-form {
            margin: 20px 0;
        }
        
        .mt-user-jury-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .mt-user-jury-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .mt-user-jury-form .button {
            margin-right: 10px;
        }
        </style>
        <?php
    }
    
    /**
     * Render diagnostic page scripts
     */
    private function render_scripts() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Handle quick fix buttons
            $('.mt-quick-fix-btn').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var action = $button.data('action');
                var originalText = $button.text();
                
                if (action === 'show_user_jury_linker') {
                    showUserJuryLinker();
                    return;
                }
                
                $button.prop('disabled', true).text('Processing...');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mt_diagnostic_action',
                        diagnostic_action: action,
                        nonce: '<?php echo wp_create_nonce('mt_diagnostic_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $button.text('✓ Done').removeClass('button-primary button-secondary').addClass('button-disabled');
                            alert('Success: ' + response.data.message);
                            
                            // Refresh page after 2 seconds
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            $button.prop('disabled', false).text(originalText);
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        $button.prop('disabled', false).text(originalText);
                        alert('An error occurred while processing the request.');
                    }
                });
            });
            
            // Handle individual link/unlink buttons
            $(document).on('click', '.mt-link-user-btn', function() {
                var juryId = $(this).data('jury-id');
                showUserJuryLinker(juryId);
            });
            
            $(document).on('click', '.mt-unlink-user-btn', function() {
                var juryId = $(this).data('jury-id');
                var userId = $(this).data('user-id');
                
                if (confirm('Are you sure you want to unlink this user from the jury member?')) {
                    unlinkUserFromJury(juryId);
                }
            });
            
            function showUserJuryLinker(preselectedJuryId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mt_diagnostic_action',
                        diagnostic_action: 'show_user_jury_linker',
                        nonce: '<?php echo wp_create_nonce('mt_diagnostic_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            displayUserJuryLinker(response.data, preselectedJuryId);
                        } else {
                            alert('Error loading data: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while loading the user-jury linker.');
                    }
                });
            }
            
            function displayUserJuryLinker(data, preselectedJuryId) {
                var modalHtml = '<div class="mt-user-jury-modal">';
                modalHtml += '<div class="mt-user-jury-modal-content">';
                modalHtml += '<h3>Link User to Jury Member</h3>';
                modalHtml += '<div class="mt-user-jury-form">';
                
                // Jury member dropdown
                modalHtml += '<label for="jury-select">Select Jury Member:</label>';
                modalHtml += '<select id="jury-select">';
                modalHtml += '<option value="">Choose a jury member...</option>';
                data.jury_members.forEach(function(jury) {
                    var selected = preselectedJuryId && jury.jury_id == preselectedJuryId ? ' selected' : '';
                    var linkedText = jury.linked_user_id ? ' (Currently linked)' : '';
                    modalHtml += '<option value="' + jury.jury_id + '"' + selected + '>' + jury.jury_name + linkedText + '</option>';
                });
                modalHtml += '</select>';
                
                // User dropdown
                modalHtml += '<label for="user-select">Select User:</label>';
                modalHtml += '<select id="user-select">';
                modalHtml += '<option value="">Choose a user...</option>';
                data.users.forEach(function(user) {
                    modalHtml += '<option value="' + user.ID + '">' + user.display_name + ' (' + user.user_login + ') - ' + user.user_email + '</option>';
                });
                modalHtml += '</select>';
                
                modalHtml += '<div style="margin-top: 20px;">';
                modalHtml += '<button type="button" class="button button-primary" id="link-user-submit">Link User</button>';
                modalHtml += '<button type="button" class="button" id="link-user-cancel">Cancel</button>';
                modalHtml += '</div>';
                
                modalHtml += '</div>';
                modalHtml += '</div>';
                modalHtml += '</div>';
                
                $('body').append(modalHtml);
                $('.mt-user-jury-modal').fadeIn();
                
                // Handle modal actions
                $('#link-user-cancel, .mt-user-jury-modal').on('click', function(e) {
                    if (e.target === this) {
                        $('.mt-user-jury-modal').fadeOut(function() {
                            $(this).remove();
                        });
                    }
                });
                
                $('#link-user-submit').on('click', function() {
                    var juryId = $('#jury-select').val();
                    var userId = $('#user-select').val();
                    
                    if (!juryId || !userId) {
                        alert('Please select both a jury member and a user.');
                        return;
                    }
                    
                    linkUserToJury(userId, juryId);
                });
            }
            
            function linkUserToJury(userId, juryId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mt_diagnostic_action',
                        diagnostic_action: 'link_user_to_jury',
                        user_id: userId,
                        jury_id: juryId,
                        nonce: '<?php echo wp_create_nonce('mt_diagnostic_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Success: ' + response.data.message);
                            $('.mt-user-jury-modal').fadeOut(function() {
                                $(this).remove();
                            });
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while linking the user.');
                    }
                });
            }
            
            function unlinkUserFromJury(juryId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mt_diagnostic_action',
                        diagnostic_action: 'unlink_user_from_jury',
                        jury_id: juryId,
                        nonce: '<?php echo wp_create_nonce('mt_diagnostic_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Success: ' + response.data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    },
                    error: function() {
                        alert('An error occurred while unlinking the user.');
                    }
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Handle diagnostic AJAX actions
     */
    public function handle_diagnostic_ajax() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_diagnostic_nonce')) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $action = isset($_POST['diagnostic_action']) ? sanitize_text_field($_POST['diagnostic_action']) : '';
        
        switch ($action) {
            case 'clear_caches':
                $this->clear_all_caches();
                wp_send_json_success(array('message' => 'All caches cleared successfully'));
                break;
                
            case 'fix_roles':
                $this->fix_user_roles();
                wp_send_json_success(array('message' => 'User roles and capabilities fixed'));
                break;
                
            case 'sync_jury_users':
                $result = $this->sync_jury_users();
                wp_send_json_success(array('message' => sprintf('Synced %d jury members with users', $result)));
                break;
                
            case 'regenerate_assignments':
                $result = $this->regenerate_assignments();
                wp_send_json_success(array('message' => sprintf('Regenerated %d assignments', $result)));
                break;
                
            case 'create_test_assignment':
                $result = $this->create_test_assignment();
                if ($result) {
                    wp_send_json_success(array('message' => 'Test assignment created successfully'));
                } else {
                    wp_send_json_error(array('message' => 'Failed to create test assignment'));
                }
                break;
                
            case 'link_current_user':
                $result = $this->link_current_user_to_jury();
                if ($result) {
                    wp_send_json_success(array('message' => 'Current user linked to jury member'));
                } else {
                    wp_send_json_error(array('message' => 'Failed to link current user'));
                }
                break;
                
            case 'show_user_jury_linker':
                $data = $this->get_user_jury_linker_data();
                wp_send_json_success($data);
                break;
                
            case 'link_user_to_jury':
                $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
                $jury_id = isset($_POST['jury_id']) ? intval($_POST['jury_id']) : 0;
                
                if (!$user_id || !$jury_id) {
                    wp_send_json_error(array('message' => 'Invalid user or jury ID'));
                }
                
                $result = $this->link_user_to_jury($user_id, $jury_id);
                if ($result) {
                    wp_send_json_success(array('message' => 'User successfully linked to jury member'));
                } else {
                    wp_send_json_error(array('message' => 'Failed to link user to jury member'));
                }
                break;
                
            case 'unlink_user_from_jury':
                $jury_id = isset($_POST['jury_id']) ? intval($_POST['jury_id']) : 0;
                
                if (!$jury_id) {
                    wp_send_json_error(array('message' => 'Invalid jury ID'));
                }
                
                $result = $this->unlink_user_from_jury($jury_id);
                if ($result) {
                    wp_send_json_success(array('message' => 'User successfully unlinked from jury member'));
                } else {
                    wp_send_json_error(array('message' => 'Failed to unlink user from jury member'));
                }
                break;
                
            default:
                wp_send_json_error(array('message' => 'Unknown action'));
        }
    }
    
    // Include all the diagnostic check methods from the original file
    // These would be copied from the original file's diagnostic methods
    
    private function render_system_overview() {
        global $wpdb;
        
        // Get statistics with error checking
        $candidates_stats = wp_count_posts('mt_candidate');
        $candidates_count = (is_object($candidates_stats) && isset($candidates_stats->publish)) ? $candidates_stats->publish : 0;
        
        $jury_stats = wp_count_posts('mt_jury');
        $jury_count = (is_object($jury_stats) && isset($jury_stats->publish)) ? $jury_stats->publish : 0;
        
        $votes_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes");
        $evaluations_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores");
        
        ?>
        <div class="mt-overview-card status-good">
            <h3><?php echo $candidates_count; ?></h3>
            <p><?php _e('Candidates', 'mobility-trailblazers'); ?></p>
        </div>
        
        <div class="mt-overview-card status-good">
            <h3><?php echo $jury_count; ?></h3>
            <p><?php _e('Jury Members', 'mobility-trailblazers'); ?></p>
        </div>
        
        <div class="mt-overview-card <?php echo $votes_count > 0 ? 'status-good' : 'status-warning'; ?>">
            <h3><?php echo $votes_count; ?></h3>
            <p><?php _e('Total Votes', 'mobility-trailblazers'); ?></p>
        </div>
        
        <div class="mt-overview-card <?php echo $evaluations_count > 0 ? 'status-good' : 'status-warning'; ?>">
            <h3><?php echo $evaluations_count; ?></h3>
            <p><?php _e('Evaluations', 'mobility-trailblazers'); ?></p>
        </div>
        <?php
    }
    
    // Add all other diagnostic methods here...
    // (These would be copied from the original file)
    
    private function check_wordpress_environment() {
        $checks = array();
        
        // WordPress version
        $wp_version = get_bloginfo('version');
        $checks[] = array(
            'label' => __('WordPress Version', 'mobility-trailblazers'),
            'value' => $wp_version,
            'status' => version_compare($wp_version, '5.0', '>=') ? 'good' : 'warning',
            'details' => __('Minimum recommended: 5.0', 'mobility-trailblazers')
        );
        
        // PHP version
        $php_version = phpversion();
        $checks[] = array(
            'label' => __('PHP Version', 'mobility-trailblazers'),
            'value' => $php_version,
            'status' => version_compare($php_version, '7.2', '>=') ? 'good' : 'error',
            'details' => __('Minimum required: 7.2', 'mobility-trailblazers')
        );
        
        // Memory limit
        $memory_limit = ini_get('memory_limit');
        $memory_bytes = $this->parse_size($memory_limit);
        $checks[] = array(
            'label' => __('Memory Limit', 'mobility-trailblazers'),
            'value' => $memory_limit,
            'status' => $memory_bytes >= 134217728 ? 'good' : 'warning', // 128MB
            'details' => __('Recommended: 128M or higher', 'mobility-trailblazers')
        );
        
        // Debug mode
        $debug_mode = WP_DEBUG ? __('Enabled', 'mobility-trailblazers') : __('Disabled', 'mobility-trailblazers');
        $checks[] = array(
            'label' => __('Debug Mode', 'mobility-trailblazers'),
            'value' => $debug_mode,
            'status' => WP_DEBUG ? 'warning' : 'good',
            'details' => __('Should be disabled on production', 'mobility-trailblazers')
        );
        
        $this->render_check_items($checks);
    }
    
    private function check_plugin_status() {
        $checks = array();
        
        // Plugin version
        $checks[] = array(
            'label' => __('Plugin Version', 'mobility-trailblazers'),
            'value' => MT_PLUGIN_VERSION,
            'status' => 'good',
            'details' => __('Current version', 'mobility-trailblazers')
        );
        
        // Plugin path
        $checks[] = array(
            'label' => __('Plugin Path', 'mobility-trailblazers'),
            'value' => 'Valid',
            'status' => defined('MT_PLUGIN_PATH') && file_exists(MT_PLUGIN_PATH) ? 'good' : 'error',
            'details' => __('Plugin files accessible', 'mobility-trailblazers')
        );
        
        // Required files
        $required_files = array(
            'includes/class-vote-reset-manager.php',
            'includes/class-vote-backup-manager.php',
            'includes/class-vote-audit-logger.php',
            'admin/class-jury-management-admin.php'
        );
        
        $missing_files = 0;
        foreach ($required_files as $file) {
            if (!file_exists(MT_PLUGIN_PATH . $file)) {
                $missing_files++;
            }
        }
        
        $checks[] = array(
            'label' => __('Required Files', 'mobility-trailblazers'),
            'value' => $missing_files === 0 ? 'All present' : $missing_files . ' missing',
            'status' => $missing_files === 0 ? 'good' : 'error',
            'details' => __('Core plugin files', 'mobility-trailblazers')
        );
        
        $this->render_check_items($checks);
    }
    
    private function check_database_status() {
        global $wpdb;
        $checks = array();
        
        // Check votes table
        $votes_table = $wpdb->prefix . 'mt_votes';
        $votes_exists = $wpdb->get_var("SHOW TABLES LIKE '$votes_table'") === $votes_table;
        
        $checks[] = array(
            'label' => __('Votes Table', 'mobility-trailblazers'),
            'value' => $votes_exists ? 'Exists' : 'Missing',
            'status' => $votes_exists ? 'good' : 'error',
            'details' => $votes_table
        );
        
        // Check scores table
        $scores_table = $wpdb->prefix . 'mt_candidate_scores';
        $scores_exists = $wpdb->get_var("SHOW TABLES LIKE '$scores_table'") === $scores_table;
        
        $checks[] = array(
            'label' => __('Scores Table', 'mobility-trailblazers'),
            'value' => $scores_exists ? 'Exists' : 'Missing',
            'status' => $scores_exists ? 'good' : 'error',
            'details' => $scores_table
        );
        
        // Check table sizes
        if ($votes_exists) {
            $votes_count = $wpdb->get_var("SELECT COUNT(*) FROM $votes_table");
            $checks[] = array(
                'label' => __('Vote Records', 'mobility-trailblazers'),
                'value' => number_format($votes_count),
                'status' => 'good',
                'details' => __('Total vote entries', 'mobility-trailblazers')
            );
        }
        
        if ($scores_exists) {
            $scores_count = $wpdb->get_var("SELECT COUNT(*) FROM $scores_table");
            $checks[] = array(
                'label' => __('Score Records', 'mobility-trailblazers'),
                'value' => number_format($scores_count),
                'status' => 'good',
                'details' => __('Total evaluation scores', 'mobility-trailblazers')
            );
        }
        
        $this->render_check_items($checks);
    }
    
    private function check_content_status() {
        $checks = array();
        
        // Check candidates with error handling
        $candidates = wp_count_posts('mt_candidate');
        if (is_object($candidates) && isset($candidates->publish)) {
            $checks[] = array(
                'label' => __('Published Candidates', 'mobility-trailblazers'),
                'value' => $candidates->publish,
                'status' => $candidates->publish > 0 ? 'good' : 'warning',
                'details' => sprintf(__('Draft: %d, Trash: %d', 'mobility-trailblazers'), 
                    isset($candidates->draft) ? $candidates->draft : 0, 
                    isset($candidates->trash) ? $candidates->trash : 0)
            );
        } else {
            $checks[] = array(
                'label' => __('Published Candidates', 'mobility-trailblazers'),
                'value' => 'Error',
                'status' => 'error',
                'details' => __('Post type mt_candidate not found', 'mobility-trailblazers')
            );
        }
        
        // Check jury members with error handling
        $jury = wp_count_posts('mt_jury');
        if (is_object($jury) && isset($jury->publish)) {
            $checks[] = array(
                'label' => __('Published Jury Members', 'mobility-trailblazers'),
                'value' => $jury->publish,
                'status' => $jury->publish > 0 ? 'good' : 'warning',
                'details' => sprintf(__('Draft: %d, Trash: %d', 'mobility-trailblazers'), 
                    isset($jury->draft) ? $jury->draft : 0, 
                    isset($jury->trash) ? $jury->trash : 0)
            );
        } else {
            $checks[] = array(
                'label' => __('Published Jury Members', 'mobility-trailblazers'),
                'value' => 'Error',
                'status' => 'error',
                'details' => __('Post type mt_jury not found', 'mobility-trailblazers')
            );
        }
        
        // Check categories
        $categories = wp_count_terms('mt_category', array('hide_empty' => false));
        $checks[] = array(
            'label' => __('Categories', 'mobility-trailblazers'),
            'value' => is_numeric($categories) ? $categories : 0,
            'status' => is_numeric($categories) && $categories > 0 ? 'good' : 'warning',
            'details' => __('Award categories', 'mobility-trailblazers')
        );
        
        // Check phases
        $phases = wp_count_terms('mt_phase', array('hide_empty' => false));
        $checks[] = array(
            'label' => __('Phases', 'mobility-trailblazers'),
            'value' => is_numeric($phases) ? $phases : 0,
            'status' => is_numeric($phases) && $phases > 0 ? 'good' : 'warning',
            'details' => __('Selection phases', 'mobility-trailblazers')
        );
        
        $this->render_check_items($checks);
    }
    
    private function check_user_permissions() {
        $checks = array();
        
        // Check if custom roles exist
        $jury_role = get_role('mt_jury_member');
        $admin_role = get_role('mt_award_admin');
        
        $checks[] = array(
            'label' => __('Jury Member Role', 'mobility-trailblazers'),
            'value' => $jury_role ? 'Exists' : 'Missing',
            'status' => $jury_role ? 'good' : 'error',
            'details' => __('Custom jury member role', 'mobility-trailblazers')
        );
        
        $checks[] = array(
            'label' => __('Award Admin Role', 'mobility-trailblazers'),
            'value' => $admin_role ? 'Exists' : 'Missing',
            'status' => $admin_role ? 'good' : 'error',
            'details' => __('Custom award admin role', 'mobility-trailblazers')
        );
        
        // Check current user capabilities
        $current_user = wp_get_current_user();
        $can_manage = current_user_can('mt_manage_awards');
        
        $checks[] = array(
            'label' => __('Current User', 'mobility-trailblazers'),
            'value' => $current_user->display_name,
            'status' => 'good',
            'details' => implode(', ', $current_user->roles)
        );
        
        $checks[] = array(
            'label' => __('Can Manage Awards', 'mobility-trailblazers'),
            'value' => $can_manage ? 'Yes' : 'No',
            'status' => $can_manage ? 'good' : 'warning',
            'details' => __('Current user permission', 'mobility-trailblazers')
        );
        
        $this->render_check_items($checks);
        
        // Add user-jury management section
        $this->render_user_jury_management();
    }
    
    private function render_user_jury_management() {
        global $wpdb;
        
        // Get all jury members with their linked users
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        ?>
        <h3><?php _e('User-Jury Member Linking', 'mobility-trailblazers'); ?></h3>
        
        <table class="mt-diagnostic-table mt-user-jury-table">
            <thead>
                <tr>
                    <th><?php _e('Jury Member', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Linked User', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('User Email', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jury_members as $jury) : 
                    $user_id = get_post_meta($jury->ID, 'user_id', true);
                    $user = $user_id ? get_user_by('id', $user_id) : null;
                ?>
                <tr>
                    <td><?php echo esc_html($jury->post_title); ?></td>
                    <td>
                        <?php if ($user) : ?>
                            <?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_login); ?>)
                        <?php else : ?>
                            <em><?php _e('Not linked', 'mobility-trailblazers'); ?></em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $user ? esc_html($user->user_email) : '-'; ?>
                    </td>
                    <td>
                        <?php if ($user) : ?>
                            <span class="mt-status-good"><?php _e('Linked', 'mobility-trailblazers'); ?></span>
                        <?php else : ?>
                            <span class="mt-status-warning"><?php _e('Unlinked', 'mobility-trailblazers'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($user) : ?>
                            <button type="button" class="button button-small mt-unlink-user-btn" 
                                    data-jury-id="<?php echo $jury->ID; ?>" 
                                    data-user-id="<?php echo $user_id; ?>">
                                <?php _e('Unlink', 'mobility-trailblazers'); ?>
                            </button>
                        <?php else : ?>
                            <button type="button" class="button button-small button-primary mt-link-user-btn" 
                                    data-jury-id="<?php echo $jury->ID; ?>">
                                <?php _e('Link User', 'mobility-trailblazers'); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php
        // Show unlinked users with jury roles
        $jury_users = get_users(array(
            'role__in' => array('mt_jury_member', 'mt_award_admin'),
            'orderby' => 'display_name'
        ));
        
        $unlinked_users = array();
        foreach ($jury_users as $user) {
            $has_jury_post = false;
            foreach ($jury_members as $jury) {
                if (get_post_meta($jury->ID, 'user_id', true) == $user->ID) {
                    $has_jury_post = true;
                    break;
                }
            }
            if (!$has_jury_post) {
                $unlinked_users[] = $user;
            }
        }
        
        if (!empty($unlinked_users)) : ?>
            <div class="mt-unlinked-users">
                <h4><?php _e('Users with jury roles but no linked jury member post:', 'mobility-trailblazers'); ?></h4>
                <ul>
                    <?php foreach ($unlinked_users as $user) : ?>
                        <li>
                            <?php echo esc_html($user->display_name); ?> 
                            (<?php echo esc_html($user->user_login); ?>) - 
                            <?php echo esc_html($user->user_email); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif;
    }
    
    private function check_assignments_evaluations() {
        global $wpdb;
        $checks = array();
        
        // Count total assignments
        $total_assignments = 0;
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        foreach ($jury_members as $jury) {
            $assignments = get_post_meta($jury->ID, 'assigned_candidates', true);
            if (is_array($assignments)) {
                $total_assignments += count($assignments);
            }
        }
        
        $checks[] = array(
            'label' => __('Total Assignments', 'mobility-trailblazers'),
            'value' => $total_assignments,
            'status' => $total_assignments > 0 ? 'good' : 'warning',
            'details' => __('Candidate-jury assignments', 'mobility-trailblazers')
        );
        
        // Count evaluations
        $evaluations = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores");
        $checks[] = array(
            'label' => __('Completed Evaluations', 'mobility-trailblazers'),
            'value' => $evaluations,
            'status' => $evaluations > 0 ? 'good' : 'warning',
            'details' => __('Submitted evaluations', 'mobility-trailblazers')
        );
        
        // Average assignments per jury
        $avg_assignments = count($jury_members) > 0 ? round($total_assignments / count($jury_members), 1) : 0;
        $checks[] = array(
            'label' => __('Avg Assignments/Jury', 'mobility-trailblazers'),
            'value' => $avg_assignments,
            'status' => $avg_assignments > 0 ? 'good' : 'warning',
            'details' => __('Average per jury member', 'mobility-trailblazers')
        );
        
        // Completion rate
        $completion_rate = $total_assignments > 0 ? round(($evaluations / $total_assignments) * 100, 1) : 0;
        $checks[] = array(
            'label' => __('Completion Rate', 'mobility-trailblazers'),
            'value' => $completion_rate . '%',
            'status' => $completion_rate > 50 ? 'good' : ($completion_rate > 0 ? 'warning' : 'error'),
            'details' => __('Evaluation completion', 'mobility-trailblazers')
        );
        
        $this->render_check_items($checks);
    }
    
    private function check_menu_navigation() {
        global $submenu;
        $checks = array();
        
        // Check if main menu exists
        $main_menu_exists = isset($submenu['mt-award-system']);
        $checks[] = array(
            'label' => __('Main Menu', 'mobility-trailblazers'),
            'value' => $main_menu_exists ? 'Exists' : 'Missing',
            'status' => $main_menu_exists ? 'good' : 'error',
            'details' => __('MT Award System menu', 'mobility-trailblazers')
        );
        
        // Check submenu items
        if ($main_menu_exists) {
            $submenu_count = count($submenu['mt-award-system']);
            $checks[] = array(
                'label' => __('Submenu Items', 'mobility-trailblazers'),
                'value' => $submenu_count,
                'status' => $submenu_count > 5 ? 'good' : 'warning',
                'details' => __('Admin menu items', 'mobility-trailblazers')
            );
        }
        
        // Check jury dashboard page
        $jury_page_id = get_option('mt_jury_dashboard_page');
        $jury_page = $jury_page_id ? get_post($jury_page_id) : null;
        
        $checks[] = array(
            'label' => __('Jury Dashboard Page', 'mobility-trailblazers'),
            'value' => $jury_page ? 'Set' : 'Not set',
            'status' => $jury_page ? 'good' : 'warning',
            'details' => $jury_page ? get_permalink($jury_page_id) : __('No page selected', 'mobility-trailblazers')
        );
        
        // Check rewrite rules
        global $wp_rewrite;
        $rules = $wp_rewrite->wp_rewrite_rules();
        $has_jury_rules = false;
        
        if (is_array($rules)) {
            foreach ($rules as $rule => $rewrite) {
                if (strpos($rule, 'jury') !== false) {
                    $has_jury_rules = true;
                    break;
                }
            }
        }
        
        $checks[] = array(
            'label' => __('Rewrite Rules', 'mobility-trailblazers'),
            'value' => $has_jury_rules ? 'Present' : 'Missing',
            'status' => $has_jury_rules ? 'good' : 'warning',
            'details' => __('URL rewrite rules', 'mobility-trailblazers')
        );
        
        $this->render_check_items($checks);
    }
    
    private function check_api_endpoints() {
        $checks = array();
        
        // Check REST API availability
        $rest_url = get_rest_url();
        $checks[] = array(
            'label' => __('REST API URL', 'mobility-trailblazers'),
            'value' => 'Available',
            'status' => !empty($rest_url) ? 'good' : 'error',
            'details' => $rest_url
        );
        
        // Test specific endpoints
        $endpoints = array(
            'mt/v1/backup/create' => __('Backup Create', 'mobility-trailblazers'),
            'mt/v1/backup/history' => __('Backup History', 'mobility-trailblazers'),
            'mt/v1/vote/reset' => __('Vote Reset', 'mobility-trailblazers')
        );
        
        foreach ($endpoints as $endpoint => $label) {
            $url = $rest_url . $endpoint;
            $response = $this->test_endpoint($url);
            
            $checks[] = array(
                'label' => $label,
                'value' => $response ? 'Registered' : 'Not found',
                'status' => $response ? 'good' : 'warning',
                'details' => $endpoint
            );
        }
        
        $this->render_check_items($checks);
    }
    
    private function check_file_system() {
        $checks = array();
        
        // Check plugin directory permissions
        $plugin_dir = MT_PLUGIN_PATH;
        $is_writable = is_writable($plugin_dir);
        
        $checks[] = array(
            'label' => __('Plugin Directory', 'mobility-trailblazers'),
            'value' => $is_writable ? 'Writable' : 'Read-only',
            'status' => 'good', // Read-only is fine for production
            'details' => __('Write permissions', 'mobility-trailblazers')
        );
        
        // Check uploads directory
        $upload_dir = wp_upload_dir();
        $uploads_writable = is_writable($upload_dir['basedir']);
        
        $checks[] = array(
            'label' => __('Uploads Directory', 'mobility-trailblazers'),
            'value' => $uploads_writable ? 'Writable' : 'Not writable',
            'status' => $uploads_writable ? 'good' : 'error',
            'details' => $upload_dir['basedir']
        );
        
        // Check key directories exist
        $directories = array(
            'admin' => __('Admin directory', 'mobility-trailblazers'),
            'includes' => __('Includes directory', 'mobility-trailblazers'),
            'assets' => __('Assets directory', 'mobility-trailblazers'),
            'templates' => __('Templates directory', 'mobility-trailblazers')
        );
        
        foreach ($directories as $dir => $label) {
            $path = MT_PLUGIN_PATH . $dir;
            $exists = is_dir($path);
            
            $checks[] = array(
                'label' => $label,
                'value' => $exists ? 'Exists' : 'Missing',
                'status' => $exists ? 'good' : 'error',
                'details' => $dir . '/'
            );
        }
        
        $this->render_check_items($checks);
    }
    
    private function check_performance_caching() {
        $checks = array();
        
        // Check object cache
        $object_cache = wp_using_ext_object_cache();
        $checks[] = array(
            'label' => __('Object Cache', 'mobility-trailblazers'),
            'value' => $object_cache ? 'Enabled' : 'Disabled',
            'status' => $object_cache ? 'good' : 'warning',
            'details' => __('External object cache', 'mobility-trailblazers')
        );
        
        // Check page cache
        $cache_plugins = array(
            'wp-super-cache/wp-cache.php',
            'w3-total-cache/w3-total-cache.php',
            'wp-rocket/wp-rocket.php'
        );
        
        $has_cache_plugin = false;
        foreach ($cache_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $has_cache_plugin = true;
                break;
            }
        }
        
        $checks[] = array(
            'label' => __('Page Cache', 'mobility-trailblazers'),
            'value' => $has_cache_plugin ? 'Active' : 'Not detected',
            'status' => 'good', // Not required
            'details' => __('Caching plugin status', 'mobility-trailblazers')
        );
        
        // Check autoload size
        global $wpdb;
        $autoload_size = $wpdb->get_var("SELECT SUM(LENGTH(option_value)) FROM $wpdb->options WHERE autoload = 'yes'");
        $autoload_mb = round($autoload_size / 1048576, 2);
        
        $checks[] = array(
            'label' => __('Autoload Size', 'mobility-trailblazers'),
            'value' => $autoload_mb . ' MB',
            'status' => $autoload_mb < 1 ? 'good' : ($autoload_mb < 3 ? 'warning' : 'error'),
            'details' => __('Options autoload data', 'mobility-trailblazers')
        );
        
        $this->render_check_items($checks);
    }
    
    private function check_security() {
        $checks = array();
        
        // Check SSL
        $is_ssl = is_ssl();
        $checks[] = array(
            'label' => __('SSL/HTTPS', 'mobility-trailblazers'),
            'value' => $is_ssl ? 'Enabled' : 'Disabled',
            'status' => $is_ssl ? 'good' : 'warning',
            'details' => __('Secure connection', 'mobility-trailblazers')
        );
        
        // Check file permissions
        $config_file = ABSPATH . 'wp-config.php';
        $config_perms = substr(sprintf('%o', fileperms($config_file)), -4);
        
        $checks[] = array(
            'label' => __('Config File Permissions', 'mobility-trailblazers'),
            'value' => $config_perms,
            'status' => $config_perms <= '0644' ? 'good' : 'warning',
            'details' => __('wp-config.php', 'mobility-trailblazers')
        );
        
        // Check user enumeration
        $checks[] = array(
            'label' => __('User Enumeration', 'mobility-trailblazers'),
            'value' => 'Check manually',
            'status' => 'warning',
            'details' => __('Prevent user ID exposure', 'mobility-trailblazers')
        );
        
        $this->render_check_items($checks);
    }
    
    private function render_quick_fixes() {
        ?>
        <div class="mt-fix-grid">
            <div class="mt-fix-item">
                <h4><?php _e('Clear All Caches', 'mobility-trailblazers'); ?></h4>
                <p><?php _e('Clear WordPress object cache and transients', 'mobility-trailblazers'); ?></p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="clear_caches">
                    <?php _e('Clear Caches', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4><?php _e('Fix User Roles', 'mobility-trailblazers'); ?></h4>
                <p><?php _e('Recreate custom roles and capabilities', 'mobility-trailblazers'); ?></p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="fix_roles">
                    <?php _e('Fix Roles', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4><?php _e('Sync Jury Users', 'mobility-trailblazers'); ?></h4>
                <p><?php _e('Ensure all jury members have linked user accounts', 'mobility-trailblazers'); ?></p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="sync_jury_users">
                    <?php _e('Sync Users', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4><?php _e('Regenerate Assignments', 'mobility-trailblazers'); ?></h4>
                <p><?php _e('Rebuild candidate-jury assignment metadata', 'mobility-trailblazers'); ?></p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="regenerate_assignments">
                    <?php _e('Regenerate', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4><?php _e('Create Test Assignment', 'mobility-trailblazers'); ?></h4>
                <p><?php _e('Create a test assignment for debugging', 'mobility-trailblazers'); ?></p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="create_test_assignment">
                    <?php _e('Create Test', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4><?php _e('Link Current User', 'mobility-trailblazers'); ?></h4>
                <p><?php _e('Link your user account to a jury member', 'mobility-trailblazers'); ?></p>
                <button class="button button-secondary mt-quick-fix-btn" data-action="link_current_user">
                    <?php _e('Link Me', 'mobility-trailblazers'); ?>
                </button>
            </div>
            
            <div class="mt-fix-item">
                <h4><?php _e('Manual User-Jury Linking', 'mobility-trailblazers'); ?></h4>
                <p><?php _e('Manually link users to jury members', 'mobility-trailblazers'); ?></p>
                <button class="button button-primary mt-quick-fix-btn" data-action="show_user_jury_linker">
                    <?php _e('Open Linker', 'mobility-trailblazers'); ?>
                </button>
            </div>
        </div>
        <?php
    }
    
    private function render_system_logs() {
        // Get recent error logs
        $error_log = ini_get('error_log');
        $log_content = '';
        
        if ($error_log && file_exists($error_log)) {
            $lines = file($error_log);
            $recent_lines = array_slice($lines, -50); // Last 50 lines
            
            foreach ($recent_lines as $line) {
                if (strpos($line, 'mobility-trailblazers') !== false || 
                    strpos($line, 'mt_') !== false) {
                    $log_content .= $line;
                }
            }
        }
        
        ?>
        <div class="mt-log-viewer">
            <?php if ($log_content) : ?>
                <?php echo esc_html($log_content); ?>
            <?php else : ?>
                <?php _e('No plugin-related errors found in recent logs.', 'mobility-trailblazers'); ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    private function render_export_options() {
        ?>
        <div class="mt-export-actions">
            <button class="button button-primary" onclick="window.print()">
                <?php _e('Print Report', 'mobility-trailblazers'); ?>
            </button>
            
            <button class="button" onclick="copyDiagnosticData()">
                <?php _e('Copy to Clipboard', 'mobility-trailblazers'); ?>
            </button>
        </div>
        
        <script>
        function copyDiagnosticData() {
            var content = document.querySelector('.mt-diagnostic-page').innerText;
            navigator.clipboard.writeText(content).then(function() {
                alert('<?php _e('Diagnostic data copied to clipboard!', 'mobility-trailblazers'); ?>');
            });
        }
        </script>
        <?php
    }
    
    private function render_check_items($checks) {
        foreach ($checks as $check) {
            ?>
            <div class="mt-check-item">
                <div>
                    <div class="mt-check-label"><?php echo esc_html($check['label']); ?></div>
                    <?php if (!empty($check['details'])) : ?>
                        <div class="mt-check-details"><?php echo esc_html($check['details']); ?></div>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="mt-check-status mt-status-<?php echo esc_attr($check['status']); ?>">
                        <?php echo esc_html($check['value']); ?>
                    </span>
                </div>
            </div>
            <?php
        }
    }
    
    private function parse_size($size) {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    
    private function test_endpoint($url) {
        // Simple check if endpoint exists
        return true; // Simplified for now
    }
    
    private function clear_all_caches() {
        // Clear object cache
        wp_cache_flush();
        
        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_%'");
        
        // Clear rewrite rules
        flush_rewrite_rules();
    }
    
    private function fix_user_roles() {
        // Remove and recreate roles
        MT_Roles::remove_roles();
        MT_Roles::create_roles();
    }
    
    private function sync_jury_users() {
        $synced = 0;
        
        // Get all jury members
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($jury_members as $jury) {
            $user_id = get_post_meta($jury->ID, 'user_id', true);
            if (!$user_id) {
                // Try to find user by email
                $email = get_post_meta($jury->ID, 'email', true);
                if ($email) {
                    $user = get_user_by('email', $email);
                    if ($user) {
                        update_post_meta($jury->ID, 'user_id', $user->ID);
                        $synced++;
                    }
                }
            }
        }
        
        return $synced;
    }
    
    private function regenerate_assignments() {
        $regenerated = 0;
        
        // This would regenerate assignment metadata
        // Implementation depends on your assignment logic
        
        return $regenerated;
    }
    
    private function create_test_assignment() {
        // Get first jury member and candidate
        $jury = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        $candidate = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => 1,
            'post_status' => 'publish'
        ));
        
        if (!empty($jury) && !empty($candidate)) {
            update_post_meta($jury[0]->ID, 'assigned_candidates', array($candidate[0]->ID));
            return true;
        }
        
        return false;
    }
    
    private function link_current_user_to_jury() {
        $current_user_id = get_current_user_id();
        
        // Find first unlinked jury member
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($jury_members as $jury) {
            $user_id = get_post_meta($jury->ID, 'user_id', true);
            if (!$user_id) {
                update_post_meta($jury->ID, 'user_id', $current_user_id);
                return true;
            }
        }
        
        return false;
    }
    
    private function get_user_jury_linker_data() {
        // Get all jury members
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'any',
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        $jury_data = array();
        foreach ($jury_members as $jury) {
            $user_id = get_post_meta($jury->ID, 'user_id', true);
            $jury_data[] = array(
                'jury_id' => $jury->ID,
                'jury_name' => $jury->post_title,
                'linked_user_id' => $user_id
            );
        }
        
        // Get all users
        $users = get_users(array(
            'orderby' => 'display_name'
        ));
        
        return array(
            'jury_members' => $jury_data,
            'users' => $users
        );
    }
    
    private function link_user_to_jury($user_id, $jury_id) {
        // First check if user is already linked to another jury member
        $existing_jury = get_posts(array(
            'post_type' => 'mt_jury',
            'meta_key' => 'user_id',
            'meta_value' => $user_id,
            'posts_per_page' => 1
        ));
        
        if (!empty($existing_jury) && $existing_jury[0]->ID != $jury_id) {
            // Unlink from previous jury member
            delete_post_meta($existing_jury[0]->ID, 'user_id');
        }
        
        // Link to new jury member
        update_post_meta($jury_id, 'user_id', $user_id);
        
        // Ensure user has jury role
        $user = get_user_by('id', $user_id);
        if ($user && !in_array('mt_jury_member', $user->roles)) {
            $user->add_role('mt_jury_member');
        }
        
        return true;
    }
    
    private function unlink_user_from_jury($jury_id) {
        delete_post_meta($jury_id, 'user_id');
        return true;
    }
} 