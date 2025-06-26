# Jury Rankings System - Technical Documentation

## Overview

The Jury Rankings System provides a dynamic, real-time display of candidate rankings for jury members in the Mobility Trailblazers WordPress plugin. This system offers personalized rankings based on each jury member's evaluations, with automatic updates and modern visual design.

## Architecture

### Core Components

1. **Repository Layer** (`MT_Evaluation_Repository`)
   - `get_ranked_candidates_for_jury()` - Fetches personalized rankings for a specific jury member
   - `get_overall_rankings()` - Fetches overall rankings across all jury members

2. **AJAX Handler** (`MT_Evaluation_Ajax`)
   - `mt_get_jury_rankings` - Handles dynamic ranking requests with security validation

3. **Template System**
   - `jury-dashboard.php` - Main dashboard with conditional rankings section
   - `jury-rankings.php` - Partial template for rankings display with progress rings

4. **Frontend Assets**
   - CSS Grid-based responsive design with modern animations
   - JavaScript for dynamic updates and interactive features

## Database Schema

### Key Tables
- `wp_posts` - Candidate information (post_type: 'mt_candidate')
- `wp_postmeta` - Candidate metadata (organization, position)
- `wp_mt_evaluations` - Evaluation scores and criteria

### Query Optimization
```sql
-- Optimized query with proper JOINs and indexing
SELECT 
    p.ID, p.post_title, p.post_content,
    pm_org.meta_value as organization,
    pm_pos.meta_value as position,
    e.total_score, e.criteria_scores
FROM wp_posts p
LEFT JOIN wp_postmeta pm_org ON p.ID = pm_org.post_id AND pm_org.meta_key = 'organization'
LEFT JOIN wp_postmeta pm_pos ON p.ID = pm_pos.post_id AND pm_pos.meta_key = 'position'
INNER JOIN wp_mt_evaluations e ON p.ID = e.candidate_id
WHERE p.post_type = 'mt_candidate' 
    AND p.post_status = 'publish'
    AND e.jury_member_id = %d
    AND e.status = 'completed'
ORDER BY e.total_score DESC
LIMIT %d
```

## Visual Design System

### Grid Layout Architecture
```css
.mt-rankings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
    gap: 1.5rem;
    padding: 1.5rem;
}
```

