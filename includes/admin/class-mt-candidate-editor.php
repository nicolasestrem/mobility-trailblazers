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
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Innovation Summary meta box
        add_meta_box(
            'mt_innovation_summary',
            __('Innovation Summary', 'mobility-trailblazers'),
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
        
        // Biography meta box
        add_meta_box(
            'mt_biography',
            __('Biography', 'mobility-trailblazers'),
            [$this, 'render_biography_box'],
            'mt_candidate',
            'normal',
            'default'
        );
    }
    
    /**
     * Render Innovation Summary meta box
     */
    public function render_innovation_summary_box($post) {
        wp_nonce_field('mt_candidate_editor', 'mt_candidate_editor_nonce');
        
        $overview = get_post_meta($post->ID, '_mt_overview', true);
        ?>
        <div class="mt-editor-wrapper">
            <p class="description">
                <?php _e('This content appears in the Innovation Summary section on the candidate profile page.', 'mobility-trailblazers'); ?>
            </p>
            <?php
            wp_editor($overview, 'mt_overview', [
                'textarea_name' => 'mt_overview',
                'media_buttons' => true,
                'textarea_rows' => 10,
                'tinymce' => [
                    'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,undo,redo',
                    'toolbar2' => '',
                ]
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
                <?php _e('Enter the evaluation criteria content. Use **bold** for criteria headers (e.g., **Mut & Pioniergeist:**)', 'mobility-trailblazers'); ?>
            </p>
            <?php
            wp_editor($criteria, 'mt_evaluation_criteria', [
                'textarea_name' => 'mt_evaluation_criteria',
                'media_buttons' => false,
                'textarea_rows' => 20,
                'tinymce' => [
                    'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,undo,redo',
                    'toolbar2' => '',
                ]
            ]);
            ?>
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
     * Render Biography meta box
     */
    public function render_biography_box($post) {
        $personality = get_post_meta($post->ID, '_mt_personality', true);
        ?>
        <div class="mt-editor-wrapper">
            <p class="description">
                <?php _e('This content appears in the Biography section on the candidate profile page.', 'mobility-trailblazers'); ?>
            </p>
            <?php
            wp_editor($personality, 'mt_personality', [
                'textarea_name' => 'mt_personality',
                'media_buttons' => true,
                'textarea_rows' => 10,
                'tinymce' => [
                    'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,undo,redo',
                    'toolbar2' => '',
                ]
            ]);
            ?>
        </div>
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
        
        // Save Innovation Summary
        if (isset($_POST['mt_overview'])) {
            update_post_meta($post_id, '_mt_overview', wp_kses_post($_POST['mt_overview']));
        }
        
        // Save Evaluation Criteria
        if (isset($_POST['mt_evaluation_criteria'])) {
            update_post_meta($post_id, '_mt_evaluation_criteria', wp_kses_post($_POST['mt_evaluation_criteria']));
        }
        
        // Save Biography
        if (isset($_POST['mt_personality'])) {
            update_post_meta($post_id, '_mt_personality', wp_kses_post($_POST['mt_personality']));
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
        
        // Add JavaScript for inline editing
        if ($hook === 'edit.php') {
            // Enqueue rich text editor assets
            wp_enqueue_script(
                'mt-rich-editor',
                MT_PLUGIN_URL . 'assets/js/mt-rich-editor.js',
                [],
                MT_VERSION,
                true
            );
            
            wp_enqueue_style(
                'mt-rich-editor',
                MT_PLUGIN_URL . 'assets/css/mt-rich-editor.css',
                ['dashicons'],
                MT_VERSION
            );
            
            wp_enqueue_script(
                'mt-candidate-editor',
                MT_PLUGIN_URL . 'assets/js/candidate-editor.js',
                ['jquery', 'mt-rich-editor'],
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
                        <?php _e('Innovation Summary', 'mobility-trailblazers'); ?>
                    </button>
                    <button class="mt-tab-btn" data-tab="criteria">
                        <?php _e('Evaluation Criteria', 'mobility-trailblazers'); ?>
                    </button>
                    <button class="mt-tab-btn" data-tab="biography">
                        <?php _e('Biography', 'mobility-trailblazers'); ?>
                    </button>
                </div>
                <div class="mt-modal-body">
                    <div class="mt-tab-content active" data-content="overview">
                        <textarea id="mt-edit-overview" rows="10"></textarea>
                    </div>
                    <div class="mt-tab-content" data-content="criteria">
                        <textarea id="mt-edit-criteria" rows="15"></textarea>
                    </div>
                    <div class="mt-tab-content" data-content="biography">
                        <textarea id="mt-edit-biography" rows="10"></textarea>
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
            'criteria' => '_mt_evaluation_criteria',
            'biography' => '_mt_personality'
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
        $biography = get_post_meta($post_id, '_mt_personality', true);
        
        wp_send_json_success([
            'overview' => $overview,
            'criteria' => $criteria,
            'biography' => $biography,
            'post_id' => $post_id
        ]);
    }
}

// Initialize the editor
new MT_Candidate_Editor();