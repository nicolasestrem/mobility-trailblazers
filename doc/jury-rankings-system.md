# Jury Rankings System - Technical Documentation

## Overview

The Jury Rankings System provides a dynamic, real-time display of candidate rankings for jury members in the Mobility Trailblazers WordPress plugin. This system offers personalized rankings based on each jury member's evaluations, with automatic updates and modern visual design. **Version 2.0.9** introduces a new 5x2 grid layout with inline evaluation controls for enhanced user experience.

## Recent Updates (v2.0.9)

### New Features
- **5x2 Grid Layout**: Fixed 10-candidate display in a 5-column by 2-row grid
- **Inline Evaluation Controls**: Direct score adjustment without page navigation
- **Real-time Score Updates**: Instant visual feedback with mini progress rings
- **Enhanced Responsive Design**: Adaptive grid that works on all screen sizes
- **Auto-refresh Rankings**: Automatic updates every 30 seconds
- **Success Animations**: Visual feedback for saved evaluations

### Technical Improvements
- **AJAX-powered Inline Saves**: New `mt_save_inline_evaluation` endpoint
- **Enhanced Security**: Improved nonce verification for inline operations
- **Performance Optimization**: Efficient DOM updates and reduced server load
- **Better Error Handling**: Graceful fallbacks and user feedback

## Architecture

### Core Components

1. **Repository Layer** (`MT_Evaluation_Repository`)
   - `get_ranked_candidates_for_jury()` - Fetches personalized rankings for a specific jury member
   - `get_overall_rankings()` - Fetches overall rankings across all jury members

2. **AJAX Handler** (`MT_Evaluation_Ajax`)
   - `mt_get_jury_rankings` - Handles dynamic ranking requests with security validation
   - `mt_save_inline_evaluation` - **NEW**: Handles inline evaluation saves

3. **Template System**
   - `jury-dashboard.php` - Main dashboard with conditional rankings section
   - `jury-rankings.php` - **UPDATED**: Partial template with 5x2 grid and inline controls

4. **Frontend Assets**
   - **UPDATED**: CSS Grid-based 5x2 layout with responsive breakpoints
   - **NEW**: JavaScript for inline evaluation interactions and real-time updates

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
LIMIT 10  -- Fixed limit for 5x2 grid
```

## Visual Design System

### 5x2 Grid Layout Architecture
```css
/* Primary 5x2 Grid Layout */
.mt-rankings-grid.mt-rankings-5x2 {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    grid-template-rows: repeat(2, 1fr);
    gap: 20px;
    padding: 20px;
    max-width: 1400px;
    margin: 0 auto;
}

/* Responsive Breakpoints */
@media (max-width: 1400px) {
    .mt-rankings-grid.mt-rankings-5x2 {
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: repeat(3, 1fr);
    }
}

@media (max-width: 1024px) {
    .mt-rankings-grid.mt-rankings-5x2 {
        grid-template-columns: repeat(3, 1fr);
        grid-template-rows: repeat(4, 1fr);
    }
}

@media (max-width: 768px) {
    .mt-rankings-grid.mt-rankings-5x2 {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(5, 1fr);
    }
}

@media (max-width: 480px) {
    .mt-rankings-grid.mt-rankings-5x2 {
        grid-template-columns: 1fr;
        grid-template-rows: auto;
    }
}
```

### Enhanced Position Badge System
- **Corner Badge Design**: Absolute positioned circular badges in top-right corner
- **Medal Colors**: Gold (#FFD700), Silver (#C0C0C0), Bronze (#CD7F32)
- **Gradient Backgrounds**: Modern gradient styling for visual appeal
- **Responsive Sizing**: Adaptive badge sizes for different screen sizes

### Inline Evaluation Controls
```html
<!-- Inline evaluation form structure -->
<div class="mt-inline-evaluation-controls">
    <form class="mt-inline-evaluation-form" data-candidate-id="<?php echo esc_attr($candidate_id); ?>">
        <?php wp_nonce_field('mt_inline_evaluation_' . $candidate_id, 'mt_inline_nonce'); ?>
        
        <div class="mt-criteria-grid-inline">
            <!-- Individual criterion controls -->
            <div class="mt-criterion-inline">
                <label class="mt-criterion-label">
                    <span class="dashicons dashicons-superhero"></span>
                    <span class="mt-criterion-short">Cou</span>
                </label>
                <div class="mt-score-control">
                    <button type="button" class="mt-score-adjust mt-score-decrease" data-action="decrease">
                        <span class="dashicons dashicons-minus"></span>
                    </button>
                    <input type="number" class="mt-score-input" value="7.5" min="0" max="10" step="0.5">
                    <button type="button" class="mt-score-adjust mt-score-increase" data-action="increase">
                        <span class="dashicons dashicons-plus"></span>
                    </button>
                </div>
                <div class="mt-score-ring-mini" data-score="7.5">
                    <!-- Mini progress ring SVG -->
                </div>
            </div>
        </div>
        
        <div class="mt-inline-actions">
            <button type="button" class="mt-btn-save-inline">Save</button>
            <a href="#" class="mt-btn-full-evaluation">Full View</a>
        </div>
    </form>
