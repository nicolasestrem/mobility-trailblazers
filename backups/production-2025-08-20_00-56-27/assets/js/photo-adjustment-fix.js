/**
 * Photo Adjustment Fix for Issue #13
 * Specifically fixes Friedrich Dräxlmaier's photo in grid view
 * Created: 2025-08-19
 */
(function($) {
    'use strict';
    function fixFriedrichPhoto() {
        // Find Friedrich's candidate card
        var $friedrichCard = $('[data-candidate-id="4627"]');
        if ($friedrichCard.length > 0) {
            // Find the image within the card
            var $img = $friedrichCard.find('img.mt-candidate-photo, .mt-candidate-image img');
            if ($img.length > 0) {
                // Apply inline styles directly
                $img.css({
                    'object-position': 'center 15%',
                    'object-fit': 'cover',
                    'width': '100%',
                    'height': '100%'
                });
                // Also fix the container
                var $container = $friedrichCard.find('.mt-candidate-image');
                if ($container.length > 0) {
                    $container.css({
                        'overflow': 'hidden',
                        'position': 'relative'
                    });
                }
            }
        }
    }
    // Run on document ready
    $(document).ready(function() {
        fixFriedrichPhoto();
    });
    // Run after window load (in case images load late)
    $(window).on('load', function() {
        fixFriedrichPhoto();
    });
    // Run after AJAX requests (in case content is loaded dynamically)
    $(document).ajaxComplete(function() {
        setTimeout(fixFriedrichPhoto, 100);
    });
    // Also run periodically for Elementor dynamic content
    setInterval(function() {
        if ($('[data-candidate-id="4627"] img:not([data-fixed])').length > 0) {
            fixFriedrichPhoto();
            $('[data-candidate-id="4627"] img').attr('data-fixed', 'true');
        }
    }, 1000);
})(jQuery);
