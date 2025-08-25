# CSS v4 Framework Developer Migration Guide

**Target Audience:** WordPress Developers working with Mobility Trailblazers Plugin  
**Version:** v4.1.0  
**Last Updated:** August 24, 2025  
**Migration Status:** Phase 2 Complete

## Overview

This guide provides comprehensive instructions for developers transitioning from the legacy v3 CSS system to the modern v4 CSS framework. Phase 2 completion means all v3 dependencies have been removed, and v4 is now the sole CSS framework for the plugin.

## Quick Start Checklist

Before you begin development:

- ✅ **Understand that v3 CSS is completely removed** - No backward compatibility
- ✅ **All new styles must use v4 classes and tokens** - No exceptions  
- ✅ **Use the token system for all custom CSS** - Never hardcode values
- ✅ **Follow BEM naming conventions** with `.mt-` prefix
- ✅ **Test responsive design from mobile-first** perspective

## Framework Architecture

### File Structure (Post Phase 2)

```
assets/css/v4/
├── mt-tokens.css              # 260+ CSS custom properties (FOUNDATION)
├── mt-reset.css               # Browser normalization
├── mt-base.css                # Base HTML elements  
├── mt-components.css          # Reusable UI components
├── mt-pages.css               # Page-specific styles
└── mt-mobile-jury-dashboard.css # Mobile enhancements
```

### Loading Order (Always On)

v4 framework is always loaded on plugin routes in this sequence:

1. **Tokens** → 2. **Reset** → 3. **Base** → 4. **Components** → 5. **Pages** → 6. **Mobile**

## Token System Usage

### Core Philosophy

**⚡ NEVER hardcode CSS values. Always use tokens.**

```css
/* ❌ WRONG - Hardcoded values */
.my-component {
    padding: 16px;
    color: #26a69a;
    border-radius: 8px;
}

/* ✅ CORRECT - Token-based */
.mt-my-component {
    padding: var(--mt-space-lg);
    color: var(--mt-primary);
    border-radius: var(--mt-radius-md);
}
```

### Essential Tokens Reference

#### Colors (Primary Usage)
```css
/* Brand Colors */
--mt-primary: #26a69a;           /* Main brand color */
--mt-primary-dark: #00897b;      /* Darker variant */  
--mt-primary-light: #4db6ac;     /* Lighter variant */

/* Semantic Colors */
--mt-success: #4caf50;           /* Success states */
--mt-warning: #ff9800;           /* Warning states */
--mt-error: #f44336;             /* Error states */
--mt-info: #2196f3;              /* Information */

/* Neutral Colors */
--mt-white: #ffffff;
--mt-bg-cream: #f8f0e3;          /* Background */
--mt-text-dark: #302c37;         /* Primary text */
--mt-text-light: #666666;        /* Secondary text */
--mt-border: #e0e0e0;            /* Borders */
```

#### Spacing (Mobile-First)
```css
/* Responsive spacing using clamp() */
--mt-space-xs: clamp(0.25rem, 1vw, 0.5rem);   /* 4-8px */
--mt-space-sm: clamp(0.5rem, 2vw, 0.75rem);   /* 8-12px */
--mt-space-md: clamp(0.75rem, 3vw, 1rem);     /* 12-16px */
--mt-space-lg: clamp(1rem, 4vw, 1.5rem);      /* 16-24px */
--mt-space-xl: clamp(1.5rem, 5vw, 2rem);      /* 24-32px */
--mt-space-2xl: clamp(2rem, 6vw, 3rem);       /* 32-48px */
```

#### Typography
```css
/* Font Sizes (Fluid) */
--mt-font-size-sm: clamp(0.875rem, 2.5vw, 0.9rem);
--mt-font-size-base: clamp(1rem, 2.5vw, 1.1rem);
--mt-font-size-lg: clamp(1.125rem, 3vw, 1.25rem);

/* Font Weights */
--mt-font-regular: 400;
--mt-font-medium: 500;
--mt-font-semibold: 600;
--mt-font-bold: 700;
```

#### Shadows & Effects
```css
/* Standard Shadows */
--mt-shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
--mt-shadow-md: 0 4px 6px rgba(0, 0, 0, 0.16);
--mt-shadow-lg: 0 10px 20px rgba(0, 0, 0, 0.19);

/* Brand-colored shadows */
--mt-shadow-primary: 0 4px 14px 0 rgba(38, 166, 154, 0.39);
```

