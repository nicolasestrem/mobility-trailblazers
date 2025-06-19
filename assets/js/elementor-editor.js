/**
 * Elementor Editor Integration
 *
 * @package MobilityTrailblazers
 */

(function($) {
    'use strict';

    // Initialize when Elementor editor is ready
    $(window).on('elementor/init', function() {
        // Add custom panel categories
        elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
            // Add custom controls or behaviors for specific widgets
            if (model.attributes.widgetType === 'mt-candidates-grid') {
                // Add custom controls for candidates grid
                panel.$el.find('.elementor-control-type-select2').on('change', function() {
                    // Handle select2 changes
                });
            }
        });

        // Add custom controls to the panel
        elementor.hooks.addAction('panel/open_editor/widget', function(panel, model, view) {
            // Add custom controls for all MT widgets
            if (model.attributes.widgetType.startsWith('mt-')) {
                // Add custom behaviors
                panel.$el.find('.elementor-control-type-select2').on('change', function() {
                    // Handle select2 changes
                });
            }
        });

        // Handle widget rendering in editor
        elementor.hooks.addAction('frontend/element_ready/mt-candidates-grid.default', function($scope) {
            // Initialize widget in editor
            initCandidatesGrid($scope);
        });

        elementor.hooks.addAction('frontend/element_ready/mt_jury_dashboard.default', function($scope) {
            // Initialize widget in editor
            initJuryDashboard($scope);
        });

        elementor.hooks.addAction('frontend/element_ready/mt-voting-form.default', function($scope) {
            // Initialize widget in editor
            initVotingForm($scope);
        });
    });

    // Initialize Candidates Grid in editor
    function initCandidatesGrid($scope) {
        var $grid = $scope.find('.mt-candidates-grid');
        
        if ($grid.length) {
            // Initialize masonry if enabled
            var settings = $grid.data('settings');
            
            if (settings && settings.enable_masonry) {
                $grid.masonry({
                    itemSelector: '.mt-candidate-card',
                    columnWidth: '.grid-sizer',
                    percentPosition: true,
                    gutter: settings.grid_gap || 20
                });
            }
        }
    }

    // Initialize Jury Dashboard in editor
    function initJuryDashboard($scope) {
        var $dashboard = $scope.find('.mt_jury_dashboard');
        
        if ($dashboard.length) {
            // Initialize dashboard functionality
            if (typeof MTJuryDashboard !== 'undefined') {
                MTJuryDashboard.init();
            }
        }
    }

    // Initialize Voting Form in editor
    function initVotingForm($scope) {
        var $form = $scope.find('.mt-voting-form');
        
        if ($form.length) {
            // Initialize form functionality
            if (typeof MTFrontend !== 'undefined') {
                MTFrontend.initializeVoting();
            }
        }
    }

})(jQuery); 