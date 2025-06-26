<?php
/**
 * Jury Rankings Partial Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="mt-rankings-section">
    <div class="mt-rankings-header">
        <h2><?php _e('Top Ranked Candidates', 'mobility-trailblazers'); ?></h2>
        <p class="mt-rankings-description">
            <?php _e('Your current ranking based on evaluation scores. Click on a candidate to review or update their evaluation.', 'mobility-trailblazers'); ?>
        </p>
    </div>
    
    <?php if (!empty($rankings)) : ?>
        <div class="mt-rankings-list">
            <?php 
            $position = 1;
            foreach ($rankings as $candidate) : 
                $medal_class = '';
                if ($position === 1) $medal_class = 'gold';
                elseif ($position === 2) $medal_class = 'silver';
                elseif ($position === 3) $medal_class = 'bronze';
            ?>
                <div class="mt-ranking-item <?php echo $medal_class; ?>" data-candidate-id="<?php echo esc_attr($candidate->candidate_id); ?>">
                    <div class="mt-ranking-position">
                        <span class="mt-position-number"><?php echo $position; ?></span>
                        <?php if ($position <= 3) : ?>
                            <span class="mt-medal-icon"></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-ranking-details">
                        <h3 class="mt-ranking-name">
                            <a href="<?php echo esc_url(add_query_arg('evaluate', $candidate->candidate_id)); ?>">
                                <?php echo esc_html($candidate->candidate_name); ?>
                            </a>
                        </h3>
                        <?php if (!empty($candidate->organization)) : ?>
                            <p class="mt-ranking-org"><?php echo esc_html($candidate->organization); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-ranking-scores">
                        <div class="mt-total-score">
                            <span class="mt-score-value"><?php echo number_format($candidate->total_score, 1); ?></span>
                            <span class="mt-score-label"><?php _e('Total', 'mobility-trailblazers'); ?></span>
                        </div>
                        
                        <div class="mt-score-breakdown">
                            <div class="mt-mini-scores">
                                <span title="<?php _e('Courage & Pioneer Spirit', 'mobility-trailblazers'); ?>">
                                    <?php echo number_format($candidate->courage_score, 1); ?>
                                </span>
                                <span title="<?php _e('Innovation Degree', 'mobility-trailblazers'); ?>">
                                    <?php echo number_format($candidate->innovation_score, 1); ?>
                                </span>
                                <span title="<?php _e('Implementation & Impact', 'mobility-trailblazers'); ?>">
                                    <?php echo number_format($candidate->implementation_score, 1); ?>
                                </span>
                                <span title="<?php _e('Mobility Transformation Relevance', 'mobility-trailblazers'); ?>">
                                    <?php echo number_format($candidate->relevance_score, 1); ?>
                                </span>
                                <span title="<?php _e('Role Model & Visibility', 'mobility-trailblazers'); ?>">
                                    <?php echo number_format($candidate->visibility_score, 1); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-ranking-actions">
                        <a href="<?php echo esc_url(add_query_arg('evaluate', $candidate->candidate_id)); ?>" 
                           class="mt-btn mt-btn-small mt-btn-primary">
                            <?php _e('Review', 'mobility-trailblazers'); ?>
                        </a>
                    </div>
                </div>
            <?php 
                $position++;
            endforeach; 
            ?>
        </div>
    <?php else : ?>
        <div class="mt-no-rankings">
            <p><?php _e('No completed evaluations yet. Start evaluating candidates to see rankings.', 'mobility-trailblazers'); ?></p>
        </div>
    <?php endif; ?>
</div>
