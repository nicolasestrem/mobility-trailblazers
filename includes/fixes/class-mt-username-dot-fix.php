<?php
/**
 * Username Dot Fix - Permanent Solution
 *
 * @package MobilityTrailblazers
 * @since 2.5.38
 */

namespace MobilityTrailblazers\Fixes;

use MobilityTrailblazers\Core\MT_Logger;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Username_Dot_Fix
 *
 * Fixes jury member usernames with leading dots and prevents them from reoccurring
 */
class MT_Username_Dot_Fix {
    
    /**
     * Initialize the fix
     */
    public static function init() {
        // Hook into user creation and update to prevent dots
        add_filter('pre_user_login', [__CLASS__, 'sanitize_username'], 999);
        add_action('user_register', [__CLASS__, 'check_and_fix_username'], 10, 1);
        add_action('profile_update', [__CLASS__, 'check_and_fix_username'], 10, 1);
        add_action('wp_insert_user', [__CLASS__, 'check_and_fix_username'], 10, 1);
        
        // Add filter to wp_update_user to catch any updates
        add_filter('wp_pre_insert_user_data', [__CLASS__, 'filter_user_data'], 999, 3);
        
        // Log any username changes
        add_action('updated_user_meta', [__CLASS__, 'log_username_changes'], 10, 4);
        
        // Add admin notice if dots are detected
        add_action('admin_notices', [__CLASS__, 'show_dot_warning']);
    }
    
