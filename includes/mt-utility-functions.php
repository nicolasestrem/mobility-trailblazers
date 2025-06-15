<?php
/**
 * Utility Functions
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get user evaluation count
 *
 * @param int $user_id User ID
 * @return int Number of evaluations completed
 */
function mt_get_user_evaluation_count($user_id) {
    global $wpdb;
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE jury_member_id = %d",
        $user_id
    ));
    
    return intval($count);
}

/**
 * Check if jury has evaluated a candidate
 *
 * @param int $user_id User ID
 * @param int $candidate_id Candidate ID
 * @return bool True if evaluated, false otherwise
 */
function mt_has_jury_evaluated($user_id, $candidate_id) {
    global $wpdb;
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores 
        WHERE jury_member_id = %d AND candidate_id = %d",
        $user_id,
        $candidate_id
    ));
    
    return $count > 0;
}

/**
 * Get jury scores for a candidate
 *
 * @param int $user_id User ID
 * @param int $candidate_id Candidate ID
 * @return array|null Score data or null if not found
 */
function mt_get_jury_scores($user_id, $candidate_id) {
    global $wpdb;
    
    $scores = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mt_candidate_scores 
        WHERE jury_member_id = %d AND candidate_id = %d",
        $user_id,
        $candidate_id
    ), ARRAY_A);
    
    return $scores;
}

/**
 * Safe strpos function for PHP 8 compatibility
 *
 * @param string $haystack The string to search in
 * @param string $needle The string to search for
 * @param int $offset Search offset
 * @return int|false Position or false if not found
 */
if (!function_exists('mt_safe_strpos')) {
    function mt_safe_strpos($haystack, $needle, $offset = 0) {
        if (empty($haystack) || empty($needle)) {
            return false;
        }
        return strpos($haystack, $needle, $offset);
    }
}

/**
 * Safe str_replace function for PHP 8 compatibility
 *
 * @param mixed $search Search value
 * @param mixed $replace Replace value
 * @param mixed $subject Subject string
 * @return string|array Replaced string or array
 */
if (!function_exists('mt_safe_str_replace')) {
    function mt_safe_str_replace($search, $replace, $subject) {
        if (empty($subject)) {
            return $subject;
        }
        return str_replace($search, $replace, $subject);
    }
}

/**
 * Safe plugin_basename function
 *
 * @param string $file File path
 * @return string Plugin basename
 */
if (!function_exists('mt_safe_plugin_basename')) {
    function mt_safe_plugin_basename($file = null) {
        if ($file === null) {
            $file = MT_PLUGIN_FILE;
        }
        
        // Ensure we have a valid file path
        if (empty($file) || !is_string($file)) {
            return '';
        }
        
        return plugin_basename($file);
    }
}

/**
 * Get total active votes
 *
 * @return int Total number of votes
 */
function mt_get_total_active_votes() {
    global $wpdb;
    return intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes"));
}

/**
 * Get total evaluations
 *
 * @return int Total number of evaluations
 */
function mt_get_total_evaluations() {
    global $wpdb;
    return intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores"));
}

/**
 * Get recent vote activity
 *
 * @param int $limit Number of recent activities to retrieve
 * @return array Recent vote activities
 */
