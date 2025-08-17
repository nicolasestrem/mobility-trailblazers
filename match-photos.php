<?php
/**
 * Script to match candidate photos with database entries
 * Place this in your plugin folder and run once to update all candidates
 */

// Photo mapping based on the CSV and webp files
$photo_mappings = [
    'Alexander Möller' => 'AlexanderMöller.webp',
    'André Schwämmlein' => 'AndréSchwämmlein.webp',
    'Anjes Tjarks' => 'AnjesTjarks.webp',
    'Anna-Theresa Korbutt' => 'Anna-TheresaKorbutt.webp',
    'Benedikt Middendorf' => 'BenediktMiddendorf.webp',
    'Björn Bender' => 'BjörnBender.webp',
    'Boris Palmer' => 'BorisPalmer.webp',
    'Catrin von Cisewski' => 'CatrinVonCisewski.webp',
    'Dr. Christian Dahlheim' => 'ChristianDahlheim.webp',
    'Christine von Breitenbuch' => 'ChristineVonBreitenbuch.webp',
    'Christoph Seyerlein' => 'ChristophSeyerlein.webp',
    'Christoph Weigler' => 'ChristophWeigler.webp',
    'Dr. Corsin Sulser' => 'CorsinSulser.webp',
    'Dr. Jan Hegner' => 'JanHegner.webp',
    'Fabian Beste' => 'FabianBeste.webp',
    'Felix Pörnbacher' => 'FelixPörnbacher.webp',
    'Franz Reiner' => 'FranzReiner.webp',
    'Friedrich Dräxlmaier' => 'FriedrichDräxlmaier.webp',
    'Günther Schuh' => 'GüntherSchuh.webp',
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
    'Xanthi Doubara' => 'XanthiDoubara.webp'
];

// Function to upload and attach photo to candidate
function mt_attach_candidate_photo($candidate_id, $photo_filename) {
    $upload_dir = wp_upload_dir();
    $source_path = plugin_dir_path(__FILE__) . 'Photos_candidates/webp/' . $photo_filename;
    
    // Check if source file exists
    if (!file_exists($source_path)) {
        error_log("Photo file not found: " . $source_path);
        return false;
    }
    
    // Check if already has thumbnail
    if (has_post_thumbnail($candidate_id)) {
        error_log("Candidate $candidate_id already has thumbnail");
        return true;
    }
    
    // Copy file to uploads directory
    $target_path = $upload_dir['path'] . '/' . $photo_filename;
    if (!copy($source_path, $target_path)) {
        error_log("Failed to copy photo: " . $photo_filename);
        return false;
    }
    
    // Create attachment
    $attachment = array(
        'post_mime_type' => 'image/webp',
        'post_title' => preg_replace('/\.[^.]+$/', '', $photo_filename),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    
    $attach_id = wp_insert_attachment($attachment, $target_path, $candidate_id);
    
    // Generate metadata
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $target_path);
    wp_update_attachment_metadata($attach_id, $attach_data);
    
    // Set as featured image
    set_post_thumbnail($candidate_id, $attach_id);
    
    return true;
}

// Main matching function
function mt_match_all_candidate_photos() {
    global $photo_mappings;
    
    $candidates = get_posts([
        'post_type' => 'mt_candidate',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ]);
    
    $matched = 0;
    $failed = 0;
    
    foreach ($candidates as $candidate) {
        $title = $candidate->post_title;
        
        // Try exact match first
        if (isset($photo_mappings[$title])) {
            if (mt_attach_candidate_photo($candidate->ID, $photo_mappings[$title])) {
                $matched++;
                echo "✓ Matched: $title -> " . $photo_mappings[$title] . "\n";
            } else {
                $failed++;
                echo "✗ Failed: $title\n";
            }
            continue;
        }
        
        // Try partial match
        $found = false;
        foreach ($photo_mappings as $name => $photo) {
            if (stripos($title, $name) !== false || stripos($name, $title) !== false) {
                if (mt_attach_candidate_photo($candidate->ID, $photo)) {
                    $matched++;
                    echo "✓ Matched (partial): $title -> $photo\n";
                    $found = true;
                    break;
                } else {
                    $failed++;
                    echo "✗ Failed (partial): $title\n";
                    $found = true;
                    break;
                }
            }
        }
        
        if (!$found) {
            echo "? No match found for: $title\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "Total candidates: " . count($candidates) . "\n";
    echo "Successfully matched: $matched\n";
    echo "Failed: $failed\n";
    echo "Unmatched: " . (count($candidates) - $matched - $failed) . "\n";
}

// Run the matching
if (defined('WP_CLI')) {
    mt_match_all_candidate_photos();
} else {
    // Can be run via admin panel
    add_action('admin_init', function() {
        if (isset($_GET['match_candidate_photos']) && current_user_can('manage_options')) {
            mt_match_all_candidate_photos();
            wp_die('Photo matching complete. Check the logs for details.');
        }
    });
}
?>