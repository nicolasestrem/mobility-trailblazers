<?php
/**
 * Migrate Candidate Profiles
 * 
 * This script adds the new profile meta fields to existing candidates
 *
 * @package MobilityTrailblazers
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only run if accessed by admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

/**
 * Migrate candidate profiles to include new meta fields
 */
function mt_migrate_candidate_profiles() {
    global $wpdb;
    
    echo '<div class="wrap">';
    echo '<h1>Migrate Candidate Profiles</h1>';
    
    // Get all candidates
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'any',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    if (empty($candidates)) {
        echo '<p>No candidates found to migrate.</p>';
        echo '</div>';
        return;
    }
    
    echo '<p>Found ' . count($candidates) . ' candidates to process.</p>';
    echo '<ol>';
    
    $migrated = 0;
    $skipped = 0;
    
    foreach ($candidates as $candidate) {
        echo '<li>';
        echo 'Processing: <strong>' . esc_html($candidate->post_title) . '</strong> (ID: ' . $candidate->ID . ')... ';
        
        $updated = false;
        
        // Check and set display name if not set
        if (!get_post_meta($candidate->ID, '_mt_display_name', true)) {
            update_post_meta($candidate->ID, '_mt_display_name', $candidate->post_title);
            $updated = true;
        }
        
        // Initialize empty profile sections if not set
        if (!metadata_exists('post', $candidate->ID, '_mt_overview')) {
            update_post_meta($candidate->ID, '_mt_overview', '');
            $updated = true;
        }
        
        if (!metadata_exists('post', $candidate->ID, '_mt_evaluation_criteria')) {
            update_post_meta($candidate->ID, '_mt_evaluation_criteria', '');
            $updated = true;
        }
        
        if (!metadata_exists('post', $candidate->ID, '_mt_personality_motivation')) {
            update_post_meta($candidate->ID, '_mt_personality_motivation', '');
            $updated = true;
        }
        
        if ($updated) {
            echo '<span style="color: green;">✓ Migrated</span>';
            $migrated++;
        } else {
            echo '<span style="color: gray;">○ Already has profile fields</span>';
            $skipped++;
        }
        
        echo '</li>';
        
        // Prevent timeout on large datasets
        if ($migrated % 50 === 0) {
            flush();
        }
    }
    
    echo '</ol>';
    
    echo '<div class="notice notice-success">';
    echo '<p><strong>Migration Complete!</strong></p>';
    echo '<ul>';
    echo '<li>Total candidates processed: ' . count($candidates) . '</li>';
    echo '<li>Candidates migrated: ' . $migrated . '</li>';
    echo '<li>Candidates skipped (already had fields): ' . $skipped . '</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<p><a href="' . admin_url('edit.php?post_type=mt_candidate') . '" class="button button-primary">View All Candidates</a></p>';
    
    echo '</div>';
}

// Check if we should run the migration
if (isset($_GET['page']) && $_GET['page'] === 'mt-migrate-profiles' && isset($_GET['run']) && $_GET['run'] === 'true') {
    mt_migrate_candidate_profiles();
} else {
    // Show migration info page
    ?>
    <div class="wrap">
        <h1>Migrate Candidate Profiles</h1>
        
        <div class="card">
            <h2>What This Migration Does</h2>
            <p>This migration script will add the new enhanced profile fields to all existing candidates:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><strong>Display Name</strong> - Full name with titles (e.g., Prof. Dr. Jane Smith)</li>
                <li><strong>Überblick (Overview)</strong> - Biographical overview section</li>
                <li><strong>Bewertung nach Kriterien</strong> - Evaluation criteria section</li>
                <li><strong>Persönlichkeit & Motivation</strong> - Personality and motivation section</li>
            </ul>
            <p>Existing data will not be modified. Only missing fields will be added with empty values.</p>
        </div>
        
        <div class="card">
            <h2>Before You Begin</h2>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>Make sure you have a recent backup of your database</li>
                <li>This process may take a few minutes if you have many candidates</li>
                <li>The migration is safe to run multiple times - it will skip candidates that already have the fields</li>
            </ul>
        </div>
        
        <p>
            <a href="<?php echo admin_url('admin.php?page=mt-migrate-profiles&run=true'); ?>" 
               class="button button-primary"
               onclick="return confirm('Are you ready to run the migration? Make sure you have a backup first.');">
                Run Migration
            </a>
            <a href="<?php echo admin_url('edit.php?post_type=mt_candidate'); ?>" class="button">
                Cancel
            </a>
        </p>
    </div>
    <?php
}
?>