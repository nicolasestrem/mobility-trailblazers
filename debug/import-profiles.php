<?php
/**
 * Profile Import Page
 *
 * @package MobilityTrailblazers
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only run if accessed by admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Handle template download
if (isset($_GET['download_template'])) {
    require_once MT_PLUGIN_DIR . 'includes/admin/class-mt-profile-importer.php';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="candidate-profiles-template.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo \MobilityTrailblazers\Admin\MT_Profile_Importer::generate_template();
    exit;
}

// Handle file upload
$import_results = null;
if (isset($_POST['import_profiles']) && isset($_FILES['csv_file'])) {
    if (!wp_verify_nonce($_POST['_wpnonce'], 'mt_import_profiles')) {
        wp_die('Security check failed.');
    }
    
    require_once MT_PLUGIN_DIR . 'includes/admin/class-mt-profile-importer.php';
    
    $uploaded_file = $_FILES['csv_file'];
    
    // Check for upload errors
    if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
        $import_results = [
            'success' => 0,
            'errors' => 1,
            'messages' => [__('File upload failed.', 'mobility-trailblazers')]
        ];
    } else {
        // Check file type
        $file_type = wp_check_filetype($uploaded_file['name']);
        if ($file_type['ext'] !== 'csv') {
            $import_results = [
                'success' => 0,
                'errors' => 1,
                'messages' => [__('Please upload a CSV file.', 'mobility-trailblazers')]
            ];
        } else {
            // Process import
            $import_results = \MobilityTrailblazers\Admin\MT_Profile_Importer::import_csv($uploaded_file['tmp_name']);
        }
    }
}
?>

<div class="wrap">
    <h1><?php _e('Import Candidate Profiles', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($import_results): ?>
        <div class="notice notice-<?php echo $import_results['errors'] === 0 ? 'success' : 'warning'; ?>">
            <p><strong><?php _e('Import Complete!', 'mobility-trailblazers'); ?></strong></p>
            <ul>
                <li><?php printf(__('Successfully imported: %d candidates', 'mobility-trailblazers'), $import_results['success']); ?></li>
                <?php if ($import_results['errors'] > 0): ?>
                    <li><?php printf(__('Errors: %d', 'mobility-trailblazers'), $import_results['errors']); ?></li>
                <?php endif; ?>
            </ul>
            <?php if (!empty($import_results['messages'])): ?>
                <h4><?php _e('Messages:', 'mobility-trailblazers'); ?></h4>
                <ul>
                    <?php foreach ($import_results['messages'] as $message): ?>
                        <li><?php echo esc_html($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2><?php _e('Import from CSV', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Upload a CSV file containing candidate profile information. The file should include columns for name, organization, position, and profile content.', 'mobility-trailblazers'); ?></p>
        
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('mt_import_profiles'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="csv_file"><?php _e('CSV File', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required />
                        <p class="description">
                            <?php _e('Maximum file size: 2MB', 'mobility-trailblazers'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="import_profiles" class="button button-primary" 
                       value="<?php esc_attr_e('Import Profiles', 'mobility-trailblazers'); ?>" />
                <a href="<?php echo add_query_arg('download_template', '1'); ?>" class="button">
                    <?php _e('Download CSV Template', 'mobility-trailblazers'); ?>
                </a>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2><?php _e('CSV Format', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Your CSV file should include the following columns:', 'mobility-trailblazers'); ?></p>
        
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Column Name', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Description', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Required', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Example', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Name</strong></td>
                    <td><?php _e('Full name of the candidate', 'mobility-trailblazers'); ?></td>
                    <td><?php _e('Yes', 'mobility-trailblazers'); ?></td>
                    <td>Dr. Anna Schmidt</td>
                </tr>
                <tr class="alternate">
                    <td><strong>Organization</strong></td>
                    <td><?php _e('Company or organization name', 'mobility-trailblazers'); ?></td>
                    <td><?php _e('No', 'mobility-trailblazers'); ?></td>
                    <td>Mobility Innovations GmbH</td>
                </tr>
                <tr>
                    <td><strong>Position</strong></td>
                    <td><?php _e('Job title or position', 'mobility-trailblazers'); ?></td>
                    <td><?php _e('No', 'mobility-trailblazers'); ?></td>
                    <td>CEO & Gründerin</td>
                </tr>
                <tr class="alternate">
                    <td><strong>LinkedIn URL</strong></td>
                    <td><?php _e('LinkedIn profile URL', 'mobility-trailblazers'); ?></td>
                    <td><?php _e('No', 'mobility-trailblazers'); ?></td>
                    <td>https://linkedin.com/in/anna-schmidt</td>
                </tr>
                <tr>
                    <td><strong>Website URL</strong></td>
                    <td><?php _e('Personal or company website', 'mobility-trailblazers'); ?></td>
                    <td><?php _e('No', 'mobility-trailblazers'); ?></td>
                    <td>https://www.example.com</td>
                </tr>
                <tr class="alternate">
                    <td><strong>Category</strong></td>
                    <td><?php _e('Award category', 'mobility-trailblazers'); ?></td>
                    <td><?php _e('No', 'mobility-trailblazers'); ?></td>
                    <td>Innovation Leaders</td>
                </tr>
                <tr>
                    <td><strong>Overview</strong></td>
                    <td><?php _e('Biographical overview (HTML allowed)', 'mobility-trailblazers'); ?></td>
                    <td><?php _e('No', 'mobility-trailblazers'); ?></td>
                    <td>&lt;p&gt;Biography text...&lt;/p&gt;</td>
                </tr>
                <tr class="alternate">
                    <td><strong>Evaluation Criteria</strong></td>
                    <td><?php _e('How candidate meets criteria (HTML allowed)', 'mobility-trailblazers'); ?></td>
                    <td><?php _e('No', 'mobility-trailblazers'); ?></td>
                    <td>&lt;h3&gt;Innovation&lt;/h3&gt;&lt;p&gt;...&lt;/p&gt;</td>
                </tr>
                <tr>
                    <td><strong>Personality & Motivation</strong></td>
                    <td><?php _e('Personality and motivation section (HTML allowed)', 'mobility-trailblazers'); ?></td>
                    <td><?php _e('No', 'mobility-trailblazers'); ?></td>
                    <td>&lt;p&gt;Motivation text...&lt;/p&gt;</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h2><?php _e('Import Tips', 'mobility-trailblazers'); ?></h2>
        <ul style="list-style-type: disc; margin-left: 20px;">
            <li><?php _e('The CSV file must be UTF-8 encoded to properly handle special characters (ä, ö, ü, etc.)', 'mobility-trailblazers'); ?></li>
            <li><?php _e('If a candidate with the same name already exists, their profile will be updated', 'mobility-trailblazers'); ?></li>
            <li><?php _e('HTML tags are allowed in Overview, Evaluation Criteria, and Personality fields', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Leave fields empty if you don\'t have the information - you can always edit later', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Categories will be created automatically if they don\'t exist', 'mobility-trailblazers'); ?></li>
        </ul>
    </div>
    
    <p>
        <a href="<?php echo admin_url('edit.php?post_type=mt_candidate'); ?>" class="button">
            <?php _e('View All Candidates', 'mobility-trailblazers'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=mobility-trailblazers'); ?>" class="button">
            <?php _e('Back to Dashboard', 'mobility-trailblazers'); ?>
        </a>
    </p>
</div>
