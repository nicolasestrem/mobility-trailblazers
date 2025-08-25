<?php
/**
 * UTF-8 Encoding Handler
 *
 * Handles UTF-8 encoding for templates and ensures proper character display
 * for German and other special characters.
 *
 * @package MobilityTrailblazers
 * @subpackage Core
 * @since 4.1.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * UTF-8 Handler Class
 *
 * @since 4.1.0
 */
class MT_UTF8_Handler {
    
    /**
     * Common encoding fixes for double-encoded UTF-8
     *
     * @var array
     */
    private static $encoding_fixes = [
        // German umlauts and eszett
        'Ã¤' => 'ä',
        'Ã¶' => 'ö',
        'Ã¼' => 'ü',
        'ÃŸ' => 'ß',
        'Ã„' => 'Ä',
        'Ã–' => 'Ö',
        'Ãœ' => 'Ü',
        
        // Common punctuation issues
        'â€™' => "'",
        'â€"' => '–',
        'â€"' => '—',
        'â€œ' => '"',
        'â€' => '"',
        'â€¦' => '…',
        'â€¢' => '•',
        
        // French characters (if any)
        'Ã©' => 'é',
        'Ã¨' => 'è',
        'Ãª' => 'ê',
        'Ã ' => 'à',
        'Ã§' => 'ç',
        
        // Other European characters
        'Ã±' => 'ñ',
        'Ã­' => 'í',
        'Ã³' => 'ó',
        'Ãº' => 'ú'
    ];
    
    /**
     * Initialize UTF-8 handler
     *
     * @since 4.1.0
     */
    public static function init() {
        // Ensure WordPress outputs UTF-8
        add_action('init', [__CLASS__, 'set_charset']);
        
        // Add charset meta tag for admin pages
        add_action('admin_head', [__CLASS__, 'add_charset_meta']);
        
        // Filter content for encoding issues
        add_filter('the_content', [__CLASS__, 'fix_content_encoding'], 5);
        
        // Filter widget text
        add_filter('widget_text', [__CLASS__, 'fix_content_encoding'], 5);
        
        // Filter post meta
        add_filter('get_post_metadata', [__CLASS__, 'fix_meta_encoding'], 10, 4);
        
        // Ensure proper headers for AJAX responses
        add_action('wp_ajax_nopriv_mt_ajax', [__CLASS__, 'set_ajax_headers'], 1);
        add_action('wp_ajax_mt_ajax', [__CLASS__, 'set_ajax_headers'], 1);
    }
    
    /**
     * Set proper charset for WordPress
     *
     * @since 4.1.0
     */
    public static function set_charset() {
        // Ensure database connection uses UTF-8
        global $wpdb;
        if (!empty($wpdb->charset)) {
            $wpdb->charset = 'utf8mb4';
        }
        
        // Set internal encoding for PHP
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }
        
