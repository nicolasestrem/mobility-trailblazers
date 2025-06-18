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
 * Fix database issues
 * This function can be called manually to fix database problems
 */
function mt_fix_database_issues() {
    global $wpdb;
    
    // Check if database class exists
    if (!class_exists('MT_Database')) {
        require_once MT_PLUGIN_DIR . 'includes/class-database.php';
    }
    
    $database = new MT_Database();
    
    // Force create tables
    $database->force_create_tables();
    
    // Check if tables were created successfully
    $tables_to_check = array(
        $wpdb->prefix . 'mt_jury_assignments',
        $wpdb->prefix . 'mt_evaluations',
        $wpdb->prefix . 'mt_votes',
        $wpdb->prefix . 'mt_candidate_scores'
    );
    
    $results = array();
    foreach ($tables_to_check as $table) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        $results[$table] = $exists;
    }
    
    return $results;
}

/**
 * Check if all required database tables exist
 *
 * @return array Array of table existence status
 */
function mt_check_database_tables() {
    global $wpdb;
    
    $tables_to_check = array(
        'mt_jury_assignments' => $wpdb->prefix . 'mt_jury_assignments',
        'mt_evaluations' => $wpdb->prefix . 'mt_evaluations',
        'mt_votes' => $wpdb->prefix . 'mt_votes',
        'mt_candidate_scores' => $wpdb->prefix . 'mt_candidate_scores',
        'vote_reset_logs' => $wpdb->prefix . 'vote_reset_logs',
        'mt_vote_backups' => $wpdb->prefix . 'mt_vote_backups'
    );
    
    $results = array();
    foreach ($tables_to_check as $table_name => $full_table_name) {
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") === $full_table_name;
        $results[$table_name] = array(
            'exists' => $exists,
            'full_name' => $full_table_name
        );
        
        if ($exists) {
            // Get table structure info
            $columns = $wpdb->get_results("SHOW COLUMNS FROM $full_table_name");
            $results[$table_name]['columns'] = count($columns);
        }
    }
    
    return $results;
}

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