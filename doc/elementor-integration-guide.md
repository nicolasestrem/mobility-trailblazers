# Elementor Integration Guide

*Version 2.5.24 | Last Updated: January 18, 2025*

## Overview

The Mobility Trailblazers plugin provides native Elementor widget integration, allowing seamless use of plugin functionality within Elementor page builder. This guide covers the implementation, usage, and troubleshooting of the Elementor integration.

## Architecture

### Directory Structure

```
includes/integrations/elementor/
├── class-mt-elementor-loader.php       # Main loader and registration
├── class-mt-widget-base.php            # Base widget class
└── widgets/
    ├── class-mt-widget-jury-dashboard.php
    ├── class-mt-widget-candidates-grid.php
    ├── class-mt-widget-evaluation-stats.php
    └── class-mt-widget-winners-display.php
```

### Design Patterns

1. **Singleton Loader**: Prevents multiple instantiation issues
2. **Base Widget Class**: Provides common functionality and error handling
3. **Delegation Pattern**: Widgets delegate to existing shortcodes for consistency
4. **Lazy Loading**: Only loads when Elementor is active

## Available Widgets

### 1. MT Jury Dashboard

**Purpose**: Displays the jury member dashboard for logged-in users

**Shortcode Equivalent**: `[mt_jury_dashboard]`

**Settings**: None (automatically detects logged-in user)

**Usage Notes**:
- Only visible to logged-in jury members
- Shows access denied message for non-jury users
- Includes evaluation progress and assigned candidates

### 2. MT Candidates Grid

**Purpose**: Displays candidates in a responsive grid layout

**Shortcode Equivalent**: `[mt_candidates_grid]`

**Settings**:
- **Category**: Filter by award category (dropdown)
- **Columns**: Number of columns (1-4)
- **Number of Candidates**: Limit display (-1 for all)
- **Order By**: Title, Date, Menu Order, Random
- **Order**: Ascending or Descending
- **Show Biography**: Toggle biography display
- **Show Category**: Toggle category tag display

### 3. MT Evaluation Statistics

**Purpose**: Displays evaluation statistics for administrators

**Shortcode Equivalent**: `[mt_evaluation_stats]`

**Settings**:
- **Statistics Type**: Summary, By Category, By Jury Member
- **Show Chart**: Toggle chart display

**Usage Notes**:
- Only visible to users with `mt_view_all_evaluations` capability
- Typically used in admin dashboards

### 4. MT Winners Display

**Purpose**: Shows top-ranked candidates

**Shortcode Equivalent**: `[mt_winners_display]`

**Settings**:
- **Category**: Filter by award category
- **Year**: Award year (default: current year)
- **Number of Winners**: How many to display (1-10)
- **Show Scores**: Toggle score display

## Implementation Details

### Widget Registration

```php
// Automatic registration when Elementor loads
add_action('elementor/widgets/register', [$this, 'register_widgets'], 10);

// Category registration
add_action('elementor/elements/categories_registered', [$this, 'register_category']);
```

### Base Widget Pattern

```php
abstract class MT_Widget_Base extends \Elementor\Widget_Base {
    
    public function get_categories() {
        return ['mobility-trailblazers'];
    }
    
    protected function render_shortcode($shortcode_name, $attributes = []) {
        // Build and render shortcode
        $shortcode = '[' . $shortcode_name;
        foreach ($attributes as $key => $value) {
            if ($value !== '' && $value !== null) {
                $shortcode .= ' ' . $key . '="' . esc_attr($value) . '"';
            }
        }
        $shortcode .= ']';
        echo do_shortcode($shortcode);
    }
}
```

### Error Handling

```php
// Safe widget registration with error handling
try {
    $widgets_manager->register(new $class_name());
} catch (\Exception $e) {
    error_log('MT Elementor: Failed to register widget ' . $class_name . ': ' . $e->getMessage());
}
```

## Usage Instructions

### For Content Editors

1. **Open Elementor Editor**: Edit any page with Elementor
2. **Search for Widgets**: Type "MT" or "Mobility" in the widget search
3. **Drag Widget**: Drag desired widget onto the page
4. **Configure Settings**: Adjust widget settings in the left panel
5. **Preview**: Use Elementor preview to see the result
6. **Publish**: Save and publish when satisfied

### For Developers

#### Adding Custom Widgets

1. Create new widget class extending `MT_Widget_Base`:

