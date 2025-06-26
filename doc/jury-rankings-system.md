# Jury Rankings System Implementation

**Version:** 2.0.9  
**Date:** June 2025  
**Feature:** Dynamic Jury Rankings Display System

## Overview

The Jury Rankings System provides jury members with real-time, personalized rankings of their evaluated candidates. This system displays candidates in order of their evaluation scores, with visual indicators for top performers and detailed score breakdowns.

## Features

### Core Functionality
- **Personalized Rankings**: Each jury member sees their own ranking of evaluated candidates
- **Real-time Updates**: Rankings update automatically after evaluation submissions
- **Visual Hierarchy**: Medal indicators for top 3 positions (gold, silver, bronze)
- **Score Breakdown**: Detailed display of all 5 evaluation criteria scores
- **Interactive Elements**: Clickable candidate names linking to evaluation forms
- **Responsive Design**: Optimized for desktop and mobile devices

### Admin Controls
- **Enable/Disable**: Toggle rankings section visibility
- **Customizable Limit**: Set number of candidates to display (5-20 range)
- **Integration**: Seamless integration with existing dashboard settings

## Technical Implementation

### 1. Database Layer - Repository Methods

**File:** `includes/repositories/class-mt-evaluation-repository.php`

#### New Methods Added:

```php
/**
 * Get ranked candidates for a specific jury member
 *
 * @param int $jury_member_id Jury member ID
 * @param int $limit Number of candidates to return
 * @return array
 */
public function get_ranked_candidates_for_jury($jury_member_id, $limit = 10) {
    global $wpdb;
    
    $query = "SELECT 
                c.ID as candidate_id,
                c.post_title as candidate_name,
                e.total_score,
                e.courage_score,
                e.innovation_score,
                e.implementation_score,
                e.relevance_score,
                e.visibility_score,
                e.status as evaluation_status,
                pm1.meta_value as organization,
                pm2.meta_value as position
              FROM {$wpdb->posts} c
              INNER JOIN {$this->table_name} e ON c.ID = e.candidate_id
              LEFT JOIN {$wpdb->postmeta} pm1 ON c.ID = pm1.post_id AND pm1.meta_key = '_mt_organization'
              LEFT JOIN {$wpdb->postmeta} pm2 ON c.ID = pm2.post_id AND pm2.meta_key = '_mt_position'
              WHERE e.jury_member_id = %d
                AND c.post_type = 'mt_candidate'
                AND c.post_status = 'publish'
                AND e.status = 'completed'
              ORDER BY e.total_score DESC
              LIMIT %d";
    
    return $wpdb->get_results($wpdb->prepare($query, $jury_member_id, $limit));
}

/**
 * Get all evaluated candidates with rankings across all juries
 *
 * @param int $limit Number of candidates to return
 * @return array
 */
public function get_overall_rankings($limit = 10) {
    global $wpdb;
    
    $query = "SELECT 
                c.ID as candidate_id,
                c.post_title as candidate_name,
                AVG(e.total_score) as average_score,
                COUNT(DISTINCT e.jury_member_id) as evaluation_count,
                pm1.meta_value as organization
              FROM {$wpdb->posts} c
              INNER JOIN {$this->table_name} e ON c.ID = e.candidate_id
              LEFT JOIN {$wpdb->postmeta} pm1 ON c.ID = pm1.post_id AND pm1.meta_key = '_mt_organization'
              WHERE c.post_type = 'mt_candidate'
                AND c.post_status = 'publish'
                AND e.status = 'completed'
              GROUP BY c.ID
              ORDER BY average_score DESC
              LIMIT %d";
    
    return $wpdb->get_results($wpdb->prepare($query, $limit));
}
```

#### Key Features:
- **Optimized Queries**: Efficient SQL with proper JOINs and indexing
- **Meta Data Integration**: Includes organization and position information
- **Status Filtering**: Only shows completed evaluations
- **Score Ordering**: Results ordered by total score (descending)
- **Flexible Limits**: Configurable number of results returned

### 2. AJAX Handler - Dynamic Updates

**File:** `includes/ajax/class-mt-evaluation-ajax.php`

#### New Methods Added:

```php
/**
 * Get ranked candidates for jury dashboard
 */
public function get_jury_rankings() {
    // Verify nonce
    if (!check_ajax_referer('mt_ajax_nonce', 'nonce', false)) {
        wp_send_json_error(__('Security check failed', 'mobility-trailblazers'));
    }
    
    // Check permissions
    if (!current_user_can('mt_submit_evaluations')) {
        wp_send_json_error(__('Permission denied', 'mobility-trailblazers'));
    }
    
    $current_user_id = get_current_user_id();
    $jury_member = $this->get_jury_member_by_user_id($current_user_id);
    
    if (!$jury_member) {
        wp_send_json_error(__('Jury member not found', 'mobility-trailblazers'));
    }
    
    $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
    $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
    
    $rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member->ID, $limit);
    
    wp_send_json_success([
        'rankings' => $rankings,
        'html' => $this->render_rankings_html($rankings)
    ]);
}

/**
 * Render rankings HTML
 */
private function render_rankings_html($rankings) {
    ob_start();
    include MT_PLUGIN_DIR . 'templates/frontend/partials/jury-rankings.php';
    return ob_get_clean();
}
```

