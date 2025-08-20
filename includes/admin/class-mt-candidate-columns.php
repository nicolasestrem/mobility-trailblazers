<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * Candidate Custom Columns Class
 *
 * @package MobilityTrailblazers
 * @since 2.2.14
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidate_Columns
 *
 * Manages custom columns and CSV import for the candidates post type
 */
class MT_Candidate_Columns {
    
    /**
     * Initialize the class
     *
     * @return void
     */
    public function init() {
        // Add custom columns
        add_filter('manage_mt_candidate_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_mt_candidate_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);
        add_filter('manage_edit-mt_candidate_sortable_columns', [$this, 'make_columns_sortable']);
        
        // Add import button to the list page
        add_action('admin_notices', [$this, 'add_import_button']);
        
        // Handle CSV import
        add_action('admin_init', [$this, 'handle_csv_import']);
        
        // Handle CSV export
        add_action('admin_post_mt_export_candidates', [$this, 'handle_export_candidates']);
        
        // Handle import success/error messages
        add_action('admin_notices', [$this, 'display_import_notices']);
        
        // Add bulk actions
        add_filter('bulk_actions-edit-mt_candidate', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-edit-mt_candidate', [$this, 'handle_bulk_actions'], 10, 3);
        
        // Add custom meta ordering
        add_action('pre_get_posts', [$this, 'custom_orderby']);
    }
    
    /**
     * Add custom columns to candidates list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_custom_columns($columns) {
        // Remove default columns we don't need
        unset($columns['date']);
        
        // Add custom columns after title
        $new_columns = [];
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['import_id'] = __('Import ID', 'mobility-trailblazers');
                $new_columns['organization'] = __('Organization', 'mobility-trailblazers');
                $new_columns['position'] = __('Position', 'mobility-trailblazers');
                $new_columns['category_type'] = __('Category', 'mobility-trailblazers');
                $new_columns['top_50'] = __('Top 50', 'mobility-trailblazers');
                $new_columns['links'] = __('Links', 'mobility-trailblazers');
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Render custom column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     * @return void
     */
    public function render_custom_columns($column, $post_id) {
        switch ($column) {
            case 'import_id':
                $import_id = get_post_meta($post_id, '_mt_candidate_id', true);
                if ($import_id) {
                    echo '<code style="background: #f0f0f0; padding: 2px 5px; border-radius: 3px;">' . esc_html($import_id) . '</code>';
                } else {
                    echo '<span class="description">—</span>';
                }
                break;
                
            case 'organization':
                $organization = get_post_meta($post_id, '_mt_organization', true);
                echo esc_html($organization ?: '—');
                break;
                
            case 'position':
                $position = get_post_meta($post_id, '_mt_position', true);
                echo esc_html($position ?: '—');
                break;
                
            case 'category_type':
                $category = get_post_meta($post_id, '_mt_category_type', true);
                if ($category) {
                    $color = '';
                    $icon = '';
                    switch (strtolower($category)) {
                        case 'startup':
                            $color = '#28a745';
                            $icon = 'dashicons-lightbulb';
                            break;
                        case 'gov':
                        case 'government':
                            $color = '#007cba';
                            $icon = 'dashicons-building';
                            break;
                        case 'tech':
                        case 'technology':
                            $color = '#dc3545';
                            $icon = 'dashicons-desktop';
                            break;
                        default:
                            $color = '#6c757d';
                            $icon = 'dashicons-category';
                    }
                    echo '<span style="display: inline-flex; align-items: center; gap: 5px; color: ' . esc_attr($color) . ';">';
                    echo '<span class="dashicons ' . esc_attr($icon) . '" style="font-size: 16px;"></span>';
                    echo '<strong>' . esc_html(ucfirst($category)) . '</strong>';
                    echo '</span>';
                } else {
                    echo '<span class="description">—</span>';
                }
                break;
                
            case 'top_50':
                $top_50 = get_post_meta($post_id, '_mt_top_50_status', true);
                if ($top_50 === 'yes' || $top_50 === '1' || $top_50 === true) {
                    echo '<span style="color: #00736C; font-size: 20px;" title="' . esc_attr__('Top 50', 'mobility-trailblazers') . '">✓</span>';
                } else {
                    echo '<span style="color: #ccc;">—</span>';
                }
                break;
                
            case 'links':
                $linkedin = get_post_meta($post_id, '_mt_linkedin_url', true);
                $website = get_post_meta($post_id, '_mt_website_url', true);
                $article = get_post_meta($post_id, '_mt_article_url', true);
                
                echo '<div style="display: flex; gap: 8px; align-items: center;">';
                
                if ($linkedin) {
                    echo '<a href="' . esc_url($linkedin) . '" target="_blank" title="' . esc_attr__('LinkedIn Profile', 'mobility-trailblazers') . '" style="text-decoration: none;">';
                    echo '<span class="dashicons dashicons-linkedin" style="font-size: 20px; color: #0077b5;"></span>';
                    echo '</a>';
                }
                
                if ($website) {
                    echo '<a href="' . esc_url($website) . '" target="_blank" title="' . esc_attr__('Website', 'mobility-trailblazers') . '" style="text-decoration: none;">';
                    echo '<span class="dashicons dashicons-admin-links" style="font-size: 20px; color: #00736C;"></span>';
                    echo '</a>';
                }
                
                if ($article) {
                    echo '<a href="' . esc_url($article) . '" target="_blank" title="' . esc_attr__('Article', 'mobility-trailblazers') . '" style="text-decoration: none;">';
                    echo '<span class="dashicons dashicons-media-document" style="font-size: 20px; color: #C27A5E;"></span>';
                    echo '</a>';
                }
                
                if (!$linkedin && !$website && !$article) {
                    echo '<span class="description">—</span>';
                }
                
                echo '</div>';
                break;
        }
    }
    
    /**
     * Make columns sortable
     *
     * @param array $columns Sortable columns
     * @return array Modified columns
     */
    public function make_columns_sortable($columns) {
        $columns['import_id'] = 'import_id';
        $columns['organization'] = 'organization';
        $columns['position'] = 'position';
        $columns['category_type'] = 'category_type';
        $columns['top_50'] = 'top_50';
        
        return $columns;
    }
    
    /**
     * Add import button to the candidates list page
     *
     * @return void
     */
    public function add_import_button() {
        $screen = get_current_screen();
        
        // Only on candidates list page
        if ($screen && $screen->id === 'edit-mt_candidate' && $screen->action === '') {
            // Enqueue jQuery UI dialog
            wp_enqueue_script('jquery-ui-dialog');
            wp_enqueue_style('wp-jquery-ui-dialog');
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Use a small delay to ensure DOM is fully ready
                setTimeout(function() {
                    // Check if button area exists
                    if ($('.wrap .page-title-action').length === 0) {
                        console.error('MT Import: Cannot find page-title-action element to add import button');
                        return;
                    }
                    
                    // Add import button next to the Add New button
                    var importButton = '<a href="#" id="mt-import-candidates" class="page-title-action"><?php echo esc_js(__('Import CSV', 'mobility-trailblazers')); ?></a>';
                    $('.wrap .page-title-action').first().after(importButton);
                    
                    // Add export button
                    var exportButton = '<a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_export_candidates'), 'mt_export_candidates'); ?>" class="page-title-action"><?php echo esc_js(__('Export CSV', 'mobility-trailblazers')); ?></a>';
                    $('#mt-import-candidates').after(exportButton);
                    
                    console.log('MT Import: Buttons added successfully');
                }, 100);
                
                // Handle category assignment bulk action
                var originalBulkAction = $('#bulk-action-selector-top').clone();
                
                // Monitor bulk action selection
                $('#bulk-action-selector-top, #bulk-action-selector-bottom').on('change', function() {
                    var $this = $(this);
                    var $wrapper = $this.closest('.bulkactions');
                    
                    // Remove any existing category selector
                    $wrapper.find('.mt-category-selector').remove();
                    
                    if ($this.val() === 'mt_assign_category') {
                        // Add category selector
                        var categorySelector = '<select name="mt_category" class="mt-category-selector" style="margin-left: 5px;">' +
                            '<option value=""><?php echo esc_js(__('Select Category', 'mobility-trailblazers')); ?></option>' +
                            '<option value="startup"><?php echo esc_js(__('Startup', 'mobility-trailblazers')); ?></option>' +
                            '<option value="gov"><?php echo esc_js(__('Government', 'mobility-trailblazers')); ?></option>' +
                            '<option value="tech"><?php echo esc_js(__('Technology', 'mobility-trailblazers')); ?></option>' +
                            '</select>';
                        
                        $this.after(categorySelector);
                    }
                });
                
                // Validate category selection before applying bulk action
                $('#doaction, #doaction2').on('click', function(e) {
                    var $form = $(this).closest('form');
                    var $selector = $(this).siblings('select[name="action"], select[name="action2"]').first();
                    
                    if ($selector.val() === 'mt_assign_category') {
                        var $categorySelector = $selector.siblings('.mt-category-selector');
                        if (!$categorySelector.val()) {
                            e.preventDefault();
                            alert('<?php echo esc_js(__('Please select a category to assign.', 'mobility-trailblazers')); ?>');
                            return false;
                        }
                    }
                });
            });
            </script>
            <?php
        }
    }
    
    /**
     * Handle CSV import
     *
     * @return void
     */
    public function handle_csv_import() {
        if (!isset($_POST['mt_action']) || $_POST['mt_action'] !== 'import_csv') {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['mt_import_nonce'], 'mt_import_csv')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to import candidates.', 'mobility-trailblazers'));
        }
        
        // Check file upload
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            wp_die(__('File upload failed.', 'mobility-trailblazers'));
        }
        
        $file = $_FILES['csv_file']['tmp_name'];
        $update_existing = !empty($_POST['update_existing']);
        $skip_duplicates = !empty($_POST['skip_duplicates']);
        
        // Process CSV
        $result = $this->process_csv_import($file, $update_existing, $skip_duplicates);
        
        // Redirect with message
        $redirect_url = admin_url('edit.php?post_type=mt_candidate');
        
        if ($result['success']) {
            $redirect_url = add_query_arg([
                'mt_import' => 'success',
                'imported' => $result['imported'],
                'updated' => $result['updated'],
                'skipped' => $result['skipped']
            ], $redirect_url);
        } else {
            $redirect_url = add_query_arg([
                'mt_import' => 'error',
                'error' => urlencode($result['error'])
            ], $redirect_url);
        }
        
        wp_redirect($redirect_url);
        wp_die();
    }
    
    /**
     * Process CSV import using MT_Import_Handler
     *
     * @param string $file File path
     * @param bool $update_existing Update existing candidates
     * @param bool $skip_duplicates Skip duplicate entries
     * @return array Result
     */
    private function process_csv_import($file, $update_existing, $skip_duplicates) {
        // Use the MT_Import_Handler for consistency
        $handler = new \MobilityTrailblazers\Admin\MT_Import_Handler();
        
        $result = $handler->process_csv_import(
            $file,
            'candidates',
            $update_existing
        );
        
        if (!empty($result['messages'])) {
            // Log messages for debugging
            foreach ($result['messages'] as $message) {
                MT_Logger::info('CSV Import', ['message' => $message]);
            }
        }
        
        return [
            'success' => true,
            'imported' => $result['success'],
            'updated' => $result['updated'],
            'skipped' => $result['skipped'],
            'errors' => $result['error_details'] ?? []
        ];
    }
    
    /**
     * Legacy process CSV import method (kept for backward compatibility)
     *
     * @deprecated Use process_csv_import() instead
     * @param string $file File path
     * @param bool $update_existing Update existing candidates
     * @param bool $skip_duplicates Skip duplicate entries
     * @return array Result
     */
    private function process_csv_import_legacy($file, $update_existing, $skip_duplicates) {
        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        
        // Open CSV file
        $handle = fopen($file, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => __('Could not open CSV file.', 'mobility-trailblazers')];
        }
        
        // Get header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'error' => __('CSV file is empty or invalid.', 'mobility-trailblazers')];
        }
        
        // Map header columns
        $header_map = array_flip(array_map('strtolower', array_map('trim', $header)));
        
        // Required columns
        if (!isset($header_map['name']) || !isset($header_map['email'])) {
            fclose($handle);
            return ['success' => false, 'error' => __('CSV must have "name" and "email" columns.', 'mobility-trailblazers')];
        }
        
        // Process rows
        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Get data from row
            $data = [
                'name' => isset($header_map['name']) ? trim($row[$header_map['name']]) : '',
                'email' => isset($header_map['email']) ? trim($row[$header_map['email']]) : '',
                'import_id' => isset($header_map['import_id']) ? trim($row[$header_map['import_id']]) : '',
                'organization' => isset($header_map['organization']) ? trim($row[$header_map['organization']]) : '',
                'position' => isset($header_map['position']) ? trim($row[$header_map['position']]) : '',
                'category_type' => isset($header_map['category_type']) ? trim($row[$header_map['category_type']]) : '',
                'top_50' => isset($header_map['top_50']) ? trim($row[$header_map['top_50']]) : '',
                'bio' => isset($header_map['bio']) ? trim($row[$header_map['bio']]) : '',
                'linkedin_url' => isset($header_map['linkedin_url']) ? trim($row[$header_map['linkedin_url']]) : '',
                'website_url' => isset($header_map['website_url']) ? trim($row[$header_map['website_url']]) : '',
                'profile_image_url' => isset($header_map['profile_image_url']) ? trim($row[$header_map['profile_image_url']]) : '',
            ];
            
            // Skip if required fields are empty
            if (empty($data['name']) || empty($data['email'])) {
                $skipped++;
                continue;
            }
            
            // Check for existing candidate by email
            $existing = get_posts([
                'post_type' => 'mt_candidate',
                'meta_key' => '_mt_email',
                'meta_value' => $data['email'],
                'posts_per_page' => 1,
                'post_status' => 'any'
            ]);
            
            if (!empty($existing)) {
                if ($skip_duplicates && !$update_existing) {
                    $skipped++;
                    continue;
                }
                
                if ($update_existing) {
                    // Update existing candidate
                    $post_id = $existing[0]->ID;
                    
                    wp_update_post([
                        'ID' => $post_id,
                        'post_title' => $data['name'],
                        'post_content' => $data['bio']
                    ]);
                    
                    $updated++;
                } else {
                    $skipped++;
                    continue;
                }
            } else {
                // Create new candidate
                $post_id = wp_insert_post([
                    'post_type' => 'mt_candidate',
                    'post_title' => $data['name'],
                    'post_content' => $data['bio'],
                    'post_status' => 'publish'
                ]);
                
                if (is_wp_error($post_id)) {
                    $errors[] = sprintf(__('Failed to create candidate: %s', 'mobility-trailblazers'), $data['name']);
                    continue;
                }
                
                $imported++;
            }
            
            // Update meta fields
            update_post_meta($post_id, '_mt_email', $data['email']);
            if (!empty($data['import_id'])) {
                update_post_meta($post_id, '_mt_candidate_id', $data['import_id']);
            }
            update_post_meta($post_id, '_mt_organization', $data['organization']);
            update_post_meta($post_id, '_mt_position', $data['position']);
            if (!empty($data['category_type'])) {
                update_post_meta($post_id, '_mt_category_type', $data['category_type']);
            }
            
            // Handle Top 50 status
            if (!empty($data['top_50'])) {
                $top_50_value = strtolower($data['top_50']);
                if (in_array($top_50_value, ['yes', 'y', '1', 'true'])) {
                    update_post_meta($post_id, '_mt_top_50_status', 'yes');
                } else {
                    update_post_meta($post_id, '_mt_top_50_status', 'no');
                }
            }
            
            update_post_meta($post_id, '_mt_linkedin_url', $data['linkedin_url']);
            update_post_meta($post_id, '_mt_website_url', $data['website_url']);
            update_post_meta($post_id, '_mt_import_date', current_time('mysql'));
            
            // Handle profile image
            if (!empty($data['profile_image_url'])) {
                $this->set_featured_image_from_url($post_id, $data['profile_image_url']);
            }
        }
        
        fclose($handle);
        
        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }
    
    /**
     * Set featured image from URL
     *
     * @param int $post_id Post ID
     * @param string $image_url Image URL
     * @return bool Success
     */
    private function set_featured_image_from_url($post_id, $image_url) {
        // Download image
        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            return false;
        }
        
        // Get file info
        $file_array = [
            'name' => basename($image_url),
            'tmp_name' => $tmp
        ];
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        // Clean up temp file
        @unlink($tmp);
        
        if (is_wp_error($attachment_id)) {
            return false;
        }
        
        // Set as featured image
        set_post_thumbnail($post_id, $attachment_id);
        
        return true;
    }
    
    /**
     * Add bulk actions
     *
     * @param array $bulk_actions Existing bulk actions
     * @return array Modified bulk actions
     */
    public function add_bulk_actions($bulk_actions) {
        $bulk_actions['mt_export'] = __('Export to CSV', 'mobility-trailblazers');
        $bulk_actions['mt_assign_category'] = __('Assign Category', 'mobility-trailblazers');
        $bulk_actions['mt_remove_category'] = __('Remove Category', 'mobility-trailblazers');
        
        return $bulk_actions;
    }
    
    /**
     * Handle bulk actions
     *
     * @param string $redirect_to Redirect URL
     * @param string $doaction Action being performed
     * @param array $post_ids Post IDs
     * @return string Redirect URL
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'mt_export') {
            // Handle export
            $this->export_candidates($post_ids);
            exit;
        }
        
        // Handle category assignment
        if ($doaction === 'mt_assign_category') {
            // Check for category parameter
            $category = isset($_REQUEST['mt_category']) ? sanitize_text_field($_REQUEST['mt_category']) : '';
            
            if (!empty($category) && !empty($post_ids)) {
                $updated = 0;
                foreach ($post_ids as $post_id) {
                    update_post_meta($post_id, '_mt_category_type', $category);
                    $updated++;
                }
                
                // Add success message
                $redirect_to = add_query_arg([
                    'mt_bulk_action' => 'category_assigned',
                    'updated' => $updated,
                    'category' => $category
                ], $redirect_to);
            }
        }
        
        // Handle category removal
        if ($doaction === 'mt_remove_category' && !empty($post_ids)) {
            $updated = 0;
            foreach ($post_ids as $post_id) {
                delete_post_meta($post_id, '_mt_category_type');
                $updated++;
            }
            
            // Add success message
            $redirect_to = add_query_arg([
                'mt_bulk_action' => 'category_removed',
                'updated' => $updated
            ], $redirect_to);
        }
        
        return $redirect_to;
    }
    
    /**
     * Export candidates to CSV
     *
     * @param array $post_ids Post IDs to export
     * @return void
     */
    private function export_candidates($post_ids = []) {
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=candidates-' . date('Y-m-d') . '.csv');
        
        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write header row
        fputcsv($output, [
            'Name',
            'Email',
            'Import ID',
            'Organization',
            'Position',
            'Category Type',
            'Top 50',
            'Bio',
            'LinkedIn URL',
            'Website URL',
            'Status',
            'Import Date'
        ]);
        
        // Get candidates
        $args = [
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ];
        
        if (!empty($post_ids)) {
            $args['post__in'] = $post_ids;
        }
        
        $candidates = get_posts($args);
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        
        // Write data rows
        foreach ($candidates as $candidate) {
            $top_50 = get_post_meta($candidate->ID, '_mt_top_50_status', true);
            $top_50_display = ($top_50 === 'yes' || $top_50 === '1' || $top_50 === true) ? 'yes' : 'no';
            
            fputcsv($output, [
                $candidate->post_title,
                get_post_meta($candidate->ID, '_mt_email', true),
                get_post_meta($candidate->ID, '_mt_candidate_id', true),
                get_post_meta($candidate->ID, '_mt_organization', true),
                get_post_meta($candidate->ID, '_mt_position', true),
                get_post_meta($candidate->ID, '_mt_category_type', true),
                $top_50_display,
                wp_strip_all_tags($candidate->post_content),
                get_post_meta($candidate->ID, '_mt_linkedin_url', true),
                get_post_meta($candidate->ID, '_mt_website_url', true),
                $candidate->post_status,
                get_post_meta($candidate->ID, '_mt_import_date', true)
            ]);
        }
        
        fclose($output);
    }
    
    /**
     * Handle custom ordering by meta fields
     *
     * @param WP_Query $query Query object
     * @return void
     */
    public function custom_orderby($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        if ($query->get('post_type') !== 'mt_candidate') {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        switch ($orderby) {
            case 'import_id':
                $query->set('meta_key', '_mt_candidate_id');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'organization':
                $query->set('meta_key', '_mt_organization');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'position':
                $query->set('meta_key', '_mt_position');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'category_type':
                $query->set('meta_key', '_mt_category_type');
                $query->set('orderby', 'meta_value');
                break;
                
            case 'top_50':
                $query->set('meta_key', '_mt_top_50_status');
                $query->set('orderby', 'meta_value');
                break;
        }
    }
    
    /**
     * Handle export candidates action
     *
     * @return void
     */
    public function handle_export_candidates() {
        // Verify nonce
        if (!wp_verify_nonce($_GET['_wpnonce'], 'mt_export_candidates')) {
            wp_die(__('Security check failed.', 'mobility-trailblazers'));
        }
        
        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to export candidates.', 'mobility-trailblazers'));
        }
        
        // Export all candidates
        $this->export_candidates();
    }
    
    /**
     * Display import and bulk action notices
     *
     * @return void
     */
    public function display_import_notices() {
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'edit-mt_candidate') {
            return;
        }
        
        // Handle import notices
        if (isset($_GET['mt_import'])) {
            if ($_GET['mt_import'] === 'success') {
                $imported = isset($_GET['imported']) ? intval($_GET['imported']) : 0;
                $updated = isset($_GET['updated']) ? intval($_GET['updated']) : 0;
                $skipped = isset($_GET['skipped']) ? intval($_GET['skipped']) : 0;
                
                $message = sprintf(
                    __('CSV import completed. %d candidates imported, %d updated, %d skipped.', 'mobility-trailblazers'),
                    $imported,
                    $updated,
                    $skipped
                );
                
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            } elseif ($_GET['mt_import'] === 'error') {
                $error = isset($_GET['error']) ? urldecode($_GET['error']) : __('An error occurred during import.', 'mobility-trailblazers');
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            }
        }
        
        // Handle bulk action notices
        if (isset($_GET['mt_bulk_action'])) {
            $updated = isset($_GET['updated']) ? intval($_GET['updated']) : 0;
            
            if ($_GET['mt_bulk_action'] === 'category_assigned' && $updated > 0) {
                $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
                $message = sprintf(
                    _n(
                        'Category "%s" assigned to %d candidate.',
                        'Category "%s" assigned to %d candidates.',
                        $updated,
                        'mobility-trailblazers'
                    ),
                    ucfirst($category),
                    $updated
                );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            } elseif ($_GET['mt_bulk_action'] === 'category_removed' && $updated > 0) {
                $message = sprintf(
                    _n(
                        'Category removed from %d candidate.',
                        'Category removed from %d candidates.',
                        $updated,
                        'mobility-trailblazers'
                    ),
                    $updated
                );
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            }
        }
    }
}