    /**
     * Remove leading dots from username before it's saved
     *
     * @param string $username
     * @return string
     */
    public static function sanitize_username($username) {
        $original = $username;
        
        // Remove all leading dots
        $username = ltrim($username, '.');
        
        // Log if we made changes
        if ($original !== $username) {
            MT_Logger::warning('Username dot prevention triggered', [
                'original' => $original,
                'sanitized' => $username,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            
            // Store in transient for admin notice
            set_transient('mt_username_dots_prevented', [
                'original' => $original,
                'sanitized' => $username,
                'time' => current_time('mysql')
            ], DAY_IN_SECONDS);
        }
        
        return $username;
    }
    
    /**
     * Filter user data before insert/update
     *
     * @param array $data User data
     * @param bool $update Whether this is an update
     * @param int|null $id User ID (if update)
     * @return array
     */
    public static function filter_user_data($data, $update, $id) {
        if (isset($data['user_login'])) {
            $original = $data['user_login'];
            $data['user_login'] = ltrim($data['user_login'], '.');
            
            if ($original !== $data['user_login']) {
                MT_Logger::warning('Username dots filtered during user data update', [
                    'user_id' => $id,
                    'original' => $original,
                    'sanitized' => $data['user_login']
                ]);
            }
        }
        
        return $data;
    }
    
    /**
     * Check and fix username after user is created/updated
     *
     * @param int $user_id
     */
    public static function check_and_fix_username($user_id) {
        global $wpdb;
        
        $user = get_userdata($user_id);
        if (!$user) {
            return;
        }
        
        $username = $user->user_login;
        
        // Check if username starts with dots
        if (strpos($username, '.') === 0) {
            $clean_username = ltrim($username, '.');
            
            // Direct database update (bypasses filters)
            $result = $wpdb->update(
                $wpdb->users,
                ['user_login' => $clean_username],
                ['ID' => $user_id],
                ['%s'],
                ['%d']
            );
            
            if ($result !== false) {
                MT_Logger::info('Fixed username with leading dots', [
                    'user_id' => $user_id,
                    'original' => $username,
                    'fixed' => $clean_username
                ]);
                
                // Clear user cache
                clean_user_cache($user_id);
            }
        }
    }
    
    /**
     * Log username meta changes
     *
     * @param int $meta_id
     * @param int $user_id
     * @param string $meta_key
     * @param mixed $meta_value
     */
    public static function log_username_changes($meta_id, $user_id, $meta_key, $meta_value) {
        // Track nickname changes that might affect display
        if ($meta_key === 'nickname' && strpos($meta_value, '.') === 0) {
            MT_Logger::warning('Nickname with leading dots detected', [
                'user_id' => $user_id,
                'nickname' => $meta_value
            ]);
        }
    }
    
    /**
     * Fix all existing usernames with dots
     *
     * @return array Results of the fix
     */
    public static function fix_all_usernames() {
        global $wpdb;
        
        $results = [
            'total' => 0,
            'fixed' => 0,
            'errors' => 0,
            'users' => []
        ];
        
        // Find all users with usernames starting with dots
        $users_with_dots = $wpdb->get_results("
            SELECT ID, user_login, user_email, display_name 
            FROM {$wpdb->users} 
            WHERE user_login LIKE '.%'
            ORDER BY user_login
        ");
        
        $results['total'] = count($users_with_dots);
        
        foreach ($users_with_dots as $user) {
            $clean_username = ltrim($user->user_login, '.');
            
            // Check if clean username already exists
            if (username_exists($clean_username)) {
                MT_Logger::error('Cannot fix username - clean version already exists', [
                    'user_id' => $user->ID,
                    'original' => $user->user_login,
                    'clean' => $clean_username
                ]);
                $results['errors']++;
                $results['users'][] = [
                    'id' => $user->ID,
                    'original' => $user->user_login,
                    'status' => 'error',
                    'message' => 'Clean username already exists'
                ];
                continue;
            }
            
            // Update the username
            $update_result = $wpdb->update(
                $wpdb->users,
                ['user_login' => $clean_username],
                ['ID' => $user->ID],
                ['%s'],
                ['%d']
            );
            
            if ($update_result !== false) {
                $results['fixed']++;
                $results['users'][] = [
                    'id' => $user->ID,
                    'original' => $user->user_login,
                    'fixed' => $clean_username,
                    'email' => $user->user_email,
                    'display_name' => $user->display_name,
                    'status' => 'success'
                ];
                
                MT_Logger::info('Fixed username', [
                    'user_id' => $user->ID,
                    'original' => $user->user_login,
                    'fixed' => $clean_username
                ]);
                
                // Clear user cache
                clean_user_cache($user->ID);
            } else {
                $results['errors']++;
                $results['users'][] = [
                    'id' => $user->ID,
                    'original' => $user->user_login,
                    'status' => 'error',
                    'message' => $wpdb->last_error
                ];
                
                MT_Logger::error('Failed to fix username', [
                    'user_id' => $user->ID,
                    'original' => $user->user_login,
                    'error' => $wpdb->last_error
                ]);
            }
        }
        
        // Clear all caches
        wp_cache_flush();
        
        // Store results for display
        set_transient('mt_username_fix_results', $results, HOUR_IN_SECONDS);
        
        return $results;
    }
    
    /**
     * Show admin notice if dots are detected
     */
    public static function show_dot_warning() {
        // Check if we prevented dots recently
        $prevented = get_transient('mt_username_dots_prevented');
        if ($prevented) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong><?php _e('Username Dot Prevention Active', 'mobility-trailblazers'); ?></strong></p>
                <p><?php printf(
                    __('Attempted to create/update username with leading dots: %s → %s', 'mobility-trailblazers'),
                    '<code>' . esc_html($prevented['original']) . '</code>',
                    '<code>' . esc_html($prevented['sanitized']) . '</code>'
                ); ?></p>
                <p><small><?php echo esc_html($prevented['time']); ?></small></p>
            </div>
            <?php
            delete_transient('mt_username_dots_prevented');
        }
        
        // Show fix results if available
        $results = get_transient('mt_username_fix_results');
        if ($results && !empty($results['fixed'])) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong><?php _e('Username Fix Complete', 'mobility-trailblazers'); ?></strong></p>
                <p><?php printf(
                    __('Fixed %d of %d usernames with leading dots.', 'mobility-trailblazers'),
                    $results['fixed'],
                    $results['total']
                ); ?></p>
                <?php if (!empty($results['users'])): ?>
                    <details>
                        <summary><?php _e('View Details', 'mobility-trailblazers'); ?></summary>
                        <ul>
                            <?php foreach ($results['users'] as $user): ?>
                                <?php if ($user['status'] === 'success'): ?>
                                    <li>✅ <?php echo esc_html($user['original']); ?> → <?php echo esc_html($user['fixed']); ?></li>
                                <?php else: ?>
                                    <li>❌ <?php echo esc_html($user['original']); ?>: <?php echo esc_html($user['message']); ?></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </div>
            <?php
            delete_transient('mt_username_fix_results');
        }
    }
    
    /**
     * Check if any usernames have dots
     *
     * @return bool
     */
    public static function has_usernames_with_dots() {
        global $wpdb;
        
        $count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->users} 
            WHERE user_login LIKE '.%'
        ");
        
        return $count > 0;
    }
}

// Initialize the fix
add_action('init', ['\MobilityTrailblazers\Fixes\MT_Username_Dot_Fix', 'init']);