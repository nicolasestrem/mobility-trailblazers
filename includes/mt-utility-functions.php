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
    $jury_member_id = get_user_meta($user_id, '_mt_jury_member_id', true);
    
    if ($jury_member_id) {
        $jury_member = get_post($jury_member_id);
        
        if ($jury_member && $jury_member->post_type === 'mt_jury_member') {
            return $jury_member;
        }
    }
    
    // Fallback: Query by user ID meta
    $args = array(
        'post_type' => 'mt_jury_member',
        'meta_key' => '_mt_user_id',
        'meta_value' => $user_id,
        'posts_per_page' => 1,
        'post_status' => 'publish',
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        return $query->posts[0];
    }
    
    return null;
}

/**
 * Get assigned candidates for jury member
 * Returns array of candidate post objects by default
 *
 * @param int $jury_member_id Jury member ID
 * @param bool $ids_only Whether to return only IDs (default: false)
 * @return array Array of candidate objects or IDs
 */
function mt_get_assigned_candidates($jury_member_id, $ids_only = false) {
    global $wpdb;
    
    $jury_member_id = intval($jury_member_id);
    $assigned_candidates = array();
    
    // Get all candidates with assignments
    $results = $wpdb->get_results("
        SELECT post_id, meta_value 
        FROM {$wpdb->postmeta} 
        WHERE meta_key = '_mt_assigned_jury_members' 
        AND meta_value != ''
        AND meta_value != 'a:0:{}'
        AND meta_value IS NOT NULL
    ");
    
    foreach ($results as $row) {
        $jury_ids = maybe_unserialize($row->meta_value);
        if (is_array($jury_ids)) {
            // Convert all to integers for comparison
            $jury_ids = array_map('intval', $jury_ids);
            if (in_array($jury_member_id, $jury_ids)) {
                $candidate_id = intval($row->post_id);
                
                if ($ids_only) {
                    // Return just the ID
                    $assigned_candidates[] = $candidate_id;
                } else {
                    // Return the post object
                    $candidate = get_post($candidate_id);
                    if ($candidate && $candidate->post_type === 'mt_candidate' && $candidate->post_status === 'publish') {
                        $assigned_candidates[] = $candidate;
                    }
                }
            }
        }
    }
    
    return $assigned_candidates;
}

/**
 * Alternative: Create a separate function for getting IDs only
 *
 * @param int $jury_member_id Jury member ID
 * @return array Array of candidate IDs
 */
function mt_get_assigned_candidate_ids($jury_member_id) {
    return mt_get_assigned_candidates($jury_member_id, true);
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
    
    $table_name = $wpdb->prefix . 'mt_candidate_scores';
    
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name 
         WHERE candidate_id = %d 
         AND jury_member_id = %d 
         AND is_active = 1",
        $candidate_id,
        $jury_member_id
    ));
    
    return $exists > 0;
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
    
    $table_name = $wpdb->prefix . 'mt_candidate_scores';
    
    $evaluation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name 
         WHERE candidate_id = %d 
         AND jury_member_id = %d 
         AND is_active = 1 
         ORDER BY created_at DESC 
         LIMIT 1",
        $candidate_id,
        $jury_member_id
    ));
    
    return $evaluation;
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
 * Sanitize evaluation score
 *
 * @param mixed $score Score value
 * @param int $max Maximum allowed score
 * @return int Sanitized score
 */
function mt_sanitize_score($score, $max = 10) {
    $score = intval($score);
    
    if ($score < 0) {
        $score = 0;
    } elseif ($score > $max) {
        $score = $max;
    }
    
    return $score;
}

/**
 * Get candidate excerpt
 *
 * @param int $candidate_id Candidate ID
 * @param int $length Excerpt length
 * @return string Candidate excerpt
 */
function mt_get_candidate_excerpt($candidate_id, $length = 150) {
    $candidate = get_post($candidate_id);
    
    if (!$candidate) {
        return '';
    }
    
    if ($candidate->post_excerpt) {
        return $candidate->post_excerpt;
    }
    
    $content = strip_shortcodes($candidate->post_content);
    $content = wp_strip_all_tags($content);
    $content = substr($content, 0, $length);
    
    if (strlen($candidate->post_content) > $length) {
        $content .= '...';
    }
    
    return $content;
}

