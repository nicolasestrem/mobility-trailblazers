<?php
/**
 * Admin Assignments Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get assignment repository
$assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
$assignment_service = new \MobilityTrailblazers\Services\MT_Assignment_Service();

// Handle form submissions
if (isset($_POST['action']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_assignments')) {
    if ($_POST['action'] === 'auto_assign') {
        $method = sanitize_text_field($_POST['method']);
        $candidates_per_jury = intval($_POST['candidates_per_jury']);
        
        $result = $assignment_service->auto_assign($method, $candidates_per_jury);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>' . __('Auto-assignment completed successfully!', 'mobility-trailblazers') . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('Auto-assignment failed. Please check the logs.', 'mobility-trailblazers') . '</p></div>';
        }
    }
}

// Get all assignments
$assignments = $assignment_repo->find_all();

// Get jury members and candidates
$jury_members = get_posts([
    'post_type' => 'mt_jury_member',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);

$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
]);

// Group assignments by jury member
$assignments_by_jury = [];
foreach ($assignments as $assignment) {
    if (!isset($assignments_by_jury[$assignment->jury_member_id])) {
        $assignments_by_jury[$assignment->jury_member_id] = [];
    }
    $assignments_by_jury[$assignment->jury_member_id][] = $assignment;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Jury Assignments', 'mobility-trailblazers'); ?></h1>
    
    <hr class="wp-header-end">
    
    <!-- Auto-Assignment Form -->
    <div class="card">
        <h2><?php _e('Auto-Assignment', 'mobility-trailblazers'); ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('mt_assignments'); ?>
            <input type="hidden" name="action" value="auto_assign">
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="method"><?php _e('Assignment Method', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <select name="method" id="method">
                            <option value="balanced"><?php _e('Balanced (Equal distribution)', 'mobility-trailblazers'); ?></option>
                            <option value="random"><?php _e('Random', 'mobility-trailblazers'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="candidates_per_jury"><?php _e('Candidates per Jury Member', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <input type="number" name="candidates_per_jury" id="candidates_per_jury" value="5" min="1" max="50">
                        <p class="description"><?php _e('Number of candidates to assign to each jury member.', 'mobility-trailblazers'); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-primary"><?php _e('Run Auto-Assignment', 'mobility-trailblazers'); ?></button>
                <span class="description"><?php _e('Warning: This will replace all existing assignments!', 'mobility-trailblazers'); ?></span>
            </p>
        </form>
    </div>
    
    <!-- Current Assignments -->
    <div class="card">
        <h2><?php _e('Current Assignments', 'mobility-trailblazers'); ?></h2>
        
        <?php if (!empty($jury_members)) : ?>
            <?php foreach ($jury_members as $jury) : 
                $jury_assignments = isset($assignments_by_jury[$jury->ID]) ? $assignments_by_jury[$jury->ID] : [];
                $user = get_user_by('ID', get_post_meta($jury->ID, '_mt_user_id', true));
            ?>
                <div class="jury-assignments">
                    <h3>
                        <?php echo esc_html($jury->post_title); ?>
                        <?php if ($user) : ?>
                            <span class="description">(<?php echo esc_html($user->user_email); ?>)</span>
                        <?php endif; ?>
                        <span class="count"><?php echo count($jury_assignments); ?> <?php _e('candidates', 'mobility-trailblazers'); ?></span>
                    </h3>
                    
                    <?php if (!empty($jury_assignments)) : ?>
                        <ul class="candidate-list">
                            <?php foreach ($jury_assignments as $assignment) : 
                                $candidate = get_post($assignment->candidate_id);
                                if ($candidate) :
                            ?>
                                <li>
                                    <a href="<?php echo get_edit_post_link($candidate->ID); ?>">
                                        <?php echo esc_html($candidate->post_title); ?>
                                    </a>
                                    <button class="button-link remove-assignment" 
                                            data-assignment-id="<?php echo esc_attr($assignment->id); ?>"
                                            style="color: #a00;">
                                        <?php _e('Remove', 'mobility-trailblazers'); ?>
                                    </button>
                                </li>
                            <?php endif; endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="description"><?php _e('No candidates assigned.', 'mobility-trailblazers'); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else : ?>
            <p><?php _e('No jury members found. Please create jury members first.', 'mobility-trailblazers'); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Manual Assignment -->
    <div class="card">
        <h2><?php _e('Manual Assignment', 'mobility-trailblazers'); ?></h2>
        <form id="manual-assignment-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="jury_member_id"><?php _e('Jury Member', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <select name="jury_member_id" id="jury_member_id" required>
                            <option value=""><?php _e('Select Jury Member', 'mobility-trailblazers'); ?></option>
                            <?php foreach ($jury_members as $jury) : ?>
                                <option value="<?php echo esc_attr($jury->ID); ?>">
                                    <?php echo esc_html($jury->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="candidate_id"><?php _e('Candidate', 'mobility-trailblazers'); ?></label>
                    </th>
                    <td>
                        <select name="candidate_id" id="candidate_id" required>
                            <option value=""><?php _e('Select Candidate', 'mobility-trailblazers'); ?></option>
                            <?php foreach ($candidates as $candidate) : ?>
                                <option value="<?php echo esc_attr($candidate->ID); ?>">
                                    <?php echo esc_html($candidate->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" class="button button-secondary"><?php _e('Add Assignment', 'mobility-trailblazers'); ?></button>
            </p>
        </form>
    </div>
</div>

<style>
.jury-assignments {
    margin-bottom: 30px;
    padding: 15px;
    background: #f1f1f1;
    border-radius: 5px;
}
.jury-assignments h3 {
    margin-top: 0;
}
.jury-assignments .count {
    background: #2271b1;
    color: white;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    margin-left: 10px;
}
.candidate-list {
    list-style: disc;
    margin-left: 20px;
}
.candidate-list li {
    margin: 5px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Manual assignment
    $('#manual-assignment-form').on('submit', function(e) {
        e.preventDefault();
        
        var data = {
            action: 'mt_create_assignment',
            nonce: '<?php echo wp_create_nonce('mt_assignment_nonce'); ?>',
            jury_member_id: $('#jury_member_id').val(),
            candidate_id: $('#candidate_id').val()
        };
        
        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                alert('Assignment created successfully!');
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
    
    // Remove assignment
    $('.remove-assignment').on('click', function() {
        if (!confirm('Are you sure you want to remove this assignment?')) {
            return;
        }
        
        var assignmentId = $(this).data('assignment-id');
        var $button = $(this);
        
        $.post(ajaxurl, {
            action: 'mt_delete_assignment',
            nonce: '<?php echo wp_create_nonce('mt_assignment_nonce'); ?>',
            assignment_id: assignmentId
        }, function(response) {
            if (response.success) {
                $button.closest('li').fadeOut();
            } else {
                alert('Error: ' + response.data.message);
            }
        });
    });
});
</script> 