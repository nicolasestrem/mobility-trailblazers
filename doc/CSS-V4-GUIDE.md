# CSS v4 Framework Guide

## Overview

The CSS v4 framework is a modern, token-based CSS architecture designed for the Mobility Trailblazers WordPress plugin. It replaces the legacy CSS system with a maintainable, scalable, and mobile-first approach.

**Version:** 4.1.0  
**Released:** August 23, 2025

## Architecture

### File Structure

```
assets/css/v4/
├── mt-tokens.css              # Design tokens (colors, spacing, typography)
├── mt-reset.css               # CSS reset and normalization
├── mt-base.css                # Base element styles
├── mt-components.css          # Reusable component styles
├── mt-pages.css               # Page-specific styles
└── mt-mobile-jury-dashboard.css # Mobile-specific jury dashboard styles (v4.1.0)
```

### Loading Order

1. **mt-tokens.css** - CSS custom properties and design tokens
2. **mt-reset.css** - Browser normalization
3. **mt-base.css** - Base HTML element styling
4. **mt-components.css** - Component library
5. **mt-pages.css** - Page-specific implementations
6. **mt-mobile-jury-dashboard.css** - Mobile overrides (conditional)

## Design Tokens

Design tokens are the foundation of the v4 framework, providing consistent values across the entire system.

### Color Tokens

```css
:root {
  /* Primary Brand Colors */
  --mt-color-primary: #26a69a;
  --mt-color-primary-dark: #00897b;
  --mt-color-primary-light: #4db6ac;
  
  /* Semantic Colors */
  --mt-color-success: #4caf50;
  --mt-color-warning: #ff9800;
  --mt-color-error: #f44336;
  --mt-color-info: #2196f3;
  
  /* Neutral Colors */
  --mt-color-white: #ffffff;
  --mt-color-black: #000000;
  --mt-color-gray-light: #f5f5f5;
  --mt-color-gray: #9e9e9e;
  --mt-color-gray-dark: #616161;
}
```

### Spacing Tokens

```css
:root {
  /* Base spacing unit: 8px */
  --mt-space-xs: 4px;
  --mt-space-sm: 8px;
  --mt-space: 16px;
  --mt-space-md: 24px;
  --mt-space-lg: 32px;
  --mt-space-xl: 48px;
  --mt-space-xxl: 64px;
  
  /* Touch targets (mobile) */
  --mt-touch-target: 44px;
  --mt-touch-target-large: 48px;
}
```

### Typography Tokens

```css
:root {
  /* Font Families */
  --mt-font-base: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  --mt-font-heading: inherit;
  
  /* Font Sizes */
  --mt-text-xs: 0.75rem;   /* 12px */
  --mt-text-sm: 0.875rem;  /* 14px */
  --mt-text-base: 1rem;    /* 16px */
  --mt-text-lg: 1.125rem;  /* 18px */
  --mt-text-xl: 1.25rem;   /* 20px */
  --mt-text-2xl: 1.5rem;   /* 24px */
  --mt-text-3xl: 1.875rem; /* 30px */
  
  /* Font Weights */
  --mt-font-normal: 400;
  --mt-font-medium: 500;
  --mt-font-semibold: 600;
  --mt-font-bold: 700;
}
```

## Mobile-First Approach (v4.1.0)

The v4.1.0 release introduces comprehensive mobile-first design patterns.

### Breakpoints

```css
/* Mobile First Breakpoints */
--mt-bp-xs: 320px;   /* Small phones */
--mt-bp-sm: 375px;   /* Standard phones */
--mt-bp-md: 414px;   /* Large phones */
--mt-bp-lg: 768px;   /* Tablets */
--mt-bp-xl: 1024px;  /* Desktop */
--mt-bp-xxl: 1200px; /* Wide screens */
```

### Mobile Patterns

#### Table-to-Card Transformation

```css
@media (max-width: 767px) {
  .mt-evaluation-table {
    display: block;
  }
  
  .mt-evaluation-table tr {
    display: block;
    margin-bottom: var(--mt-space);
    background: var(--mt-color-white);
    border-radius: var(--mt-radius);
    padding: var(--mt-space);
    box-shadow: var(--mt-shadow-sm);
  }
}
```

#### Touch-Friendly Inputs

```css
@media (max-width: 767px) {
  input, button, select, textarea {
    min-height: var(--mt-touch-target);
    font-size: 16px; /* Prevents zoom on iOS */
  }
}
```

## Component Library

### Cards

```css
.mt-card {
  background: var(--mt-color-white);
  border-radius: var(--mt-radius);
  padding: var(--mt-space);
  box-shadow: var(--mt-shadow);
}

.mt-card__header {
  border-bottom: 1px solid var(--mt-border);
  margin-bottom: var(--mt-space);
  padding-bottom: var(--mt-space);
}

.mt-card__body {
  /* Content area */
}

.mt-card__footer {
  border-top: 1px solid var(--mt-border);
  margin-top: var(--mt-space);
  padding-top: var(--mt-space);
}
```

### Buttons

```css
.mt-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--mt-space-sm) var(--mt-space);
  border-radius: var(--mt-radius-sm);
  font-weight: var(--mt-font-medium);
  transition: all 0.2s ease;
  cursor: pointer;
}

.mt-btn--primary {
  background: var(--mt-color-primary);
  color: var(--mt-color-white);
}

.mt-btn--secondary {
  background: transparent;
  color: var(--mt-color-primary);
  border: 1px solid var(--mt-color-primary);
}
```

