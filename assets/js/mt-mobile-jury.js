/**
 * Mobile Jury Dashboard Enhancements
 * Adds mobile-specific functionality and data attributes for responsive CSS
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        // Check if we're on mobile
        if (window.innerWidth <= 767) {
            enhanceMobileTable();
        }

        // Re-apply on window resize
        let resizeTimer;
        $(window).resize(function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth <= 767) {
                    enhanceMobileTable();
                } else {
                    removeMobileEnhancements();
                }
            }, 250);
        });
    });

    /**
     * Enhance table for mobile display
     */
    function enhanceMobileTable() {
        const $table = $('.mt-evaluation-table');
        
        if ($table.length === 0) return;

        // Add mobile class to wrapper
        $('.mt-evaluation-table-wrap').addClass('mt-mobile-view');
        
        // Get column headers for labels
        const headers = [];
        $table.find('thead th').each(function(index) {
            headers[index] = $(this).text().trim();
        });

        // Add data-label attributes to cells for mobile display
        $table.find('tbody tr').each(function() {
            const $row = $(this);
            
            // Add mobile card class
            $row.addClass('mt-mobile-card');
            
            $row.find('td').each(function(index) {
                const $cell = $(this);
                
                // Skip if already has label
                if ($cell.attr('data-label')) return;
                
                // Add label based on header
                if (headers[index]) {
                    $cell.attr('data-label', headers[index]);
                }
                
                // Special handling for score inputs
                if ($cell.find('.mt-eval-score-input').length) {
                    const $input = $cell.find('.mt-eval-score-input');
                    const criterion = $input.attr('data-criterion');
                    
                    // Add wrapper for better mobile layout
                    if (!$input.parent().hasClass('mt-mobile-score-wrapper')) {
                        $input.wrap('<div class="mt-mobile-score-wrapper"></div>');
                        $input.before('<span class="mt-mobile-score-label">' + headers[index] + '</span>');
                    }
                }
                
                // Special handling for action buttons
                if ($cell.hasClass('mt-eval-actions')) {
                    $cell.find('button, a').each(function() {
                        const $btn = $(this);
                        // Ensure buttons are full width on mobile
                        $btn.addClass('mt-mobile-btn');
                    });
                }
            });
        });

        // Create mobile summary cards if needed
        createMobileSummaryCards();
    }

    /**
     * Remove mobile enhancements when switching to desktop
     */
    function removeMobileEnhancements() {
        $('.mt-evaluation-table-wrap').removeClass('mt-mobile-view');
        $('.mt-evaluation-table tbody tr').removeClass('mt-mobile-card');
        $('.mt-mobile-score-wrapper').each(function() {
            const $input = $(this).find('.mt-eval-score-input');
            $(this).replaceWith($input);
        });
        $('.mt-mobile-score-label').remove();
        $('.mt-mobile-btn').removeClass('mt-mobile-btn');
        $('.mt-mobile-summary-card').remove();
    }

    /**
     * Create mobile-friendly summary cards
     */
    function createMobileSummaryCards() {
        // Check if summary cards already exist
        if ($('.mt-mobile-summary-card').length > 0) return;

        const $tableWrap = $('.mt-evaluation-table-wrap');
        
        // Add a mobile-friendly header if needed
        if (!$tableWrap.find('.mt-mobile-header').length) {
            const $header = $('<div class="mt-mobile-header">' +
                '<h3>' + MT_Mobile.strings.yourRankings + '</h3>' +
                '<p>' + MT_Mobile.strings.tapToEdit + '</p>' +
                '</div>');
            $tableWrap.prepend($header);
        }
    }

    // Handle touch interactions for better mobile UX
    if ('ontouchstart' in window) {
        $(document).on('touchstart', '.mt-mobile-card', function() {
            $(this).addClass('mt-touch-active');
        });

        $(document).on('touchend', '.mt-mobile-card', function() {
            $(this).removeClass('mt-touch-active');
        });
    }

    // Optimize input interactions on mobile
    $(document).on('focus', '.mt-eval-score-input', function() {
        if (window.innerWidth <= 767) {
            // Scroll input into view with some padding
            const element = this;
            setTimeout(function() {
                element.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'center' 
                });
            }, 300);
        }
    });

    // Handle save button on mobile with better feedback
    $(document).on('click', '.mt-btn-save-eval', function(e) {
        if (window.innerWidth <= 767) {
            const $btn = $(this);
            const originalText = $btn.html();
            
            // Visual feedback
            $btn.html('<span class="dashicons dashicons-update spin"></span> ' + MT_Mobile.strings.saving);
            
            // Simulate save (this should connect to your actual AJAX handler)
            setTimeout(function() {
                $btn.html('<span class="dashicons dashicons-yes"></span> ' + MT_Mobile.strings.saved);
                setTimeout(function() {
                    $btn.html(originalText);
                }, 2000);
            }, 1000);
        }
    });

})(jQuery);

// Localization object (this would normally be passed from PHP)
window.MT_Mobile = window.MT_Mobile || {
    strings: {
        yourRankings: 'Ihre Rangliste',
        tapToEdit: 'Tippen Sie zum Bearbeiten der Bewertungen',
        saving: 'Speichern...',
        saved: 'Gespeichert!'
    }
};