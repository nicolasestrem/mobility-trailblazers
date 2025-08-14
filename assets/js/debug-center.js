/**
 * Debug Center JavaScript
 *
 * @package MobilityTrailblazers
 * @since 2.3.0
 */

(function($) {
    'use strict';
    
    const MTDebugCenter = {
        
        // Properties
        ajaxUrl: mt_debug.ajax_url || ajaxurl,
        nonce: mt_debug.nonce,
        activeTab: null,
        
        /**
         * Initialize Debug Center
         */
        init: function() {
            this.bindEvents();
            this.initActiveTab();
            this.initTooltips();
            this.startAutoRefresh();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Tab navigation
            $(document).on('click', '.nav-tab', this.handleTabClick.bind(this));
            
            // Diagnostic operations
            $(document).on('submit', '.diagnostic-form', this.handleDiagnosticSubmit.bind(this));
            $(document).on('click', '.mt-run-diagnostic', this.runDiagnostic.bind(this));
            $(document).on('click', '.mt-export-diagnostics', this.exportDiagnostics.bind(this));
            
            // Debug script operations
            $(document).on('click', '.mt-execute-script', this.executeScript.bind(this));
            
            // Maintenance operations
            $(document).on('click', '.mt-run-maintenance', this.runMaintenance.bind(this));
            $(document).on('click', '.mt-confirm-operation', this.confirmOperation.bind(this));
            
            // Error monitoring
            $(document).on('click', '.mt-clear-logs', this.clearLogs.bind(this));
            $(document).on('click', '.mt-refresh-errors', this.refreshErrors.bind(this));
            
            // Database operations
            $(document).on('click', '.mt-analyze-table', this.analyzeTable.bind(this));
            $(document).on('click', '.mt-optimize-table', this.optimizeTable.bind(this));
            
            // System info
            $(document).on('click', '.mt-copy-sysinfo', this.copySystemInfo.bind(this));
            $(document).on('click', '.mt-export-sysinfo', this.exportSystemInfo.bind(this));
            
            // Widget refresh
            $(document).on('click', '.mt-refresh-widget', this.refreshWidget.bind(this));
        },
        
        /**
         * Initialize active tab
         */
        initActiveTab: function() {
            const urlParams = new URLSearchParams(window.location.search);
            this.activeTab = urlParams.get('tab') || 'diagnostics';
        },
        
        /**
         * Initialize tooltips
         */
        initTooltips: function() {
            $('.mt-tooltip').tooltip({
                position: { my: 'center bottom-10', at: 'center top' }
            });
        },
        
        /**
         * Start auto-refresh for certain widgets
         */
        startAutoRefresh: function() {
            // Refresh system status widget every 30 seconds
            setInterval(() => {
                if (this.activeTab === 'diagnostics') {
                    this.refreshWidget({ target: $('[data-widget="system_status"]')[0] });
                }
            }, 30000);
        },
        
        /**
         * Handle tab click
         */
        handleTabClick: function(e) {
            // Let default navigation happen, just track active tab
            const href = $(e.currentTarget).attr('href');
            const match = href.match(/tab=([^&]+)/);
            if (match) {
                this.activeTab = match[1];
            }
        },
        
        /**
         * Handle diagnostic form submission
         */
        handleDiagnosticSubmit: function(e) {
            e.preventDefault();
            this.runDiagnostic(e);
        },
        
        /**
         * Run diagnostic
         */
        runDiagnostic: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget).find('button[name="run_diagnostic"]') || $(e.currentTarget);
            const $form = $button.closest('form');
            const type = $form.find('#diagnostic_type').val() || 'full';
            
            $button.prop('disabled', true).addClass('updating-message');
            const originalText = $button.text();
            $button.text(mt_debug.i18n.running || 'Running...');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_run_diagnostic',
                    nonce: this.nonce,
                    diagnostic_type: type
                },
                success: (response) => {
                    if (response.success) {
                        this.displayDiagnosticResults(response.data);
                        this.showNotification(mt_debug.i18n.diagnostic_complete || 'Diagnostic complete', 'success');
                    } else {
                        this.showNotification(response.data || mt_debug.i18n.diagnostic_failed || 'Diagnostic failed', 'error');
                    }
                },
                error: () => {
                    this.showNotification(mt_debug.i18n.network_error || 'Network error occurred', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).removeClass('updating-message').text(originalText);
                }
            });
        },
        
        /**
         * Display diagnostic results
         */
        displayDiagnosticResults: function(data) {
            const $results = $('.diagnostic-results');
            
            if (!$results.length) {
                location.reload(); // Reload to show results from server
                return;
            }
            
            // Update results container
            // This would typically update the DOM with the results
            // For now, we'll reload the page to show server-rendered results
            location.reload();
        },
        
        /**
         * Export diagnostics
         */
        exportDiagnostics: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            $button.prop('disabled', true);
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_export_diagnostics',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.downloadJSON(response.data.data, response.data.filename);
                        this.showNotification(mt_debug.i18n.export_complete || 'Export complete', 'success');
                    } else {
                        this.showNotification(response.data || mt_debug.i18n.export_failed || 'Export failed', 'error');
                    }
                },
                error: () => {
                    this.showNotification(mt_debug.i18n.network_error || 'Network error occurred', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false);
                }
            });
        },
        
        /**
         * Execute debug script
         */
        executeScript: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const script = $button.data('script');
            const dangerous = $button.data('dangerous');
            
            if (dangerous && !confirm(mt_debug.i18n.confirm_dangerous || 'This is a dangerous operation. Are you sure?')) {
                return;
            }
            
            $button.prop('disabled', true).addClass('updating-message');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_execute_debug_script',
                    nonce: this.nonce,
                    script: script,
                    params: {
                        confirm: dangerous ? true : false
                    }
                },
                success: (response) => {
                    if (response.success) {
                        this.showScriptOutput(response.data);
                        this.showNotification(mt_debug.i18n.script_complete || 'Script executed', 'success');
                    } else {
                        if (response.data && response.data.requires_confirmation) {
                            if (confirm(response.data.message)) {
                                // Retry with confirmation
                                this.executeScript(e);
                            }
                        } else {
                            this.showNotification(response.data || mt_debug.i18n.script_failed || 'Script failed', 'error');
                        }
                    }
                },
                error: () => {
                    this.showNotification(mt_debug.i18n.network_error || 'Network error occurred', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).removeClass('updating-message');
                }
            });
        },
        
        /**
         * Show script output
         */
        showScriptOutput: function(data) {
            const $modal = $('<div class="mt-modal">').html(`
                <div class="mt-modal-content">
                    <h3>${mt_debug.i18n.script_output || 'Script Output'}</h3>
                    <pre>${this.escapeHtml(data.output || '')}</pre>
                    ${data.errors && data.errors.length ? 
                        `<div class="notice notice-error">
                            <p>${mt_debug.i18n.errors_occurred || 'Errors occurred'}:</p>
                            <ul>${data.errors.map(err => `<li>${this.escapeHtml(err.message)}</li>`).join('')}</ul>
                        </div>` : ''}
                    <button class="button mt-close-modal">${mt_debug.i18n.close || 'Close'}</button>
                </div>
            `);
            
            $('body').append($modal);
            
            $modal.on('click', '.mt-close-modal, .mt-modal', function(e) {
                if (e.target === this || $(e.target).hasClass('mt-close-modal')) {
                    $modal.remove();
                }
            });
        },
        
        /**
         * Run maintenance operation
         */
        runMaintenance: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const category = $button.data('category');
            const operation = $button.data('operation');
            const dangerous = $button.data('dangerous');
            
            if (dangerous && !confirm(mt_debug.i18n.confirm_dangerous || 'This is a dangerous operation. Are you sure?')) {
                return;
            }
            
            $button.prop('disabled', true).addClass('updating-message');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_run_maintenance',
                    nonce: this.nonce,
                    category: category,
                    operation: operation,
                    params: {
                        confirm: dangerous ? true : false
                    }
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message || mt_debug.i18n.operation_complete || 'Operation complete', 'success');
                        
                        // Show details if available
                        if (response.data.data) {
                            console.log('Operation details:', response.data.data);
                        }
                    } else {
                        if (response.data && response.data.requires_password) {
                            this.promptPassword(category, operation);
                        } else {
                            this.showNotification(response.data || mt_debug.i18n.operation_failed || 'Operation failed', 'error');
                        }
                    }
                },
                error: () => {
                    this.showNotification(mt_debug.i18n.network_error || 'Network error occurred', 'error');
                },
                complete: () => {
                    $button.prop('disabled', false).removeClass('updating-message');
                }
            });
        },
        
        /**
         * Prompt for password
         */
        promptPassword: function(category, operation) {
            const password = prompt(mt_debug.i18n.enter_password || 'Enter your admin password:');
            
            if (password) {
                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'mt_run_maintenance',
                        nonce: this.nonce,
                        category: category,
                        operation: operation,
                        params: {
                            confirm: true,
                            password: password
                        }
                    },
                    success: (response) => {
                        if (response.success) {
                            this.showNotification(response.data.message || mt_debug.i18n.operation_complete || 'Operation complete', 'success');
                        } else {
                            this.showNotification(response.data || mt_debug.i18n.operation_failed || 'Operation failed', 'error');
                        }
                    }
                });
            }
        },
        
        /**
         * Clear logs
         */
        clearLogs: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const logType = $button.data('log-type') || 'all';
            
            if (!confirm(mt_debug.i18n.confirm_clear_logs || 'Are you sure you want to clear the logs?')) {
                return;
            }
            
            $button.prop('disabled', true);
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_clear_debug_logs',
                    nonce: this.nonce,
                    log_type: logType
                },
                success: (response) => {
                    if (response.success) {
                        this.showNotification(response.data.message || mt_debug.i18n.logs_cleared || 'Logs cleared', 'success');
                        this.refreshErrors();
                    } else {
                        this.showNotification(response.data || mt_debug.i18n.clear_failed || 'Failed to clear logs', 'error');
                    }
                },
                complete: () => {
                    $button.prop('disabled', false);
                }
            });
        },
        
        /**
         * Refresh error display
         */
        refreshErrors: function(e) {
            if (e) e.preventDefault();
            
            const $container = $('.error-stats-container');
            if (!$container.length) return;
            
            $container.addClass('updating');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_get_error_stats',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Update error stats display
                        // This would typically update the DOM
                        location.reload();
                    }
                },
                complete: () => {
                    $container.removeClass('updating');
                }
            });
        },
        
        /**
         * Refresh widget
         */
        refreshWidget: function(e) {
            e.preventDefault();
            
            const $widget = $(e.target).closest('.mt-widget');
            const widgetId = $widget.data('widget');
            
            $widget.addClass('updating');
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_refresh_debug_widget',
                    nonce: this.nonce,
                    widget_id: widgetId
                },
                success: (response) => {
                    if (response.success) {
                        $widget.find('.mt-widget-content').html(response.data.html);
                    }
                },
                complete: () => {
                    $widget.removeClass('updating');
                }
            });
        },
        
        /**
         * Download JSON data
         */
        downloadJSON: function(data, filename) {
            const blob = new Blob([data], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type = 'info') {
            const $notice = $(`
                <div class="notice notice-${type} is-dismissible mt-notification">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">${mt_debug.i18n.dismiss || 'Dismiss'}</span>
                    </button>
                </div>
            `);
            
            $('.wrap h1').first().after($notice);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                $notice.fadeOut(() => $notice.remove());
            }, 5000);
            
            // Manual dismiss
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(() => $notice.remove());
            });
        },
        
        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, m => map[m]);
        },
        
        /**
         * Copy system info to clipboard
         */
        copySystemInfo: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const $content = $('#system-info-export');
            
            if ($content.length) {
                $content[0].select();
                document.execCommand('copy');
                this.showNotification(mt_debug.i18n.copied || 'Copied to clipboard', 'success');
            }
        },
        
        /**
         * Export system info
         */
        exportSystemInfo: function(e) {
            e.preventDefault();
            
            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'mt_get_system_info',
                    nonce: this.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const filename = 'mt-sysinfo-' + new Date().toISOString().slice(0, 10) + '.json';
                        this.downloadJSON(JSON.stringify(response.data, null, 2), filename);
                    }
                }
            });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        if ($('.mt-debug-center').length) {
            MTDebugCenter.init();
        }
    });
    
})(jQuery);