## BEM Class Naming Convention

### Structure
```
.mt-[block]__[element]--[modifier]
```

### Examples

#### Basic Component
```css
/* Block */
.mt-candidate-card { }

/* Elements */
.mt-candidate-card__image { }
.mt-candidate-card__title { }
.mt-candidate-card__description { }
.mt-candidate-card__actions { }

/* Modifiers */
.mt-candidate-card--featured { }
.mt-candidate-card--mobile { }
.mt-candidate-card__title--large { }
```

#### Complex Component
```css
/* Evaluation Form */
.mt-evaluation-form { }
.mt-evaluation-form__header { }
.mt-evaluation-form__section { }
.mt-evaluation-form__field { }
.mt-evaluation-form__field-label { }
.mt-evaluation-form__field-input { }
.mt-evaluation-form__actions { }
.mt-evaluation-form__submit-btn { }

/* States */
.mt-evaluation-form--loading { }
.mt-evaluation-form__field--error { }
.mt-evaluation-form__submit-btn--disabled { }
```

## Component Development Patterns

### 1. Card Component Pattern

```css
.mt-card {
    background: var(--mt-white);
    border-radius: var(--mt-radius-md);
    padding: var(--mt-space-lg);
    box-shadow: var(--mt-shadow-sm);
    transition: var(--mt-transition);
}

.mt-card:hover {
    box-shadow: var(--mt-shadow-md);
    transform: translateY(-2px);
}

.mt-card__header {
    border-bottom: 1px solid var(--mt-border);
    margin-bottom: var(--mt-space-lg);
    padding-bottom: var(--mt-space-lg);
}

.mt-card__title {
    font-size: var(--mt-font-size-lg);
    font-weight: var(--mt-font-semibold);
    color: var(--mt-text-dark);
    margin: 0;
}

.mt-card__body {
    color: var(--mt-text-light);
    line-height: var(--mt-line-height-relaxed);
}

.mt-card__footer {
    border-top: 1px solid var(--mt-border);
    margin-top: var(--mt-space-lg);
    padding-top: var(--mt-space-lg);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
```

### 2. Button Component Pattern

```css
.mt-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--mt-space-sm) var(--mt-space-lg);
    border-radius: var(--mt-radius-sm);
    font-weight: var(--mt-font-medium);
    text-decoration: none;
    cursor: pointer;
    transition: var(--mt-transition-colors);
    border: var(--mt-border-width) solid transparent;
    min-height: var(--mt-touch-target);
}

.mt-btn--primary {
    background: var(--mt-primary);
    color: var(--mt-white);
    border-color: var(--mt-primary);
}

.mt-btn--primary:hover {
    background: var(--mt-primary-dark);
    border-color: var(--mt-primary-dark);
}

.mt-btn--secondary {
    background: transparent;
    color: var(--mt-primary);
    border-color: var(--mt-primary);
}

.mt-btn--secondary:hover {
    background: var(--mt-primary);
    color: var(--mt-white);
}

.mt-btn--large {
    padding: var(--mt-space-md) var(--mt-space-xl);
    font-size: var(--mt-font-size-lg);
    min-height: var(--mt-touch-target-lg);
}
```

### 3. Form Component Pattern

```css
.mt-form-group {
    margin-bottom: var(--mt-space-lg);
}

.mt-form-label {
    display: block;
    margin-bottom: var(--mt-space-sm);
    font-weight: var(--mt-font-medium);
    color: var(--mt-text-dark);
}

.mt-form-input {
    width: 100%;
    padding: var(--mt-space-sm) var(--mt-space-md);
    border: var(--mt-border-width) solid var(--mt-border);
    border-radius: var(--mt-radius-sm);
    font-size: var(--mt-font-size-base);
    transition: var(--mt-transition-colors);
    min-height: var(--mt-touch-target);
}

.mt-form-input:focus {
    outline: none;
    border-color: var(--mt-primary);
    box-shadow: var(--mt-focus-ring);
}

.mt-form-input--error {
    border-color: var(--mt-error);
}

.mt-form-error {
    color: var(--mt-error);
    font-size: var(--mt-font-size-sm);
    margin-top: var(--mt-space-xs);
}
```

