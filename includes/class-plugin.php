<?php
/**
 * Main Plugin Class
 *
 * @package MobilityTrailblazers
 * @subpackage Includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin class
 */
class MT_Plugin {
    
    /**
     * Instance of the class
     *
     * @var MT_Plugin
     */
    private static $instance = null;
    
    /**
     * Get instance of the class
     *
     * @return MT_Plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        // Constructor logic here
    }
    
    /**
     * Plugin version
     *
     * @var string
     */
    public $version = '2.0.0';
    
    /**
     * Components container
     *
     * @var array
     */
    private $components = array();
    
    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function run() {
        $this->load_dependencies();
        $this->init_components();
        $this->init_hooks();
    }
    
    /**
     * Load plugin dependencies
     *
     * @return void
     */
    private function load_dependencies() {
        // Load existing modules that are available
        $this->load_if_exists(MT_PLUGIN_DIR . 'modules/jury/class-jury-manager.php');
        $this->load_if_exists(MT_PLUGIN_DIR . 'modules/reset/class-reset-manager.php');
        $this->load_if_exists(MT_PLUGIN_DIR . 'modules/reset/class-backup-manager.php');
        $this->load_if_exists(MT_PLUGIN_DIR . 'modules/reset/class-audit-logger.php');
        
        // Load integrations
        $this->load_if_exists(MT_PLUGIN_DIR . 'integrations/elementor/class-elementor-integration.php');
        $this->load_if_exists(MT_PLUGIN_DIR . 'integrations/ajax/class-ajax-fix.php');
    }
    
    /**
     * Load file if it exists
     *
     * @param string $file_path Path to the file
     * @return bool True if loaded, false if not found
     */
    private function load_if_exists($file_path) {
        if (file_exists($file_path)) {
            require_once $file_path;
            return true;
        }
        return false;
    }
    
    /**
     * Initialize components
     *
     * @return void
     */
    private function init_components() {
        // Initialize basic admin functionality
        if (is_admin()) {
            // Create a basic admin menu for now
            add_action('admin_menu', array($this, 'add_basic_admin_menu'));
        }
        
        // Initialize available modules
        $this->components['modules'] = array();
        
        // Only initialize modules that exist
        if (class_exists('MT_Jury_Management_Admin')) {
            $this->components['modules']['jury'] = MT_Jury_Management_Admin::get_instance();
        }
        
        if (class_exists('MT_Vote_Reset_Manager')) {
            $this->components['modules']['reset'] = new MT_Vote_Reset_Manager();
        }
        
        // Initialize integrations if available
        $this->components['integrations'] = array();
        
        if (class_exists('MT_Elementor_Compatibility')) {
            $this->components['integrations']['elementor'] = new MT_Elementor_Compatibility();
        }
        
        if (class_exists('MT_Ajax_Fix')) {
            $this->components['integrations']['ajax'] = MT_Ajax_Fix::get_instance();
        }
    }
    
    /**
     * Initialize hooks
     *
     * @return void
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_components'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }
    
    /**
     * Register all components
     *
     * @return void
     */
    public function register_components() {
        // Initialize modules
        if (isset($this->components['modules'])) {
            foreach ($this->components['modules'] as $module) {
                if (method_exists($module, 'init')) {
                    $module->init();
                }
            }
        }
        
        // Initialize integrations
        if (isset($this->components['integrations'])) {
            foreach ($this->components['integrations'] as $integration) {
                if (method_exists($integration, 'init')) {
                    $integration->init();
                } elseif (method_exists($integration, 'init_elementor_hooks')) {
                    $integration->init_elementor_hooks();
                } elseif (method_exists($integration, 'init_fixes')) {
                    $integration->init_fixes();
                }
            }
        }
    }
    
