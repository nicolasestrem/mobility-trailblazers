/**
 * Fix WordPress Editors on Candidate Edit Page
 * 
 * @package MobilityTrailblazers
 * @since 2.5.37
 */
(function($) {
    'use strict';
    
    // Function to reinitialize broken editors
    function fixBrokenEditors() {
        // Check if we're on the candidate edit page
        if (!$('body').hasClass('post-type-mt_candidate')) {
            return;
        }
        
        // Wait for TinyMCE to be available
        if (typeof tinymce === 'undefined' || typeof wp === 'undefined' || !wp.editor) {
            console.log('Editor libraries not ready, retrying...');
            setTimeout(fixBrokenEditors, 500);
            return;
        }
        
        // Check if evaluation criteria editor exists but is not initialized
        // The criteria editor now has a unique ID based on post ID
        var postId = $('#post_ID').val();
        var criteriaEditorId = postId ? 'mt_criteria_editor_' + postId : 'mt_evaluation_criteria';
        var overviewEditorId = 'mt_overview';
        
        // Function to initialize an editor if it's broken
        function initializeEditorIfBroken(editorId) {
            var $textarea = $('#' + editorId);
            
            if ($textarea.length === 0) {
                return;
            }
            
            // Check if TinyMCE instance exists
            var editor = tinymce.get(editorId);
            
            // If editor doesn't exist or is not properly initialized
            if (!editor || !editor.initialized || $('#' + editorId + '_ifr').length === 0) {
                console.log('Fixing broken editor: ' + editorId);
                
                // Remove any existing broken instance
                if (editor) {
                    tinymce.remove('#' + editorId);
                    wp.editor.remove(editorId);
                }
                
                // Reinitialize with full settings
                var settings = {
                    tinymce: {
                        wpautop: true,
                        plugins: 'charmap,colorpicker,hr,lists,media,paste,tabfocus,textcolor,fullscreen,wordpress,wpautoresize,wpeditimage,wpemoji,wpgallery,wplink,wpdialogs,wptextpattern,wpview',
                        toolbar1: 'formatselect,bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,wp_more,spellchecker,fullscreen,wp_adv',
                        toolbar2: 'underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                        toolbar3: '',
                        toolbar4: '',
                        tabfocus_elements: 'content-html,save-post',
                        height: (editorId === criteriaEditorId) ? 400 : 300
                    },
                    quicktags: true,
                    mediaButtons: (editorId === overviewEditorId)
                };
                
                // Initialize the editor
                wp.editor.initialize(editorId, settings);
                
                // Set content after initialization
                setTimeout(function() {
                    var newEditor = tinymce.get(editorId);
                    if (newEditor && $textarea.val()) {
                        newEditor.setContent($textarea.val());
                    }
                }, 500);
            }
        }
        
        // Fix both editors
        initializeEditorIfBroken(overviewEditorId);
        initializeEditorIfBroken(criteriaEditorId);
    }
    
    // Run when document is ready
    $(document).ready(function() {
        // Only run on admin pages
        if (typeof pagenow !== 'undefined' && pagenow === 'mt_candidate') {
            // Give WordPress time to attempt its own initialization
            setTimeout(fixBrokenEditors, 1000);
        }
    });
    
    // Also try on window load
    $(window).on('load', function() {
        if ($('body').hasClass('post-type-mt_candidate')) {
            setTimeout(fixBrokenEditors, 1500);
        }
    });
    
})(jQuery);