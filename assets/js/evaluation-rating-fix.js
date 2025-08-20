/**
 * Evaluation Rating Fix
 * Purpose: Fix the issue where only one rating can be selected across all categories
 * Issue #21: Evaluation page does not allow more than one rating across all categories
 * Created: 2025-08-18
 * Updated: 2025-08-19 - Added proper button group handling
 * Updated: 2025-08-20 - Fixed event handler conflicts with proper namespacing (.mt-evaluation)
 */
(function($) {
    'use strict';
    // Wait for DOM to be fully loaded
    $(document).ready(function() {
        // CRITICAL FIX: Handle button groups independently for each criterion
        function initializeButtonGroups() {
            // Remove ONLY our namespaced handlers to prevent conflicts with other plugins
            $('.mt-score-button').off('.mt-evaluation');
            $(document).off('click.mt-evaluation', '.mt-score-button');
            // Handle each button group independently
            $('.mt-button-group').each(function() {
                var $group = $(this);
                var criterionKey = $group.data('criterion');
                // CRITICAL FIX: Hidden input is INSIDE the group, not a sibling!
                var $hiddenInput = $group.find('input[type="hidden"]');
                // Handle button clicks within this specific group with namespaced events
                $group.find('.mt-score-button').on('click.mt-evaluation', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation(); // Prevent any other handlers
                    var $button = $(this);
                    var value = $button.data('value');
                    // Remove active AND selected classes from all buttons IN THIS GROUP ONLY
                    $group.find('.mt-score-button').removeClass('active selected');
                    // Add both active AND selected classes to clicked button
                    $button.addClass('active selected');
                    // Update the hidden input for this criterion
                    $hiddenInput.val(value);
                    // Update the score display for this criterion
                    var $card = $group.closest('.mt-criterion-card');
                    $card.find('.mt-score-value').text(value);
                    // Update overall score
                    updateOverallScore();
                    // Also update using the original MTJuryDashboard function if it exists
                    if (typeof MTJuryDashboard !== 'undefined' && MTJuryDashboard.updateTotalScore) {
                        MTJuryDashboard.updateTotalScore();
                    }
                });
            });
        }
        // Fix 1: Ensure each slider operates independently
        function initializeIndependentSliders() {
            // Remove ONLY our namespaced event handlers to prevent conflicts with other plugins
            $('.mt-score-slider').off('.mt-evaluation');
            $(document).off('input.mt-evaluation change.mt-evaluation', '.mt-score-slider');
            // Initialize each slider independently
            $('.mt-score-slider').each(function() {
                var $slider = $(this);
                var sliderName = $slider.attr('name');
                // Ensure unique name attribute
                if (!sliderName) {
                    // Error logging removed for production
                    return;
                }
                // Remove any conflicting event handlers with our namespace
                $slider.off('change.mt-evaluation input.mt-evaluation');
                // Add independent event handler with namespace
                $slider.on('input.mt-evaluation change.mt-evaluation', function(e) {
                    e.stopPropagation(); // Prevent event bubbling
                    var value = $(this).val();
                    var $card = $(this).closest('.mt-criterion-card');
                    // Update only this slider's display
                    $card.find('.mt-score-value').text(value);
                    // Update visual feedback for this slider only
                    var percentage = (value / 10) * 100;
                    $(this).css('background', 'linear-gradient(to right, #667eea 0%, #667eea ' + percentage + '%, #e5e7eb ' + percentage + '%, #e5e7eb 100%)');
                    // Log for debugging
                    // Update total score
                    updateOverallScore();
                });
                // Initialize visual state
                var initialValue = $slider.val();
                var percentage = (initialValue / 10) * 100;
                $slider.css('background', 'linear-gradient(to right, #667eea 0%, #667eea ' + percentage + '%, #e5e7eb ' + percentage + '%, #e5e7eb 100%)');
            });
        }
        // Fix 2: Ensure score marks work independently
        function fixScoreMarks() {
            $('.mt-score-mark').off('click.mt-evaluation');
            $('.mt-score-mark').on('click.mt-evaluation', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var value = $(this).data('value');
                var $card = $(this).closest('.mt-criterion-card');
                var $slider = $card.find('.mt-score-slider');
                // Set only this slider's value
                $slider.val(value).trigger('input');
                // Visual feedback
                $(this).addClass('selected').siblings().removeClass('selected');
            });
        }
        // Fix 3: Update total score calculation (works with both sliders and buttons)
        function updateOverallScore() {
            var total = 0;
            var count = 0;
            // Check for sliders first
            $('.mt-score-slider').each(function() {
                var value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    total += value;
                    count++;
                }
            });
            // If no sliders, check for button groups (hidden inputs)
            if (count === 0) {
                $('.mt-button-group').each(function() {
                    // FIX: Hidden input is INSIDE the group
                    var $hiddenInput = $(this).find('input[type="hidden"]');
                    var value = parseFloat($hiddenInput.val());
                    if (!isNaN(value)) {
                        total += value;
                        count++;
                    }
                });
            }
            if (count > 0) {
                var average = (total / count).toFixed(1);
                // Update all possible total score displays
                $('#mt-total-score').text(average);
                $('.mt-total-score-value').text(average);
                $('.mt-average-score').text(average + '/10');
                // Update evaluation status
                $('.mt-evaluated-count').text('(' + count + '/5 criteria evaluated)');
            }
        }
        // Fix 4: Prevent form submission if ratings are missing
        function validateEvaluation() {
            $('#mt-evaluation-form').off('submit.mt-evaluation').on('submit.mt-evaluation', function(e) {
                var allRated = true;
                var unratedCriteria = [];
                $('.mt-score-slider').each(function() {
                    var value = parseFloat($(this).val());
                    var name = $(this).attr('name');
                    if (isNaN(value) || value === 0) {
                        allRated = false;
                        unratedCriteria.push(name.replace('_score', ''));
                    }
                });
                if (!allRated && unratedCriteria.length === 5) {
                    e.preventDefault();
                    alert('Please rate at least one criterion before submitting.');
                    return false;
                }
                // Log submission data for debugging
                $('.mt-score-slider').each(function() {
                    console.log($(this).attr('name') + ': ' + $(this).val());
                });
            });
        }
        // Fix 5: Ensure sliders work after AJAX loads
        function reinitializeAfterAjax() {
            $(document).off('ajaxComplete.mt-evaluation').on('ajaxComplete.mt-evaluation', function(event, xhr, settings) {
                if (settings.url && (settings.url.includes('evaluate') || settings.url.includes('mt_get_candidate'))) {
                    setTimeout(function() {
                        initializeIndependentSliders();
                        fixScoreMarks();
                        updateOverallScore();
                    }, 100);
                }
            });
        }
        // Fix 6: Add visual debugging
        function addDebugInfo() {
            if (window.location.search.includes('debug=1')) {
                var debugHtml = '<div id="evaluation-debug" style="position:fixed;bottom:10px;right:10px;background:#fff;border:2px solid #000;padding:10px;z-index:9999;">';
                debugHtml += '<h4>Evaluation Debug</h4>';
                debugHtml += '<div id="debug-values"></div>';
                debugHtml += '</div>';
                $('body').append(debugHtml);
                setInterval(function() {
                    var debugInfo = '';
                    $('.mt-score-slider').each(function() {
                        debugInfo += $(this).attr('name') + ': ' + $(this).val() + '<br>';
                    });
                    $('#debug-values').html(debugInfo);
                }, 1000);
            }
        }
        // Initialize all fixes
        function initializeAllFixes() {
            // Wait a moment for any other scripts to load
            setTimeout(function() {
                // Remove only our namespaced handlers first to prevent conflicts with other plugins
                $('.mt-score-slider').off('.mt-evaluation');
                $(document).off('input.mt-evaluation change.mt-evaluation', '.mt-score-slider');
                $('.mt-score-button').off('.mt-evaluation');
                $(document).off('click.mt-evaluation', '.mt-score-button');
                $('.mt-score-mark').off('.mt-evaluation');
                // Then initialize our fixed handlers
                // CRITICAL: Initialize button groups if they exist
                if ($('.mt-button-group').length > 0) {
                    initializeButtonGroups();
                }
                // Initialize sliders if they exist
                if ($('.mt-score-slider').length > 0) {
                    initializeIndependentSliders();
                }
                fixScoreMarks();
                updateOverallScore();
                validateEvaluation();
                reinitializeAfterAjax();
                addDebugInfo();
            }, 750); // Increased delay to ensure other scripts have loaded
        }
        // Run initialization with higher priority
        initializeAllFixes();
        // Also run on window load as fallback with namespace
        $(window).off('load.mt-evaluation').on('load.mt-evaluation', function() {
            if ($('.mt-score-slider').length > 0) {
                initializeAllFixes();
            }
        });
        // Expose functions globally for debugging
        window.MTEvaluationFix = {
            reinitialize: initializeAllFixes,
            checkValues: function() {
                $('.mt-score-slider').each(function() {
                    console.log($(this).attr('name') + ': ' + $(this).val());
                });
            }
        };
    });
})(jQuery);
