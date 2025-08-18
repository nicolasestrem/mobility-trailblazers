<?php
/**
 * Script to analyze which candidates are missing biography content
 * 
 * Usage: php analyze-missing-biographies.php
 */

// Load WordPress
$wp_load_paths = [
    '/var/www/html/wp-load.php',  // Docker environment
    dirname(__FILE__) . '/../../../../wp-load.php',  // Standard WordPress
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die("Could not load WordPress.\n");
}

echo "========================================\n";
echo "  BIOGRAPHY CONTENT ANALYSIS\n";
echo "========================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Get all candidates
$candidates = get_posts([
    'post_type' => 'mt_candidate',
    'posts_per_page' => -1,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC'
]);

echo "Total candidates: " . count($candidates) . "\n\n";

$with_biography = [];
$without_biography = [];
$with_criteria = [];
$without_criteria = [];
$complete = [];
$partial = [];
$empty = [];

foreach ($candidates as $candidate) {
    $has_content = !empty($candidate->post_content);
    $overview = get_post_meta($candidate->ID, '_mt_overview', true);
    $has_overview = !empty($overview);
    
    // Check evaluation criteria
    $courage = get_post_meta($candidate->ID, '_mt_criterion_courage', true);
    $innovation = get_post_meta($candidate->ID, '_mt_criterion_innovation', true);
    $implementation = get_post_meta($candidate->ID, '_mt_criterion_implementation', true);
    $relevance = get_post_meta($candidate->ID, '_mt_criterion_relevance', true);
    $visibility = get_post_meta($candidate->ID, '_mt_criterion_visibility', true);
    
    $has_any_criteria = !empty($courage) || !empty($innovation) || !empty($implementation) || 
                        !empty($relevance) || !empty($visibility);
    
    $criteria_count = 0;
    if (!empty($courage)) $criteria_count++;
    if (!empty($innovation)) $criteria_count++;
    if (!empty($implementation)) $criteria_count++;
    if (!empty($relevance)) $criteria_count++;
    if (!empty($visibility)) $criteria_count++;
    
    $has_biography = $has_content || $has_overview;
    
    $status = [
        'name' => $candidate->post_title,
        'id' => $candidate->ID,
        'has_biography' => $has_biography,
        'has_criteria' => $has_any_criteria,
        'criteria_count' => $criteria_count,
        'content_length' => strlen($candidate->post_content),
        'overview_length' => strlen($overview)
    ];
    
    if ($has_biography && $criteria_count >= 3) {
        $complete[] = $status;
    } elseif ($has_biography || $has_any_criteria) {
        $partial[] = $status;
        if (!$has_biography) {
            $without_biography[] = $status;
        }
        if (!$has_any_criteria) {
            $without_criteria[] = $status;
        }
    } else {
        $empty[] = $status;
        $without_biography[] = $status;
        $without_criteria[] = $status;
    }
    
    if ($has_biography) {
        $with_biography[] = $status;
    }
    if ($has_any_criteria) {
        $with_criteria[] = $status;
    }
}

// Display results
echo "=== SUMMARY ===\n";
echo "Complete profiles (biography + 3+ criteria): " . count($complete) . "\n";
echo "Partial profiles: " . count($partial) . "\n";
echo "Empty profiles: " . count($empty) . "\n";
echo "With biography: " . count($with_biography) . "\n";
echo "Without biography: " . count($without_biography) . "\n";
echo "With criteria: " . count($with_criteria) . "\n";
echo "Without criteria: " . count($without_criteria) . "\n\n";

if (count($without_biography) > 0) {
    echo "=== CANDIDATES WITHOUT BIOGRAPHY ===\n";
    foreach ($without_biography as $candidate) {
        echo "- {$candidate['name']} (ID: {$candidate['id']}";
        if ($candidate['criteria_count'] > 0) {
            echo ", has {$candidate['criteria_count']} criteria";
        }
        echo ")\n";
    }
    echo "\n";
}

if (count($empty) > 0) {
    echo "=== CANDIDATES WITH NO CONTENT ===\n";
    foreach ($empty as $candidate) {
        echo "- {$candidate['name']} (ID: {$candidate['id']})\n";
    }
    echo "\n";
}

if (count($partial) > 0) {
    echo "=== PARTIAL PROFILES (Need Completion) ===\n";
    foreach ($partial as $candidate) {
        $missing = [];
        if (!$candidate['has_biography']) $missing[] = "biography";
        if ($candidate['criteria_count'] < 3) $missing[] = (3 - $candidate['criteria_count']) . " criteria";
        echo "- {$candidate['name']} (missing: " . implode(', ', $missing) . ")\n";
    }
}

echo "\n========================================\n";