/**
 * Candidate Import Handler
 * 
 * Handles CSV file selection and AJAX upload for candidate imports
 * 
 * @package MobilityTrailblazers
 * @since 2.2.15
 */
jQuery(document).ready(function($) {
    'use strict';
    // Check if mt_ajax is available
    if (typeof mt_ajax === 'undefined') {
        // MT Import: mt_ajax object not found. Script localization may have failed.
    }
    // Handle import button click - use event delegation since button is added dynamically
    $(document).on('click', '#mt-import-candidates', function(e) {
        e.preventDefault();
        // Double-check mt_ajax exists
        if (typeof mt_ajax === 'undefined') {
            alert('Import functionality is not properly initialized. Please refresh the page and try again.');
            return;
        }
        // Create a temporary file input
        var fileInput = $('<input>', {
            type: 'file',
            accept: '.csv,text/csv,application/csv',
            style: 'display: none'
        });
        // Append to body temporarily
        $('body').append(fileInput);
        // Handle file selection
        fileInput.on('change', function(e) {
            var file = this.files[0];
            // Validate file selection
            if (!file) {
                // Clean up
                fileInput.remove();
                return;
            }
            // Validate file type
            var fileName = file.name.toLowerCase();
            if (!fileName.endsWith('.csv')) {
                var fileExt = fileName.split('.').pop();
                if (fileExt === 'xlsx' || fileExt === 'xls') {
                    alert('Please convert your Excel file to CSV format first. Use "Save As" in Excel and choose "CSV (Comma delimited)" as the file type.');
                } else {
                    alert(mt_ajax.i18n.invalid_file_type || 'Please select a CSV file.');
                }
                fileInput.remove();
                return;
            }
            // Validate file size (10MB max)
            var maxSize = 10 * 1024 * 1024; // 10MB in bytes
            if (file.size > maxSize) {
                alert(mt_ajax.i18n.file_too_large || 'File is too large. Maximum size is 10MB.');
                fileInput.remove();
                return;
            }
            // Prepare form data
            var formData = new FormData();
            formData.append('action', 'mt_import_candidates');
            formData.append('csv_file', file);
            formData.append('nonce', mt_ajax.nonce);
            // Optional: Add import options if they exist on the page
            if ($('#update_existing').length) {
                formData.append('update_existing', $('#update_existing').is(':checked') ? '1' : '0');
            }
            if ($('#skip_duplicates').length) {
                formData.append('skip_duplicates', $('#skip_duplicates').is(':checked') ? '1' : '0');
            }
            // Show loading state
            var $button = $('#mt-import-candidates');
            var originalText = $button.text();
            $button.prop('disabled', true)
                   .text(mt_ajax.i18n.importing || 'Importing...');
            // Show loading overlay if it exists
            if ($('.mt-import-overlay').length) {
                $('.mt-import-overlay').show();
            } else {
                // Create a simple loading overlay
                var $overlay = $('<div>', {
                    class: 'mt-import-overlay',
                    style: 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;'
                }).html('<div style="background: white; padding: 20px; border-radius: 5px;"><p>' + (mt_ajax.i18n.importing || 'Importing...') + '</p><div class="spinner is-active" style="float: none;"></div></div>');
                $('body').append($overlay);
            }
            // Debug: Request details logged silently
            // Send AJAX request
            $.ajax({
                url: mt_ajax.ajax_url || ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Hide loading overlay
                    $('.mt-import-overlay').remove();
                    // Restore button
                    $button.prop('disabled', false).text(originalText);
                    if (response.success) {
                        // Build success message
                        var message = mt_ajax.i18n.import_complete || 'Import complete!';
                        if (response.data) {
                            var stats = [];
                            if (response.data.imported > 0) {
                                stats.push(response.data.imported + ' ' + (mt_ajax.i18n.created || 'created'));
                            }
                            if (response.data.updated > 0) {
                                stats.push(response.data.updated + ' ' + (mt_ajax.i18n.updated || 'updated'));
                            }
                            if (response.data.skipped > 0) {
                                stats.push(response.data.skipped + ' ' + (mt_ajax.i18n.skipped || 'skipped'));
                            }
                            if (response.data.errors > 0) {
                                stats.push(response.data.errors + ' ' + (mt_ajax.i18n.errors || 'errors'));
                            }
                            if (stats.length > 0) {
                                message += '\n\n' + stats.join(', ');
                            }
                            // Add error details if present
                            if (response.data.error_details && response.data.error_details.length > 0) {
                                message += '\n\n' + (mt_ajax.i18n.error_details || 'Error details:');
                                response.data.error_details.slice(0, 5).forEach(function(error) {
                                    message += '\n- Row ' + error.row + ' (' + error.name + '): ' + error.error;
                                });
                                if (response.data.error_details.length > 5) {
                                    message += '\n... and ' + (response.data.error_details.length - 5) + ' more errors';
                                }
                            }
                        }
                        // Show success message
                        alert(message);
                        // Reload page to show imported candidates
                        window.location.reload();
                    } else {
                        // Hide loading overlay
                        $('.mt-import-overlay').remove();
                        // Restore button
                        $button.prop('disabled', false).text(originalText);
                        // Show error message
                        var errorMessage = mt_ajax.i18n.import_failed || 'Import failed!';
                        if (response.data && response.data.message) {
                            errorMessage += '\n\n' + response.data.message;
                        }
                        alert(errorMessage);
                    }
                    // Clean up file input
                    fileInput.remove();
                },
                error: function(xhr, status, error) {
                    // Hide loading overlay
                    $('.mt-import-overlay').remove();
                    // Restore button
                    $button.prop('disabled', false).text(originalText);
                    // Build error message
                    var errorMessage = mt_ajax.i18n.import_error || 'An error occurred during import.';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMessage += '\n\n' + xhr.responseJSON.data.message;
                    } else if (xhr.responseText) {
                        // Try to extract error from response text
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.data && response.data.message) {
                                errorMessage += '\n\n' + response.data.message;
                            }
                        } catch(e) {
                            errorMessage += '\n\nStatus: ' + status + '\nError: ' + error;
                        }
                    } else {
                        errorMessage += '\n\nStatus: ' + status + '\nError: ' + error;
                    }
                    alert(errorMessage);
                    // Clean up file input
                    fileInput.remove();
                }
            });
        });
        // Trigger file selection dialog
        fileInput.trigger('click');
    });
    // Alternative: Handle drag and drop if container exists
    var $dropZone = $('#mt-import-drop-zone');
    if ($dropZone.length) {
        // Prevent default drag behaviors
        $dropZone.on('dragenter dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('drag-over');
        });
        $dropZone.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
        });
        // Handle file drop
        $dropZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('drag-over');
            var files = e.originalEvent.dataTransfer.files;
            if (files.length > 0) {
                var file = files[0];
                // Validate file type
                var fileName = file.name.toLowerCase();
                if (!fileName.endsWith('.csv')) {
                    var fileExt = fileName.split('.').pop();
                    if (fileExt === 'xlsx' || fileExt === 'xls') {
                        alert('Please convert your Excel file to CSV format first. Use "Save As" in Excel and choose "CSV (Comma delimited)" as the file type.');
                    } else {
                        alert(mt_ajax.i18n.invalid_file_type || 'Please select a CSV file.');
                    }
                    return;
                }
                // Trigger the import process
                processImportFile(file);
            }
        });
    }
    /**
     * Process import file (shared function for both methods)
     */
    function processImportFile(file) {
        // This function could be extracted if needed for reuse
        // For now, it's defined inline within the drag-drop handler
    }
    // Add visual feedback for file input hover (optional enhancement)
    $(document).on('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
    $(document).on('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
    });
});
