<?php
/**
 * Security Headers Manager
 *
 * Implements Content Security Policy (CSP) and other security headers
 * to protect against XSS, clickjacking, and other attacks.
 *
 * @package MobilityTrailblazers
 * @since 4.1.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Security_Headers
 *
 * Manages security headers for the plugin
 */
class MT_Security_Headers {
    
    /**
     * CSP nonce for inline scripts
     *
     * @var string
     */
    private static $csp_nonce = null;
    
    /**
     * Initialize security headers
     *
     * @return void
     */
    public static function init() {
        // Add security headers early in the request
        add_action('send_headers', [__CLASS__, 'add_security_headers'], 1);
        add_action('admin_init', [__CLASS__, 'add_admin_security_headers'], 1);
        
        // Removed nonce filters as we're using 'unsafe-inline' for compatibility
        // with WordPress, Elementor, and third-party plugins
    }
    
    /**
     * Get or generate CSP nonce
     *
     * @return string
     */
    public static function get_csp_nonce() {
        if (is_null(self::$csp_nonce)) {
            self::$csp_nonce = wp_create_nonce('mt_csp_' . session_id());
        }
        return self::$csp_nonce;
    }
    
    /**
     * Add security headers for frontend
     *
     * @return void
     */
    public static function add_security_headers() {
        // Only add headers on plugin-specific pages
        if (!self::is_plugin_page()) {
            return;
        }
        
        // Check if headers already sent
        if (headers_sent()) {
            return;
        }
        
        // Content Security Policy
        $csp_directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://ajax.googleapis.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https: http:",
            "font-src 'self' data: https://fonts.gstatic.com",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "object-src 'none'",
            "frame-src 'none'"
        ];
        
        $csp = implode('; ', $csp_directives);
        
        // Send security headers
        header("Content-Security-Policy: {$csp}");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Additional security for sensitive pages
        if (self::is_evaluation_page()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, private');
            header('Pragma: no-cache');
        }
    }
    
    /**
     * Add security headers for admin pages
     *
     * @return void
     */
    public static function add_admin_security_headers() {
        // Only on plugin admin pages
        if (!self::is_plugin_admin_page()) {
            return;
        }
        
        // Check if headers already sent
        if (headers_sent()) {
            return;
        }
        
        // Less restrictive CSP for admin (WordPress admin needs inline scripts)
        
        $csp_directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'", // WordPress admin requires these
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https: http:",
            "font-src 'self' data:",
            "connect-src 'self'",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "object-src 'none'"
        ];
        
        $csp = implode('; ', $csp_directives);
        
        // Send headers
        header("Content-Security-Policy: {$csp}");
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    /**
     * Add nonce attribute to script tags
     *
     * @param string $tag Script tag HTML
     * @param string $handle Script handle
     * @param string $src Script source
     * @return string Modified script tag
     */
    public static function add_nonce_to_scripts($tag, $handle, $src) {
        // Only add nonce to plugin scripts
        if (!self::is_plugin_asset($handle)) {
            return $tag;
        }
        
        $nonce = self::get_csp_nonce();
        
        // Add nonce attribute if not already present
        if (strpos($tag, 'nonce=') === false) {
            $tag = str_replace('<script ', '<script nonce="' . esc_attr($nonce) . '" ', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Add nonce attribute to style tags
     *
     * @param string $tag Style tag HTML
     * @param string $handle Style handle
     * @param string $href Style source
     * @return string Modified style tag
     */
    public static function add_nonce_to_styles($tag, $handle, $href) {
        // Only add nonce to inline styles
        if (!self::is_plugin_asset($handle)) {
            return $tag;
        }
        
        $nonce = self::get_csp_nonce();
        
        // Add nonce attribute for inline styles
        if (strpos($tag, '<style') !== false && strpos($tag, 'nonce=') === false) {
            $tag = str_replace('<style', '<style nonce="' . esc_attr($nonce) . '"', $tag);
        }
        
        return $tag;
    }
    
    /**
     * Print CSP nonce for JavaScript access
     *
     * @return void
     */
    public static function print_csp_nonce() {
        if (!self::is_plugin_page() && !self::is_plugin_admin_page()) {
            return;
        }
        
        $nonce = self::get_csp_nonce();
        ?>
        <script nonce="<?php echo esc_attr($nonce); ?>">
            window.mtCSPNonce = '<?php echo esc_js($nonce); ?>';
        </script>
        <?php
    }
    
    /**
     * Check if current page is a plugin page
     *
     * @return bool
     */
    private static function is_plugin_page() {
        // Check for plugin-specific pages
        if (is_page(['vote', 'jury-dashboard', 'rankings', 'kandidaten'])) {
            return true;
        }
        
        // Check for custom post types
        if (is_singular('mt_candidate') || is_post_type_archive('mt_candidate')) {
            return true;
        }
        
        if (is_singular('mt_jury_member')) {
            return true;
        }
        
        // Check for shortcodes
        global $post;
        if ($post && has_shortcode($post->post_content, 'mt_')) {
            return true;
        }
        
        // Check URL parameters
        if (isset($_GET['evaluate']) || isset($_GET['mt_category'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if current page is a plugin admin page
     *
     * @return bool
     */
    private static function is_plugin_admin_page() {
        if (!is_admin()) {
            return false;
        }
        
        $screen = get_current_screen();
        if (!$screen) {
            return false;
        }
        
        // Check for plugin admin pages
        $plugin_screens = [
            'toplevel_page_mt-award-system',
            'mt-award-system_page_mt-assignments',
            'mt-award-system_page_mt-evaluations',
            'mt-award-system_page_mt-settings',
            'mt-award-system_page_mt-import-export',
            'mt-award-system_page_mt-debug-center',
            'edit-mt_candidate',
            'edit-mt_jury_member',
            'mt_candidate',
            'mt_jury_member'
        ];
        
        return in_array($screen->id, $plugin_screens);
    }
    
    /**
     * Check if current page is an evaluation page
     *
     * @return bool
     */
    private static function is_evaluation_page() {
        return isset($_GET['evaluate']) || 
               is_page('jury-dashboard') || 
               (isset($_GET['page']) && $_GET['page'] === 'mt-evaluations');
    }
    
    /**
     * Check if handle belongs to plugin asset
     *
     * @param string $handle Asset handle
     * @return bool
     */
    private static function is_plugin_asset($handle) {
        $plugin_handles = [
            'mt-critical',
            'mt-core',
            'mt-components',
            'mt-mobile',
            'mt-admin',
            'mt-specificity-layer',
            'mt-assignments',
            'mt-evaluations',
            'mt-dashboard'
        ];
        
        foreach ($plugin_handles as $plugin_handle) {
            if (strpos($handle, $plugin_handle) !== false) {
                return true;
            }
        }
        
        return false;
    }
}