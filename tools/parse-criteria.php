<?php
/**
 * Parse and Structure Evaluation Criteria
 * Extracts and structures the evaluation criteria text into individual meta fields
 * 
 * @package MobilityTrailblazers
 * @version 2.4.0
 */

// Function to parse criteria text into structured data
function mt_parse_evaluation_criteria($criteria_text) {
    $structured_criteria = [
        'mut' => '',
        'innovation' => '',
        'umsetzung' => '',
        'relevanz' => '',
        'vorbild' => ''
    ];
    
    // Define patterns for each criterion
    $patterns = [
        'mut' => [
            'pattern' => '/(?:Mut\s*&?\s*Pioniergeist|Mut und Pioniergeist):?\s*(.+?)(?=(?:Innovationsgrad|Umsetzungskraft|Relevanz|Vorbildfunktion|$))/si',
            'key' => 'mut'
        ],
        'innovation' => [
            'pattern' => '/(?:Innovationsgrad|Innovation):?\s*(.+?)(?=(?:Mut|Umsetzungskraft|Relevanz|Vorbildfunktion|$))/si',
            'key' => 'innovation'
        ],
        'umsetzung' => [
            'pattern' => '/(?:Umsetzungskraft\s*&?\s*Wirkung|Umsetzungskraft und Wirkung|Umsetzung):?\s*(.+?)(?=(?:Mut|Innovationsgrad|Relevanz|Vorbildfunktion|$))/si',
            'key' => 'umsetzung'
        ],
        'relevanz' => [
            'pattern' => '/(?:Relevanz\s*(?:für\s*die\s*)?Mobilitätswende|Relevanz):?\s*(.+?)(?=(?:Mut|Innovationsgrad|Umsetzungskraft|Vorbildfunktion|$))/si',
            'key' => 'relevanz'
        ],
        'vorbild' => [
            'pattern' => '/(?:Vorbildfunktion\s*&?\s*Sichtbarkeit|Vorbildfunktion und Sichtbarkeit|Vorbild):?\s*(.+?)(?=(?:Mut|Innovationsgrad|Umsetzungskraft|Relevanz|$))/si',
            'key' => 'vorbild'
        ]
    ];
    
    // Try to extract each criterion
    foreach ($patterns as $criterion) {
        if (preg_match($criterion['pattern'], $criteria_text, $matches)) {
            $structured_criteria[$criterion['key']] = trim($matches[1]);
        }
    }
    
    // If no patterns matched, try alternative parsing
    if (empty(array_filter($structured_criteria))) {
        $structured_criteria = mt_parse_criteria_alternative($criteria_text);
    }
    
    return $structured_criteria;
}

// Alternative parsing method for different formats
function mt_parse_criteria_alternative($criteria_text) {
    $structured_criteria = [
        'mut' => '',
        'innovation' => '',
        'umsetzung' => '',
        'relevanz' => '',
        'vorbild' => ''
    ];
    
    // Split by line breaks or periods for simpler parsing
    $lines = preg_split('/[\n\r]+/', $criteria_text);
    $current_criterion = null;
    $buffer = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Check if this line starts a new criterion
        if (stripos($line, 'Mut') !== false && stripos($line, 'Pioniergeist') !== false) {
            if ($current_criterion && $buffer) {
                $structured_criteria[$current_criterion] = trim($buffer);
            }
            $current_criterion = 'mut';
            $buffer = preg_replace('/^.*?:\s*/i', '', $line);
        } elseif (stripos($line, 'Innovation') !== false) {
            if ($current_criterion && $buffer) {
                $structured_criteria[$current_criterion] = trim($buffer);
            }
            $current_criterion = 'innovation';
            $buffer = preg_replace('/^.*?:\s*/i', '', $line);
        } elseif (stripos($line, 'Umsetzung') !== false) {
            if ($current_criterion && $buffer) {
                $structured_criteria[$current_criterion] = trim($buffer);
            }
            $current_criterion = 'umsetzung';
            $buffer = preg_replace('/^.*?:\s*/i', '', $line);
        } elseif (stripos($line, 'Relevanz') !== false) {
            if ($current_criterion && $buffer) {
                $structured_criteria[$current_criterion] = trim($buffer);
            }
            $current_criterion = 'relevanz';
            $buffer = preg_replace('/^.*?:\s*/i', '', $line);
        } elseif (stripos($line, 'Vorbild') !== false) {
            if ($current_criterion && $buffer) {
                $structured_criteria[$current_criterion] = trim($buffer);
            }
            $current_criterion = 'vorbild';
            $buffer = preg_replace('/^.*?:\s*/i', '', $line);
        } else {
            // Continue building current criterion
            if ($current_criterion) {
                $buffer .= ' ' . $line;
            }
        }
    }
    
    // Save last criterion
    if ($current_criterion && $buffer) {
        $structured_criteria[$current_criterion] = trim($buffer);
    }
    
    return $structured_criteria;
}

