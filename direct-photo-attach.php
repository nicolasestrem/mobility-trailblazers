<?php
/**
 * Direct photo attachment script using WordPress Media Library
 * Handles special characters properly
 */

// Function to directly attach photos
function mt_direct_attach_photos() {
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $upload_dir = wp_upload_dir();
    $plugin_photos_dir = WP_PLUGIN_DIR . '/mobility-trailblazers/Photos_candidates/webp/';
    
    // Direct mapping of candidate IDs to photo files
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
    ];
    
    $success = 0;
    $failed = 0;
    $skipped = 0;
    
    echo "=== Starting Direct Photo Attachment ===\n\n";
    
    foreach ($direct_mappings as $candidate_id => $photo_filename) {
        // Get candidate name
        $candidate = get_post($candidate_id);
        if (!$candidate) {
            echo "✗ Candidate ID $candidate_id not found\n";
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
        
        // Check if file exists
        if (!file_exists($source_path)) {
            echo "  ✗ Photo not found: $photo_filename\n";
            $failed++;
            continue;
        }
        
        // Generate unique filename for upload
        $filename = time() . '_' . $photo_filename;
        $target_path = $upload_dir['path'] . '/' . $filename;
        
        // Copy file to uploads
        if (!copy($source_path, $target_path)) {
            echo "  ✗ Failed to copy file\n";
            $failed++;
            continue;
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => 'image/webp',
            'post_title'     => $candidate->post_title,
            'post_content'   => '',
            'post_status'    => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $target_path, $candidate_id);
        
        if (is_wp_error($attach_id)) {
            echo "  ✗ Failed to create attachment\n";
            unlink($target_path); // Clean up
            $failed++;
            continue;
        }
        
        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        // Set as featured image
        set_post_thumbnail($candidate_id, $attach_id);
        
        echo "  ✓ Successfully attached (Attachment ID: $attach_id)\n";
        $success++;
    }
    
    echo "\n=== Summary ===\n";
    echo "Successful: $success\n";
    echo "Failed: $failed\n";
    echo "Skipped (already had image): $skipped\n";
    echo "Total processed: " . count($direct_mappings) . "\n";
}

// Run the function
mt_direct_attach_photos();
