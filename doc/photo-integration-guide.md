# Photo Integration & UI Enhancement Guide

## Overview
This guide documents the complete photo management system and UI enhancements implemented for the Mobility Trailblazers plugin v2.4.0.

## Photo Management System

### Components

#### 1. Direct Photo Attachment (`direct-photo-attach-complete.php`)
Complete mapping of all 52 candidates with their photos using WordPress post IDs.

**Usage:**
```bash
wp eval-file direct-photo-attach-complete.php
```

**Features:**
- Direct ID-to-photo mapping for accuracy
- Handles all 52 candidates including Günther Schuh (ID: 4444)
- Automatic media library upload
- Sets featured images for each candidate
- Progress reporting with success/failure counts

#### 2. Photo Verification (`verify-photo-matching.php`)
Comprehensive verification tool to check photo attachment status.

**Usage:**
```bash
wp eval-file verify-photo-matching.php
```

**Reports:**
- Candidates with/without featured images
- Photo file availability
- Missing mappings
- Unused photo files
- Ready-to-attach candidates

#### 3. Enhanced Matching Script (`match-photos-updated.php`)
Improved name matching algorithm for complex cases.

**Features:**
- Handles title variations (Dr., Prof. Dr.)
- Manages special characters (umlauts, hyphens)
- Partial name matching fallback
- Detailed logging of matching process

### Photo Specifications
- **Format:** WebP for optimal compression
- **Location:** `/Photos_candidates/webp/`
- **Naming:** CamelCase format (e.g., `AlexanderMöller.webp`)
- **Dimensions:** Optimized for 280x280px display

### Troubleshooting Photo Issues

| Issue | Solution |
|-------|----------|
| Photo not displaying | Run `wp eval-file verify-photo-matching.php` to check status |
| Wrong photo matched | Use direct attachment script with correct ID mapping |
| Missing photo file | Check `/Photos_candidates/webp/` directory |
| Special character issues | Ensure UTF-8 encoding in file names |

## UI Templates

### 1. Enhanced Candidate Profile (`single-mt_candidate.php`)
Already implemented with:
- Gradient hero sections by category
- Floating photo frames with hover effects
- Criteria cards with icons
- Responsive sidebar
- Social media integration

### 2. Modern Candidates Grid (`candidates-grid-enhanced.php`)
**Features:**
- Card-based layout (320px min-width)
- Live search with highlighting
- Category filtering
- Hover animations
- Social quick links

**Shortcode Usage:**
```php
[mt_candidates_grid columns="3" show_bio="yes" show_category="yes" enable_filter="yes" enable_search="yes"]
```

### 3. Jury Member Profile (`single-mt_jury.php`)
**Components:**
- Circular profile photo (200px)
- Statistics dashboard
- Expertise tags
- Activity metrics
- Professional gradient design

### 4. Interactive JavaScript (`candidate-interactions.js`)
**Features:**
- Lazy loading images
- Quick view modals
- Keyboard navigation
- Search highlighting
- Smooth animations

**Integration:**
```php
wp_enqueue_script('mt-candidate-interactions', 
    plugin_dir_url(__FILE__) . 'assets/js/candidate-interactions.js', 
    array('jquery'), '2.4.0', true);
```

## Criteria Parsing Tool

### Usage
Parse evaluation criteria text into structured meta fields:

```bash
# Process all candidates
wp eval-file tools/parse-criteria.php process

# Verify specific candidate
wp eval-file tools/parse-criteria.php verify 4377

# Clear all parsed data (for testing)
wp eval-file tools/parse-criteria.php clear
```

### Meta Fields Created
- `_mt_criterion_mut` - Mut & Pioniergeist
- `_mt_criterion_innovation` - Innovationsgrad
- `_mt_criterion_umsetzung` - Umsetzungskraft & Wirkung
- `_mt_criterion_relevanz` - Relevanz für die Mobilitätswende
- `_mt_criterion_vorbild` - Vorbildfunktion & Sichtbarkeit

## CSS Design System

### Color Palette
```css
--primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
--success: #10b981;
--warning: #f59e0b;
--error: #f44336;
--text-primary: #1f2937;
--text-secondary: #4b5563;
--background: #f8f9fa;
```

### Typography Scale
```css
--heading-xl: 3.5rem;  /* Hero titles */
--heading-lg: 2.5rem;  /* Section headers */
--heading-md: 1.8rem;  /* Subsections */
--body-lg: 1.05rem;    /* Content */
--body-md: 0.95rem;    /* Secondary text */
```

### Spacing System
```css
--space-xs: 8px;
--space-sm: 16px;
--space-md: 24px;
--space-lg: 40px;
--space-xl: 60px;
```

## Performance Considerations

### Optimizations Implemented
1. **Image Loading**
   - Lazy loading with Intersection Observer
   - WebP format for 30-50% smaller files
   - Appropriate sizing (280px for cards, 200px for jury)

2. **JavaScript**
   - Debounced search (300ms)
   - Staggered animations (50ms intervals)
   - Event delegation for dynamic content

3. **CSS**
   - GPU-accelerated transforms
   - Efficient grid layouts
   - Minimal repaints

## Migration Steps

### From v2.3.x to v2.4.0
1. **Backup database and files**
2. **Upload new files:**
   - Photo management scripts
   - Template files
   - JavaScript assets
3. **Run photo attachment:**
   ```bash
   wp eval-file direct-photo-attach-complete.php
   ```
4. **Parse criteria (optional):**
   ```bash
   wp eval-file tools/parse-criteria.php process
   ```
5. **Clear cache**
6. **Test templates**

## Browser Compatibility
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

## Future Enhancements
- [ ] AJAX pagination for grid
- [ ] Advanced filtering (multiple categories)
- [ ] Photo cropping tool
- [ ] Bulk photo upload interface
- [ ] Animation preferences setting
- [ ] Dark mode support

## Support
For issues or questions:
1. Check verification script output
2. Review browser console for JS errors
3. Verify file permissions (uploads directory)
4. Check WordPress debug log