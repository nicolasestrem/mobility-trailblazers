<?php
/**
 * Utility functions for Mobility Trailblazers plugin
 *
 * @package MobilityTrailblazers
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the current voting round
 *
 * @return int
 */
function mt_get_current_vote_round() {
    return (int) get_option('mt_current_vote_round', 1);
}

/**
 * Check if voting is currently active
 *
 * @return bool
 */
function mt_is_voting_active() {
    $start = get_option('mt_voting_start');
    $end = get_option('mt_voting_end');
    
    if (!$start || !$end) {
        return false;
    }
    
    $now = current_time('mysql');
    return ($now >= $start && $now <= $end);
}

/**
 * Get evaluation criteria
 *
 * @return array
 */
function mt_get_evaluation_criteria() {
    return get_option('mt_evaluation_criteria', array(
        'innovation' => array(
            'label' => 'Innovation',
            'description' => 'How innovative is the solution?'
        ),
        'impact' => array(
            'label' => 'Impact',
            'description' => 'What is the potential impact of the solution?'
        ),
        'feasibility' => array(
            'label' => 'Feasibility',
            'description' => 'How feasible is the implementation?'
        ),
        'sustainability' => array(
            'label' => 'Sustainability',
            'description' => 'How sustainable is the solution?'
        )
    ));
}

/**
 * Get jury member assignments
 *
 * @param int $jury_member_id
 * @return array
 */
function mt_get_jury_assignments($jury_member_id) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT c.*, jm.category_id 
        FROM {$wpdb->prefix}mt_jury_members jm 
        JOIN {$wpdb->posts} c ON jm.candidate_id = c.ID 
        WHERE jm.jury_member_id = %d 
        AND c.post_type = 'mt_candidate' 
        AND c.post_status = 'publish'",
        $jury_member_id
    ));
}

/**
 * Log an action to the audit log
 *
 * @param string $action
 * @param string $details
 * @param int $user_id
 * @return bool
 */
function mt_log_action($action, $details, $user_id = null) {
    global $wpdb;
    
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return $wpdb->insert(
        $wpdb->prefix . 'mt_vote_audit_log',
        array(
            'action' => $action,
            'details' => $details,
            'user_id' => $user_id,
            'timestamp' => current_time('mysql')
        ),
        array('%s', '%s', '%d', '%s')
    );
}

/**
 * Get recent activity from audit log
 *
 * @param int $limit
 * @return array
 */
function mt_get_recent_activity($limit = 10) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT l.*, u.display_name 
        FROM {$wpdb->prefix}mt_vote_audit_log l 
        LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
        ORDER BY l.timestamp DESC 
        LIMIT %d",
        $limit
    ));
}

/**
 * Check if user is a jury member
 *
 * @param int $user_id
 * @return bool
 */
function mt_is_jury_member($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return user_can($user_id, 'mt_jury_member');
}

/**
 * Check if user is an admin
 *
 * @param int $user_id
 * @return bool
 */
function mt_is_admin($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    return user_can($user_id, 'mt_admin');
}

/**
 * Get candidate evaluation
 *
 * @param int $candidate_id
 * @param int $jury_member_id
 * @return array|false
 */
function mt_get_candidate_evaluation($candidate_id, $jury_member_id) {
    global $wpdb;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mt_evaluations 
        WHERE candidate_id = %d 
        AND jury_member_id = %d",
        $candidate_id,
        $jury_member_id
    ));
}

/**
 * Save candidate evaluation
 *
 * @param int $candidate_id
 * @param int $jury_member_id
 * @param array $scores
 * @param string $comments
 * @return bool
 */
function mt_save_candidate_evaluation($candidate_id, $jury_member_id, $scores, $comments) {
    global $wpdb;
    
    $data = array(
        'candidate_id' => $candidate_id,
        'jury_member_id' => $jury_member_id,
        'scores' => maybe_serialize($scores),
        'comments' => $comments,
        'timestamp' => current_time('mysql')
    );
    
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}mt_evaluations 
        WHERE candidate_id = %d 
        AND jury_member_id = %d",
        $candidate_id,
        $jury_member_id
    ));
    
    if ($existing) {
        return $wpdb->update(
            $wpdb->prefix . 'mt_evaluations',
            $data,
            array('id' => $existing),
            array('%d', '%d', '%s', '%s', '%s'),
            array('%d')
        );
    } else {
        return $wpdb->insert(
            $wpdb->prefix . 'mt_evaluations',
            $data,
            array('%d', '%d', '%s', '%s', '%s')
        );
    }
} 