function mt_get_recent_vote_activity($limit = 10) {
    global $wpdb;
    
    $activities = array();
    
    // Get recent votes
    $recent_votes = $wpdb->get_results($wpdb->prepare("
        SELECT v.*, c.post_title as candidate_name, j.post_title as jury_name
        FROM {$wpdb->prefix}mt_votes v
        LEFT JOIN {$wpdb->posts} c ON v.candidate_id = c.ID
        LEFT JOIN {$wpdb->posts} j ON j.ID = (
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'mt_jury' 
            AND ID IN (
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = 'user_id' 
                AND meta_value = v.jury_member_id
            )
            LIMIT 1
        )
        ORDER BY v.vote_date DESC
        LIMIT %d
    ", $limit));
    
    foreach ($recent_votes as $vote) {
        $activities[] = array(
            'type' => 'vote',
            'date' => $vote->vote_date,
            'jury_name' => $vote->jury_name ?: __('Unknown Jury', 'mobility-trailblazers'),
            'candidate_name' => $vote->candidate_name,
            'rating' => $vote->rating
        );
    }
    
    // Get recent evaluations
    $recent_evaluations = $wpdb->get_results($wpdb->prepare("
        SELECT s.*, c.post_title as candidate_name, j.post_title as jury_name
        FROM {$wpdb->prefix}mt_candidate_scores s
        LEFT JOIN {$wpdb->posts} c ON s.candidate_id = c.ID
        LEFT JOIN {$wpdb->posts} j ON j.ID = (
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'mt_jury' 
            AND ID IN (
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = 'user_id' 
                AND meta_value = s.jury_member_id
            )
            LIMIT 1
        )
        ORDER BY s.evaluation_date DESC
        LIMIT %d
    ", $limit));
    
    foreach ($recent_evaluations as $eval) {
        $activities[] = array(
            'type' => 'evaluation',
            'date' => $eval->evaluation_date,
            'jury_name' => $eval->jury_name ?: __('Unknown Jury', 'mobility-trailblazers'),
            'candidate_name' => $eval->candidate_name,
            'total_score' => $eval->total_score
        );
    }
    
    // Sort by date
    usort($activities, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    return array_slice($activities, 0, $limit);
}

/**
 * Get candidates for assignment
 *
 * @return array Array of candidate posts
 */
function mt_get_candidates_for_assignment() {
    return get_posts(array(
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ));
}

/**
 * Get jury members for assignment
 *
 * @return array Array of jury member posts
 */
function mt_get_jury_members_for_assignment() {
    return get_posts(array(
        'post_type' => 'mt_jury',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ));
}

/**
 * Get existing assignments
 *
 * @return array Array of assignments by jury member ID
 */
function mt_get_existing_assignments() {
    $assignments = array();
    
    $jury_members = mt_get_jury_members_for_assignment();
    
    foreach ($jury_members as $jury) {
        $assigned = get_post_meta($jury->ID, 'assigned_candidates', true);
        if (is_array($assigned) && !empty($assigned)) {
            $assignments[$jury->ID] = $assigned;
        }
    }
    
    return $assignments;
}

/**
 * Get jury member for user
 *
 * @param int $user_id User ID
 * @return WP_Post|null Jury member post or null
 */
function mt_get_jury_member_for_user($user_id) {
    $args = array(
        'post_type' => 'mt_jury',
        'meta_key' => 'user_id',
        'meta_value' => $user_id,
        'posts_per_page' => 1,
        'post_status' => 'any'
    );
    
    $jury_members = get_posts($args);
    return !empty($jury_members) ? $jury_members[0] : null;
}

/**
 * Get assigned candidates for jury member
 *
 * @param int $jury_member_id Jury member post ID
 * @return array Array of candidate IDs
 */
function mt_get_assigned_candidates($jury_member_id) {
    $assigned = get_post_meta($jury_member_id, 'assigned_candidates', true);
    
    if (!is_array($assigned)) {
        return array();
    }
    
    // Filter out any invalid IDs
    return array_filter($assigned, function($id) {
        return get_post($id) && get_post_type($id) === 'mt_candidate';
    });
}

/**
 * Get jury dashboard page URL
 *
 * @return string Dashboard URL
 */
function mt_get_jury_dashboard_url() {
    $dashboard_page_id = get_option('mt_jury_dashboard_page');
    
    if ($dashboard_page_id) {
        $url = get_permalink($dashboard_page_id);
        if ($url) {
            return $url;
        }
    }
    
    // Fallback to admin page
    return admin_url('admin.php?page=mt-jury-evaluation');
}

/**
 * Get assigned candidates count for jury
 *
 * @param int $jury_id Jury member post ID
 * @return int Number of assigned candidates
 */
function mt_get_assigned_candidates_count($jury_id) {
    $assigned = get_post_meta($jury_id, 'assigned_candidates', true);
    return is_array($assigned) ? count($assigned) : 0;
}

/**
 * Get completed evaluations count for user
 *
 * @param int $user_id User ID
 * @return int Number of completed evaluations
 */
function mt_get_completed_evaluations_count($user_id) {
    global $wpdb;
    
    return intval($wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE jury_member_id = %d",
        $user_id
    )));
}

/**
 * Diagnostic function to check plugin status
 *
 * @return array Status information
 */
function mt_diagnostic_check() {
    $status = array(
        'post_types_registered' => array(),
        'capabilities_check' => array(),
        'database_tables' => array(),
        'classes_loaded' => array()
    );
    
    // Check post types
    $post_types = array('mt_candidate', 'mt_jury', 'mt_backup');
    foreach ($post_types as $post_type) {
        $status['post_types_registered'][$post_type] = post_type_exists($post_type);
    }
    
    // Check capabilities
    $capabilities = array(
        'edit_mt_candidates',
        'edit_mt_jurys', 
        'mt_manage_awards',
        'mt_manage_assignments',
        'mt_view_all_evaluations'
    );
    foreach ($capabilities as $cap) {
        $status['capabilities_check'][$cap] = current_user_can($cap);
    }
    
    // Check database tables
    global $wpdb;
    $tables = array(
        'mt_votes' => $wpdb->prefix . 'mt_votes',
        'mt_candidate_scores' => $wpdb->prefix . 'mt_candidate_scores',
        'vote_reset_logs' => $wpdb->prefix . 'vote_reset_logs'
    );
    foreach ($tables as $name => $table) {
        $status['database_tables'][$name] = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }
    
    // Check classes
    $classes = array(
        'MT_Post_Types',
        'MT_Taxonomies', 
        'MT_Database',
        'MT_Roles',
        'MT_Admin_Menus',
        'MT_AJAX_Handlers',
        'MT_Vote_Reset_Manager'
    );
    foreach ($classes as $class) {
        $status['classes_loaded'][$class] = class_exists($class);
    }
    
    return $status;
}

/**
 * Display diagnostic information (for debugging)
 */
function mt_display_diagnostic() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $status = mt_diagnostic_check();
    
    echo '<div style="background: #f1f1f1; padding: 10px; margin: 10px 0; font-family: monospace;">';
    echo '<h3>MT Plugin Diagnostic</h3>';
    
    foreach ($status as $category => $items) {
        echo '<h4>' . ucwords(str_replace('_', ' ', $category)) . '</h4>';
        echo '<ul>';
        foreach ($items as $item => $result) {
            $color = $result ? 'green' : 'red';
            $text = $result ? 'OK' : 'FAIL';
            echo "<li><span style='color: $color;'>[$text]</span> $item</li>";
        }
        echo '</ul>';
    }
    
    echo '</div>';
} 