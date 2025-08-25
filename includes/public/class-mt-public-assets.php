<?php
/**
 * Public Assets Manager for CSS v4 Framework
 *
 * Handles conditional loading of CSS assets only on plugin-specific routes.
 * This class implements the v4 CSS framework that replaces the legacy CSS system.
 *
 * @package MobilityTrailblazers
 * @since 4.0.0
 */

namespace MobilityTrailblazers\Public;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Public_Assets
 *
 * Manages public-facing CSS assets with conditional loading
 */
class MT_Public_Assets {
    
    /**
     * CSS Framework version
     *
     * @var string
     */
    const V4_VERSION = '4.1.0';
    
    /**
     * Whether v4 framework is enabled
     *
     * @var bool|null
     */
    private $is_enabled = null;
    
    /**
     * Whether current environment is compatible
     *
     * @var bool|null
     */
    private $is_compatible = null;
    
    /**
     * Initialize the public assets manager
     *
     * @return void
     */
    public function init() {
        // Hook into WordPress asset loading
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets'], 15);
        add_action('wp_head', [$this, 'print_dynamic_tokens'], 1);
    }
    
    /**
     * Check if v4 framework is enabled
     *
     * @return bool
     */
    private function is_enabled() {
        if (is_null($this->is_enabled)) {
            // v4 CSS framework is always enabled
            $this->is_enabled = true;
        }
        return $this->is_enabled;
    }
    
    /**
     * Check if environment is compatible with v4
     *
     * @return bool
     */
    private function is_compatible() {
        if (is_null($this->is_compatible)) {
            $this->is_compatible = $this->check_compatibility();
        }
        return $this->is_compatible;
    }
    
