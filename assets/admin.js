/* 
 * Mobility Trailblazers Admin JavaScript - FIXED VERSION
 * File: assets/admin.js
 */

jQuery(document).ready(function($) {
    console.log('MT Admin JS loading...');
    
    // Skip initialization on assignment management page to avoid conflicts
    if (window.location.search.includes('page=mt-assignments')) {
        console.log('Assignment page detected, skipping admin.js initialization');
        return;
    }
    
    console.log('MT Admin JS initializing...');
    
    // Evaluation form handling
    $('.mt-evaluation-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(form[0]);
        
        // Add AJAX action
        formData.append('action', 'mt_submit_vote');
        formData.append('nonce', mt_ajax.nonce);
        
        // Disable submit button
        var submitBtn = form.find('input[type="submit"]');
        var originalText = submitBtn.val();
        submitBtn.prop('disabled', true).val('Submitting...');
        
        $.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert(mt_ajax.strings.vote_success);
                    // Optionally redirect or update UI
                    if (response.data.redirect) {
                        window.location.href = response.data.redirect;
                    }
                } else {
                    alert(response.data.message || mt_ajax.strings.vote_error);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alert(mt_ajax.strings.vote_error);
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).val(originalText);
            }
        });
    });
    
    // Candidate management
    $('.mt-candidate-actions').on('click', '.mt-edit-candidate', function(e) {
        e.preventDefault();
        var candidateId = $(this).data('candidate-id');
        // Handle candidate editing
        console.log('Edit candidate:', candidateId);
    });
    
    // Jury member management
    $('.mt-jury-actions').on('click', '.mt-edit-jury', function(e) {
        e.preventDefault();
        var juryId = $(this).data('jury-id');
        // Handle jury editing
        console.log('Edit jury member:', juryId);
    });
    
    // FIXED: Filter handling with null check
    $('.mt-filter-select').on('change', function() {
        var filterValue = $(this).val();
        var filterType = $(this).data('filter-type');
        
        // FIXED: Add null/undefined check before calling toLowerCase
        if (filterValue && typeof filterValue === 'string') {
            filterValue = filterValue.toLowerCase();
        } else {
            filterValue = '';  // Default to empty string if null/undefined
        }
        
        console.log('Filter changed:', filterType, filterValue);
        
        // Apply filter logic here
        applyFilter(filterType, filterValue);
    });
    
    // FIXED: Search handling with proper validation
    $('.mt-search-input').on('input', function() {
        var searchTerm = $(this).val();
        
        // FIXED: Validate search term before processing
        if (searchTerm && typeof searchTerm === 'string') {
            searchTerm = searchTerm.toLowerCase().trim();
        } else {
            searchTerm = '';
        }
        
        console.log('Search term:', searchTerm);
        
        // Apply search logic
        applySearch(searchTerm);
    });
    
    // Filter application function
    function applyFilter(filterType, filterValue) {
        if (!filterType) return;
        
        $('.mt-item').each(function() {
            var item = $(this);
            var itemValue = item.data(filterType);
            
            // FIXED: Ensure itemValue is a string before comparison
            if (itemValue && typeof itemValue !== 'string') {
                itemValue = String(itemValue);
            }
            
            var matches = !filterValue || 
                         (itemValue && itemValue.toLowerCase().includes(filterValue));
            
            item.toggle(matches);
        });
    }
    
    // Search application function
    function applySearch(searchTerm) {
        $('.mt-item').each(function() {
            var item = $(this);
            var itemText = item.text();
            
            // FIXED: Ensure itemText is properly handled
            if (itemText && typeof itemText === 'string') {
                itemText = itemText.toLowerCase();
            } else {
                itemText = '';
            }
            
            var matches = !searchTerm || itemText.includes(searchTerm);
            item.toggle(matches);
        });
    }
    
    // Bulk actions
    $('.mt-bulk-actions').on('change', function() {
        var action = $(this).val();
        if (action) {
            var selectedItems = $('.mt-item-checkbox:checked');
            if (selectedItems.length === 0) {
                alert('Please select items first.');
                $(this).val('');
                return;
            }
            
            if (confirm('Are you sure you want to perform this action?')) {
                performBulkAction(action, selectedItems);
            }
            $(this).val('');
        }
    });
    
    // Bulk action performer
    function performBulkAction(action, items) {
        var itemIds = [];
        items.each(function() {
            itemIds.push($(this).val());
        });
        
        $.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_bulk_action',
                bulk_action: action,
                item_ids: itemIds,
                nonce: mt_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Bulk action completed successfully.');
                    location.reload();
                } else {
                    alert('Bulk action failed: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Network error. Please try again.');
            }
        });
    }
    
    // Select all/none functionality
    $('#mt-select-all').on('change', function() {
        var checked = $(this).prop('checked');
        $('.mt-item-checkbox').prop('checked', checked);
    });
    
    $('.mt-item-checkbox').on('change', function() {
        var totalCheckboxes = $('.mt-item-checkbox').length;
        var checkedCheckboxes = $('.mt-item-checkbox:checked').length;
        var selectAll = $('#mt-select-all');
        
        if (checkedCheckboxes === 0) {
            selectAll.prop('indeterminate', false).prop('checked', false);
        } else if (checkedCheckboxes === totalCheckboxes) {
            selectAll.prop('indeterminate', false).prop('checked', true);
        } else {
            selectAll.prop('indeterminate', true);
        }
    });
    
    // Tab functionality
    $('.mt-nav-tab').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).attr('href');
        
        // Update active tab
        $('.mt-nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Show corresponding tab content
        $('.mt-tab-content').removeClass('active');
        $(tabId).addClass('active');
    });
    
    // Sortable tables
    if ($.fn.sortable) {
        $('.mt-sortable-table tbody').sortable({
            handle: '.mt-sort-handle',
            update: function(event, ui) {
                var order = $(this).sortable('toArray', {attribute: 'data-id'});
                
                $.ajax({
                    url: mt_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'mt_update_order',
                        order: order,
                        nonce: mt_ajax.nonce
                    },
                    success: function(response) {
                        if (!response.success) {
                            console.error('Failed to update order');
                        }
                    }
                });
            }
        });
    }
    
    // Confirmation dialogs
    $('.mt-confirm-action').on('click', function(e) {
        var message = $(this).data('confirm-message') || 'Are you sure?';
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Dynamic form fields
    $('.mt-add-field').on('click', function() {
        var template = $(this).data('template');
        var container = $(this).siblings('.mt-dynamic-fields');
        
        if (template && container.length) {
            var newField = $(template).clone();
            
            // Update field names and IDs
            var index = container.children().length;
            newField.find('input, select, textarea').each(function() {
                var field = $(this);
                var name = field.attr('name');
                var id = field.attr('id');
                
                if (name) {
                    field.attr('name', name.replace('[0]', '[' + index + ']'));
                }
                if (id) {
                    field.attr('id', id.replace('_0', '_' + index));
                }
            });
            
            container.append(newField);
        }
    });
    
    $(document).on('click', '.mt-remove-field', function() {
        $(this).closest('.mt-field-group').remove();
    });
    
    // Media uploader
    if (typeof wp !== 'undefined' && wp.media) {
        $('.mt-media-upload').on('click', function(e) {
            e.preventDefault();
            
            var button = $(this);
            var targetInput = button.siblings('input[type="hidden"]');
            var previewContainer = button.siblings('.mt-media-preview');
            
            var mediaUploader = wp.media({
                title: 'Select Media',
                button: {
                    text: 'Use this media'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                targetInput.val(attachment.id);
                previewContainer.html('<img src="' + attachment.url + '" style="max-width: 150px; height: auto;">');
            });
            
            mediaUploader.open();
        });
    }
    
    console.log('MT Admin JS initialized successfully');
});

// Global utility functions
window.MTAdmin = {
    showNotification: function(message, type) {
        type = type || 'info';
        var notification = jQuery('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        jQuery('.wrap h1').first().after(notification);
        
        setTimeout(function() {
            notification.fadeOut();
        }, 5000);
    },
    
    confirmAction: function(message, callback) {
        if (confirm(message || 'Are you sure?')) {
            if (typeof callback === 'function') {
                callback();
            }
            return true;
        }
        return false;
    },
    
    loadCandidates: function(filters, callback) {
        jQuery.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_get_candidates',
                filters: filters || {},
                nonce: mt_ajax.nonce
            },
            success: function(response) {
                if (response.success && typeof callback === 'function') {
                    callback(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Failed to load candidates:', error);
            }
        });
    },
    
    // Safe string operations
    safeToLowerCase: function(str) {
        if (str && typeof str === 'string') {
            return str.toLowerCase();
        }
        return '';
    },
    
    safeStringOperation: function(str, operation) {
        if (str && typeof str === 'string') {
            switch(operation) {
                case 'lower':
                    return str.toLowerCase();
                case 'upper':
                    return str.toUpperCase();
                case 'trim':
                    return str.trim();
                default:
                    return str;
            }
        }
        return '';
    }
};