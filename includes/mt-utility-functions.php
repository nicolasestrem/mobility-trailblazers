<?php
/**
 * Utility functions for Mobility Trailblazers plugin
 *
 * @package MobilityTrailblazers
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get current award year
 *
 * @return string Current award year
 */
function mt_get_current_award_year() {
    return get_option('mt_current_award_year', date('Y'));
}

/**
 * Get current award phase
 *
 * @return string Current award phase
 */
function mt_get_current_phase() {
    return get_option('mt_current_phase', 'nomination');
}

/**
 * Check if public voting is enabled
 *
 * @return bool Whether public voting is enabled
 */
function mt_is_public_voting_enabled() {
    return (bool) get_option('mt_public_voting_enabled', false);
}

/**
 * Check if registration is open
 *
 * @return bool Whether registration is open
 */
function mt_is_registration_open() {
    return (bool) get_option('mt_registration_open', true);
}

/**
 * Get evaluation criteria
 *
 * @return array Evaluation criteria
 */
function mt_get_evaluation_criteria() {
    return array(
        'courage' => array(
            'label' => __('Mut & Pioniergeist', 'mobility-trailblazers'),
            'description' => __('Courage and pioneer spirit in mobility innovation', 'mobility-trailblazers'),
            'max_score' => 10,
        ),
        'innovation' => array(
            'label' => __('Innovationsgrad', 'mobility-trailblazers'),
            'description' => __('Degree of innovation and uniqueness', 'mobility-trailblazers'),
            'max_score' => 10,
        ),
        'implementation' => array(
            'label' => __('Umsetzungskraft & Wirkung', 'mobility-trailblazers'),
            'description' => __('Implementation strength and impact', 'mobility-trailblazers'),
            'max_score' => 10,
        ),
        'relevance' => array(
            'label' => __('Relevanz für Mobilitätswende', 'mobility-trailblazers'),
            'description' => __('Relevance for mobility transformation', 'mobility-trailblazers'),
            'max_score' => 10,
        ),
        'visibility' => array(
            'label' => __('Vorbildfunktion & Sichtbarkeit', 'mobility-trailblazers'),
            'description' => __('Role model function and visibility', 'mobility-trailblazers'),
            'max_score' => 10,
        ),
    );
}

/**
 * Calculate total score from criteria scores
 *
 * @param array $scores Criteria scores
 * @return int Total score
 */
function mt_calculate_total_score($scores) {
    $total = 0;
    
    if (is_array($scores)) {
        foreach ($scores as $score) {
            $total += intval($score);
        }
    }
    
    return $total;
}

/**
 * Get jury member by user ID
 *
 * @param int $user_id User ID
 * @return WP_Post|null Jury member post or null
 */
function mt_get_jury_member_by_user_id($user_id) {
    // First try the underscore prefix version (used by diagnostic tool)
    $args = array(
        'post_type' => 'mt_jury_member',
        'meta_key' => '_mt_user_id',
        'meta_value' => $user_id,
        'posts_per_page' => 1,
        'post_status' => 'publish'
    );
    
    $jury_members = get_posts($args);
    
    // If not found, try without underscore (legacy)
    if (empty($jury_members)) {
        $args['meta_key'] = 'mt_jury_user_id';
        $jury_members = get_posts($args);
    }
    
    // If still not found, check user meta for linked jury member
    if (empty($jury_members)) {
        $jury_member_id = get_user_meta($user_id, '_mt_jury_member_id', true);
        if ($jury_member_id) {
            $jury_member = get_post($jury_member_id);
            if ($jury_member && $jury_member->post_type === 'mt_jury_member' && $jury_member->post_status === 'publish') {
                return $jury_member;
            }
        }
    }
    
    return !empty($jury_members) ? $jury_members[0] : null;
}

/**
 * Check if current user is a jury member
 *
 * @param int $user_id Optional user ID, defaults to current user
 * @return bool Whether user is a jury member
 */