</div>
```

### Mini Progress Ring Visualizations
```html
<!-- Enhanced SVG-based circular progress indicators -->
<div class="mt-score-ring-mini" data-score="7.5">
    <svg viewBox="0 0 36 36" class="mt-score-svg">
        <path class="mt-ring-bg"
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
        <path class="mt-ring-progress"
              stroke-dasharray="75, 100"
              d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
    </svg>
</div>
```

### Animation System
- **Page Load**: Staggered slide-in animations (0.1s delay between cards)
- **Score Rings**: Progressive reveal with cubic-bezier easing
- **Hover Effects**: Smooth color transitions and elevation changes
- **Click Feedback**: Scale animations for tactile response
- **Loading States**: Spinner animations during AJAX operations
- **Success Feedback**: Green pulse animation for saved evaluations

## JavaScript Functionality

### Inline Evaluation System
```javascript
// Initialize inline evaluation controls
function initializeInlineEvaluations() {
    // Score adjustment buttons
    $(document).on('click', '.mt-score-adjust', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $input = $button.siblings('.mt-score-input');
        const action = $button.data('action');
        const currentValue = parseFloat($input.val()) || 0;
        let newValue = currentValue;
        
        if (action === 'increase' && currentValue < 10) {
            newValue = Math.min(10, currentValue + 0.5);
        } else if (action === 'decrease' && currentValue > 0) {
            newValue = Math.max(0, currentValue - 0.5);
        }
        
        $input.val(newValue).trigger('change');
    });
    
    // Save inline evaluation
    $(document).on('click', '.mt-btn-save-inline', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const $form = $button.closest('.mt-inline-evaluation-form');
        const candidateId = $form.data('candidate-id');
        
        // Collect scores and save via AJAX
        const scores = {};
        $form.find('.mt-score-input').each(function() {
            const criterion = $(this).data('criterion');
            scores[criterion] = $(this).val();
        });
        
        // AJAX save with loading states and success feedback
        $.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mt_save_inline_evaluation',
                nonce: $form.find('input[name*="mt_inline_nonce"]').val(),
                candidate_id: candidateId,
                scores: scores,
                status: 'completed'
            },
            success: function(response) {
                if (response.success) {
                    // Update UI and trigger rankings refresh
                    updateTotalScoreDisplay(response.data.total_score);
                    refreshRankings();
                }
            }
        });
    });
}
```

### Real-time Updates
```javascript
// Enhanced AJAX-based ranking updates with inline support
function refreshRankings() {
    const $container = $('#mt-rankings-container');
    
    $.ajax({
        url: mt_ajax.ajax_url,
        type: 'POST',
        data: {
            action: 'mt_get_jury_rankings',
            nonce: mt_ajax.nonce,
            limit: 10
        },
        success: function(response) {
            if (response.success && response.data.html) {
                $container.fadeOut(300, function() {
                    $(this).html(response.data.html).fadeIn(300);
                    
                    // Reinitialize score rings and inline controls
                    $('.mt-score-ring-mini').each(function() {
                        const score = $(this).data('score');
                        updateMiniScoreRing($(this).find('.mt-score-input'));
                    });
                });
            }
        }
    });
}

// Auto-refresh every 30 seconds
if ($('.mt-rankings-section').length > 0) {
    setInterval(refreshRankings, 30000);
}
```

### Interactive Features
- **Score Adjustment**: +/- buttons with 0.5 step increments
- **Real-time Validation**: Score constraints (0-10 range) with visual feedback
- **Mini Ring Updates**: Dynamic color coding based on score values
- **Total Score Preview**: Live calculation and display of average scores
- **Loading States**: Visual feedback during AJAX operations
- **Success Animations**: Green pulse effect for saved evaluations

## Security Implementation

### Enhanced AJAX Security
```php
/**
 * Save inline evaluation from rankings grid
 */
public function save_inline_evaluation() {
    // Verify nonce with candidate-specific nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'mt_inline_evaluation_' . $_POST['candidate_id'])) {
        wp_send_json_error(__('Security check failed', 'mobility-trailblazers'));
    }
    
    // Check permissions
    if (!current_user_can('mt_submit_evaluations')) {
        wp_send_json_error(__('Permission denied', 'mobility-trailblazers'));
    }
    
    // Verify assignment exists
    $assignment_repo = new \MobilityTrailblazers\Repositories\MT_Assignment_Repository();
    if (!$assignment_repo->exists($jury_member->ID, $candidate_id)) {
        wp_send_json_error(__('You are not assigned to evaluate this candidate', 'mobility-trailblazers'));
    }
    
    // Process and save evaluation data
    $evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
    $result = $evaluation_service->save_evaluation($evaluation_data);
    
    wp_send_json_success([
        'message' => __('Evaluation saved successfully', 'mobility-trailblazers'),
        'total_score' => $evaluation->total_score ?? 0,
        'refresh_rankings' => true
    ]);
}
```

### Data Sanitization
- Input validation for all parameters with type checking
- SQL preparation with proper escaping
- Output sanitization for display
- Score validation (0-10 range with 0.5 step increments)

## Performance Optimization

### Database Optimization
- **Indexed Queries**: Proper indexing on frequently queried columns
- **Efficient JOINs**: Optimized table relationships
- **Fixed Result Sets**: 10-candidate limit for 5x2 grid optimization
- **Caching**: Optional result caching for frequently accessed rankings

### Frontend Performance
- **CSS Grid**: Hardware-accelerated layout system
- **Efficient Animations**: GPU-accelerated transforms
- **Targeted DOM Updates**: Minimal element modifications
- **Debounced AJAX**: Prevents excessive server requests
- **Lazy Loading**: Progressive enhancement for better perceived performance

## Configuration Options

### Admin Settings
```php
// Enhanced settings registration
register_setting('mt_options', 'mt_rankings_layout', array(
    'type' => 'string',
    'default' => '5x2',
    'sanitize_callback' => function($value) {
        return in_array($value, ['5x2', '4x3', '3x4']) ? $value : '5x2';
    }
));

