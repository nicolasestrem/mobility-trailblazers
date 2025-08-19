/**
 * Mobility Trailblazers Settings Admin JavaScript
 * 
 * @package MobilityTrailblazers
 * @since 2.5.29
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
            var previewContainer = $('.mt-image-preview');
            preview.attr('src', attachment.url);
            previewContainer.show();
            // Show clear button if not already visible
            var clearBtn = previewContainer.find('.mt-clear-image');
            if (clearBtn.length === 0) {
                previewContainer.append(' <button type="button" class="button mt-clear-image" style="margin-top: 10px;">Remove Image</button>');
            } else {
                clearBtn.show();
            }
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
        $(this).hide();
        // Mark form as changed
        $('input[name="submit"]').prop('disabled', false);
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
    // Animation Speed Preview
    $(document).on('click', '.mt-preview-animation-speed', function(e) {
        e.preventDefault();
        const speed = $('#animation_speed').val();
        const $preview = $('<div class="mt-animation-preview-box">Animation Preview</div>');
        // Remove any existing preview
        $('.mt-animation-preview-box').remove();
        // Add preview box
        $(this).after($preview);
        // Apply animation with selected speed
        $preview.addClass('mt-anim-' + speed + ' mtFadeInUp');
        // Remove after animation
        setTimeout(function() {
            $preview.fadeOut(function() {
                $(this).remove();
            });
        }, 2000);
    });
    // Animation Style Preview
    $(document).on('click', '.mt-preview-animation-style', function(e) {
        e.preventDefault();
        const style = $('#animation_style').val();
        const $preview = $('<div class="mt-animation-preview-box">Animation Preview</div>');
        // Remove any existing preview
        $('.mt-animation-preview-box').remove();
        // Add preview box
        $(this).after($preview);
        // Map style to animation class
        const animationMap = {
            'fade': 'mtFadeIn',
            'slide': 'mtSlideInLeft',
            'zoom': 'mtZoomIn',
            'rotate': 'mtRotateIn',
            'flip': 'mtFlipInY',
            'bounce': 'mtZoomInBounce'
        };
        // Apply animation
        $preview.addClass(animationMap[style] || 'mtFadeIn');
        // Remove after animation
        setTimeout(function() {
            $preview.fadeOut(function() {
                $(this).remove();
            });
        }, 2000);
    });
    // Add enhanced animation preview styles
    const enhancedStyles = '<style>' +
        '.mt-animation-preview-box {' +
        '    display: inline-block;' +
        '    margin-left: 10px;' +
        '    padding: 10px 20px;' +
        '    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);' +
        '    color: white;' +
        '    border-radius: 5px;' +
        '    font-weight: bold;' +
        '    box-shadow: 0 4px 6px rgba(0,0,0,0.1);' +
        '}' +
        '@keyframes mtFadeIn { from { opacity: 0; } to { opacity: 1; } }' +
        '@keyframes mtFadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }' +
        '@keyframes mtSlideInLeft { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }' +
        '@keyframes mtZoomIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }' +
        '@keyframes mtRotateIn { from { opacity: 0; transform: rotate(-180deg) scale(0.8); } to { opacity: 1; transform: rotate(0) scale(1); } }' +
        '@keyframes mtFlipInY { from { opacity: 0; transform: perspective(600px) rotateY(90deg); } to { opacity: 1; transform: perspective(600px) rotateY(0); } }' +
        '@keyframes mtZoomInBounce {' +
        '    0% { opacity: 0; transform: scale(0.3); }' +
        '    50% { transform: scale(1.05); }' +
        '    70% { transform: scale(0.95); }' +
        '    100% { opacity: 1; transform: scale(1); }' +
        '}' +
        '.mtFadeIn { animation: mtFadeIn 0.5s ease forwards; }' +
        '.mtFadeInUp { animation: mtFadeInUp 0.5s ease forwards; }' +
        '.mtSlideInLeft { animation: mtSlideInLeft 0.5s ease forwards; }' +
        '.mtZoomIn { animation: mtZoomIn 0.5s ease forwards; }' +
        '.mtRotateIn { animation: mtRotateIn 0.5s ease forwards; }' +
        '.mtFlipInY { animation: mtFlipInY 0.5s ease forwards; }' +
        '.mtZoomInBounce { animation: mtZoomInBounce 0.8s ease forwards; }' +
        '.mt-anim-instant { animation-duration: 0.1s !important; }' +
        '.mt-anim-fast { animation-duration: 0.2s !important; }' +
        '.mt-anim-normal { animation-duration: 0.3s !important; }' +
        '.mt-anim-slow { animation-duration: 0.5s !important; }' +
        '.mt-anim-slower { animation-duration: 0.8s !important; }' +
        '</style>';
    $('head').append(enhancedStyles);
});
