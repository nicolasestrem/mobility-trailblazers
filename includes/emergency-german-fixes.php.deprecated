<?php
/**
 * Emergency German Language and Display Fixes
 * Date: 2025-08-19
 * Purpose: Force German locale and fix display issues
 * 
 * This file contains emergency fixes for:
 * 1. Evaluation criteria descriptions not displaying
 * 2. Rankings text still in English
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use MobilityTrailblazers\Core\MT_Logger;

/**
 * Force German locale for the entire plugin
 */
add_filter('locale', function($locale) {
    // Only apply to frontend and admin pages related to Mobility Trailblazers
    if (is_admin()) {
        // Check if get_current_screen() is available (not available during AJAX or early init)
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && strpos($screen->id, 'mt_') !== false) {
                return 'de_DE';
            }
        } else {
            // Fallback: Check URL for MT admin pages
            if (isset($_SERVER['REQUEST_URI']) && 
                (strpos($_SERVER['REQUEST_URI'], 'mt_') !== false ||
                 strpos($_SERVER['REQUEST_URI'], 'mobility-trailblazers') !== false)) {
                return 'de_DE';
            }
        }
    } else {
        // Check if we're on a Mobility Trailblazers page
        if (is_singular('mt_candidate') || 
            is_post_type_archive('mt_candidate') || 
            strpos($_SERVER['REQUEST_URI'], 'jury') !== false ||
            strpos($_SERVER['REQUEST_URI'], 'ranking') !== false) {
            return 'de_DE';
        }
    }
    
    return $locale;
}, 1);

/**
 * Load emergency CSS fixes - Now consolidated into mt-hotfixes-consolidated.css
 * BACKUP: Uncomment if consolidated approach fails
 */
/*
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style(
        'mt-emergency-fixes',
        MT_PLUGIN_URL . 'assets/css/emergency-fixes.css',
        [],
        '2025.08.19.1',
        'all'
    );
}, 999);

add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style(
        'mt-emergency-fixes-admin',
        MT_PLUGIN_URL . 'assets/css/emergency-fixes.css',
        [],
        '2025.08.19.1',
        'all'
    );
}, 999);
*/

/**
 * Emergency text override for German translations
 */
add_filter('gettext', function($translated, $text, $domain) {
    if ($domain !== 'mobility-trailblazers') {
        return $translated;
    }
    
    // Critical German translations that must work
    $emergency_translations = [
        // Rankings page
        'Top Ranked Candidates' => 'Rangliste der bewerteten Kandidaten',
        'Real-time ranking based on evaluation scores' => 'Sie können die Werte direkt in der Rangliste ändern.',
        'Rank' => 'Rang',
        'Candidate' => 'Kandidat/in',
        'Actions' => 'Änderungen',
        'Full View' => 'Details anzeigen',
        'Average Score' => 'Durchschnittliche Bewertung',
        'Average Score:' => 'Durchschnittliche Bewertung:',
        'criteria evaluated' => 'Kriterien bewertet',
        
        // Evaluation criteria - CRITICAL
        'Evaluation Criteria' => 'Bewertungskriterien',
        'Mut & Pioniergeist' => 'Mut & Pioniergeist',
        'Mut, Konventionen herauszufordern und neue Wege in der Mobilität zu beschreiten' => 'Mut, Konventionen herauszufordern und neue Wege in der Mobilität zu beschreiten',
        'Innovationsgrad' => 'Innovationsgrad',
        'Grad an Innovation und Kreativität bei der Lösung von Mobilitätsherausforderungen' => 'Grad an Innovation und Kreativität bei der Lösung von Mobilitätsherausforderungen',
        'Umsetzungskraft & Wirkung' => 'Umsetzungskraft & Wirkung',
        'Fähigkeit zur Umsetzung und realer Einfluss der Initiativen' => 'Fähigkeit zur Umsetzung und realer Einfluss der Initiativen',
        'Relevanz für die Mobilitätswende' => 'Relevanz für die Mobilitätswende',
        'Bedeutung und Beitrag zur Transformation der Mobilität' => 'Bedeutung und Beitrag zur Transformation der Mobilität',
        'Vorbildfunktion & Sichtbarkeit' => 'Vorbildfunktion & Sichtbarkeit',
        'Rolle als Vorbild und öffentliche Wahrnehmbarkeit im Mobilitätssektor' => 'Rolle als Vorbild und öffentliche Wahrnehmbarkeit im Mobilitätssektor',
        
        // Evaluation form
        'Evaluate Candidate' => 'Kandidat bewerten',
        'Back to Dashboard' => 'Zurück zum Dashboard',
        'Submit Evaluation' => 'Bewertung abschließen',
        'Evaluation Guidelines' => 'Bewertungsrichtlinien',
        'Score each criterion from 0 (lowest) to 10 (highest) based on your assessment' => 'Bewerten Sie jedes Kriterium von 0 (niedrigste) bis 10 (höchste) basierend auf Ihrer Einschätzung',
        'Consider the candidate\'s overall impact on mobility transformation' => 'Berücksichtigen Sie die Gesamtwirkung des Kandidaten auf die Mobilitätstransformation',
        'Once submitted, you can still edit your evaluation if needed' => 'Nach dem Einreichen können Sie Ihre Bewertung bei Bedarf noch bearbeiten',
        'Evaluation Submitted' => 'Bewertung eingereicht',
        'Description' => 'Beschreibung',
    ];
    
    // Apply emergency translation if available
    if (isset($emergency_translations[$text])) {
        return $emergency_translations[$text];
    }
    
    return $translated;
}, 999, 3);

/**
 * Debug function to check if criteria descriptions are being loaded
 */
add_action('wp_footer', function() {
    if (current_user_can('administrator')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Check if criteria descriptions exist and log to console
            var descriptions = $('.mt-criterion-description');
            if (descriptions.length > 0) {
                console.log('✅ MT Debug: Found ' + descriptions.length + ' criteria descriptions');
                descriptions.each(function(index) {
                    var text = $(this).text().trim();
                    var isVisible = $(this).is(':visible');
                    console.log('Description ' + (index + 1) + ': ' + 
                               (text.length > 0 ? '✅ Has text' : '❌ No text') + 
                               ' | ' + 
                               (isVisible ? '✅ Visible' : '❌ Hidden'));
                });
            } else {
                console.log('❌ MT Debug: No criteria descriptions found on page');
            }
        });
        </script>
        <?php
    }
});

/**
 * Add inline styles as absolute fallback
 */
add_action('wp_head', function() {
    ?>
    <style id="mt-emergency-inline-styles">
    /* Emergency inline styles - highest priority */
    .mt-criterion-description {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
        font-size: 14px !important;
        color: #6c757d !important;
        margin-top: 5px !important;
        line-height: 1.5 !important;
    }
    
    /* Ensure German language attribute is set */
    html:not([lang]) {
        lang: de-DE;
    }
    </style>
    <?php
});

/**
 * Log activation of emergency fixes
 */
add_action('init', function() {
    if (current_user_can('administrator')) {
        MT_Logger::info('Emergency German language and display fixes activated');
    }
});
