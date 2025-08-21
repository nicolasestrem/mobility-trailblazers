<?php
/**
 * Candidate Content Editor
 *
 * @package MobilityTrailblazers
 * @since 2.5.21
 */

namespace MobilityTrailblazers\Admin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidate_Editor
 *
 * Handles inline editing of candidate content sections
 */
class MT_Candidate_Editor {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        
        // Save meta data
        add_action('save_post_mt_candidate', [$this, 'save_meta_data'], 10, 2);
        
        // Add admin styles and scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Add quick edit functionality
        add_action('admin_footer', [$this, 'add_inline_edit_modal']);
        add_action('wp_ajax_mt_update_candidate_content', [$this, 'ajax_update_content']);
        add_action('wp_ajax_mt_get_candidate_content', [$this, 'ajax_get_content']);
        
        // Add inline edit button to post row actions
        add_filter('post_row_actions', [$this, 'add_inline_edit_button'], 10, 2);
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Description meta box
        add_meta_box(
            'mt_innovation_summary',
            __('Beschreibung', 'mobility-trailblazers'),
            [$this, 'render_innovation_summary_box'],
            'mt_candidate',
            'normal',
            'high'
        );
        
        // Evaluation Criteria meta box
        add_meta_box(
            'mt_evaluation_criteria',
            __('Evaluation Criteria', 'mobility-trailblazers'),
            [$this, 'render_evaluation_criteria_box'],
            'mt_candidate',
            'normal',
            'high'
        );
        
