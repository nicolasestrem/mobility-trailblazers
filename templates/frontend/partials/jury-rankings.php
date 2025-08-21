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
    echo '<p class="mt-no-rankings">' . esc_html__('No rankings available yet.', 'mobility-trailblazers') . '</p>';
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
        <h2><?php _e('Your Rankings', 'mobility-trailblazers'); ?></h2>
        <p class="mt-rankings-subtitle"><?php _e('Real-time ranking based on evaluation scores', 'mobility-trailblazers'); ?></p>
    </div>
    <div class="mt-evaluation-table-wrap">
        <table class="mt-evaluation-table">
            <thead>
                <tr>
                    <th><?php _e('Rank', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Candidate', 'mobility-trailblazers'); ?></th>
                    <?php foreach ($criteria as $criterion): ?>
                        <th title="<?php echo esc_attr($criterion['description']); ?>"><?php echo esc_html(mb_substr($criterion['label'], 0, 3)); ?></th>
                    <?php endforeach; ?>
                    <th><?php _e('Total', 'mobility-trailblazers'); ?></th>
                    <th><?php _e('Actions', 'mobility-trailblazers'); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php 
            $position = 1;
            foreach ($rankings as $ranking) : 
                $candidate_id = isset($ranking->candidate_id) ? $ranking->candidate_id : $ranking->ID;
                $candidate_name = $ranking->candidate_name ?? $ranking->post_title ?? '';
                $organization = $ranking->organization ?? '';
                $position_title = $ranking->position ?? '';
                $total_score = floatval($ranking->total_score ?? 0);
                $evaluation = null;
                if ($jury_member) {
                    $evaluations = $evaluation_repo->find_all([
                        'jury_member_id' => $jury_member->ID,
                        'candidate_id' => $candidate_id,
                        'limit' => 1
                    ]);
                    $evaluation = !empty($evaluations) ? $evaluations[0] : null;
                }
                $position_class = '';
                if ($position === 1) $position_class = 'position-gold';
                elseif ($position === 2) $position_class = 'position-silver';
                elseif ($position === 3) $position_class = 'position-bronze';
                $rank_class = 'rank-' . $position;
            ?>
                <tr class="mt-eval-row <?php echo esc_attr($position_class . ' ' . $rank_class); ?>" data-candidate-id="<?php echo esc_attr($candidate_id); ?>">
                    <td class="mt-eval-rank">
                        <?php 
                        // Use the new ranking display utility
                        if (class_exists('\MobilityTrailblazers\Utilities\MT_Ranking_Display')) {
                            echo \MobilityTrailblazers\Utilities\MT_Ranking_Display::get_position_badge($position, [
                                'show_medal' => ($position <= 3),
                                'show_number' => true,
                                'size' => 'small',
                                'context' => 'table'
                            ]);
                        } else {
                            // Fallback to simple display
                            ?>
                            <span class="mt-position-badge <?php echo esc_attr($position_class); ?>">
                                <span class="position-number"><?php echo $position; ?></span>
                            </span>
                            <?php
                        }
                        ?>
                    </td>
                    <td class="mt-eval-candidate">
                        <span class="mt-candidate-name"><?php echo esc_html($candidate_name); ?></span>
                        <?php if ($organization || $position_title) : ?>
                            <div class="mt-candidate-meta">
                                <?php if ($position_title) echo esc_html($position_title); ?>
                                <?php if ($organization && $position_title) echo ' @ '; ?>
                                <?php if ($organization) echo esc_html($organization); ?>
                            </div>
                        <?php endif; ?>
                        <?php 
                        // Add biography/excerpt
                        $excerpt = get_the_excerpt($candidate_id);
                        if ($excerpt) : ?>
                            <div class="mt-candidate-bio">
                                <?php echo esc_html(wp_trim_words($excerpt, 15, '...')); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <?php 
                    $row_total = 0;
                    $score_count = 0;
                    foreach ($criteria as $key => $criterion) : 
                        $score_field = $criterion['key'];
                        // Ensure we always show 0 when the score is 0, not blank
                        if ($evaluation && property_exists($evaluation, $score_field)) {
                            $current_score = floatval($evaluation->$score_field);
                        } else {
                            $current_score = 0;
                        }
                        $row_total += $current_score;
                        if ($current_score > 0) $score_count++;
                        $score_class = $current_score >= 8 ? 'score-high' : ($current_score <= 3 ? 'score-low' : '');
                    ?>
                        <td>
                            <input type="number" min="0" max="10" step="0.5" 
                                class="mt-eval-score-input <?php echo $score_class; ?>" 
                                name="<?php echo esc_attr($criterion['key']); ?>"
                                value="<?php echo esc_attr(number_format($current_score, 1, '.', '')); ?>"
                                data-criterion="<?php echo esc_attr($criterion['key']); ?>"
                                data-candidate-id="<?php echo esc_attr($candidate_id); ?>">
                        </td>
                    <?php endforeach; 
                    // Calculate average for display (consistent with database storage)
                    $avg_score = count($criteria) > 0 ? ($row_total / count($criteria)) : 0;
                    ?>
                    <td class="mt-eval-total-score">
                        <span class="mt-eval-total-value"><?php echo number_format($avg_score, 1); ?></span>
                    </td>
                    <td class="mt-eval-actions">
                        <button class="mt-btn-save-eval" data-candidate-id="<?php echo esc_attr($candidate_id); ?>">
                            <span class="dashicons dashicons-saved"></span> <?php _e('Save', 'mobility-trailblazers'); ?>
                        </button>
                        <a href="<?php echo esc_url(add_query_arg('evaluate', $candidate_id)); ?>" class="mt-btn-full-evaluation">
                            <span class="dashicons dashicons-visibility"></span> <?php _e('Full View', 'mobility-trailblazers'); ?>
                        </a>
                    </td>
                </tr>
            <?php 
                $position++;
            endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
