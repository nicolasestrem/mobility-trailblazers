/**
 * Mobility Trailblazers Frontend JavaScript
 * Version: 1.0.0
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        MobilityTrailblazers.init();
    });

    // Main frontend object
    window.MobilityTrailblazers = {
        
        /**
         * Initialize frontend functionality
         */
        init: function() {
            this.initVoting();
            this.initCandidateDisplay();
        },

        /**
         * Initialize voting interface
         */
        initVoting: function() {
            $('.mt-vote-button').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $container = $button.closest('.mt-voting-interface');
                
                // Remove selected class from siblings
                $container.find('.mt-vote-button').removeClass('selected');
                
                // Add selected class to clicked button
                $button.addClass('selected');
                
                // Trigger custom event
                $(document).trigger('mt-vote-selected', {
                    button: $button,
                    value: $button.data('value')
                });
            });
        },

        /**
         * Initialize candidate display functionality
         */
        initCandidateDisplay: function() {
            // Add any candidate display initialization here
            $('.mt-candidate-display').each(function() {
                var $display = $(this);
                // Initialize candidate display features
            });
        },

        /**
         * Utility function for AJAX requests
         */
        ajaxRequest: function(action, data, callback) {
            if (typeof mtFrontend === 'undefined') {
                console.error('Frontend localization not available');
                return;
            }

            var requestData = {
                action: action,
                nonce: mtFrontend.nonce
            };

            // Merge additional data
            if (data) {
                $.extend(requestData, data);
            }

            $.ajax({
                url: mtFrontend.ajax_url,
                type: 'POST',
                data: requestData,
                success: function(response) {
                    if (callback && typeof callback === 'function') {
                        callback(response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', error);
                    if (callback && typeof callback === 'function') {
                        callback({success: false, error: error});
                    }
                }
            });
        }
    };

})(jQuery); 