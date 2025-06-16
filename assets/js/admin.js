/**
 * Admin JavaScript for Mobility Trailblazers
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize tooltips if tooltipster is available
        if ($.fn.tooltipster) {
            $('.mt-tooltip').tooltipster({
                theme: 'tooltipster-light',
                maxWidth: 300
            });
        }

        // Handle form submissions with AJAX
        $('.mt-ajax-form').on('submit', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $submitButton = $form.find('button[type="submit"]');
            var originalText = $submitButton.text();

            // Disable submit button and show loading state
            $submitButton.prop('disabled', true).text('Processing...');

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response.success) {
                        showNotice('success', response.data.message);
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        showNotice('error', response.data.message);
                    }
                },
                error: function() {
                    showNotice('error', 'An error occurred. Please try again.');
                },
                complete: function() {
                    // Re-enable submit button
                    $submitButton.prop('disabled', false).text(originalText);
                }
            });
        });

        // Handle dynamic form fields
        $('.mt-add-field').on('click', function(e) {
            e.preventDefault();
            var $container = $(this).closest('.mt-form-group').find('.mt-field-container');
            var $template = $container.find('.mt-field-template').clone();
            $template.removeClass('mt-field-template').show();
            $container.append($template);
        });

        $('.mt-field-container').on('click', '.mt-remove-field', function(e) {
            e.preventDefault();
            $(this).closest('.mt-field').remove();
        });

        // Handle tab navigation
        $('.mt-tab-nav a').on('click', function(e) {
            e.preventDefault();
            var target = $(this).attr('href');
            
            // Update active states
            $('.mt-tab-nav a').removeClass('active');
            $(this).addClass('active');
            
            // Show target content
            $('.mt-tab-content').hide();
            $(target).show();
        });

        // Initialize date pickers if jQuery UI is available
        if ($.fn.datepicker) {
            $('.mt-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                changeMonth: true,
                changeYear: true
            });
        }

        // Handle bulk actions
        $('.mt-bulk-action').on('change', function() {
            var action = $(this).val();
            if (action) {
                var $form = $(this).closest('form');
                $form.find('input[name="bulk_action"]').val(action);
                $form.submit();
            }
        });

        // Handle select all checkboxes
        $('.mt-select-all').on('change', function() {
            var isChecked = $(this).prop('checked');
            $(this).closest('table').find('input[type="checkbox"]').prop('checked', isChecked);
        });

        // Handle dynamic search
        var searchTimeout;
        $('.mt-search-input').on('input', function() {
            var $input = $(this);
            var searchTerm = $input.val();
            var $container = $input.closest('.mt-search-container');
            var $results = $container.find('.mt-search-results');
            
            clearTimeout(searchTimeout);
            
            if (searchTerm.length < 2) {
                $results.hide();
                return;
            }
            
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'mt_search',
                        nonce: mtAdmin.nonce,
                        term: searchTerm
                    },
                    success: function(response) {
                        if (response.success) {
                            $results.html(response.data.html).show();
                        }
                    }
                });
            }, 300);
        });

        // Close search results when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.mt-search-container').length) {
                $('.mt-search-results').hide();
            }
        });

        // Handle file uploads
        $('.mt-file-upload').on('change', function() {
            var $input = $(this);
            var $preview = $input.closest('.mt-upload-container').find('.mt-upload-preview');
            var file = this.files[0];
            
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    $preview.html('<img src="' + e.target.result + '" alt="Preview">');
                };
                reader.readAsDataURL(file);
            }
        });

        // Handle drag and drop file uploads
        $('.mt-dropzone').on('dragover', function(e) {
            e.preventDefault();
            $(this).addClass('mt-drag-over');
        }).on('dragleave', function() {
            $(this).removeClass('mt-drag-over');
        }).on('drop', function(e) {
            e.preventDefault();
            $(this).removeClass('mt-drag-over');
            
            var files = e.originalEvent.dataTransfer.files;
            if (files.length) {
                $(this).find('input[type="file"]').prop('files', files).trigger('change');
            }
        });
    });

    // Helper function to show notices
    function showNotice(type, message) {
        var $notice = $('<div class="mt-alert mt-alert-' + type + '">' + message + '</div>');
        $('.mt-notices').append($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

})(jQuery); 