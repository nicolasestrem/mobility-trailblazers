/**
 * Elementor Compatibility JavaScript
 */
(function($) {
    'use strict';
    
    // Ensure elementorFrontend is available
    $(window).on('load', function() {
        // Check if elementorFrontend exists
        if (typeof elementorFrontend === 'undefined') {
            console.log('MT Plugin: Elementor frontend not detected, running in standalone mode');
            return;
        }
        
        // Hook into Elementor frontend init
        elementorFrontend.hooks.addAction('frontend/element_ready/mt_jury_dashboard.default', function($scope) {
            // Initialize any dashboard-specific JavaScript here
            console.log('MT Jury Dashboard widget initialized');
        });
        
        elementorFrontend.hooks.addAction('frontend/element_ready/mt_candidate_grid.default', function($scope) {
            // Initialize candidate grid JavaScript
            console.log('MT Candidate Grid widget initialized');
        });
        
        elementorFrontend.hooks.addAction('frontend/element_ready/mt_evaluation_stats.default', function($scope) {
            // Initialize stats widget JavaScript
            console.log('MT Evaluation Stats widget initialized');
        });
    });
    
})(jQuery);