function mt_is_jury_member($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Check if user has jury member role
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return false;
    }
    
    // Check for jury member role
    if (in_array('mt_jury_member', $user->roles)) {
        return true;
    }
    
    // Check if user has jury member capability
    if (user_can($user_id, 'mt_access_jury_dashboard')) {
        return true;
    }
    
    // Check if user is associated with a jury member post
    $jury_member = mt_get_jury_member_by_user_id($user_id);
    if ($jury_member) {
        return true;
    }
    
    return false;
}

/**
 * Get assigned candidates for a jury member
 * This function now has a fallback method to check post meta if the table doesn't exist
 *
 * @param int $jury_member_id Jury member ID
 * @return array Array of candidate IDs
 */
function mt_get_assigned_candidates($jury_member_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mt_jury_assignments';
    
    // Check if the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        // Use the table if it exists
        $candidates = $wpdb->get_col($wpdb->prepare(
            "SELECT candidate_id FROM $table_name WHERE jury_member_id = %d AND is_active = 1",
            $jury_member_id
        ));
        return array_map('intval', $candidates);
    } else {
        // Fallback: Query candidates by post meta
        $args = array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => array(
                array(
                    'key' => '_mt_assigned_jury_members',
                    'value' => serialize(strval($jury_member_id)),
                    'compare' => 'LIKE'
                )
            ),
            'fields' => 'ids'
        );
        
        $candidates = get_posts($args);
        
        // Double-check by loading the meta and verifying
        $verified_candidates = array();
        foreach ($candidates as $candidate_id) {
            $assigned_jury = get_post_meta($candidate_id, '_mt_assigned_jury_members', true);
            if (is_array($assigned_jury) && in_array($jury_member_id, array_map('intval', $assigned_jury))) {
                $verified_candidates[] = $candidate_id;
            }
        }
        
        return $verified_candidates;
    }
}

/**
 * Get jury members assigned to candidate
 *
 * @param int $candidate_id Candidate ID
 * @return array Array of jury member IDs
 */
function mt_get_assigned_jury_members($candidate_id) {
    $jury_members = get_post_meta($candidate_id, '_mt_assigned_jury_members', true);
    
    if (!is_array($jury_members)) {
        $jury_members = array();
    }
    
    // Ensure all IDs are integers
    return array_map('intval', $jury_members);
}

/**
 * Check if jury member has evaluated candidate
 *
 * @param int $candidate_id Candidate ID
 * @param int $jury_member_id Jury member ID
 * @return bool Whether evaluation exists
 */
function mt_has_evaluated($candidate_id, $jury_member_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mt_evaluations';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE candidate_id = %d AND jury_member_id = %d",
            $candidate_id,
            $jury_member_id
        ));
        
        return $exists > 0;
    }
    
    // Fallback: check post meta
    $evaluation = get_post_meta($candidate_id, '_mt_evaluation_' . $jury_member_id, true);
    return !empty($evaluation);
}

/**
 * Get evaluation for candidate by jury member
 *
 * @param int $candidate_id Candidate ID
 * @param int $jury_member_id Jury member ID
 * @return object|null Evaluation object or null
 */
function mt_get_evaluation($candidate_id, $jury_member_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mt_evaluations';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        $evaluation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE candidate_id = %d AND jury_member_id = %d ORDER BY created_at DESC LIMIT 1",
            $candidate_id,
            $jury_member_id
        ));
        return $evaluation;
    }
    
    // Fallback: get from post meta
    $evaluation_data = get_post_meta($candidate_id, '_mt_evaluation_' . $jury_member_id, true);
    if ($evaluation_data) {
        // Convert array to object format
        return (object) $evaluation_data;
    }
    
    return null;
}

/**
 * Format date according to plugin settings
 *
 * @param string $date Date string
 * @param string $format Optional format override
 * @return string Formatted date
 */
