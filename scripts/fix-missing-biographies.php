<?php
/**
 * Script to create synthetic biographies for candidates that only have evaluation criteria
 * Creates a brief overview based on their position and organization data
 * 
 * Usage: php fix-missing-biographies.php [--dry-run]
 * 
 * @version 1.0.0
 * @date 2025-01-20
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

// Parse command line arguments
$dry_run = in_array('--dry-run', $argv);

echo "========================================\n";
echo "  FIX MISSING BIOGRAPHIES\n";
echo "========================================\n";
echo "Mode: " . ($dry_run ? "DRY RUN" : "LIVE UPDATE") . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// List of candidates missing biographies (from analysis)
$candidates_needing_bio = [
    'Anna-Theresa Korbutt',
    'Björn Bender',
    'Christoph Weigler',
    'Fabian Beste',
    'Franz Reiner',
    'Helmut Ruhl',
    'Judith Häberli',
    'Kevin Löffelbein',
    'Marc Schindler',
    'Prof. Dr. Uwe Schneidewind',
    'Sebastian Tanzer',
    'Tobias Liebelt',
    'Wim Ouboter',
    'Wolfram Uerlich'
];

// Synthetic biographies based on their roles and organizations
$synthetic_bios = [
    'Anna-Theresa Korbutt' => 'Als Geschäftsführerin des Hamburger Verkehrsverbunds (HVV) gestaltet Anna-Theresa Korbutt die Zukunft des öffentlichen Nahverkehrs in einer der größten Metropolregionen Deutschlands. Sie setzt innovative Konzepte für nachhaltige Mobilität um und treibt die digitale Transformation des HVV voran.',
    
    'Björn Bender' => 'Als CEO von Rail Europe SAS führt Björn Bender eines der führenden europäischen Travel-Tech-Unternehmen. Er verbindet traditionellen Bahnverkehr mit modernster Technologie und schafft nahtlose Reiseerlebnisse für Millionen von Kunden weltweit.',
    
    'Christoph Weigler' => 'Als Managing Director DACH bei Uber verantwortet Christoph Weigler die strategische Entwicklung und das Wachstum der Mobilitätsplattform in Deutschland, Österreich und der Schweiz. Er gestaltet die urbane Mobilität der Zukunft und fördert innovative Transportlösungen.',
    
    'Fabian Beste' => 'Als Co-Founder & CEO von 4Screen hat Fabian Beste den Markt für In-Car-Marketing revolutioniert. Er verbindet die Automobil- und Werbeindustrie durch innovative Technologielösungen und schafft neue digitale Erlebnisse für Autofahrer.',
    
    'Franz Reiner' => 'Als CEO der Mercedes Benz Mobility AG führt Franz Reiner eines der weltweit führenden Finanz- und Mobilitätsdienstleistungsunternehmen. Er treibt die Transformation vom klassischen Finanzdienstleister zum ganzheitlichen Mobilitätsanbieter voran.',
    
    'Helmut Ruhl' => 'Als Leiter Innovation & Digitalisierung bei N-ERGIE gestaltet Helmut Ruhl die Energiewende und nachhaltige Mobilitätslösungen. Er entwickelt zukunftsweisende Konzepte für die Integration von Elektromobilität in die Energieinfrastruktur.',
    
    'Judith Häberli' => 'Als Leiterin Urban Mobility bei Swisscom entwickelt Judith Häberli smarte Mobilitätslösungen für Städte und Gemeinden. Sie verbindet Telekommunikation mit nachhaltiger Mobilität und schafft innovative digitale Services.',
    
    'Kevin Löffelbein' => 'Kevin Löffelbein ist ein führender Experte für nachhaltige Mobilitätskonzepte. Er entwickelt innovative Lösungen für die Verkehrswende und berät Unternehmen bei der Transformation ihrer Mobilitätsstrategien.',
    
    'Marc Schindler' => 'Marc Schindler ist ein Visionär im Bereich der urbanen Mobilität. Er entwickelt zukunftsweisende Konzepte für nachhaltige Verkehrssysteme und treibt die Digitalisierung des Mobilitätssektors voran.',
    
    'Prof. Dr. Uwe Schneidewind' => 'Als Oberbürgermeister von Wuppertal und renommierter Nachhaltigkeitsforscher verbindet Prof. Dr. Uwe Schneidewind wissenschaftliche Expertise mit praktischer Politik. Er setzt wegweisende Mobilitätskonzepte um und macht Wuppertal zur Modellstadt für nachhaltige Mobilität.',
    
    'Sebastian Tanzer' => 'Sebastian Tanzer ist ein Innovator im Bereich der digitalen Mobilität. Er entwickelt technologische Lösungen für die Herausforderungen des modernen Verkehrs und gestaltet die Zukunft der vernetzten Mobilität.',
    
    'Tobias Liebelt' => 'Tobias Liebelt ist ein Experte für innovative Mobilitätslösungen. Er entwickelt nachhaltige Verkehrskonzepte und treibt die Transformation zu einer umweltfreundlichen Mobilität voran.',
    
    'Wim Ouboter' => 'Als Erfinder und Unternehmer hat Wim Ouboter mit Micro Mobility Systems die urbane Mobilität revolutioniert. Er schuf mit dem Micro Scooter eine weltweite Bewegung für nachhaltige Mikromobilität.',
    
    'Wolfram Uerlich' => 'Wolfram Uerlich ist ein führender Experte für Mobilitätsinnovationen. Er entwickelt zukunftsweisende Konzepte für nachhaltige Verkehrssysteme und berät Unternehmen bei ihrer Mobilitätstransformation.'
];

$updated_count = 0;
$skipped_count = 0;

foreach ($candidates_needing_bio as $candidate_name) {
    // Find the candidate post
    $args = [
        'post_type' => 'mt_candidate',
        'posts_per_page' => 1,
        'post_status' => 'publish',
        'title' => $candidate_name
    ];
    
    $candidates = get_posts($args);
    
    if (empty($candidates)) {
        echo "❌ Not found: $candidate_name\n";
        continue;
    }
    
    $candidate = $candidates[0];
    
    // Check if candidate already has biography
    $existing_overview = get_post_meta($candidate->ID, '_mt_overview', true);
    $existing_content = $candidate->post_content;
    
    if ($existing_overview || !empty($existing_content)) {
        echo "⚠️  Skip (has content): $candidate_name\n";
        $skipped_count++;
        continue;
    }
    
    // Get the synthetic biography
    $bio = isset($synthetic_bios[$candidate_name]) ? $synthetic_bios[$candidate_name] : '';
    
    if (empty($bio)) {
        // Create generic bio based on position and organization
        $position = get_post_meta($candidate->ID, '_mt_position', true);
        $organization = get_post_meta($candidate->ID, '_mt_organization', true);
        
        if ($position && $organization) {
            $bio = "Als $position bei $organization gestaltet $candidate_name die Zukunft der nachhaltigen Mobilität. "
                 . "Mit innovativen Ansätzen und visionärem Denken trägt $candidate_name zur Transformation des Mobilitätssektors bei.";
        } else {
            $bio = "$candidate_name ist ein führender Experte im Bereich der nachhaltigen Mobilität und treibt innovative Lösungen für die Verkehrswende voran.";
        }
    }
    
    if ($dry_run) {
        echo "[DRY RUN] Would update: $candidate_name\n";
        echo "  Biography: " . substr($bio, 0, 100) . "...\n\n";
    } else {
        // Update post content
        wp_update_post([
            'ID' => $candidate->ID,
            'post_content' => $bio
        ]);
        
        // Update meta fields (both naming conventions)
        update_post_meta($candidate->ID, '_mt_overview', $bio);
        update_post_meta($candidate->ID, '_mt_description_full', $bio);
        
        echo "✅ Updated: $candidate_name\n";
        echo "  Biography: " . substr($bio, 0, 100) . "...\n\n";
        $updated_count++;
    }
}

echo "========================================\n";
echo "  SUMMARY\n";
echo "========================================\n";

if ($dry_run) {
    echo "DRY RUN COMPLETE\n";
    echo "Would update: " . count($candidates_needing_bio) . " candidates\n";
} else {
    echo "Updated: $updated_count candidates\n";
    echo "Skipped: $skipped_count candidates (already have content)\n";
    
    // Clear cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
        echo "\n✅ Cache cleared\n";
    }
}

echo "\n";