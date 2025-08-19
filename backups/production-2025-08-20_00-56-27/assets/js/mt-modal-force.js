/**
 * Force modal visibility for Mobility Trailblazers
 * This is a standalone script to ensure modals work regardless of other scripts
 */
(function() {
    'use strict';
    // Wait for DOM to be fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initModals);
    } else {
        initModals();
    }
    function initModals() {
        // Create overlay if it doesn't exist
        var overlay = document.getElementById('mt-modal-overlay');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'mt-modal-overlay';
            overlay.style.cssText = 'display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 999998;';
            document.body.appendChild(overlay);
        }
        // Auto-assign button
        var autoAssignBtn = document.getElementById('mt-auto-assign-btn');
        if (autoAssignBtn) {
            autoAssignBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showModal('mt-auto-assign-modal');
            });
        }
        // Manual assign button
        var manualAssignBtn = document.getElementById('mt-manual-assign-btn');
        if (manualAssignBtn) {
            manualAssignBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showModal('mt-manual-assign-modal');
            });
        }
        // Close buttons
        var closeButtons = document.querySelectorAll('.mt-modal-close');
        closeButtons.forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var modal = this.closest('.mt-modal');
                if (modal) {
                    hideModal(modal.id);
                }
            });
        });
        // Click overlay to close
        overlay.addEventListener('click', function() {
            hideAllModals();
        });
    }
    function showModal(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) {
            // Error logging removed for production
            return;
        }
        // Show overlay
        var overlay = document.getElementById('mt-modal-overlay');
        if (overlay) {
            overlay.style.display = 'block';
        }
        // Position modal content absolutely in center
        var content = modal.querySelector('.mt-modal-content');
        if (content) {
            // Reset all styles first
            content.style.cssText = '';
            // Apply new styles
            content.style.position = 'fixed';
            content.style.top = '50%';
            content.style.left = '50%';
            content.style.transform = 'translate(-50%, -50%)';
            content.style.background = 'white';
            content.style.padding = '30px';
            content.style.borderRadius = '8px';
            content.style.boxShadow = '0 10px 40px rgba(0,0,0,0.3)';
            content.style.maxWidth = '600px';
            content.style.width = '90%';
            content.style.maxHeight = '90vh';
            content.style.overflowY = 'auto';
            content.style.zIndex = '999999';
            content.style.display = 'block';
        }
        // Show modal
        modal.style.display = 'block';
        // Force a reflow to ensure styles are applied
        void modal.offsetHeight;
    }
    function hideModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            var content = modal.querySelector('.mt-modal-content');
            if (content) {
                content.style.display = 'none';
            }
        }
        // Hide overlay
        var overlay = document.getElementById('mt-modal-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }
    function hideAllModals() {
        hideModal('mt-auto-assign-modal');
        hideModal('mt-manual-assign-modal');
    }
    // Also bind to jQuery if available for compatibility
    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function($) {
            // Override any existing handlers
            $('#mt-auto-assign-btn').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showModal('mt-auto-assign-modal');
                return false;
            });
            $('#mt-manual-assign-btn').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                showModal('mt-manual-assign-modal');
                return false;
            });
        });
    }
})();