function mt_format_date($date, $format = null) {
    if (null === $format) {
        $format = get_option('mt_date_format', get_option('date_format'));
    }
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    
    return date_i18n($format, $timestamp);
}

/**
 * Get status label
 *
 * @param string $status Status slug
 * @return string Status label
 */
function mt_get_status_label($status) {
    $statuses = array(
        'active' => __('Active', 'mobility-trailblazers'),
        'inactive' => __('Inactive', 'mobility-trailblazers'),
        'pending' => __('Pending', 'mobility-trailblazers'),
        'approved' => __('Approved', 'mobility-trailblazers'),
        'rejected' => __('Rejected', 'mobility-trailblazers'),
        'shortlisted' => __('Shortlisted', 'mobility-trailblazers'),
        'winner' => __('Winner', 'mobility-trailblazers'),
    );
    
    return isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
}

/**
 * Get phase label
 *
 * @param string $phase Phase slug
 * @return string Phase label
 */
function mt_get_phase_label($phase) {
    $phases = array(
        'nomination' => __('Nomination', 'mobility-trailblazers'),
        'screening' => __('Screening', 'mobility-trailblazers'),
        'evaluation' => __('Evaluation', 'mobility-trailblazers'),
        'selection' => __('Selection', 'mobility-trailblazers'),
        'announcement' => __('Announcement', 'mobility-trailblazers'),
    );
    
    return isset($phases[$phase]) ? $phases[$phase] : ucfirst($phase);
}

/**
 * Log activity
 *
 * @param string $message Log message
 * @param string $level Log level (info, warning, error)
 * @param array $context Additional context
 */
function mt_log($message, $level = 'info', $context = array()) {
    if (!defined('MT_DEBUG') || !MT_DEBUG) {
        return;
    }
    
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'level' => $level,
        'message' => $message,
        'context' => $context,
        'user_id' => get_current_user_id(),
    );
    
    // Log to error log in debug mode
    error_log('[MT ' . strtoupper($level) . '] ' . $message . ' ' . json_encode($context));
    
    // Fire action for custom logging
    do_action('mt_log_activity', $log_entry);
}

/**
 * Get formatted score display
 *
 * @param float $score Score value
 * @param int $max_score Maximum possible score
 * @return string Formatted score display
 */
function mt_format_score($score, $max_score = 10) {
    return sprintf('%.1f / %d', floatval($score), intval($max_score));
}

/**
 * Check if user can evaluate candidate
 *
 * @param int $candidate_id Candidate ID
 * @param int $user_id User ID (optional, defaults to current user)
 * @return bool Whether user can evaluate
 */
function mt_user_can_evaluate($candidate_id, $user_id = null) {
    if (null === $user_id) {
        $user_id = get_current_user_id();
    }
    
    // Check basic capability
    if (!user_can($user_id, 'mt_submit_evaluations')) {
        return false;
    }
    
    // Get jury member
    $jury_member = mt_get_jury_member_by_user_id($user_id);
    if (!$jury_member) {
        return false;
    }
    
    // Check if candidate is assigned
    $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
    
    return in_array($candidate_id, $assigned_candidates);
}

/**
 * Sanitize score value
 *
 * @param mixed $score Score value
 * @param int $min Minimum allowed score
 * @param int $max Maximum allowed score
 * @return int Sanitized score
 */
function mt_sanitize_score($score, $min = 1, $max = 10) {
    $score = intval($score);
    
    if ($score < $min) {
        return $min;
    }
    
    if ($score > $max) {
        return $max;
    }
    
    return $score;
}

/**
 * Get evaluation statistics
 *
 * @param array $args Query arguments
 * @return array Statistics data
 */
