/**
 * I18n Admin JavaScript
 *
 * Handles client-side internationalization functionality for the admin area
 * 
 * @package MobilityTrailblazers
 * @since 2.1.0
 */
(function($) {
    'use strict';
    // I18n Admin Manager
    window.MT_I18n_Admin = {
        /**
         * Initialize i18n admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initLanguageSelector();
            this.updateStrings();
        },
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Language selector change
            $(document).on('change', '#mt_language_preference', this.handleLanguageChange.bind(this));
            // Admin bar language switcher
            $(document).on('click', '.mt-language-switcher a', this.handleQuickSwitch.bind(this));
            // Settings page language options
            $(document).on('change', '#mt_default_language', this.handleDefaultLanguageChange.bind(this));
        },
        /**
         * Initialize language selector enhancement
         */
        initLanguageSelector: function() {
            // Enhance language selectors with better UI
            $('#mt_language_preference, #mt_default_language').each(function() {
                var $select = $(this);
                if ($select.hasClass('enhanced')) {
                    return;
                }
                $select.addClass('enhanced');
                // Add visual preview of selected language
                var updatePreview = function() {
                    var $selected = $select.find('option:selected');
                    var flag = $selected.text().split(' ')[0];
                    var $preview = $select.siblings('.mt-language-preview');
                    if (!$preview.length) {
                        $preview = $('<span class="mt-language-preview"></span>');
                        $select.after($preview);
                    }
                    $preview.html('<span class="mt-flag">' + flag + '</span>');
                };
                $select.on('change', updatePreview);
                updatePreview();
            });
        },
        /**
         * Handle language preference change
         */
        handleLanguageChange: function(e) {
            var $select = $(e.target);
            var language = $select.val();
            // Show loading indicator
            $select.prop('disabled', true);
            $select.after('<span class="spinner is-active mt-spinner"></span>');
            // Save preference via AJAX
            $.ajax({
                url: mt_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'mt_update_language_preference',
                    language: language,
                    nonce: mt_admin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        MT_I18n_Admin.showNotice(
                            mt_admin.i18n.language_updated || 'Language preference updated successfully.',
                            'success'
                        );
                        // Reload page to apply new language
                        if (response.data.reload) {
                            setTimeout(function() {
                                window.location.reload();
                            }, 1000);
                        }
                    } else {
                        MT_I18n_Admin.showNotice(
                            response.data || mt_admin.i18n.error || 'An error occurred.',
                            'error'
                        );
                    }
                },
                error: function() {
                    MT_I18n_Admin.showNotice(
                        mt_admin.i18n.error || 'An error occurred.',
                        'error'
                    );
                },
                complete: function() {
                    $select.prop('disabled', false);
                    $('.mt-spinner').remove();
                }
            });
        },
        /**
         * Handle quick language switch from admin bar
         */
        handleQuickSwitch: function(e) {
            e.preventDefault();
            var $link = $(e.currentTarget);
            var href = $link.attr('href');
            // Add loading state
            $link.addClass('mt-loading');
            // Navigate to new language
            window.location.href = href;
        },
        /**
         * Handle default language change in settings
         */
        handleDefaultLanguageChange: function(e) {
            var $select = $(e.target);
            var language = $select.val();
            // Show confirmation if changing from current default
            var currentDefault = $select.data('current');
            if (currentDefault && currentDefault !== language) {
                if (!confirm(mt_admin.i18n.confirm_default_language || 'Are you sure you want to change the default language? This will affect all users who haven\'t set a preference.')) {
                    $select.val(currentDefault);
                    return;
                }
            }
            // Update data attribute
            $select.data('current', language);
        },
        /**
         * Update dynamic strings based on current language
         */
        updateStrings: function() {
            // Update any dynamic UI elements that need translation
            $('.mt-translatable').each(function() {
                var $elem = $(this);
                var key = $elem.data('i18n-key');
                if (key && mt_admin.i18n[key]) {
                    $elem.text(mt_admin.i18n[key]);
                }
            });
            // Update tooltips
            $('.mt-tooltip').each(function() {
                var $elem = $(this);
                var key = $elem.data('i18n-tooltip');
                if (key && mt_admin.i18n[key]) {
                    $elem.attr('title', mt_admin.i18n[key]);
                }
            });
            // Update placeholders
            $('[data-i18n-placeholder]').each(function() {
                var $elem = $(this);
                var key = $elem.data('i18n-placeholder');
                if (key && mt_admin.i18n[key]) {
                    $elem.attr('placeholder', mt_admin.i18n[key]);
                }
            });
        },
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible mt-notice"><p>' + message + '</p></div>');
            // Find or create notices container
            var $container = $('.mt-notices-container');
            if (!$container.length) {
                $container = $('<div class="mt-notices-container"></div>');
                $('.wrap h1').first().after($container);
            }
            // Add notice
            $container.append($notice);
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
            // Make dismissible
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            });
        },
        /**
         * Get translated string
         */
        getString: function(key, fallback) {
            if (mt_admin.i18n && mt_admin.i18n[key]) {
                return mt_admin.i18n[key];
            }
            return fallback || key;
        },
        /**
         * Format string with placeholders
         */
        formatString: function(key, replacements) {
            var string = this.getString(key);
            if (replacements && typeof replacements === 'object') {
                Object.keys(replacements).forEach(function(placeholder) {
                    var regex = new RegExp('%' + placeholder + '%', 'g');
                    string = string.replace(regex, replacements[placeholder]);
                });
            }
            return string;
        }
    };
    // Initialize when document is ready
    $(document).ready(function() {
        MT_I18n_Admin.init();
    });
    // Add styles for language UI elements
    var styles = `
        <style>
        .mt-language-preview {
            display: inline-block;
            margin-left: 10px;
            font-size: 20px;
            vertical-align: middle;
        }
        .mt-spinner {
            margin-left: 10px;
            float: none;
            vertical-align: middle;
        }
        .mt-loading {
            opacity: 0.6;
            pointer-events: none;
        }
        .mt-notices-container {
            margin-top: 20px;
        }
        .mt-notice {
            margin: 5px 0;
        }
        #mt_language_preference,
        #mt_default_language {
            min-width: 200px;
        }
        .mt-language-switcher {
            margin-top: 10px;
        }
        .mt-flag {
            font-size: 1.2em;
            margin-right: 5px;
        }
        /* Admin bar language switcher styles */
        #wpadminbar .mt-language-switcher > a {
            padding: 0 8px !important;
        }
        #wpadminbar .mt-language-switcher .ab-submenu {
            min-width: 180px;
        }
        #wpadminbar .mt-language-switcher .ab-submenu a {
            padding: 0 16px !important;
            line-height: 32px !important;
        }
        </style>
    `;
    $('head').append(styles);
})(jQuery);