    /**
     * Perform compatibility checks
     *
     * @return bool
     */
    private function check_compatibility() {
        // Check WordPress version (requires 6.0+)
        if (version_compare(get_bloginfo('version'), '6.0', '<')) {
            return false;
        }
        
        // Check for known conflicting plugins
        $conflicting_plugins = apply_filters('mt_v4_conflicting_plugins', [
            // Add any known conflicting plugins here
        ]);
        
        foreach ($conflicting_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if current page is a plugin-specific route
     *
     * @return bool
     */
    private function is_mt_public_route() {
        // Check specific page slugs
        if (is_page(['vote', 'mt_jury_dashboard', 'rankings', 'jury-dashboard'])) {
            return true;
        }
        
        // Check custom post type pages
        if (is_post_type_archive('mt_candidate') || is_singular('mt_candidate')) {
            return true;
        }
        
        // Check for jury member pages
        if (is_singular('mt_jury_member')) {
            return true;
        }
        
        // Check for shortcodes in content
        if ($this->has_mt_shortcodes()) {
            return true;
        }
        
        // Check for evaluation parameter in URL (sanitized)
        if (isset($_GET['evaluate']) && !empty(sanitize_text_field($_GET['evaluate']))) {
            return true;
        }
        
        // Check for category parameter
        if (isset($_GET['mt_category']) && !empty(sanitize_text_field($_GET['mt_category']))) {
            return true;
        }
        
        // Allow themes/plugins to add custom conditions
        return apply_filters('mt_is_plugin_route', false);
    }
    
    /**
     * Check if current page contains plugin shortcodes
     *
     * @param string|null $content Optional content to check
     * @return bool
     */
    private function has_mt_shortcodes($content = null) {
        if (is_null($content)) {
            global $post;
            if (!$post || !isset($post->post_content)) {
                return false;
            }
            $content = $post->post_content;
        }
        
        // List of plugin shortcodes
        $mt_shortcodes = [
            'mt_jury_dashboard',
            'mt_candidates_grid',
            'mt_evaluation_stats',
            'mt_winners_display',
            'mt_candidate_list',
            'mt_evaluation_form',
            'mt_rankings_table'
        ];
        
        // Check for each shortcode
        foreach ($mt_shortcodes as $shortcode) {
            if (has_shortcode($content, $shortcode)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Conditionally enqueue v4 CSS assets
     *
     * @return void
     */
    public function maybe_enqueue_assets() {
        // Check if v4 is enabled and compatible
        if (!$this->is_enabled() || !$this->is_compatible()) {
            return;
        }
        
        // Only load on plugin routes
        if (!$this->is_mt_public_route()) {
            return;
        }
        
        // Register all v4 styles first (WordPress best practice)
        $this->register_v4_styles();
        
        // Enqueue styles in dependency order
        wp_enqueue_style('mt-critical');
        wp_enqueue_style('mt-core');
        wp_enqueue_style('mt-components');
        wp_enqueue_style('mt-brand-fixes');
        // mt-header-gradient-fix removed - file deleted
        wp_enqueue_style('mt-mobile');
        wp_enqueue_style('mt-specificity-layer');
        
        // Enqueue progress bar fix CSS
        wp_enqueue_style(
            'mt-progress-bar-fix',
            MT_PLUGIN_URL . 'assets/css/mt-progress-bar-fix.css',
            ['mt-core'],
            self::V4_VERSION,
            'all'
        );
        // Progress bar styles removed - feature deleted
        
        // Consolidated CSS fixes are now included in mt-core.css and mt-components.css
        // All emergency fixes, frontend critical fixes, hotfixes, modal fixes, and medal fixes
        // have been merged into the new consolidated CSS architecture
        
        // Optionally optimize third-party CSS
        $this->maybe_optimize_third_party_css();
        
        // Add inline styles for dynamic adjustments
        $this->add_inline_styles();
    }
    
    /**
     * Register v4 CSS files with WordPress
     *
     * @return void
     */
    private function register_v4_styles() {
        $base_url = MT_PLUGIN_URL . 'assets/css/';
        
        // Use cache busting version with file modification time
        $version = $this->get_asset_version();
        
        // Use minified files in production
        $suffix = $this->get_asset_suffix();
        
        // Register critical above-fold styles
        wp_register_style(
            'mt-critical',
            $base_url . 'mt-critical' . $suffix . '.css',
            [],
            $version
        );
        
        // Register core consolidated styles
        wp_register_style(
            'mt-core',
            $base_url . 'mt-core' . $suffix . '.css',
            ['mt-critical'],
            $version
        );
        
        // Register component styles
        wp_register_style(
            'mt-components',
            $base_url . 'mt-components' . $suffix . '.css',
            ['mt-core'],
            $version
        );
        
        // Progress bar CSS removed - feature deleted
        
        // Header gradient fix removed - using mt-brand-fixes.css instead
        
        // Register brand fixes for header gradient and styling
        wp_register_style(
            'mt-brand-fixes',
            $base_url . 'mt-brand-fixes.css',
            ['mt-core'],
            $version
        );
        
        // Register admin-specific styles (only for admin pages)
        if (is_admin()) {
            wp_register_style(
                'mt-admin',
                $base_url . 'mt-admin' . $suffix . '.css',
                ['mt-core'],
                $version
            );
        }
        
        // Register mobile-specific styles
        wp_register_style(
            'mt-mobile',
            $base_url . 'mt-mobile' . $suffix . '.css',
            ['mt-core'],
            $version,
            'screen and (max-width: 768px)' // Add media query for mobile
        );
        
        // Register specificity layer for cascade management
        wp_register_style(
            'mt-specificity-layer',
            $base_url . 'mt-specificity-layer' . $suffix . '.css',
            ['mt-core'],
            $version,
            'all'
        );
    }
    
    /**
     * Print dynamic CSS tokens based on plugin settings
     *
     * @return void
     */
    public function print_dynamic_tokens() {
        // Only on plugin routes
        if (!$this->is_mt_public_route()) {
            return;
        }
        
        // Get brand colors from settings
        $colors = get_option('mt_brand_colors', []);
        $dashboard_settings = get_option('mt_dashboard_settings', []);
        
        // Merge colors from different sources
        if (!empty($dashboard_settings['primary_color'])) {
            $colors['primary'] = $dashboard_settings['primary_color'];
        }
        if (!empty($dashboard_settings['secondary_color'])) {
            $colors['secondary'] = $dashboard_settings['secondary_color'];
        }
        
        // If no custom colors, don't output anything
        if (empty($colors)) {
            return;
        }
        
        // Build CSS variables
        $css = ':root {';
        
        if (!empty($colors['primary'])) {
            $css .= '--mt-color-primary: ' . esc_attr($colors['primary']) . ';';
            // Auto-generate darker variant
            $css .= '--mt-color-primary-dark: ' . $this->darken_color($colors['primary'], 10) . ';';
            $css .= '--mt-color-primary-light: ' . $this->lighten_color($colors['primary'], 10) . ';';
        }
        
        if (!empty($colors['secondary'])) {
            $css .= '--mt-color-secondary: ' . esc_attr($colors['secondary']) . ';';
        }
        
        if (!empty($colors['accent'])) {
            $css .= '--mt-color-accent: ' . esc_attr($colors['accent']) . ';';
        }
        
        if (!empty($colors['bg'])) {
            $css .= '--mt-color-bg: ' . esc_attr($colors['bg']) . ';';
        }
        
        $css .= '}';
        
        // Output inline style
        echo '<style id="mt-dynamic-tokens">' . $css . '</style>' . "\n";
    }
    
    /**
     * Add inline styles for dynamic adjustments
     *
     * @return void
     */
    private function add_inline_styles() {
        $inline_css = '';
        
        // Add any dynamic inline styles based on settings
        $presentation = get_option('mt_candidate_presentation', []);
        
        // Photo style adjustments
        if (!empty($presentation['photo_style'])) {
            if ($presentation['photo_style'] === 'circle') {
                $inline_css .= '.mt-candidate-card__image { border-radius: 50%; }';
            } elseif ($presentation['photo_style'] === 'rounded') {
                $inline_css .= '.mt-candidate-card__image { border-radius: var(--mt-radius-sm); }';
            }
        }
        
        // Add inline styles if any
        if (!empty($inline_css)) {
            wp_add_inline_style('mt-components', $inline_css);
        }
        
        // Progress bar inline styles removed - feature deleted
    }
    
    /**
     * Optimize third-party CSS on plugin pages
     *
     * @return void
     */
    private function maybe_optimize_third_party_css() {
        // Only on specific plugin pages
        if (!is_page(['vote', 'mt_jury_dashboard', 'jury-dashboard'])) {
            return;
        }
        
        // Reduce Elementor CSS impact (don't fully dequeue to avoid breaking layouts)
        add_action('wp_print_styles', function() {
            // Add minimal overrides to neutralize Elementor styles in plugin areas
            $override_css = '
                .mt-root .elementor-widget-container {
                    all: unset;
                    display: block;
                }
                .mt-root .elementor-column-gap-default {
                    gap: inherit;
                }
            ';
            
            wp_add_inline_style('mt-core', $override_css);
        }, 100);
    }
    
    /**
     * Darken a color by a percentage
     *
     * @param string $color Hex color
     * @param int $percent Percentage to darken
     * @return string
     */
    private function darken_color($color, $percent) {
        // Remove # if present
        $color = ltrim($color, '#');
        
        // Convert to RGB
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        
        // Darken
        $r = max(0, min(255, $r - ($r * $percent / 100)));
        $g = max(0, min(255, $g - ($g * $percent / 100)));
        $b = max(0, min(255, $b - ($b * $percent / 100)));
        
        // Convert back to hex
        return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Lighten a color by a percentage
     *
     * @param string $color Hex color
     * @param int $percent Percentage to lighten
     * @return string
     */
    private function lighten_color($color, $percent) {
        // Remove # if present
        $color = ltrim($color, '#');
        
        // Convert to RGB
        $r = hexdec(substr($color, 0, 2));
        $g = hexdec(substr($color, 2, 2));
        $b = hexdec(substr($color, 4, 2));
        
        // Lighten
        $r = min(255, $r + ((255 - $r) * $percent / 100));
        $g = min(255, $g + ((255 - $g) * $percent / 100));
        $b = min(255, $b + ((255 - $b) * $percent / 100));
        
        // Convert back to hex
        return '#' . sprintf('%02x%02x%02x', $r, $g, $b);
    }
    
    /**
     * Get asset version for cache busting
     *
     * @return string Version string
     * @since 4.1.0
     */
    private function get_asset_version() {
        // In development, use file modification time for instant cache busting
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $css_file = MT_PLUGIN_DIR . 'assets/css/mt-core.css';
            if (file_exists($css_file)) {
                return self::V4_VERSION . '.' . filemtime($css_file);
            }
        }
        
        // In production, use plugin version
        return self::V4_VERSION;
    }
    
    /**
     * Get asset suffix for minified files
     *
     * @return string File suffix
     * @since 4.1.0
     */
    private function get_asset_suffix() {
        // Use minified files in production
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Check if minified version exists
            $test_file = MT_PLUGIN_DIR . 'assets/css/mt-core.min.css';
            if (file_exists($test_file)) {
                return '.min';
            }
        }
        
        // Use non-minified in development or if minified doesn't exist
        return '';
    }
}