function mt_get_evaluation_statistics($args = array()) {
    global $wpdb;
    
    $defaults = array(
        'candidate_id' => 0,
        'jury_member_id' => 0,
        'category_id' => 0,
        'phase' => '',
        'date_from' => '',
        'date_to' => '',
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Initialize stats array
    $stats = array(
        'total_evaluations' => 0,
        'average_score' => 0,
        'min_score' => 0,
        'max_score' => 0,
        'completion_rate' => 0,
        'by_criteria' => array(),
        'by_date' => array(),
        'by_category' => array(),
    );
    
    $table_name = $wpdb->prefix . 'mt_evaluations';
    
    // Check if table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        // Build WHERE clause
        $where = array('1=1');
        
        if ($args['candidate_id']) {
            $where[] = $wpdb->prepare('candidate_id = %d', $args['candidate_id']);
        }
        
        if ($args['jury_member_id']) {
            $where[] = $wpdb->prepare('jury_member_id = %d', $args['jury_member_id']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get basic statistics
        $basic_stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_evaluations,
                AVG(total_score) as average_score,
                MIN(total_score) as min_score,
                MAX(total_score) as max_score,
                AVG(courage_score) as avg_courage,
                AVG(innovation_score) as avg_innovation,
                AVG(implementation_score) as avg_implementation,
                AVG(relevance_score) as avg_relevance,
                AVG(visibility_score) as avg_visibility
            FROM $table_name
            WHERE $where_clause
        ");
        
        if ($basic_stats) {
            $stats = array_merge($stats, (array) $basic_stats);
        }
        
        // Get evaluations by date
        $date_stats = $wpdb->get_results("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
            FROM $table_name
            WHERE $where_clause
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        
        if ($date_stats) {
            foreach ($date_stats as $day) {
                $stats['by_date'][$day->date] = $day->count;
            }
        }
        
        // Get evaluations by category
        $category_stats = $wpdb->get_results("
            SELECT 
                t.name as category,
                COUNT(DISTINCT p.ID) as candidates,
                COUNT(e.id) as evaluations,
                AVG(e.total_score) as avg_score,
                (COUNT(e.id) / COUNT(DISTINCT p.ID)) * 100 as completion_rate
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
            LEFT JOIN $table_name e ON p.ID = e.candidate_id
            WHERE p.post_type = 'mt_candidate'
            AND p.post_status = 'publish'
            AND tt.taxonomy = 'mt_category'
            GROUP BY t.term_id
        ");
        
        if ($category_stats) {
            foreach ($category_stats as $cat) {
                $stats['by_category'][$cat->category] = array(
                    'candidates' => $cat->candidates,
                    'evaluations' => $cat->evaluations,
                    'avg_score' => $cat->avg_score,
                    'completion_rate' => $cat->completion_rate,
                );
            }
        }
        
        // Calculate criteria averages
        $stats['by_criteria'] = array(
            'courage' => floatval($stats['avg_courage'] ?? 0),
            'innovation' => floatval($stats['avg_innovation'] ?? 0),
            'implementation' => floatval($stats['avg_implementation'] ?? 0),
            'relevance' => floatval($stats['avg_relevance'] ?? 0),
            'visibility' => floatval($stats['avg_visibility'] ?? 0),
        );
    }
    
    // Calculate completion rate
    $stats['total_candidates'] = wp_count_posts('mt_candidate')->publish;
    $stats['completion_rate'] = $stats['total_candidates'] > 0 
        ? round(($stats['total_evaluations'] / $stats['total_candidates']) * 100, 1)
        : 0;
    
    return apply_filters('mt_evaluation_statistics', $stats, $args);
}

/**
 * Check if user has draft evaluation
 *
 * @param int $candidate_id Candidate ID
 * @param int $jury_member_id Jury member ID
 * @return bool Whether draft exists
 */
function mt_has_draft_evaluation($candidate_id, $jury_member_id) {
    $jury_user_id = get_post_meta($jury_member_id, '_mt_user_id', true);
    if (!$jury_user_id) {
        return false;
    }
    
    $draft = get_user_meta($jury_user_id, 'mt_evaluation_draft_' . $candidate_id, true);
    return !empty($draft);
}

/**
 * Get draft evaluations for a jury member
 *
 * @param int $jury_member_id Jury member ID
 * @return array Array of candidate IDs with drafts
 */
function mt_get_draft_evaluations($jury_member_id) {
    $draft_candidates = array();
    
    // Get the user ID for the jury member
    $jury_user_id = get_post_meta($jury_member_id, '_mt_user_id', true);
    
    if (!$jury_user_id) {
        return $draft_candidates;
    }
    
    // Get all user meta keys for this user
    $user_meta = get_user_meta($jury_user_id);
    
    foreach ($user_meta as $key => $value) {
        // Check if this is a draft evaluation key
        if (strpos($key, 'mt_evaluation_draft_') === 0) {
            $candidate_id = str_replace('mt_evaluation_draft_', '', $key);
            if (is_numeric($candidate_id) && !empty($value[0])) {
                $draft_candidates[] = intval($candidate_id);
            }
        }
    }
    
    return $draft_candidates;
}

/**
 * Get user evaluation count
 * @param int $user_id User ID
 * @return int Number of evaluations
 */
function mt_get_user_evaluation_count($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mt_candidate_scores';
    
    // Check if table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        return intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE jury_id = %d",
            $user_id
        )));
    }
    
    // Fallback: count evaluations using the mt_evaluations table
    $table_name = $wpdb->prefix . 'mt_evaluations';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
        // Get jury member by user ID
        $jury_member = mt_get_jury_member_by_user_id($user_id);
        if ($jury_member) {
            return intval($wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE jury_member_id = %d",
                $jury_member->ID
            )));
        }
    }
    
    return 0;
}

