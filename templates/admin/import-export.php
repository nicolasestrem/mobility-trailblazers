<?php
/**
 * Admin Import/Export Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get any messages from the URL
$import_message = '';
$message_type = 'success';
if (isset($_GET['message'])) {
    switch ($_GET['message']) {
        case 'import_success':
            $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
            $import_message = sprintf(__('Successfully imported %d records.', 'mobility-trailblazers'), $count);
            $message_type = 'success';
            break;
        case 'export_started':
            $import_message = __('Export started. Download should begin automatically.', 'mobility-trailblazers');
            $message_type = 'success';
            break;
        case 'import_error':
            $import_message = __('Error during import. Please check the file format and ensure you have selected a file.', 'mobility-trailblazers');
            $message_type = 'error';
            break;
        case 'no_file':
            $import_message = __('No file was selected. Please choose a CSV file to import.', 'mobility-trailblazers');
            $message_type = 'error';
            break;
        case 'invalid_type':
            $import_message = __('Please select an import type (Candidates or Jury Members).', 'mobility-trailblazers');
            $message_type = 'error';
            break;
    }
}
?>

<div class="wrap">
    <h1><?php _e('Import/Export', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($import_message): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($import_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="mt-import-export-container">
        <div class="mt-section">
            <h2><?php _e('Export Data', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Export your data as CSV files for backup or analysis.', 'mobility-trailblazers'); ?></p>
            
            <div class="mt-export-buttons">
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_export_candidates'), 'mt_export_candidates'); ?>" 
                   class="button button-primary">
                    <?php _e('Export Candidates', 'mobility-trailblazers'); ?>
                </a>
                
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_export_evaluations'), 'mt_export_evaluations'); ?>" 
                   class="button button-primary">
                    <?php _e('Export Evaluations', 'mobility-trailblazers'); ?>
                </a>
                
                <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_export_assignments'), 'mt_export_assignments'); ?>" 
                   class="button button-primary">
                    <?php _e('Export Assignments', 'mobility-trailblazers'); ?>
                </a>
            </div>
        </div>
        
        <div class="mt-section">
            <h2><?php _e('Import Data', 'mobility-trailblazers'); ?></h2>
            <p><?php _e('Import data from CSV files. Make sure your CSV matches the required format.', 'mobility-trailblazers'); ?></p>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field('mt_import_data', '_wpnonce'); ?>
                <input type="hidden" name="action" value="mt_import_data">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="import_type"><?php _e('Import Type', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <select name="import_type" id="import_type" required>
                                <option value=""><?php _e('Select type...', 'mobility-trailblazers'); ?></option>
                                <option value="candidates"><?php _e('Candidates', 'mobility-trailblazers'); ?></option>
                                <option value="jury_members"><?php _e('Jury Members', 'mobility-trailblazers'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="csv_file"><?php _e('CSV File', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                            <p class="description">
                                <?php _e('Select a CSV file to import. Maximum size: 10MB.', 'mobility-trailblazers'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="update_existing">
                                <input type="checkbox" name="update_existing" id="update_existing" value="1">
                                <?php _e('Update existing records', 'mobility-trailblazers'); ?>
                            </label>
                        </th>
                        <td>
                            <p class="description">
                                <?php _e('If checked, existing records with matching IDs will be updated.', 'mobility-trailblazers'); ?>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Import Data', 'mobility-trailblazers'); ?>
                    </button>
                </p>
            </form>
            
            <div class="mt-template-downloads">
                <h3><?php _e('Download Templates', 'mobility-trailblazers'); ?></h3>
                <p><?php _e('Download these templates to see the required CSV format:', 'mobility-trailblazers'); ?></p>
                
                <ul>
                    <li>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_download_template&type=candidates'), 'mt_download_template'); ?>" 
                           class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Candidates Template', 'mobility-trailblazers'); ?>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=mt_download_template&type=jury_members'), 'mt_download_template'); ?>" 
                           class="button button-secondary">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Jury Members Template', 'mobility-trailblazers'); ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.mt-import-export-container {
    max-width: 800px;
    margin-top: 20px;
}

.mt-section {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.mt-export-buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.mt-template-downloads {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.mt-template-downloads ul {
    list-style: none;
    padding: 0;
    margin-top: 15px;
}

.mt-template-downloads li {
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.mt-template-downloads .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.mt-template-downloads .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.mt-template-downloads small {
    color: #666;
}

.mt-template-downloads small a {
    text-decoration: none;
}

.mt-template-downloads small a:hover {
    text-decoration: underline;
}
</style>