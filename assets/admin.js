/**
 * Mobility Trailblazers Admin JavaScript
 */

(function($) {
    'use strict';

    // Wait for document ready
    $(document).ready(function() {
        
        // Initialize tooltips
        initTooltips();
        
        // Initialize tabs
        initTabs();
        
        // Initialize modals
        initModals();
        
        // Initialize confirmations
        initConfirmations();
        
        // Initialize AJAX forms
        initAjaxForms();
        
        // Initialize select2 if available
        if ($.fn.select2) {
            $('.mt-select2').select2();
        }
        
        // Initialize date pickers if available
        if ($.fn.datepicker) {
            $('.mt-datepicker').datepicker({
                dateFormat: 'yy-mm-dd'
            });
        }
    });
    
    /**
     * Initialize tooltips
     */
    function initTooltips() {
        // Already handled by CSS, but we can add enhanced functionality here
        $('.mt-tooltip').on('mouseenter', function() {
            var $tooltip = $(this).find('.mt-tooltip-content');
            
            // Check if tooltip goes off screen
            var tooltipOffset = $tooltip.offset();
            if (tooltipOffset && tooltipOffset.left < 0) {
                $tooltip.css('left', '0');
                $tooltip.css('margin-left', '0');
            }
        });
    }
    
    /**
     * Initialize tabs
     */
    function initTabs() {
        $('.mt-tab-nav a').on('click', function(e) {
            e.preventDefault();
            
            var $this = $(this);
            var target = $this.attr('href');
            
            // Update active states
            $this.siblings().removeClass('active');
            $this.addClass('active');
            
            // Show target content
            $(target).siblings('.mt-tab-content').removeClass('active');
            $(target).addClass('active');
            
            // Save active tab to localStorage
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('mt_active_tab_' + window.location.pathname, target);
            }
        });
        
        // Restore active tab from localStorage
        if (typeof(Storage) !== "undefined") {
            var savedTab = localStorage.getItem('mt_active_tab_' + window.location.pathname);
            if (savedTab && $(savedTab).length) {
                $('.mt-tab-nav a[href="' + savedTab + '"]').trigger('click');
            }
        }
    }
    
    /**
     * Initialize modals
     */
    function initModals() {
        // Open modal
        $('[data-modal]').on('click', function(e) {
            e.preventDefault();
            var modalId = $(this).data('modal');
            $('#' + modalId).fadeIn();
        });
        
        // Close modal
        $('.mt-modal-close, .mt-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).closest('.mt-modal').fadeOut();
            }
        });
        
        // Close on escape key
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape') {
                $('.mt-modal:visible').fadeOut();
            }
        });
    }
    
    /**
     * Initialize confirmations
     */
    function initConfirmations() {
        $('[data-confirm]').on('click', function(e) {
            var message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });
    }
    
    /**
     * Initialize AJAX forms
     */
    function initAjaxForms() {
        $('.mt-ajax-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submit = $form.find('[type="submit"]');
            var originalText = $submit.text();
            
            // Disable submit button and show loading
            $submit.prop('disabled', true).html(originalText + ' <span class="mt-spinner"></span>');
            
            // Clear previous errors
            $form.find('.mt-form-error').remove();
            $form.find('.mt-alert').remove();
            
            // Prepare data
            var formData = new FormData(this);
            formData.append('action', $form.data('action'));
            formData.append('nonce', mt_admin.nonce);
            
            // Send AJAX request
            $.ajax({
                url: mt_admin.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        $form.prepend('<div class="mt-alert mt-alert-success">' + response.data.message + '</div>');
                        
                        // Reset form if specified
                        if ($form.data('reset-on-success')) {
                            $form[0].reset();
                        }
                        
                        // Trigger custom event
                        $form.trigger('mt:form:success', [response]);
                        
                        // Redirect if URL provided
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        }
                    } else {
                        // Show error message
                        $form.prepend('<div class="mt-alert mt-alert-danger">' + response.data.message + '</div>');
                        
                        // Show field errors if any
                        if (response.data.errors) {
                            $.each(response.data.errors, function(field, error) {
                                var $field = $form.find('[name="' + field + '"]');
                                $field.after('<span class="mt-form-error">' + error + '</span>');
                            });
                        }
                        
                        // Trigger custom event
                        $form.trigger('mt:form:error', [response]);
                    }
                },
                error: function() {
                    $form.prepend('<div class="mt-alert mt-alert-danger">' + mt_admin.i18n.error + '</div>');
                },
                complete: function() {
                    // Re-enable submit button
                    $submit.prop('disabled', false).text(originalText);
                }
            });
        });
    }
    
    /**
     * Utility function to show notification
     */
    window.mtShowNotification = function(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap > h1').after($notification);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        $notification.on('click', '.notice-dismiss', function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        });
    };
    
    /**
     * Utility function to handle AJAX errors
     */
    window.mtHandleAjaxError = function(xhr, textStatus, errorThrown) {
        console.error('AJAX Error:', textStatus, errorThrown);
        
        var message = mt_admin.i18n.error;
        
        if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
            message = xhr.responseJSON.data.message;
        } else if (xhr.responseText) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.data && response.data.message) {
                    message = response.data.message;
                }
            } catch (e) {
                // Use default message
            }
        }
        
        mtShowNotification(message, 'error');
    };
    
    /**
     * Utility function to serialize form data
     */
    window.mtSerializeForm = function($form) {
        var data = {};
        
        $form.find('input, select, textarea').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            var value = $field.val();
            
            if (!name) return;
            
            if ($field.is(':checkbox')) {
                if ($field.is(':checked')) {
                    if (name.endsWith('[]')) {
                        if (!data[name]) data[name] = [];
                        data[name].push(value);
                    } else {
                        data[name] = value;
                    }
                }
            } else if ($field.is(':radio')) {
                if ($field.is(':checked')) {
                    data[name] = value;
                }
            } else {
                data[name] = value;
            }
        });
        
        return data;
    };
    
    /**
     * Utility function to update URL parameters
     */
    window.mtUpdateUrlParam = function(key, value) {
        var url = new URL(window.location);
        
        if (value) {
            url.searchParams.set(key, value);
        } else {
            url.searchParams.delete(key);
        }
        
        window.history.pushState({}, '', url);
    };
    
    /**
     * Utility function to get URL parameter
     */
    window.mtGetUrlParam = function(key) {
        var url = new URL(window.location);
        return url.searchParams.get(key);
    };
    
    /**
     * Utility function to format number
     */
    window.mtFormatNumber = function(number) {
        return new Intl.NumberFormat('de-DE').format(number);
    };
    
    /**
     * Utility function to debounce
     */
    window.mtDebounce = function(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

})(jQuery); 