## Mobile-First Development

### Breakpoint Strategy

```css
/* Mobile-first approach - base styles are mobile */
.mt-component {
    /* Mobile styles (default) */
    display: block;
    padding: var(--mt-space-sm);
}

/* Tablet and up */
@media (min-width: 768px) {
    .mt-component {
        display: flex;
        padding: var(--mt-space-lg);
    }
}

/* Desktop and up */
@media (min-width: 1024px) {
    .mt-component {
        padding: var(--mt-space-xl);
    }
}
```

### Touch-Friendly Design

```css
/* Ensure minimum touch target size */
.mt-interactive-element {
    min-height: var(--mt-touch-target); /* 44px */
    min-width: var(--mt-touch-target);
}

/* Larger touch targets for primary actions */
.mt-primary-action {
    min-height: var(--mt-touch-target-lg); /* 48px */
}
```

### Mobile Table Patterns

```css
/* Transform table to cards on mobile */
@media (max-width: 767px) {
    .mt-table {
        display: block;
    }
    
    .mt-table__row {
        display: block;
        margin-bottom: var(--mt-space-lg);
        background: var(--mt-white);
        border-radius: var(--mt-radius-md);
        padding: var(--mt-space-lg);
        box-shadow: var(--mt-shadow-sm);
    }
    
    .mt-table__cell {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: var(--mt-space-sm) 0;
        border-bottom: 1px solid var(--mt-border);
    }
    
    .mt-table__cell:before {
        content: attr(data-label) ": ";
        font-weight: var(--mt-font-medium);
        color: var(--mt-text-dark);
    }
}
```

## Template Development

### HTML Structure Patterns

#### Basic Page Template
```html
<div class="mt-root">
    <div class="mt-container">
        
        <!-- Page Header -->
        <header class="mt-page-header">
            <h1 class="mt-page-title">Page Title</h1>
            <div class="mt-page-actions">
                <button class="mt-btn mt-btn--primary">Primary Action</button>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="mt-page-content">
            <div class="mt-content-grid">
                
                <!-- Sidebar/Filters -->
                <aside class="mt-sidebar">
                    <div class="mt-filter-panel">
                        <!-- Filter content -->
                    </div>
                </aside>
                
                <!-- Main Content Area -->
                <section class="mt-main-content">
                    <!-- Primary content -->
                </section>
                
            </div>
        </main>
        
    </div>
</div>
```

#### Card Grid Layout
```html
<div class="mt-card-grid">
    <article class="mt-candidate-card">
        <div class="mt-candidate-card__image">
            <img src="photo.jpg" alt="Candidate Name">
        </div>
        <div class="mt-candidate-card__content">
            <h3 class="mt-candidate-card__title">Candidate Name</h3>
            <p class="mt-candidate-card__description">Brief description...</p>
        </div>
        <div class="mt-candidate-card__actions">
            <button class="mt-btn mt-btn--primary">View Details</button>
            <button class="mt-btn mt-btn--secondary">Evaluate</button>
        </div>
    </article>
</div>
```

## PHP Integration

### Enqueueing v4 Styles

The framework is automatically loaded on plugin routes. For custom pages or shortcodes:

```php
// In your PHP class
public function enqueue_custom_styles() {
    // v4 framework will be automatically loaded
    // Add your custom styles that depend on v4
    wp_enqueue_style(
        'mt-custom-component',
        MT_PLUGIN_URL . 'assets/css/custom/my-component.css',
        ['mt-v4-components'], // Depend on v4 components
        MT_VERSION
    );
}
```

### Dynamic Token Override

```php
// Override specific tokens via PHP
public function add_custom_tokens() {
    $custom_css = ':root {
        --mt-primary: ' . esc_attr(get_option('custom_primary_color', '#26a69a')) . ';
    }';
    
    wp_add_inline_style('mt-v4-tokens', $custom_css);
}
```

### Conditional Mobile Styles

```php
// Add mobile-specific enhancements
public function maybe_add_mobile_styles() {
    if (wp_is_mobile()) {
        wp_add_inline_style('mt-v4-mobile-jury', '
            .mt-jury-filters { 
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                z-index: var(--mt-z-fixed);
            }
        ');
    }
}
```

