<?php
/**
 * Template Loader for Enhanced Candidate Profiles
 *
 * @package MobilityTrailblazers
 * @since 2.4.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Template_Loader
 *
 * Handles loading of custom templates for candidate profiles
 */
class MT_Template_Loader {
    
    /**
     * Initialize template loader
     *
     * @return void
     */
    public static function init() {
        add_filter('template_include', [__CLASS__, 'load_candidate_template']);
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_enhanced_styles']);
    }
    
    /**
     * Load custom template for candidate profiles
     *
     * @param string $template Current template path
     * @return string Modified template path
     */
    public static function load_candidate_template($template) {
        // Only apply to single candidate posts
        if (!is_singular('mt_candidate')) {
            return $template;
        }
        
        // Check if enhanced template should be used
        $use_enhanced = get_option('mt_use_enhanced_template', true);
        
        if (!$use_enhanced) {
            return $template;
        }
        
        // Path to enhanced template
        // Use v2 template with automatic German section formatting
        $enhanced_template = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced-v2.php';
        
        // Fallback to original enhanced template if v2 doesn't exist
        if (!file_exists($enhanced_template)) {
            $enhanced_template = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate-enhanced.php';
        }
        
        // Check if enhanced template exists
        if (file_exists($enhanced_template)) {
            return $enhanced_template;
        }
        
        // Fallback to original template
        $original_template = MT_PLUGIN_DIR . 'templates/frontend/single/single-mt_candidate.php';
        
        if (file_exists($original_template)) {
            return $original_template;
        }
        
        // Final fallback to theme template
        return $template;
    }
    
    /**
     * Enqueue enhanced styles for candidate profiles
     *
     * @return void
     */
    public static function enqueue_enhanced_styles() {
        // Only load on candidate pages
        if (!is_singular('mt_candidate')) {
            return;
        }
        
        // Check if enhanced template is being used
        $use_enhanced = get_option('mt_use_enhanced_template', true);
        
        if (!$use_enhanced) {
            return;
        }
        
        // Enqueue enhanced styles
        wp_enqueue_style(
            'mt-enhanced-candidate-profile',
            MT_PLUGIN_URL . 'assets/css/enhanced-candidate-profile.css',
            ['mt-frontend'],
            MT_VERSION
        );
        
        // Enqueue hotfix for single candidate pages
        // TODO: Remove in v2.5.39 - All fixes have been merged into enhanced-candidate-profile.css
        // Keeping for now to ensure no visual regression
        if (is_singular('mt_candidate')) {
            wp_enqueue_style(
                'mt-candidate-single-hotfix',
                MT_PLUGIN_URL . 'assets/css/candidate-single-hotfix.css',
                [],
                '2025-08-19'
            );
        }
        
        // Add custom CSS for criterion colors and animations
        $custom_css = self::generate_custom_css();
        wp_add_inline_style('mt-enhanced-candidate-profile', $custom_css);
    }
    
    /**
     * Generate custom CSS for enhanced template
     *
     * @return string Custom CSS
     */
    private static function generate_custom_css() {
        $settings = get_option('mt_dashboard_settings', []);
        $primary_color = $settings['primary_color'] ?? '#003C3D';
        $accent_color = $settings['accent_color'] ?? '#C1693C';
        
        $css = "
        :root {
            --mt-dynamic-primary: {$primary_color};
            --mt-dynamic-accent: {$accent_color};
        }
        
        .mt-enhanced-candidate-profile .mt-candidate-hero {
            background: linear-gradient(135deg, {$primary_color} 0%, var(--mt-secondary) 50%, {$accent_color} 100%);
        }
        
        .mt-enhanced-candidate-profile .mt-section-header i {
            color: {$accent_color};
        }
        
        .mt-enhanced-candidate-profile .mt-cta-button:hover {
            background: {$accent_color};
        }
        ";
        
        return $css;
    }
    
    /**
     * Parse evaluation criteria from text content
     *
     * @param string $criteria_text Raw criteria text
     * @return array Parsed criteria
     */
    public static function parse_evaluation_criteria($criteria_text) {
        if (empty($criteria_text)) {
            return [];
        }
        
        $criteria_patterns = [
            'courage' => '/Mut\s*&\s*Pioniergeist[:\s]*(.+?)(?=Innovationsgrad|$)/is',
            'innovation' => '/Innovationsgrad[:\s]*(.+?)(?=Umsetzungskraft|$)/is',
            'implementation' => '/Umsetzungskraft\s*&\s*Wirkung[:\s]*(.+?)(?=Relevanz|$)/is',
            'relevance' => '/Relevanz\s*für\s*Mobilitätswende[:\s]*(.+?)(?=Vorbildfunktion|$)/is',
            'visibility' => '/Vorbildfunktion\s*&\s*Sichtbarkeit[:\s]*(.+?)(?=$)/is'
        ];
        
        $parsed_criteria = [];
        
        foreach ($criteria_patterns as $key => $pattern) {
            if (preg_match($pattern, $criteria_text, $matches)) {
                $content = trim($matches[1]);
                $content = preg_replace('/^[:\-\s]+/', '', $content); // Remove leading colons, dashes, spaces
                $content = preg_replace('/\s+/', ' ', $content); // Normalize whitespace
                
                if (!empty($content)) {
                    $parsed_criteria[$key] = $content;
                }
            }
        }
        
        return $parsed_criteria;
    }
    
    /**
     * Save parsed criteria as individual meta fields
     *
     * @param int $candidate_id Candidate post ID
     * @param string $criteria_text Raw criteria text
     * @return bool Success status
     */
    public static function save_parsed_criteria($candidate_id, $criteria_text) {
        $parsed = self::parse_evaluation_criteria($criteria_text);
        
        if (empty($parsed)) {
            return false;
        }
        
        $success = true;
        
        foreach ($parsed as $key => $content) {
            $meta_key = '_mt_criterion_' . $key;
            $result = update_post_meta($candidate_id, $meta_key, $content);
            
            if ($result === false) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Bulk process all candidates to parse their criteria
     *
     * @return array Processing results
     */
    public static function bulk_parse_criteria() {
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_mt_evaluation_criteria',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $results = [
            'processed' => 0,
            'success' => 0,
            'errors' => []
        ];
        
        foreach ($candidates as $candidate) {
            $results['processed']++;
            
            $criteria_text = get_post_meta($candidate->ID, '_mt_evaluation_criteria', true);
            
            if (empty($criteria_text)) {
                continue;
            }
            
            $success = self::save_parsed_criteria($candidate->ID, $criteria_text);
            
            if ($success) {
                $results['success']++;
            } else {
                $results['errors'][] = sprintf(
                    __('Failed to parse criteria for candidate: %s (ID: %d)', 'mobility-trailblazers'),
                    get_the_title($candidate->ID),
                    $candidate->ID
                );
            }
        }
        
        return $results;
    }
}

