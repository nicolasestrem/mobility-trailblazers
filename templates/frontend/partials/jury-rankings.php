<?php
/**
 * Jury Rankings Partial Template
 * Displays top-ranked candidates in a 2x5 grid with inline evaluation controls
 *
 * @package MobilityTrailblazers
 * @since 2.0.9
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure we have rankings data
if (empty($rankings)) {
    echo '<p class="mt-no-rankings">' . __('No rankings available yet.', 'mobility-trailblazers') . '</p>';
    return;
}

// Get evaluation criteria and repository
$evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
$evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
$criteria = $evaluation_service->get_criteria();

// Get current jury member
$current_user_id = get_current_user_id();
$jury_member = null;

// Find jury member by user ID
$args = [
    'post_type' => 'mt_jury_member',
    'meta_key' => '_mt_user_id',
    'meta_value' => $current_user_id,
    'posts_per_page' => 1,
    'post_status' => 'publish'
];
$jury_members = get_posts($args);
if (!empty($jury_members)) {
    $jury_member = $jury_members[0];
}
?>

<div class="mt-rankings-section">
    <div class="mt-rankings-header">
        <h2><?php _e('Top Ranked Candidates', 'mobility-trailblazers'); ?></h2>
        <p class="mt-rankings-subtitle"><?php _e('Real-time ranking based on evaluation scores', 'mobility-trailblazers'); ?></p>
    </div>
    
    <div class="mt-rankings-grid mt-rankings-5x2">
        <?php 
        $position = 1;
        foreach ($rankings as $ranking) : 
            $candidate_id = isset($ranking->candidate_id) ? $ranking->candidate_id : $ranking->ID;
            $candidate_name = $ranking->candidate_name ?? $ranking->post_title ?? '';
            $organization = $ranking->organization ?? '';
            $position_title = $ranking->position ?? '';
            $total_score = floatval($ranking->total_score ?? 0);
            
            // Get the full evaluation data for this candidate
            $evaluation = null;
            if ($jury_member) {
                $evaluations = $evaluation_repo->find_all([
                    'jury_member_id' => $jury_member->ID,
                    'candidate_id' => $candidate_id,
                    'limit' => 1
                ]);
                $evaluation = !empty($evaluations) ? $evaluations[0] : null;
            }
            
            // Position classes for medal styling
            $position_class = '';
            if ($position === 1) $position_class = 'position-gold';
            elseif ($position === 2) $position_class = 'position-silver';
            elseif ($position === 3) $position_class = 'position-bronze';
        ?>
            <div class="mt-ranking-item <?php echo esc_attr($position_class); ?>" 
                 data-candidate-id="<?php echo esc_attr($candidate_id); ?>"
                 data-position="<?php echo esc_attr($position); ?>">
                
                <!-- Position Badge -->
                <div class="mt-position-badge">
                    <span class="position-number"><?php echo $position; ?></span>
                </div>
                
                <!-- Candidate Info -->
                <div class="mt-candidate-info">
                    <h3 class="mt-candidate-name"><?php echo esc_html($candidate_name); ?></h3>
                    <?php if ($organization || $position_title) : ?>
                        <p class="mt-candidate-meta">
                            <?php if ($position_title) echo esc_html($position_title); ?>
                            <?php if ($organization && $position_title) echo ' @ '; ?>
                            <?php if ($organization) echo esc_html($organization); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- Total Score Display -->
                <div class="mt-total-score-display">
                    <span class="score-label"><?php _e('Total Score', 'mobility-trailblazers'); ?></span>
                    <span class="score-value" data-score="<?php echo $total_score; ?>">
                        <?php echo number_format($total_score, 1); ?>/10
                    </span>
                </div>
                
                <!-- Inline Evaluation Controls -->
                <div class="mt-inline-evaluation-controls">
                    <form class="mt-inline-evaluation-form" data-candidate-id="<?php echo esc_attr($candidate_id); ?>">
                        <?php wp_nonce_field('mt_inline_evaluation', 'mt_inline_nonce'); ?>
                        <input type="hidden" name="candidate_id" value="<?php echo esc_attr($candidate_id); ?>">
                        
                        <div class="mt-criteria-grid-inline">
                            <?php foreach ($criteria as $key => $criterion) : 
                                // Get current score from evaluation
                                $score_field = $criterion['key'];
                                $current_score = $evaluation && isset($evaluation->$score_field) ? floatval($evaluation->$score_field) : 0;
                            ?>
                                <div class="mt-criterion-inline">
                                    <label class="mt-criterion-label" title="<?php echo esc_attr($criterion['description']); ?>">
                                        <span class="dashicons <?php echo esc_attr($criterion['icon']); ?>"></span>
                                        <span class="mt-criterion-short"><?php echo esc_html(mb_substr($criterion['label'], 0, 3)); ?></span>
                                    </label>
                                    <div class="mt-score-control">
                                        <button type="button" class="mt-score-adjust mt-score-decrease" 
                                                data-criterion="<?php echo esc_attr($criterion['key']); ?>"
                                                data-action="decrease">
                                            <span class="dashicons dashicons-minus"></span>
                                        </button>
                                        <input type="number" 
                                               class="mt-score-input" 
                                               name="<?php echo esc_attr($criterion['key']); ?>"
                                               id="score_<?php echo esc_attr($candidate_id); ?>_<?php echo esc_attr($criterion['key']); ?>"
                                               value="<?php echo esc_attr($current_score); ?>"
                                               min="0" 
                                               max="10" 
                                               step="0.5"
                                               data-criterion="<?php echo esc_attr($criterion['key']); ?>">
                                        <button type="button" class="mt-score-adjust mt-score-increase" 
                                                data-criterion="<?php echo esc_attr($criterion['key']); ?>"
                                                data-action="increase">
                                            <span class="dashicons dashicons-plus"></span>
                                        </button>
                                    </div>
                                    <div class="mt-score-ring-mini" data-score="<?php echo esc_attr($current_score); ?>">
                                        <svg viewBox="0 0 36 36" class="mt-score-svg">
                                            <path class="mt-ring-bg"
                                                  d="M18 2.0845
                                                     a 15.9155 15.9155 0 0 1 0 31.831
                                                     a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                            <path class="mt-ring-progress"
                                                  stroke-dasharray="<?php echo ($current_score * 10); ?>, 100"
                                                  d="M18 2.0845
                                                     a 15.9155 15.9155 0 0 1 0 31.831
                                                     a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                        </svg>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-inline-actions">
                            <button type="button" class="mt-btn-save-inline" data-candidate-id="<?php echo esc_attr($candidate_id); ?>">
                                <span class="dashicons dashicons-saved"></span>
                                <?php _e('Save', 'mobility-trailblazers'); ?>
                            </button>
                            <a href="<?php echo esc_url(add_query_arg('evaluate', $candidate_id)); ?>" 
                               class="mt-btn-full-evaluation">
                                <span class="dashicons dashicons-visibility"></span>
                                <?php _e('Full View', 'mobility-trailblazers'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php 
            $position++;
            if ($position > 10) break; // Limit to 10 candidates (2x5 grid)
        endforeach; 
        ?>
    </div>
</div>