    /**
     * Add basic admin menu
     *
     * @return void
     */
    public function add_basic_admin_menu() {
        add_menu_page(
            __('MT Award System', 'mobility-trailblazers'),
            __('MT Award System', 'mobility-trailblazers'),
            'manage_options',
            'mt-award-system',
            array($this, 'render_basic_admin_page'),
            'dashicons-awards',
            30
        );
        
        // Add submenu for existing functionality
        if (class_exists('MT_Jury_Management_Admin')) {
            add_submenu_page(
                'mt-award-system',
                __('Jury Management', 'mobility-trailblazers'),
                __('Jury Management', 'mobility-trailblazers'),
                'manage_options',
                'mt-jury-management',
                array(MT_Jury_Management_Admin::get_instance(), 'render_jury_management_page')
            );
        }
        
        // Add vote reset page if available
        if (file_exists(MT_PLUGIN_DIR . 'admin/pages/vote-reset.php')) {
            add_submenu_page(
                'mt-award-system',
                __('Vote Reset', 'mobility-trailblazers'),
                __('Vote Reset', 'mobility-trailblazers'),
                'manage_options',
                'mt-vote-reset',
                array($this, 'render_vote_reset_page')
            );
        }
    }
    
    /**
     * Render basic admin page
     *
     * @return void
     */
    public function render_basic_admin_page() {
        echo '<div class="wrap">';
        echo '<h1>' . __('Mobility Trailblazers Award System', 'mobility-trailblazers') . '</h1>';
        echo '<p>' . __('Welcome to the Mobility Trailblazers Award System. Use the menu items to manage your award competition.', 'mobility-trailblazers') . '</p>';
        
        // Show system status
        echo '<h2>' . __('System Status', 'mobility-trailblazers') . '</h2>';
        echo '<ul>';
        echo '<li><strong>' . __('Plugin Version:', 'mobility-trailblazers') . '</strong> ' . $this->version . '</li>';
        echo '<li><strong>' . __('Composer Autoloader:', 'mobility-trailblazers') . '</strong> ' . (file_exists(MT_PLUGIN_DIR . 'vendor/autoload.php') ? __('Available', 'mobility-trailblazers') : __('Not Available (using manual loading)', 'mobility-trailblazers')) . '</li>';
        echo '<li><strong>' . __('Loaded Components:', 'mobility-trailblazers') . '</strong> ' . count($this->components) . '</li>';
        echo '</ul>';
        
        echo '</div>';
    }
    
    /**
     * Render vote reset page
     *
     * @return void
     */
    public function render_vote_reset_page() {
        if (file_exists(MT_PLUGIN_DIR . 'admin/pages/vote-reset.php')) {
            include MT_PLUGIN_DIR . 'admin/pages/vote-reset.php';
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . __('Vote Reset', 'mobility-trailblazers') . '</h1>';
            echo '<p>' . __('Vote reset functionality is not available in this installation.', 'mobility-trailblazers') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Load plugin textdomain
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'mobility-trailblazers',
            false,
            dirname(MT_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     *
     * @return void
     */
    public function activate() {
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     *
     * @return void
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set default plugin options
     *
     * @return void
     */
    private function set_default_options() {
        $defaults = array(
            'mt_current_voting_phase' => 'phase_1',
            'mt_voting_phase_phase_1_status' => 'open',
            'mt_voting_phase_phase_2_status' => 'closed',
            'mt_voting_phase_phase_3_status' => 'closed',
            'mt_plugin_version' => $this->version,
        );
        
        foreach ($defaults as $option => $value) {
            if (false === get_option($option)) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Get component by name
     *
     * @param string $component_name Component name
     * @return mixed|null
     */
    public function get_component($component_name) {
        return isset($this->components[$component_name]) ? $this->components[$component_name] : null;
    }
    
    /**
     * Get module by name
     *
     * @param string $module_name Module name
     * @return mixed|null
     */
    public function get_module($module_name) {
        return isset($this->components['modules'][$module_name]) ? $this->components['modules'][$module_name] : null;
    }
} 