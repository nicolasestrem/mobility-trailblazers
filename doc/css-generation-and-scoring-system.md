# CSS Generation and Interactive Scoring System

**Version:** 2.0.8  
**Last Updated:** June 24, 2025

## Overview

The Mobility Trailblazers plugin now features a comprehensive CSS generation system that provides dynamic styling for all components and an interactive scoring system with multiple interface options. This system allows for flexible presentation of candidates and evaluation forms while maintaining consistent branding and user experience.

## Table of Contents
1. [CSS Generation System](#css-generation-system)
2. [Layout System](#layout-system)
3. [Interactive Scoring System](#interactive-scoring-system)
4. [Implementation Details](#implementation-details)
5. [Configuration Options](#configuration-options)
6. [JavaScript Functionality](#javascript-functionality)
7. [Troubleshooting](#troubleshooting)

## CSS Generation System

### Core Architecture

The CSS generation system is built around three main methods in `includes/core/class-mt-shortcodes.php`:

#### 1. `generate_dashboard_custom_css()`
Generates CSS for the jury dashboard with layout-specific styles.

```php
private function generate_dashboard_custom_css() {
    $settings = get_option('mt_dashboard_settings', []);
    $primary_color = $settings['primary_color'] ?? '#667eea';
    $secondary_color = $settings['secondary_color'] ?? '#764ba2';
    
    // Base CSS with color variables
    $css = "...";
    
    // Layout-specific styles
    $card_layout = $settings['card_layout'] ?? 'grid';
    
    // Generate layout CSS based on settings
    return $css;
}
```

#### 2. `generate_candidates_grid_css()`
Provides styling for the candidates grid shortcode.

```php
private function generate_candidates_grid_css() {
    $settings = get_option('mt_dashboard_settings', []);
    $presentation = get_option('mt_candidate_presentation', []);
    $primary_color = $settings['primary_color'] ?? '#667eea';
    
    $css = "
    .mt-candidate-grid-item:hover {
        border-color: {$primary_color};
    }
    .mt-category-tag {
        background: {$primary_color};
        color: white;
    }
    ";
    
    // Apply photo styles
    if (($presentation['photo_style'] ?? '') === 'circle') {
        $css .= ".mt-candidate-grid-item .mt-candidate-photo { border-radius: 50%; }";
    }
    
    return $css;
}
```

#### 3. `generate_stats_custom_css()`
Styles for evaluation statistics display.

```php
private function generate_stats_custom_css() {
    $settings = get_option('mt_dashboard_settings', []);
    $primary_color = $settings['primary_color'] ?? '#667eea';
    $secondary_color = $settings['secondary_color'] ?? '#764ba2';
    
    $css = "
    .mt-stat-number {
        color: {$primary_color};
    }
    .mt-bar-fill {
        background: linear-gradient(to right, {$primary_color}, {$secondary_color});
    }
    .mt-progress-mini-fill {
        background: {$primary_color};
    }
    ";
    
    return $css;
}
```

### Integration Points

CSS generation is integrated into shortcode rendering:

```php
public function render_jury_dashboard($atts) {
    // ... existing code ...
    
    // Output custom CSS
    echo '<style type="text/css">' . $this->generate_dashboard_custom_css() . '</style>';
    
    // Include template
    include MT_PLUGIN_DIR . 'templates/frontend/jury-dashboard.php';
    
    return ob_get_clean();
}
```

## Layout System

### Candidate Card Layouts

#### 1. Grid Layout (`card_layout: 'grid'`)
```css
.mt-candidates-list.mt-candidates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.mt-candidates-grid.columns-2 {
    grid-template-columns: repeat(2, 1fr);
}

.mt-candidates-grid.columns-3 {
    grid-template-columns: repeat(3, 1fr);
}

.mt-candidates-grid.columns-4 {
    grid-template-columns: repeat(4, 1fr);
}

@media (max-width: 768px) {
    .mt-candidates-grid.columns-2,
    .mt-candidates-grid.columns-3,
    .mt-candidates-grid.columns-4 {
        grid-template-columns: 1fr;
    }
}
```

#### 2. List Layout (`card_layout: 'list'`)
```css
.mt-candidates-list.mt-candidates-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.mt-candidates-list .mt-candidate-card {
    display: flex;
    align-items: center;
    padding: 20px;
    gap: 20px;
}

.mt-candidates-list .mt-candidate-header {
    padding: 0;
    border: none;
    background: transparent;
}

.mt-candidates-list .mt-candidate-body {
    padding: 0;
    flex: 1;
}
```

#### 3. Compact Layout (`card_layout: 'compact'`)
```css
.mt-candidates-list.mt-candidates-compact {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.mt-candidates-compact .mt-candidate-card {
    display: flex;
    align-items: center;
    padding: 15px;
    gap: 15px;
    min-height: auto;
}

.mt-candidates-compact .mt-candidate-header {
    padding: 0;
    border: none;
    background: transparent;
    flex: 0 0 auto;
}

.mt-candidates-compact .mt-candidate-body {
    padding: 0;
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 15px;
}
```

### Profile Layouts

#### 1. Side-by-Side (`profile_layout: 'side-by-side'`)
```css
.mt-layout-side-by-side .mt-candidate-profile {
    display: grid;
    grid-template-columns: auto 1fr;
    gap: 30px;
    align-items: start;
}

.mt-layout-side-by-side .mt-candidate-details {
    text-align: left;
}

.mt-layout-side-by-side .mt-candidate-details h2 {
    text-align: left;
    margin: 0 0 15px 0;
}
```

#### 2. Stacked (`profile_layout: 'stacked'`)
```css
.mt-layout-stacked .mt-candidate-profile {
    grid-template-columns: 1fr;
    text-align: center;
}

.mt-layout-stacked .mt-candidate-photo {
    margin: 0 auto;
}

.mt-layout-stacked .mt-candidate-details {
    text-align: center;
}
```

#### 3. Card (`profile_layout: 'card'`)
```css
.mt-layout-card {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.mt-layout-card .mt-candidate-profile {
    background: rgba(255,255,255,0.9);
    border-radius: 8px;
    padding: 20px;
    margin: 20px;
}

.mt-layout-card .mt-candidate-details {
    text-align: left;
}
```

#### 4. Minimal (`profile_layout: 'minimal'`)
```css
.mt-layout-minimal .mt-candidate-photo-wrap {
    display: none;
}

.mt-layout-minimal .mt-candidate-profile {
    grid-template-columns: 1fr;
}

.mt-layout-minimal .mt-candidate-details {
    text-align: left;
}
```

## Interactive Scoring System

### Scoring Interface Options

#### 1. Slider (`scoring_style: 'slider'`)
Default scoring method with range input and visual marks.

```html
<div class="mt-score-slider-wrapper">
    <input type="range" 
           name="courage_score" 
           class="mt-score-slider" 
           min="0" 
           max="10" 
           value="5"
           data-criterion="courage">
    <div class="mt-score-marks">
        <span class="mt-score-mark" data-value="0">0</span>
        <!-- ... marks 1-9 ... -->
        <span class="mt-score-mark" data-value="10">10</span>
    </div>
</div>
```

#### 2. Star Rating (`scoring_style: 'stars'`)
Interactive 10-star rating system.

```html
<div class="mt-star-rating" data-criterion="courage">
    <span class="dashicons dashicons-star-empty" data-value="1"></span>
    <span class="dashicons dashicons-star-empty" data-value="2"></span>
    <!-- ... stars 3-9 ... -->
    <span class="dashicons dashicons-star-empty" data-value="10"></span>
    <input type="hidden" name="courage_score" value="5" />
</div>
```

CSS for star rating:
```css
.mt-scoring-control .mt-star-rating {
    display: flex;
    gap: 5px;
    font-size: 30px;
    color: #ddd;
    cursor: pointer;
}

.mt-scoring-control .mt-star-rating .dashicons {
    transition: color 0.2s;
}

.mt-scoring-control .mt-star-rating .dashicons.active,
.mt-scoring-control .mt-star-rating .dashicons:hover {
    color: #f39c12;
}
```

#### 3. Numeric Input (`scoring_style: 'numeric'`)
Direct number input with validation.

```html
<div class="mt-numeric-input">
    <input type="number" 
           name="courage_score" 
           min="0" 
           max="10" 
           value="5"
           class="mt-score-input" />
    <span class="mt-score-label">/ 10</span>
</div>
```

CSS for numeric input:
```css
.mt-scoring-control .mt-numeric-input {
    display: flex;
    align-items: center;
    gap: 10px;
}

.mt-scoring-control .mt-numeric-input input {
    width: 80px;
    padding: 10px;
    font-size: 20px;
    text-align: center;
    border: 2px solid #ddd;
    border-radius: 5px;
}
```

#### 4. Button Group (`scoring_style: 'buttons'`)
11-button selection (0-10).

```html
<div class="mt-button-group" data-criterion="courage">
    <button type="button" class="mt-score-button" data-value="0">0</button>
    <button type="button" class="mt-score-button" data-value="1">1</button>
    <!-- ... buttons 2-9 ... -->
    <button type="button" class="mt-score-button" data-value="10">10</button>
    <input type="hidden" name="courage_score" value="5" />
</div>
```

CSS for button group:
```css
.mt-scoring-control .mt-button-group {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

.mt-scoring-control .mt-score-button {
    padding: 8px 15px;
    border: 2px solid #ddd;
    background: white;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.2s;
}

.mt-scoring-control .mt-score-button:hover {
    border-color: {$primary_color};
    color: {$primary_color};
}

.mt-scoring-control .mt-score-button.active {
    background: {$primary_color};
    border-color: {$primary_color};
    color: white;
}
```

## Implementation Details

### Template Integration

#### Jury Dashboard Template
```php
// Apply layout classes
$layout_class = 'mt-candidates-' . $dashboard_settings['card_layout'];

// Use in HTML
<div class="mt-candidates-list <?php echo esc_attr($layout_class); ?>" id="mt-candidates-list">
```

#### Evaluation Form Template
```php
// Apply layout classes
$showcase_class = 'mt-candidate-showcase mt-layout-' . $presentation_settings['profile_layout'];
$photo_class = 'mt-candidate-photo mt-photo-' . $presentation_settings['photo_style'] . ' mt-photo-' . $presentation_settings['photo_size'];

// Add animation classes if enabled
if (!empty($presentation_settings['enable_animations'])) {
    $showcase_class .= ' mt-animated';
}

// Use in HTML
<div class="<?php echo esc_attr($showcase_class); ?>">
```

### Form Style Variations

#### List Form Style
```css
.mt-form-list .mt-criteria-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.mt-form-list .mt-criterion-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
}

.mt-form-list .mt-criterion-header {
    flex: 0 0 300px;
}

.mt-form-list .mt-scoring-control {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 20px;
}
```

#### Compact Form Style
```css
.mt-form-compact .mt-criterion-card {
    padding: 15px;
}

.mt-form-compact .mt-criterion-header {
    margin-bottom: 10px;
}

.mt-form-compact .mt-criterion-icon {
    font-size: 20px;
}

.mt-form-compact .mt-criterion-label {
    font-size: 16px;
}

.mt-form-compact .mt-criterion-description {
    display: none;
}
```

#### Wizard Form Style
```css
.mt-form-wizard .mt-criteria-grid {
    position: relative;
}

.mt-form-wizard .mt-criterion-card {
    display: none;
    animation: fadeIn 0.3s ease-in;
}

.mt-form-wizard .mt-criterion-card.active {
    display: block;
}

.mt-form-wizard .mt-wizard-navigation {
    display: flex;
    justify-content: space-between;
    margin-top: 30px;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
```

## Configuration Options

### Dashboard Settings (`mt_dashboard_settings`)
```php
$dashboard_settings = [
    'header_style' => 'gradient', // gradient, solid, image
    'primary_color' => '#667eea',
    'secondary_color' => '#764ba2',
    'progress_bar_style' => 'rounded', // rounded, square, striped
    'show_welcome_message' => 1,
    'show_progress_bar' => 1,
    'show_stats_cards' => 1,
    'show_search_filter' => 1,
    'card_layout' => 'grid', // grid, list, compact
    'intro_text' => ''
];
```

### Candidate Presentation Settings (`mt_candidate_presentation`)
```php
$presentation_settings = [
    'profile_layout' => 'side-by-side', // side-by-side, stacked, card, minimal
    'photo_style' => 'rounded', // square, circle, rounded
    'photo_size' => 'medium', // small, medium, large
    'show_organization' => 1,
    'show_position' => 1,
    'show_category' => 1,
    'show_innovation_summary' => 1,
    'show_full_bio' => 1,
    'form_style' => 'cards', // cards, list, compact, wizard
    'scoring_style' => 'slider', // slider, stars, numeric, buttons
    'enable_animations' => 1,
    'enable_hover_effects' => 1
];
```

## JavaScript Functionality

### Event Handling

All scoring methods use event delegation for dynamic content support:

```javascript
// Star rating functionality
$(document).on('click', '.mt-star-rating .dashicons', function() {
    const $star = $(this);
    const value = $star.data('value');
    const $rating = $star.parent();
    
    $rating.find('.dashicons').removeClass('active');
    $rating.find('.dashicons').each(function() {
        if ($(this).data('value') <= value) {
            $(this).addClass('active');
        }
    });
    
    $rating.find('input[type="hidden"]').val(value);
    updateScoreDisplay($star.closest('.mt-criterion-card'), value);
});

// Button scoring functionality
$(document).on('click', '.mt-score-button', function() {
    const $button = $(this);
    const value = $button.data('value');
    const $group = $button.parent();
    
    $group.find('.mt-score-button').removeClass('active');
    $button.addClass('active');
    
    $group.find('input[type="hidden"]').val(value);
    updateScoreDisplay($button.closest('.mt-criterion-card'), value);
});

// Numeric input functionality
$(document).on('input', '.mt-score-input', function() {
    const value = Math.min(10, Math.max(0, $(this).val()));
    $(this).val(value);
    updateScoreDisplay($(this).closest('.mt-criterion-card'), value);
});

// Update score display
function updateScoreDisplay($criterion, value) {
    $criterion.find('.mt-score-value').text(value);
}
```

### Key Features

1. **Event Delegation**: Uses `$(document).on()` for dynamic content
2. **Form Integration**: All methods update hidden input fields
3. **Visual Feedback**: Immediate updates for user interactions
4. **Data Validation**: Numeric input constraints and sanitization
5. **Cross-Method Consistency**: All scoring methods use the same display update function

## Troubleshooting

### Common Issues

#### 1. CSS Not Applying
- **Check**: Settings are saved in database
- **Verify**: CSS generation methods are being called
- **Debug**: Check browser developer tools for CSS conflicts

#### 2. Scoring Methods Not Working
- **Check**: JavaScript is loaded and jQuery is available
- **Verify**: Event handlers are properly bound
- **Debug**: Check browser console for JavaScript errors

#### 3. Layout Classes Not Applied
- **Check**: Template variables are correctly set
- **Verify**: Layout class is being output in HTML
- **Debug**: Inspect element to confirm class application

#### 4. Form Submission Issues
- **Check**: Hidden input fields are being updated
- **Verify**: Form data includes all scoring values
- **Debug**: Check network tab for AJAX requests

### Debug Mode

Enable / disable debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('SCRIPT_DEBUG', true);
```

### Performance Optimization

1. **CSS Generation**: Only generates CSS when shortcodes are used
2. **JavaScript Loading**: Loads only on pages with evaluation forms
3. **Caching**: Consider implementing CSS caching for production
4. **Minification**: Minify CSS and JavaScript for production

### Browser Compatibility

- **Modern Browsers**: Full support for all features
- **CSS Grid**: Requires IE11+ or modern browsers
- **Flexbox**: Requires IE10+ or modern browsers
- **JavaScript**: ES6+ features require modern browsers

## Future Enhancements

### Planned Features

1. **Live Preview**: Real-time preview of customization changes
2. **CSS Caching**: Cache generated CSS for better performance
3. **Advanced Animations**: More sophisticated animation options
4. **Mobile Optimization**: Enhanced mobile-specific layouts
5. **Accessibility**: Improved accessibility features for all scoring methods

### Extension Points

The system is designed for easy extension:

1. **New Layout Types**: Add new layout options by extending CSS generation
2. **Custom Scoring Methods**: Implement new scoring interfaces
3. **Additional Styling**: Extend CSS generation for new components
4. **JavaScript Enhancements**: Add new interactive features

---

**Note**: This documentation covers the complete CSS generation and interactive scoring system. For specific implementation details, refer to the source code in the respective files.
