<?php
/**
 * Enhanced Jury Rankings Grid Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define criteria labels
$criteria_labels = [
    'courage' => __('Courage', 'mobility-trailblazers'),
    'innovation' => __('Innovation', 'mobility-trailblazers'),
    'implementation' => __('Impact', 'mobility-trailblazers'),
    'relevance' => __('Relevance', 'mobility-trailblazers'),
    'visibility' => __('Visibility', 'mobility-trailblazers')
];
?>

<div class="mt-rankings-section">
    <div class="mt-rankings-header">
        <h2><?php _e('Top Ranked Candidates', 'mobility-trailblazers'); ?></h2>
        <p class="mt-rankings-description">
            <?php _e('Your evaluation rankings at a glance. Click any candidate card to review or update their scores.', 'mobility-trailblazers'); ?>
        </p>
    </div>
    
    <?php if (!empty($rankings)) : ?>
        <div class="mt-rankings-list">
            <?php 
            $position = 1;
            foreach ($rankings as $candidate) : 
                $score_percentage = ($candidate->total_score / 10) * 100;
            ?>
                <div class="mt-ranking-item" 
                     data-candidate-id="<?php echo esc_attr($candidate->candidate_id); ?>"
                     onclick="window.location.href='<?php echo esc_url(add_query_arg('evaluate', $candidate->candidate_id)); ?>'">
                    
                    <!-- Position Badge -->
                    <div class="mt-ranking-position">
                        <span class="mt-position-number"><?php echo $position; ?></span>
                    </div>
                    
                    <div class="mt-ranking-content">
                        <!-- Candidate Details -->
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
                        
                        <!-- Score Display -->
                        <div class="mt-ranking-scores">
                            <!-- Total Score -->
                            <div class="mt-total-score">
                                <span class="mt-total-score-label"><?php _e('Total Score', 'mobility-trailblazers'); ?></span>
                                <span class="mt-score-value"><?php echo number_format($candidate->total_score, 1); ?></span>
                            </div>
                            
                            <!-- Score Breakdown -->
                            <div class="mt-score-breakdown">
                                <h4 class="mt-score-breakdown-title"><?php _e('Score Breakdown', 'mobility-trailblazers'); ?></h4>
                                <div class="mt-criteria-scores">
                                    <div class="mt-criteria-score">
                                        <div class="mt-score-ring">
                                            <svg width="40" height="40">
                                                <circle class="mt-score-ring-bg" cx="20" cy="20" r="16"></circle>
                                                <circle class="mt-score-ring-progress" 
                                                        cx="20" cy="20" r="16"
                                                        style="stroke-dasharray: 100; stroke-dashoffset: <?php echo 100 - ($candidate->courage_score * 10); ?>">
                                                </circle>
                                            </svg>
                                            <span class="mt-criteria-score-value" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                                <?php echo number_format($candidate->courage_score, 1); ?>
                                            </span>
                                        </div>
                                        <span class="mt-criteria-score-label"><?php echo $criteria_labels['courage']; ?></span>
                                    </div>
                                    
                                    <div class="mt-criteria-score">
                                        <div class="mt-score-ring">
                                            <svg width="40" height="40">
                                                <circle class="mt-score-ring-bg" cx="20" cy="20" r="16"></circle>
                                                <circle class="mt-score-ring-progress" 
                                                        cx="20" cy="20" r="16"
                                                        style="stroke-dasharray: 100; stroke-dashoffset: <?php echo 100 - ($candidate->innovation_score * 10); ?>">
                                                </circle>
                                            </svg>
                                            <span class="mt-criteria-score-value" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                                <?php echo number_format($candidate->innovation_score, 1); ?>
                                            </span>
                                        </div>
                                        <span class="mt-criteria-score-label"><?php echo $criteria_labels['innovation']; ?></span>
                                    </div>
                                    
                                    <div class="mt-criteria-score">
                                        <div class="mt-score-ring">
                                            <svg width="40" height="40">
                                                <circle class="mt-score-ring-bg" cx="20" cy="20" r="16"></circle>
                                                <circle class="mt-score-ring-progress" 
                                                        cx="20" cy="20" r="16"
                                                        style="stroke-dasharray: 100; stroke-dashoffset: <?php echo 100 - ($candidate->implementation_score * 10); ?>">
                                                </circle>
                                            </svg>
                                            <span class="mt-criteria-score-value" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                                <?php echo number_format($candidate->implementation_score, 1); ?>
                                            </span>
                                        </div>
                                        <span class="mt-criteria-score-label"><?php echo $criteria_labels['implementation']; ?></span>
                                    </div>
                                    
                                    <div class="mt-criteria-score">
                                        <div class="mt-score-ring">
                                            <svg width="40" height="40">
                                                <circle class="mt-score-ring-bg" cx="20" cy="20" r="16"></circle>
                                                <circle class="mt-score-ring-progress" 
                                                        cx="20" cy="20" r="16"
                                                        style="stroke-dasharray: 100; stroke-dashoffset: <?php echo 100 - ($candidate->relevance_score * 10); ?>">
                                                </circle>
                                            </svg>
                                            <span class="mt-criteria-score-value" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                                <?php echo number_format($candidate->relevance_score, 1); ?>
                                            </span>
                                        </div>
                                        <span class="mt-criteria-score-label"><?php echo $criteria_labels['relevance']; ?></span>
                                    </div>
                                    
                                    <div class="mt-criteria-score">
                                        <div class="mt-score-ring">
                                            <svg width="40" height="40">
                                                <circle class="mt-score-ring-bg" cx="20" cy="20" r="16"></circle>
                                                <circle class="mt-score-ring-progress" 
                                                        cx="20" cy="20" r="16"
                                                        style="stroke-dasharray: 100; stroke-dashoffset: <?php echo 100 - ($candidate->visibility_score * 10); ?>">
                                                </circle>
                                            </svg>
                                            <span class="mt-criteria-score-value" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                                                <?php echo number_format($candidate->visibility_score, 1); ?>
                                            </span>
                                        </div>
                                        <span class="mt-criteria-score-label"><?php echo $criteria_labels['visibility']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Button -->
                        <div class="mt-ranking-actions">
                            <button type="button" class="mt-btn mt-btn-primary">
                                <?php _e('Review Evaluation', 'mobility-trailblazers'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            <?php 
                $position++;
            endforeach; 
            ?>
        </div>
    <?php else : ?>
        <div class="mt-no-rankings">
            <div class="mt-no-rankings-icon">📊</div>
            <h3><?php _e('No Rankings Yet', 'mobility-trailblazers'); ?></h3>
            <p><?php _e('Complete your first candidate evaluation to see rankings appear here.', 'mobility-trailblazers'); ?></p>
        </div>
    <?php endif; ?>
</div>
