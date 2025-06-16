<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user permissions
if (!current_user_can('mt_manage_awards')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Get current voting round
$current_round = get_option('mt_current_vote_round', 1);

// Get all candidates
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish'
]);

// Get all votes for current round
global $wpdb;
$votes = $wpdb->get_results($wpdb->prepare(
    "SELECT v.*, u.display_name as voter_name 
     FROM {$wpdb->prefix}mt_votes v 
     LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID 
     WHERE v.round = %d 
     ORDER BY v.candidate_id, v.user_id",
    $current_round
));

// Calculate results
$results = [];
foreach ($candidates as $candidate) {
    $candidate_votes = array_filter($votes, function($vote) use ($candidate) {
        return $vote->candidate_id == $candidate->ID;
    });
    
    $total_score = 0;
    $vote_count = count($candidate_votes);
    
    foreach ($candidate_votes as $vote) {
        $total_score += $vote->score;
    }
    
    $average_score = $vote_count > 0 ? $total_score / $vote_count : 0;
    
    $results[$candidate->ID] = [
        'title' => $candidate->post_title,
        'total_score' => $total_score,
        'vote_count' => $vote_count,
        'average_score' => $average_score,
        'votes' => $candidate_votes
    ];
}

// Sort results by average score
uasort($results, function($a, $b) {
    return $b['average_score'] <=> $a['average_score'];
});
?>

<div class="wrap">
    <h1><?php _e('Voting Results', 'mobility-trailblazers'); ?></h1>
    
    <div class="mt-voting-results">
        <div class="mt-results-header">
            <h2><?php printf(__('Round %d Results', 'mobility-trailblazers'), $current_round); ?></h2>
            <div class="mt-results-summary">
                <p><?php printf(
                    __('Total Candidates: %d | Total Votes: %d', 'mobility-trailblazers'),
                    count($candidates),
                    count($votes)
                ); ?></p>
            </div>
        </div>
        
        <div class="mt-results-grid">
            <?php foreach ($results as $candidate_id => $result): ?>
                <div class="mt-result-card">
                    <h3><?php echo esc_html($result['title']); ?></h3>
                    <div class="mt-result-stats">
                        <div class="mt-stat">
                            <span class="mt-stat-label"><?php _e('Average Score:', 'mobility-trailblazers'); ?></span>
                            <span class="mt-stat-value"><?php echo number_format($result['average_score'], 2); ?></span>
                        </div>
                        <div class="mt-stat">
                            <span class="mt-stat-label"><?php _e('Total Votes:', 'mobility-trailblazers'); ?></span>
                            <span class="mt-stat-value"><?php echo $result['vote_count']; ?></span>
                        </div>
                        <div class="mt-stat">
                            <span class="mt-stat-label"><?php _e('Total Score:', 'mobility-trailblazers'); ?></span>
                            <span class="mt-stat-value"><?php echo $result['total_score']; ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-vote-details">
                        <h4><?php _e('Vote Details:', 'mobility-trailblazers'); ?></h4>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Voter', 'mobility-trailblazers'); ?></th>
                                    <th><?php _e('Score', 'mobility-trailblazers'); ?></th>
                                    <th><?php _e('Date', 'mobility-trailblazers'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($result['votes'] as $vote): ?>
                                    <tr>
                                        <td><?php echo esc_html($vote->voter_name); ?></td>
                                        <td><?php echo $vote->score; ?></td>
                                        <td><?php echo date_i18n(get_option('date_format'), strtotime($vote->created_at)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<style>
.mt-voting-results {
    max-width: 1200px;
    margin: 20px 0;
}

.mt-results-header {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.mt-results-header h2 {
    margin: 0 0 10px 0;
}

.mt-results-summary {
    color: #666;
}

.mt-results-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.mt-result-card {
    background: #fff;
    padding: 20px;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.mt-result-card h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.mt-result-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}

.mt-stat {
    text-align: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 4px;
}

.mt-stat-label {
    display: block;
    font-size: 0.9em;
    color: #666;
    margin-bottom: 5px;
}

.mt-stat-value {
    display: block;
    font-size: 1.2em;
    font-weight: 600;
    color: #2271b1;
}

.mt-vote-details {
    margin-top: 20px;
}

.mt-vote-details h4 {
    margin: 0 0 10px 0;
}

.mt-vote-details table {
    margin-top: 10px;
}

@media screen and (max-width: 782px) {
    .mt-results-grid {
        grid-template-columns: 1fr;
    }
    
    .mt-result-stats {
        grid-template-columns: 1fr;
    }
}
</style> 