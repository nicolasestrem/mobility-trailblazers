# Jury Rankings System - Technical Documentation

## Overview

The Jury Rankings System provides a dynamic, real-time display of candidate rankings for jury members in the Mobility Trailblazers WordPress plugin. This system offers personalized rankings based on each jury member's evaluations, with automatic updates and modern visual design. **Version 2.0.12** introduces a table-based layout with inline evaluation controls for enhanced user experience.

## Recent Updates (v2.0.12)

### New Features
- **Table-Based Layout**: Condensed table format for efficient data display
- **Inline Evaluation Controls**: Direct score editing in table cells
- **Real-time Score Updates**: Instant calculation and color coding
- **Individual Row Saving**: Save each candidate's scores independently
- **Enhanced Visual Feedback**: Color-coded scores and save states
- **Auto-refresh Rankings**: Automatic updates after successful saves

### Technical Improvements
- **AJAX-powered Row Saves**: Efficient single-row save operations
- **Enhanced Security**: Row-specific operations with proper validation
- **Performance Optimization**: Minimal DOM updates and targeted refreshes
- **Better Error Handling**: User-friendly error messages and recovery

## Architecture

### Core Components

1. **Repository Layer** (`MT_Evaluation_Repository`)
   - `get_ranked_candidates_for_jury()` - Fetches personalized rankings for a specific jury member
   - `get_overall_rankings()` - Fetches overall rankings across all jury members

2. **AJAX Handler** (`MT_Evaluation_Ajax`)
   - `mt_get_jury_rankings` - Handles dynamic ranking requests with security validation
   - `mt_save_inline_evaluation` - Handles inline evaluation saves

3. **Template System**
   - `jury-dashboard.php` - Main dashboard with conditional rankings section
   - `jury-rankings.php` - **UPDATED**: Partial template with table layout

4. **Frontend Assets**
   - **UPDATED**: CSS table styling with modern design
   - **UPDATED**: JavaScript for inline evaluation interactions and real-time updates

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
LIMIT 10
```

## Visual Design System

### Table Layout Architecture
```css
/* Modern table design */
.mt-evaluation-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    min-width: 900px;
    font-size: 15px;
    background: var(--mt-bg-base);
    border-radius: 14px;
    overflow: hidden;
}

/* Table header with gradient */
.mt-evaluation-table thead th {
    background: linear-gradient(135deg, var(--mt-primary), var(--mt-secondary));
    color: var(--mt-bg-base);
    font-weight: 700;
    padding: 14px 10px;
    text-align: center;
    position: sticky;
    top: 0;
    z-index: 2;
}

/* Row styling with hover effects */
.mt-evaluation-table tbody tr {
    transition: background 0.2s;
}

.mt-evaluation-table tbody tr:nth-child(even) {
    background: var(--mt-bg-beige);
}

.mt-evaluation-table tbody tr:hover {
    background: rgba(164, 220, 213, 0.2);
}
```

### Enhanced Position Badge System
- **Medal Icons**: SVG-based medal icons for top 3 positions
- **Color Scheme**: Using brand colors instead of traditional gold/silver/bronze
- **Position Numbers**: Clear ranking numbers with gradient backgrounds
- **Responsive Sizing**: Adaptive badge sizes for different screen sizes

### Inline Score Inputs
```html
<!-- Score input structure -->
<input type="number" min="0" max="10" step="0.5" 
    class="mt-eval-score-input" 
    name="courage_score"
    value="7.5"
    data-criterion="courage_score"
    data-candidate-id="123">
```

### Color Coding System
```css
/* Score color coding */
.mt-eval-score-input.score-high {
    background-color: rgba(164, 220, 213, 0.2);
    border-color: var(--mt-blue-accent);
    color: var(--mt-primary);
}

.mt-eval-score-input.score-low {
    background-color: rgba(184, 111, 82, 0.1);
    border-color: var(--mt-kupfer-soft);
    color: var(--mt-kupfer-bold);
}
```

### Row State Indicators
- **Unsaved**: Light orange background indicates changes pending
- **Saving**: Opacity reduction and spinner in save button
- **Saved**: Green flash animation confirms successful save

## JavaScript Functionality

### Table Interactivity System
```javascript
// Live total score calculation
function updateRowTotal($row) {
    var total = 0;
    var count = 0;
    $row.find('.mt-eval-score-input').each(function() {
        var val = parseFloat($(this).val());
        if (!isNaN(val)) {
            total += val;
            count++;
        }
    });
    var avg = count > 0 ? (total / count) : 0;
    $row.find('.mt-eval-total-value').text(avg.toFixed(1));
}

// Color coding for scores
function updateScoreColor($input) {
    var val = parseFloat($input.val());
    $input.removeClass('score-high score-low');
    if (val >= 8) $input.addClass('score-high');
    else if (val <= 3) $input.addClass('score-low');
}

