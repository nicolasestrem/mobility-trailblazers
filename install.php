<?php
/**
 * Installation script for Mobility Trailblazers
 */

// Create default terms
function mt_create_default_terms() {
    // Categories
    $categories = array(
        'etablierte-unternehmen' => 'Etablierte Unternehmen',
        'startups-neue-gestalter' => 'Start-ups & Neue Gestalter',
        'infrastruktur-politik-oeffentliche' => 'Infrastruktur/Politik/Öffentliche'
    );
    
    foreach ($categories as $slug => $name) {
        if (!term_exists($slug, 'mt_category')) {
            wp_insert_term($name, 'mt_category', array('slug' => $slug));
        }
    }
    
    // Status terms
    $statuses = array(
        'nominated' => 'Nominiert',
        'under-review' => 'In Prüfung',
        'shortlisted' => 'Shortlist',
        'finalist' => 'Finalist',
        'winner' => 'Gewinner',
        'not-selected' => 'Nicht ausgewählt'
    );
    
    foreach ($statuses as $slug => $name) {
        if (!term_exists($slug, 'mt_status')) {
            wp_insert_term($name, 'mt_status', array('slug' => $slug));
        }
    }
    
    // Award years
    $current_year = date('Y');
    if (!term_exists($current_year, 'mt_award_year')) {
        wp_insert_term($current_year, 'mt_award_year');
    }
}

// Run installation
mt_create_default_terms();

// Set default options
update_option('mt_current_award_year', date('Y'));
update_option('mt_evaluation_phase', 'active');
update_option('mt_public_voting_enabled', '0');

echo "Mobility Trailblazers installation completed!\n";