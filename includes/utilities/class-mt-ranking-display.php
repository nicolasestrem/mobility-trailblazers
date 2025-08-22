<?php
/**
 * Ranking Display Utility Class
 * 
 * Handles all ranking, medal, and position badge displays
 * 
 * @package MobilityTrailblazers
 * @since 2.5.19
 */

namespace MobilityTrailblazers\Utilities;

if (!defined('ABSPATH')) {
    exit;
}

class MT_Ranking_Display {
    
    /**
     * Get position badge HTML
     * 
     * @param int $position The ranking position
     * @param array $args Optional arguments
     * @return string HTML output
     */
    public static function get_position_badge($position, $args = []) {
        $defaults = [
            'show_medal' => true,
            'show_number' => true,
            'size' => 'medium', // small, medium, large
            'context' => 'default' // default, table, card, hero
        ];
        
        $args = wp_parse_args($args, $defaults);
        $position = intval($position);
        
        // Determine position class using v4 BEM naming
        $position_class = '';
        if ($position === 1) {
            $position_class = 'mt-ranking-badge--gold';
        } elseif ($position === 2) {
            $position_class = 'mt-ranking-badge--silver';
        } elseif ($position === 3) {
            $position_class = 'mt-ranking-badge--bronze';
        } else {
            $position_class = 'mt-ranking-badge--standard';
        }
        
        // Size class with BEM modifier
        $size_class = 'mt-ranking-badge--' . $args['size'];
        
        // Context class with BEM modifier
        $context_class = 'mt-ranking-badge--' . $args['context'];
        
        // Build the HTML with proper ARIA labels
        $aria_label = sprintf(
            /* translators: %d: ranking position */
            __('Rank %d', 'mobility-trailblazers'),
            $position
        );
        
        $html = sprintf(
            '<div class="mt-ranking-badge %s %s %s" data-position="%d" aria-label="%s" role="img">',
            esc_attr($position_class),
            esc_attr($size_class),
            esc_attr($context_class),
            $position,
            esc_attr($aria_label)
        );
        
        // Add medal icon for top 3
        if ($args['show_medal'] && $position <= 3) {
            $html .= self::get_medal_svg($position);
        }
        
        // Add position number
        if ($args['show_number']) {
            $html .= sprintf(
                '<span class="mt-rank-number">%d</span>',
                $position
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Get medal SVG icon
     * 
     * @param int $position
     * @return string SVG HTML
     */
    private static function get_medal_svg($position) {
        $color_class = '';
        if ($position === 1) {
            $color_class = 'mt-medal-gold';
        } elseif ($position === 2) {
            $color_class = 'mt-medal-silver';
        } elseif ($position === 3) {
            $color_class = 'mt-medal-bronze';
        }
        
        // Return a proper medal SVG with accessibility
        return sprintf(
            '<svg class="mt-medal-icon %s" width="24" height="24" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <g class="mt-medal-ribbon">
                    <path d="M7 2L9 7L12 3L15 7L17 2L17 10L12 13L7 10Z" fill="currentColor" opacity="0.3"/>
                </g>
                <circle class="mt-medal-circle" cx="12" cy="16" r="6" fill="currentColor"/>
                <text x="12" y="19" text-anchor="middle" class="mt-medal-text" fill="white" font-size="8" font-weight="bold">%d</text>
            </svg>',
            esc_attr($color_class),
            $position
        );
    }
    
    /**
     * Get ranking table row HTML
     * 
     * @param int $position
     * @param array $candidate_data
     * @return string HTML output
     */
    public static function get_ranking_row($position, $candidate_data) {
        $position_class = '';
        if ($position === 1) $position_class = 'position-gold';
        elseif ($position === 2) $position_class = 'position-silver';
        elseif ($position === 3) $position_class = 'position-bronze';
        
        $html = sprintf(
            '<tr class="mt-ranking-row %s">',
            esc_attr($position_class)
        );
        
        // Position cell
        $html .= '<td class="mt-rank-cell">';
        $html .= self::get_position_badge($position, [
            'show_medal' => ($position <= 3),
            'show_number' => true,
            'size' => 'small',
            'context' => 'table'
        ]);
        $html .= '</td>';
        
        // Candidate info cell
        $html .= '<td class="mt-candidate-cell">';
        $html .= sprintf(
            '<div class="mt-candidate-info">
                <span class="mt-candidate-name">%s</span>',
            esc_html($candidate_data['name'])
        );
        
        if (!empty($candidate_data['organization'])) {
            $html .= sprintf(
                '<span class="mt-candidate-org">%s</span>',
                esc_html($candidate_data['organization'])
            );
        }
        
        $html .= '</div></td>';
        
        // Score cell (if applicable)
        if (isset($candidate_data['score'])) {
            $html .= sprintf(
                '<td class="mt-score-cell">
                    <span class="mt-score-value">%.1f</span>
                </td>',
                floatval($candidate_data['score'])
            );
        }
        
        $html .= '</tr>';
        
        return $html;
    }
    
    /**
     * Get winner card HTML
     * 
     * @param int $position
     * @param array $candidate_data
     * @return string HTML output
     */
    public static function get_winner_card($position, $candidate_data) {
        $position_class = '';
        if ($position === 1) $position_class = 'mt-winner-gold';
        elseif ($position === 2) $position_class = 'mt-winner-silver';
        elseif ($position === 3) $position_class = 'mt-winner-bronze';
        
        $html = sprintf(
            '<div class="mt-winner-card %s">',
            esc_attr($position_class)
        );
        
        // Position badge
        $html .= self::get_position_badge($position, [
            'show_medal' => true,
            'show_number' => true,
            'size' => 'large',
            'context' => 'card'
        ]);
        
        // Candidate photo
        if (!empty($candidate_data['photo'])) {
            $html .= sprintf(
                '<div class="mt-winner-photo">
                    <img src="%s" alt="%s">
                </div>',
                esc_url($candidate_data['photo']),
                esc_attr($candidate_data['name'])
            );
        }
        
        // Candidate info
        $html .= sprintf(
            '<h3 class="mt-winner-name">%s</h3>',
            esc_html($candidate_data['name'])
        );
        
        if (!empty($candidate_data['position'])) {
            $html .= sprintf(
                '<div class="mt-winner-title">%s</div>',
                esc_html($candidate_data['position'])
            );
        }
        
        if (!empty($candidate_data['organization'])) {
            $html .= sprintf(
                '<div class="mt-winner-org">%s</div>',
                esc_html($candidate_data['organization'])
            );
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
