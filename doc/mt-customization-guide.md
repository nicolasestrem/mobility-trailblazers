# Mobility Trailblazers - Customization Guide

**Version:** 2.0.11
**Last Updated:** July 2025

## Overview
This guide provides step-by-step instructions for implementing dashboard and candidate presentation customization features.

*For architecture details, see [Architecture Documentation](mt-architecture-docs.md)*

## Required File Changes

### 1. Update Settings Page (`templates/admin/settings.php`)

Add after the existing criteria weights section:

```php
// Get dashboard and presentation settings (add at top with other settings)
$dashboard_settings = get_option('mt_dashboard_settings', [
    'header_style' => 'gradient',
    'primary_color' => '#667eea',
    'secondary_color' => '#764ba2',
    'progress_bar_style' => 'rounded',
    'show_welcome_message' => 1,
    'show_progress_bar' => 1,
    'show_stats_cards' => 1,
    'show_search_filter' => 1,
    'card_layout' => 'grid',
    'intro_text' => ''
]);

$candidate_presentation = get_option('mt_candidate_presentation', [
    'profile_layout' => 'side-by-side',
    'photo_style' => 'rounded',
    'photo_size' => 'medium',
    'show_organization' => 1,
    'show_position' => 1,
    'show_category' => 1,
    'show_innovation_summary' => 1,
    'show_full_bio' => 1,
    'form_style' => 'cards',
    'scoring_style' => 'slider',
    'enable_animations' => 1,
    'enable_hover_effects' => 1
]);
```

Add the new settings sections (see main documentation for full HTML).

### 2. Update Admin Class (`includes/admin/class-mt-admin.php`)

In the `register_settings()` method, add:

```php
// Dashboard customization settings
register_setting('mt_dashboard_settings', 'mt_dashboard_settings', [
    'sanitize_callback' => [$this, 'sanitize_dashboard_settings']
]);

// Candidate presentation settings  
register_setting('mt_candidate_presentation', 'mt_candidate_presentation', [
    'sanitize_callback' => [$this, 'sanitize_candidate_presentation']
]);
```

Add the sanitization methods:

```php
public function sanitize_dashboard_settings($input) {
    $sanitized = [];
    
    $sanitized['header_style'] = in_array($input['header_style'], ['gradient', 'solid', 'image']) 
        ? $input['header_style'] : 'gradient';
    $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']) ?: '#667eea';
    $sanitized['secondary_color'] = sanitize_hex_color($input['secondary_color']) ?: '#764ba2';
    // ... rest of sanitization
    
    return $sanitized;
}

public function sanitize_candidate_presentation($input) {
    $sanitized = [];
    
    $sanitized['profile_layout'] = in_array($input['profile_layout'], ['side-by-side', 'stacked', 'card', 'minimal'])
        ? $input['profile_layout'] : 'side-by-side';
    // ... rest of sanitization
    
    return $sanitized;
}
```

### 3. Update Jury Dashboard Template (`templates/frontend/jury-dashboard.php`)

Add at the top after getting user info:

```php
// Get dashboard customization settings
$dashboard_settings = get_option('mt_dashboard_settings', [
    'header_style' => 'gradient',
    'primary_color' => '#667eea',
    'secondary_color' => '#764ba2',
    'progress_bar_style' => 'rounded',
    'show_welcome_message' => 1,
    'show_progress_bar' => 1,
    'show_stats_cards' => 1,
    'show_search_filter' => 1,
    'card_layout' => 'grid',
    'intro_text' => ''
]);

// Apply custom styles
$header_class = 'mt-dashboard-header mt-header-' . $dashboard_settings['header_style'];
$progress_class = 'mt-progress-bar mt-progress-' . $dashboard_settings['progress_bar_style'];
$layout_class = 'mt-candidates-' . $dashboard_settings['card_layout'];
```

Update the HTML to use these classes and conditional displays.

### 4. Update Evaluation Form (`templates/frontend/jury-evaluation-form.php`)

Add presentation settings retrieval:

```php
// Get candidate presentation settings
$presentation_settings = get_option('mt_candidate_presentation', [
    'profile_layout' => 'side-by-side',
    'photo_style' => 'rounded',
    'photo_size' => 'medium',
    'show_organization' => 1,
    'show_position' => 1,
    'show_category' => 1,
    'show_innovation_summary' => 1,
    'show_full_bio' => 1,
    'form_style' => 'cards',
    'scoring_style' => 'slider',
    'enable_animations' => 1,
    'enable_hover_effects' => 1
]);
```

Apply classes and conditionals throughout the template.

### 5. Update Shortcodes Class (`includes/core/class-mt-shortcodes.php`)

Add after the shortcode rendering in `render_jury_dashboard()`:

```php
// Add custom CSS
$custom_css = $this->generate_dashboard_custom_css();
if (!empty($custom_css)) {
    echo '<style type="text/css">' . $custom_css . '</style>';
}
```

Add the CSS generation method (see main documentation).

### 6. Update Activator (`includes/core/class-mt-activator.php`)

In the `create_options()` method, add:

```php
// Dashboard customization settings
add_option('mt_dashboard_settings', [
    'header_style' => 'gradient',
    'primary_color' => '#667eea',
    'secondary_color' => '#764ba2',
    'progress_bar_style' => 'rounded',
    'show_welcome_message' => 1,
    'show_progress_bar' => 1,
    'show_stats_cards' => 1,
    'show_search_filter' => 1,
    'card_layout' => 'grid',
    'intro_text' => '',
    'header_image_url' => ''
]);

// Candidate presentation settings
add_option('mt_candidate_presentation', [
    'profile_layout' => 'side-by-side',
    'photo_style' => 'rounded',
    'photo_size' => 'medium',
    'show_organization' => 1,
    'show_position' => 1,
    'show_category' => 1,
    'show_innovation_summary' => 1,
    'show_full_bio' => 1,
    'form_style' => 'cards',
    'scoring_style' => 'slider',
    'enable_animations' => 1,
    'enable_hover_effects' => 1
]);
```

## Testing Checklist

After implementation, test these features:

1. **Settings Page**
   - [ ] All form fields display correctly
   - [ ] Settings save without errors
   - [ ] Saved values persist after page reload

2. **Dashboard Customization**
   - [ ] Header style changes apply correctly
   - [ ] Colors update throughout the interface
   - [ ] Progress bar styles work
   - [ ] Display toggles hide/show elements
   - [ ] Layout options change candidate display

3. **Candidate Presentation**
   - [ ] Profile layouts render correctly
   - [ ] Photo styles apply
   - [ ] Information toggles work
   - [ ] Form styles change appropriately
   - [ ] Animations work when enabled

4. **Performance**
   - [ ] Page load times remain acceptable
   - [ ] No JavaScript errors in console
   - [ ] Mobile responsiveness maintained

## Rollback Instructions

If issues occur, you can rollback by:

1. Remove the new options from the database:
   ```sql
   DELETE FROM wp_options WHERE option_name IN ('mt_dashboard_settings', 'mt_candidate_presentation');
   ```

2. Restore original template files from backup

3. Remove added methods from admin class

4. Clear any caching plugins

## Common Issues and Solutions

### Settings Not Saving
- Check file permissions on WordPress installation
- Verify nonce field is present in form
- Check PHP error logs for issues

### Styles Not Applying
- Clear browser cache
- Check for CSS conflicts with theme
- Verify shortcode is rendering custom CSS

### Missing Default Values
- Deactivate and reactivate plugin
- Manually run activator::create_options()
- Check database for option existence