// Main function to process all candidates
function mt_process_all_criteria() {
    echo "=== Starting Criteria Parsing Process ===\n\n";
    
    // Get all candidates
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    $processed = 0;
    $updated = 0;
    $failed = 0;
    
    foreach ($candidates as $candidate) {
        $candidate_id = $candidate->ID;
        $title = $candidate->post_title;
        
        echo "Processing: $title (ID: $candidate_id)\n";
        
        // Get existing evaluation criteria
        $eval_criteria = get_post_meta($candidate_id, '_mt_evaluation_criteria', true);
        
        if (empty($eval_criteria)) {
            echo "  → No evaluation criteria found, skipping\n\n";
            continue;
        }
        
        // Check if already has structured criteria
        $existing_mut = get_post_meta($candidate_id, '_mt_criterion_mut', true);
        if (!empty($existing_mut)) {
            echo "  → Already has structured criteria, skipping\n\n";
            $processed++;
            continue;
        }
        
        // Parse the criteria
        $structured = mt_parse_evaluation_criteria($eval_criteria);
        
        // Save structured criteria as individual meta fields
        $fields_updated = 0;
        foreach ($structured as $key => $value) {
            if (!empty($value)) {
                update_post_meta($candidate_id, '_mt_criterion_' . $key, $value);
                $fields_updated++;
                echo "  ✓ Saved criterion: $key (" . str_word_count($value) . " words)\n";
            }
        }
        
        if ($fields_updated > 0) {
            echo "  ✓ Successfully parsed and saved $fields_updated criteria\n";
            $updated++;
        } else {
            echo "  ✗ Failed to parse criteria\n";
            $failed++;
        }
        
        echo "\n";
    }
    
    // Summary
    echo "=== Parsing Complete ===\n";
    echo "Total candidates: " . count($candidates) . "\n";
    echo "Already processed: $processed\n";
    echo "Successfully updated: $updated\n";
    echo "Failed to parse: $failed\n";
    
    return [
        'total' => count($candidates),
        'processed' => $processed,
        'updated' => $updated,
        'failed' => $failed
    ];
}

// Function to display parsed criteria for verification
function mt_verify_parsed_criteria($candidate_id = null) {
    if ($candidate_id) {
        $candidates = [get_post($candidate_id)];
    } else {
        $candidates = get_posts([
            'post_type' => 'mt_candidate',
            'posts_per_page' => 5, // Sample check
            'post_status' => 'publish',
            'orderby' => 'rand'
        ]);
    }
    
    echo "=== Criteria Parsing Verification ===\n\n";
    
    foreach ($candidates as $candidate) {
        $candidate_id = $candidate->ID;
        $title = $candidate->post_title;
        
        echo "Candidate: $title (ID: $candidate_id)\n";
        echo str_repeat('-', 50) . "\n";
        
        // Get original criteria
        $original = get_post_meta($candidate_id, '_mt_evaluation_criteria', true);
        
        if ($original) {
            echo "Original Text Length: " . strlen($original) . " characters\n\n";
            
            // Get or parse structured criteria
            $structured = [
                'mut' => get_post_meta($candidate_id, '_mt_criterion_mut', true),
                'innovation' => get_post_meta($candidate_id, '_mt_criterion_innovation', true),
                'umsetzung' => get_post_meta($candidate_id, '_mt_criterion_umsetzung', true),
                'relevanz' => get_post_meta($candidate_id, '_mt_criterion_relevanz', true),
                'vorbild' => get_post_meta($candidate_id, '_mt_criterion_vorbild', true)
            ];
            
            // If not saved, parse it
            if (empty(array_filter($structured))) {
                $structured = mt_parse_evaluation_criteria($original);
                echo "⚠ Criteria not yet saved - showing parsed result:\n\n";
            } else {
                echo "✓ Showing saved structured criteria:\n\n";
            }
            
            // Display each criterion
            $labels = [
                'mut' => 'Mut & Pioniergeist',
                'innovation' => 'Innovationsgrad',
                'umsetzung' => 'Umsetzungskraft & Wirkung',
                'relevanz' => 'Relevanz für die Mobilitätswende',
                'vorbild' => 'Vorbildfunktion & Sichtbarkeit'
            ];
            
            foreach ($labels as $key => $label) {
                echo "【 $label 】\n";
                if (!empty($structured[$key])) {
                    echo wordwrap($structured[$key], 80) . "\n";
                } else {
                    echo "(No content extracted)\n";
                }
                echo "\n";
            }
        } else {
            echo "No evaluation criteria found.\n";
        }
        
        echo "\n" . str_repeat('=', 60) . "\n\n";
    }
}

// Function to clear parsed criteria (for testing)
function mt_clear_parsed_criteria() {
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    $cleared = 0;
    foreach ($candidates as $candidate) {
        delete_post_meta($candidate->ID, '_mt_criterion_mut');
        delete_post_meta($candidate->ID, '_mt_criterion_innovation');
        delete_post_meta($candidate->ID, '_mt_criterion_umsetzung');
        delete_post_meta($candidate->ID, '_mt_criterion_relevanz');
        delete_post_meta($candidate->ID, '_mt_criterion_vorbild');
        $cleared++;
    }
    
    echo "Cleared parsed criteria for $cleared candidates.\n";
}

// Run the appropriate function based on command line arguments
if (defined('WP_CLI')) {
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case 'process':
                mt_process_all_criteria();
                break;
            case 'verify':
                $candidate_id = isset($argv[2]) ? intval($argv[2]) : null;
                mt_verify_parsed_criteria($candidate_id);
                break;
            case 'clear':
                mt_clear_parsed_criteria();
                break;
            default:
                echo "Usage: wp eval-file parse-criteria.php [process|verify|clear] [candidate_id]\n";
        }
    } else {
        echo "Usage: wp eval-file parse-criteria.php [process|verify|clear] [candidate_id]\n";
    }
} elseif (defined('ABSPATH')) {
    // If run directly in WordPress context, process all
    echo "<pre>";
    mt_process_all_criteria();
    echo "</pre>";
} else {
    echo "This script must be run within WordPress context.\n";
    echo "Use: wp eval-file tools/parse-criteria.php process\n";
}