### Position Badge System
- **Triangular Design**: CSS-based triangular badges with rotated numbers
- **Medal Colors**: Gold (#FFD700), Silver (#C0C0C0), Bronze (#CD7F32)
- **Responsive Sizing**: Adaptive badge sizes for different screen sizes

### Progress Ring Visualizations
```html
<!-- SVG-based circular progress indicators -->
<svg class="score-ring" viewBox="0 0 36 36">
    <path class="score-ring-bg" d="M18 2.0845..."/>
    <path class="score-ring-progress" 
          stroke-dasharray="0 100" 
          data-score="85"/>
</svg>
```

### Animation System
- **Page Load**: Staggered slide-in animations (0.1s delay between cards)
- **Score Rings**: Progressive reveal with cubic-bezier easing
- **Hover Effects**: Smooth color transitions and elevation changes
- **Click Feedback**: Scale animations for tactile response

## JavaScript Functionality

### Dynamic Updates
```javascript
// AJAX-based ranking updates
function updateRankings() {
    jQuery.ajax({
        url: mt_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'mt_get_jury_rankings',
            nonce: mt_ajax.nonce,
            limit: rankingsLimit
        },
        success: function(response) {
            if (response.success) {
                animateRankingsUpdate(response.data.html);
            }
        }
    });
}
```

### Interactive Features
- **Hover Effects**: Color transitions and elevation changes
- **Click Handling**: Scale animations and navigation to evaluation forms
- **Periodic Refresh**: Automatic updates every 30 seconds
- **Smooth Animations**: CSS transitions with optimized timing

## Security Implementation

### AJAX Security
```php
// Nonce verification
if (!wp_verify_nonce($_POST['nonce'], 'mt_jury_rankings_nonce')) {
    wp_die('Security check failed');
}

// Permission validation
if (!current_user_can('mt_submit_evaluations')) {
    wp_die('Insufficient permissions');
}
```

### Data Sanitization
- Input validation for all parameters
- SQL preparation with proper escaping
- Output sanitization for display

## Performance Optimization

### Database Optimization
- **Indexed Queries**: Proper indexing on frequently queried columns
- **Efficient JOINs**: Optimized table relationships
- **Result Limiting**: Configurable result sets (5-20 candidates)

### Frontend Performance
- **CSS Grid**: Hardware-accelerated layout system
- **Efficient Animations**: GPU-accelerated transforms
- **Minimal DOM Updates**: Targeted element modifications
- **Debounced Updates**: Prevents excessive AJAX calls

## Configuration Options

### Admin Settings
```php
// Settings registration
register_setting('mt_options', 'show_rankings', array(
    'type' => 'boolean',
    'default' => true
));

register_setting('mt_options', 'rankings_limit', array(
    'type' => 'integer',
    'default' => 10,
    'sanitize_callback' => function($value) {
        return max(5, min(20, intval($value)));
    }
));
```

### Template Integration
```php
// Conditional display based on settings
if (get_option('show_rankings', true)) {
    $rankings_limit = get_option('rankings_limit', 10);
    include MT_PLUGIN_DIR . 'templates/frontend/partials/jury-rankings.php';
}
```

## Responsive Design

### Breakpoint System
- **Mobile**: Single column layout (< 768px)
- **Tablet**: Adaptive grid columns (768px - 1024px)
- **Desktop**: Multi-column layout (> 1024px)

### Touch Optimization
- **Touch Targets**: Minimum 44px touch areas
- **Gesture Support**: Optimized for touch interactions
- **Mobile Performance**: Reduced animations on mobile devices

## Accessibility Features

### ARIA Support
- **Semantic HTML**: Proper heading hierarchy and landmarks
- **Screen Reader Support**: Descriptive text and labels
- **Keyboard Navigation**: Full keyboard accessibility
- **Color Contrast**: WCAG AA compliant color ratios

### Focus Management
- **Visible Focus Indicators**: Clear focus states for all interactive elements
- **Logical Tab Order**: Intuitive keyboard navigation flow
- **Skip Links**: Quick navigation to main content areas

## Customization Guide

### CSS Customization
```css
/* Custom medal colors */
.mt-ranking-card.position-1 .position-badge {
    background: linear-gradient(135deg, #FFD700, #FFA500);
}

/* Custom grid layout */
.mt-rankings-grid {
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 2rem;
}
```

### Template Customization
```php
// Custom ranking display logic
$custom_rankings = apply_filters('mt_custom_rankings_display', $rankings);
```

### JavaScript Extensions
```javascript
// Custom animation timing
jQuery(document).on('mt-rankings-updated', function(event, data) {
    // Custom post-update logic
});
```

## Testing Strategy

### Unit Testing
- Repository method testing with mock data
- AJAX handler validation testing
- Template rendering verification

### Integration Testing
- End-to-end ranking display testing
- Cross-browser compatibility testing
- Mobile responsiveness validation

### Performance Testing
- Database query performance analysis
- Frontend animation performance testing
- Memory usage optimization

## Troubleshooting

### Common Issues
1. **Rankings Not Displaying**: Check `show_rankings` setting and user permissions
2. **AJAX Errors**: Verify nonce generation and AJAX URL configuration
3. **Performance Issues**: Check database indexing and query optimization
4. **Visual Glitches**: Verify CSS compatibility and browser support

### Debug Mode
```php
// Enable debug logging
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('MT Rankings Debug: ' . $debug_message);
}
```

## Future Enhancements

### Planned Features
- **Advanced Filtering**: Filter by organization, position, or date range
- **Export Functionality**: CSV/PDF export of rankings
- **Comparative Analysis**: Side-by-side candidate comparison
- **Real-time Collaboration**: Live updates across multiple jury members

### Performance Improvements
- **Caching Layer**: Redis/Memcached integration for faster queries
- **Lazy Loading**: Progressive loading of ranking data
- **WebSocket Integration**: Real-time updates without polling

## API Reference

### Repository Methods
```php
/**
 * Get ranked candidates for a specific jury member
 * @param int $jury_member_id
 * @param int $limit
 * @return array
 */
public function get_ranked_candidates_for_jury($jury_member_id, $limit = 10)

/**
 * Get overall rankings across all jury members
 * @param int $limit
 * @return array
 */
public function get_overall_rankings($limit = 10)
```

### AJAX Endpoints
```php
/**
 * AJAX handler for jury rankings
 * Action: mt_get_jury_rankings
 * Nonce: mt_jury_rankings_nonce
 * Capability: mt_submit_evaluations
 */
public function get_jury_rankings()
```

### Template Functions
```php
/**
 * Render rankings partial template
 * @param array $rankings
 * @param int $limit
 * @return string
 */
private function render_rankings_template($rankings, $limit)
```

## Version History

### v2.0.9 (Initial Release)
- Basic ranking system with medal indicators
- AJAX-powered dynamic updates
- Responsive card-based layout
- Admin configuration options

### v2.0.10 (Enhanced Design)
- Modern CSS Grid layout system
- SVG progress ring visualizations
- Enhanced animation system
- Improved interactivity and hover effects
- Advanced responsive design
- Performance optimizations

## Support and Maintenance

### Regular Maintenance
- Database query optimization reviews
- CSS/JS performance monitoring
- Security updates and vulnerability checks
- Browser compatibility testing

### Update Procedures
- Database migration scripts for schema changes
- Backward compatibility maintenance
- User notification system for breaking changes
- Rollback procedures for critical issues 