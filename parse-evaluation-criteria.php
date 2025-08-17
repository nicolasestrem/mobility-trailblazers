<?php
/**
 * Parse Evaluation Criteria for Enhanced Template
 * 
 * This script processes all candidates and parses their evaluation criteria
 * into individual meta fields for the enhanced template display.
 *
 * Usage: wp eval-file parse-evaluation-criteria.php
 *
 * @package MobilityTrailblazers
 * @since 2.4.0
 */

// Make sure we're in WordPress context
if (!defined('ABSPATH')) {
    echo "Error: This script must be run from WordPress context.\n";
    echo "Usage: wp eval-file parse-evaluation-criteria.php\n";
    exit(1);
}

// Include the template loader class
require_once(WP_PLUGIN_DIR . '/mobility-trailblazers/includes/core/class-mt-template-loader.php');

// Parse evaluation criteria for all candidates
function parse_candidate_criteria() {
    echo "=== Mobility Trailblazers: Parse Evaluation Criteria ===\n\n";
    
    // Get all candidates with evaluation criteria
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_query' => [
            [
                'key' => '_mt_evaluation_criteria',
                'value' => '',
                'compare' => '!='
            ]
        ]
    ]);
    
    echo "Found " . count($candidates) . " candidates with evaluation criteria.\n\n";
    
    if (empty($candidates)) {
        echo "No candidates found with evaluation criteria to process.\n";
        return;
    }
    
    $processed = 0;
    $successful = 0;
    $errors = [];
    
    foreach ($candidates as $candidate) {
        $processed++;
        $candidate_name = get_the_title($candidate->ID);
        
        echo "Processing: {$candidate_name} (ID: {$candidate->ID})... ";
        
        // Get the evaluation criteria text
        $criteria_text = get_post_meta($candidate->ID, '_mt_evaluation_criteria', true);
        
        if (empty($criteria_text)) {
            echo "SKIPPED (no criteria text)\n";
            continue;
        }
        
        // Parse criteria using the template loader
        $parsed = \MobilityTrailblazers\Core\MT_Template_Loader::parse_evaluation_criteria($criteria_text);
        
        if (empty($parsed)) {
            echo "FAILED (could not parse criteria)\n";
            $errors[] = "Failed to parse criteria for {$candidate_name} (ID: {$candidate->ID})";
            continue;
        }
        
        // Save parsed criteria as individual meta fields
        $saved_count = 0;
        foreach ($parsed as $key => $content) {
            $meta_key = '_mt_criterion_' . $key;
            $result = update_post_meta($candidate->ID, $meta_key, $content);
            
            if ($result !== false) {
                $saved_count++;
            }
        }
        
        if ($saved_count > 0) {
            echo "SUCCESS ({$saved_count} criteria saved)\n";
            $successful++;
        } else {
            echo "FAILED (could not save criteria)\n";
            $errors[] = "Failed to save criteria for {$candidate_name} (ID: {$candidate->ID})";
        }
    }
    
    echo "\n=== PROCESSING COMPLETE ===\n";
    echo "Total candidates processed: {$processed}\n";
    echo "Successfully parsed: {$successful}\n";
    echo "Errors: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "\nError details:\n";
        foreach ($errors as $error) {
            echo "- {$error}\n";
        }
    }
    
    echo "\nNext steps:\n";
    echo "1. Visit any candidate profile page to see the enhanced template\n";
    echo "2. The template will show structured criteria cards if parsing was successful\n";
    echo "3. Check the browser developer tools for any CSS/JavaScript errors\n\n";
}

// Verify specific candidate criteria parsing
function verify_candidate_parsing($candidate_id) {
    echo "=== Verifying Candidate Criteria Parsing ===\n\n";
    
    $candidate = get_post($candidate_id);
    if (!$candidate || $candidate->post_type !== 'mt_candidate') {
        echo "Error: Invalid candidate ID or not a candidate post.\n";
        return;
    }
    
    $candidate_name = get_the_title($candidate_id);
    echo "Candidate: {$candidate_name} (ID: {$candidate_id})\n\n";
    
    // Check original criteria
    $original_criteria = get_post_meta($candidate_id, '_mt_evaluation_criteria', true);
    echo "Original criteria text:\n";
    echo "--- START ---\n";
    echo $original_criteria;
    echo "\n--- END ---\n\n";
    
    // Check parsed criteria
    $criteria_fields = [
        'courage' => 'Mut & Pioniergeist',
        'innovation' => 'Innovationsgrad',
        'implementation' => 'Umsetzungskraft & Wirkung',
        'relevance' => 'Relevanz für Mobilitätswende',
        'visibility' => 'Vorbildfunktion & Sichtbarkeit'
    ];
    
    echo "Parsed criteria fields:\n";
    $found_criteria = 0;
    
    foreach ($criteria_fields as $key => $label) {
        $meta_key = '_mt_criterion_' . $key;
        $value = get_post_meta($candidate_id, $meta_key, true);
        
        if (!empty($value)) {
            $found_criteria++;
            echo "✓ {$label}: " . substr($value, 0, 100) . "...\n";
        } else {
            echo "✗ {$label}: NOT FOUND\n";
        }
    }
    
    echo "\nSummary: {$found_criteria}/5 criteria fields found\n";
    
    if ($found_criteria === 0) {
        echo "\nTrying to parse criteria now...\n";
        $parsed = \MobilityTrailblazers\Core\MT_Template_Loader::parse_evaluation_criteria($original_criteria);
        
        if (!empty($parsed)) {
            echo "Successfully parsed " . count($parsed) . " criteria:\n";
            foreach ($parsed as $key => $content) {
                echo "- {$key}: " . substr($content, 0, 100) . "...\n";
            }
        } else {
            echo "Failed to parse criteria. The text might not match expected patterns.\n";
        }
    }
}

// Check what action to perform
$args = isset($args) ? $args : [];

if (empty($args)) {
    // Default action: parse all candidates
    parse_candidate_criteria();
} else {
    $action = $args[0];
    
    switch ($action) {
        case 'verify':
            if (isset($args[1]) && is_numeric($args[1])) {
                verify_candidate_parsing(intval($args[1]));
            } else {
                echo "Usage: wp eval-file parse-evaluation-criteria.php verify CANDIDATE_ID\n";
                echo "Example: wp eval-file parse-evaluation-criteria.php verify 4377\n";
            }
            break;
            
        case 'process':
            parse_candidate_criteria();
            break;
            
        default:
            echo "Available actions:\n";
            echo "- process: Parse criteria for all candidates (default)\n";
            echo "- verify CANDIDATE_ID: Verify parsing for specific candidate\n";
            echo "\nUsage examples:\n";
            echo "wp eval-file parse-evaluation-criteria.php\n";
            echo "wp eval-file parse-evaluation-criteria.php process\n";
            echo "wp eval-file parse-evaluation-criteria.php verify 4377\n";
    }
}
