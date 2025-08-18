/**
 * Candidate Content Editor JavaScript
 * 
 * @package MobilityTrailblazers
 * @since 2.5.21
 */

(function($) {
    'use strict';
    
    var MTCandidateEditor = {
        currentPostId: null,
        
        init: function() {
            this.addEditButtons();
            this.bindEvents();
        },
        
        addEditButtons: function() {
            // Add edit button to each candidate row
            $('#the-list tr').each(function() {
                var $row = $(this);
                var postId = $row.find('input[name="post[]"]').val();
                
                if (postId) {
                    var $titleCol = $row.find('.column-title strong');
                    var editBtn = '<a class="mt-inline-edit-btn" data-post-id="' + postId + '" href="#">' +
                                 '<span class="dashicons dashicons-edit"></span> ' +
                                 mtCandidateEditor.i18n.edit +
                                 '</a>';
                    $titleCol.append(editBtn);
                }
            });
        },
        
        bindEvents: function() {
            var self = this;
            
            // Edit button click
            $(document).on('click', '.mt-inline-edit-btn', function(e) {
                e.preventDefault();
                var postId = $(this).data('post-id');
                self.openEditModal(postId);
            });
            
            // Tab switching
            $('.mt-tab-btn').on('click', function() {
                var tab = $(this).data('tab');
                self.switchTab(tab);
            });
            
            // Save button
            $('.mt-save-content').on('click', function() {
                self.saveContent();
            });
            
            // Cancel button
            $('.mt-cancel-edit').on('click', function() {
                self.closeModal();
            });
            
            // Close modal on background click
            $('#mt-inline-edit-modal').on('click', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });
            
            // ESC key to close
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#mt-inline-edit-modal').is(':visible')) {
                    self.closeModal();
                }
            });
        },
        
        openEditModal: function(postId) {
            var self = this;
            this.currentPostId = postId;
            
            // Show loading state
            $('#mt-inline-edit-modal').show();
            $('.mt-modal-body').html('<div class="spinner is-active" style="float:none;margin:50px auto;"></div>');
            
            // Load content via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mt_get_candidate_content',
                    post_id: postId,
                    nonce: mtCandidateEditor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.populateModal(response.data);
                    } else {
                        alert(response.data.message || mtCandidateEditor.i18n.error);
                        self.closeModal();
                    }
                },
                error: function() {
                    alert(mtCandidateEditor.i18n.error);
                    self.closeModal();
                }
            });
        },
        
        populateModal: function(data) {
            // Restore modal body HTML
            var modalBodyHTML = '<div class="mt-tab-content active" data-content="overview">' +
                              '<textarea id="mt-edit-overview" rows="10"></textarea>' +
                              '</div>' +
                              '<div class="mt-tab-content" data-content="criteria">' +
                              '<textarea id="mt-edit-criteria" rows="15"></textarea>' +
                              '</div>' +
                              '<div class="mt-tab-content" data-content="biography">' +
                              '<textarea id="mt-edit-biography" rows="10"></textarea>' +
                              '</div>';
            
            $('.mt-modal-body').html(modalBodyHTML);
            
            // Populate textareas
            $('#mt-edit-overview').val(data.overview || '');
            $('#mt-edit-criteria').val(data.criteria || '');
            $('#mt-edit-biography').val(data.biography || '');
            
            // Reset to first tab
            this.switchTab('overview');
        },
        
        switchTab: function(tab) {
            // Update tab buttons
            $('.mt-tab-btn').removeClass('active');
            $('.mt-tab-btn[data-tab="' + tab + '"]').addClass('active');
            
            // Update content
            $('.mt-tab-content').removeClass('active');
            $('.mt-tab-content[data-content="' + tab + '"]').addClass('active');
        },
        
        saveContent: function() {
            var self = this;
            var $saveBtn = $('.mt-save-content');
            
            // Show saving state
            $saveBtn.prop('disabled', true).text(mtCandidateEditor.i18n.saving);
            
            // Get active tab
            var activeTab = $('.mt-tab-btn.active').data('tab');
            var content = '';
            
            switch(activeTab) {
                case 'overview':
                    content = $('#mt-edit-overview').val();
                    break;
                case 'criteria':
                    content = $('#mt-edit-criteria').val();
                    break;
                case 'biography':
                    content = $('#mt-edit-biography').val();
                    break;
            }
            
            // Save via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mt_update_candidate_content',
                    post_id: self.currentPostId,
                    field: activeTab,
                    content: content,
                    nonce: mtCandidateEditor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $saveBtn.text(mtCandidateEditor.i18n.saved);
                        setTimeout(function() {
                            $saveBtn.prop('disabled', false).text(mtCandidateEditor.i18n.save);
                        }, 2000);
                    } else {
                        alert(response.data.message || mtCandidateEditor.i18n.error);
                        $saveBtn.prop('disabled', false).text(mtCandidateEditor.i18n.save);
                    }
                },
                error: function() {
                    alert(mtCandidateEditor.i18n.error);
                    $saveBtn.prop('disabled', false).text(mtCandidateEditor.i18n.save);
                }
            });
        },
        
        closeModal: function() {
            $('#mt-inline-edit-modal').hide();
            this.currentPostId = null;
        }
    };
    
    // Add AJAX handler for getting content
    $(document).ready(function() {
        // Add the get content handler
        if (typeof ajaxurl !== 'undefined') {
            $.post(ajaxurl, {
                action: 'mt_register_get_content_handler'
            });
        }
        
        MTCandidateEditor.init();
    });
    
})(jQuery);