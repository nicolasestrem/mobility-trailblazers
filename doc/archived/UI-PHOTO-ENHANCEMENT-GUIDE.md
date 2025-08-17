# Mobility Trailblazers - UI & Photo Enhancement Guide
*Last Updated: August 16, 2025 | Version 2.4.1*

## Table of Contents
1. [Overview](#overview)
2. [Photo Management System](#photo-management-system)
3. [UI Templates](#ui-templates)
4. [CSS Design System](#css-design-system)
5. [JavaScript Interactions](#javascript-interactions)
6. [Jury Grid Display Fix](#jury-grid-display-fix)
7. [Implementation Guide](#implementation-guide)
8. [Troubleshooting](#troubleshooting)

---

## Overview

This guide documents the complete photo management system and UI enhancements implemented for the Mobility Trailblazers platform, including modern template designs, interactive features, and responsive grid layouts.

## Photo Management System

### Directory Structure
```
/Photos_candidates/
├── webp/                                # 50+ candidate photos in WebP format
└── mobility_trailblazers_candidates.csv # Photo-to-candidate mapping
```

### Photo Attachment Scripts

#### 1. Direct Photo Attachment (Complete)
**File**: `direct-photo-attach-complete.php`

Complete mapping of all 52 candidates with their photos using WordPress post IDs:

```php
$photo_mappings = [
    4377 => 'AlexanderMöller.webp',        // Alexander Möller
    4379 => 'AndreasHerrmann.webp',        // Andreas Herrmann
    4381 => 'AndreasKnie.webp',            // Andreas Knie
    // ... all 52 candidates
    4444 => 'GüntherSchuh.webp'            // Special case: Günther Schuh
];
```

**Usage**:
```bash
wp eval-file direct-photo-attach-complete.php
```

**Features**:
- Direct ID-to-photo mapping for accuracy
- Handles all candidates including special cases
- Automatic media library upload
- Sets featured images
- Progress reporting

#### 2. Enhanced Matching Script
**File**: `match-photos-updated.php`

Improved name matching algorithm for complex cases:

```php
// Handles variations:
- Title removal (Dr., Prof. Dr.)
- Special characters (ä, ö, ü, ß)
- Hyphenated names
- Partial matching fallback
```

#### 3. Photo Verification Tool
**File**: `verify-photo-matching.php`

```bash
wp eval-file verify-photo-matching.php
```

**Reports**:
- Candidates with/without featured images
- Photo file availability
- Missing mappings
- Unused photo files

### Photo Specifications
- **Format**: WebP for optimal compression
- **Location**: `/Photos_candidates/webp/`
- **Naming**: CamelCase (e.g., `AlexanderMöller.webp`)
- **Display Size**: 280x280px for cards, 200px for jury

---

## UI Templates

### 1. Enhanced Candidate Profile
**File**: `templates/frontend/single/single-mt_candidate.php`

**Features**:
- Gradient hero sections by category
- Floating photo frames with hover effects
- Criteria cards with icons
- Responsive sidebar
- Social media integration

**Hero Section**:
```html
<div class="hero-section category-<?php echo $category_class; ?>">
    <div class="hero-pattern"></div>
    <div class="hero-content">
        <!-- Content -->
    </div>
</div>
```

**Category Gradients**:
- Startup: Purple to pink gradient
- Gov: Blue to teal gradient  
- Tech: Orange to red gradient

### 2. Modern Candidates Grid
**File**: `templates/frontend/candidates-grid-enhanced.php`

**Features**:
- Card-based layout (320px min-width)
- Live search with debounce
- Category filtering
- Hover animations
- Social quick links

**Shortcode**:
```php
[mt_candidates_grid 
    columns="3" 
    show_bio="yes" 
    show_category="yes" 
    enable_filter="yes" 
    enable_search="yes"]
```

### 3. Jury Member Profile
**File**: `templates/frontend/single/single-mt_jury.php`

**Components**:
- Circular profile photo (200px)
- Statistics dashboard
- Expertise tags
- Activity metrics
- Professional gradient

**Statistics Display**:
```php
- Assigned Candidates: <?php echo $assigned_count; ?>
- Submitted Evaluations: <?php echo $submitted_count; ?>
- Draft Evaluations: <?php echo $draft_count; ?>
- Average Score: <?php echo $average_score; ?>
```

---

## CSS Design System

### Color Palette
```css
:root {
    /* Primary Colors */
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --primary-blue: #3b82f6;
    --primary-dark: #1e3a8a;
    
    /* Category Colors */
    --startup-gradient: linear-gradient(135deg, #667eea, #f093fb);
    --gov-gradient: linear-gradient(135deg, #4facfe, #00f2fe);
    --tech-gradient: linear-gradient(135deg, #fa709a, #fee140);
    
    /* Status Colors */
    --success: #10b981;
    --warning: #f59e0b;
    --error: #f44336;
    
    /* Text Colors */
    --text-primary: #1f2937;
    --text-secondary: #4b5563;
    --text-muted: #9ca3af;
    
    /* Backgrounds */
    --bg-primary: #ffffff;
    --bg-secondary: #f9fafb;
    --bg-tertiary: #f3f4f6;
}
```

### Typography Scale
```css
/* Headings */
.heading-xl { font-size: 3.5rem; font-weight: 700; }
.heading-lg { font-size: 2.5rem; font-weight: 700; }
.heading-md { font-size: 1.8rem; font-weight: 600; }
.heading-sm { font-size: 1.3rem; font-weight: 600; }

/* Body Text */
.body-lg { font-size: 1.05rem; line-height: 1.8; }
.body-md { font-size: 0.95rem; line-height: 1.6; }
.body-sm { font-size: 0.875rem; line-height: 1.5; }
```

### Component Styles

#### Cards
```css
.card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}
```

#### Buttons
```css
.btn {
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-primary {
    background: var(--primary-gradient);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
}
```

#### Animations
```css
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-20px); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animate-fade-in-up {
    animation: fadeInUp 0.6s ease forwards;
}
```

---

## JavaScript Interactions

### File: `assets/js/candidate-interactions.js`

#### Features

##### 1. Lazy Loading
```javascript
const imageObserver = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.add('loaded');
            imageObserver.unobserve(img);
        }
    });
});

document.querySelectorAll('img[data-src]').forEach(img => {
    imageObserver.observe(img);
});
```

##### 2. Live Search
```javascript
const searchInput = document.querySelector('#candidate-search');
const searchHandler = debounce((e) => {
    const term = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.candidate-card');
    
    cards.forEach(card => {
        const name = card.dataset.name.toLowerCase();
        const org = card.dataset.org.toLowerCase();
        
        if (name.includes(term) || org.includes(term)) {
            card.style.display = '';
            highlightMatch(card, term);
        } else {
            card.style.display = 'none';
        }
    });
}, 300);

searchInput.addEventListener('input', searchHandler);
```

##### 3. Category Filtering
```javascript
document.querySelectorAll('.category-filter').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const category = e.target.dataset.category;
        
        // Update active state
        document.querySelectorAll('.category-filter').forEach(b => {
            b.classList.remove('active');
        });
        e.target.classList.add('active');
        
        // Filter cards with animation
        filterWithAnimation(category);
    });
});

function filterWithAnimation(category) {
    const cards = document.querySelectorAll('.candidate-card');
    
    cards.forEach((card, index) => {
        setTimeout(() => {
            if (category === 'all' || card.dataset.category === category) {
                card.style.display = '';
                card.classList.add('animate-fade-in-up');
            } else {
                card.style.display = 'none';
            }
        }, index * 50); // Staggered animation
    });
}
```

##### 4. Quick View Modal
```javascript
function openQuickView(candidateId) {
    // Fetch candidate data via AJAX
    fetch(`/wp-json/mt/v1/candidate/${candidateId}`)
        .then(response => response.json())
        .then(data => {
            const modal = createModal(data);
            document.body.appendChild(modal);
            modal.classList.add('show');
        });
}

function createModal(data) {
    const modal = document.createElement('div');
    modal.className = 'quick-view-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <button class="close-modal">&times;</button>
            <img src="${data.photo}" alt="${data.name}">
            <h2>${data.name}</h2>
            <p>${data.organization}</p>
            <p>${data.position}</p>
            <div class="modal-actions">
                <a href="${data.permalink}" class="btn btn-primary">View Full Profile</a>
            </div>
        </div>
    `;
    return modal;
}
```

---

## Jury Grid Display Fix (v2.4.1)

### Problem Identification
The jury member cards on the voting page had:
1. **Inconsistent sizes** - Variable heights based on content
2. **No interactivity** - Static display without links

### Solutions Implemented

#### 1. CSS Grid Standardization
**File**: `assets/css/frontend.css`

```css
/* Fixed-size grid items */
.mt-candidate-grid-item {
    background: #FFFFFF !important;
    border: 2px solid #E5E7EB !important;
    border-radius: 12px !important;
    padding: 20px !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    text-align: center !important;
    min-height: 320px !important;
    height: 100% !important;
}

/* Fixed image container */
.mt-candidate-image {
    width: 150px !important;
    height: 150px !important;
    margin: 0 auto 15px !important;
    overflow: hidden !important;
    border-radius: 8px !important;
    background: #F5F5F5 !important;
}

/* Text truncation */
.mt-candidate-info h3 {
    display: -webkit-box !important;
    -webkit-line-clamp: 2 !important;
    -webkit-box-orient: vertical !important;
    overflow: hidden !important;
}
```

#### 2. Adding Click Functionality
**File**: `templates/frontend/candidates-grid.php`

```php
<div class="mt-candidate-grid-item" data-candidate-id="<?php echo get_the_ID(); ?>">
    <a href="<?php the_permalink(); ?>" class="mt-candidate-link">
        <!-- Card content -->
    </a>
</div>
```

#### 3. Hover Effects
```css
.mt-candidate-grid-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
}

.mt-candidate-grid-item:hover .mt-candidate-image img {
    transform: scale(1.05);
}

.mt-candidate-grid-item:hover h3 {
    color: #C1693C; /* Brand accent */
}
```

#### 4. Responsive Breakpoints
```css
/* Responsive columns */
@media (min-width: 1400px) {
    .mt-candidates-grid {
        grid-template-columns: repeat(5, 1fr);
    }
}

@media (min-width: 1200px) and (max-width: 1399px) {
    .mt-candidates-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

@media (min-width: 992px) and (max-width: 1199px) {
    .mt-candidates-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 768px) and (max-width: 991px) {
    .mt-candidates-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767px) {
    .mt-candidates-grid {
        grid-template-columns: 1fr;
    }
}
```

### Results
✅ Clean, uniform grid presentation  
✅ Interactive cards with hover effects  
✅ Clear navigation to individual profiles  
✅ Professional appearance matching brand standards  
✅ Perfect responsive behavior on all devices  

---

## Implementation Guide

### Step 1: Run Photo Attachment
```bash
# Attach all photos to candidates
wp eval-file direct-photo-attach-complete.php

# Verify attachment
wp eval-file verify-photo-matching.php
```

### Step 2: Update Templates
1. Replace existing templates with enhanced versions
2. Clear any caching plugins
3. Test on staging environment first

### Step 3: Add JavaScript
1. Enqueue `candidate-interactions.js`:
```php
wp_enqueue_script(
    'mt-candidate-interactions',
    plugin_dir_url(__FILE__) . 'assets/js/candidate-interactions.js',
    array('jquery'),
    '2.4.0',
    true
);
```

### Step 4: Configure Settings
```php
// In theme functions.php or plugin
add_filter('mt_grid_default_columns', function() {
    return 3; // Default columns
});

add_filter('mt_enable_quick_view', '__return_true');
add_filter('mt_enable_lazy_loading', '__return_true');
```

---

## Troubleshooting

### Photo Issues

| Issue | Solution |
|-------|----------|
| Photo not displaying | Run verify script to check attachment |
| Wrong photo matched | Use direct attachment script with ID |
| Missing photo file | Check `/Photos_candidates/webp/` |
| Special characters | Ensure UTF-8 encoding |

### Grid Display Issues

| Issue | Solution |
|-------|----------|
| Uneven card heights | Check min-height CSS is applied |
| Cards not clickable | Verify anchor tags in template |
| Hover not working | Check for CSS conflicts |
| Not responsive | Verify media queries loaded |

### JavaScript Issues

| Issue | Solution |
|-------|----------|
| Search not working | Check debounce function defined |
| Filters not working | Verify data attributes on cards |
| Animations janky | Reduce stagger delay |
| Modal not opening | Check AJAX endpoint configured |

### Performance

1. **Optimize Images**:
   - Use WebP format
   - Implement lazy loading
   - Serve appropriate sizes

2. **Minimize Repaints**:
   - Use transform for animations
   - Batch DOM updates
   - Debounce search/filter

3. **Cache Assets**:
   - Enable browser caching
   - Use CDN if available
   - Minify CSS/JS

---

## Browser Compatibility

Tested and working on:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Accessibility Features

- Alt text for all images
- ARIA labels for interactive elements
- Keyboard navigation support
- Focus indicators
- Screen reader compatible
- Proper heading hierarchy
- Color contrast compliance

---

*End of UI & Photo Enhancement Guide*