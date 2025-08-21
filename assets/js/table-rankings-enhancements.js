/**
 * Mobility Trailblazers - Table Rankings Enhancements
 * Version: 1.0.0
 * 
 * This file contains enhancements for the table-based rankings system
 * to improve UX and add missing features.
 */
(function($) {
    'use strict';
    $(document).ready(function() {
        // Only run if the evaluation table exists
        var $table = $('.mt-evaluation-table');
        if (!$table.length) return;
        // Configuration
        const CONFIG = {
            autoSaveDelay: 2000, // Auto-save after 2 seconds of inactivity
            minScore: 0,
            maxScore: 10,
            scoreStep: 0.5  // Changed from 0.1 to 0.5 for consistency
        };
        // Track unsaved changes per row
        const unsavedRows = new Map();
        let autoSaveTimers = new Map();
        /**
         * Enhanced score validation with step support
         */
        function validateScore(value) {
            // Round to nearest step
            value = Math.round(value / CONFIG.scoreStep) * CONFIG.scoreStep;
            // Clamp to min/max
            return Math.max(CONFIG.minScore, Math.min(CONFIG.maxScore, value));
        }
        /**
         * Add keyboard navigation support
         */
        function initKeyboardNavigation() {
            $table.on('keydown', '.mt-eval-score-input', function(e) {
                const $input = $(this);
                const $td = $input.parent();
                const $tr = $td.parent();
                let $target;
                switch(e.key) {
                    case 'ArrowUp':
                        e.preventDefault();
                        // Move to same column in previous row
                        $target = $tr.prev().find('td').eq($td.index()).find('.mt-eval-score-input');
                        if ($target.length) $target.focus().select();
                        break;
                    case 'ArrowDown':
                        e.preventDefault();
                        // Move to same column in next row
                        $target = $tr.next().find('td').eq($td.index()).find('.mt-eval-score-input');
                        if ($target.length) $target.focus().select();
                        break;
                    case 'ArrowLeft':
                    case 'Tab':
                        if (e.shiftKey || e.key === 'ArrowLeft') {
                            e.preventDefault();
                            // Move to previous score input
                            $target = $td.prev().find('.mt-eval-score-input');
                            if ($target.length) $target.focus().select();
                        }
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        // Move to next score input
                        $target = $td.next().find('.mt-eval-score-input');
                        if ($target.length) $target.focus().select();
                        break;
                    case 'Enter':
                        e.preventDefault();
                        // Save the current row
                        $tr.find('.mt-btn-save-eval').click();
                        break;
                    case 'Escape':
                        // Revert changes
                        revertRowChanges($tr);
                        $input.blur();
                        break;
                }
                // Quick score adjustment with +/- keys
                if (e.key === '+' || e.key === '=') {
                    e.preventDefault();
                    let newVal = validateScore(parseFloat($input.val()) + CONFIG.scoreStep);
                    $input.val(newVal).trigger('input');
                } else if (e.key === '-' || e.key === '_') {
                    e.preventDefault();
                    let newVal = validateScore(parseFloat($input.val()) - CONFIG.scoreStep);
                    $input.val(newVal).trigger('input');
                }
            });
        }
        /**
         * Store original values when focusing an input
         */
        function storeOriginalValues($row) {
            if (!unsavedRows.has($row[0])) {
                const originalValues = {};
                $row.find('.mt-eval-score-input').each(function() {
                    const $input = $(this);
                    originalValues[$input.attr('name')] = $input.val();
                });
                unsavedRows.set($row[0], originalValues);
            }
        }
        /**
         * Revert row changes to original values
         */
        function revertRowChanges($row) {
            const originalValues = unsavedRows.get($row[0]);
            if (originalValues) {
                Object.keys(originalValues).forEach(name => {
                    $row.find(`input[name="${name}"]`).val(originalValues[name]);
                });
                updateRowTotal($row);
                $row.removeClass('unsaved');
                $row.find('.mt-btn-save-eval').removeClass('unsaved');
                unsavedRows.delete($row[0]);
            }
        }
        /**
         * Auto-save functionality
         */
        function initAutoSave() {
            // Auto-save is now handled in the main input event handler
            // This function is kept for potential future enhancements
        }
        /**
         * Visual feedback for score changes
         */
        function animateScoreChange($input, oldValue, newValue) {
            const diff = newValue - oldValue;
            const $indicator = $('<span class="mt-score-change-indicator"></span>');
            if (diff > 0) {
                $indicator.text('+' + diff.toFixed(1)).addClass('positive');
            } else if (diff < 0) {
                $indicator.text(diff.toFixed(1)).addClass('negative');
            } else {
                return; // No change
            }
            // Position and animate
            const offset = $input.offset();
            $indicator.css({
                position: 'absolute',
                left: offset.left + $input.outerWidth() - 20,
                top: offset.top - 10,
                zIndex: 1000
            }).appendTo('body');
            // Animate up and fade
            $indicator.animate({
                top: offset.top - 30,
                opacity: 0
            }, 800, function() {
                $(this).remove();
            });
        }
        /**
         * Add hover tooltips for criteria headers
         */
        function initTooltips() {
            $table.find('th[title]').each(function() {
                const $th = $(this);
                const title = $th.attr('title');
                $th.hover(
                    function() {
                        const $tooltip = $('<div class="mt-enhanced-tooltip"></div>')
                            .text(title)
                            .appendTo('body');
                        const offset = $th.offset();
                        $tooltip.css({
                            top: offset.top - $tooltip.outerHeight() - 10,
                            left: offset.left + ($th.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                        }).fadeIn(200);
                    },
                    function() {
                        $('.mt-enhanced-tooltip').fadeOut(200, function() {
                            $(this).remove();
                        });
                    }
                );
            });
        }
        /**
         * Batch save functionality
         */
        function initBatchSave() {
            // Removed batch save button - not needed
        }
        /**
         * Export functionality
         */
        function initExportFeature() {
            // Removed export button - not needed
        }
        // Removed CSV generation function - not needed
        // Removed CSV download function - not needed
        /**
         * Update row total with animation
         */
        function updateRowTotal($row) {
            let total = 0;
            let count = 0;
            $row.find('.mt-eval-score-input').each(function() {
                const val = parseFloat($(this).val());
                if (!isNaN(val)) {
                    total += val;
                    count++;
                }
            });
            // Calculate AVERAGE for display and ranking (consistent with database)
            const avgScore = count > 0 ? (total / count) : 0;
            const $totalValue = $row.find('.mt-eval-total-value');
            const oldValue = parseFloat($totalValue.text());
            // Animate if value changed
            if (oldValue !== avgScore) {
                $totalValue.fadeOut(100, function() {
                    $(this).text(avgScore.toFixed(1)).fadeIn(100);
                });
            }
            // Update color coding based on average
            const $totalCell = $row.find('.mt-eval-total-score');
            $totalCell.removeClass('score-high score-low score-medium');
            if (avgScore >= 8) {
                $totalCell.addClass('score-high');
            } else if (avgScore >= 5) {
                $totalCell.addClass('score-medium');
            } else if (avgScore <= 3) {
                $totalCell.addClass('score-low');
            }
            
            // Store the average score in data attribute for sorting
            $row.data('total-score', avgScore);
        }
        
        /**
         * Re-rank table rows based on total scores
         */
        function rerankTable() {
            const $tbody = $table.find('tbody');
            const $rows = $tbody.find('tr.mt-eval-row').get();
            
            // Sort rows by total score (descending)
            $rows.sort(function(a, b) {
                const scoreA = parseFloat($(a).data('total-score') || $(a).find('.mt-eval-total-value').text()) || 0;
                const scoreB = parseFloat($(b).data('total-score') || $(b).find('.mt-eval-total-value').text()) || 0;
                return scoreB - scoreA; // Descending order
            });
            
            // Store current positions before reordering
            const currentPositions = {};
            $rows.forEach((row, index) => {
                const $row = $(row);
                const candidateId = $row.data('candidate-id');
                const $badge = $row.find('.mt-ranking-badge');
                currentPositions[candidateId] = parseInt($badge.data('position') || (index + 1));
            });
            
            // Update ranks and reorder rows
            $.each($rows, function(index, row) {
                const $row = $(row);
                const newPosition = index + 1;
                const candidateId = $row.data('candidate-id');
                const oldPosition = currentPositions[candidateId];
                const $rankCell = $row.find('.mt-eval-rank');
                const $badge = $rankCell.find('.mt-ranking-badge');
                
                // Only update if position changed
                if (oldPosition !== newPosition) {
                    // Update the badge's data-position attribute
                    $badge.attr('data-position', newPosition);
                    
                    // Update rank number in span.mt-rank-number
                    const $rankNumber = $badge.find('.mt-rank-number');
                    if ($rankNumber.length) {
                        $rankNumber.fadeOut(200, function() {
                            $(this).text(newPosition).fadeIn(200);
                        });
                    }
                    
                    // Update rank number in SVG text element
                    const $svgText = $badge.find('svg text');
                    if ($svgText.length) {
                        $svgText.fadeOut(200, function() {
                            $(this).text(newPosition).fadeIn(200);
                        });
                    }
                    
                    // Update badge classes (remove all rank classes first)
                    $badge.removeClass('mt-rank-gold mt-rank-silver mt-rank-bronze mt-rank-standard');
                    
                    // Add new rank class based on position
                    if (newPosition === 1) {
                        $badge.addClass('mt-rank-gold');
                    } else if (newPosition === 2) {
                        $badge.addClass('mt-rank-silver');
                    } else if (newPosition === 3) {
                        $badge.addClass('mt-rank-bronze');
                    } else {
                        $badge.addClass('mt-rank-standard');
                    }
                    
                    // Update medal icon color class if it exists
                    const $medalIcon = $badge.find('.mt-medal-icon');
                    if ($medalIcon.length) {
                        $medalIcon.removeClass('mt-medal-gold mt-medal-silver mt-medal-bronze');
                        if (newPosition === 1) {
                            $medalIcon.addClass('mt-medal-gold');
                        } else if (newPosition === 2) {
                            $medalIcon.addClass('mt-medal-silver');
                        } else if (newPosition === 3) {
                            $medalIcon.addClass('mt-medal-bronze');
                        }
                    }
                }
                
                // Update row classes
                $row.removeClass('position-gold position-silver position-bronze');
                // Remove all rank-N classes
                for (let i = 1; i <= $rows.length; i++) {
                    $row.removeClass('rank-' + i);
                }
                
                // Add new position classes
                if (newPosition === 1) {
                    $row.addClass('position-gold');
                } else if (newPosition === 2) {
                    $row.addClass('position-silver');
                } else if (newPosition === 3) {
                    $row.addClass('position-bronze');
                }
                $row.addClass('rank-' + newPosition);
            });
            
            // Reorder rows in the DOM with smooth animation
            const rowHeight = $rows.length > 0 ? $($rows[0]).outerHeight() : 50;
            
            // Calculate target positions
            const targetPositions = {};
            $rows.forEach((row, index) => {
                const $row = $(row);
                targetPositions[$row.data('candidate-id')] = index * rowHeight;
            });
            
            // Animate row movements
            $rows.forEach((row) => {
                const $row = $(row);
                const candidateId = $row.data('candidate-id');
                const currentTop = $row.position().top;
                const targetTop = targetPositions[candidateId];
                
                if (Math.abs(currentTop - targetTop) > 5) {
                    // Row needs to move
                    $row.css({
                        position: 'relative',
                        top: 0,
                        zIndex: 100
                    }).animate({
                        top: targetTop - currentTop
                    }, 400, function() {
                        // Reset position after animation
                        $(this).css({
                            position: '',
                            top: '',
                            zIndex: ''
                        });
                    });
                }
            });
            
            // After animation completes, physically reorder the DOM
            setTimeout(function() {
                $.each($rows, function(index, row) {
                    $tbody.append(row);
                });
            }, 450);
        }
        /**
         * Initialize all enhancements
         */
        function init() {
            initKeyboardNavigation();
            initAutoSave();
            initTooltips();
            initBatchSave();
            initExportFeature();
            // Track original values on focus
            $table.on('focus', '.mt-eval-score-input', function() {
                storeOriginalValues($(this).closest('tr'));
            });
            
            // Update row total and re-rank on input change
            $table.on('input', '.mt-eval-score-input', function() {
                const $input = $(this);
                const $row = $input.closest('tr');
                const rowId = $row.data('candidate-id');
                
                // Update row total
                updateRowTotal($row);
                
                // Mark row as unsaved
                $row.addClass('unsaved');
                $row.find('.mt-btn-save-eval').addClass('unsaved');
                
                // Auto-save functionality
                if (autoSaveTimers.has(rowId)) {
                    clearTimeout(autoSaveTimers.get(rowId));
                }
                const timerId = setTimeout(() => {
                    if ($row.hasClass('unsaved')) {
                        $row.find('.mt-btn-save-eval').click();
                    }
                    autoSaveTimers.delete(rowId);
                }, CONFIG.autoSaveDelay);
                autoSaveTimers.set(rowId, timerId);
                
                // Delay re-ranking to avoid too many animations
                clearTimeout(window.rerankTimer);
                window.rerankTimer = setTimeout(function() {
                    rerankTable();
                }, 500);
            });
            
            // Enhanced score change animation
            $table.on('change', '.mt-eval-score-input', function() {
                const $input = $(this);
                const newValue = parseFloat($input.val());
                const oldValue = parseFloat($input.data('oldValue') || $input.val());
                if (oldValue !== newValue) {
                    animateScoreChange($input, oldValue, newValue);
                    $input.data('oldValue', newValue);
                }
            });
            // MT Table Rankings Enhancements loaded
        }
        // Initialize
        init();
    });
})(jQuery);