/**
 * Get candidate photo URL
 *
 * @param int $candidate_id Candidate ID
 * @param string $size Image size
 * @return string Photo URL or default
 */
function mt_get_candidate_photo($candidate_id, $size = 'medium') {
    if (has_post_thumbnail($candidate_id)) {
        $image = wp_get_attachment_image_src(get_post_thumbnail_id($candidate_id), $size);
        return $image[0];
    }
    
    // Return default image
    return MT_PLUGIN_URL . 'assets/images/default-candidate.png';
}

/**
 * Get jury member photo URL
 *
 * @param int $jury_member_id Jury member ID
 * @param string $size Image size
 * @return string Photo URL or default
 */
function mt_get_jury_member_photo($jury_member_id, $size = 'medium') {
    if (has_post_thumbnail($jury_member_id)) {
        $image = wp_get_attachment_image_src(get_post_thumbnail_id($jury_member_id), $size);
        return $image[0];
    }
    
    // Return default image
    return MT_PLUGIN_URL . 'assets/images/default-jury.png';
}

/**
 * Log plugin activity
 *
 * @param string $message Log message
 * @param string $type Log type (info, warning, error)
 * @param array $context Additional context
 */
function mt_log($message, $type = 'info', $context = array()) {
    if (!defined('MT_DEBUG') || !MT_DEBUG) {
        return;
    }
    
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'type' => $type,
        'message' => $message,
        'context' => $context,
        'user_id' => get_current_user_id(),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    );
    
    // Log to error log
    error_log('[MT ' . strtoupper($type) . '] ' . $message . ' | Context: ' . json_encode($context));
    
    // Fire action for custom logging
    do_action('mt_log_activity', $log_entry);
}

/**
 * Send email notification
 *
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @param array $headers Optional headers
 * @return bool Whether email was sent
 */
function mt_send_email($to, $subject, $message, $headers = array()) {
    // Get email settings
    $from_name = get_option('mt_email_from_name', get_bloginfo('name'));
    $from_email = get_option('mt_email_from_address', get_option('admin_email'));
    
    // Set default headers
    $default_headers = array(
        'From: ' . $from_name . ' <' . $from_email . '>',
        'Content-Type: text/html; charset=UTF-8',
    );
    
    $headers = array_merge($default_headers, $headers);
    
    // Apply filters
    $to = apply_filters('mt_email_recipient', $to, $subject);
    $subject = apply_filters('mt_email_subject', $subject, $to);
    $message = apply_filters('mt_email_message', $message, $to, $subject);
    $headers = apply_filters('mt_email_headers', $headers, $to, $subject);
    
    // Send email
    $sent = wp_mail($to, $subject, $message, $headers);
    
    // Log email activity
    mt_log('Email sent', $sent ? 'info' : 'error', array(
        'to' => $to,
        'subject' => $subject,
        'sent' => $sent,
    ));
    
    return $sent;
}

/**
 * Get email template
 *
 * @param string $template Template name
 * @param array $variables Template variables
 * @return string Processed template
 */
function mt_get_email_template($template, $variables = array()) {
    $template_file = MT_PLUGIN_DIR . 'templates/emails/' . $template . '.php';
    
    if (!file_exists($template_file)) {
        return '';
    }
    
    // Extract variables
    extract($variables);
    
    // Start output buffering
    ob_start();
    
    // Include template
    include $template_file;
    
    // Get content
    $content = ob_get_clean();
    
    // Process variables
    foreach ($variables as $key => $value) {
        $content = str_replace('{{' . $key . '}}', $value, $content);
    }
    
    return $content;
}

/**
 * Export data to CSV
 *
 * @param array $data Data to export
 * @param string $filename Filename
 * @param array $headers Column headers
 */
function mt_export_csv($data, $filename, $headers = array()) {
    // Set headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write headers
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    // Close stream
    fclose($output);
    
    exit;
}

/**
 * Get plugin version
 *
 * @return string Plugin version
 */
function mt_get_plugin_version() {
    return MT_PLUGIN_VERSION;
}

/**
 * Check if current user is jury member
 *
 * @param int $user_id Optional user ID
 * @return bool Whether user is jury member
 */
