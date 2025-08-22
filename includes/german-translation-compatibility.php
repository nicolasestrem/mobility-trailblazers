<?php
/**
 * German Translation Compatibility Layer
 * 
 * Purpose: Ensure German translations work properly until .mo file is updated
 * This file provides fallback translations for critical strings
 * 
 * @package MobilityTrailblazers
 * @since 2.5.37
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Provide fallback translations for critical strings
 * This ensures German translations work even if .mo file is not updated
 */
add_filter('gettext', function($translated, $text, $domain) {
    // Only apply to our plugin domain
    if ($domain !== 'mobility-trailblazers') {
        return $translated;
    }
    
    // Only apply when locale is German
    $locale = get_locale();
    if ($locale !== 'de_DE' && $locale !== 'de_DE_formal') {
        return $translated;
    }
    
    // Critical German translations fallback
    // These will be used if the .mo file hasn't been updated
    $fallback_translations = [
        // Rankings page - only if not already translated
        'Average Score:' => 'Durchschnittliche Bewertung:',
        'criteria evaluated' => 'Kriterien bewertet',
        
        // Make sure critical evaluation criteria are translated
        'Evaluation Criteria' => 'Bewertungskriterien',
        'Description' => 'Beschreibung',
        
        // Button and status translations (reported issue)
        'View/Edit Evaluation' => 'Ansehen',
        'Full View' => 'Details anzeigen',
        'Completed' => 'Abgeschlossen',
        'Start Evaluation' => 'Bewertung starten',
        'Submit Evaluation' => 'Bewertung abschicken',
        'Save Draft' => 'Entwurf speichern',
        'In Progress' => 'In Bearbeitung',
        'Draft' => 'Entwurf',
        'Pending' => 'Ausstehend',
        'Not Started' => 'Nicht begonnen',
        
        // Dashboard translations
        'Total Assigned' => 'Gesamt zugewiesen',
        'TOTAL ASSIGNED' => 'GESAMT ZUGEWIESEN',
        'COMPLETED' => 'ABGESCHLOSSEN',
        'PENDING' => 'AUSSTEHEND',
        'Your Rankings' => 'Ihre Rangliste',
        'YOUR RANKINGS' => 'IHRE RANGLISTE',
        'Search candidates...' => 'Kandidaten suchen...',
        'Back to Dashboard' => 'Zurück zum Dashboard',
        'LinkedIn Profile' => 'LinkedIn-Profil',
        'Website' => 'Webseite',
        'Evaluate Candidate' => 'Kandidat bewerten',
        'EVALUATE CANDIDATE' => 'KANDIDAT BEWERTEN',
        'EVALUATION SUBMITTED' => 'BEWERTUNG EINGEREICHT',
        
        // Table headers
        'Rank' => 'Rang',
        'Candidate' => 'Kandidat',
        'Total' => 'Gesamt',
        'Actions' => 'Aktionen',
        'Save' => 'Speichern',
        
        // Additional strings
        'Real-time ranking based on evaluation scores' => 'Echtzeit-Rangliste basierend auf Bewertungspunkten',
        'Thank you for submitting your evaluation!' => 'Vielen Dank für die Einreichung Ihrer Bewertung!',
    ];
    
    // Apply fallback translation only if current translation is the same as original
    // (meaning it wasn't translated by the .mo file)
    if ($translated === $text && isset($fallback_translations[$text])) {
        return $fallback_translations[$text];
    }
    
    return $translated;
}, 10, 3);

/**
 * Debug helper to verify translations are working
 * Only active for administrators in debug mode
 */
if (defined('WP_DEBUG') && WP_DEBUG) {
    add_action('wp_footer', function() {
        // Check user capability inside the hook when WordPress is fully loaded
        if (!current_user_can('administrator')) {
            return;
        }
        
        if (strpos($_SERVER['REQUEST_URI'], 'evaluate') !== false || 
            strpos($_SERVER['REQUEST_URI'], 'jury') !== false) {
            ?>
            <script>
            console.log('MT Translation Check:', {
                locale: '<?php echo get_locale(); ?>',
                domain: 'mobility-trailblazers',
                criteriaDescriptions: document.querySelectorAll('.mt-criterion-description').length,
                visible: Array.from(document.querySelectorAll('.mt-criterion-description')).filter(el => {
                    const style = window.getComputedStyle(el);
                    return style.display !== 'none' && style.visibility !== 'hidden';
                }).length
            });
            </script>
            <?php
        }
    });
}
