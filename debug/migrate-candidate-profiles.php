<?php
/**
 * Migrate Candidate Profiles Tool
 *
 * @package MobilityTrailblazers
 * @since 2.2.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Handle migration action
$migration_result = null;
if (isset($_POST['migrate_profiles']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_migrate_profiles')) {
    // Run migration
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ]);
    
    $migrated = 0;
    $errors = [];
    
    foreach ($candidates as $candidate) {
        try {
            // Ensure all meta fields are properly set
            $meta_fields = [
                '_mt_display_name' => $candidate->post_title,
                '_mt_organization' => get_post_meta($candidate->ID, '_mt_organization', true),
                '_mt_position' => get_post_meta($candidate->ID, '_mt_position', true),
                '_mt_linkedin' => get_post_meta($candidate->ID, '_mt_linkedin', true),
                '_mt_website' => get_post_meta($candidate->ID, '_mt_website', true),
                '_mt_top50' => get_post_meta($candidate->ID, '_mt_top50', true),
                '_mt_nominator' => get_post_meta($candidate->ID, '_mt_nominator', true),
            ];
            
            foreach ($meta_fields as $key => $value) {
                if (!empty($value)) {
                    update_post_meta($candidate->ID, $key, $value);
                }
            }
            
            $migrated++;
        } catch (Exception $e) {
            $errors[] = sprintf(__('Error migrating %s: %s', 'mobility-trailblazers'), $candidate->post_title, $e->getMessage());
        }
    }
    
    $migration_result = [
        'success' => true,
        'migrated' => $migrated,
        'total' => count($candidates),
        'errors' => $errors
    ];
}

// Get current statistics
$total_candidates = wp_count_posts('mt_candidate');
$categories = get_terms([
    'taxonomy' => 'mt_award_category',
    'hide_empty' => false
]);
?>

<div class="wrap">
    <h1><?php _e('Migrate Candidate Profiles', 'mobility-trailblazers'); ?></h1>
    
    <?php if ($migration_result): ?>
        <div class="notice notice-<?php echo empty($migration_result['errors']) ? 'success' : 'warning'; ?>">
            <p>
                <?php printf(
                    __('Migration completed: %d of %d profiles migrated.', 'mobility-trailblazers'),
                    $migration_result['migrated'],
                    $migration_result['total']
                ); ?>
            </p>
            <?php if (!empty($migration_result['errors'])): ?>
                <ul>
                    <?php foreach ($migration_result['errors'] as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <h2><?php _e('Profile Migration Tool', 'mobility-trailblazers'); ?></h2>
        <p><?php _e('This tool ensures all candidate profiles have the correct meta fields and structure for the enhanced profile system.', 'mobility-trailblazers'); ?></p>
        
        <h3><?php _e('Current Statistics', 'mobility-trailblazers'); ?></h3>
        <table class="wp-list-table widefat">
            <tbody>
                <tr>
                    <td><?php _e('Total Candidates', 'mobility-trailblazers'); ?></td>
                    <td><strong><?php echo ($total_candidates->publish + $total_candidates->draft); ?></strong></td>
                </tr>
                <tr>
                    <td><?php _e('Published', 'mobility-trailblazers'); ?></td>
                    <td><strong><?php echo $total_candidates->publish; ?></strong></td>
                </tr>
                <tr>
                    <td><?php _e('Draft', 'mobility-trailblazers'); ?></td>
                    <td><strong><?php echo $total_candidates->draft; ?></strong></td>
                </tr>
                <tr>
                    <td><?php _e('Categories', 'mobility-trailblazers'); ?></td>
                    <td><strong><?php echo count($categories); ?></strong></td>
                </tr>
            </tbody>
        </table>
        
        <form method="post" style="margin-top: 20px;">
            <?php wp_nonce_field('mt_migrate_profiles'); ?>
            <p>
                <button type="submit" name="migrate_profiles" class="button button-primary">
                    <?php _e('Run Migration', 'mobility-trailblazers'); ?>
                </button>
                <span class="description"><?php _e('This will update all existing candidate profiles to ensure compatibility.', 'mobility-trailblazers'); ?></span>
            </p>
        </form>
    </div>
    
    <div class="card">
        <h2><?php _e('Migration Checklist', 'mobility-trailblazers'); ?></h2>
        <ul style="list-style: disc; padding-left: 20px;">
            <li><?php _e('Backs up database before migration (recommended)', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Updates meta field structure for all candidates', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Ensures compatibility with enhanced import system', 'mobility-trailblazers'); ?></li>
            <li><?php _e('Preserves existing data', 'mobility-trailblazers'); ?></li>
        </ul>
    </div>
</div>