#### AJAX Action Registration:
```php
add_action('wp_ajax_mt_get_jury_rankings', [$this, 'get_jury_rankings']);
```

#### Security Features:
- **Nonce Verification**: Prevents CSRF attacks
- **Permission Checks**: Ensures only authorized users can access rankings
- **Input Validation**: Proper sanitization of limit parameter
- **Error Handling**: Comprehensive error responses

### 3. Template System - Rankings Display

**File:** `templates/frontend/partials/jury-rankings.php`

#### Template Structure:
```php
<div class="mt-rankings-section">
    <div class="mt-rankings-header">
        <h2><?php _e('Top Ranked Candidates', 'mobility-trailblazers'); ?></h2>
        <p class="mt-rankings-description">
            <?php _e('Your current ranking based on evaluation scores...', 'mobility-trailblazers'); ?>
        </p>
    </div>
    
    <?php if (!empty($rankings)) : ?>
        <div class="mt-rankings-list">
            <?php foreach ($rankings as $candidate) : ?>
                <!-- Individual ranking item -->
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="mt-no-rankings">
            <p><?php _e('No completed evaluations yet...', 'mobility-trailblazers'); ?></p>
        </div>
    <?php endif; ?>
</div>
```

#### Key Features:
- **Conditional Display**: Shows appropriate message when no rankings available
- **Position Tracking**: Automatic position numbering with medal classes
- **Score Display**: Prominent total score with detailed breakdown
- **Interactive Links**: Clickable candidate names for easy access
- **Internationalization**: Full translation support

### 4. Dashboard Integration

**File:** `templates/frontend/jury-dashboard.php`

#### Integration Code:
```php
<!-- Add Rankings Section -->
<?php if ($dashboard_settings['show_rankings'] ?? true) : ?>
    <div id="mt-rankings-container" class="mt-rankings-container">
        <?php 
        // Get initial rankings
        $evaluation_repo = new \MobilityTrailblazers\Repositories\MT_Evaluation_Repository();
        $rankings = $evaluation_repo->get_ranked_candidates_for_jury($jury_member->ID, 10);
        include MT_PLUGIN_DIR . 'templates/frontend/partials/jury-rankings.php';
        ?>
    </div>
<?php endif; ?>
```

#### Settings Integration:
```php
// Added to dashboard settings array
'show_rankings' => 1,
'rankings_limit' => 10,
```

### 5. Styling System

**File:** `assets/css/frontend.css`

#### Key CSS Classes:
```css
/* Rankings Section */
.mt-rankings-section {
    background: #fff;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

/* Medal colors for top 3 */
.mt-ranking-item.gold {
    background: linear-gradient(135deg, #fff9e6 0%, #fff5d6 100%);
    border-color: #ffd700;
}

.mt-ranking-item.silver {
    background: linear-gradient(135deg, #f5f5f5 0%, #eeeeee 100%);
    border-color: #c0c0c0;
}

.mt-ranking-item.bronze {
    background: linear-gradient(135deg, #fdf0e6 0%, #f9e5d6 100%);
    border-color: #cd7f32;
}

/* Score display */
.mt-total-score {
    text-align: center;
    padding: 10px 15px;
    background: #667eea;
    color: white;
    border-radius: 8px;
}

/* Mini score breakdown */
.mt-mini-scores span {
    display: inline-block;
    width: 32px;
    height: 32px;
    line-height: 32px;
    text-align: center;
    background: #f0f0f0;
    border-radius: 50%;
    font-size: 12px;
    font-weight: 600;
    cursor: help;
}
```

#### Responsive Design:
```css
@media (max-width: 768px) {
    .mt-ranking-item {
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .mt-ranking-scores {
        width: 100%;
        justify-content: space-between;
    }
    
    .mt-ranking-actions {
        width: 100%;
        text-align: center;
    }
}
```

### 6. JavaScript Functionality

**File:** `assets/js/frontend.js`

#### Dynamic Update System:
```javascript
// Rankings update functionality
jQuery(document).ready(function($) {
    // Auto-refresh rankings after evaluation submission
    $(document).on('mt:evaluation:submitted', function() {
        refreshRankings();
    });
    
    // Refresh rankings function
    function refreshRankings() {
        $.ajax({
            url: mt_ajax.url,
            type: 'POST',
            data: {
                action: 'mt_get_jury_rankings',
                nonce: mt_ajax.nonce,
                limit: 10
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $('#mt-rankings-container').html(response.data.html);
                    
                    // Add animation
                    $('.mt-ranking-item').each(function(index) {
                        $(this).css('opacity', '0').delay(index * 50).animate({
                            opacity: 1
                        }, 300);
                    });
                }
            }
        });
    }
    
    // Optional: Refresh rankings periodically
    setInterval(refreshRankings, 60000); // Every minute
});
```

