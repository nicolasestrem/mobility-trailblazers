<?php
/**
 * Test Profile System
 *
 * @package MobilityTrailblazers
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle test action
$test_result = null;
if (isset($_POST['run_test']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_test_profiles')) {
    $test_type = sanitize_text_field($_POST['test_type']);
    $test_result = [];
    
    switch ($test_type) {
        case 'meta_fields':
            // Test meta fields
            $sample_candidate = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => 1,
                'orderby' => 'rand'
            ]);
            
            if (!empty($sample_candidate)) {
                $candidate = $sample_candidate[0];
                $meta_keys = [
                    '_mt_display_name',
                    '_mt_organization',
                    '_mt_position',
                    '_mt_linkedin',
                    '_mt_website',
                    '_mt_email',
                    '_mt_top50',
                    '_mt_nominator',
                    '_mt_notes',
                    '_mt_courage',
                    '_mt_innovation',
                    '_mt_implementation',
                    '_mt_relevance',
                    '_mt_visibility',
                    '_mt_personality'
                ];
                
                $test_result['candidate'] = $candidate->post_title;
                $test_result['meta_fields'] = [];
                
                foreach ($meta_keys as $key) {
                    $value = get_post_meta($candidate->ID, $key, true);
                    $test_result['meta_fields'][$key] = [
                        'exists' => !empty($value),
                        'value' => $value ?: 'Not set'
                    ];
                }
            } else {
                $test_result['error'] = __('No candidates found to test.', 'mobility-trailblazers');
            }
            break;
            
        case 'taxonomy':
            // Test taxonomy terms
            $categories = get_terms([
                'taxonomy' => 'mt_award_category',
                'hide_empty' => false
            ]);
            
            $test_result['categories'] = [];
            foreach ($categories as $category) {
                $test_result['categories'][] = [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'count' => $category->count
                ];
            }
            break;
            
        case 'create_test':
            // Create a test candidate
            $post_data = [
                'post_title' => 'Test Candidate ' . uniqid(),
                'post_type' => 'mt_candidate',
                'post_status' => 'draft',
                'post_content' => 'This is a test candidate profile created for testing purposes.'
            ];
            
            $post_id = wp_insert_post($post_data);
            
            if ($post_id && !is_wp_error($post_id)) {
                // Add meta data
                update_post_meta($post_id, '_mt_organization', 'Test Organization');
                update_post_meta($post_id, '_mt_position', 'Test Position');
                update_post_meta($post_id, '_mt_linkedin', 'https://linkedin.com/in/test');
                update_post_meta($post_id, '_mt_website', 'https://example.com');
                update_post_meta($post_id, '_mt_top50', 'no');
                
                // Add to category
                wp_set_post_terms($post_id, ['Start-ups & Scale-ups'], 'mt_award_category');
                
                $test_result['success'] = true;
                $test_result['post_id'] = $post_id;
                $test_result['message'] = sprintf(
                    __('Test candidate created successfully. <a href="%s">Edit candidate</a>', 'mobility-trailblazers'),
                    get_edit_post_link($post_id)
                );
            } else {
                $test_result['error'] = __('Failed to create test candidate.', 'mobility-trailblazers');
            }
            break;
    }
}

// Get system info
$candidates_count = wp_count_posts('mt_candidate');
$jury_count = wp_count_posts('mt_jury_member');
$categories = get_terms(['taxonomy' => 'mt_award_category', 'hide_empty' => false]);
?>

<div class="wrap">
    <h1><?php _e('Test Profile System', 'mobility-trailblazers'); ?></h1>
    
    <div class="card">
        <h2><?php _e('System Overview', 'mobility-trailblazers'); ?></h2>
        <table class="wp-list-table widefat">
            <tbody>
                <tr>
                    <td><?php _e('Total Candidates', 'mobility-trailblazers'); ?></td>
                    <td><strong><?php echo ($candidates_count->publish + $candidates_count->draft); ?></strong></td>
                </tr>
                <tr>
                    <td><?php _e('Jury Members', 'mobility-trailblazers'); ?></td>
                    <td><strong><?php echo ($jury_count->publish + $jury_count->draft); ?></strong></td>
                </tr>
                <tr>
                    <td><?php _e('Categories', 'mobility-trailblazers'); ?></td>
                    <td><strong><?php echo count($categories); ?></strong></td>
                </tr>
                <tr>
                    <td><?php _e('PHP Version', 'mobility-trailblazers'); ?></td>
                    <td><strong><?php echo PHP_VERSION; ?></strong></td>
                </tr>
                <tr>
                    <td><?php _e('WordPress Version', 'mobility-trailblazers'); ?></td>
                    <td><strong><?php echo get_bloginfo('version'); ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="card">
        <h2><?php _e('Run Tests', 'mobility-trailblazers'); ?></h2>
        <form method="post">
            <?php wp_nonce_field('mt_test_profiles'); ?>
            <p>
                <label for="test_type"><?php _e('Select Test:', 'mobility-trailblazers'); ?></label>
                <select name="test_type" id="test_type">
                    <option value="meta_fields"><?php _e('Test Meta Fields', 'mobility-trailblazers'); ?></option>
                    <option value="taxonomy"><?php _e('Test Taxonomy', 'mobility-trailblazers'); ?></option>
                    <option value="create_test"><?php _e('Create Test Candidate', 'mobility-trailblazers'); ?></option>
                </select>
            </p>
            <p>
                <button type="submit" name="run_test" class="button button-primary">
                    <?php _e('Run Test', 'mobility-trailblazers'); ?>
                </button>
            </p>
        </form>
    </div>
    
    <?php if ($test_result): ?>
        <div class="card">
            <h2><?php _e('Test Results', 'mobility-trailblazers'); ?></h2>
            
            <?php if (isset($test_result['error'])): ?>
                <div class="notice notice-error inline">
                    <p><?php echo esc_html($test_result['error']); ?></p>
                </div>
            <?php elseif (isset($test_result['success'])): ?>
                <div class="notice notice-success inline">
                    <p><?php echo wp_kses_post($test_result['message']); ?></p>
                </div>
            <?php elseif (isset($test_result['meta_fields'])): ?>
                <h3><?php printf(__('Testing Candidate: %s', 'mobility-trailblazers'), esc_html($test_result['candidate'])); ?></h3>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Meta Key', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Status', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Value', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($test_result['meta_fields'] as $key => $data): ?>
                            <tr>
                                <td><code><?php echo esc_html($key); ?></code></td>
                                <td>
                                    <?php if ($data['exists']): ?>
                                        <span style="color: green;">✓ <?php _e('Set', 'mobility-trailblazers'); ?></span>
                                    <?php else: ?>
                                        <span style="color: orange;">○ <?php _e('Not set', 'mobility-trailblazers'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(substr($data['value'], 0, 100)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif (isset($test_result['categories'])): ?>
                <h3><?php _e('Award Categories', 'mobility-trailblazers'); ?></h3>
                <table class="wp-list-table widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Category', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Slug', 'mobility-trailblazers'); ?></th>
                            <th><?php _e('Candidates', 'mobility-trailblazers'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($test_result['categories'] as $cat): ?>
                            <tr>
                                <td><?php echo esc_html($cat['name']); ?></td>
                                <td><code><?php echo esc_html($cat['slug']); ?></code></td>
                                <td><?php echo esc_html($cat['count']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2><?php _e('Quick Actions', 'mobility-trailblazers'); ?></h2>
        <p>
            <a href="<?php echo admin_url('edit.php?post_type=mt_candidate'); ?>" class="button">
                <?php _e('View All Candidates', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('post-new.php?post_type=mt_candidate'); ?>" class="button">
                <?php _e('Add New Candidate', 'mobility-trailblazers'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=mt-import-profiles'); ?>" class="button">
                <?php _e('Import Profiles', 'mobility-trailblazers'); ?>
            </a>
        </p>
    </div>
</div>
