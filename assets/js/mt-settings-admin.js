/**
 * Mobility Trailblazers Settings Admin JavaScript
 * 
 * @package MobilityTrailblazers
 * @since 2.5.27
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Media Library for Header Image
    var mediaUploader;
    
    $('#upload_header_image').on('click', function(e) {
        e.preventDefault();
        
        // If the uploader object has already been created, reopen it
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        
        // Create the media frame
        mediaUploader = wp.media({
            title: 'Choose Header Background Image',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected, run a callback
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            
            // Update the input field with the image URL
            $('#header_image_url').val(attachment.url);
            
            // Update the preview
            var preview = $('#header_image_preview');
            preview.attr('src', attachment.url);
            preview.parent('.mt-image-preview').show();
            
            // Mark form as changed
            $('input[name="submit"]').prop('disabled', false);
        });
        
        // Open the media frame
        mediaUploader.open();
    });
    
    // Clear header image
    $(document).on('click', '.mt-clear-image', function(e) {
        e.preventDefault();
        $('#header_image_url').val('');
        $('#header_image_preview').attr('src', '');
        $('.mt-image-preview').hide();
    });
    
    // Animation Effects Preview
    var animationCheckbox = $('input[name="mt_candidate_presentation[enable_animations]"]');
    var hoverCheckbox = $('input[name="mt_candidate_presentation[enable_hover_effects]"]');
    
    // Add preview button for animations
    if (animationCheckbox.length) {
        animationCheckbox.parent().append(
            ' <button type="button" class="button button-small mt-preview-animation">' +
            'Preview Animation</button>'
        );
    }
    
    // Preview animation on button click
    $(document).on('click', '.mt-preview-animation', function(e) {
        e.preventDefault();
        var $button = $(this);
        
        // Add animation class
        $button.addClass('mt-animate-preview');
        
        // Remove after animation completes
        setTimeout(function() {
            $button.removeClass('mt-animate-preview');
        }, 1000);
    });
    
    // Live preview of color changes
    $('input[name="mt_dashboard_settings[primary_color]"]').on('change', function() {
        var color = $(this).val();
        $('.mt-color-preview-primary').css('background-color', color);
    });
    
    $('input[name="mt_dashboard_settings[secondary_color]"]').on('change', function() {
        var color = $(this).val();
        $('.mt-color-preview-secondary').css('background-color', color);
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        var weights = {
            courage: parseFloat($('input[name="weight_courage"]').val()),
            innovation: parseFloat($('input[name="weight_innovation"]').val()),
            implementation: parseFloat($('input[name="weight_implementation"]').val()),
            relevance: parseFloat($('input[name="weight_relevance"]').val()),
            visibility: parseFloat($('input[name="weight_visibility"]').val())
        };
        
        // Check if weights are valid
        for (var key in weights) {
            if (isNaN(weights[key]) || weights[key] < 0 || weights[key] > 10) {
                alert('Please enter valid weights between 0 and 10');
                e.preventDefault();
                return false;
            }
        }
        
        // Warn about data deletion if checked
        if ($('input[name="mt_remove_data_on_uninstall"]').is(':checked')) {
            if (!confirm('WARNING: You have enabled data deletion on uninstall. This will permanently delete all plugin data when the plugin is removed. Are you sure?')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Add animation preview styles
    var animationStyles = '<style>' +
        '.mt-animate-preview {' +
        '    animation: mt-preview-pulse 1s ease-in-out;' +
        '}' +
        '@keyframes mt-preview-pulse {' +
        '    0% { transform: scale(1); }' +
        '    50% { transform: scale(1.1); background-color: #C1693C; color: white; }' +
        '    100% { transform: scale(1); }' +
        '}' +
        '.mt-color-preview-primary, .mt-color-preview-secondary {' +
        '    display: inline-block;' +
        '    width: 30px;' +
        '    height: 30px;' +
        '    border-radius: 4px;' +
        '    margin-left: 10px;' +
        '    vertical-align: middle;' +
        '    border: 1px solid #ccc;' +
        '}' +
        '</style>';
    
    $('head').append(animationStyles);
    
    // Add clear button next to header image input if image exists
    if ($('#header_image_url').val()) {
        $('#upload_header_image').after(
            ' <button type="button" class="button mt-clear-image">Clear Image</button>'
        );
    }
});