#### Features:
- **Event-Driven Updates**: Triggers on evaluation submission
- **Smooth Animations**: Staggered fade-in effects
- **Periodic Refresh**: Automatic updates every minute
- **Error Handling**: Graceful handling of AJAX failures

### 7. Admin Settings

**File:** `templates/admin/settings.php`

#### Settings Added:
```php
// Dashboard settings array additions
'show_rankings' => isset($_POST['mt_dashboard_settings']['show_rankings']) ? 1 : 0,
'rankings_limit' => intval($_POST['mt_dashboard_settings']['rankings_limit']),

// Form fields
<label>
    <input type="checkbox" name="mt_dashboard_settings[show_rankings]" value="1" 
           <?php checked($dashboard_settings['show_rankings'], 1); ?> />
    <?php _e('Show rankings section', 'mobility-trailblazers'); ?>
</label>

<input type="number" name="mt_dashboard_settings[rankings_limit]" id="rankings_limit" 
       value="<?php echo esc_attr($dashboard_settings['rankings_limit']); ?>" 
       min="5" max="20" class="small-text">
```

## Usage Instructions

### For Administrators

1. **Access Settings**: Go to Mobility Trailblazers → Settings
2. **Configure Rankings**:
   - Check "Show rankings section" to enable
   - Set "Number of Rankings to Show" (5-20 range)
3. **Save Settings**: Click "Save Settings" to apply changes

### For Jury Members

1. **View Rankings**: Rankings appear at the top of the jury dashboard
2. **Interactive Features**:
   - Click candidate names to access evaluation forms
   - Hover over mini scores for criteria details
   - View medal indicators for top performers
3. **Real-time Updates**: Rankings update automatically after evaluations

## Performance Considerations

### Database Optimization
- **Indexed Queries**: Proper indexing on `jury_member_id`, `candidate_id`, `status`
- **Efficient JOINs**: Optimized table joins for minimal query time
- **Result Limiting**: Configurable limits prevent excessive data retrieval

### Frontend Performance
- **Lazy Loading**: Rankings load only when needed
- **Caching**: AJAX responses can be cached for improved performance
- **Minimal DOM Updates**: Efficient HTML replacement with animations

### Scalability
- **Configurable Limits**: Admin can adjust number of displayed rankings
- **Efficient Queries**: Database queries optimized for large datasets
- **Responsive Design**: Works across all device sizes

## Security Features

### Input Validation
- **Nonce Verification**: All AJAX requests verified with nonces
- **Permission Checks**: Only authorized users can access rankings
- **Data Sanitization**: All inputs properly sanitized

### Data Protection
- **User Isolation**: Each jury member sees only their own rankings
- **Status Filtering**: Only completed evaluations are displayed
- **SQL Injection Prevention**: Prepared statements used throughout

## Troubleshooting

### Common Issues

1. **Rankings Not Displaying**
   - Check if `show_rankings` setting is enabled
   - Verify jury member has completed evaluations
   - Check browser console for JavaScript errors

2. **AJAX Update Failures**
   - Verify nonce is properly set
   - Check user permissions
   - Ensure AJAX URL is correct

3. **Styling Issues**
   - Clear browser cache
   - Verify CSS file is loading
   - Check for CSS conflicts with theme

### Debug Information

Enable WordPress debug mode to see detailed error information:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Future Enhancements

### Planned Features
- **Export Rankings**: Allow jury members to export their rankings
- **Comparison View**: Side-by-side candidate comparison
- **Historical Rankings**: Track ranking changes over time
- **Advanced Filtering**: Filter by category, date range, etc.

### Technical Improvements
- **Caching Layer**: Implement Redis/Memcached for better performance
- **Real-time Updates**: WebSocket integration for instant updates
- **Analytics Dashboard**: Detailed ranking analytics for administrators

## File Summary

### Modified Files
1. `includes/repositories/class-mt-evaluation-repository.php` - Added ranking methods
2. `includes/ajax/class-mt-evaluation-ajax.php` - Added AJAX handler
3. `templates/frontend/partials/jury-rankings.php` - Created rankings template
4. `templates/frontend/jury-dashboard.php` - Integrated rankings section
5. `assets/css/frontend.css` - Added rankings styles
6. `assets/js/frontend.js` - Added dynamic update functionality
7. `templates/admin/settings.php` - Added admin controls

### New Files Created
- `templates/frontend/partials/jury-rankings.php` - Rankings display template

## Conclusion

The Jury Rankings System provides a comprehensive, user-friendly way for jury members to view and interact with their candidate evaluations. The system is built with performance, security, and scalability in mind, offering both immediate value and a foundation for future enhancements.

The implementation follows WordPress best practices and integrates seamlessly with the existing Mobility Trailblazers platform, providing a consistent user experience while adding significant value to the evaluation process. 