function mt_is_jury_member($user_id = null) {
    if (null === $user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    $user = get_user_by('id', $user_id);
    
    if (!$user) {
        return false;
    }
    
    return in_array('mt_jury', $user->roles) || user_can($user, 'mt_submit_evaluations');
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
        'jury_member_id' => null,
        'candidate_id' => null,
        'category_id' => null,
        'phase' => null,
        'year' => mt_get_current_award_year(),
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $table_name = $wpdb->prefix . 'mt_candidate_scores';
    $where = array('is_active = 1');
    
    if ($args['jury_member_id']) {
        $where[] = $wpdb->prepare('jury_member_id = %d', $args['jury_member_id']);
    }
    
    if ($args['candidate_id']) {
        $where[] = $wpdb->prepare('candidate_id = %d', $args['candidate_id']);
    }
    
    $where_clause = implode(' AND ', $where);
    
    // Get basic statistics
    $stats = $wpdb->get_row("
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
    
    // Convert to array
    $stats = (array) $stats;
    
    // Get total candidates
    $stats['total_candidates'] = wp_count_posts('mt_candidate')->publish;
    
    // Calculate completion rate
    $stats['completion_rate'] = $stats['total_candidates'] > 0 
        ? round(($stats['total_evaluations'] / $stats['total_candidates']) * 100) 
        : 0;
    
    // Get daily evaluations
    $daily_evaluations = $wpdb->get_results("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as count
        FROM $table_name
        WHERE $where_clause
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ", ARRAY_A);
    
    // Format daily evaluations
    $stats['daily_evaluations'] = array();
    foreach ($daily_evaluations as $day) {
        $stats['daily_evaluations'][$day['date']] = intval($day['count']);
    }
    
    // Get category statistics
    $category_stats = $wpdb->get_results("
        SELECT 
            t.name as category,
            COUNT(DISTINCT p.ID) as candidates,
            COUNT(s.id) as evaluations,
            AVG(s.total_score) as avg_score,
            (COUNT(s.id) / COUNT(DISTINCT p.ID)) * 100 as completion_rate
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
        LEFT JOIN $table_name s ON p.ID = s.candidate_id
        WHERE p.post_type = 'mt_candidate'
        AND p.post_status = 'publish'
        AND tt.taxonomy = 'mt_category'
        GROUP BY t.term_id
    ", ARRAY_A);
    
    // Format category statistics
    $stats['by_category'] = array();
    foreach ($category_stats as $cat) {
        $stats['by_category'][$cat['category']] = array(
            'candidates' => intval($cat['candidates']),
            'evaluations' => intval($cat['evaluations']),
            'avg_score' => round(floatval($cat['avg_score']), 1),
            'completion_rate' => round(floatval($cat['completion_rate']))
        );
    }
    
    // Get criteria statistics
    $stats['by_criteria'] = array(
        'courage' => round(floatval($stats['avg_courage']), 1),
        'innovation' => round(floatval($stats['avg_innovation']), 1),
        'implementation' => round(floatval($stats['avg_implementation']), 1),
        'relevance' => round(floatval($stats['avg_relevance']), 1),
        'visibility' => round(floatval($stats['avg_visibility']), 1)
    );
    
    return $stats;
}

/**
 * Check if jury member has a draft evaluation for a candidate
 * Fixed version without is_draft column check
 *
 * @param int|WP_Post $candidate_id Candidate ID or WP_Post object
 * @param int $jury_member_id Jury member ID
 * @return bool Whether draft evaluation exists
 */
function mt_has_draft_evaluation($candidate_id, $jury_member_id) {
    // Get candidate ID if WP_Post object is passed
    if (is_object($candidate_id) && isset($candidate_id->ID)) {
        $candidate_id = $candidate_id->ID;
    }
    
    // Get the user ID for the jury member
    $jury_user_id = get_post_meta($jury_member_id, '_mt_user_id', true);
    
    if (!$jury_user_id) {
        // If no user ID stored, try to find by current user
        $current_user_id = get_current_user_id();
        $current_jury = mt_get_jury_member_by_user_id($current_user_id);
        
        if ($current_jury && $current_jury->ID == $jury_member_id) {
            $jury_user_id = $current_user_id;
        }
    }
    
    if (!$jury_user_id) {
        return false;
    }
    
    // Check for draft in user meta
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
function mt_get_jury_member_meta_key() {
    if (get_option('mt_jury_nomenclature_migrated', false)) {
        return '_mt_jury_member_id';
    }
    return '_mt_jury_member_id';
} 