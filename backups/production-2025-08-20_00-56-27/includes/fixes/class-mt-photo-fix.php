<?php
/**
 * Photo Fix for Issue #13
 * Specifically fixes Friedrich Dräxlmaier's photo positioning
 *
 * @package MobilityTrailblazers
 * @since 2.5.26
 */

namespace MobilityTrailblazers\Fixes;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Photo_Fix
 *
 * Applies specific photo positioning fixes
 */
class MT_Photo_Fix {
    
    /**
     * Initialize the fix
     */
    public static function init() {
        // Add inline styles to head
        add_action('wp_head', [__CLASS__, 'add_inline_styles'], 999);
        
        // Add body class for targeting
        add_filter('body_class', [__CLASS__, 'add_body_class']);
    }
    
    /**
     * Add inline styles for photo fixes
     */
    public static function add_inline_styles() {
        ?>
        <style type="text/css" id="mt-photo-fix-inline">
            /* CRITICAL FIX for Issue #13 - Friedrich Dräxlmaier photo */
            
            /* Force fix for grid view */
            [data-candidate-id="4627"] .mt-candidate-image {
                overflow: hidden !important;
                position: relative !important;
            }
            
            [data-candidate-id="4627"] img,
            [data-candidate-id="4627"] .mt-candidate-photo,
            [data-candidate-id="4627"] .mt-candidate-image img,
            .mt-candidate-grid-item[data-candidate-id="4627"] img {
                object-position: 50% 15% !important;
                object-fit: cover !important;
            }
            
            /* Specific override for any context */
            img[src*="FriedrichDr"] {
                object-position: 50% 15% !important;
                object-fit: cover !important;
            }
            
            /* Target by partial URL match */
            a[href*="friedrich-draexlmaier"] img {
                object-position: 50% 15% !important;
                object-fit: cover !important;
            }
            
            /* Override all other styles with maximum specificity */
            html body .mt-candidates-grid .mt-candidate-grid-item[data-candidate-id="4627"] .mt-candidate-image img,
            html body .elementor .mt-candidates-grid .mt-candidate-grid-item[data-candidate-id="4627"] .mt-candidate-image img,
            html body .mt-candidate-grid-item[data-candidate-id="4627"] .mt-candidate-link .mt-candidate-image img.mt-candidate-photo,
            html body .mt-candidate-grid-item[data-candidate-id="4627"] .mt-candidate-link .mt-candidate-image img.wp-post-image {
                object-position: center 15% !important;
                object-fit: cover !important;
            }
        </style>
        <?php
    }
    
    /**
     * Add body class for additional targeting
     */
    public static function add_body_class($classes) {
        $classes[] = 'mt-photo-fix-active';
        return $classes;
    }
}
