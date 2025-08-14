<?php
/**
 * Generate Sample Profiles Page Template
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'mobility-trailblazers'));
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-warning">
        <p><?php esc_html_e('This tool generates sample candidate profiles for testing purposes. Use with caution in production environments.', 'mobility-trailblazers'); ?></p>
    </div>
    
    <div class="mt-generate-container">
        <div class="card">
            <h2><?php esc_html_e('Current Statistics', 'mobility-trailblazers'); ?></h2>
            
            <?php
            // Get current counts
            $candidates = wp_count_posts('mt_candidate');
            $jury_members = get_users(['meta_key' => 'mt_jury_member', 'meta_value' => 'yes']);
            
            global $wpdb;
            $evaluations = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_evaluations");
            $assignments = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_jury_assignments");
            ?>
            
            <table class="wp-list-table widefat">
                <tbody>
                    <tr>
                        <th><?php esc_html_e('Total Candidates', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($candidates->publish + $candidates->draft); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Jury Members', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html(count($jury_members)); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Evaluations', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($evaluations ?: 0); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Assignments', 'mobility-trailblazers'); ?></th>
                        <td><?php echo esc_html($assignments ?: 0); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <div class="card">
            <h2><?php esc_html_e('Generate Sample Data', 'mobility-trailblazers'); ?></h2>
            
            <form method="post" action="" id="mt-generate-form">
                <?php wp_nonce_field('mt_generate_samples', 'mt_generate_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="num_candidates"><?php esc_html_e('Number of Candidates', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="num_candidates" name="num_candidates" value="10" min="1" max="100" class="small-text">
                            <p class="description"><?php esc_html_e('Number of sample candidates to generate (max 100)', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="num_jury"><?php esc_html_e('Number of Jury Members', 'mobility-trailblazers'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="num_jury" name="num_jury" value="5" min="0" max="20" class="small-text">
                            <p class="description"><?php esc_html_e('Number of sample jury members to generate (max 20)', 'mobility-trailblazers'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Additional Options', 'mobility-trailblazers'); ?>
                        </th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="checkbox" name="create_assignments" value="1">
                                    <?php esc_html_e('Create automatic assignments', 'mobility-trailblazers'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="create_evaluations" value="1">
                                    <?php esc_html_e('Create sample evaluations', 'mobility-trailblazers'); ?>
                                </label>
                                <br>
                                <label>
                                    <input type="checkbox" name="use_realistic_data" value="1" checked>
                                    <?php esc_html_e('Use realistic mobility company names', 'mobility-trailblazers'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="generate_samples" class="button button-primary">
                        <?php esc_html_e('Generate Sample Data', 'mobility-trailblazers'); ?>
                    </button>
                    <button type="button" id="clear-samples" class="button button-secondary">
                        <?php esc_html_e('Clear All Sample Data', 'mobility-trailblazers'); ?>
                    </button>
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2><?php esc_html_e('Generation Log', 'mobility-trailblazers'); ?></h2>
            <div id="generation-log" style="max-height: 400px; overflow-y: auto; padding: 10px; background: #f0f0f0;">
                <p><?php esc_html_e('Generation log will appear here...', 'mobility-trailblazers'); ?></p>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#mt-generate-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        var $log = $('#generation-log');
        
        $button.prop('disabled', true).text('<?php esc_html_e('Generating...', 'mobility-trailblazers'); ?>');
        $log.html('<p><?php esc_html_e('Starting generation...', 'mobility-trailblazers'); ?></p>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_generate_samples',
                nonce: $('#mt_generate_nonce').val(),
                num_candidates: $('#num_candidates').val(),
                num_jury: $('#num_jury').val(),
                create_assignments: $('input[name="create_assignments"]').is(':checked') ? 1 : 0,
                create_evaluations: $('input[name="create_evaluations"]').is(':checked') ? 1 : 0,
                use_realistic_data: $('input[name="use_realistic_data"]').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    $log.html('<p class="success">' + response.data.message + '</p>');
                    if (response.data.details) {
                        var details = '<ul>';
                        if (response.data.details.candidates) {
                            details += '<li><?php esc_html_e('Candidates created:', 'mobility-trailblazers'); ?> ' + response.data.details.candidates + '</li>';
                        }
                        if (response.data.details.jury_members) {
                            details += '<li><?php esc_html_e('Jury members created:', 'mobility-trailblazers'); ?> ' + response.data.details.jury_members + '</li>';
                        }
                        if (response.data.details.assignments) {
                            details += '<li><?php esc_html_e('Assignments created:', 'mobility-trailblazers'); ?> ' + response.data.details.assignments + '</li>';
                        }
                        if (response.data.details.evaluations) {
                            details += '<li><?php esc_html_e('Evaluations created:', 'mobility-trailblazers'); ?> ' + response.data.details.evaluations + '</li>';
                        }
                        details += '</ul>';
                        $log.append(details);
                    }
                    // Reload page after successful generation
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $log.html('<p class="error">' + (response.data || '<?php esc_html_e('Generation failed', 'mobility-trailblazers'); ?>') + '</p>');
                }
            },
            error: function() {
                $log.html('<p class="error"><?php esc_html_e('Generation failed. Please try again.', 'mobility-trailblazers'); ?></p>');
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php esc_html_e('Generate Sample Data', 'mobility-trailblazers'); ?>');
            }
        });
    });
    
    $('#clear-samples').on('click', function() {
        if (!confirm('<?php esc_html_e('Are you sure you want to clear all sample data? This action cannot be undone.', 'mobility-trailblazers'); ?>')) {
            return;
        }
        
        var $button = $(this);
        var $log = $('#generation-log');
        
        $button.prop('disabled', true).text('<?php esc_html_e('Clearing...', 'mobility-trailblazers'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mt_clear_samples',
                nonce: '<?php echo wp_create_nonce('mt_clear_samples'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $log.html('<p class="success">' + response.data.message + '</p>');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $log.html('<p class="error">' + (response.data || '<?php esc_html_e('Clear failed', 'mobility-trailblazers'); ?>') + '</p>');
                }
            },
            complete: function() {
                $button.prop('disabled', false).text('<?php esc_html_e('Clear All Sample Data', 'mobility-trailblazers'); ?>');
            }
        });
    });
});
</script>