<?php
/**
 * Mobile Styles Injector
 * 
 * Injects critical mobile CSS inline for immediate rendering
 * 
 * @package MobilityTrailblazers
 * @since 4.1.0
 */

namespace MobilityTrailblazers\Public;

if (!defined('ABSPATH')) {
    exit;
}

class MT_Mobile_Styles {
    
    /**
     * Initialize mobile styles
     */
    public function init() {
        add_action('wp_head', [$this, 'inject_critical_mobile_css'], 999);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_mobile_scripts'], 20);
    }
    
    /**
     * Inject critical mobile CSS directly into head
     */
    public function inject_critical_mobile_css() {
        // Check various conditions for jury dashboard pages
        $is_jury_page = false;
        
        // Check page slugs
        if (is_page(['vote', 'mt_jury_dashboard', 'jury-dashboard'])) {
            $is_jury_page = true;
        }
        
        // Check for evaluation parameter
        if (isset($_GET['evaluate']) && !empty($_GET['evaluate'])) {
            $is_jury_page = true;
        }
        
        // Check for shortcode in content
        global $post;
        if ($post && (has_shortcode($post->post_content, 'mt_jury_dashboard') || 
                     has_shortcode($post->post_content, 'mt_evaluation_form'))) {
            $is_jury_page = true;
        }
        
        // Check if we're on the homepage with jury content (root URL scenario)
        if (is_front_page() && $post && has_shortcode($post->post_content, 'mt_jury_dashboard')) {
            $is_jury_page = true;
        }
        
        if (!$is_jury_page) {
            return;
        }
        ?>
        <style id="mt-mobile-critical-css">
        /* Critical Mobile Styles - Inline for immediate rendering */
        @media (max-width: 767px) {
            /* Force table to card layout */
            .mt-evaluation-table-wrap {
                overflow: visible !important;
                width: 100% !important;
                -webkit-overflow-scrolling: auto !important;
            }
            
            .mt-evaluation-table {
                display: block !important;
                width: 100% !important;
                border-collapse: collapse !important;
                border-spacing: 0 !important;
            }
            
            .mt-evaluation-table thead {
                position: absolute !important;
                top: -9999px !important;
                left: -9999px !important;
            }
            
            .mt-evaluation-table tbody {
                display: block !important;
            }
            
            .mt-evaluation-table tr {
                display: block !important;
                margin-bottom: 15px !important;
                background: white !important;
                border: 1px solid #ddd !important;
                border-radius: 8px !important;
                padding: 15px !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
                position: relative !important;
            }
            
            .mt-evaluation-table td {
                display: block !important;
                text-align: left !important;
                padding: 8px 0 !important;
                border: none !important;
                position: relative !important;
            }
            
            /* Add labels before each cell */
            .mt-evaluation-table td[data-label]:before {
                content: attr(data-label) ": ";
                font-weight: bold;
                display: inline-block;
                width: 120px;
                color: #666;
            }
            
            /* Rank cell styling */
            .mt-evaluation-table .mt-eval-rank {
                position: absolute !important;
                top: 10px !important;
                right: 10px !important;
                width: auto !important;
                padding: 0 !important;
            }
            
            /* Candidate info prominent */
            .mt-evaluation-table .mt-eval-candidate {
                padding-right: 60px !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                border-bottom: 1px solid #eee !important;
                padding-bottom: 10px !important;
                margin-bottom: 10px !important;
            }
            
            .mt-evaluation-table .mt-candidate-name {
                display: block !important;
                color: #2c3e50 !important;
                margin-bottom: 5px !important;
            }
            
            .mt-evaluation-table .mt-candidate-meta {
                font-size: 14px !important;
                color: #666 !important;
                font-weight: normal !important;
            }
            
            /* Score inputs in grid */
            .mt-evaluation-table td:has(.mt-eval-score-input) {
                display: inline-block !important;
                width: 30% !important;
                margin: 5px 1.5% !important;
                text-align: center !important;
                padding: 5px !important;
            }
            
            .mt-evaluation-table .mt-eval-score-input {
                width: 100% !important;
                padding: 10px 5px !important;
                font-size: 16px !important;
                text-align: center !important;
                border: 1px solid #ddd !important;
                border-radius: 4px !important;
                min-height: 44px !important;
            }
            
            /* Total score prominent */
            .mt-evaluation-table .mt-eval-total-score {
                background: #26a69a !important;
                color: white !important;
                padding: 10px !important;
                border-radius: 6px !important;
                text-align: center !important;
                font-size: 18px !important;
                font-weight: bold !important;
                margin: 10px 0 !important;
            }
            
            .mt-evaluation-table .mt-eval-total-score:before {
                content: "Total: " !important;
                font-size: 14px !important;
                opacity: 0.9 !important;
            }
            
            /* Action buttons full width */
            .mt-evaluation-table .mt-eval-actions {
                display: flex !important;
                flex-direction: column !important;
                gap: 8px !important;
                margin-top: 10px !important;
                padding-top: 10px !important;
                border-top: 1px solid #eee !important;
            }
            
            .mt-evaluation-table .mt-eval-actions button,
            .mt-evaluation-table .mt-eval-actions a {
                width: 100% !important;
                padding: 12px !important;
                text-align: center !important;
                border-radius: 6px !important;
                min-height: 44px !important;
                font-size: 14px !important;
                text-decoration: none !important;
                display: block !important;
            }
            
            .mt-evaluation-table .mt-btn-save-eval {
                background: #26a69a !important;
                color: white !important;
                border: none !important;
            }
            
            .mt-evaluation-table .mt-btn-full-evaluation {
                background: white !important;
                color: #26a69a !important;
                border: 1px solid #26a69a !important;
            }
            
            /* Hide horizontal scroll */
            body .mt-rankings-section {
                overflow-x: hidden !important;
            }
            
            /* Stats grid mobile */
            .mt-stats-grid,
            .mt-jury-stats {
                grid-template-columns: 1fr !important;
                gap: 10px !important;
            }
            
            @media (min-width: 375px) {
                .mt-stats-grid,
                .mt-jury-stats {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
            }
            
            /* Search and filter controls */
            .mt-search-controls,
            .mt-filter-controls,
            .mt-filters-section {
                display: flex !important;
                flex-direction: column !important;
                gap: 10px !important;
            }
            
            .mt-search-controls input,
            .mt-filter-controls select,
            input[type="search"],
            select {
                width: 100% !important;
                min-height: 44px !important;
                padding: 10px !important;
                font-size: 16px !important;
            }
            
            /* Dashboard header mobile */
            .mt-dashboard-header {
                padding: 20px 15px !important;
                text-align: center !important;
            }
            
            .mt-dashboard-header h1 {
                font-size: 24px !important;
            }
            
            /* Progress bar mobile */
            .mt-progress-bar {
                height: 32px !important;
            }
            
            /* Candidate cards mobile */
            .mt-candidates-grid {
                grid-template-columns: 1fr !important;
            }
            
            @media (min-width: 414px) {
                .mt-candidates-grid {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
            }
        }
        
        /* Additional mobile helpers */
        @media (max-width: 767px) {
            .hide-mobile { display: none !important; }
            .show-mobile { display: block !important; }
            
            /* Touch feedback */
            .mt-touch-active {
                background: #f0f0f0 !important;
                transform: scale(0.98);
            }
            
            /* Loading spinner */
            .spin {
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        }
        </style>
        <?php
    }
    
    /**
     * Enqueue mobile JavaScript
     */
    public function enqueue_mobile_scripts() {
        // Check various conditions for jury dashboard pages
        $is_jury_page = false;
        
        // Check page slugs
        if (is_page(['vote', 'mt_jury_dashboard', 'jury-dashboard'])) {
            $is_jury_page = true;
        }
        
        // Check for evaluation parameter
        if (isset($_GET['evaluate']) && !empty($_GET['evaluate'])) {
            $is_jury_page = true;
        }
        
        // Check for shortcode in content
        global $post;
        if ($post && (has_shortcode($post->post_content, 'mt_jury_dashboard') || 
                     has_shortcode($post->post_content, 'mt_evaluation_form'))) {
            $is_jury_page = true;
        }
        
        // Check if we're on the homepage with jury content
        if (is_front_page() && $post && has_shortcode($post->post_content, 'mt_jury_dashboard')) {
            $is_jury_page = true;
        }
        
        if (!$is_jury_page) {
            return;
        }
        
        wp_enqueue_script(
            'mt-mobile-jury',
            MT_PLUGIN_URL . 'assets/js/mt-mobile-jury.js',
            ['jquery'],
            '4.1.0',
            true
        );
        
        // Localize script
        wp_localize_script('mt-mobile-jury', 'MT_Mobile', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mt_ajax_nonce'),
            'strings' => [
                'yourRankings' => __('Ihre Rangliste', 'mobility-trailblazers'),
                'tapToEdit' => __('Tippen Sie zum Bearbeiten der Bewertungen', 'mobility-trailblazers'),
                'saving' => __('Speichern...', 'mobility-trailblazers'),
                'saved' => __('Gespeichert!', 'mobility-trailblazers'),
            ]
        ]);
    }
}

// Initialize if not already done
add_action('init', function() {
    $mobile_styles = new MT_Mobile_Styles();
    $mobile_styles->init();
});