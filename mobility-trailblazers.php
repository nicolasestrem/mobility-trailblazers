<?php
/**
 * Plugin Name: Mobility Trailblazers
 * Description: Award platform for mobility shapers with jury voting system
 * Version: 2.1.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MT_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('MT_PLUGIN_VERSION', '2.1.0');

// Include all necessary files
require_once MT_PLUGIN_PATH . 'includes/class-mt-database.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-candidate.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-jury.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-evaluation.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-admin.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-shortcodes.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-frontend.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-ajax.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-notifications.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-reports.php';
require_once MT_PLUGIN_PATH . 'includes/class-mt-assignment.php';

// Include the NEW consistency fix
require_once MT_PLUGIN_PATH . 'includes/class-mt-jury-consistency-v2.php';

/**
 * Main plugin class
 */
class MobilityTrailblazers {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Initialize all components
        MT_Database::get_instance();
        MT_Candidate::get_instance();
        MT_Jury::get_instance();
        MT_Evaluation::get_instance();
        MT_Admin::get_instance();
        MT_Shortcodes::get_instance();
        MT_Frontend::get_instance();
        MT_Ajax::get_instance();
        MT_Notifications::get_instance();
        MT_Reports::get_instance();
        MT_Assignment::get_instance();
        
        // Initialize consistency fix
        MT_Jury_Consistency_V2::get_instance();
        
        // Load textdomain
        load_plugin_textdomain('mobility-trailblazers', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        // Create database tables
        MT_Database::get_instance()->create_tables();
        
        // Create default roles
        $this->create_roles();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag for consistency check
        update_option('mt_consistency_check_needed', true);
    }
    
    public function deactivate() {
        // Cleanup temporary data
        flush_rewrite_rules();
    }
    
    private function create_roles() {
        // Create jury member role
        add_role('mt_jury_member', __('Jury Member', 'mobility-trailblazers'), array(
            'read' => true,
            'mt_access_jury_dashboard' => true,
            'mt_submit_evaluations' => true,
        ));
        
        // Add capabilities to admin
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('mt_manage_candidates');
            $admin_role->add_cap('mt_manage_jury');
            $admin_role->add_cap('mt_view_reports');
            $admin_role->add_cap('mt_access_jury_dashboard');
            $admin_role->add_cap('mt_submit_evaluations');
        }
    }
}

// Initialize the plugin
MobilityTrailblazers::get_instance();

/**
 * UNIFIED DASHBOARD FUNCTIONS
 * These functions MUST be used by both admin and frontend dashboards
 * to ensure 100% consistency
 */

if (!function_exists('mt_get_user_evaluation_count')) {
    /**
     * Get evaluation count for a user (UNIFIED FUNCTION)
     * This ensures consistency across all dashboards
     */
    function mt_get_user_evaluation_count($user_id) {
        // Use the unified function from the consistency class
        return mt_get_user_evaluation_count_unified($user_id);
    }
}

if (!function_exists('mt_has_jury_evaluated')) {
    /**
     * Check if jury member has evaluated a candidate (UNIFIED FUNCTION)
     */
    function mt_has_jury_evaluated($user_id, $candidate_id) {
        // Use the unified function from the consistency class
        return mt_has_jury_evaluated_unified($user_id, $candidate_id);
    }
}

if (!function_exists('mt_get_jury_evaluation')) {
    /**
     * Get jury evaluation for a candidate (UNIFIED FUNCTION)
     */
    function mt_get_jury_evaluation($user_id, $candidate_id) {
        // Use the unified function from the consistency class
        return mt_get_jury_evaluation_unified($user_id, $candidate_id);
    }
}

/**
 * DEPRECATED FUNCTIONS - These are kept for backward compatibility
 * but they redirect to the unified functions
 */

if (!function_exists('mt_get_user_evaluation_count_old')) {
    function mt_get_user_evaluation_count_old($user_id) {
        // Redirect to unified function
        return mt_get_user_evaluation_count($user_id);
    }
}

/**
 * Plugin activation hook
 */
function mt_plugin_activate() {
    // Create database tables
    MT_Database::get_instance()->create_tables();
    
    // Create roles
    MobilityTrailblazers::get_instance()->create_roles();
    
    // Set flag for consistency check
    update_option('mt_consistency_check_needed', true);
    
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'mt_plugin_activate');

/**
 * Plugin deactivation hook
 */
function mt_plugin_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'mt_plugin_deactivate');

/**
 * Check plugin dependencies
 */
function mt_check_dependencies() {
    if (!function_exists('wp_create_user')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Mobility Trailblazers requires WordPress user functions to be available.</p></div>';
        });
        return false;
    }
    return true;
}
add_action('plugins_loaded', 'mt_check_dependencies');

/**
 * Add custom admin menu styling
 */
function mt_admin_styles() {
    ?>
    <style>
    .mt-consistency-notice {
        border-left: 4px solid #dc3545;
    }
    .mt-success-notice {
        border-left: 4px solid #28a745;
    }
    .mt-warning-notice {
        border-left: 4px solid #ffc107;
    }
    </style>
    <?php
}
add_action('admin_head', 'mt_admin_styles');

/**
 * Emergency cleanup function - can be called via WP-CLI
 */
if (defined('WP_CLI') && WP_CLI) {
    function mt_emergency_cleanup() {
        global $wpdb;
        $table_scores = $wpdb->prefix . 'mt_candidate_scores';
        
        // Delete all evaluations with IDs that don't correspond to real users
        $deleted = $wpdb->query(
            "DELETE FROM $table_scores 
            WHERE jury_member_id NOT IN (SELECT ID FROM {$wpdb->users})
            OR jury_member_id <= 0"
        );
        
        WP_CLI::success("Deleted $deleted invalid evaluations.");
        
        // Force sync remaining evaluations
        $consistency = MT_Jury_Consistency_V2::get_instance();
        $result = $consistency->fix_all_consistency_issues();
        
        WP_CLI::success($result['message']);
    }
    
    WP_CLI::add_command('mt cleanup', 'mt_emergency_cleanup');
}

// End of file