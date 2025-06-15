<?php
/**
 * Jury System Handler
 *
 * @package MobilityTrailblazers
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Jury_System
 * Handles all jury-related functionality
 */
class MT_Jury_System {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Rewrite rules
        add_action('init', array($this, 'add_jury_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_jury_query_vars'));
        add_action('template_redirect', array($this, 'jury_template_redirect'));
        
        // Login redirect
        add_filter('login_redirect', array($this, 'jury_login_redirect'), 10, 3);
        
        // Dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_jury_dashboard_widget'));
        
        // Evaluation submission
        add_action('admin_post_mt_submit_evaluation', array($this, 'handle_evaluation_submission'));
        
        // Debug hooks
        add_action('admin_init', array($this, 'handle_jury_dashboard_direct'));
        add_action('admin_init', array($this, 'debug_evaluation_access'));
        add_action('admin_notices', array($this, 'debug_jury_access'));
    }
    
    /**
     * Add jury rewrite rules
     */
    public function add_jury_rewrite_rules() {
        add_rewrite_rule(
            '^jury-dashboard/?$',
            'index.php?mt_jury_dashboard=1',
            'top'
        );
        
        add_rewrite_rule(
            '^jury-evaluation/([0-9]+)/?$',
            'index.php?mt_jury_evaluation=1&candidate_id=$matches[1]',
            'top'
        );
    }
    
    /**
     * Add jury query vars
     */
    public function add_jury_query_vars($vars) {
        $vars[] = 'mt_jury_dashboard';
        $vars[] = 'mt_jury_evaluation';
        $vars[] = 'candidate_id';
        return $vars;
    }
    
    /**
     * Handle jury template redirect
     */
    public function jury_template_redirect() {
        if (get_query_var('mt_jury_dashboard')) {
            if (!is_user_logged_in() || !MT_Roles::is_jury_member()) {
                wp_redirect(wp_login_url(home_url('/jury-dashboard/')));
                exit;
            }
            
            // Load jury dashboard template
            $template = locate_template('jury-dashboard.php');
            if (!$template) {
                $template = MT_PLUGIN_PATH . 'templates/jury-dashboard.php';
            }
            
            if (file_exists($template)) {
                include $template;
                exit;
            }
        }
    }
    
    /**
     * Jury login redirect
     */
    public function jury_login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('mt_jury_member', $user->roles) && !in_array('administrator', $user->roles)) {
                return admin_url('admin.php?page=mt-jury-evaluation');
            }
        }
        return $redirect_to;
    }
    
    /**
     * Add jury dashboard widget
     */
    public function add_jury_dashboard_widget() {
        if (MT_Roles::is_jury_member()) {
            wp_add_dashboard_widget(
                'mt_jury_dashboard_widget',
                __('Jury Evaluation Progress', 'mobility-trailblazers'),
                array($this, 'jury_dashboard_widget')
            );
        }
    }
    
    /**
     * Jury dashboard widget content
     */
    public function jury_dashboard_widget() {
        $user_id = get_current_user_id();
        $jury_member = $this->get_jury_member_for_user($user_id);
        
        if (!$jury_member) {
            echo '<p>' . __('Your jury member profile is not set up. Please contact the administrator.', 'mobility-trailblazers') . '</p>';
            return;
        }
        
        $assigned_candidates = $this->get_assigned_candidates($jury_member->ID);
        $total_assigned = count($assigned_candidates);
        $completed = 0;
        
        foreach ($assigned_candidates as $candidate_id) {
            if ($this->has_jury_member_evaluated($user_id, $candidate_id)) {
                $completed++;
            }
        }
        
        $progress = $total_assigned > 0 ? round(($completed / $total_assigned) * 100) : 0;
        ?>
        
        <div class="mt-jury-widget">
            <div class="progress-summary">
                <p class="progress-text">
                    <?php printf(
                        __('You have completed <strong>%d of %d</strong> evaluations', 'mobility-trailblazers'),
                        $completed,
                        $total_assigned
                    ); ?>
                </p>
                
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                </div>
                
                <p class="progress-percentage"><?php echo $progress; ?>% <?php _e('Complete', 'mobility-trailblazers'); ?></p>
            </div>
            
            <?php if ($total_assigned > $completed) : ?>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=mt-jury-evaluation'); ?>" class="button button-primary">
                        <?php _e('Continue Evaluations', 'mobility-trailblazers'); ?>
                    </a>
                </p>
            <?php else : ?>
                <p class="all-complete">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('All evaluations complete!', 'mobility-trailblazers'); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <style>
        .mt-jury-widget .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .mt-jury-widget .progress-fill {
            height: 100%;
            background: #2271b1;
            transition: width 0.3s ease;
        }
        
        .mt-jury-widget .progress-percentage {
            text-align: center;
            font-weight: bold;
            color: #2271b1;
        }
        
        .mt-jury-widget .all-complete {
            color: #00a32a;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .mt-jury-widget .all-complete .dashicons {
            font-size: 20px;
        }
        </style>
        <?php
    }
    
    /**
     * Handle jury dashboard direct access
     */
    public function handle_jury_dashboard_direct() {
        if (isset($_GET['page']) && $_GET['page'] === 'mt-jury-evaluation' && !isset($_GET['candidate_id'])) {
            // Check if this is a jury member
            if (is_user_logged_in() && MT_Roles::is_jury_member()) {
                // Redirect to the jury dashboard page if set
                $dashboard_page_id = get_option('mt_jury_dashboard_page');
                if ($dashboard_page_id) {
                    $dashboard_url = get_permalink($dashboard_page_id);
                    if ($dashboard_url) {
                        wp_redirect($dashboard_url);
                        exit;
                    }
                }
            }
        }
    }
    
    /**
     * Get jury member for user
     */
    public function get_jury_member_for_user($user_id) {
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
     */
    public function get_assigned_candidates($jury_member_id) {
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
     * Check if jury member has evaluated a candidate
     */
    public function has_jury_member_evaluated($jury_member_id, $candidate_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores 
            WHERE jury_member_id = %d AND candidate_id = %d",
            $jury_member_id,
            $candidate_id
        ));
        
        return $count > 0;
    }
    
    /**
     * Handle evaluation submission
     */
    public function handle_evaluation_submission() {
        // Verify nonce
        if (!isset($_POST['mt_evaluation_nonce']) || !wp_verify_nonce($_POST['mt_evaluation_nonce'], 'mt_submit_evaluation')) {
            wp_die(__('Security check failed', 'mobility-trailblazers'));
        }
        
        // Check if user is logged in and is a jury member
        if (!is_user_logged_in() || !MT_Roles::is_jury_member()) {
            wp_die(__('You do not have permission to submit evaluations', 'mobility-trailblazers'));
        }
        
        // Get form data
        $candidate_id = intval($_POST['candidate_id']);
        $jury_member_id = get_current_user_id();
        
        // Validate scores
        $scores = array(
            'courage_score' => intval($_POST['courage_score']),
            'innovation_score' => intval($_POST['innovation_score']),
            'implementation_score' => intval($_POST['implementation_score']),
            'mobility_relevance_score' => intval($_POST['mobility_relevance_score']),
            'visibility_score' => intval($_POST['visibility_score'])
        );
        
        // Ensure all scores are between 1 and 5
        foreach ($scores as $key => $score) {
            if ($score < 1 || $score > 5) {
                wp_die(__('Invalid score values', 'mobility-trailblazers'));
            }
        }
        
        // Calculate total score
        $total_score = array_sum($scores);
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'mt_candidate_scores';
        
        $data = array_merge($scores, array(
            'candidate_id' => $candidate_id,
            'jury_member_id' => $jury_member_id,
            'total_score' => $total_score,
            'evaluation_date' => current_time('mysql')
        ));
        
        // Check if evaluation already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE candidate_id = %d AND jury_member_id = %d",
            $candidate_id,
            $jury_member_id
        ));
        
        if ($existing) {
            // Update existing evaluation
            $result = $wpdb->update($table_name, $data, array('id' => $existing));
        } else {
            // Insert new evaluation
            $result = $wpdb->insert($table_name, $data);
        }
        
        if ($result === false) {
            wp_die(__('Failed to save evaluation', 'mobility-trailblazers'));
        }
        
        // Redirect back with success message
        $redirect_url = add_query_arg(array(
            'page' => 'mt-jury-evaluation',
            'evaluation_saved' => '1'
        ), admin_url('admin.php'));
        
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Debug jury access
     */
    public function debug_jury_access() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_GET['debug_jury']) && $_GET['debug_jury'] === '1') {
            $current_user = wp_get_current_user();
            ?>
            <div class="notice notice-info">
                <h3>Jury Access Debug Information</h3>
                <p><strong>Current User ID:</strong> <?php echo $current_user->ID; ?></p>
                <p><strong>User Roles:</strong> <?php echo implode(', ', $current_user->roles); ?></p>
                <p><strong>Is Jury Member:</strong> <?php echo MT_Roles::is_jury_member() ? 'Yes' : 'No'; ?></p>
                <p><strong>Can Access Jury Dashboard:</strong> <?php echo current_user_can('mt_access_jury_dashboard') ? 'Yes' : 'No'; ?></p>
                <p><strong>Can Submit Evaluations:</strong> <?php echo current_user_can('mt_submit_evaluations') ? 'Yes' : 'No'; ?></p>
                
                <?php
                $jury_member = $this->get_jury_member_for_user($current_user->ID);
                if ($jury_member) {
                    echo '<p><strong>Linked Jury Member:</strong> ' . $jury_member->post_title . ' (ID: ' . $jury_member->ID . ')</p>';
                    $assigned = get_post_meta($jury_member->ID, 'assigned_candidates', true);
                    echo '<p><strong>Assigned Candidates:</strong> ' . (is_array($assigned) ? count($assigned) : 0) . '</p>';
                } else {
                    echo '<p><strong>Linked Jury Member:</strong> None</p>';
                }
                ?>
            </div>
            <?php
        }
    }
    
    /**
     * Debug evaluation access
     */
    public function debug_evaluation_access() {
        if (isset($_GET['page']) && $_GET['page'] === 'mt-jury-evaluation' && isset($_GET['debug']) && current_user_can('manage_options')) {
            ?>
            <div class="notice notice-info">
                <h3>Evaluation Page Debug</h3>
                <pre><?php
                $user_id = get_current_user_id();
                $jury_member = $this->get_jury_member_for_user($user_id);
                
                echo "User ID: $user_id\n";
                echo "Is Jury Member: " . (MT_Roles::is_jury_member() ? 'Yes' : 'No') . "\n";
                echo "Jury Member Post: " . ($jury_member ? $jury_member->ID : 'None') . "\n";
                
                if ($jury_member) {
                    $assigned = get_post_meta($jury_member->ID, 'assigned_candidates', true);
                    echo "Assigned Candidates: " . print_r($assigned, true) . "\n";
                }
                
                if (isset($_GET['candidate_id'])) {
                    $candidate_id = intval($_GET['candidate_id']);
                    echo "Requested Candidate ID: $candidate_id\n";
                    echo "Has Evaluated: " . ($this->has_jury_member_evaluated($user_id, $candidate_id) ? 'Yes' : 'No') . "\n";
                }
                ?></pre>
            </div>
            <?php
        }
    }
    
    /**
     * Create enhanced jury member
     */
    public function create_enhanced_jury_member($data) {
        // Create jury member post
        $jury_post = array(
            'post_title' => $data['name'],
            'post_type' => 'mt_jury',
            'post_status' => 'publish'
        );
        
        $jury_id = wp_insert_post($jury_post);
        
        if (is_wp_error($jury_id)) {
            return false;
        }
        
        // Add meta data
        update_post_meta($jury_id, 'email', $data['email']);
        update_post_meta($jury_id, 'organization', $data['organization']);
        update_post_meta($jury_id, 'position', $data['position']);
        
        // Create WordPress user if email doesn't exist
        $user = get_user_by('email', $data['email']);
        
        if (!$user) {
            $user_id = $this->create_jury_wordpress_user($data['email'], $data['name']);
            if ($user_id) {
                update_post_meta($jury_id, 'user_id', $user_id);
                
                // Send welcome email
                $this->send_jury_welcome_email($user_id, $data['temp_password']);
            }
        } else {
            // Link existing user
            update_post_meta($jury_id, 'user_id', $user->ID);
            
            // Add jury role if not present
            if (!in_array('mt_jury_member', $user->roles)) {
                $user->add_role('mt_jury_member');
            }
        }
        
        return $jury_id;
    }
    
    /**
     * Create jury WordPress user
     */
    private function create_jury_wordpress_user($email, $name) {
        $username = sanitize_user(strtolower(str_replace(' ', '.', $name)));
        $password = wp_generate_password(12, true);
        
        // Ensure unique username
        $base_username = $username;
        $i = 1;
        while (username_exists($username)) {
            $username = $base_username . $i;
            $i++;
        }
        
        $user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
            'display_name' => $name,
            'role' => 'mt_jury_member'
        );
        
        $user_id = wp_insert_user($user_data);
        
        if (!is_wp_error($user_id)) {
            // Store temp password for email
            update_user_meta($user_id, 'mt_temp_password', $password);
            return $user_id;
        }
        
        return false;
    }
    
    /**
     * Send jury welcome email
     */
    private function send_jury_welcome_email($user_id, $password) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        $subject = sprintf(__('Welcome to %s Jury Panel', 'mobility-trailblazers'), get_bloginfo('name'));
        
        $message = sprintf(
            __('Dear %s,<br><br>You have been added as a jury member for the Mobility Trailblazers Award.<br><br>Your login credentials:<br>Username: %s<br>Password: %s<br><br>Login URL: %s<br><br>Please change your password after your first login.<br><br>Best regards,<br>The Mobility Trailblazers Team', 'mobility-trailblazers'),
            $user->display_name,
            $user->user_login,
            $password,
            wp_login_url()
        );
        
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($user->user_email, $subject, $message, $headers);
    }
    
    /**
     * Get jury statistics
     */
    public function get_jury_statistics() {
        global $wpdb;
        
        $stats = array(
            'total_jury_members' => 0,
            'active_jury_members' => 0,
            'total_evaluations' => 0,
            'average_completion_rate' => 0,
            'by_jury_member' => array()
        );
        
        // Get all jury members
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $stats['total_jury_members'] = count($jury_members);
        
        $total_completion_rate = 0;
        $active_count = 0;
        
        foreach ($jury_members as $jury) {
            $user_id = get_post_meta($jury->ID, 'user_id', true);
            $assigned = get_post_meta($jury->ID, 'assigned_candidates', true);
            $assigned_count = is_array($assigned) ? count($assigned) : 0;
            
            $completed = 0;
            if ($user_id) {
                $completed = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE jury_member_id = %d",
                    $user_id
                ));
                
                if ($completed > 0) {
                    $active_count++;
                }
            }
            
            $completion_rate = $assigned_count > 0 ? ($completed / $assigned_count) * 100 : 0;
            $total_completion_rate += $completion_rate;
            
            $stats['by_jury_member'][] = array(
                'id' => $jury->ID,
                'name' => $jury->post_title,
                'assigned' => $assigned_count,
                'completed' => $completed,
                'completion_rate' => round($completion_rate, 1)
            );
            
            $stats['total_evaluations'] += $completed;
        }
        
        $stats['active_jury_members'] = $active_count;
        $stats['average_completion_rate'] = $stats['total_jury_members'] > 0 
            ? round($total_completion_rate / $stats['total_jury_members'], 1) 
            : 0;
        
        return $stats;
    }
    
    /**
     * Optimize jury assignments
     */
    public function optimize_jury_assignments() {
        $candidates = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        $jury_members = get_posts(array(
            'post_type' => 'mt_jury',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ));
        
        if (empty($candidates) || empty($jury_members)) {
            return false;
        }
        
        $candidate_count = count($candidates);
        $jury_count = count($jury_members);
        
        // Calculate optimal distribution
        $candidates_per_jury = ceil($candidate_count / $jury_count);
        $overlap_factor = 2; // Each candidate evaluated by 2 jury members
        
        // Create assignment matrix
        $assignments = array();
        foreach ($jury_members as $jury) {
            $assignments[$jury->ID] = array();
        }
        
        // Distribute candidates
        $jury_index = 0;
        foreach ($candidates as $candidate) {
            for ($i = 0; $i < $overlap_factor; $i++) {
                $jury_id = $jury_members[$jury_index]->ID;
                $assignments[$jury_id][] = $candidate->ID;
                
                $jury_index = ($jury_index + 1) % $jury_count;
            }
        }
        
        // Balance assignments
        foreach ($assignments as $jury_id => $candidate_ids) {
            if (count($candidate_ids) > $candidates_per_jury) {
                $assignments[$jury_id] = array_slice($candidate_ids, 0, $candidates_per_jury);
            }
        }
        
        // Save assignments
        foreach ($assignments as $jury_id => $candidate_ids) {
            update_post_meta($jury_id, 'assigned_candidates', array_unique($candidate_ids));
        }
        
        return true;
    }
    
    /**
     * Send bulk jury email
     */
    public function send_bulk_jury_email($subject, $message, $jury_ids = array()) {
        if (empty($jury_ids)) {
            // Send to all jury members
            $jury_members = get_posts(array(
                'post_type' => 'mt_jury',
                'posts_per_page' => -1,
                'post_status' => 'publish'
            ));
            $jury_ids = wp_list_pluck($jury_members, 'ID');
        }
        
        $sent_count = 0;
        
        foreach ($jury_ids as $jury_id) {
            $user_id = get_post_meta($jury_id, 'user_id', true);
            if (!$user_id) {
                continue;
            }
            
            $user = get_user_by('id', $user_id);
            if (!$user) {
                continue;
            }
            
            $jury_member = get_post($jury_id);
            
            // Replace placeholders
            $personalized_message = str_replace(
                array('{name}', '{email}', '{dashboard_url}'),
                array(
                    $jury_member->post_title,
                    $user->user_email,
                    admin_url('admin.php?page=mt-jury-evaluation')
                ),
                $message
            );
            
            $headers = array('Content-Type: text/html; charset=UTF-8');
            
            if (wp_mail($user->user_email, $subject, $personalized_message, $headers)) {
                $sent_count++;
            }
        }
        
        return $sent_count;
    }
    
    /**
     * Export jury evaluation report
     */
    public function export_jury_evaluation_report() {
        global $wpdb;
        
        $report_data = array();
        
        // Get all evaluations with jury and candidate details
        $evaluations = $wpdb->get_results("
            SELECT 
                s.*,
                c.post_title as candidate_name,
                j.post_title as jury_name
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
        ");
        
        foreach ($evaluations as $eval) {
            $report_data[] = array(
                'Date' => $eval->evaluation_date,
                'Jury Member' => $eval->jury_name ?: 'Unknown',
                'Candidate' => $eval->candidate_name,
                'Courage Score' => $eval->courage_score,
                'Innovation Score' => $eval->innovation_score,
                'Implementation Score' => $eval->implementation_score,
                'Mobility Relevance Score' => $eval->mobility_relevance_score,
                'Visibility Score' => $eval->visibility_score,
                'Total Score' => $eval->total_score
            );
        }
        
        return $report_data;
    }
} 