<?php
/**
 * Main Plugin Class
 *
 * @package MobilityTrailblazers
 * @subpackage Includes
 */

namespace MobilityTrailblazers;

use MobilityTrailblazers\Core\Traits\Singleton;
use MobilityTrailblazers\Core\PostTypes\Candidate_Post_Type;
use MobilityTrailblazers\Core\PostTypes\Jury_Post_Type;
use MobilityTrailblazers\Core\Taxonomies\Category_Taxonomy;
use MobilityTrailblazers\Core\Taxonomies\Phase_Taxonomy;
use MobilityTrailblazers\Core\Taxonomies\Status_Taxonomy;
use MobilityTrailblazers\Core\Roles\Roles_Manager;
use MobilityTrailblazers\Modules\Voting\Voting_Manager;
use MobilityTrailblazers\Modules\Evaluation\Evaluation_Manager;
use MobilityTrailblazers\Modules\Jury\Jury_Manager;
use MobilityTrailblazers\Modules\Candidates\Candidate_Manager;
use MobilityTrailblazers\Modules\Assignments\Assignment_Manager;
use MobilityTrailblazers\Modules\Reset\Reset_Manager;
use MobilityTrailblazers\Modules\Reports\Reports_Manager;
use MobilityTrailblazers\Admin\Admin;
use MobilityTrailblazers\PublicFrontend\PublicFrontend;
use MobilityTrailblazers\Api\Api_Manager;
use MobilityTrailblazers\Integrations\Elementor\Elementor_Integration;
use MobilityTrailblazers\Integrations\Ajax\Ajax_Manager;
use MobilityTrailblazers\Database\Database_Manager;

/**
 * Main plugin class
 */
class Plugin {
    
    use Singleton;
    
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
        // Load core abstracts and interfaces
        require_once MT_PLUGIN_DIR . 'core/interfaces/interface-registrable.php';
        require_once MT_PLUGIN_DIR . 'core/interfaces/interface-hookable.php';
        require_once MT_PLUGIN_DIR . 'core/traits/trait-singleton.php';
        require_once MT_PLUGIN_DIR . 'core/traits/trait-ajax-handler.php';
        require_once MT_PLUGIN_DIR . 'core/abstracts/class-abstract-post-type.php';
        require_once MT_PLUGIN_DIR . 'core/abstracts/class-abstract-taxonomy.php';
        
        // Load post types
        require_once MT_PLUGIN_DIR . 'core/post-types/class-candidate-post-type.php';
        require_once MT_PLUGIN_DIR . 'core/post-types/class-jury-post-type.php';
        
        // Load taxonomies
        require_once MT_PLUGIN_DIR . 'core/taxonomies/class-category-taxonomy.php';
        require_once MT_PLUGIN_DIR . 'core/taxonomies/class-phase-taxonomy.php';
        require_once MT_PLUGIN_DIR . 'core/taxonomies/class-status-taxonomy.php';
        
        // Load roles
        require_once MT_PLUGIN_DIR . 'core/roles/class-roles-manager.php';
        
        // Load modules
        require_once MT_PLUGIN_DIR . 'modules/voting/class-voting-manager.php';
        require_once MT_PLUGIN_DIR . 'modules/evaluation/class-evaluation-manager.php';
        require_once MT_PLUGIN_DIR . 'modules/jury/class-jury-manager.php';
        require_once MT_PLUGIN_DIR . 'modules/candidates/class-candidate-manager.php';
        require_once MT_PLUGIN_DIR . 'modules/assignments/class-assignment-manager.php';
        require_once MT_PLUGIN_DIR . 'modules/reset/class-reset-manager.php';
        require_once MT_PLUGIN_DIR . 'modules/reports/class-reports-manager.php';
        
        // Load admin
        require_once MT_PLUGIN_DIR . 'admin/class-admin.php';
        
        // Load public
        require_once MT_PLUGIN_DIR . 'public/class-public.php';
        
        // Load API
        require_once MT_PLUGIN_DIR . 'api/class-api-manager.php';
        
        // Load integrations
        require_once MT_PLUGIN_DIR . 'integrations/elementor/class-elementor-integration.php';
        require_once MT_PLUGIN_DIR . 'integrations/ajax/class-ajax-manager.php';
        
        // Load database
        require_once MT_PLUGIN_DIR . 'database/class-database-manager.php';
    }
    
    /**
     * Initialize components
     *
     * @return void
     */
    private function init_components() {
        // Initialize core components
        $this->components['post_types'] = array(
            'candidate' => new Candidate_Post_Type(),
            'jury' => new Jury_Post_Type(),
        );
        
        $this->components['taxonomies'] = array(
            'category' => new Category_Taxonomy(),
            'phase' => new Phase_Taxonomy(),
            'status' => new Status_Taxonomy(),
        );
        
        $this->components['roles'] = new Roles_Manager();
        
        // Initialize modules
        $this->components['modules'] = array(
            'voting' => new Voting_Manager(),
            'evaluation' => new Evaluation_Manager(),
            'jury' => new Jury_Manager(),
            'candidates' => new Candidate_Manager(),
            'assignments' => new Assignment_Manager(),
            'reset' => new Reset_Manager(),
            'reports' => new Reports_Manager(),
        );
        
        // Initialize admin and public
        if (is_admin()) {
            $this->components['admin'] = new Admin();
        }
        
        if (!is_admin() || wp_doing_ajax()) {
            $this->components['public'] = new PublicFrontend();
        }
        
        // Initialize API
        $this->components['api'] = new Api_Manager();
        
        // Initialize integrations
        $this->components['integrations'] = array(
            'elementor' => new Elementor_Integration(),
            'ajax' => new Ajax_Manager(),
        );
        
        // Initialize database
        $this->components['database'] = new Database_Manager();
    }
    
    /**
     * Initialize hooks
     *
     * @return void
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_components'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Register activation and deactivation hooks
        register_activation_hook(MT_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(MT_PLUGIN_FILE, array($this, 'deactivate'));
    }
    
    /**
     * Register all components
     *
     * @return void
     */
    public function register_components() {
        // Register post types
        foreach ($this->components['post_types'] as $post_type) {
            $post_type->register();
        }
        
        // Register taxonomies
        foreach ($this->components['taxonomies'] as $taxonomy) {
            $taxonomy->register();
        }
        
        // Register roles
        $this->components['roles']->register();
        
        // Initialize modules
        foreach ($this->components['modules'] as $module) {
            if (method_exists($module, 'init')) {
                $module->init();
            }
        }
        
        // Initialize other components
        if (isset($this->components['admin'])) {
            $this->components['admin']->init();
        }
        
        if (isset($this->components['public'])) {
            $this->components['public']->init();
        }
        
        $this->components['api']->init();
        
        foreach ($this->components['integrations'] as $integration) {
            if (method_exists($integration, 'init')) {
                $integration->init();
            }
        }
        
        $this->components['database']->init();
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
        // Create database tables
        $this->components['database']->create_tables();
        
        // Register post types and taxonomies for rewrite rules
        $this->register_components();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set default options
        $this->set_default_options();
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