register_setting('mt_options', 'mt_inline_evaluation_enabled', array(
    'type' => 'boolean',
    'default' => true
));

register_setting('mt_options', 'mt_auto_refresh_interval', array(
    'type' => 'integer',
    'default' => 30,
    'sanitize_callback' => function($value) {
        return max(10, min(300, intval($value))); // 10 seconds to 5 minutes
    }
));
```

### Template Integration
```php
// Enhanced conditional display with inline evaluation support
if (get_option('mt_rankings_layout', '5x2') === '5x2') {
    $rankings_limit = 10; // Fixed for 5x2 grid
    $show_inline_evaluation = get_option('mt_inline_evaluation_enabled', true);
    include MT_PLUGIN_DIR . 'templates/frontend/partials/jury-rankings.php';
}
```

## Responsive Design

### Enhanced Breakpoint System
- **Large Desktop**: 5x2 grid (1400px+)
- **Desktop**: 4x3 grid (1024px - 1400px)
- **Tablet**: 3x4 grid (768px - 1024px)
- **Mobile Landscape**: 2x5 grid (480px - 768px)
- **Mobile Portrait**: 1x10 grid (< 480px)

### Touch Optimization
- **Touch Targets**: Minimum 44px touch areas for all interactive elements
- **Gesture Support**: Optimized for touch interactions and swipes
- **Mobile Performance**: Reduced animations and simplified interactions on mobile devices
- **Accessibility**: Proper focus management and keyboard navigation

## Accessibility Features

### Enhanced ARIA Support
- **Semantic HTML**: Proper heading hierarchy and landmarks
- **Screen Reader Support**: Descriptive text and labels for all interactive elements
- **Keyboard Navigation**: Full keyboard accessibility for inline controls
- **Color Contrast**: WCAG AA compliant color ratios for all states

### Focus Management
- **Visible Focus Indicators**: Clear focus states for all interactive elements
- **Logical Tab Order**: Intuitive keyboard navigation flow through inline controls
- **Skip Links**: Quick navigation to main content areas
- **Error Announcements**: Screen reader announcements for validation errors

## Browser Compatibility

### Supported Browsers
- **Chrome**: 90+ (Full support for CSS Grid and modern JavaScript)
- **Firefox**: 88+ (Full support for CSS Grid and modern JavaScript)
- **Safari**: 14+ (Full support for CSS Grid and modern JavaScript)
- **Edge**: 90+ (Full support for CSS Grid and modern JavaScript)

### Fallback Support
- **IE11**: Graceful degradation with flexbox fallback
- **Older Mobile**: Simplified layout without advanced animations
- **JavaScript Disabled**: Basic functionality with server-side rendering

## Testing and Quality Assurance

### Automated Testing
- **Unit Tests**: PHPUnit tests for AJAX handlers and data processing
- **Integration Tests**: End-to-end testing of inline evaluation workflow
- **Frontend Tests**: JavaScript testing with Jest for interactive features
- **Accessibility Tests**: Automated accessibility testing with axe-core

### Manual Testing Checklist
- [ ] 5x2 grid displays correctly on all screen sizes
- [ ] Inline evaluation controls work for all criteria
- [ ] Score validation prevents invalid values
- [ ] AJAX saves work without page refresh
- [ ] Success animations provide clear feedback
- [ ] Auto-refresh updates rankings correctly
- [ ] Error handling works gracefully
- [ ] Keyboard navigation is fully functional
- [ ] Screen readers can access all features
- [ ] Touch interactions work on mobile devices

## Future Enhancements

### Planned Features
- **Drag and Drop**: Reorder candidates by dragging
- **Bulk Operations**: Select multiple candidates for batch evaluation
- **Advanced Filtering**: Filter by score ranges, categories, or status
- **Export Functionality**: Export rankings to PDF or Excel
- **Real-time Collaboration**: Live updates when other jury members save evaluations

### Performance Improvements
- **WebSocket Integration**: Real-time updates without polling
- **Service Worker**: Offline support for basic functionality
- **Progressive Web App**: Installable dashboard with offline capabilities
- **Advanced Caching**: Intelligent caching strategies for better performance

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