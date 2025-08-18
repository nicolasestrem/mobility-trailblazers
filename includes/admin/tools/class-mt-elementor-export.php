<?php
/**
 * Elementor Export Tool
 *
 * @package MobilityTrailblazers
 * @since 2.5.22
 */

namespace MobilityTrailblazers\Admin\Tools;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Elementor_Export
 *
 * Admin tool for creating/refreshing Elementor templates
 */
class MT_Elementor_Export {
    
    /**
     * Initialize the tool
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_post_mt_create_elementor_templates', [$this, 'handle_create_templates']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'mobility-trailblazers',
            __('MT Elementor Export', 'mobility-trailblazers'),
            __('Elementor Export', 'mobility-trailblazers'),
            'manage_options',
            'mt-elementor-export',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            ?>
            <div class="wrap">
                <h1><?php echo esc_html__('MT Elementor Export', 'mobility-trailblazers'); ?></h1>
                <div class="notice notice-error">
                    <p><?php echo esc_html__('Elementor must be active to use this tool.', 'mobility-trailblazers'); ?></p>
                </div>
            </div>
            <?php
            return;
        }
        
        // Get existing templates
        $templates = $this->get_existing_templates();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('MT Elementor Export', 'mobility-trailblazers'); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html__('Generate Elementor Templates', 'mobility-trailblazers'); ?></h2>
                <p><?php echo esc_html__('Create or refresh Elementor templates for all Mobility Trailblazers widgets.', 'mobility-trailblazers'); ?></p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('mt_create_elementor_templates', 'mt_elementor_nonce'); ?>
                    <input type="hidden" name="action" value="mt_create_elementor_templates">
                    
                    <p>
                        <button type="submit" class="button button-primary">
                            <?php echo esc_html__('Create or Refresh Templates', 'mobility-trailblazers'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <?php if (!empty($templates)) : ?>
                <div class="card">
                    <h2><?php echo esc_html__('Existing Templates', 'mobility-trailblazers'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Template Name', 'mobility-trailblazers'); ?></th>
                                <th><?php echo esc_html__('ID', 'mobility-trailblazers'); ?></th>
                                <th><?php echo esc_html__('Status', 'mobility-trailblazers'); ?></th>
                                <th><?php echo esc_html__('Actions', 'mobility-trailblazers'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($templates as $template) : ?>
                                <tr>
                                    <td><?php echo esc_html($template->post_title); ?></td>
                                    <td><?php echo esc_html($template->ID); ?></td>
                                    <td><?php echo esc_html($template->post_status); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url(get_edit_post_link($template->ID)); ?>" class="button button-small">
                                            <?php echo esc_html__('Edit', 'mobility-trailblazers'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <h2><?php echo esc_html__('How to Use', 'mobility-trailblazers'); ?></h2>
                <ol>
                    <li><?php echo esc_html__('Click "Create or Refresh Templates" to generate the templates', 'mobility-trailblazers'); ?></li>
                    <li><?php echo esc_html__('Go to Elementor > My Templates to see the generated templates', 'mobility-trailblazers'); ?></li>
                    <li><?php echo esc_html__('Use the templates in any page by clicking "Add Template" in Elementor', 'mobility-trailblazers'); ?></li>
                    <li><?php echo esc_html__('The templates will use the same styling and functionality as the shortcodes', 'mobility-trailblazers'); ?></li>
                </ol>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle create templates action
     */
    public function handle_create_templates() {
        // Check permissions and nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['mt_elementor_nonce'], 'mt_create_elementor_templates')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Create templates
        $results = $this->create_or_update_templates();
        
        // Redirect with message
        $redirect_url = add_query_arg([
            'page' => 'mt-elementor-export',
            'message' => $results['success'] ? 'templates_created' : 'templates_failed',
            'created' => implode(',', $results['created']),
            'updated' => implode(',', $results['updated'])
        ], admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Create or update Elementor templates
     *
     * @return array
     */
    private function create_or_update_templates() {
        $templates = [
            'mt_jury_dashboard' => __('MT Jury Dashboard', 'mobility-trailblazers'),
            'mt_candidates_grid' => __('MT Candidates Grid', 'mobility-trailblazers'),
            'mt_evaluation_stats' => __('MT Evaluation Statistics', 'mobility-trailblazers'),
            'mt_winners_display' => __('MT Winners Display', 'mobility-trailblazers')
        ];
        
        $created = [];
        $updated = [];
        $success = true;
        
        // Check if using container mode
        $use_container = get_option('elementor_experiment-container', 'inactive') === 'active';
        
        foreach ($templates as $widget_name => $template_title) {
            // Check if template exists
            $existing = get_posts([
                'post_type' => 'elementor_library',
                'title' => $template_title,
                'post_status' => 'any',
                'posts_per_page' => 1
            ]);
            
            if (!empty($existing)) {
                // Update existing template
                $post_id = $existing[0]->ID;
                $action = 'update';
            } else {
                // Create new template
                $post_id = wp_insert_post([
                    'post_title' => $template_title,
                    'post_type' => 'elementor_library',
                    'post_status' => 'publish',
                    'post_content' => ''
                ]);
                $action = 'create';
            }
            
            if ($post_id) {
                // Generate Elementor data
                $elementor_data = $this->generate_elementor_data($widget_name, $use_container);
                
                // Update meta
                update_post_meta($post_id, '_elementor_data', $elementor_data);
                update_post_meta($post_id, '_elementor_template_type', $use_container ? 'container' : 'section');
                update_post_meta($post_id, '_elementor_edit_mode', 'builder');
                update_post_meta($post_id, '_wp_page_template', 'elementor_canvas');
                
                if ($action === 'create') {
                    $created[] = $post_id;
                } else {
                    $updated[] = $post_id;
                }
            } else {
                $success = false;
            }
        }
        
        return [
            'success' => $success,
            'created' => $created,
            'updated' => $updated
        ];
    }
    
    /**
     * Generate Elementor data for a widget
     *
     * @param string $widget_name Widget name
     * @param bool $use_container Use container mode
     * @return string JSON encoded Elementor data
     */
    private function generate_elementor_data($widget_name, $use_container = false) {
        if ($use_container) {
            // Container mode
            $data = [
                [
                    'id' => wp_generate_uuid4(),
                    'elType' => 'container',
                    'settings' => [
                        'content_width' => 'full',
                        'flex_direction' => 'column',
                        'flex_gap' => ['column' => '0', 'row' => '0', 'isLinked' => true, 'unit' => 'px'],
                    ],
                    'elements' => [
                        [
                            'id' => wp_generate_uuid4(),
                            'elType' => 'widget',
                            'widgetType' => $widget_name,
                            'settings' => $this->get_default_widget_settings($widget_name)
                        ]
                    ],
                    'isInner' => false
                ]
            ];
        } else {
            // Section mode
            $data = [
                [
                    'id' => wp_generate_uuid4(),
                    'elType' => 'section',
                    'settings' => [],
                    'elements' => [
                        [
                            'id' => wp_generate_uuid4(),
                            'elType' => 'column',
                            'settings' => [
                                '_column_size' => 100,
                                '_inline_size' => null
                            ],
                            'elements' => [
                                [
                                    'id' => wp_generate_uuid4(),
                                    'elType' => 'widget',
                                    'widgetType' => $widget_name,
                                    'settings' => $this->get_default_widget_settings($widget_name)
                                ]
                            ],
                            'isInner' => false
                        ]
                    ],
                    'isInner' => false
                ]
            ];
        }
        
        return wp_json_encode($data);
    }
    
    /**
     * Get default widget settings
     *
     * @param string $widget_name Widget name
     * @return array
     */
    private function get_default_widget_settings($widget_name) {
        switch ($widget_name) {
            case 'mt_candidates_grid':
                return [
                    'columns' => '3',
                    'limit' => '-1',
                    'orderby' => 'title',
                    'order' => 'ASC',
                    'show_bio' => 'yes',
                    'show_category' => 'yes'
                ];
                
            case 'mt_evaluation_stats':
                return [
                    'type' => 'summary',
                    'show_chart' => 'yes'
                ];
                
            case 'mt_winners_display':
                return [
                    'year' => date('Y'),
                    'limit' => '3',
                    'show_scores' => 'no'
                ];
                
            case 'mt_jury_dashboard':
            default:
                return [];
        }
    }
    
    /**
     * Get existing templates
     *
     * @return array
     */
    private function get_existing_templates() {
        $template_titles = [
            __('MT Jury Dashboard', 'mobility-trailblazers'),
            __('MT Candidates Grid', 'mobility-trailblazers'),
            __('MT Evaluation Statistics', 'mobility-trailblazers'),
            __('MT Winners Display', 'mobility-trailblazers')
        ];
        
        return get_posts([
            'post_type' => 'elementor_library',
            'post_status' => 'any',
            'posts_per_page' => -1,
            'post_title__in' => $template_titles,
            'meta_query' => [
                [
                    'key' => '_elementor_template_type',
                    'value' => ['section', 'container'],
                    'compare' => 'IN'
                ]
            ]
        ]);
    }
}