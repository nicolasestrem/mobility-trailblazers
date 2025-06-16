<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
if (!$current_user) {
    wp_die(__('You must be logged in to access this page.', 'mobility-trailblazers'));
}

// Get jury member
$jury_member = get_posts(array(
    'post_type' => 'mt_jury',
    'meta_key' => 'user_id',
    'meta_value' => $current_user->ID,
    'posts_per_page' => 1
));

if (empty($jury_member)) {
    wp_die(__('You are not registered as a jury member.', 'mobility-trailblazers'));
}

$jury_member = $jury_member[0];

// Get assigned candidates
$assigned_candidates = get_post_meta($jury_member->ID, 'assigned_candidates', true);
if (!is_array($assigned_candidates)) {
    $assigned_candidates = array();
}

// Get current round
$current_round = get_option('mt_current_vote_round', 1);

// Get evaluation criteria
$criteria = array(
    'innovation' => array(
        'label' => __('Innovation', 'mobility-trailblazers'),
        'description' => __('How innovative is the solution?', 'mobility-trailblazers')
    ),
    'impact' => array(
        'label' => __('Impact', 'mobility-trailblazers'),
        'description' => __('What is the potential impact of the solution?', 'mobility-trailblazers')
    ),
    'feasibility' => array(
        'label' => __('Feasibility', 'mobility-trailblazers'),
        'description' => __('How feasible is the implementation?', 'mobility-trailblazers')
    ),
    'sustainability' => array(
        'label' => __('Sustainability', 'mobility-trailblazers'),
        'description' => __('How sustainable is the solution?', 'mobility-trailblazers')
    ),
    'scalability' => array(
        'label' => __('Scalability', 'mobility-trailblazers'),
        'description' => __('How scalable is the solution?', 'mobility-trailblazers')
    )
);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mt_evaluation_nonce']) && wp_verify_nonce($_POST['mt_evaluation_nonce'], 'mt_evaluation')) {
    $candidate_id = intval($_POST['candidate_id']);
    $scores = array();
    $comments = sanitize_textarea_field($_POST['comments']);

    foreach ($criteria as $key => $criterion) {
        $scores[$key] = floatval($_POST['score_' . $key]);
    }

    // Save evaluation
    $evaluation_data = array(
        'candidate_id' => $candidate_id,
        'jury_member_id' => $jury_member->ID,
        'scores' => $scores,
        'comments' => $comments,
        'round' => $current_round
    );

    // Log the evaluation
    global $wpdb;
    $wpdb->insert(
        $wpdb->prefix . 'mt_vote_audit_log',
        array(
            'action' => 'evaluation_submitted',
            'details' => sprintf(
                __('Evaluation submitted for candidate ID %d in round %d', 'mobility-trailblazers'),
                $candidate_id,
                $current_round
            ),
            'user_id' => $current_user->ID,
            'timestamp' => current_time('mysql')
        )
    );

    // Save the evaluation
    update_post_meta($jury_member->ID, 'evaluation_' . $candidate_id . '_round_' . $current_round, $evaluation_data);

    // Show success message
    echo '<div class="notice notice-success"><p>' . __('Evaluation submitted successfully.', 'mobility-trailblazers') . '</p></div>';
}
?>

<div class="wrap">
    <h1><?php _e('Jury Evaluation', 'mobility-trailblazers'); ?></h1>
    
    <?php if (empty($assigned_candidates)): ?>
        <div class="notice notice-warning">
            <p><?php _e('You have not been assigned any candidates to evaluate.', 'mobility-trailblazers'); ?></p>
        </div>
    <?php else: ?>
        <div class="mt-evaluation-container">
            <?php foreach ($assigned_candidates as $candidate_id): 
                $candidate = get_post($candidate_id);
                if (!$candidate) continue;

                // Get existing evaluation if any
                $existing_evaluation = get_post_meta($jury_member->ID, 'evaluation_' . $candidate_id . '_round_' . $current_round, true);
                ?>
                <div class="mt-evaluation-card">
                    <h2><?php echo esc_html($candidate->post_title); ?></h2>
                    
                    <form method="post" class="mt-evaluation-form">
                        <?php wp_nonce_field('mt_evaluation', 'mt_evaluation_nonce'); ?>
                        <input type="hidden" name="candidate_id" value="<?php echo esc_attr($candidate_id); ?>">
                        
                        <div class="mt-evaluation-criteria">
                            <?php foreach ($criteria as $key => $criterion): ?>
                                <div class="mt-criterion">
                                    <label for="score_<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($criterion['label']); ?>
                                        <span class="description"><?php echo esc_html($criterion['description']); ?></span>
                                    </label>
                                    <input type="number" 
                                           name="score_<?php echo esc_attr($key); ?>" 
                                           id="score_<?php echo esc_attr($key); ?>" 
                                           min="0" 
                                           max="10" 
                                           step="0.5" 
                                           value="<?php echo isset($existing_evaluation['scores'][$key]) ? esc_attr($existing_evaluation['scores'][$key]) : ''; ?>" 
                                           required>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="mt-comments">
                            <label for="comments"><?php _e('Comments', 'mobility-trailblazers'); ?></label>
                            <textarea name="comments" id="comments" rows="5"><?php echo isset($existing_evaluation['comments']) ? esc_textarea($existing_evaluation['comments']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mt-submit">
                            <button type="submit" class="button button-primary">
                                <?php echo isset($existing_evaluation) ? __('Update Evaluation', 'mobility-trailblazers') : __('Submit Evaluation', 'mobility-trailblazers'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.mt-evaluation-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.mt-evaluation-card {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.mt-evaluation-criteria {
    margin: 20px 0;
}

.mt-criterion {
    margin-bottom: 15px;
}

.mt-criterion label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.mt-criterion .description {
    display: block;
    font-weight: normal;
    color: #666;
    font-size: 0.9em;
    margin-top: 2px;
}

.mt-criterion input[type="number"] {
    width: 100px;
}

.mt-comments textarea {
    width: 100%;
    margin-top: 5px;
}

.mt-submit {
    margin-top: 20px;
    text-align: right;
}
</style> 