        // Set HTTP header for UTF-8
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
    }
    
    /**
     * Add charset meta tag to admin pages
     *
     * @since 4.1.0
     */
    public static function add_charset_meta() {
        echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . "\n";
    }
    
    /**
     * Set proper headers for AJAX responses
     *
     * @since 4.1.0
     */
    public static function set_ajax_headers() {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
    }
    
    /**
     * Fix double-encoded UTF-8 characters in content
     *
     * @since 4.1.0
     * @param string $content The content to fix
     * @return string Fixed content
     */
    public static function fix_content_encoding($content) {
        if (empty($content)) {
            return $content;
        }
        
        // Check if content has encoding issues
        if (self::has_encoding_issues($content)) {
            $content = self::fix_encoding($content);
        }
        
        return $content;
    }
    
    /**
     * Fix encoding in post meta values
     *
     * @since 4.1.0
     * @param mixed  $value     The meta value
     * @param int    $object_id Object ID
     * @param string $meta_key  Meta key
     * @param bool   $single    Whether to return a single value
     * @return mixed Fixed meta value
     */
    public static function fix_meta_encoding($value, $object_id, $meta_key, $single) {
        if ($value !== null && is_string($value)) {
            $value = self::fix_content_encoding($value);
        } elseif (is_array($value)) {
            $value = array_map([__CLASS__, 'fix_content_encoding'], $value);
        }
        
        return $value;
    }
    
    /**
     * Check if string has encoding issues
     *
     * @since 4.1.0
     * @param string $string The string to check
     * @return bool True if encoding issues detected
     */
    public static function has_encoding_issues($string) {
        foreach (array_keys(self::$encoding_fixes) as $pattern) {
            if (strpos($string, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Fix encoding issues in a string
     *
     * @since 4.1.0
     * @param string $string The string to fix
     * @return string Fixed string
     */
    public static function fix_encoding($string) {
        // Apply all encoding fixes
        foreach (self::$encoding_fixes as $bad => $good) {
            $string = str_replace($bad, $good, $string);
        }
        
        // Additional cleanup for mojibake
        $string = str_replace(['Ã¢â‚¬', 'Ã‚Â', 'ï»¿'], '', $string);
        
        // Ensure string is valid UTF-8
        if (function_exists('mb_convert_encoding')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        }
        
        return $string;
    }
    
    /**
     * Validate and fix file encoding
     *
     * @since 4.1.0
     * @param string $file_path Path to the file
     * @return bool True if file was fixed, false otherwise
     */
    public static function fix_file_encoding($file_path) {
        if (!file_exists($file_path) || !is_readable($file_path)) {
            return false;
        }
        
        // Read file content
        $content = file_get_contents($file_path);
        if ($content === false) {
            return false;
        }
        
        // Check if file has encoding issues
        if (!self::has_encoding_issues($content)) {
            return false;
        }
        
        // Fix encoding
        $fixed_content = self::fix_encoding($content);
        
        // Write back to file (without BOM)
        $result = file_put_contents($file_path, $fixed_content);
        
        return $result !== false;
    }
    
    /**
     * Ensure string uses formal German address (Sie form)
     *
     * @since 4.1.0
     * @param string $string The string to check
     * @return string String with formal address
     */
    public static function ensure_formal_german($string) {
        // Common informal to formal replacements
        $informal_to_formal = [
            // Avoid replacing in URLs or code
            '/(?<![a-zA-Z])du(?![a-zA-Z])/i' => 'Sie',
            '/(?<![a-zA-Z])dein(?![a-zA-Z])/i' => 'Ihr',
            '/(?<![a-zA-Z])deine(?![a-zA-Z])/i' => 'Ihre',
            '/(?<![a-zA-Z])deinem(?![a-zA-Z])/i' => 'Ihrem',
            '/(?<![a-zA-Z])deinen(?![a-zA-Z])/i' => 'Ihren',
            '/(?<![a-zA-Z])deiner(?![a-zA-Z])/i' => 'Ihrer',
            '/(?<![a-zA-Z])deines(?![a-zA-Z])/i' => 'Ihres',
            '/(?<![a-zA-Z])dir(?![a-zA-Z])/i' => 'Ihnen',
            '/(?<![a-zA-Z])dich(?![a-zA-Z])/i' => 'Sie',
        ];
        
        foreach ($informal_to_formal as $pattern => $replacement) {
            $string = preg_replace($pattern, $replacement, $string);
        }
        
        return $string;
    }
    
    /**
     * Get encoding status for a file
     *
     * @since 4.1.0
     * @param string $file_path Path to the file
     * @return array Status information
     */
    public static function get_file_encoding_status($file_path) {
        $status = [
            'has_issues' => false,
            'issues' => [],
            'encoding' => 'UTF-8',
            'has_bom' => false
        ];
        
        if (!file_exists($file_path) || !is_readable($file_path)) {
            $status['error'] = 'File not accessible';
            return $status;
        }
        
        $content = file_get_contents($file_path);
        if ($content === false) {
            $status['error'] = 'Could not read file';
            return $status;
        }
        
        // Check for BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $status['has_bom'] = true;
        }
        
        // Check for encoding issues
        foreach (self::$encoding_fixes as $bad => $good) {
            if (strpos($content, $bad) !== false) {
                $status['has_issues'] = true;
                $count = substr_count($content, $bad);
                $status['issues'][] = [
                    'pattern' => $bad,
                    'replacement' => $good,
                    'count' => $count
                ];
            }
        }
        
        // Detect encoding
        if (function_exists('mb_detect_encoding')) {
            $detected = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            if ($detected) {
                $status['encoding'] = $detected;
            }
        }
        
        return $status;
    }
    
    /**
     * Scan directory for encoding issues
     *
     * @since 4.1.0
     * @param string $directory Directory to scan
     * @param array  $extensions File extensions to check
     * @return array Files with issues
     */
    public static function scan_directory_encoding($directory, $extensions = ['php', 'html']) {
        $files_with_issues = [];
        
        if (!is_dir($directory)) {
            return $files_with_issues;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
                if (in_array($ext, $extensions)) {
                    $status = self::get_file_encoding_status($file->getPathname());
                    if ($status['has_issues']) {
                        $files_with_issues[] = [
                            'path' => $file->getPathname(),
                            'status' => $status
                        ];
                    }
                }
            }
        }
        
        return $files_with_issues;
    }
}