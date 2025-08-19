<?php
/**
 * Shortcodes Registration
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

use MobilityTrailblazers\Public\Renderers\MT_Shortcode_Renderer;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Shortcodes
 *
 * Registers and handles shortcodes
 */
class MT_Shortcodes {
    
    /**
     * Renderer instance
     *
     * @var MT_Shortcode_Renderer
     */
    private $renderer;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize renderer
        require_once MT_PLUGIN_DIR . 'includes/public/renderers/class-mt-shortcode-renderer.php';
        $this->renderer = new MT_Shortcode_Renderer();
    }
    
    /**
     * Initialize shortcodes
     *
     * @return void
     */
    public function init() {
        add_shortcode('mt_jury_dashboard', [$this, 'render_jury_dashboard']);
        add_shortcode('mt_candidates_grid', [$this, 'render_candidates_grid']);
        add_shortcode('mt_evaluation_stats', [$this, 'render_evaluation_stats']);
        add_shortcode('mt_winners_display', [$this, 'render_winners_display']);
    }
    
    /**
     * Render jury dashboard shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_jury_dashboard($atts) {
        $atts = shortcode_atts([], $atts, 'mt_jury_dashboard');
        return $this->renderer->render_jury_dashboard($atts);
    }
    
    /**
     * Render candidates grid shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_candidates_grid($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'columns' => 3,
            'limit' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'show_bio' => 'yes',
            'show_category' => 'yes'
        ], $atts, 'mt_candidates_grid');
        
        return $this->renderer->render_candidates_grid($atts);
    }
    
    /**
     * Render evaluation statistics shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_evaluation_stats($atts) {
        $atts = shortcode_atts([
            'type' => 'summary', // summary, by-category, by-jury
            'show_chart' => 'yes'
        ], $atts, 'mt_evaluation_stats');
        
        return $this->renderer->render_evaluation_stats($atts);
    }
    
    /**
     * Render winners display shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     */
    public function render_winners_display($atts) {
        $atts = shortcode_atts([
            'category' => '',
            'year' => date('Y'),
            'limit' => 3,
            'show_scores' => 'no'
        ], $atts, 'mt_winners_display');
        
        return $this->renderer->render_winners_display($atts);
    }
}
