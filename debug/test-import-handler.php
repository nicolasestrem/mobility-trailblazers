<?php
/**
 * Test Import Handler
 * 
 * Usage: Load this file in WordPress admin to test the import handler
 * 
 * @package MobilityTrailblazers
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only allow admin users
if (!current_user_can('manage_options')) {
    wp_die('Permission denied');
}

?>
<div class="wrap">
    <h1>Test Import Handler</h1>
    
    <?php
    // Test import handler
    if (isset($_POST['test_import'])) {
        // Security check
        if (!wp_verify_nonce($_POST['_wpnonce'], 'test_import')) {
            wp_die('Security check failed');
        }
        
        $test_file = MT_PLUGIN_DIR . 'data/templates/candidates.csv';
        
        if (file_exists($test_file)) {
            echo '<h2>Testing Import with: ' . basename($test_file) . '</h2>';
            
            // Create handler instance
            $handler = new \MobilityTrailblazers\Admin\MT_Import_Handler();
            
            // Process import
            $results = $handler->process_csv_import($test_file, 'candidates', false);
            
            // Display results
            echo '<div class="notice notice-info">';
            echo '<h3>Import Results:</h3>';
            echo '<ul>';
            echo '<li>Success: ' . $results['success'] . '</li>';
            echo '<li>Updated: ' . $results['updated'] . '</li>';
            echo '<li>Skipped: ' . $results['skipped'] . '</li>';
            echo '<li>Errors: ' . $results['errors'] . '</li>';
            echo '</ul>';
            
            if (!empty($results['messages'])) {
                echo '<h4>Messages:</h4>';
                echo '<ul>';
                foreach ($results['messages'] as $message) {
                    echo '<li>' . esc_html($message) . '</li>';
                }
                echo '</ul>';
            }
            
            if (!empty($results['error_details'])) {
                echo '<h4>Error Details:</h4>';
                echo '<ul>';
                foreach ($results['error_details'] as $error) {
                    echo '<li>Row ' . $error['row'] . ': ' . esc_html($error['error']) . '</li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
        } else {
            echo '<div class="notice notice-error"><p>Test file not found: ' . $test_file . '</p></div>';
        }
    }
    
    // Test with actual uploaded file
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        // Security check
        if (!wp_verify_nonce($_POST['_wpnonce'], 'test_upload')) {
            wp_die('Security check failed');
        }
        
        $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : 'candidates';
        $update_existing = isset($_POST['update_existing']);
        
        echo '<h2>Testing Import with uploaded file</h2>';
        
        // Create handler instance
        $handler = new \MobilityTrailblazers\Admin\MT_Import_Handler();
        
        // Process import
        $results = $handler->process_csv_import($_FILES['csv_file']['tmp_name'], $import_type, $update_existing);
        
        // Display results
        echo '<div class="notice notice-' . ($results['errors'] > 0 ? 'warning' : 'success') . '">';
        echo '<h3>Import Results:</h3>';
        echo '<ul>';
        echo '<li>Success: ' . $results['success'] . '</li>';
        echo '<li>Updated: ' . $results['updated'] . '</li>';
        echo '<li>Skipped: ' . $results['skipped'] . '</li>';
        echo '<li>Errors: ' . $results['errors'] . '</li>';
        echo '</ul>';
        
        if (!empty($results['messages'])) {
            echo '<h4>Messages:</h4>';
            echo '<ul>';
            foreach ($results['messages'] as $message) {
                echo '<li>' . esc_html($message) . '</li>';
            }
            echo '</ul>';
        }
        
        if (!empty($results['error_details'])) {
            echo '<h4>Error Details:</h4>';
            echo '<ul>';
            foreach ($results['error_details'] as $error) {
                echo '<li>Row ' . $error['row'] . ': ' . esc_html($error['error']) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '</div>';
    }
    ?>
    
    <div class="card">
        <h2>Test with Template File</h2>
        <form method="post">
            <?php wp_nonce_field('test_import'); ?>
            <p>This will test importing the candidates.csv template file.</p>
            <p class="submit">
                <button type="submit" name="test_import" class="button button-primary">
                    Test Import Template
                </button>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2>Test with Custom File</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('test_upload'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="import_type">Import Type</label></th>
                    <td>
                        <select name="import_type" id="import_type">
                            <option value="candidates">Candidates</option>
                            <option value="jury_members">Jury Members</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="csv_file">CSV File</label></th>
                    <td>
                        <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                    </td>
                </tr>
                <tr>
                    <th><label for="update_existing">Update Existing</label></th>
                    <td>
                        <input type="checkbox" name="update_existing" id="update_existing" value="1">
                        <label for="update_existing">Update existing records if found</label>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">
                    Test Import File
                </button>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2>Import Handler Information</h2>
        <?php
        if (class_exists('\MobilityTrailblazers\Admin\MT_Import_Handler')) {
            echo '<p class="notice notice-success">✓ MT_Import_Handler class is loaded</p>';
            
            $handler = new \MobilityTrailblazers\Admin\MT_Import_Handler();
            
            echo '<h3>Candidate Field Mapping:</h3>';
            echo '<pre>';
            print_r(\MobilityTrailblazers\Admin\MT_Import_Handler::CANDIDATE_FIELD_MAPPING);
            echo '</pre>';
            
            echo '<h3>Jury Field Mapping:</h3>';
            echo '<pre>';
            print_r(\MobilityTrailblazers\Admin\MT_Import_Handler::JURY_FIELD_MAPPING);
            echo '</pre>';
        } else {
            echo '<p class="notice notice-error">✗ MT_Import_Handler class is not loaded</p>';
        }
        ?>
    </div>
</div>

<style>
.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-top: 20px;
    padding: 20px;
}
.card h2 {
    margin-top: 0;
}
</style>