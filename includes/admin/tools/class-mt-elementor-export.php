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
        add_action('admin_post_mt_create_elementor_export_templates', [$this, 'handle_create_templates']);
        add_action('admin_post_mt_import_elementor_template', [$this, 'handle_import_template']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'mobility-trailblazers',
            __('MT Elementor Import/Export', 'mobility-trailblazers'),
            __('Elementor Import/Export', 'mobility-trailblazers'),
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
        
        // Display messages
        if (isset($_GET['message'])) {
            $message_type = 'notice-success';
            $message_text = '';
            
            switch ($_GET['message']) {
                case 'templates_created':
                    $message_text = __('Templates created successfully!', 'mobility-trailblazers');
                    break;
                case 'templates_failed':
                    $message_text = __('Failed to create templates.', 'mobility-trailblazers');
                    $message_type = 'notice-error';
                    break;
                case 'import_success':
                    $message_text = __('Template imported successfully!', 'mobility-trailblazers');
                    if (isset($_GET['template_id'])) {
                        $edit_link = get_edit_post_link($_GET['template_id']);
                        $message_text .= ' <a href="' . esc_url($edit_link) . '">' . __('Edit Template', 'mobility-trailblazers') . '</a>';
                    }
                    break;
                case 'import_failed':
                    $message_type = 'notice-error';
                    $error = isset($_GET['error']) ? $_GET['error'] : 'unknown';
                    switch ($error) {
                        case 'no_file':
                            $message_text = __('No file was uploaded.', 'mobility-trailblazers');
                            break;
                        case 'empty_file':
                            $message_text = __('The uploaded file is empty.', 'mobility-trailblazers');
                            break;
                        case 'invalid_json':
                            $message_text = __('The uploaded file contains invalid JSON.', 'mobility-trailblazers');
                            break;
                        case 'invalid_format':
                            $message_text = __('The uploaded file is not a valid Elementor template.', 'mobility-trailblazers');
                            break;
                        case 'create_failed':
                            $message_text = __('Failed to create the template.', 'mobility-trailblazers');
                            break;
                        default:
                            $message_text = __('Import failed.', 'mobility-trailblazers');
                    }
                    break;
            }
            
            if (!empty($message_text)) {
                echo '<div class="notice ' . esc_attr($message_type) . ' is-dismissible"><p>' . wp_kses_post($message_text) . '</p></div>';
            }
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('MT Elementor Import/Export', 'mobility-trailblazers'); ?></h1>
            
            <div class="card">
                <h2><?php echo esc_html__('Generate Elementor Templates', 'mobility-trailblazers'); ?></h2>
                <p><?php echo esc_html__('Create or refresh Elementor templates for all Mobility Trailblazers widgets.', 'mobility-trailblazers'); ?></p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('mt_create_elementor_templates', 'mt_elementor_nonce'); ?>
                    <input type="hidden" name="action" value="mt_create_elementor_export_templates">
                    
                    <p>
                        <button type="submit" class="button button-primary">
                            <?php echo esc_html__('Create or Refresh Templates', 'mobility-trailblazers'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2><?php echo esc_html__('Import Elementor Template', 'mobility-trailblazers'); ?></h2>
                <p><?php echo esc_html__('Import an Elementor template from a JSON file.', 'mobility-trailblazers'); ?></p>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
                    <?php wp_nonce_field('mt_import_elementor_template', 'mt_import_nonce'); ?>
                    <input type="hidden" name="action" value="mt_import_elementor_template">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="template_file"><?php echo esc_html__('Template File', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="template_file" id="template_file" accept=".json" required>
                                <p class="description"><?php echo esc_html__('Select a JSON file exported from Elementor.', 'mobility-trailblazers'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="template_title"><?php echo esc_html__('Template Title', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <input type="text" name="template_title" id="template_title" class="regular-text" required>
                                <p class="description"><?php echo esc_html__('Enter a title for the imported template.', 'mobility-trailblazers'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p>
                        <button type="submit" class="button button-primary">
                            <?php echo esc_html__('Import Template', 'mobility-trailblazers'); ?>
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
    
    /**
     * Handle import template action
     * 
     * @since 2.5.37
     */
    public function handle_import_template() {
        // Check permissions and nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['mt_import_nonce'], 'mt_import_elementor_template')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check if file was uploaded
        if (!isset($_FILES['template_file']) || $_FILES['template_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg([
                'page' => 'mt-elementor-export',
                'message' => 'import_failed',
                'error' => 'no_file'
            ], admin_url('admin.php')));
            exit;
        }
        
        // Get template title
        $template_title = sanitize_text_field($_POST['template_title']);
        if (empty($template_title)) {
            $template_title = __('Imported Template', 'mobility-trailblazers');
        }
        
        // Read file contents
        $file_contents = file_get_contents($_FILES['template_file']['tmp_name']);
        if (empty($file_contents)) {
            wp_redirect(add_query_arg([
                'page' => 'mt-elementor-export',
                'message' => 'import_failed',
                'error' => 'empty_file'
            ], admin_url('admin.php')));
            exit;
        }
        
        // Decode JSON
        $template_data = json_decode($file_contents, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_redirect(add_query_arg([
                'page' => 'mt-elementor-export',
                'message' => 'import_failed',
                'error' => 'invalid_json'
            ], admin_url('admin.php')));
            exit;
        }
        
        // Process the template data
        $result = $this->import_elementor_template($template_data, $template_title);
        
        // Redirect with result
        if ($result['success']) {
            wp_redirect(add_query_arg([
                'page' => 'mt-elementor-export',
                'message' => 'import_success',
                'template_id' => $result['template_id']
            ], admin_url('admin.php')));
        } else {
            wp_redirect(add_query_arg([
                'page' => 'mt-elementor-export',
                'message' => 'import_failed',
                'error' => $result['error']
            ], admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Import Elementor template
     * 
     * @param array $template_data Template data
     * @param string $title Template title
     * @return array Result array
     */
    private function import_elementor_template($template_data, $title) {
        // Check if it's a valid Elementor export
        if (isset($template_data['content']) && is_array($template_data['content'])) {
            // Standard Elementor export format
            $elementor_data = $template_data['content'];
            $template_type = isset($template_data['type']) ? $template_data['type'] : 'page';
        } elseif (isset($template_data[0]) && is_array($template_data[0])) {
            // Direct Elementor data array
            $elementor_data = $template_data;
            $template_type = 'page';
        } else {
            return [
                'success' => false,
                'error' => 'invalid_format'
            ];
        }
        
        // Create the template post
        $post_id = wp_insert_post([
            'post_title' => $title,
            'post_type' => 'elementor_library',
            'post_status' => 'publish',
            'post_content' => ''
        ]);
        
        if (is_wp_error($post_id)) {
            return [
                'success' => false,
                'error' => 'create_failed'
            ];
        }
        
        // Save Elementor data
        update_post_meta($post_id, '_elementor_data', wp_json_encode($elementor_data));
        update_post_meta($post_id, '_elementor_template_type', $template_type);
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
        update_post_meta($post_id, '_wp_page_template', 'elementor_canvas');
        
        // Add version info
        if (defined('ELEMENTOR_VERSION')) {
            update_post_meta($post_id, '_elementor_version', ELEMENTOR_VERSION);
        }
        
        return [
            'success' => true,
            'template_id' => $post_id
        ];
    }
}