### Forms

```css
.mt-form-group {
  margin-bottom: var(--mt-space);
}

.mt-form-label {
  display: block;
  margin-bottom: var(--mt-space-xs);
  font-weight: var(--mt-font-medium);
}

.mt-form-input {
  width: 100%;
  padding: var(--mt-space-sm);
  border: 1px solid var(--mt-border);
  border-radius: var(--mt-radius-sm);
  font-size: var(--mt-text-base);
}
```

## PHP Integration

### Conditional Loading

The CSS v4 framework uses conditional loading to only include styles on plugin-specific pages:

```php
// includes/public/class-mt-public-assets.php
class MT_Public_Assets {
    const V4_VERSION = '4.1.0';
    
    private function is_mt_public_route() {
        // Check for plugin pages
        if (is_page(['vote', 'mt_jury_dashboard', 'rankings'])) {
            return true;
        }
        
        // Check for shortcodes
        if ($this->has_mt_shortcodes()) {
            return true;
        }
        
        return false;
    }
}
```

### Mobile Styles Injection

Critical mobile CSS is injected inline for immediate rendering:

```php
// includes/public/class-mt-mobile-styles.php
class MT_Mobile_Styles {
    public function inject_critical_mobile_css() {
        if (!$this->is_jury_page()) {
            return;
        }
        ?>
        <style id="mt-mobile-critical-css">
            /* Critical mobile styles */
        </style>
        <?php
    }
}
```

## BEM Naming Convention

All CSS classes follow BEM (Block Element Modifier) methodology with the `.mt-` prefix:

```css
/* Block */
.mt-evaluation-table { }

/* Element */
.mt-evaluation-table__header { }
.mt-evaluation-table__row { }

/* Modifier */
.mt-evaluation-table--mobile { }
.mt-evaluation-table__row--highlighted { }
```

## Migration from Legacy CSS

### Replacing Legacy Files

When v4 is enabled, the following legacy files are automatically dequeued:

- mt-variables.css
- mt-components.css
- mt-frontend.css
- mt-candidate-grid.css
- mt-evaluation-forms.css
- mt-jury-dashboard.css
- All v3 CSS files

### Backward Compatibility

The v4 framework maintains backward compatibility by:

1. Supporting both old and new class names during transition
2. Using `:where()` for lower specificity base styles
3. Progressive enhancement approach

## Performance Optimization

### Critical CSS

Critical above-the-fold CSS is inlined directly in the HTML:

```php
add_action('wp_head', function() {
    if (is_mt_critical_page()) {
        echo '<style id="mt-critical-css">';
        include MT_PLUGIN_DIR . 'assets/css/critical.css';
        echo '</style>';
    }
}, 1);
```

### Conditional Loading

CSS files are only loaded on pages that need them:

```php
if ($this->is_mt_public_route()) {
    wp_enqueue_style('mt-v4-tokens');
    wp_enqueue_style('mt-v4-base');
    // ... other styles
}
```

## JavaScript Integration

The v4 framework includes mobile-specific JavaScript enhancements:

```javascript
// assets/js/mt-mobile-jury.js
(function($) {
    'use strict';
    
    // Mobile detection
    if (window.innerWidth <= 767) {
        enhanceMobileTable();
    }
    
    function enhanceMobileTable() {
        // Add data attributes for mobile display
        $('.mt-evaluation-table').addClass('mt-mobile-view');
        // ... enhancement logic
    }
})(jQuery);
```

## Testing

### Browser Support

The v4 framework supports:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile Safari (iOS 14+)
- Chrome Mobile (Android 10+)

### Responsive Testing

Test at these viewport widths:
- 320px (iPhone SE)
- 375px (iPhone 11)
- 414px (iPhone 11 Pro Max)
- 768px (iPad)
- 1024px (Desktop)
- 1200px+ (Wide)

### Performance Metrics

Target metrics:
- First Contentful Paint: < 1.5s
- Largest Contentful Paint: < 2.5s
- Cumulative Layout Shift: < 0.1
- First Input Delay: < 100ms

## Troubleshooting

### CSS Not Loading

1. Check if v4 is enabled:
```php
add_filter('mt_enable_css_v4', '__return_true');
```

2. Verify page detection:
```php
// Add to functions.php temporarily
add_action('wp_head', function() {
    if (class_exists('MobilityTrailblazers\Public\MT_Public_Assets')) {
        $assets = new MT_Public_Assets();
        var_dump($assets->is_mt_public_route());
    }
});
```

### Mobile Styles Not Applying

1. Check viewport meta tag:
```html
<meta name="viewport" content="width=device-width, initial-scale=1">
```

2. Verify mobile CSS is loaded:
```javascript
console.log(document.querySelector('#mt-mobile-critical-css'));
```

### Cache Issues

Clear caches after updates:
```bash
wp cache flush
wp transient delete --all
```

## Future Enhancements

### Planned for v4.2.0
- Dark mode support
- RTL (Right-to-Left) language support
- Enhanced accessibility features
- Print styles optimization

### Planned for v5.0.0
- CSS Grid-based layouts
- Container queries
- Native CSS nesting
- Cascade layers (@layer)

## Resources

- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [BEM Methodology](http://getbem.com/)
- [CSS Custom Properties](https://developer.mozilla.org/en-US/docs/Web/CSS/--*)
- [Mobile-First Design](https://www.lukew.com/ff/entry.asp?933)