## Common Development Patterns

### 1. State Management with CSS

```css
/* Loading states */
.mt-component[data-loading="true"] {
    opacity: var(--mt-opacity-50);
    cursor: wait;
}

.mt-component[data-loading="true"]:before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid var(--mt-primary);
    border-radius: 50%;
    border-top-color: transparent;
    animation: var(--mt-animate-spin);
}

/* Error states */
.mt-component[data-error="true"] {
    border-color: var(--mt-error);
    background: rgba(244, 67, 54, 0.05);
}

/* Success states */
.mt-component[data-success="true"] {
    border-color: var(--mt-success);
    background: rgba(76, 175, 80, 0.05);
}
```

### 2. JavaScript Integration

```javascript
// Work with v4 classes in JavaScript
class MTComponentManager {
    constructor(element) {
        this.element = element;
        this.loadingClass = 'mt-component--loading';
        this.errorClass = 'mt-component--error';
    }
    
    showLoading() {
        this.element.classList.add(this.loadingClass);
        this.element.setAttribute('data-loading', 'true');
    }
    
    hideLoading() {
        this.element.classList.remove(this.loadingClass);
        this.element.removeAttribute('data-loading');
    }
    
    showError(message) {
        this.element.classList.add(this.errorClass);
        this.element.setAttribute('data-error', 'true');
        
        // Use tokens for dynamic styling
        this.element.style.setProperty('--component-error-color', 'var(--mt-error)');
    }
}
```

### 3. CSS Custom Property Manipulation

```css
/* Component with customizable properties */
.mt-progress-bar {
    width: 100%;
    height: var(--mt-space-sm);
    background: var(--mt-gray-200);
    border-radius: var(--mt-radius-full);
    overflow: hidden;
    
    --progress-width: 0%;
    --progress-color: var(--mt-primary);
}

.mt-progress-bar__fill {
    width: var(--progress-width);
    height: 100%;
    background: var(--progress-color);
    transition: width 0.3s ease;
}
```

```javascript
// Update progress via CSS custom properties
function updateProgress(element, percentage) {
    element.style.setProperty('--progress-width', percentage + '%');
    
    // Change color based on progress
    if (percentage >= 100) {
        element.style.setProperty('--progress-color', 'var(--mt-success)');
    } else if (percentage >= 75) {
        element.style.setProperty('--progress-color', 'var(--mt-primary)');
    } else if (percentage >= 50) {
        element.style.setProperty('--progress-color', 'var(--mt-warning)');
    } else {
        element.style.setProperty('--progress-color', 'var(--mt-error)');
    }
}
```

## Testing & Quality Assurance

### CSS Validation

```bash
# Validate your custom CSS
npm run css-validate

# Check for v4 token usage
npm run check-token-usage

# Test responsive breakpoints
npm run test-responsive
```

### Browser Testing Checklist

- ✅ **Chrome 120+** - Primary development browser
- ✅ **Firefox 115+** - CSS Grid and Flexbox validation  
- ✅ **Safari 16+** - WebKit compatibility
- ✅ **Edge 120+** - Chromium-based compatibility
- ✅ **Mobile Safari iOS 16+** - Touch interaction testing
- ✅ **Chrome Mobile Android 12+** - Mobile performance

### Performance Guidelines

```css
/* Optimize animations for performance */
.mt-component {
    /* Use transform and opacity for animations */
    transform: translateX(0);
    opacity: 1;
    transition: transform 0.3s ease, opacity 0.3s ease;
    
    /* Promote to compositor layer if needed */
    will-change: transform, opacity;
}

/* Avoid animating layout-triggering properties */
.mt-component:hover {
    /* ✅ Good - triggers composite layer */
    transform: scale(1.05);
    
    /* ❌ Bad - triggers layout/paint */
    /* width: 110%; */
}
```

## Debugging & Troubleshooting

### Common Issues & Solutions

#### 1. Styles Not Applying

**Issue:** Custom styles not overriding v4 defaults

**Solution:** Check CSS specificity and use proper BEM nesting
```css
/* ❌ Too generic */
.card { color: red; }

/* ✅ Properly specific */
.mt-candidate-card .mt-candidate-card__title { 
    color: var(--mt-error); 
}
```

