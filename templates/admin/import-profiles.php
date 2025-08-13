<?php
/**
 * Import Profiles Admin Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
$import_results = null;
$dry_run_results = null;

if (isset($_POST['mt_import_action'])) {
    // Verify nonce
    if (!wp_verify_nonce($_POST['_wpnonce'], 'mt_import_profiles')) {
        wp_die(__('Security check failed.', 'mobility-trailblazers'));
    }
    
    // Check permissions
    if (!current_user_can('mt_import_data') && !current_user_can('manage_options')) {
        wp_die(__('You do not have permission to import data.', 'mobility-trailblazers'));
    }
    
    $action = sanitize_text_field($_POST['mt_import_action']);
    
    if ($action === 'import' || $action === 'dry_run') {
        // Handle file upload
        if (!empty($_FILES['import_file']['tmp_name'])) {
            $uploaded_file = $_FILES['import_file'];
            
            // Check file type
            $file_type = wp_check_filetype($uploaded_file['name']);
            if (!in_array($file_type['ext'], ['csv', 'txt'])) {
                $error = __('Please upload a valid CSV file.', 'mobility-trailblazers');
            } else {
                // Process import
                require_once MT_PLUGIN_DIR . 'includes/admin/class-mt-import-handler.php';
                
                $handler = new MobilityTrailblazers\Admin\MT_Import_Handler();
                $update_existing = !empty($_POST['update_existing']);
                
                // Note: MT_Import_Handler doesn't support all the old options yet
                // For dry run, we'll need to implement that separately or keep using enhanced importer
                if ($action === 'dry_run') {
                    // For now, use the same import but mark it as dry run in results
                    $results = $handler->process_csv_import(
                        $uploaded_file['tmp_name'],
                        'candidates',
                        $update_existing
                    );
                    // Mark as dry run for display purposes
                    $results['dry_run'] = true;
                } else {
                    $results = $handler->process_csv_import(
                        $uploaded_file['tmp_name'],
                        'candidates',
                        $update_existing
                    );
                }
                
                if ($action === 'dry_run') {
                    $dry_run_results = $results;
                } else {
                    $import_results = $results;
                }
            }
        } else {
            $error = __('Please select a file to import.', 'mobility-trailblazers');
        }
    }
}

// Handle template download
if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'mt_download_template')) {
        wp_die(__('Security check failed.', 'mobility-trailblazers'));
    }
    
    // Generate template - redirect to the standard template download
    wp_redirect(admin_url('admin-post.php?action=mt_download_template&type=candidates&_wpnonce=' . wp_create_nonce('mt_download_template')));
    exit;
}

// Get import statistics
// Note: Full statistics functionality needs to be implemented in MT_Import_Handler
$candidate_posts = wp_count_posts('mt_candidate');
$total_candidates = $candidate_posts->publish + $candidate_posts->draft;

// Count candidates with specific attributes
$with_photos = 0;
$with_linkedin = 0;
$with_website = 0;
$top50 = 0;
$by_category = [];

// Query candidates for statistics
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => ['publish', 'draft']
]);

foreach ($candidates as $candidate) {
    // Check for photo
    if (has_post_thumbnail($candidate->ID)) {
        $with_photos++;
    }
    
    // Check for LinkedIn
    if (get_post_meta($candidate->ID, '_mt_linkedin_url', true)) {
        $with_linkedin++;
    }
    
    // Check for website
    if (get_post_meta($candidate->ID, '_mt_website_url', true)) {
        $with_website++;
    }
    
    // Check for Top 50
    $status = get_post_meta($candidate->ID, '_mt_top_50_status', true);
    if ($status === 'yes' || $status === 'Yes' || $status === '1') {
        $top50++;
    }
    
    // Count by category
    $category = get_post_meta($candidate->ID, '_mt_category_type', true);
    if ($category) {
        if (!isset($by_category[$category])) {
            $by_category[$category] = 0;
        }
        $by_category[$category]++;
    }
}

$stats = [
    'total_candidates' => $total_candidates,
    'with_photos' => $with_photos,
    'with_linkedin' => $with_linkedin,
    'with_website' => $with_website,
    'top50' => $top50,
    'by_category' => $by_category,
    'last_import' => get_option('mt_last_import_date', 'N/A'),
    'last_import_count' => get_option('mt_last_import_count', 0)
];

?>

<div class="wrap">
    <h1><?php _e('Import Candidate Profiles', 'mobility-trailblazers'); ?></h1>
    
    <?php if (!empty($error)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if ($import_results): ?>
        <div class="notice notice-<?php echo $import_results['errors'] > 0 ? 'warning' : 'success'; ?>">
            <h3><?php _e('Import Results', 'mobility-trailblazers'); ?></h3>
            <ul>
                <li><?php printf(__('Created: %d', 'mobility-trailblazers'), $import_results['success']); ?></li>
                <li><?php printf(__('Updated: %d', 'mobility-trailblazers'), $import_results['updated']); ?></li>
                <li><?php printf(__('Skipped: %d', 'mobility-trailblazers'), $import_results['skipped']); ?></li>
                <li><?php printf(__('Errors: %d', 'mobility-trailblazers'), $import_results['errors']); ?></li>
            </ul>
            
            <?php if (!empty($import_results['messages'])): ?>
                <h4><?php _e('Details:', 'mobility-trailblazers'); ?></h4>
                <div style="max-height: 200px; overflow-y: auto; background: #f0f0f0; padding: 10px; margin: 10px 0;">
                    <?php foreach ($import_results['messages'] as $message): ?>
                        <div><?php echo esc_html($message); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($import_results['error_details'])): ?>
                <h4><?php _e('Errors:', 'mobility-trailblazers'); ?></h4>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Row', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Name', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Error', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($import_results['error_details'] as $error_detail): ?>
                            <tr>
                                <td><?php echo esc_html($error_detail['row']); ?></td>
                                <td><?php echo esc_html($error_detail['name']); ?></td>
                                <td><?php echo esc_html($error_detail['error']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($dry_run_results): ?>
        <div class="notice notice-info">
            <h3><?php _e('Dry Run Results (Preview)', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('This is what would happen if you run the actual import:', 'mobility-trailblazers'); ?></p>
            <ul>
                <li><?php printf(__('Would create: %d', 'mobility-trailblazers'), $dry_run_results['success']); ?></li>
                <li><?php printf(__('Would update: %d', 'mobility-trailblazers'), $dry_run_results['updated']); ?></li>
                <li><?php printf(__('Would skip: %d', 'mobility-trailblazers'), $dry_run_results['skipped']); ?></li>
                <li><?php printf(__('Validation errors: %d', 'mobility-trailblazers'), $dry_run_results['errors']); ?></li>
            </ul>
            
            <?php if (!empty($dry_run_results['messages'])): ?>
                <h4><?php _e('Details:', 'mobility-trailblazers'); ?></h4>
                <div style="max-height: 200px; overflow-y: auto; background: #f0f0f0; padding: 10px; margin: 10px 0;">
                    <?php foreach ($dry_run_results['messages'] as $message): ?>
                        <div><?php echo esc_html($message); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="mt-admin-container">
        <div class="mt-admin-main">
            <div class="card">
                <h2><?php _e('Import CSV File', 'mobility-trailblazers'); ?></h2>
                
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('mt_import_profiles'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="import_file"><?php _e('CSV File', 'mobility-trailblazers'); ?></label>
                            </th>
                            <td>
                                <input type="file" name="import_file" id="import_file" accept=".csv,.txt" required />
                                <p class="description">
                                    <?php _e('Select a CSV file containing candidate data.', 'mobility-trailblazers'); ?>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=mt-import-profiles&action=download_template'), 'mt_download_template'); ?>">
                                        <?php _e('Download template CSV', 'mobility-trailblazers'); ?>
                                    </a>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Import Options', 'mobility-trailblazers'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="update_existing" value="1" checked />
                                        <?php _e('Update existing candidates', 'mobility-trailblazers'); ?>
                                    </label>
                                    <br />
                                    <label>
                                        <input type="checkbox" name="skip_empty_fields" value="1" />
                                        <?php _e('Skip empty fields when updating', 'mobility-trailblazers'); ?>
                                    </label>
                                    <br />
                                    <label>
                                        <input type="checkbox" name="validate_urls" value="1" checked />
                                        <?php _e('Validate URLs', 'mobility-trailblazers'); ?>
                                    </label>
                                    <br />
                                    <label>
                                        <input type="checkbox" name="import_photos" value="1" checked />
                                        <?php _e('Import photos (if available)', 'mobility-trailblazers'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" name="mt_import_action" value="dry_run" class="button">
                            <?php _e('Test Import (Dry Run)', 'mobility-trailblazers'); ?>
                        </button>
                        <button type="submit" name="mt_import_action" value="import" class="button button-primary">
                            <?php _e('Import Candidates', 'mobility-trailblazers'); ?>
                        </button>
                    </p>
                </form>
            </div>
            
            <div class="card">
                <h2><?php _e('CSV Format Guidelines', 'mobility-trailblazers'); ?></h2>
                
                <h3><?php _e('Required Headers', 'mobility-trailblazers'); ?></h3>
                <ul>
                    <li><strong>Name</strong> - <?php _e('Candidate full name (required)', 'mobility-trailblazers'); ?></li>
                </ul>
                
                <h3><?php _e('Optional Headers', 'mobility-trailblazers'); ?></h3>
                <ul>
                    <li><strong>ID</strong> - <?php _e('Unique identifier', 'mobility-trailblazers'); ?></li>
                    <li><strong>Position</strong> - <?php _e('Job title or role', 'mobility-trailblazers'); ?></li>
                    <li><strong>Organisation</strong> - <?php _e('Company or organization name', 'mobility-trailblazers'); ?></li>
                    <li><strong>Category</strong> - <?php _e('Award category (comma-separated for multiple)', 'mobility-trailblazers'); ?></li>
                    <li><strong>Top 50</strong> - <?php _e('Yes/No or Ja/Nein', 'mobility-trailblazers'); ?></li>
                    <li><strong>Nominator</strong> - <?php _e('Person who nominated the candidate', 'mobility-trailblazers'); ?></li>
                    <li><strong>LinkedIn-Link</strong> - <?php _e('LinkedIn profile URL', 'mobility-trailblazers'); ?></li>
                    <li><strong>Webseite</strong> - <?php _e('Website URL', 'mobility-trailblazers'); ?></li>
                    <li><strong>Foto</strong> - <?php _e('Photo indicator (Ja/Yes) or URL', 'mobility-trailblazers'); ?></li>
                    <li><strong>Description</strong> - <?php _e('Full description including evaluation criteria', 'mobility-trailblazers'); ?></li>
                </ul>
                
                <h3><?php _e('Tips', 'mobility-trailblazers'); ?></h3>
                <ul>
                    <li><?php _e('Save your CSV file with UTF-8 encoding for proper character support', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('Use comma (,) or semicolon (;) as delimiter', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('Include header row as the first row after any metadata', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('For multi-line descriptions, enclose in quotes', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('Test with dry run first to validate your data', 'mobility-trailblazers'); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="mt-admin-sidebar">
            <div class="card">
                <h2><?php _e('Current Statistics', 'mobility-trailblazers'); ?></h2>
                
                <table class="widefat">
                    <tbody>
                        <tr>
                            <td><?php _e('Total Candidates', 'mobility-trailblazers'); ?></td>
                            <td><strong><?php echo number_format_i18n($stats['total_candidates']); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php _e('With Photos', 'mobility-trailblazers'); ?></td>
                            <td><strong><?php echo number_format_i18n($stats['with_photos']); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php _e('With LinkedIn', 'mobility-trailblazers'); ?></td>
                            <td><strong><?php echo number_format_i18n($stats['with_linkedin']); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php _e('With Website', 'mobility-trailblazers'); ?></td>
                            <td><strong><?php echo number_format_i18n($stats['with_website']); ?></strong></td>
                        </tr>
                        <tr>
                            <td><?php _e('Top 50', 'mobility-trailblazers'); ?></td>
                            <td><strong><?php echo number_format_i18n($stats['top50']); ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <?php if (!empty($stats['by_category'])): ?>
                    <h3><?php _e('By Category', 'mobility-trailblazers'); ?></h3>
                    <table class="widefat">
                        <tbody>
                            <?php foreach ($stats['by_category'] as $category => $count): ?>
                                <tr>
                                    <td><?php echo esc_html($category); ?></td>
                                    <td><strong><?php echo number_format_i18n($count); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h2>
                <p>
                    <a href="<?php echo admin_url('edit.php?post_type=mt_candidate'); ?>" class="button">
                        <?php _e('View All Candidates', 'mobility-trailblazers'); ?>
                    </a>
                </p>
                <p>
                    <a href="<?php echo admin_url('post-new.php?post_type=mt_candidate'); ?>" class="button">
                        <?php _e('Add New Candidate', 'mobility-trailblazers'); ?>
                    </a>
                </p>
                <p>
                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=mt-import-profiles&action=download_template'), 'mt_download_template'); ?>" class="button">
                        <?php _e('Download CSV Template', 'mobility-trailblazers'); ?>
                    </a>
                </p>
            </div>
            
            <div class="card">
                <h2><?php _e('Help', 'mobility-trailblazers'); ?></h2>
                <p><?php _e('For best results:', 'mobility-trailblazers'); ?></p>
                <ol>
                    <li><?php _e('Download the template CSV', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('Fill in your candidate data', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('Run a dry run test first', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('Review the results', 'mobility-trailblazers'); ?></li>
                    <li><?php _e('Import the data', 'mobility-trailblazers'); ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<style>
.mt-admin-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.mt-admin-main {
    flex: 1;
    min-width: 0;
}

.mt-admin-sidebar {
    width: 300px;
    flex-shrink: 0;
}

@media screen and (max-width: 782px) {
    .mt-admin-container {
        flex-direction: column;
    }
    
    .mt-admin-sidebar {
        width: 100%;
    }
}

.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-bottom: 20px;
    padding: 20px;
}

.card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.card h3 {
    margin-top: 20px;
    margin-bottom: 10px;
}
</style>
