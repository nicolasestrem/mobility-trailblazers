/**
 * Candidate Content Editor JavaScript with Rich Text Support
 * 
 * @package MobilityTrailblazers
 * @since 2.5.21
 */
(function($) {
    'use strict';
    
    var MTCandidateEditor = {
        currentPostId: null,
        originalContent: {},
        hasUnsavedChanges: false,
        activeEditors: {},
        
        init: function() {
            this.bindEvents();
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
            $(document).on('click', '.mt-tab-btn', function() {
                var tab = $(this).data('tab');
                self.switchTab(tab);
            });
            
            // Save button
            $(document).on('click', '.mt-save-content', function() {
                self.saveContent();
            });
            
            // Cancel button
            $(document).on('click', '.mt-cancel-edit', function() {
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
            
            // Show modal
            $('#mt-inline-edit-modal').show();
            
            // Show loading state
            $('.mt-modal-body').html('<div style="text-align:center;padding:50px;">Loading...</div>');
            
            // Load content via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mt_get_candidate_content',
                    post_id: postId,
                    nonce: $('#mt_candidate_editor_nonce').val() || mtCandidateEditor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.populateModal(response.data);
                    } else {
                        alert(response.data.message || (mt_ajax && mt_ajax.i18n && mt_ajax.i18n.error_loading_content ? mt_ajax.i18n.error_loading_content : 'Error loading content'));
                        self.closeModal();
                    }
                },
                error: function() {
                    alert(mt_ajax && mt_ajax.i18n && mt_ajax.i18n.error_loading_content ? mt_ajax.i18n.error_loading_content : 'Error loading content');
                    self.closeModal();
                }
            });
        },
        
        populateModal: function(data) {
            var self = this;
            
            // Store original content
            self.originalContent = {
                overview: data.overview || '',
                criteria: data.criteria || ''
            };
            
            // Reset unsaved changes flag
            self.hasUnsavedChanges = false;
            
            // Build modal content with rich text editors
            var modalBodyHTML = 
                '<div class="mt-tab-content active" data-content="overview">' +
                    '<div class="mt-editor-container">' +
                        '<textarea id="mt-edit-overview" class="mt-rich-editor" rows="10" style="width:100%;"></textarea>' +
                    '</div>' +
                '</div>' +
                '<div class="mt-tab-content" data-content="criteria" style="display:none;">' +
                    '<div style="margin-bottom:10px;">' +
                        '<button type="button" class="button mt-insert-template">Insert Criteria Template</button>' +
                    '</div>' +
                    '<div class="mt-editor-container">' +
                        '<textarea id="mt-edit-criteria" class="mt-rich-editor" rows="15" style="width:100%;"></textarea>' +
                    '</div>' +
                    '<div style="margin-top:10px;padding:10px;background:#f0f0f0;border-radius:3px;font-size:12px;">' +
                        '<strong>Format Tips:</strong><br>' +
                        'Use bold text for criteria headers (e.g., <strong>Mut & Pioniergeist:</strong>)<br>' +
                        'Add line breaks between sections for better readability' +
                    '</div>' +
                '</div>';
            
            $('.mt-modal-body').html(modalBodyHTML);
            
            // Set initial content in textareas
            $('#mt-edit-overview').val(data.overview || '');
            $('#mt-edit-criteria').val(data.criteria || '');
            
            // Initialize TinyMCE editors with delay to ensure DOM is ready
            setTimeout(function() {
                self.initRichEditors();
            }, 100);
            
            // Bind template button
            $('.mt-insert-template').on('click', function() {
                self.insertCriteriaTemplate();
            });
            
            // Reset to first tab
            this.switchTab('overview');
        },
        
        initRichEditors: function() {
            var self = this;
            
            // Destroy existing editors if any
            self.destroyEditors();
            
            // Initialize WordPress editor for overview
            var overviewSettings = {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,wptextpattern',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                    toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                    height: 300,
                    setup: function(editor) {
                        self.activeEditors.overview = editor;
                        editor.on('change keyup', function() {
                            self.hasUnsavedChanges = true;
                        });
                    }
                },
                quicktags: true,
                mediaButtons: true
            };
            
            // Initialize WordPress editor for criteria
            var criteriaSettings = {
                tinymce: {
                    wpautop: true,
                    plugins: 'lists,link,wordpress,wplink,wptextpattern',
                    toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                    toolbar2: 'strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                    height: 400,
                    setup: function(editor) {
                        self.activeEditors.criteria = editor;
                        editor.on('change keyup', function() {
                            self.hasUnsavedChanges = true;
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            };
            
            // Use wp.editor API if available (WordPress 4.8+)
            if (typeof wp !== 'undefined' && wp.editor) {
                // Remove any existing instance first
                wp.editor.remove('mt-edit-overview');
                wp.editor.remove('mt-edit-criteria');
                
                // Initialize editors
                wp.editor.initialize('mt-edit-overview', overviewSettings);
                wp.editor.initialize('mt-edit-criteria', criteriaSettings);
            } 
            // Fallback to direct TinyMCE initialization
            else if (typeof tinymce !== 'undefined') {
                // Remove existing instances
                tinymce.remove('#mt-edit-overview');
                tinymce.remove('#mt-edit-criteria');
                
                // Init overview editor
                tinymce.init({
                    selector: '#mt-edit-overview',
                    height: 300,
                    menubar: false,
                    plugins: 'lists link wordpress wplink',
                    toolbar: 'formatselect | bold italic | bullist numlist | link unlink | removeformat',
                    content_css: false,
                    setup: function(editor) {
                        self.activeEditors.overview = editor;
                        editor.on('change keyup', function() {
                            self.hasUnsavedChanges = true;
                        });
                    }
                });
                
                // Init criteria editor
                tinymce.init({
                    selector: '#mt-edit-criteria',
                    height: 400,
                    menubar: false,
                    plugins: 'lists link wordpress wplink',
                    toolbar: 'formatselect | bold italic | bullist numlist | link unlink | removeformat',
                    content_css: false,
                    setup: function(editor) {
                        self.activeEditors.criteria = editor;
                        editor.on('change keyup', function() {
                            self.hasUnsavedChanges = true;
                        });
                    }
                });
            }
            // Plain textarea fallback
            else {
                console.warn('WordPress editor not available, using plain textareas');
                
                $('#mt-edit-overview').on('input', function() {
                    self.hasUnsavedChanges = true;
                });
                
                $('#mt-edit-criteria').on('input', function() {
                    self.hasUnsavedChanges = true;
                });
            }
        },
        
        destroyEditors: function() {
            // Use wp.editor API if available
            if (typeof wp !== 'undefined' && wp.editor) {
                wp.editor.remove('mt-edit-overview');
                wp.editor.remove('mt-edit-criteria');
            }
            // Fallback to TinyMCE
            else if (typeof tinymce !== 'undefined') {
                tinymce.remove('#mt-edit-overview');
                tinymce.remove('#mt-edit-criteria');
            }
            this.activeEditors = {};
        },
        
        insertCriteriaTemplate: function() {
            var template = '<strong>Mut & Pioniergeist:</strong><br>\n' +
                          '[Content here]<br><br>\n' +
                          '<strong>Innovationsgrad:</strong><br>\n' +
                          '[Content here]<br><br>\n' +
                          '<strong>Umsetzungskraft & Wirkung:</strong><br>\n' +
                          '[Content here]<br><br>\n' +
                          '<strong>Relevanz für die Mobilitätswende:</strong><br>\n' +
                          '[Content here]<br><br>\n' +
                          '<strong>Vorbildfunktion & Sichtbarkeit:</strong><br>\n' +
                          '[Content here]';
            
            // Try wp.editor first
            if (typeof wp !== 'undefined' && wp.editor) {
                var currentContent = wp.editor.getContent('mt-edit-criteria');
                if (currentContent && !confirm('This will replace the current content. Continue?')) {
                    return;
                }
                wp.editor.setContent(template, 'mt-edit-criteria');
                this.hasUnsavedChanges = true;
            }
            // Try TinyMCE editor
            else if (this.activeEditors.criteria) {
                var currentContent = this.activeEditors.criteria.getContent();
                if (currentContent && !confirm('This will replace the current content. Continue?')) {
                    return;
                }
                this.activeEditors.criteria.setContent(template);
                this.hasUnsavedChanges = true;
            } 
            // Fallback for plain textarea
            else {
                var $textarea = $('#mt-edit-criteria');
                var currentContent = $textarea.val();
                
                if (currentContent && !confirm('This will replace the current content. Continue?')) {
                    return;
                }
                
                $textarea.val(template).trigger('input');
            }
        },
        
        switchTab: function(tab) {
            // Update tab buttons
            $('.mt-tab-btn').removeClass('active');
            $('.mt-tab-btn[data-tab="' + tab + '"]').addClass('active');
            
            // Update content
            $('.mt-tab-content').hide();
            $('.mt-tab-content[data-content="' + tab + '"]').show();
            
            // Refresh editor if needed
            if (this.activeEditors[tab]) {
                this.activeEditors[tab].focus();
            }
        },
        
        getEditorContent: function(editorId) {
            // Try wp.editor API first
            if (typeof wp !== 'undefined' && wp.editor) {
                return wp.editor.getContent(editorId);
            }
            // Try TinyMCE
            else if (typeof tinymce !== 'undefined') {
                var editor = tinymce.get(editorId);
                if (editor) {
                    return editor.getContent();
                }
            }
            // Fallback to textarea value
            return $('#' + editorId).val();
        },
        
        saveContent: function() {
            var self = this;
            var $saveBtn = $('.mt-save-content');
            
            // Show saving state
            $saveBtn.prop('disabled', true).text('Saving...');
            
            // Get active tab
            var activeTab = $('.mt-tab-btn.active').data('tab');
            var content = '';
            
            // Get content from the appropriate editor
            if (activeTab === 'overview') {
                content = self.getEditorContent('mt-edit-overview');
            } else if (activeTab === 'criteria') {
                content = self.getEditorContent('mt-edit-criteria');
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
                    nonce: $('#mt_candidate_editor_nonce').val() || mtCandidateEditor.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update original content with saved value
                        self.originalContent[activeTab] = content;
                        self.hasUnsavedChanges = false;
                        
                        $saveBtn.text('Saved!');
                        
                        // Update the main form fields if they exist
                        if (activeTab === 'overview' && $('#mt_overview').length) {
                            // Update textarea
                            $('#mt_overview').val(content);
                            // Update TinyMCE if exists
                            if (typeof wp !== 'undefined' && wp.editor) {
                                wp.editor.setContent(content, 'mt_overview');
                            } else if (typeof tinymce !== 'undefined' && tinymce.get('mt_overview')) {
                                tinymce.get('mt_overview').setContent(content);
                            }
                        } else if (activeTab === 'criteria' && $('#mt_evaluation_criteria').length) {
                            // Update textarea
                            $('#mt_evaluation_criteria').val(content);
                            // Update TinyMCE if exists
                            if (typeof wp !== 'undefined' && wp.editor) {
                                wp.editor.setContent(content, 'mt_evaluation_criteria');
                            } else if (typeof tinymce !== 'undefined' && tinymce.get('mt_evaluation_criteria')) {
                                tinymce.get('mt_evaluation_criteria').setContent(content);
                            }
                        }
                        
                        setTimeout(function() {
                            $saveBtn.prop('disabled', false).text('Save Changes');
                        }, 2000);
                    } else {
                        alert(response.data.message || (mt_ajax && mt_ajax.i18n && mt_ajax.i18n.error_saving_content ? mt_ajax.i18n.error_saving_content : 'Error saving content'));
                        $saveBtn.prop('disabled', false).text('Save Changes');
                    }
                },
                error: function() {
                    alert(mt_ajax && mt_ajax.i18n && mt_ajax.i18n.error_saving_content ? mt_ajax.i18n.error_saving_content : 'Error saving content');
                    $saveBtn.prop('disabled', false).text('Save Changes');
                }
            });
        },
        
        closeModal: function() {
            // Check for unsaved changes
            if (this.hasUnsavedChanges) {
                if (!confirm(mt_ajax && mt_ajax.i18n && mt_ajax.i18n.unsaved_changes_warning ? mt_ajax.i18n.unsaved_changes_warning : 'You have unsaved changes. Are you sure you want to close?')) {
                    return;
                }
            }
            
            // Destroy editors before closing
            this.destroyEditors();
            
            $('#mt-inline-edit-modal').hide();
            this.currentPostId = null;
            this.hasUnsavedChanges = false;
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize modal editor if modal exists
        if ($('#mt-inline-edit-modal').length) {
            MTCandidateEditor.init();
        }
    });
    
    // Make it globally available
    window.MTCandidateEditor = MTCandidateEditor;
    
})(jQuery);