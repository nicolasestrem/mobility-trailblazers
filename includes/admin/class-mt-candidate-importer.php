<?php
/**
 * Candidate Importer Admin Interface
 *
 * @package MobilityTrailblazers
 * @since 2.5.26
 */

namespace MobilityTrailblazers\Admin;

use MobilityTrailblazers\Services\MT_Candidate_Import_Service;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Candidate_Importer
 *
 * Handles admin interface for candidate import
 */
class MT_Candidate_Importer {
    
    /**
     * Menu slug
     *
     * @var string
     */
    const MENU_SLUG = 'mt-candidate-importer';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'handle_import']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'mobility-trailblazers',
            __('Import Candidates', 'mobility-trailblazers'),
            __('Import Candidates', 'mobility-trailblazers'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_page']
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, self::MENU_SLUG) === false) {
            return;
        }
        
        wp_enqueue_style('mt-admin');
    }
    
    /**
     * Handle import form submission
     */
    public function handle_import() {
        if (!isset($_POST['mt_import_candidates_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['mt_import_candidates_nonce'], 'mt_import_candidates')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check capability
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'mobility-trailblazers'));
        }
        
        // Get form data
        $dry_run = isset($_POST['dry_run']) && $_POST['dry_run'] === '1';
        $action = sanitize_text_field($_POST['action'] ?? '');
        
        $service = new MT_Candidate_Import_Service();
        $results = [];
        
        // Handle different actions
        switch ($action) {
            case 'import':
                $results = $this->handle_excel_import($service, $dry_run);
                break;
                
            case 'photos':
                $results = $this->handle_photo_import($service, $dry_run);
                break;
                
            case 'delete':
                if (!$dry_run) {
                    $backup_file = $service->backup_existing_candidates();
                    if ($backup_file) {
                        $results['messages'][] = sprintf(
                            __('Backup created: %s', 'mobility-trailblazers'),
                            basename($backup_file)
                        );
                    }
                    
                    if ($service->truncate_candidate_data()) {
                        $results['messages'][] = __('All candidate data deleted successfully', 'mobility-trailblazers');
                    } else {
                        $results['messages'][] = __('Failed to delete candidate data', 'mobility-trailblazers');
                    }
                } else {
                    $results['messages'][] = __('Dry run: Would delete all candidate data', 'mobility-trailblazers');
                }
                break;
        }
        
        // Store results in transient for display
        if (!empty($results)) {
            set_transient('mt_import_results_' . get_current_user_id(), $results, 300);
            
            // Redirect to prevent re-submission
            wp_redirect(add_query_arg('imported', '1', admin_url('admin.php?page=' . self::MENU_SLUG)));
            exit;
        }
    }
    
    /**
     * Handle Excel import
     */
    private function handle_excel_import($service, $dry_run) {
        // Get Excel file path
        $excel_path = sanitize_text_field($_POST['excel_path'] ?? '');
        
        if (empty($excel_path)) {
            // Use default Docker path
            $excel_path = '/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx';
        }
        
        // Handle uploaded file if provided
        if (!empty($_FILES['excel_file']['name'])) {
            $uploaded = wp_handle_upload($_FILES['excel_file'], ['test_form' => false]);
            
            if (isset($uploaded['file'])) {
                $excel_path = $uploaded['file'];
            } elseif (isset($uploaded['error'])) {
                return ['messages' => [$uploaded['error']]];
            }
        }
        
        // Perform import
        $results = $service->import_from_excel($excel_path, $dry_run);
        
        // Import photos if requested
        if (isset($_POST['import_photos']) && $_POST['import_photos'] === '1') {
            $photos_dir = sanitize_text_field($_POST['photos_dir'] ?? '');
            
            if (empty($photos_dir)) {
                $photos_dir = '/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Photos_candidates';
            }
            
            $photo_results = $service->import_candidate_photos($photos_dir, $dry_run);
            
            // Merge results
            $results['photos_attached'] = $photo_results['photos_attached'];
            $results['messages'] = array_merge($results['messages'], $photo_results['messages']);
        }
        
        return $results;
    }
    
    /**
     * Handle photo import only
     */
    private function handle_photo_import($service, $dry_run) {
        $photos_dir = sanitize_text_field($_POST['photos_dir'] ?? '');
        
        if (empty($photos_dir)) {
            $photos_dir = '/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Photos_candidates';
        }
        
        return $service->import_candidate_photos($photos_dir, $dry_run);
    }
    
    /**
     * Render admin page
     */
    public function render_page() {
        // Check for import results
        $results = null;
        if (isset($_GET['imported'])) {
            $results = get_transient('mt_import_results_' . get_current_user_id());
            delete_transient('mt_import_results_' . get_current_user_id());
        }
        
        ?>
        <div class="wrap">
            <h1><?php _e('Import Candidates', 'mobility-trailblazers'); ?></h1>
            
            <?php if ($results): ?>
                <div class="notice notice-info">
                    <h3><?php _e('Import Results', 'mobility-trailblazers'); ?></h3>
                    
                    <div class="mt-import-stats">
                        <p>
                            <strong><?php _e('Created:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($results['created'] ?? 0); ?><br>
                            <strong><?php _e('Updated:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($results['updated'] ?? 0); ?><br>
                            <strong><?php _e('Skipped:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($results['skipped'] ?? 0); ?><br>
                            <strong><?php _e('Errors:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($results['errors'] ?? 0); ?><br>
                            <strong><?php _e('Photos Attached:', 'mobility-trailblazers'); ?></strong> <?php echo esc_html($results['photos_attached'] ?? 0); ?>
                        </p>
                    </div>
                    
                    <?php if (!empty($results['messages'])): ?>
                        <div class="mt-import-messages">
                            <h4><?php _e('Messages:', 'mobility-trailblazers'); ?></h4>
                            <ul>
                                <?php foreach ($results['messages'] as $message): ?>
                                    <li><?php echo esc_html($message); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($results['candidates'])): ?>
                        <details>
                            <summary><?php _e('Candidate Details', 'mobility-trailblazers'); ?></summary>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php _e('Action', 'mobility-trailblazers'); ?></th>
                                        <th><?php _e('Name', 'mobility-trailblazers'); ?></th>
                                        <th><?php _e('Organization', 'mobility-trailblazers'); ?></th>
                                        <th><?php _e('Sections', 'mobility-trailblazers'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($results['candidates'] as $candidate): ?>
                                        <tr>
                                            <td><?php echo esc_html($candidate['action']); ?></td>
                                            <td><?php echo esc_html($candidate['name']); ?></td>
                                            <td><?php echo esc_html($candidate['organization'] ?? ''); ?></td>
                                            <td><?php echo esc_html(implode(', ', $candidate['sections'] ?? [])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </details>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="mt-import-forms">
                <!-- Import Form -->
                <div class="card">
                    <h2><?php _e('Import from Excel', 'mobility-trailblazers'); ?></h2>
                    
                    <form method="post" enctype="multipart/form-data">
                        <?php wp_nonce_field('mt_import_candidates', 'mt_import_candidates_nonce'); ?>
                        <input type="hidden" name="action" value="import">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="excel_path"><?php _e('Excel File Path', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="excel_path" 
                                           name="excel_path" 
                                           class="large-text"
                                           value="/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Kandidatenliste Trailblazers 2025_08_18_List for Nicolas.xlsx"
                                           placeholder="<?php esc_attr_e('Leave empty to use default', 'mobility-trailblazers'); ?>">
                                    <p class="description">
                                        <?php _e('Path to Excel file with candidate data', 'mobility-trailblazers'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="excel_file"><?php _e('Or Upload File', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <input type="file" id="excel_file" name="excel_file" accept=".xlsx,.xls">
                                    <p class="description">
                                        <?php _e('Upload an Excel file instead of using path', 'mobility-trailblazers'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="import_photos"><?php _e('Import Photos', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="import_photos" name="import_photos" value="1" checked>
                                    <label for="import_photos"><?php _e('Also import candidate photos', 'mobility-trailblazers'); ?></label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="photos_dir"><?php _e('Photos Directory', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <input type="text" 
                                           id="photos_dir" 
                                           name="photos_dir" 
                                           class="large-text"
                                           value="/var/www/html/wp-content/plugins/mobility-trailblazers/.internal/Photos_candidates"
                                           placeholder="<?php esc_attr_e('Leave empty to use default', 'mobility-trailblazers'); ?>">
                                    <p class="description">
                                        <?php _e('Path to directory containing WebP photos', 'mobility-trailblazers'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="dry_run"><?php _e('Dry Run', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="dry_run" name="dry_run" value="1" checked>
                                    <label for="dry_run"><?php _e('Perform a dry run (no changes will be made)', 'mobility-trailblazers'); ?></label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php _e('Import Candidates', 'mobility-trailblazers'); ?>
                            </button>
                        </p>
                    </form>
                </div>
                
                <!-- Delete Form -->
                <div class="card" style="margin-top: 20px; border-left: 4px solid #dc3232;">
                    <h2><?php _e('Delete All Candidates', 'mobility-trailblazers'); ?></h2>
                    
                    <p class="description" style="color: #dc3232;">
                        <strong><?php _e('Warning:', 'mobility-trailblazers'); ?></strong>
                        <?php _e('This will delete all existing candidate data. A backup will be created automatically.', 'mobility-trailblazers'); ?>
                    </p>
                    
                    <form method="post" onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete all candidates?', 'mobility-trailblazers'); ?>');">
                        <?php wp_nonce_field('mt_import_candidates', 'mt_import_candidates_nonce'); ?>
                        <input type="hidden" name="action" value="delete">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="dry_run_delete"><?php _e('Dry Run', 'mobility-trailblazers'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" id="dry_run_delete" name="dry_run" value="1" checked>
                                    <label for="dry_run_delete"><?php _e('Perform a dry run (no deletion)', 'mobility-trailblazers'); ?></label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button" style="background: #dc3232; color: white; border-color: #dc3232;">
                                <?php _e('Delete All Candidates', 'mobility-trailblazers'); ?>
                            </button>
                        </p>
                    </form>
                </div>
            </div>
            
            <style>
                .mt-import-forms .card {
                    max-width: 800px;
                    padding: 20px;
                }
                .mt-import-stats {
                    background: #f8f9fa;
                    padding: 15px;
                    border-radius: 4px;
                    margin: 10px 0;
                }
                .mt-import-messages ul {
                    max-height: 300px;
                    overflow-y: auto;
                    background: #fff;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                details summary {
                    cursor: pointer;
                    padding: 10px;
                    background: #f1f1f1;
                    margin: 10px 0;
                }
            </style>
        </div>
        <?php
    }
}