/**
 * Get user assignments count
 * @param int $user_id User ID
 * @return int Number of assignments
 */
function mt_get_user_assignments_count($user_id) {
    // Get jury member by user ID
    $jury_member = mt_get_jury_member_by_user_id($user_id);
    if (!$jury_member) {
        return 0;
    }
    
    // Use existing function to get assigned candidates
    $assigned_candidates = mt_get_assigned_candidates($jury_member->ID);
    return count($assigned_candidates);
}

/**
 * Get the jury member post type name
 * This function provides backward compatibility during transition
 * 
 * @return string
 */
function mt_get_jury_post_type() {
    // Since migration is complete, always return mt_jury_member
    return 'mt_jury_member';
}

/**
 * Get jury member capability
 * This function provides backward compatibility for capabilities
 * 
 * @param string $base_cap Base capability name (e.g., 'edit', 'delete')
 * @param bool $plural Whether to return plural form
 * @return string
 */
function mt_get_jury_capability($base_cap, $plural = false) {
    $post_type = mt_get_jury_post_type();
    
    if ($post_type === 'mt_jury_member') {
        return $base_cap . '_' . ($plural ? 'mt_jury_members' : 'mt_jury_member');
    } else {
        return $base_cap . '_' . ($plural ? 'mt_jurys' : 'mt_jury');
    }
}

/**
 * Get jury member user meta key
 * 
 * @return string
 */
function mt_mt_mt_get_jury_member_meta_key() {
    if (get_option('mt_jury_nomenclature_migrated', false)) {
        return '_mt_jury_member_id';
    }
    return '_mt_jury_member_id';
}

/**
 * Get user ID by jury member post ID
 *
 * @param int $jury_member_id Jury member post ID
 * @return int|false User ID or false if not found
 */
function mt_get_user_id_by_jury_member($jury_member_id) {
    // First try with underscore prefix (current standard)
    $user_id = get_post_meta($jury_member_id, '_mt_user_id', true);
    
    // If not found, try without underscore (legacy)
    if (!$user_id) {
        $user_id = get_post_meta($jury_member_id, 'mt_jury_user_id', true);
    }
    
    return $user_id ? intval($user_id) : false;
}