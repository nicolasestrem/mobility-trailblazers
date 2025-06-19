<?php
/**
 * Debug and Fix Functions for Mobility Trailblazers
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clean up existing assignment data
 * Run this ONCE to fix assignment data
 */
function mt_fix_assignment_data() {
    global $wpdb;
    
    echo "<h3>Cleaning up assignment data...</h3>";
    
    // Get all assignment metadata
    $results = $wpdb->get_results("
        SELECT post_id, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_mt_assigned_jury_members'
    ");
    
    $fixed = 0;
    $errors = 0;
    
    foreach ($results as $row) {
        $jury_ids = maybe_unserialize($row->meta_value);
        
        // Skip if not an array or empty
        if (!is_array($jury_ids) || empty($jury_ids)) {
            continue;
        }
        
        // Ensure all IDs are integers
        $clean_ids = array();
        foreach ($jury_ids as $id) {
            $clean_id = intval($id);
            if ($clean_id > 0) {
                $clean_ids[] = $clean_id;
            }
        }
        
        // Update if changed
        if ($clean_ids !== $jury_ids) {
            $updated = update_post_meta($row->post_id, '_mt_assigned_jury_members', $clean_ids);
            if ($updated) {
                $fixed++;
                echo "<p>Fixed candidate ID {$row->post_id}: " . implode(', ', $clean_ids) . "</p>";
            } else {
                $errors++;
            }
        }
    }
    
    echo "<p><strong>Fixed: $fixed assignments</strong></p>";
    echo "<p><strong>Errors: $errors</strong></p>";
    
    // Verify the fix
    echo "<h3>Verification:</h3>";
    
    // Check a few jury members
    $jury_members = get_posts(array(
        'post_type' => 'mt_jury_member',
        'posts_per_page' => 5,
        'post_status' => 'publish'
    ));
    
    foreach ($jury_members as $jury) {
        $assigned = mt_get_assigned_candidates($jury->ID);
        echo "<p>{$jury->post_title} (ID: {$jury->ID}): " . count($assigned) . " candidates assigned</p>";
    }
}

/**
 * Debug helper function for specific assignment issues
 *
 * @param int|null $candidate_id Candidate ID to debug
 * @param int|null $jury_id Jury member ID to debug
 */
function mt_debug_specific_assignment($candidate_id = null, $jury_id = null) {
    global $wpdb;
    
    if ($candidate_id) {
        $assigned_jury = get_post_meta($candidate_id, '_mt_assigned_jury_members', true);
        echo "<h4>Candidate ID $candidate_id assignments:</h4>";
        echo "<pre>";
        var_dump($assigned_jury);
        echo "</pre>";
        
        echo "<p>Serialized value in database:</p>";
        $raw = $wpdb->get_var($wpdb->prepare(
            "SELECT meta_value FROM {$wpdb->postmeta} 
             WHERE post_id = %d AND meta_key = '_mt_assigned_jury_members'",
            $candidate_id
        ));
        echo "<pre>" . htmlspecialchars($raw) . "</pre>";
    }
    
    if ($jury_id) {
        echo "<h4>Jury ID $jury_id assignments:</h4>";
        $candidates = mt_get_assigned_candidates($jury_id);
        echo "<p>Found " . count($candidates) . " candidates</p>";
        foreach ($candidates as $candidate) {
            echo "<p>- {$candidate->post_title} (ID: {$candidate->ID})</p>";
        }
    }
}

/**
 * Check if all required database tables exist
 *
 * @return array Table status information
 */
function mt_check_database_tables() {
    global $wpdb;
    
    $tables = array(
        'mt_votes' => $wpdb->prefix . 'mt_votes',
        'mt_candidate_scores' => $wpdb->prefix . 'mt_candidate_scores',
        'mt_evaluations' => $wpdb->prefix . 'mt_evaluations',
        'mt_jury_assignments' => $wpdb->prefix . 'mt_jury_assignments',
        'vote_reset_logs' => $wpdb->prefix . 'vote_reset_logs',
        'mt_vote_backups' => $wpdb->prefix . 'mt_vote_backups',
    );
    
    $status = array();
    foreach ($tables as $name => $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $columns = 0;
        if ($exists) {
            $columns = count($wpdb->get_results("SHOW COLUMNS FROM $table"));
        }
        $status[$table] = array(
            'exists' => $exists,
            'columns' => $columns,
            'name' => $name
        );
    }
    
    return $status;
}

/**
 * Fix database issues by forcing table creation
 *
 * @return array Results of the fix attempt
 */
function mt_fix_database_issues() {
    require_once MT_PLUGIN_DIR . 'includes/class-database.php';
    
    $database = new MT_Database();
    $database->force_create_tables();
    
    // Check results
    $status = mt_check_database_tables();
    
    $results = array(
        'success' => true,
        'created' => array(),
        'failed' => array()
    );
    
    foreach ($status as $table => $info) {
        if ($info['exists']) {
            $results['created'][] = $table;
        } else {
            $results['failed'][] = $table;
            $results['success'] = false;
        }
    }
    
    return $results;
}

/**
 * Debug output for database status
 *
 * @param bool $return Whether to return the output instead of echoing
 * @return string|void Debug output
 */
function mt_debug_database_status($return = false) {
    $status = mt_check_database_tables();
    
    $output = "<div class='mt-debug-database'>";
    $output .= "<h3>Database Table Status</h3>";
    $output .= "<table style='width: 100%; border-collapse: collapse;'>";
    $output .= "<tr><th style='text-align: left; padding: 5px; border: 1px solid #ccc;'>Table</th>";
    $output .= "<th style='text-align: left; padding: 5px; border: 1px solid #ccc;'>Status</th>";
    $output .= "<th style='text-align: left; padding: 5px; border: 1px solid #ccc;'>Columns</th></tr>";
    
    foreach ($status as $table => $info) {
        $status_text = $info['exists'] ? '<span style="color: green;">✓ Exists</span>' : '<span style="color: red;">✗ Missing</span>';
        $output .= "<tr>";
        $output .= "<td style='padding: 5px; border: 1px solid #ccc;'>{$table}</td>";
        $output .= "<td style='padding: 5px; border: 1px solid #ccc;'>{$status_text}</td>";
        $output .= "<td style='padding: 5px; border: 1px solid #ccc;'>{$info['columns']}</td>";
        $output .= "</tr>";
    }
    
    $output .= "</table>";
    $output .= "</div>";
    
    if ($return) {
        return $output;
    }
    
    echo $output;
}

/**
 * Add database status to health check
 */
add_filter('debug_information', function($info) {
    $database_status = mt_check_database_tables();
    
    $fields = array();
    foreach ($database_status as $table => $status) {
        $fields[$table] = array(
            'label' => $status['name'],
            'value' => $status['exists'] ? sprintf(__('%d columns', 'mobility-trailblazers'), $status['columns']) : __('Missing', 'mobility-trailblazers'),
            'debug' => $status
        );
    }
    
    $info['mobility-trailblazers-database'] = array(
        'label' => __('Mobility Trailblazers Database', 'mobility-trailblazers'),
        'description' => __('Status of plugin database tables', 'mobility-trailblazers'),
        'fields' => $fields
    );
    
    return $info;
});

// Add debug menu
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=mt_candidate',
        'Assignment Debug & Fix',
        'Assignment Debug & Fix',
        'manage_options',
        'mt-assignment-fix',
        function() {
            echo '<div class="wrap">';
            echo '<h1>Assignment Debug & Fix</h1>';
            
            // Run fix if requested
            if (isset($_GET['fix']) && $_GET['fix'] === '1') {
                mt_fix_assignment_data();
            } else {
                echo '<p><a href="' . add_query_arg('fix', '1') . '" class="button button-primary">Run Assignment Fix</a></p>';
            }
            
            // Debug specific items
            if (isset($_GET['debug_candidate'])) {
                mt_debug_specific_assignment(intval($_GET['debug_candidate']), null);
            }
            
            if (isset($_GET['debug_jury'])) {
                mt_debug_specific_assignment(null, intval($_GET['debug_jury']));
            }
            
            echo '</div>';
        }

        
    );
}); 