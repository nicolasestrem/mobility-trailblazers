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
            scoreStep: 0.5
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
            $table.on('input', '.mt-eval-score-input', function() {
                const $input = $(this);
                const $row = $input.closest('tr');
                const rowId = $row.data('candidate-id');

                // Clear existing timer for this row
                if (autoSaveTimers.has(rowId)) {
                    clearTimeout(autoSaveTimers.get(rowId));
                }

                // Set new timer
                const timerId = setTimeout(() => {
                    if ($row.hasClass('unsaved')) {
                        $row.find('.mt-btn-save-eval').click();
                    }
                    autoSaveTimers.delete(rowId);
                }, CONFIG.autoSaveDelay);

                autoSaveTimers.set(rowId, timerId);
            });
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
            // Add batch save button
            const $batchSaveBtn = $('<button class="mt-btn-batch-save" style="display:none;">' +
                '<span class="dashicons dashicons-saved"></span> ' +
                (mt_ajax.i18n.save_all_changes || 'Save All Changes') +
                '</button>');
            
            $table.before($batchSaveBtn);

            // Show/hide based on unsaved changes
            $table.on('input', '.mt-eval-score-input', function() {
                if ($table.find('.mt-eval-row.unsaved').length > 0) {
                    $batchSaveBtn.show();
                } else {
                    $batchSaveBtn.hide();
                }
            });

            // Handle batch save
            $batchSaveBtn.on('click', function() {
                const $unsavedRows = $table.find('.mt-eval-row.unsaved');
                let savedCount = 0;
                const totalRows = $unsavedRows.length;

                $batchSaveBtn.prop('disabled', true).html(
                    '<span class="mt-eval-spinner"></span> ' +
                    (mt_ajax.i18n.saving_progress || 'Saving...') + ' (0/' + totalRows + ')'
                );

                $unsavedRows.each(function() {
                    const $row = $(this);
                    $row.find('.mt-btn-save-eval').click();
                    
                    // Update progress
                    setTimeout(() => {
                        savedCount++;
                        $batchSaveBtn.html(
                            '<span class="mt-eval-spinner"></span> ' +
                            (mt_ajax.i18n.saving_progress || 'Saving...') + 
                            ' (' + savedCount + '/' + totalRows + ')'
                        );

                        if (savedCount === totalRows) {
                            $batchSaveBtn.prop('disabled', false).hide().html(
                                '<span class="dashicons dashicons-saved"></span> ' +
                                (mt_ajax.i18n.save_all_changes || 'Save All Changes')
                            );
                        }
                    }, 200 * savedCount);
                });
            });
        }

        /**
         * Export functionality
         */
        function initExportFeature() {
            const $exportBtn = $('<button class="mt-btn-export-rankings">' +
                '<span class="dashicons dashicons-download"></span> ' +
                (mt_ajax.i18n.export_rankings || 'Export Rankings') +
                '</button>');
            
            $('.mt-rankings-header').append($exportBtn);

            $exportBtn.on('click', function() {
                const csv = generateCSV();
                downloadCSV(csv, 'jury-rankings-' + new Date().toISOString().split('T')[0] + '.csv');
            });
        }

        /**
         * Generate CSV from table data
         */
        function generateCSV() {
            const rows = [];
            
            // Header
            const headers = [];
            $table.find('thead th').each(function() {
                headers.push($(this).text().trim());
            });
            rows.push(headers.join(','));

            // Data
            $table.find('tbody tr').each(function() {
                const row = [];
                $(this).find('td').each(function(index) {
                    const $td = $(this);
                    if ($td.find('.mt-eval-score-input').length) {
                        row.push($td.find('.mt-eval-score-input').val());
                    } else if ($td.find('.mt-candidate-name').length) {
                        row.push('"' + $td.find('.mt-candidate-name').text().trim() + '"');
                    } else if ($td.find('.mt-eval-total-value').length) {
                        row.push($td.find('.mt-eval-total-value').text().trim());
                    } else if ($td.find('.position-number').length) {
                        row.push($td.find('.position-number').text().trim());
                    } else {
                        row.push('');
                    }
                });
                rows.push(row.join(','));
            });

            return rows.join('\n');
        }

        /**
         * Download CSV file
         */
        function downloadCSV(csv, filename) {
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

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
            
            const avg = count > 0 ? (total / count) : 0;
            const $totalValue = $row.find('.mt-eval-total-value');
            const oldValue = parseFloat($totalValue.text());
            
            // Animate if value changed
            if (oldValue !== avg) {
                $totalValue.fadeOut(100, function() {
                    $(this).text(avg.toFixed(1)).fadeIn(100);
                });
            }

            // Update color coding
            const $totalCell = $row.find('.mt-eval-total-score');
            $totalCell.removeClass('score-high score-low score-medium');
            if (avg >= 8) {
                $totalCell.addClass('score-high');
            } else if (avg >= 5) {
                $totalCell.addClass('score-medium');
            } else if (avg <= 3) {
                $totalCell.addClass('score-low');
            }
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
