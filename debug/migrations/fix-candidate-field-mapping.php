<?php
/**
 * Fix Candidate Field Mapping
 * Maps field names for backward compatibility
 * 
 * This script creates duplicate meta fields with alternative names
 * to ensure compatibility with both old and new field naming conventions
 * 
 * Run this script via WP-CLI: wp eval-file debug/migrations/fix-candidate-field-mapping.php
 * Or include it in admin panel for one-time execution
 * 
 * @package MobilityTrailblazers
 * @version 2.4.1
 */

// Security check
if (!defined('WP_CLI') && !current_user_can('manage_options')) {
    die('Direct access not allowed');
}

function mt_fix_candidate_field_mapping() {
    global $wpdb;
    
    echo "Starting candidate field mapping migration...\n";
    
    // Get all candidates
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'any'
    ]);
    
    $updated = 0;
    $field_mappings = [
        // Map new field names to old field names for backward compatibility
        '_mt_overview' => '_mt_description_full',
        '_mt_linkedin' => '_mt_linkedin_url',
        '_mt_website' => '_mt_website_url',
        '_mt_display_name' => '_mt_candidate_name'
    ];
    
    foreach ($candidates as $candidate) {
        $candidate_id = $candidate->ID;
        $needs_update = false;
        
        foreach ($field_mappings as $old_field => $new_field) {
            // Check if old field doesn't exist but new field does
            $old_value = get_post_meta($candidate_id, $old_field, true);
            $new_value = get_post_meta($candidate_id, $new_field, true);
            
            if (empty($old_value) && !empty($new_value)) {
                // Copy new field value to old field name for backward compatibility
                update_post_meta($candidate_id, $old_field, $new_value);
                $needs_update = true;
                echo "  - Added {$old_field} from {$new_field}\n";
            } elseif (!empty($old_value) && empty($new_value)) {
                // Copy old field value to new field name for forward compatibility
                update_post_meta($candidate_id, $new_field, $old_value);
                $needs_update = true;
                echo "  - Added {$new_field} from {$old_field}\n";
            }
        }
        
        // Handle evaluation criteria combination
        $eval_criteria = get_post_meta($candidate_id, '_mt_evaluation_criteria', true);
        if (empty($eval_criteria)) {
            $criteria_parts = [];
            
            $courage = get_post_meta($candidate_id, '_mt_evaluation_courage', true);
            if ($courage) {
                $criteria_parts[] = "**Mut & Pioniergeist:**\n" . $courage;
            }
            
            $innovation = get_post_meta($candidate_id, '_mt_evaluation_innovation', true);
            if ($innovation) {
                $criteria_parts[] = "**Innovationsgrad:**\n" . $innovation;
            }
            
            $implementation = get_post_meta($candidate_id, '_mt_evaluation_implementation', true);
            if ($implementation) {
                $criteria_parts[] = "**Umsetzungskraft & Wirkung:**\n" . $implementation;
            }
            
            $relevance = get_post_meta($candidate_id, '_mt_evaluation_relevance', true);
            if ($relevance) {
                $criteria_parts[] = "**Relevanz für die Mobilitätswende:**\n" . $relevance;
            }
            
            $visibility = get_post_meta($candidate_id, '_mt_evaluation_visibility', true);
            if ($visibility) {
                $criteria_parts[] = "**Vorbildfunktion & Sichtbarkeit:**\n" . $visibility;
            }
            
            if (!empty($criteria_parts)) {
                $combined_criteria = implode("\n\n", $criteria_parts);
                update_post_meta($candidate_id, '_mt_evaluation_criteria', $combined_criteria);
                $needs_update = true;
                echo "  - Combined individual criteria into _mt_evaluation_criteria\n";
            }
        }
        
        if ($needs_update) {
            $updated++;
            echo "Updated candidate: {$candidate->post_title} (ID: {$candidate_id})\n";
        }
    }
    
    echo "\n=================================\n";
    echo "Migration completed successfully!\n";
    echo "Total candidates processed: " . count($candidates) . "\n";
    echo "Total candidates updated: {$updated}\n";
    echo "=================================\n";
    
    // Clear any caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        echo "Cache cleared.\n";
    }
    
    return [
        'processed' => count($candidates),
        'updated' => $updated
    ];
}

// Check if running via WP-CLI
if (defined('WP_CLI') && WP_CLI) {
    $result = mt_fix_candidate_field_mapping();
    WP_CLI::success("Migration completed. Processed: {$result['processed']}, Updated: {$result['updated']}");
} elseif (isset($_GET['run_migration']) && $_GET['run_migration'] === 'fix_field_mapping') {
    // Allow running via admin URL parameter for testing
    if (current_user_can('manage_options')) {
        echo "<pre>";
        $result = mt_fix_candidate_field_mapping();
        echo "</pre>";
    }
} else {
    // Display information if accessed directly
    if (current_user_can('manage_options')) {
        echo "<h2>Candidate Field Mapping Migration</h2>";
        echo "<p>This script ensures backward compatibility by creating duplicate meta fields with both old and new naming conventions.</p>";
        echo "<p>To run this migration:</p>";
        echo "<ul>";
        echo "<li>Via WP-CLI: <code>wp eval-file " . __FILE__ . "</code></li>";
        echo "<li>Via URL: Add <code>?run_migration=fix_field_mapping</code> to this page URL</li>";
        echo "</ul>";
        echo "<p><a href='?run_migration=fix_field_mapping' class='button button-primary'>Run Migration Now</a></p>";
    }
}