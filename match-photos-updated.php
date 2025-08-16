<?php
/**
 * Enhanced script to match candidate photos with database entries
 * This version includes all 51 candidates with proper name matching
 */

// Complete photo mapping based on actual files and database entries
$photo_mappings = [
    // Exact matches
    'Alexander Möller' => 'AlexanderMöller.webp',
    'André Schwämmlein' => 'AndréSchwämmlein.webp',
    'Anjes Tjarks' => 'AnjesTjarks.webp',
    'Anna-Theresa Korbutt' => 'Anna-TheresaKorbutt.webp',
    'Benedikt Middendorf' => 'BenediktMiddendorf.webp',
    'Björn Bender' => 'BjörnBender.webp',
    'Boris Palmer' => 'BorisPalmer.webp',
    'Catrin von Cisewski' => 'CatrinVonCisewski.webp',
    'Christine von Breitenbuch' => 'ChristineVonBreitenbuch.webp',
    'Christoph Seyerlein' => 'ChristophSeyerlein.webp',
    'Christoph Weigler' => 'ChristophWeigler.webp',
    'Dr. Christian Dahlheim' => 'ChristianDahlheim.webp',
    'Dr. Corsin Sulser' => 'CorsinSulser.webp',
    'Dr. Jan Hegner' => 'JanHegner.webp',
    'Fabian Beste' => 'FabianBeste.webp',
    'Felix Pörnbacher' => 'FelixPörnbacher.webp',
    'Franz Reiner' => 'FranzReiner.webp',
    'Friedrich Dräxlmaier' => 'FriedrichDräxlmaier.webp',
    'Helmut Ruhl' => 'HelmutRuhl.webp',
    'Hildegard Müller' => 'HildegardMüller.webp',
    'Horst Graef' => 'HorstGraef.webp',
    'Johannes Pallasch' => 'JohannesPallasch.webp',
    'Judith Häberli' => 'JudithHäberli.webp',
    'Karel Dijkman' => 'KarelDijkman.webp',
    'Katharina Kreutzer' => 'KatharinaKreutzer.webp',
    'Kevin Löffelbein' => 'KevinLöffelbein.webp',
    'Klaus Zellmer' => 'KlausZellmer.webp',
    'Léa Miggiano' => 'LéaMiggiano.webp',
    'Lukas Stranger' => 'LukasStranger.webp',
    'Manuel Herzog' => 'ManuelHerzog.webp',
    'Marc Schindler' => 'MarcSchindler.webp',
    'Matthias Ballweg' => 'MatthiasBallweg.webp',
    'Michael Klasa' => 'MichaelKlasa.webp',
    'Nic Knapp' => 'NicKnapp.webp',
    'Olga Nevska' => 'OlgaNevska.webp',
    'Oliver Blume' => 'OliverBlume.webp',
    'Oliver May-Beckmann' => 'OliverMay-Beckmann.webp',
    'Oliver Zipse' => 'OliverZipse.webp',
    'Prof. Dr. Uwe Schneidewind' => 'UweSchneidewind.webp',
    'Rönke von der Heide' => 'RönkeVonDerHeide.webp',
    'Roy Uhlmann' => 'RoyUhlmann.webp',
    'Sarah Fleischer' => 'SarahFleischer.webp',
    'Sascha Meyer' => 'SaschaMeyer.webp',
    'Sebastian Tanzer' => 'SebastianTanzer.webp',
    'Stephan Obwegeser' => 'StephanObwegeser.webp',
    'Tobias Liebelt' => 'TobiasLiebelt.webp',
    'Wen Han' => 'WenHan.webp',
    'Wim Ouboter' => 'WimOuboter.webp',
    'Wolfram Uerlich' => 'WolframUerlich.webp',
    'Xanthi Doubara' => 'XanthiDoubara.webp',
    
    // Missing from photos but in CSV (Günther Schuh)
    'Günther Schuh' => 'GüntherSchuh.webp',
];