        // Category meta box - added to sidebar for easy access
        add_meta_box(
            'mt_candidate_category',
            __('Category', 'mobility-trailblazers'),
            [$this, 'render_category_meta_box'],
            'mt_candidate',
            'side',
            'high'
        );
    }
    
    /**
     * Render Description meta box
     */
    public function render_innovation_summary_box($post) {
        wp_nonce_field('mt_candidate_editor', 'mt_candidate_editor_nonce');
        
        $overview = get_post_meta($post->ID, '_mt_overview', true);
        ?>
        <div class="mt-editor-wrapper">
            <p class="description">
                <?php _e('This content appears in the Description section on the candidate profile page.', 'mobility-trailblazers'); ?>
            </p>
            <?php
            wp_editor($overview, 'mt_overview', [
                'textarea_name' => 'mt_overview',
                'media_buttons' => true,
                'textarea_rows' => 10,
                'tinymce' => true,
                'quicktags' => true
            ]);
            ?>
        </div>
        <?php
    }
    
    /**
     * Render Evaluation Criteria meta box
     */
    public function render_evaluation_criteria_box($post) {
        $criteria = get_post_meta($post->ID, '_mt_evaluation_criteria', true);
        ?>
        <div class="mt-editor-wrapper">
            <p class="description">
                <?php _e('Enter the evaluation criteria content. Use bold text for criteria headers (e.g., Mut & Pioniergeist:)', 'mobility-trailblazers'); ?>
            </p>
            <?php
            // Use a unique editor ID to avoid conflicts
            $editor_id = 'mt_criteria_editor_' . $post->ID;
            
            // Configure editor settings
            $editor_settings = array(
                'textarea_name' => 'mt_evaluation_criteria',
                'media_buttons' => false,
                'textarea_rows' => 20,
                'teeny' => false,
                'tinymce' => array(
                    'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                    'toolbar2' => 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                    'plugins' => 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview'
                ),
                'quicktags' => true,
                'dfw' => false
            );
            
            // Output the editor
            wp_editor($criteria, $editor_id, $editor_settings);
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Fix the form submission to use the correct field name
                $('#post').on('submit', function() {
                    var content = '';
                    if (typeof tinymce !== 'undefined') {
                        var editor = tinymce.get('<?php echo $editor_id; ?>');
                        if (editor) {
                            content = editor.getContent();
                        }
                    }
                    if (!content) {
                        content = $('#<?php echo $editor_id; ?>').val();
                    }
                    
                    // Create hidden field with correct name if it doesn't exist
                    if (!$('input[name="mt_evaluation_criteria"]').length) {
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'mt_evaluation_criteria',
                            value: content
                        }).appendTo('#post');
                    } else {
                        $('input[name="mt_evaluation_criteria"]').val(content);
                    }
                });
            });
            </script>
            <div class="mt-criteria-helper">
                <h4><?php _e('Standard Criteria Headers:', 'mobility-trailblazers'); ?></h4>
                <ul>
                    <li><strong>Mut & Pioniergeist:</strong></li>
                    <li><strong>Innovationsgrad:</strong></li>
                    <li><strong>Umsetzungskraft & Wirkung:</strong></li>
                    <li><strong>Relevanz für die Mobilitätswende:</strong></li>
                    <li><strong>Vorbildfunktion & Sichtbarkeit:</strong></li>
                </ul>
                <p class="description">
                    <?php _e('Copy and paste these headers to maintain consistency across all candidates.', 'mobility-trailblazers'); ?>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render Category meta box
     */
    public function render_category_meta_box($post) {
        // Get current category
        $current_category = get_post_meta($post->ID, '_mt_category_type', true);
        
        // Define available categories (matching the production categories from documentation)
        $categories = [
            'Etablierte Unternehmen' => __('Etablierte Unternehmen', 'mobility-trailblazers'),
            'Governance & Verwaltungen, Politik, öffentliche Unternehmen' => __('Governance & Verwaltungen, Politik, öffentliche Unternehmen', 'mobility-trailblazers'),
            'Start-ups, Scale-ups & Katalysatoren' => __('Start-ups, Scale-ups & Katalysatoren', 'mobility-trailblazers')
        ];
        ?>
        <div class="mt-category-wrapper">
            <p>
                <label for="mt_category_type" class="screen-reader-text">
                    <?php _e('Select Category', 'mobility-trailblazers'); ?>
                </label>
                <select name="mt_category_type" id="mt_category_type" class="widefat">
                    <option value=""><?php _e('— No Category —', 'mobility-trailblazers'); ?></option>
                    <?php foreach ($categories as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($current_category, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p class="description">
                <?php _e('Select the category for this candidate. This categorization is used for display and filtering purposes.', 'mobility-trailblazers'); ?>
            </p>
            <?php if ($current_category) : ?>
                <p class="description" style="color: #00736C; font-weight: bold;">
                    <?php _e('Current:', 'mobility-trailblazers'); ?> <?php echo esc_html($current_category); ?>
                </p>
            <?php endif; ?>
        </div>
        <style>
            .mt-category-wrapper {
                padding: 10px 0;
            }
            .mt-category-wrapper select {
                margin-top: 5px;
            }
            .mt-category-wrapper .description {
                margin-top: 8px;
                font-size: 12px;
                line-height: 1.4;
            }
        </style>
        <?php
    }
    
    /**
     * Save meta data
     */
    public function save_meta_data($post_id, $post) {
        // Check nonce
        if (!isset($_POST['mt_candidate_editor_nonce']) || 
            !wp_verify_nonce($_POST['mt_candidate_editor_nonce'], 'mt_candidate_editor')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save Description
        if (isset($_POST['mt_overview'])) {
            update_post_meta($post_id, '_mt_overview', wp_kses_post($_POST['mt_overview']));
        }
        
        // Save Evaluation Criteria
        if (isset($_POST['mt_evaluation_criteria'])) {
            update_post_meta($post_id, '_mt_evaluation_criteria', wp_kses_post($_POST['mt_evaluation_criteria']));
        }
        
        // Save Category
        if (isset($_POST['mt_category_type'])) {
            $category = sanitize_text_field($_POST['mt_category_type']);
            if (!empty($category)) {
                update_post_meta($post_id, '_mt_category_type', $category);
            } else {
                // If empty category selected, remove the meta
                delete_post_meta($post_id, '_mt_category_type');
            }
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        global $post_type;
        
        if ($post_type !== 'mt_candidate') {
            return;
        }
        
        // Add custom CSS for the editor
        wp_add_inline_style('wp-admin', '
            .mt-editor-wrapper {
                background: #fff;
                padding: 15px;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
            }
            .mt-editor-wrapper .description {
                margin-bottom: 15px;
                color: #666;
                font-style: italic;
            }
            .mt-criteria-helper {
                margin-top: 20px;
                padding: 15px;
                background: #f8f9fa;
                border-left: 4px solid #004C5F;
                border-radius: 4px;
            }
            .mt-criteria-helper h4 {
                margin-top: 0;
                color: #004C5F;
            }
            .mt-criteria-helper ul {
                list-style: none;
                padding-left: 0;
            }
            .mt-criteria-helper li {
                padding: 5px 0;
                font-family: monospace;
                background: #fff;
                padding: 8px;
                margin: 5px 0;
                border: 1px solid #ddd;
                border-radius: 3px;
            }
            .mt-inline-edit-btn {
                margin-left: 10px;
                cursor: pointer;
                color: #004C5F;
            }
            .mt-inline-edit-btn:hover {
                color: #003C3D;
            }
        ');
        
        // Add JavaScript for inline editing on list page
        if ($hook === 'edit.php' && $post_type === 'mt_candidate') {
            // Enqueue WordPress editor scripts for modal
            wp_enqueue_editor();
            wp_enqueue_script('wp-tinymce');
            
            // Enqueue our candidate editor script
            wp_enqueue_script(
                'mt-candidate-editor',
                MT_PLUGIN_URL . 'assets/js/candidate-editor.js',
                ['jquery', 'wp-tinymce'],
                MT_VERSION,
                true
            );
            
            wp_localize_script('mt-candidate-editor', 'mtCandidateEditor', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_candidate_editor'),
                'i18n' => [
                    'edit' => __('Edit Content', 'mobility-trailblazers'),
                    'save' => __('Save Changes', 'mobility-trailblazers'),
                    'cancel' => __('Cancel', 'mobility-trailblazers'),
                    'saving' => __('Saving...', 'mobility-trailblazers'),
                    'saved' => __('Saved!', 'mobility-trailblazers'),
                    'error' => __('Error saving content', 'mobility-trailblazers'),
                ]
            ]);
        }
        
        // Also enqueue on the post edit page to fix the inline editors
        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'mt_candidate') {
            // Enqueue WordPress editor properly
            wp_enqueue_editor();
            
            // Enqueue our script to reinitialize editors if needed
            wp_enqueue_script(
                'mt-candidate-editor',
                MT_PLUGIN_URL . 'assets/js/candidate-editor.js',
                ['jquery', 'wp-editor'],
                MT_VERSION,
                true
            );
            
            // Enqueue the editor fix script
            wp_enqueue_script(
                'mt-fix-editors',
                MT_PLUGIN_URL . 'assets/js/fix-editors.js',
                ['jquery', 'wp-editor', 'wp-tinymce'],
                MT_VERSION,
                true
            );
            
            wp_localize_script('mt-candidate-editor', 'mtCandidateEditor', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mt_candidate_editor')
            ]);
        }
    }
    
    /**
     * Add inline edit modal
     */
    public function add_inline_edit_modal() {
        $screen = get_current_screen();
        if ($screen->id !== 'edit-mt_candidate') {
            return;
        }
        ?>
        <div id="mt-inline-edit-modal" style="display:none;">
            <div class="mt-modal-content">
                <h2><?php _e('Edit Candidate Content', 'mobility-trailblazers'); ?></h2>
                <div class="mt-modal-tabs">
                    <button class="mt-tab-btn active" data-tab="overview">
                        <?php _e('Beschreibung', 'mobility-trailblazers'); ?>
                    </button>
                    <button class="mt-tab-btn" data-tab="criteria">
                        <?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?>
                    </button>
                </div>
                <div class="mt-modal-body">
                    <div class="mt-tab-content active" data-content="overview">
                        <textarea id="mt-edit-overview" rows="10"></textarea>
                    </div>
                    <div class="mt-tab-content" data-content="criteria">
                        <textarea id="mt-edit-criteria" rows="15"></textarea>
                    </div>
                </div>
                <div class="mt-modal-footer">
                    <button class="button button-primary mt-save-content">
                        <?php _e('Save Changes', 'mobility-trailblazers'); ?>
                    </button>
                    <button class="button mt-cancel-edit">
                        <?php _e('Cancel', 'mobility-trailblazers'); ?>
                    </button>
                </div>
            </div>
        </div>
        <style>
            #mt-inline-edit-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.6);
                z-index: 100000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .mt-modal-content {
                background: white;
                width: 90%;
                max-width: 800px;
                max-height: 90vh;
                border-radius: 8px;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }
            .mt-modal-content h2 {
                margin: 0;
                padding: 20px;
                background: #004C5F;
                color: white;
            }
            .mt-modal-tabs {
                display: flex;
                background: #f1f1f1;
                border-bottom: 1px solid #ddd;
            }
            .mt-tab-btn {
                flex: 1;
                padding: 15px;
                background: none;
                border: none;
                cursor: pointer;
                font-size: 14px;
                transition: all 0.3s;
            }
            .mt-tab-btn:hover {
                background: #e8e8e8;
            }
            .mt-tab-btn.active {
                background: white;
                border-bottom: 3px solid #004C5F;
                font-weight: bold;
            }
            .mt-modal-body {
                flex: 1;
                padding: 20px;
                overflow-y: auto;
            }
            .mt-tab-content {
                display: none;
            }
            .mt-tab-content.active {
                display: block;
            }
            .mt-tab-content textarea {
                width: 100%;
                font-family: monospace;
                font-size: 13px;
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .mt-modal-footer {
                padding: 20px;
                background: #f1f1f1;
                border-top: 1px solid #ddd;
                text-align: right;
            }
            .mt-modal-footer button {
                margin-left: 10px;
            }
        </style>
        <?php
    }
    
    /**
     * AJAX handler for updating content
     */
    public function ajax_update_content() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_candidate_editor')) {
            wp_send_json_error(['message' => __('Security check failed', 'mobility-trailblazers')]);
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'mobility-trailblazers')]);
        }
        
        $post_id = intval($_POST['post_id']);
        $field = sanitize_text_field($_POST['field']);
        $content = wp_kses_post($_POST['content']);
        
        // Map field names to meta keys
        $field_map = [
            'overview' => '_mt_overview',
            'criteria' => '_mt_evaluation_criteria'
        ];
        
        if (!isset($field_map[$field])) {
            wp_send_json_error(['message' => __('Invalid field', 'mobility-trailblazers')]);
        }
        
        // Update the meta field
        update_post_meta($post_id, $field_map[$field], $content);
        
        wp_send_json_success([
            'message' => __('Content updated successfully', 'mobility-trailblazers'),
            'field' => $field,
            'post_id' => $post_id
        ]);
    }
    
    /**
     * AJAX handler for getting content
     */
    public function ajax_get_content() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_candidate_editor')) {
            wp_send_json_error(['message' => __('Security check failed', 'mobility-trailblazers')]);
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permission denied', 'mobility-trailblazers')]);
        }
        
        $post_id = intval($_POST['post_id']);
        
        // Get all content fields
        $overview = get_post_meta($post_id, '_mt_overview', true);
        $criteria = get_post_meta($post_id, '_mt_evaluation_criteria', true);
        
        wp_send_json_success([
            'overview' => $overview,
            'criteria' => $criteria,
            'post_id' => $post_id
        ]);
    }
    
    /**
     * Add inline edit button to post row actions
     */
    public function add_inline_edit_button($actions, $post) {
        if ($post->post_type === 'mt_candidate' && current_user_can('edit_post', $post->ID)) {
            $actions['mt_inline_edit'] = sprintf(
                '<a href="#" class="mt-inline-edit-btn" data-post-id="%d">%s</a>',
                $post->ID,
                __('Quick Edit Content', 'mobility-trailblazers')
            );
        }
        return $actions;
    }
}

// Initialize the editor
new MT_Candidate_Editor();