// Save button AJAX handler
$table.on('click', '.mt-btn-save-eval', function(e) {
    e.preventDefault();
    var $btn = $(this);
    var $row = $btn.closest('tr');
    
    // Collect scores
    var candidateId = $btn.data('candidate-id');
    var scores = {};
    $row.find('.mt-eval-score-input').each(function() {
        var name = $(this).attr('name');
        var val = $(this).val();
        scores[name] = val;
    });
    
    // AJAX save with visual feedback
    $.ajax({
        url: mt_ajax.url,
        type: 'POST',
        data: {
            action: 'mt_save_inline_evaluation',
            nonce: mt_ajax.nonce,
            candidate_id: candidateId,
            scores: scores
        },
        success: function(response) {
            if (response.success) {
                $row.removeClass('unsaved saving').addClass('saved');
                // Success animation
            }
        }
    });
});
```

### Real-time Updates
- **Input Change Detection**: Immediate response to score modifications
- **Live Calculations**: Total scores update as you type
- **Visual Feedback**: Color changes based on score values
- **Save State Management**: Clear indication of save status

### Interactive Features
- **Direct Editing**: Click on any score to edit
- **Validation**: Enforces 0-10 range with 0.5 increments
- **Tab Navigation**: Efficient keyboard navigation through scores
- **Tooltips**: Hover over headers for criterion descriptions
- **Responsive Actions**: Save and Full View buttons for each row

## Security Implementation

### Enhanced AJAX Security
```php
/**
 * Save inline evaluation from rankings table
 */
public function save_inline_evaluation() {
    // Verify nonce
    if (!$this->verify_nonce()) {
        $this->error(__('Security check failed', 'mobility-trailblazers'));
        return;
    }
    
    // Check permissions
    if (!current_user_can('mt_submit_evaluations')) {
        $this->error(__('Permission denied', 'mobility-trailblazers'));
        return;
    }
    
    // Get and validate data
    $candidate_id = $this->get_int_param('candidate_id');
    $scores = $this->get_array_param('scores', []);
    
    // Process and save evaluation data
    $evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
    $result = $evaluation_service->save_evaluation($evaluation_data);
    
    $this->success([
        'message' => __('Evaluation saved successfully', 'mobility-trailblazers'),
        'total_score' => $result['total_score'],
        'evaluation_id' => $result['evaluation_id']
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
- **Limited Result Sets**: Top 10 candidates for optimal performance
- **Caching**: Optional result caching for frequently accessed rankings

### Frontend Performance
- **Minimal DOM Updates**: Only update changed elements
- **Efficient Event Delegation**: Single event listener for entire table
- **Debounced Saves**: Prevents excessive server requests
- **Optimized Animations**: CSS-only animations for better performance

## Configuration Options

### Admin Settings
```php
// Settings registration
register_setting('mt_options', 'mt_rankings_display_limit', array(
    'type' => 'integer',
    'default' => 10,
    'sanitize_callback' => 'absint'
));

register_setting('mt_options', 'mt_inline_evaluation_enabled', array(
    'type' => 'boolean',
    'default' => true
));

register_setting('mt_options', 'mt_score_color_coding_enabled', array(
    'type' => 'boolean',
    'default' => true
));
```

## Responsive Design

### Table Responsiveness
- **Horizontal Scrolling**: Table scrolls horizontally on small screens
- **Sticky Header**: Column headers remain visible while scrolling
- **Touch Optimization**: Larger touch targets for mobile devices
- **Condensed View**: Reduced padding and font sizes on mobile

### Breakpoints
```css
@media (max-width: 900px) {
    .mt-evaluation-table {
        font-size: 13px;
    }
    
    .mt-eval-score-input {
        width: 40px;
        font-size: 12px;
    }
}
```

## Accessibility Features

### Enhanced ARIA Support
- **Table Semantics**: Proper table structure with headers
- **Input Labels**: Screen reader accessible labels
- **Focus Management**: Clear focus indicators
- **Keyboard Navigation**: Full keyboard support

### Color Contrast
- All text meets WCAG AA standards
- Color coding is supplementary, not sole indicator
- High contrast mode support

## Browser Compatibility

### Supported Browsers
- **Chrome**: 90+ (Full support)
- **Firefox**: 88+ (Full support)
- **Safari**: 14+ (Full support)
- **Edge**: 90+ (Full support)

### Fallback Support
- **Older Browsers**: Basic table functionality maintained
- **JavaScript Disabled**: Read-only view with server-side rendering

## Testing and Quality Assurance

### Manual Testing Checklist
- [ ] Table displays all ranking data correctly
- [ ] Score inputs accept valid values (0-10, 0.5 increments)
- [ ] Total scores calculate correctly in real-time
- [ ] Color coding applies based on score values
- [ ] Save buttons work for individual rows
- [ ] Success/error feedback displays properly
- [ ] Row states (unsaved/saving/saved) work correctly
- [ ] Full View links navigate to evaluation page
- [ ] Table is responsive on mobile devices
- [ ] Keyboard navigation works throughout table

## Version History

### v2.0.12 (Current - Table Implementation)
- Table-based layout for efficient data display
- Inline editable score inputs
- Real-time total calculation
- Individual row saving
- Color-coded score feedback
- Enhanced visual states

### v2.0.11 (Previous)
- 5x2 Grid layout with cards
- Inline evaluation controls
- Mini progress rings
- Bulk save functionality

### v2.0.10 (Legacy)
- Basic ranking display
- Separate evaluation pages
- No inline editing

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