#### 2. Mobile Styles Not Working

**Issue:** Desktop styles overriding mobile

**Solution:** Use mobile-first approach
```css
/* ❌ Desktop-first (wrong) */
.mt-component {
    display: flex; /* Desktop style */
}

@media (max-width: 767px) {
    .mt-component {
        display: block; /* Mobile override */
    }
}

/* ✅ Mobile-first (correct) */
.mt-component {
    display: block; /* Mobile default */
}

@media (min-width: 768px) {
    .mt-component {
        display: flex; /* Desktop enhancement */
    }
}
```

#### 3. Token Not Working

**Issue:** CSS custom property not applying

**Solution:** Check token exists and is properly scoped
```css
/* Check if token exists */
.debug-tokens:before {
    content: var(--mt-primary, 'TOKEN MISSING');
}

/* Ensure proper scope */
.mt-root {
    --mt-custom-token: blue;
}

.mt-root .mt-component {
    color: var(--mt-custom-token);
}
```

### Debug Tools

#### CSS Inspector
```css
/* Add debug borders to see layout */
.debug * {
    outline: 1px solid red !important;
}

/* Show token values */
.debug:after {
    content: 
        'Primary: ' var(--mt-primary) ' | '
        'Spacing: ' var(--mt-space-lg);
    position: fixed;
    top: 0;
    left: 0;
    background: black;
    color: white;
    padding: 10px;
    z-index: 9999;
}
```

#### JavaScript Token Inspector
```javascript
// Check token values in browser console
function inspectTokens() {
    const root = document.documentElement;
    const computedStyle = getComputedStyle(root);
    
    const tokens = [
        '--mt-primary',
        '--mt-space-lg',
        '--mt-font-size-base'
    ];
    
    tokens.forEach(token => {
        const value = computedStyle.getPropertyValue(token);
        console.log(`${token}: ${value}`);
    });
}

// Usage: inspectTokens()
```

## Best Practices Summary

### Do's ✅

1. **Always use tokens** instead of hardcoded values
2. **Follow BEM naming** with `.mt-` prefix  
3. **Design mobile-first** with progressive enhancement
4. **Test across browsers** during development
5. **Use semantic class names** that describe purpose
6. **Leverage CSS custom properties** for dynamic styling
7. **Follow component patterns** established in the framework
8. **Validate CSS** before committing changes

### Don'ts ❌

1. **Never hardcode colors, spacing, or typography values**
2. **Don't use !important** unless absolutely necessary
3. **Avoid deep selector nesting** (max 3 levels)
4. **Don't animate layout-triggering properties**
5. **Never mix v3 and v4 classes** (v3 is removed)
6. **Avoid inline styles** in templates when possible
7. **Don't override core framework tokens** without good reason
8. **Never skip responsive testing**

## Migration Checklist for Existing Code

When updating existing components:

- [ ] **Remove all v3 class references** (they won't work)
- [ ] **Replace hardcoded values** with appropriate tokens
- [ ] **Update class names** to follow BEM convention
- [ ] **Test mobile responsiveness** thoroughly
- [ ] **Validate HTML structure** follows new patterns
- [ ] **Check JavaScript dependencies** on class names
- [ ] **Verify accessibility** hasn't been broken
- [ ] **Test cross-browser compatibility**
- [ ] **Update documentation** for component changes

## Support & Resources

### Documentation
- **Token Reference:** `assets/css/v4/mt-tokens.css` (comprehensive comments)
- **Component Examples:** `doc/CSS-V4-GUIDE.md`
- **Migration Report:** `doc/CSS-PHASE-2-COMPLETION-REPORT.md`

### Tools
- **CSS Validator:** Built into development workflow
- **Token Inspector:** Browser extension or JavaScript console
- **Responsive Tester:** Browser dev tools + real device testing

### Getting Help
- **Code Review:** Use `wordpress-code-reviewer` agent for WordPress-specific guidance
- **UI Review:** Use `frontend-ui-specialist` agent for design system questions
- **Performance:** Use `syntax-error-detector` agent for optimization help

---

**Happy coding with CSS v4!** 🎨

*This guide reflects the state after Phase 2 completion. All v3 references have been removed, and v4 is now the single source of truth for plugin styling.*