// Function to upload and attach photo to candidate
function mt_attach_candidate_photo($candidate_id, $photo_filename) {
    $upload_dir = wp_upload_dir();
    $plugin_dir = WP_PLUGIN_DIR . '/mobility-trailblazers/';
    $source_path = $plugin_dir . 'Photos_candidates/webp/' . $photo_filename;
    
    echo "Processing: Candidate ID $candidate_id with photo $photo_filename\n";
    
    // Check if source file exists
    if (!file_exists($source_path)) {
        echo "  ✗ Photo file not found: " . $source_path . "\n";
        return false;
    }
    
    // Check if already has thumbnail
    if (has_post_thumbnail($candidate_id)) {
        echo "  ℹ Candidate $candidate_id already has thumbnail\n";
        return true;
    }
    
    // Prepare filename for upload
    $filename = sanitize_file_name($photo_filename);
    $target_path = $upload_dir['path'] . '/' . $filename;
    
    // Copy file to uploads directory
    if (!copy($source_path, $target_path)) {
        echo "  ✗ Failed to copy photo: " . $photo_filename . "\n";
        return false;
    }
    
    // Check the filetype
    $filetype = wp_check_filetype($filename, null);
    
    // Create attachment
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title' => preg_replace('/\.[^.]+$/', '', $filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    // Insert the attachment
    $attach_id = wp_insert_attachment($attachment, $target_path, $candidate_id);
    
    if (is_wp_error($attach_id)) {
        echo "  ✗ Failed to create attachment: " . $attach_id->get_error_message() . "\n";
        return false;
    }
    
    // Generate metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    // Set as featured image
    set_post_thumbnail($candidate_id, $attach_id);
    
    echo "  ✓ Successfully attached photo (Attachment ID: $attach_id)\n";
    return true;
}

// Main matching function
function mt_match_all_candidate_photos() {
    global $photo_mappings;
    
    echo "=== Starting Photo Matching Process ===\n\n";
    
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    echo "Found " . count($candidates) . " candidates\n\n";
    
    $matched = 0;
    $already_has = 0;
    $failed = 0;
    $not_found = 0;
    
    foreach ($candidates as $candidate) {
        $title = $candidate->post_title;
        echo "\n--- Processing: $title (ID: {$candidate->ID}) ---\n";
        
        // Check if already has thumbnail
        if (has_post_thumbnail($candidate->ID)) {
            echo "  ℹ Already has featured image, skipping\n";
            $already_has++;
            continue;
        }
        
        // Try exact match first
        if (isset($photo_mappings[$title])) {
            if (mt_attach_candidate_photo($candidate->ID, $photo_mappings[$title])) {
                $matched++;
            } else {
                $failed++;
            }
            continue;
        }
        
        // Try to find a match by cleaning the title
        $found = false;
        foreach ($photo_mappings as $name => $photo) {
            // Remove "Dr." and "Prof. Dr." for comparison
            $clean_title = preg_replace('/^(Dr\.|Prof\. Dr\.) /', '', $title);
            $clean_name = preg_replace('/^(Dr\.|Prof\. Dr\.) /', '', $name);
            
            if (strcasecmp($clean_title, $clean_name) === 0) {
                if (mt_attach_candidate_photo($candidate->ID, $photo)) {
                    $matched++;
                } else {
                    $failed++;
                }
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "  ? No matching photo found for: $title\n";
            $not_found++;
        }
    }
    
    echo "\n\n=== Summary ===\n";
    echo "Total candidates: " . count($candidates) . "\n";
    echo "Successfully matched: $matched\n";
    echo "Already had images: $already_has\n";
    echo "Failed to attach: $failed\n";
    echo "No photo found: $not_found\n";
    
    return [
        'total' => count($candidates),
        'matched' => $matched,
        'already_has' => $already_has,
        'failed' => $failed,
        'not_found' => $not_found
    ];
}

// Check if running in WP context
if (defined('ABSPATH')) {
    // Run the matching
    $results = mt_match_all_candidate_photos();
    
    // If running via WP-CLI or direct execution
    if (defined('WP_CLI') && WP_CLI) {
        if ($results['matched'] > 0) {
            WP_CLI::success("Photo matching complete. {$results['matched']} photos attached.");
        } else {
            WP_CLI::warning("No new photos were attached.");
        }
    }
} else {
    echo "This script must be run within WordPress context.\n";
}
