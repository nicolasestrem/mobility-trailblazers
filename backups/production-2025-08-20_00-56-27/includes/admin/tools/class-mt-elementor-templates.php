<?php
/**
 * Elementor Templates Tool for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 * @since 2.5.17
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Elementor_Templates
 * 
 * Creates and manages Elementor templates for MT shortcodes
 */
class MT_Elementor_Templates {
    
    /**
     * Hook suffix for the admin page
     * @var string
     */
    private $hook_suffix;
    
    /**
     * Shortcodes to create templates for
     * @var array
     */
    private $shortcodes = [
        'mt_candidate_grid' => 'MT Candidate Grid',
        'mt_voting_interface' => 'MT Voting Interface',
        'mt_jury_dashboard' => 'MT Jury Dashboard',
        'mt_voting_progress' => 'MT Voting Progress'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_post_mt_create_elementor_templates', [$this, 'handle_create_templates']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }
    
    /**
     * Add admin menu item
     */
    public function add_admin_menu() {
        $this->hook_suffix = add_submenu_page(
            'tools.php',
            __('MT Elementor Templates', 'mobility-trailblazers'),
            __('MT Elementor Templates', 'mobility-trailblazers'),
            'manage_options',
            'mt-elementor-templates',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Enqueue admin styles
     */
    public function enqueue_admin_styles($hook) {
        if ($hook !== $this->hook_suffix) {
            return;
        }
        
        $css_file = MT_PLUGIN_URL . 'assets/css/mt-elementor-templates.css';
        if (file_exists(MT_PLUGIN_DIR . 'assets/css/mt-elementor-templates.css')) {
            wp_enqueue_style('mt-elementor-templates', $css_file, [], MT_VERSION);
        }
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Check if Elementor is active
        if (!defined('ELEMENTOR_VERSION')) {
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__('MT Elementor Templates', 'mobility-trailblazers'); ?></h1>
                <div class="notice notice-error">
                    <p><?php echo esc_html__('Elementor is not active. Please install and activate Elementor to use this tool.', 'mobility-trailblazers'); ?></p>
                </div>
            </div>
            <?php
            return;
        }
        
        // Get existing templates
        $existing_templates = $this->get_existing_templates();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('MT Elementor Templates', 'mobility-trailblazers'); ?></h1>
            
            <?php if (isset($_GET['message'])): ?>
                <?php if ($_GET['message'] === 'created'): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php echo esc_html__('Templates created or refreshed successfully!', 'mobility-trailblazers'); ?></p>
                    </div>
                <?php elseif ($_GET['message'] === 'error'): ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo esc_html__('An error occurred while creating templates.', 'mobility-trailblazers'); ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="mt-elementor-templates-tool">
                <p><?php echo esc_html__('This tool creates or refreshes Elementor templates for Mobility Trailblazers shortcodes.', 'mobility-trailblazers'); ?></p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('mt_create_templates', 'mt_templates_nonce'); ?>
                    <input type="hidden" name="action" value="mt_create_elementor_templates">
                    <p>
                        <button type="submit" class="button button-primary">
                            <?php echo esc_html__('Create or Refresh Templates', 'mobility-trailblazers'); ?>
                        </button>
                    </p>
                </form>
                
                <?php if (!empty($existing_templates)): ?>
                    <h2><?php echo esc_html__('Existing Templates', 'mobility-trailblazers'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Template Title', 'mobility-trailblazers'); ?></th>
                                <th><?php echo esc_html__('Template Type', 'mobility-trailblazers'); ?></th>
                                <th><?php echo esc_html__('Post ID', 'mobility-trailblazers'); ?></th>
                                <th><?php echo esc_html__('Shortcode', 'mobility-trailblazers'); ?></th>
                                <th><?php echo esc_html__('Actions', 'mobility-trailblazers'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($existing_templates as $template): ?>
                                <tr>
                                    <td><?php echo esc_html($template->post_title); ?></td>
                                    <td><?php echo esc_html(get_post_meta($template->ID, '_elementor_template_type', true)); ?></td>
                                    <td><?php echo esc_html($template->ID); ?></td>
                                    <td>
                                        <code class="mt-shortcode-copy"><?php echo esc_html($this->get_shortcode_for_title($template->post_title)); ?></code>
                                    </td>
                                    <td>
                                        <button class="button mt-copy-shortcode" data-shortcode="<?php echo esc_attr($this->get_shortcode_for_title($template->post_title)); ?>">
                                            <?php echo esc_html__('Copy', 'mobility-trailblazers'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <script>
                    jQuery(document).ready(function($) {
                        $('.mt-copy-shortcode').on('click', function() {
                            var shortcode = $(this).data('shortcode');
                            var temp = $('<input>');
                            $('body').append(temp);
                            temp.val(shortcode).select();
                            document.execCommand('copy');
                            temp.remove();
                            
                            var button = $(this);
                            var originalText = button.text();
                            button.text('Copied!');
                            setTimeout(function() {
                                button.text(originalText);
                            }, 2000);
                        });
                    });
                    </script>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle template creation
     */
    public function handle_create_templates() {
        // Security checks
        if (!isset($_POST['mt_templates_nonce']) || !wp_verify_nonce($_POST['mt_templates_nonce'], 'mt_create_templates')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
        }
        
        // Check if Elementor is active
        if (!defined('ELEMENTOR_VERSION')) {
            wp_redirect(add_query_arg(['page' => 'mt-elementor-templates', 'message' => 'error'], admin_url('tools.php')));
            exit;
        }
        
        // Detect container mode
        $container_mode = $this->is_container_mode_active();
        $template_type = $container_mode ? 'container' : 'section';
        
        // Create or update templates
        $success = true;
        foreach ($this->shortcodes as $shortcode => $title) {
            $result = $this->create_or_update_template($title, $shortcode, $template_type);
            if (!$result) {
                $success = false;
            }
        }
        
        // Redirect back with message
        $message = $success ? 'created' : 'error';
        wp_redirect(add_query_arg(['page' => 'mt-elementor-templates', 'message' => $message], admin_url('tools.php')));
        exit;
    }
    
    /**
     * Check if Elementor container mode is active
     * @return bool
     */
    private function is_container_mode_active() {
        $experiments = get_option('elementor_experiment-container', 'default');
        return $experiments === 'active';
    }
    
    /**
     * Create or update a single template
     */
    private function create_or_update_template($title, $shortcode, $template_type) {
        // Check if template exists
        $existing = get_posts([
            'post_type' => 'elementor_library',
            'title' => $title,
            'posts_per_page' => 1
        ]);
        
        // Prepare elementor data
        $elementor_data = $this->build_elementor_data($shortcode, $template_type);
        
        // Prepare post data
        $post_data = [
            'post_title' => $title,
            'post_status' => 'publish',
            'post_type' => 'elementor_library'
        ];
        
        // Create or update post
        if (!empty($existing)) {
            $post_data['ID'] = $existing[0]->ID;
            $post_id = wp_update_post($post_data);
        } else {
            $post_id = wp_insert_post($post_data);
        }
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Update post meta
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
        update_post_meta($post_id, '_elementor_template_type', $template_type);
        update_post_meta($post_id, '_elementor_data', $elementor_data);
        update_post_meta($post_id, '_elementor_version', ELEMENTOR_VERSION);
        update_post_meta($post_id, '_wp_page_template', 'default');
        
        return $post_id;
    }
    
    /**
     * Build Elementor data structure
     */
    private function build_elementor_data($shortcode, $template_type) {
        $shortcode_string = '[' . $shortcode . ']';
        $wrapper_class = 'mt_template_wrapper mt_template_' . str_replace('mt_', '', $shortcode);
        
        if ($template_type === 'container') {
            // Container mode structure
            $data = [
                [
                    'id' => wp_generate_uuid4(),
                    'elType' => 'container',
                    'settings' => [
                        'css_classes' => $wrapper_class
                    ],
                    'elements' => [
                        [
                            'id' => wp_generate_uuid4(),
                            'elType' => 'widget',
                            'widgetType' => 'shortcode',
                            'settings' => [
                                'shortcode' => $shortcode_string
                            ]
                        ]
                    ]
                ]
            ];
        } else {
            // Section mode structure
            $data = [
                [
                    'id' => wp_generate_uuid4(),
                    'elType' => 'section',
                    'settings' => [
                        'css_classes' => $wrapper_class
                    ],
                    'elements' => [
                        [
                            'id' => wp_generate_uuid4(),
                            'elType' => 'column',
                            'settings' => [
                                '_column_size' => 100
                            ],
                            'elements' => [
                                [
                                    'id' => wp_generate_uuid4(),
                                    'elType' => 'widget',
                                    'widgetType' => 'shortcode',
                                    'settings' => [
                                        'shortcode' => $shortcode_string
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ];
        }
        
        return wp_json_encode($data);
    }
    
    /**
     * Get existing MT templates
     */
    private function get_existing_templates() {
        return get_posts([
            'post_type' => 'elementor_library',
            'posts_per_page' => -1,
            'title' => 'MT ',
            's' => 'MT '
        ]);
    }
    
    /**
     * Get shortcode for template title
     */
    private function get_shortcode_for_title($title) {
        foreach ($this->shortcodes as $shortcode => $template_title) {
            if ($title === $template_title) {
                return '[' . $shortcode . ']';
            }
        }
        return '';
    }
}

// Initialize the class
if (is_admin()) {
    new MT_Elementor_Templates();
}