```php
namespace MobilityTrailblazers\Integrations\Elementor\Widgets;

class MT_Widget_Custom extends \MobilityTrailblazers\Integrations\Elementor\MT_Widget_Base {
    
    public function get_name() {
        return 'mt_custom_widget';
    }
    
    public function get_title() {
        return __('MT Custom Widget', 'mobility-trailblazers');
    }
    
    protected function register_controls() {
        // Add your controls here
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        // Render your widget
    }
}
```

2. Add to loader registration:

```php
$widget_files = [
    'jury-dashboard',
    'candidates-grid',
    'evaluation-stats',
    'winners-display',
    'custom' // Add your widget
];
```

#### Styling Widgets

Widgets automatically inherit theme styles, but can be customized:

```css
/* Target specific widget */
.mt-elementor-widget.mt-jury-dashboard-widget {
    /* Custom styles */
}

/* Target all MT widgets */
.mt-elementor-widget {
    /* Common styles */
}
```

## Troubleshooting

### Common Issues

#### Widgets Not Appearing

**Symptoms**: Widgets don't show in Elementor search

**Solutions**:
1. Verify Elementor is active
2. Check if integration files exist in `includes/integrations/elementor/`
3. Clear WordPress cache
4. Check PHP error logs for registration failures

#### Widget Causes 500 Error

**Symptoms**: Adding widget to page causes error

**Solutions**:
1. Check widget file exists and is readable
2. Verify namespace and class names match
3. Check for PHP syntax errors
4. Ensure base class is loaded

#### Shortcode Output Instead of Content

**Symptoms**: Widget shows `[mt_shortcode]` instead of rendered content

**Solutions**:
1. Verify shortcode is registered
2. Check if `do_shortcode()` is being called
3. Ensure shortcode handler exists

### Debug Mode

Enable debug logging for detailed information:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Check logs at wp-content/debug.log
```

### Version Compatibility

- **Elementor**: Requires version 3.0 or higher
- **WordPress**: Requires version 5.8 or higher
- **PHP**: Requires version 7.4 or higher

## Security Considerations

### Data Validation

All widget inputs are sanitized before rendering:

```php
$category = sanitize_text_field($settings['category']);
$limit = intval($settings['limit']);
$show_bio = $settings['show_bio'] === 'yes';
```

### Capability Checks

Widgets respect WordPress capabilities:

```php
// Evaluation stats only for authorized users
if (!current_user_can('mt_view_all_evaluations')) {
    return;
}
```

### Nonce Verification

AJAX operations include nonce verification:

```php
wp_localize_script('mt-elementor', 'mt_elementor', [
    'nonce' => wp_create_nonce('mt_elementor_nonce')
]);
```

## Performance Optimization

### Caching

Widget output can be cached using transients:

```php
$cache_key = 'mt_widget_' . md5(serialize($settings));
$output = get_transient($cache_key);

if (false === $output) {
    ob_start();
    $this->render_shortcode('mt_candidates_grid', $settings);
    $output = ob_get_clean();
    set_transient($cache_key, $output, HOUR_IN_SECONDS);
}

echo $output;
```

### Asset Loading

Styles and scripts are only loaded when widgets are used:

```php
public function get_style_depends() {
    return ['mt-frontend', 'mt-candidate-grid'];
}

public function get_script_depends() {
    return ['mt-frontend'];
}
```

## Migration from v2.5.22

### What Changed

1. **Directory Structure**: Moved from `includes/elementor/` to `includes/integrations/elementor/`
2. **Class Architecture**: Introduced base widget class
3. **Error Handling**: Added try/catch blocks for registration
4. **File Loading**: Implemented lazy loading pattern

### Migration Steps

1. Remove old Elementor files if they exist
2. Deploy new integration directory
3. Clear WordPress cache
4. Test widgets in Elementor editor

## Future Enhancements

### Planned Features

1. **Live Preview**: Real-time preview in Elementor editor
2. **Advanced Styling**: More style controls for widgets
3. **Template Library**: Pre-built templates for common layouts
4. **Dynamic Content**: Support for dynamic tags
5. **Custom Icons**: Widget-specific icons

### API Extensions

Future API for third-party developers:

```php
// Hook for custom widget registration
do_action('mt_elementor_register_widgets', $widgets_manager);

// Filter for widget settings
$settings = apply_filters('mt_elementor_widget_settings', $settings, $widget_name);
```

## Support

For issues or questions:
1. Check the [troubleshooting section](#troubleshooting)
2. Review the [developer guide](developer-guide.md)
3. Contact support with:
   - WordPress version
   - Elementor version
   - PHP version
   - Error messages from debug log

---

*This guide is part of the Mobility Trailblazers plugin documentation. For general plugin information, see the [Developer Guide](developer-guide.md).*