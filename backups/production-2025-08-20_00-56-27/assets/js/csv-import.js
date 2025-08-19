/**
 * CSV Import Handler
 * 
 * @package MobilityTrailblazers
 * @since 2.2.23
 */
(function($) {
    'use strict';
    // CSV Import Manager
    const MTCSVImport = {
        // Properties
        isImporting: false,
        progressInterval: null,
        /**
         * Initialize the CSV import functionality
         */
        init: function() {
            this.bindEvents();
            this.setupProgressModal();
        },
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Handle import form submission via AJAX
            $('#mt-import-form').on('submit', this.handleFormSubmit.bind(this));
            // Add AJAX import button to standard form if it exists
            const standardForm = $('form[action*="admin-post.php"]').filter(function() {
                return $(this).find('input[name="action"][value="mt_import_data"]').length > 0;
            });
            if (standardForm.length) {
                this.enhanceStandardForm(standardForm);
            }
            // File validation on change
            $('#csv_file').on('change', this.validateFile.bind(this));
            // Import type change handler
            $('#import_type').on('change', this.updateImportHelp.bind(this));
        },
        /**
         * Enhance standard form with AJAX capabilities
         */
        enhanceStandardForm: function(form) {
            // Add AJAX import button next to regular submit
            const submitBtn = form.find('button[type="submit"]');
            if (submitBtn.length) {
                const ajaxBtn = $('<button/>', {
                    type: 'button',
                    class: 'button button-secondary mt-ajax-import-btn',
                    html: '<span class="dashicons dashicons-upload"></span> ' + mt_csv_import.i18n.ajax_import,
                    style: 'margin-left: 10px;'
                });
                ajaxBtn.on('click', (e) => {
                    e.preventDefault();
                    this.handleAjaxImport(form);
                });
                submitBtn.after(ajaxBtn);
            }
        },
        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            e.preventDefault();
            if (this.isImporting) {
                return false;
            }
            const form = $(e.target);
            this.handleAjaxImport(form);
            return false;
        },
        /**
         * Handle AJAX import
         */
        handleAjaxImport: function(form) {
            // Get form data
            const fileInput = form.find('input[type="file"]')[0];
            const importType = form.find('select[name="import_type"]').val();
            const updateExisting = form.find('input[name="update_existing"]').is(':checked');
            // Validate
            if (!fileInput || !fileInput.files.length) {
                this.showNotice(mt_csv_import.i18n.no_file_selected, 'error');
                return;
            }
            if (!importType) {
                this.showNotice(mt_csv_import.i18n.no_type_selected, 'error');
                return;
            }
            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'mt_import_csv');
            formData.append('nonce', mt_csv_import.nonce);
            formData.append('import_type', importType);
            formData.append('update_existing', updateExisting ? 'true' : 'false');
            formData.append('csv_file', fileInput.files[0]);
            // Start import
            this.startImport(formData);
        },
        /**
         * Start the import process
         */
        startImport: function(formData) {
            this.isImporting = true;
            this.showProgressModal();
            this.updateProgress(5, mt_csv_import.i18n.uploading_file);
            // Disable form elements
            $('.mt-import-export-container input, .mt-import-export-container select, .mt-import-export-container button').prop('disabled', true);
            $.ajax({
                url: mt_csv_import.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    // Upload progress
                    xhr.upload.addEventListener('progress', (evt) => {
                        if (evt.lengthComputable) {
                            const percentComplete = Math.round((evt.loaded / evt.total) * 50);
                            MTCSVImport.updateProgress(percentComplete, mt_csv_import.i18n.uploading_file);
                        }
                    }, false);
                    return xhr;
                },
                success: (response) => {
                    this.handleImportResponse(response);
                },
                error: (xhr, status, error) => {
                    this.handleImportError(xhr, status, error);
                },
                complete: () => {
                    this.isImporting = false;
                    this.hideProgressModal();
                    // Re-enable form elements
                    $('.mt-import-export-container input, .mt-import-export-container select, .mt-import-export-container button').prop('disabled', false);
                }
            });
        },
        /**
         * Handle import response
         */
        handleImportResponse: function(response) {
            if (response.success) {
                this.updateProgress(100, mt_csv_import.i18n.import_complete);
                // Show success message
                let message = response.data.message || mt_csv_import.i18n.import_complete;
                // Build detailed message
                if (response.data.data) {
                    const details = [];
                    if (response.data.data.imported > 0) {
                        details.push(response.data.data.imported + ' ' + mt_csv_import.i18n.created);
                    }
                    if (response.data.data.updated > 0) {
                        details.push(response.data.data.updated + ' ' + mt_csv_import.i18n.updated);
                    }
                    if (response.data.data.skipped > 0) {
                        details.push(response.data.data.skipped + ' ' + mt_csv_import.i18n.skipped);
                    }
                    if (response.data.data.errors > 0) {
                        details.push(response.data.data.errors + ' ' + mt_csv_import.i18n.errors);
                    }
                    if (details.length) {
                        message += ' (' + details.join(', ') + ')';
                    }
                }
                this.showNotice(message, 'success');
                // Show error details if any
                if (response.data.data && response.data.data.error_details && response.data.data.error_details.length) {
                    this.showErrorDetails(response.data.data.error_details);
                }
                // Clear file input
                $('#csv_file').val('');
            } else {
                this.updateProgress(0, mt_csv_import.i18n.import_failed);
                this.showNotice(response.data.message || mt_csv_import.i18n.import_failed, 'error');
                // Show error details
                if (response.data.data && response.data.data.error_details) {
                    this.showErrorDetails(response.data.data.error_details);
                }
            }
        },
        /**
         * Handle import error
         */
        handleImportError: function(xhr, status, error) {
            // Import error occurred
            let errorMessage = mt_csv_import.i18n.import_error;
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                errorMessage = xhr.responseJSON.data.message;
            } else if (error) {
                errorMessage += ': ' + error;
            }
            this.showNotice(errorMessage, 'error');
            this.updateProgress(0, mt_csv_import.i18n.import_failed);
        },
        /**
         * Validate file before upload
         */
        validateFile: function(e) {
            const file = e.target.files[0];
            if (!file) {
                return;
            }
            // Check file extension
            const validExtensions = ['csv', 'txt'];
            const extension = file.name.split('.').pop().toLowerCase();
            if (!validExtensions.includes(extension)) {
                this.showNotice(mt_csv_import.i18n.invalid_file_type, 'error');
                e.target.value = '';
                return;
            }
            // Check file size (10MB max)
            const maxSize = 10 * 1024 * 1024; // 10MB in bytes
            if (file.size > maxSize) {
                this.showNotice(mt_csv_import.i18n.file_too_large, 'error');
                e.target.value = '';
                return;
            }
            // Optional: Preview file info
            const fileInfo = mt_csv_import.i18n.file_selected.replace('%s', file.name);
            const sizeInfo = ' (' + this.formatFileSize(file.size) + ')';
            this.showNotice(fileInfo + sizeInfo, 'info');
        },
        /**
         * Update import help based on selected type
         */
        updateImportHelp: function(e) {
            const importType = $(e.target).val();
            const helpText = $('.import-type-help');
            if (!helpText.length) {
                // Create help text element if it doesn't exist
                const help = $('<p/>', {
                    class: 'import-type-help description',
                    style: 'margin-top: 10px;'
                });
                $(e.target).closest('td').append(help);
            }
            if (importType === 'candidates') {
                $('.import-type-help').html(mt_csv_import.i18n.candidates_help);
            } else if (importType === 'jury_members') {
                $('.import-type-help').html(mt_csv_import.i18n.jury_help);
            } else {
                $('.import-type-help').html('');
            }
        },
        /**
         * Setup progress modal
         */
        setupProgressModal: function() {
            if ($('#mt-import-progress-modal').length) {
                return;
            }
            const modal = $('<div/>', {
                id: 'mt-import-progress-modal',
                class: 'mt-modal',
                style: 'display: none;',
                html: `
                    <div class="mt-modal-content">
                        <h2>${mt_csv_import.i18n.importing}</h2>
                        <div class="mt-progress-wrapper">
                            <div class="mt-progress-bar">
                                <div class="mt-progress-bar-fill" style="width: 0%">
                                    <span class="mt-progress-percentage">0%</span>
                                </div>
                            </div>
                            <div class="mt-progress-stats">
                                <span class="mt-progress-current">0</span> / <span class="mt-progress-total">0</span> records
                            </div>
                        </div>
                        <p class="mt-progress-message">${mt_csv_import.i18n.please_wait}</p>
                        <div class="mt-progress-details">
                            <div class="mt-progress-item mt-progress-success" style="display: none;">
                                <span class="icon">✓</span> <span class="label">${mt_csv_import.i18n.imported || 'Imported'}:</span> <span class="count">0</span>
                            </div>
                            <div class="mt-progress-item mt-progress-updated" style="display: none;">
                                <span class="icon">↻</span> <span class="label">${mt_csv_import.i18n.updated || 'Updated'}:</span> <span class="count">0</span>
                            </div>
                            <div class="mt-progress-item mt-progress-skipped" style="display: none;">
                                <span class="icon">⊘</span> <span class="label">${mt_csv_import.i18n.skipped || 'Skipped'}:</span> <span class="count">0</span>
                            </div>
                            <div class="mt-progress-item mt-progress-errors" style="display: none;">
                                <span class="icon">✗</span> <span class="label">${mt_csv_import.i18n.errors || 'Errors'}:</span> <span class="count">0</span>
                            </div>
                        </div>
                    </div>
                `
            });
            $('body').append(modal);
        },
        /**
         * Show progress modal
         */
        showProgressModal: function() {
            $('#mt-import-progress-modal').fadeIn(200);
        },
        /**
         * Hide progress modal
         */
        hideProgressModal: function() {
            setTimeout(() => {
                $('#mt-import-progress-modal').fadeOut(200);
            }, 1000);
        },
        /**
         * Update progress
         */
        updateProgress: function(percentage, message, stats) {
            // Update progress bar
            $('.mt-progress-bar-fill').css('width', percentage + '%');
            $('.mt-progress-percentage').text(percentage + '%');
            // Update message
            if (message) {
                $('.mt-progress-message').text(message);
            }
            // Update stats if provided
            if (stats) {
                if (stats.total !== undefined) {
                    $('.mt-progress-total').text(stats.total);
                }
                if (stats.current !== undefined) {
                    $('.mt-progress-current').text(stats.current);
                }
                // Update detail counts
                if (stats.success !== undefined && stats.success > 0) {
                    $('.mt-progress-success').show().find('.count').text(stats.success);
                }
                if (stats.updated !== undefined && stats.updated > 0) {
                    $('.mt-progress-updated').show().find('.count').text(stats.updated);
                }
                if (stats.skipped !== undefined && stats.skipped > 0) {
                    $('.mt-progress-skipped').show().find('.count').text(stats.skipped);
                }
                if (stats.errors !== undefined && stats.errors > 0) {
                    $('.mt-progress-errors').show().find('.count').text(stats.errors);
                }
            }
        },
        /**
         * Show notice message
         */
        showNotice: function(message, type) {
            // Remove existing notices
            $('.mt-import-notice').remove();
            const notice = $('<div/>', {
                class: 'notice notice-' + type + ' is-dismissible mt-import-notice',
                html: '<p>' + message + '</p>'
            });
            // Add dismiss button
            const dismissBtn = $('<button/>', {
                type: 'button',
                class: 'notice-dismiss',
                html: '<span class="screen-reader-text">Dismiss this notice.</span>'
            });
            dismissBtn.on('click', function() {
                notice.fadeOut(200, function() {
                    $(this).remove();
                });
            });
            notice.append(dismissBtn);
            // Insert after heading or at top of container
            const heading = $('.wrap h1').first();
            if (heading.length) {
                heading.after(notice);
            } else {
                $('.wrap').prepend(notice);
            }
            // Auto-dismiss info notices after 5 seconds
            if (type === 'info') {
                setTimeout(() => {
                    notice.find('.notice-dismiss').click();
                }, 5000);
            }
        },
        /**
         * Show error details
         */
        showErrorDetails: function(errors) {
            if (!errors || !errors.length) {
                return;
            }
            let html = '<div class="mt-error-details"><h4>' + mt_csv_import.i18n.error_details + '</h4><ul>';
            errors.forEach(function(error) {
                html += '<li>Row ' + error.row + ': ' + error.error + '</li>';
            });
            html += '</ul></div>';
            const details = $(html);
            $('.mt-import-notice').last().append(details);
        },
        /**
         * Format file size
         */
        formatFileSize: function(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    };
    // Initialize on document ready
    $(document).ready(function() {
        if (typeof mt_csv_import !== 'undefined') {
            MTCSVImport.init();
        }
    });
    // Expose to global scope for debugging
    window.MTCSVImport = MTCSVImport;
})(jQuery);
