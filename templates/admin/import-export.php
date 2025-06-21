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

// Handle export
if (isset($_GET['export']) && wp_verify_nonce($_GET['_wpnonce'], 'mt_export')) {
    $export_type = sanitize_text_field($_GET['export']);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="mt_' . $export_type . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    switch ($export_type) {
        case 'candidates':
            // Export candidates
            fputcsv($output, ['ID', 'Name', 'Biography', 'Category', 'Status']);
            
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => -1,
                'post_status' => 'any'
            ]);
            
            foreach ($candidates as $candidate) {
                $categories = wp_get_post_terms($candidate->ID, 'mt_award_category', ['fields' => 'names']);
                fputcsv($output, [
                    $candidate->ID,
                    $candidate->post_title,
                    wp_strip_all_tags($candidate->post_content),
                    implode(', ', $categories),
                    $candidate->post_status
                ]);
            }
            break;
            
        case 'evaluations':
            // Export evaluations
            $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
            $evaluations = $evaluation_repo->find_all();
            
            fputcsv($output, [
                'ID', 'Jury Member', 'Candidate', 'Courage Score', 'Innovation Score', 
                'Implementation Score', 'Relevance Score', 'Visibility Score', 
                'Total Score', 'Status', 'Comments', 'Date'
            ]);
            
            foreach ($evaluations as $evaluation) {
                $jury = get_post($evaluation->jury_member_id);
                $candidate = get_post($evaluation->candidate_id);
                
                fputcsv($output, [
                    $evaluation->id,
                    $jury ? $jury->post_title : 'Unknown',
                    $candidate ? $candidate->post_title : 'Unknown',
                    $evaluation->courage_score,
                    $evaluation->innovation_score,
                    $evaluation->implementation_score,
                    $evaluation->relevance_score,
                    $evaluation->visibility_score,
                    $evaluation->total_score,
                    $evaluation->status,
                    $evaluation->comments,
                    $evaluation->created_at
                ]);
            }
            break;
    }
    
    fclose($output);
    exit;
}

// Handle import
$import_message = '';
if (isset($_POST['import']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_import')) {
    if (!empty($_FILES['csv_file']['tmp_name'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        $import_type = sanitize_text_field($_POST['import_type']);
        
        if (($handle = fopen($file, 'r')) !== FALSE) {
            $headers = fgetcsv($handle); // Skip header row
            $imported = 0;
            
            switch ($import_type) {
                case 'candidates':
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        $post_data = [
                            'post_title' => $data[1],
                            'post_content' => $data[2],
                            'post_type' => 'mt_candidate',
                            'post_status' => 'publish'
                        ];
                        
                        $post_id = wp_insert_post($post_data);
                        
                        if ($post_id && !empty($data[3])) {
                            // Set category
                            $categories = array_map('trim', explode(',', $data[3]));
                            wp_set_object_terms($post_id, $categories, 'mt_award_category');
                        }
                        
                        $imported++;
                    }
                    break;
            }
            
            fclose($handle);
            $import_message = sprintf(__('Successfully imported %d records.', 'mobility-trailblazers'), $imported);
        }
    }
}
?>

<div class="wrap">
    <h1><?php _e('Import / Export', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($import_message) : ?>
        <div class="notice notice-success">
            <p><?php echo esc_html($import_message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Export Section -->
    <div class="card">
        <h2><?php _e('Export Data', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Export your data as CSV files for backup or analysis.', 'mobility-trailblazers'); ?></p>
        
        <p>
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=mt-import-export&export=candidates'), 'mt_export'); ?>" 
               class="button button-primary">
                <?php _e('Export Candidates', 'mobility-trailblazers'); ?>
            </a>
            
            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=mt-import-export&export=evaluations'), 'mt_export'); ?>" 
               class="button button-primary">
                <?php _e('Export Evaluations', 'mobility-trailblazers'); ?>
            </a>
        </p>
    </div>
    
    <!-- Import Section -->
    <div class="card">
        <h2><?php _e('Import Data', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Import candidates from a CSV file.', 'mobility-trailblazers'); ?></p>
        
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('mt_import'); ?>
            <input type="hidden" name="import" value="1">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="import_type"><?php _e('Import Type', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <select name="import_type" id="import_type">
                            <option value="candidates"><?php _e('Candidates', 'mobility-trailblazers'); ?></option>
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
                            <?php _e('CSV format for candidates: ID, Name, Biography, Category, Status', 'mobility-trailblazers'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Import', 'mobility-trailblazers'); ?></button>
            </p>
        </form>
    </div>
    
    <!-- Data Management -->
    <div class="card">
        <h2><?php _e('Data Management', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('Manage your plugin data.', 'mobility-trailblazers'); ?></p>
        
        <p>
            <button class="button button-secondary" id="clear-evaluations">
                <?php _e('Clear All Evaluations', 'mobility-trailblazers'); ?>
            </button>
            <span class="description"><?php _e('Warning: This action cannot be undone!', 'mobility-trailblazers'); ?></span>
        </p>
        
        <p>
            <button class="button button-secondary" id="clear-assignments">
                <?php _e('Clear All Assignments', 'mobility-trailblazers'); ?>
            </button>
            <span class="description"><?php _e('Warning: This action cannot be undone!', 'mobility-trailblazers'); ?></span>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#clear-evaluations').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to clear all evaluations? This cannot be undone!', 'mobility-trailblazers'); ?>')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'mt_clear_data',
            type: 'evaluations',
            nonce: '<?php echo wp_create_nonce('mt_clear_data'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e('All evaluations have been cleared.', 'mobility-trailblazers'); ?>');
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    $('#clear-assignments').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to clear all assignments? This cannot be undone!', 'mobility-trailblazers'); ?>')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'mt_clear_data',
            type: 'assignments',
            nonce: '<?php echo wp_create_nonce('mt_clear_data'); ?>'
        }, function(response) {
            if (response.success) {
                alert('<?php _e('All assignments have been cleared.', 'mobility-trailblazers'); ?>');
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
});
</script> 