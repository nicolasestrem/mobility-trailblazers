<?php
/**
 * Verification script to check photo matching status
 * Run this to see which candidates have photos and which are missing
 */

// Function to verify photo matching status
function mt_verify_photo_matching() {
    echo "=== Photo Matching Verification Report ===\n";
    echo "Generated: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Get all candidates
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Get available photo files
    $plugin_photos_dir = WP_PLUGIN_DIR . '/mobility-trailblazers/Photos_candidates/webp/';
    $photo_files = [];
    if (is_dir($plugin_photos_dir)) {
        $files = scandir($plugin_photos_dir);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'webp') {
                $photo_files[] = $file;
            }
        }
    }
    
    // Complete mapping including special case
    $photo_mappings = [
        4377 => 'AlexanderMöller.webp',
        4378 => 'AndréSchwämmlein.webp',
        4379 => 'AnjesTjarks.webp',
        4380 => 'Anna-TheresaKorbutt.webp',
        4381 => 'BenediktMiddendorf.webp',
        4382 => 'BjörnBender.webp',
        4383 => 'BorisPalmer.webp',
        4384 => 'CatrinVonCisewski.webp',
        4385 => 'ChristineVonBreitenbuch.webp',
        4386 => 'ChristophSeyerlein.webp',
        4387 => 'ChristophWeigler.webp',
        4388 => 'ChristianDahlheim.webp',
        4389 => 'CorsinSulser.webp',
        4390 => 'JanHegner.webp',
        4391 => 'FabianBeste.webp',
        4392 => 'FelixPörnbacher.webp',
        4393 => 'FranzReiner.webp',
        4394 => 'FriedrichDräxlmaier.webp',
        4395 => 'HelmutRuhl.webp',
        4396 => 'HildegardMüller.webp',
        4397 => 'HorstGraef.webp',
        4398 => 'JohannesPallasch.webp',
        4399 => 'JudithHäberli.webp',
        4400 => 'KarelDijkman.webp',
        4401 => 'KatharinaKreutzer.webp',
        4402 => 'KevinLöffelbein.webp',
        4403 => 'KlausZellmer.webp',
        4404 => 'LéaMiggiano.webp',
        4405 => 'LukasStranger.webp',
        4406 => 'ManuelHerzog.webp',
        4407 => 'MarcSchindler.webp',
        4408 => 'MatthiasBallweg.webp',
        4409 => 'MichaelKlasa.webp',
        4410 => 'NicKnapp.webp',
        4411 => 'OlgaNevska.webp',
        4412 => 'OliverBlume.webp',
        4413 => 'OliverMay-Beckmann.webp',
        4414 => 'OliverZipse.webp',
        4415 => 'UweSchneidewind.webp',
        4416 => 'RönkeVonDerHeide.webp',
        4417 => 'RoyUhlmann.webp',
        4418 => 'SarahFleischer.webp',
        4419 => 'SaschaMeyer.webp',
        4420 => 'SebastianTanzer.webp',
        4421 => 'StephanObwegeser.webp',
        4422 => 'TobiasLiebelt.webp',
        4423 => 'WenHan.webp',
        4424 => 'WimOuboter.webp',
        4425 => 'WolframUerlich.webp',
        4426 => 'XanthiDoubara.webp',
        4444 => 'GüntherSchuh.webp', // Special case: GüntherSchuh (no space in DB)
    ];
    
    $stats = [
        'total' => count($candidates),
        'has_featured' => 0,
        'missing_featured' => 0,
        'has_mapping' => 0,
        'missing_mapping' => 0,
        'photo_exists' => 0,
        'photo_missing' => 0
    ];
    
    $missing_photos = [];
    $missing_mappings = [];
    $ready_to_attach = [];
    
    echo "=== Candidate Status ===\n\n";
    
    foreach ($candidates as $candidate) {
        $has_thumbnail = has_post_thumbnail($candidate->ID);
        $has_mapping = isset($photo_mappings[$candidate->ID]);
        $photo_exists = false;
        $photo_filename = '';
        
        if ($has_mapping) {
            $photo_filename = $photo_mappings[$candidate->ID];
            $photo_exists = file_exists($plugin_photos_dir . $photo_filename);
        }
        
        // Build status string
        $status_parts = [];
        
        if ($has_thumbnail) {
            $status_parts[] = "✓ Has featured image";
            $stats['has_featured']++;
        } else {
            $status_parts[] = "✗ No featured image";
            $stats['missing_featured']++;
            
            if ($has_mapping && $photo_exists) {
                $ready_to_attach[] = [
                    'id' => $candidate->ID,
                    'title' => $candidate->post_title,
                    'photo' => $photo_filename
                ];
            }
        }
        
        if ($has_mapping) {
            $stats['has_mapping']++;
            if ($photo_exists) {
                $status_parts[] = "✓ Photo available: $photo_filename";
                $stats['photo_exists']++;
            } else {
                $status_parts[] = "✗ Photo file missing: $photo_filename";
                $stats['photo_missing']++;
                $missing_photos[] = $photo_filename;
            }
        } else {
            $status_parts[] = "✗ No mapping defined";
            $stats['missing_mapping']++;
            $missing_mappings[] = [
                'id' => $candidate->ID,
                'title' => $candidate->post_title
            ];
        }
        
        echo sprintf("ID: %d | %s\n", $candidate->ID, $candidate->post_title);
        foreach ($status_parts as $part) {
            echo "  → $part\n";
        }
        echo "\n";
    }
    
    // Summary section
    echo "=== Summary Statistics ===\n\n";
    echo "Total candidates: {$stats['total']}\n";
    echo "With featured image: {$stats['has_featured']}\n";
    echo "Without featured image: {$stats['missing_featured']}\n";
    echo "With photo mapping: {$stats['has_mapping']}\n";
    echo "Without photo mapping: {$stats['missing_mapping']}\n";
    echo "Photo files exist: {$stats['photo_exists']}\n";
    echo "Photo files missing: {$stats['photo_missing']}\n";
    
    // Ready to attach
    if (!empty($ready_to_attach)) {
        echo "\n=== Ready to Attach (" . count($ready_to_attach) . " candidates) ===\n";
        foreach ($ready_to_attach as $item) {
            echo "  • {$item['title']} (ID: {$item['id']}) → {$item['photo']}\n";
        }
    }
    
    // Missing mappings
    if (!empty($missing_mappings)) {
        echo "\n=== Missing Mappings (" . count($missing_mappings) . " candidates) ===\n";
        foreach ($missing_mappings as $item) {
            echo "  • {$item['title']} (ID: {$item['id']})\n";
        }
    }
    
    // Missing photo files
    if (!empty($missing_photos)) {
        echo "\n=== Missing Photo Files (" . count($missing_photos) . " files) ===\n";
        foreach (array_unique($missing_photos) as $photo) {
            echo "  • $photo\n";
        }
    }
    
    // Unused photo files
    $used_photos = array_values($photo_mappings);
    $unused_photos = array_diff($photo_files, $used_photos);
    if (!empty($unused_photos)) {
        echo "\n=== Unused Photo Files (" . count($unused_photos) . " files) ===\n";
        foreach ($unused_photos as $photo) {
            echo "  • $photo\n";
        }
    }
    
    echo "\n=== Recommendations ===\n";
    if (count($ready_to_attach) > 0) {
        echo "• " . count($ready_to_attach) . " candidates are ready for photo attachment\n";
        echo "  Run: wp eval-file direct-photo-attach-complete.php\n";
    }
    if (count($missing_mappings) > 0) {
        echo "• " . count($missing_mappings) . " candidates need photo mappings\n";
    }
    if (count($missing_photos) > 0) {
        echo "• " . count($missing_photos) . " photo files are referenced but missing\n";
    }
    
    return $stats;
}

// Run verification
if (defined('ABSPATH')) {
    mt_verify_photo_matching();
} else {
    echo "This script must be run within WordPress context.\n";
}