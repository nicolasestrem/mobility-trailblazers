# CSS Architecture & Styling Guide

**Version:** 4.0.0  
**Last Updated:** 2025-08-22  
**Author:** Mobility Trailblazers Development Team

## Table of Contents

1. [Overview](#overview)
2. [CSS v4 Framework](#css-v4-framework)
3. [File Structure](#file-structure)
4. [Design Tokens](#design-tokens)
5. [Component Architecture](#component-architecture)
6. [Conditional Loading](#conditional-loading)
7. [Container System](#container-system)
8. [Implementation Details](#implementation-details)
9. [Migration & Rollback](#migration--rollback)
10. [Performance Optimization](#performance-optimization)
11. [Best Practices](#best-practices)

## Overview

The Mobility Trailblazers plugin uses a modern CSS v4 framework that implements conditional loading, design tokens, and modular architecture. This system ensures optimal performance by loading CSS only on plugin-specific pages while maintaining complete style isolation.

### Migration Status

**Current System:** CSS v4 Framework (Conditional Loading)  
**Previous System:** v3 CSS (Elementor-scoped) - *deprecated*  
**Legacy System:** v2.5.38 Unified Container System - *obsolete*

### Key Benefits

- **60% Size Reduction**: From ~50KB to ~20KB CSS footprint
- **Conditional Loading**: CSS only loads on plugin pages
- **Elementor Independence**: Works without Elementor integration
- **Token-Based Theming**: Consistent design system via CSS custom properties
- **Safe Rollback**: Multiple fallback mechanisms available

## CSS v4 Framework

### Architecture Principles

1. **Modular Design**: Separate concerns across 5 distinct CSS files
2. **Token-Based System**: Centralized design tokens for consistency
3. **Scoped Styles**: All styles namespaced with `.mt-root` wrapper
4. **Conditional Loading**: Smart route detection prevents CSS bloat
5. **Performance First**: Optimized for minimal impact on site speed

### Core Features

- **Route Detection**: Automatically detects plugin pages and post types
- **Style Isolation**: Prevents conflicts with theme and other plugins
- **Responsive Design**: Mobile-first approach with proper breakpoints
- **Accessibility**: WCAG compliant color contrasts and focus states
- **Browser Support**: Modern browsers with graceful degradation

## File Structure

```
assets/css/v4/
├── mt-tokens.css       # CSS custom properties (1.7KB)
├── mt-reset.css        # Scoped reset styles (1.8KB)
├── mt-base.css         # Core components (5.1KB)
├── mt-components.css   # Specific components (5.9KB)
└── mt-pages.css        # Page-specific styles (5.5KB)
```

**Total Size**: ~20KB uncompressed

### File Descriptions

#### mt-tokens.css
Contains all CSS custom properties (design tokens):
```css
:root {
  /* Color System */
  --mt-primary: #2c3e50;
  --mt-secondary: #3498db;
  --mt-accent: #e74c3c;
  
  /* Typography */
  --mt-font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  --mt-font-size-base: 16px;
  --mt-line-height-base: 1.6;
  
  /* Spacing */
  --mt-space-xs: 0.25rem;
  --mt-space-sm: 0.5rem;
  --mt-space-md: 1rem;
  --mt-space-lg: 1.5rem;
  --mt-space-xl: 2rem;
  
  /* Breakpoints */
  --mt-breakpoint-sm: 768px;
  --mt-breakpoint-md: 1024px;
  --mt-breakpoint-lg: 1200px;
}
```

#### mt-reset.css
Scoped reset styles that normalize browser differences:
```css
.mt-root {
  /* Box model reset */
  *, *::before, *::after {
    box-sizing: border-box;
  }
  
  /* Typography reset */
  font-family: var(--mt-font-family);
  line-height: var(--mt-line-height-base);
  
  /* Remove default margins/padding */
  h1, h2, h3, h4, h5, h6, p, ul, ol {
    margin: 0;
    padding: 0;
  }
}
```

#### mt-base.css
Core component styles and utilities:
- Layout utilities (flexbox, grid)
- Typography classes
- Button styles
- Form controls
- Card components

#### mt-components.css
Plugin-specific components:
- Candidate cards
- Evaluation forms
- Jury dashboard elements
- Filter interfaces
- Modal dialogs

#### mt-pages.css
Page-specific styling:
- Landing page layouts
- Dashboard pages
- Archive templates
- Single post templates

## Design Tokens

The CSS v4 framework uses a comprehensive token system for consistent design:

### Color Tokens

```css
/* Primary Colors */
--mt-primary-50: #f8fafc;
--mt-primary-500: #2c3e50;
--mt-primary-900: #1a252f;

/* Semantic Colors */
--mt-success: #22c55e;
--mt-warning: #f59e0b;
--mt-error: #ef4444;
--mt-info: #3b82f6;

/* Status Colors */
--mt-status-draft: #6b7280;
--mt-status-submitted: #3b82f6;
--mt-status-approved: #22c55e;
--mt-status-rejected: #ef4444;
```

### Typography Tokens

```css
/* Font Families */
--mt-font-heading: 'Inter', sans-serif;
--mt-font-body: 'Inter', sans-serif;
--mt-font-mono: 'Menlo', 'Monaco', monospace;

/* Font Sizes */
--mt-text-xs: 0.75rem;    /* 12px */
--mt-text-sm: 0.875rem;   /* 14px */
--mt-text-base: 1rem;     /* 16px */
--mt-text-lg: 1.125rem;   /* 18px */
--mt-text-xl: 1.25rem;    /* 20px */
--mt-text-2xl: 1.5rem;    /* 24px */
--mt-text-3xl: 1.875rem;  /* 30px */
--mt-text-4xl: 2.25rem;   /* 36px */

/* Font Weights */
--mt-font-light: 300;
--mt-font-normal: 400;
--mt-font-medium: 500;
--mt-font-semibold: 600;
--mt-font-bold: 700;
```

### Spacing Tokens

```css
/* Spacing Scale */
--mt-space-px: 1px;
--mt-space-0: 0;
--mt-space-1: 0.25rem;   /* 4px */
--mt-space-2: 0.5rem;    /* 8px */
--mt-space-3: 0.75rem;   /* 12px */
--mt-space-4: 1rem;      /* 16px */
--mt-space-5: 1.25rem;   /* 20px */
--mt-space-6: 1.5rem;    /* 24px */
--mt-space-8: 2rem;      /* 32px */
--mt-space-10: 2.5rem;   /* 40px */
--mt-space-12: 3rem;     /* 48px */
--mt-space-16: 4rem;     /* 64px */
--mt-space-20: 5rem;     /* 80px */
```

## Component Architecture

### BEM Methodology

All CSS classes follow BEM (Block Element Modifier) naming convention:

```css
/* Block */
.mt-candidate-card { }

/* Element */
.mt-candidate-card__header { }
.mt-candidate-card__content { }
.mt-candidate-card__footer { }

/* Modifier */
.mt-candidate-card--featured { }
.mt-candidate-card--compact { }
.mt-candidate-card__header--no-image { }
```

### Component Examples

#### Candidate Card Component

```css
.mt-candidate-card {
  background: var(--mt-color-white);
  border: 1px solid var(--mt-color-gray-200);
  border-radius: var(--mt-radius-lg);
  padding: var(--mt-space-6);
  transition: all 0.2s ease;
}

.mt-candidate-card:hover {
  box-shadow: var(--mt-shadow-lg);
  transform: translateY(-2px);
}

.mt-candidate-card__header {
  display: flex;
  align-items: center;
  gap: var(--mt-space-4);
  margin-bottom: var(--mt-space-4);
}

.mt-candidate-card__avatar {
  width: 60px;
  height: 60px;
  border-radius: var(--mt-radius-full);
  object-fit: cover;
}

.mt-candidate-card__title {
  font-size: var(--mt-text-lg);
  font-weight: var(--mt-font-semibold);
  color: var(--mt-color-gray-900);
}

.mt-candidate-card__company {
  font-size: var(--mt-text-sm);
  color: var(--mt-color-gray-600);
}
```

#### Evaluation Form Component

```css
.mt-evaluation-form {
  background: var(--mt-color-white);
  border-radius: var(--mt-radius-lg);
  padding: var(--mt-space-8);
}

.mt-evaluation-form__section {
  margin-bottom: var(--mt-space-8);
}

.mt-evaluation-form__label {
  display: block;
  font-size: var(--mt-text-sm);
  font-weight: var(--mt-font-medium);
  color: var(--mt-color-gray-700);
  margin-bottom: var(--mt-space-2);
}

.mt-evaluation-form__input {
  width: 100%;
  padding: var(--mt-space-3);
  border: 1px solid var(--mt-color-gray-300);
  border-radius: var(--mt-radius-md);
  font-size: var(--mt-text-base);
  transition: border-color 0.2s ease;
}

.mt-evaluation-form__input:focus {
  outline: none;
  border-color: var(--mt-primary-500);
  box-shadow: 0 0 0 3px rgba(44, 62, 80, 0.1);
}
```

## Conditional Loading

The CSS v4 framework implements smart conditional loading to optimize performance:

### Route Detection

CSS is loaded only on pages that contain plugin content:

```php
class MT_Public_Assets {
    
    private function is_plugin_route(): bool {
        // Page slug detection
        $plugin_pages = ['vote', 'mt_jury_dashboard', 'rankings', 'jury-dashboard'];
        if (is_page($plugin_pages)) {
            return true;
        }
        
        // Post type detection
        if (is_singular(['mt_candidate', 'mt_jury_member'])) {
            return true;
        }
        
        // Archive detection
        if (is_post_type_archive(['mt_candidate', 'mt_jury_member'])) {
            return true;
        }
        
        // Shortcode detection
        global $post;
        if ($post && $this->has_plugin_shortcodes($post->post_content)) {
            return true;
        }
        
        // URL parameter detection
        if (isset($_GET['evaluate']) || isset($_GET['mt_category'])) {
            return true;
        }
        
        return apply_filters('mt_is_plugin_route', false);
    }
    
    private function has_plugin_shortcodes(string $content): bool {
        $shortcodes = [
            'mt_candidates_grid',
            'mt_jury_dashboard',
            'mt_evaluation_form',
            'mt_rankings_display'
        ];
        
        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($content, $shortcode)) {
                return true;
            }
        }
        
        return false;
    }
}
```

### Performance Benefits

- **Reduced Page Load**: CSS only loads when needed
- **Faster Site Speed**: Non-plugin pages load without additional CSS
- **Better Core Web Vitals**: Improved LCP and CLS metrics
- **Bandwidth Savings**: Reduces data transfer for users

## Container System

### Unified Container Pattern

All dashboard widgets and major UI elements follow a consistent 1200px max-width container pattern:

```css
.mt-jury-dashboard__container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--mt-space-5);
    box-sizing: border-box;
}

/* Responsive breakpoints */
@media (max-width: 768px) {
    .mt-jury-dashboard__container {
        padding: 0 var(--mt-space-4);
    }
}
```

### Applied Elements

1. **Dashboard Header** (`.mt-dashboard-header`)
   - Centered with 1200px max-width
   - Consistent padding: 30px 20px
   - Removed negative margins that broke alignment

2. **Stats Grid** (`.mt-stats-grid`)
   - Grid layout within 1200px container
   - Auto-fit columns with minimum 200px width
   - Proper responsive scaling

3. **Rankings Section** (`.mt-rankings-section`)
   - Fixed from margin: -30px to margin: 0
   - Centered with proper container width
   - Improved header alignment

4. **Filter Controls** (`.mt-search-input`, `.mt-filter-select`)
   - Integrated into unified container system
   - Consistent spacing and alignment
   - Responsive behavior across devices

## Implementation Details

### Template Wrapper System

All frontend templates are wrapped with the `.mt-root` class for style scoping:

```php
// In template files
echo '<div class="mt-root">';
// Template content
echo '</div>';
```

This ensures:
- Complete style isolation from theme
- Prevents CSS conflicts
- Enables token-based styling
- Maintains consistent design system

### WordPress Integration

```php
// Enqueue CSS conditionally
add_action('wp_enqueue_scripts', function() {
    if (!MT_Public_Assets::instance()->is_plugin_route()) {
        return;
    }
    
    // Load v4 CSS files
    wp_enqueue_style('mt-tokens', MT_PLUGIN_URL . 'assets/css/v4/mt-tokens.css', [], MT_VERSION);
    wp_enqueue_style('mt-reset', MT_PLUGIN_URL . 'assets/css/v4/mt-reset.css', ['mt-tokens'], MT_VERSION);
    wp_enqueue_style('mt-base', MT_PLUGIN_URL . 'assets/css/v4/mt-base.css', ['mt-reset'], MT_VERSION);
    wp_enqueue_style('mt-components', MT_PLUGIN_URL . 'assets/css/v4/mt-components.css', ['mt-base'], MT_VERSION);
    wp_enqueue_style('mt-pages', MT_PLUGIN_URL . 'assets/css/v4/mt-pages.css', ['mt-components'], MT_VERSION);
});
```

### Cache Optimization

```php
// Cache route detection results
private $route_cache = null;

private function is_plugin_route(): bool {
    if ($this->route_cache !== null) {
        return $this->route_cache;
    }
    
    $this->route_cache = $this->check_route_conditions();
    return $this->route_cache;
}
```

## Migration & Rollback

### Rollback Mechanisms

#### Method 1: Filter Toggle (Recommended)
Add to theme's `functions.php` or create a mu-plugin:

```php
// Disable v4 CSS and revert to v3
add_filter('mt_enable_css_v4', '__return_false');
```

#### Method 2: Constant Override
Define in `wp-config.php`:

```php
define('MT_FORCE_CSS_V3', true);
```

#### Method 3: Admin Setting
Available in WordPress admin under MT Award System → Settings → Advanced.

### Migration Checklist

- ✅ v4 CSS files created and tested
- ✅ Route detection implemented
- ✅ Template wrappers added
- ✅ Performance tested
- ✅ Rollback mechanisms verified
- ✅ Documentation updated

## Performance Optimization

### Current Metrics

- **File Size**: ~20KB total (uncompressed)
- **Gzip Compression**: ~5KB compressed
- **Load Time**: <100ms on plugin pages
- **Cache Friendly**: Aggressive browser caching enabled

### Optimization Strategies

1. **Critical CSS**: Inline critical styles for above-the-fold content
2. **CSS Purging**: Remove unused styles in production
3. **Minification**: Compress CSS files for production
4. **CDN Delivery**: Serve static assets from CDN
5. **HTTP/2 Push**: Server push for CSS resources

### Target Goals

- **Total Size**: <10KB compressed
- **Load Time**: <50ms
- **First Paint**: <1 second
- **Cumulative Layout Shift**: <0.1

## Best Practices

### Development Guidelines

1. **Use Design Tokens**: Always reference CSS custom properties
2. **Follow BEM**: Consistent naming convention
3. **Mobile First**: Start with mobile styles, enhance for desktop
4. **Semantic HTML**: Use proper HTML elements
5. **Accessibility**: Ensure WCAG compliance
6. **Performance**: Optimize for speed and efficiency

### Code Examples

#### Using Design Tokens
```css
/* Good - uses tokens */
.mt-button {
  padding: var(--mt-space-3) var(--mt-space-6);
  background: var(--mt-primary-500);
  color: var(--mt-color-white);
  border-radius: var(--mt-radius-md);
}

/* Bad - hardcoded values */
.mt-button {
  padding: 12px 24px;
  background: #2c3e50;
  color: #ffffff;
  border-radius: 6px;
}
```

#### Responsive Design
```css
/* Mobile first approach */
.mt-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--mt-space-4);
}

/* Tablet and up */
@media (min-width: 768px) {
  .mt-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: var(--mt-space-6);
  }
}

/* Desktop and up */
@media (min-width: 1024px) {
  .mt-grid {
    grid-template-columns: repeat(3, 1fr);
    gap: var(--mt-space-8);
  }
}
```

#### Component States
```css
.mt-card {
  background: var(--mt-color-white);
  border: 1px solid var(--mt-color-gray-200);
  transition: all 0.2s ease;
}

/* Interactive states */
.mt-card:hover {
  border-color: var(--mt-color-gray-300);
  box-shadow: var(--mt-shadow-md);
}

.mt-card:focus-within {
  border-color: var(--mt-primary-500);
  box-shadow: 0 0 0 3px var(--mt-primary-100);
}

/* Status modifiers */
.mt-card--draft {
  border-left: 4px solid var(--mt-status-draft);
}

.mt-card--submitted {
  border-left: 4px solid var(--mt-status-submitted);
}

.mt-card--approved {
  border-left: 4px solid var(--mt-status-approved);
}
```

### Testing Guidelines

1. **Cross-Browser**: Test in Chrome, Firefox, Safari, Edge
2. **Device Testing**: Mobile, tablet, desktop viewports
3. **Performance**: Audit with Lighthouse
4. **Accessibility**: Test with screen readers
5. **Visual Regression**: Compare with previous versions

---

*This CSS guide provides comprehensive documentation for the Mobility Trailblazers plugin styling system. For implementation details, see the [Architecture Guide](architecture.md) and [Developer Guide](developer-guide.md).*