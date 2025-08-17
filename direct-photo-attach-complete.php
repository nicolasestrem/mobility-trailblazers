<?php
/**
 * Complete direct photo attachment script including Günther Schuh
 * This version includes all 52 candidates with proper ID mappings
 */

// Function to directly attach photos to all candidates
function mt_complete_photo_attachment() {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $upload_dir = wp_upload_dir();
    $plugin_photos_dir = WP_PLUGIN_DIR . '/mobility-trailblazers/Photos_candidates/webp/';
    
    // Complete mapping of all candidate IDs to photo files
    $direct_mappings = [
        4377 => 'AlexanderMöller.webp',          // Alexander Möller
        4378 => 'AndréSchwämmlein.webp',         // André Schwämmlein
        4379 => 'AnjesTjarks.webp',              // Anjes Tjarks
        4380 => 'Anna-TheresaKorbutt.webp',      // Anna-Theresa Korbutt
        4381 => 'BenediktMiddendorf.webp',       // Benedikt Middendorf
        4382 => 'BjörnBender.webp',              // Björn Bender
        4383 => 'BorisPalmer.webp',              // Boris Palmer
        4384 => 'CatrinVonCisewski.webp',        // Catrin von Cisewski
        4385 => 'ChristineVonBreitenbuch.webp',  // Christine von Breitenbuch
        4386 => 'ChristophSeyerlein.webp',       // Christoph Seyerlein
        4387 => 'ChristophWeigler.webp',         // Christoph Weigler
        4388 => 'ChristianDahlheim.webp',        // Dr. Christian Dahlheim
        4389 => 'CorsinSulser.webp',             // Dr. Corsin Sulser
        4390 => 'JanHegner.webp',                // Dr. Jan Hegner
        4391 => 'FabianBeste.webp',              // Fabian Beste
        4392 => 'FelixPörnbacher.webp',          // Felix Pörnbacher
        4393 => 'FranzReiner.webp',              // Franz Reiner
        4394 => 'FriedrichDräxlmaier.webp',      // Friedrich Dräxlmaier
        4395 => 'HelmutRuhl.webp',               // Helmut Ruhl
        4396 => 'HildegardMüller.webp',          // Hildegard Müller
        4397 => 'HorstGraef.webp',               // Horst Graef
        4398 => 'JohannesPallasch.webp',         // Johannes Pallasch
        4399 => 'JudithHäberli.webp',            // Judith Häberli
        4400 => 'KarelDijkman.webp',             // Karel Dijkman
        4401 => 'KatharinaKreutzer.webp',        // Katharina Kreutzer
        4402 => 'KevinLöffelbein.webp',          // Kevin Löffelbein
        4403 => 'KlausZellmer.webp',             // Klaus Zellmer
        4404 => 'LéaMiggiano.webp',              // Léa Miggiano
        4405 => 'LukasStranger.webp',            // Lukas Stranger
        4406 => 'ManuelHerzog.webp',             // Manuel Herzog
        4407 => 'MarcSchindler.webp',            // Marc Schindler
        4408 => 'MatthiasBallweg.webp',          // Matthias Ballweg
        4409 => 'MichaelKlasa.webp',             // Michael Klasa
        4410 => 'NicKnapp.webp',                 // Nic Knapp
        4411 => 'OlgaNevska.webp',               // Olga Nevska
        4412 => 'OliverBlume.webp',              // Oliver Blume
        4413 => 'OliverMay-Beckmann.webp',       // Oliver May-Beckmann
        4414 => 'OliverZipse.webp',              // Oliver Zipse
        4415 => 'UweSchneidewind.webp',          // Prof. Dr. Uwe Schneidewind
        4416 => 'RönkeVonDerHeide.webp',         // Rönke von der Heide
        4417 => 'RoyUhlmann.webp',               // Roy Uhlmann
        4418 => 'SarahFleischer.webp',           // Sarah Fleischer
        4419 => 'SaschaMeyer.webp',              // Sascha Meyer
        4420 => 'SebastianTanzer.webp',          // Sebastian Tanzer
        4421 => 'StephanObwegeser.webp',         // Stephan Obwegeser
        4422 => 'TobiasLiebelt.webp',            // Tobias Liebelt
        4423 => 'WenHan.webp',                   // Wen Han
        4424 => 'WimOuboter.webp',               // Wim Ouboter
        4425 => 'WolframUerlich.webp',           // Wolfram Uerlich
        4426 => 'XanthiDoubara.webp',            // Xanthi Doubara
        4444 => 'GüntherSchuh.webp',             // GüntherSchuh (special case)
    ];
    
    $success = 0;
    $failed = 0;
    $skipped = 0;
    $errors = [];
    
    echo "=== Complete Photo Attachment Process ===\n";
    echo "Starting at: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Process each mapping
    foreach ($direct_mappings as $candidate_id => $photo_filename) {
        // Get candidate details
        $candidate = get_post($candidate_id);
        if (!$candidate) {
            echo "✗ Candidate ID $candidate_id not found\n";
            $errors[] = "Candidate ID $candidate_id not found";
            $failed++;
            continue;
        }
        
        echo "Processing: {$candidate->post_title} (ID: $candidate_id)\n";
        
        // Check if already has thumbnail
        if (has_post_thumbnail($candidate_id)) {
            echo "  → Already has featured image, skipping\n";
            $skipped++;
            continue;
        }
        
        $source_path = $plugin_photos_dir . $photo_filename;
        
        // Check if photo file exists
        if (!file_exists($source_path)) {
            echo "  ✗ Photo not found: $photo_filename\n";
            $errors[] = "Photo not found for {$candidate->post_title}: $photo_filename";
            $failed++;
            continue;
        }
        
        // Generate unique filename to avoid conflicts
        $filename = 'mt_candidate_' . $candidate_id . '_' . $photo_filename;
        $target_path = $upload_dir['path'] . '/' . $filename;
        
        // Copy file to uploads directory
        if (!copy($source_path, $target_path)) {
            echo "  ✗ Failed to copy file to uploads\n";
            $errors[] = "Failed to copy file for {$candidate->post_title}";
            $failed++;
            continue;
        }
        
        // Create attachment post
        $attachment = array(
            'post_mime_type' => 'image/webp',
            'post_title'     => $candidate->post_title . ' - Photo',
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_author'    => get_current_user_id()
        );
        
        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $target_path, $candidate_id);
        
        if (is_wp_error($attach_id)) {
            echo "  ✗ Failed to create attachment: " . $attach_id->get_error_message() . "\n";
            $errors[] = "Attachment creation failed for {$candidate->post_title}";
            unlink($target_path); // Clean up copied file
            $failed++;
            continue;
        }
        
        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        // Set as featured image
        $result = set_post_thumbnail($candidate_id, $attach_id);
        
        if ($result) {
            echo "  ✓ Successfully attached (Attachment ID: $attach_id)\n";
            
            // Add alt text for accessibility
            update_post_meta($attach_id, '_wp_attachment_image_alt', $candidate->post_title);
            
            $success++;
        } else {
            echo "  ✗ Failed to set as featured image\n";
            $errors[] = "Failed to set featured image for {$candidate->post_title}";
            $failed++;
        }
    }
    
    // Final summary
    echo "\n=== Attachment Summary ===\n";
    echo "Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "Total candidates processed: " . count($direct_mappings) . "\n";
    echo "✓ Successfully attached: $success\n";
    echo "→ Skipped (already had image): $skipped\n";
    echo "✗ Failed: $failed\n";
    
    // Show errors if any
    if (!empty($errors)) {
        echo "\n=== Errors Encountered ===\n";
        foreach ($errors as $error) {
            echo "  • $error\n";
        }
    }
    
    // Success message
    if ($success > 0) {
        echo "\n✓ Photo attachment completed successfully!\n";
        echo "  $success new photos were attached to candidates.\n";
    }
    
    // Recommendations
    if ($failed > 0) {
        echo "\n⚠ Recommendations:\n";
        echo "  • Check that all photo files exist in: Photos_candidates/webp/\n";
        echo "  • Verify WordPress upload directory permissions\n";
        echo "  • Check WordPress Media Library settings\n";
    }
    
    return [
        'success' => $success,
        'failed' => $failed,
        'skipped' => $skipped,
        'errors' => $errors
    ];
}

// Run the attachment process
if (defined('ABSPATH')) {
    // Check user capabilities
    if (!current_user_can('upload_files') || !current_user_can('edit_posts')) {
        wp_die('You need upload_files and edit_posts capabilities to run this script.');
    }
    
    // Run the complete attachment
    $results = mt_complete_photo_attachment();
    
    // Log results
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('MT Photo Attachment Results: ' . json_encode($results));
    }
} else {
    echo "This script must be run within WordPress context.\n";
    echo "Use: wp eval-file direct-photo-attach-complete.php\n";
}