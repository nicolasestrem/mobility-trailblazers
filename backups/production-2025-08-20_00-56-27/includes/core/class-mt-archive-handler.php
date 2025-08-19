<?php
/**
 * Archive Handler for Candidate Grid Display
 *
 * @package MobilityTrailblazers
 * @since 2.5.27
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Archive_Handler
 *
 * Handles the display of candidate archives with proper grid layout
 */
class MT_Archive_Handler {
    
    /**
     * Initialize archive handler
     */
    public static function init() {
        // Add body class for candidate archive
        add_filter('body_class', [__CLASS__, 'add_archive_body_class']);
        
        // Wrap archive posts in grid container
        add_action('loop_start', [__CLASS__, 'open_grid_container']);
        add_action('loop_end', [__CLASS__, 'close_grid_container']);
        
        // Add custom CSS for archive grid
        add_action('wp_head', [__CLASS__, 'add_archive_grid_styles']);
        
        // Filter the post class for individual candidates
        add_filter('post_class', [__CLASS__, 'add_candidate_card_class'], 10, 3);
    }
    
    /**
     * Add body class for candidate archive pages
     */
    public static function add_archive_body_class($classes) {
        if (is_post_type_archive('mt_candidate')) {
            $classes[] = 'mt-candidates-archive';
            
            // Get card layout setting
            $settings = get_option('mt_dashboard_settings', []);
            $layout = $settings['card_layout'] ?? 'grid';
            
            if ($layout === 'grid') {
                $classes[] = 'mt-grid-layout';
            }
        }
        return $classes;
    }
    
    /**
     * Open grid container before loop
     */
    public static function open_grid_container($query) {
        if (!is_post_type_archive('mt_candidate') || !$query->is_main_query()) {
            return;
        }
        
        $settings = get_option('mt_dashboard_settings', []);
        $layout = $settings['card_layout'] ?? 'grid';
        
        if ($layout === 'grid') {
            echo '<div class="mt-candidates-grid columns-3">';
        }
    }
    
    /**
     * Close grid container after loop
     */
    public static function close_grid_container($query) {
        if (!is_post_type_archive('mt_candidate') || !$query->is_main_query()) {
            return;
        }
        
        $settings = get_option('mt_dashboard_settings', []);
        $layout = $settings['card_layout'] ?? 'grid';
        
        if ($layout === 'grid') {
            echo '</div><!-- .mt-candidates-grid -->';
        }
    }
    
    /**
     * Add candidate card class to post classes
     */
    public static function add_candidate_card_class($classes, $class, $post_id) {
        if (get_post_type($post_id) === 'mt_candidate' && is_post_type_archive('mt_candidate')) {
            $classes[] = 'mt-candidate-card';
        }
        return $classes;
    }
    
    /**
     * Add custom CSS for archive grid
     */
    public static function add_archive_grid_styles() {
        if (!is_post_type_archive('mt_candidate')) {
            return;
        }
        
        $settings = get_option('mt_dashboard_settings', []);
        $layout = $settings['card_layout'] ?? 'grid';
        
        if ($layout !== 'grid') {
            return;
        }
        
        ?>
        <style type="text/css">
            /* Force grid layout for candidate archive */
            .mt-candidates-archive .mt-candidates-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 30px !important;
                padding: 30px 0 !important;
                max-width: 100% !important;
            }
            
            @media (max-width: 1200px) {
                .mt-candidates-archive .mt-candidates-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
            }
            
            @media (max-width: 768px) {
                .mt-candidates-archive .mt-candidates-grid {
                    grid-template-columns: 1fr !important;
                }
            }
            
            .mt-candidates-archive .mt-candidate-card {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                background: #fff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            
            .mt-candidates-archive .mt-candidate-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            }
            
            .mt-candidates-archive .mt-candidate-card .entry-header,
            .mt-candidates-archive .mt-candidate-card .entry-content {
                padding: 20px;
            }
            
            .mt-candidates-archive .mt-candidate-card .post-thumbnail {
                width: 100%;
                height: 250px;
                object-fit: cover;
            }
            
            .mt-candidates-archive .mt-candidate-card .entry-title {
                font-size: 1.3em;
                margin: 0 0 10px 0;
            }
            
            .mt-candidates-archive .mt-candidate-card .entry-meta {
                font-size: 0.9em;
                color: #666;
            }
            
            /* Remove default theme styles that might conflict */
            .mt-candidates-archive article.mt_candidate {
                float: none !important;
                width: 100% !important;
                margin: 0 !important;
            }
        </style>
        <?php
    }
}
