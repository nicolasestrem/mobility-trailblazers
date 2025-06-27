# Inline Evaluation System - Technical Documentation

## Overview

The Inline Evaluation System provides a streamlined interface for jury members to evaluate candidates directly from the jury dashboard without navigating to separate evaluation forms. This system implements a 2x5 grid layout with real-time score updates and AJAX-powered saving functionality.

## Features

### Core Functionality
- **2x5 Grid Layout**: Displays 10 candidates in a responsive grid format
- **Inline Score Adjustment**: Real-time score updates with +/- buttons (0.5 increments)
- **Mini Progress Rings**: Visual score indicators with color-coded feedback
- **AJAX Save**: Secure saving without page refresh
- **Success Animations**: Visual feedback for successful operations
- **Auto-refresh Rankings**: Automatic rankings update after saves
- **Test AJAX Endpoint**: Proper debugging and testing capabilities

### Visual Elements
- **Mini Progress Rings**: Circular progress indicators showing current scores
- **Score Adjustment Buttons**: +/- buttons for precise score control
- **Save/Full View Actions**: Contextual action buttons for each candidate
- **Color-coded Feedback**: Green (8+), Blue (6-7.9), Orange (4-5.9), Red (0-3.9)

## Technical Implementation

### Frontend Components

#### JavaScript (`assets/js/frontend.js`)
```javascript
// Inline evaluation initialization
function initializeInlineEvaluations() {
    // Score ring initialization
    $('.mt-score-ring-mini').each(function() {
        const score = $(this).data('score');
        updateMiniScoreRing($(this), score);
    });
    
    // Score adjustment handlers
    $(document).on('click', '.mt-score-adjust', function(e) {
        // Handle +/- button clicks
    });
    
    // AJAX save functionality
    $(document).on('click', '.mt-btn-save-inline', function(e) {
        // Handle inline evaluation saves
    });
}
```

#### CSS (`assets/css/frontend.css`)
```css
/* Grid layout */
.mt-rankings-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin: 2rem 0;
}

/* Mini progress rings */
.mt-score-ring-mini {
    width: 40px;
    height: 40px;
    position: relative;
}

/* Score adjustment buttons */
.mt-score-adjust {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
}

/* Cross-browser compatibility */
.mt-score-input {
    -webkit-appearance: none;
    appearance: none;
    border-radius: 4px;
    border: 1px solid #d1d5db;
}
```

### Backend Components

#### AJAX Handler (`includes/ajax/class-mt-evaluation-ajax.php`)
```php
public function save_inline_evaluation() {
    // Verify nonce and permissions
    $this->verify_nonce();
    $this->check_permission('mt_submit_evaluations');
    
    // Load existing evaluation
    $existing_evaluation = $evaluation_repo->get_by_jury_and_candidate(
        $jury_member->ID, 
        $candidate_id
    );
    
    // Update scores while preserving existing data
    $evaluation_data = $existing_evaluation ? $existing_evaluation->to_array() : [];
    $evaluation_data['scores'] = $scores;
    
    // Save evaluation
    $result = $service->save($evaluation_data);
    
    // Return updated data
    $this->success([
        'total_score' => $total_score,
        'evaluation_id' => $result
    ]);
}

// Test AJAX endpoint for debugging
public function test_ajax() {
    $this->success([
        'message' => 'AJAX is working correctly',
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id()
    ], 'AJAX test successful');
}
```

#### Repository (`includes/repositories/class-mt-evaluation-repository.php`)
```php
public function save($data) {
    // Use correct column names
    $update_data = [
        'jury_member_id' => $data['jury_member_id'],
        'candidate_id' => $data['candidate_id'],
        'courage_score' => $data['courage_score'],
        'innovation_score' => $data['innovation_score'],
        'implementation_score' => $data['implementation_score'],
        'relevance_score' => $data['relevance_score'],
        'visibility_score' => $data['visibility_score'],
        'comments' => $data['comments'], // Correct field name
        'updated_at' => current_time('mysql') // Correct column name
    ];
    
    return $this->wpdb->replace($this->table_name, $update_data);
}
```

## Database Schema

### Evaluation Table Structure
```sql
CREATE TABLE wp_mt_evaluations (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    jury_member_id bigint(20) unsigned NOT NULL,
    candidate_id bigint(20) unsigned NOT NULL,
    courage_score decimal(3,1) DEFAULT NULL,
    innovation_score decimal(3,1) DEFAULT NULL,
    implementation_score decimal(3,1) DEFAULT NULL,
    relevance_score decimal(3,1) DEFAULT NULL,
    visibility_score decimal(3,1) DEFAULT NULL,
    comments text DEFAULT NULL,
    status varchar(20) DEFAULT 'draft',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY jury_candidate (jury_member_id, candidate_id)
);
```

### Key Schema Corrections
- **Column Names**: `created_at` and `updated_at` (not `evaluation_date` and `last_modified`)
- **Field Names**: `comments` (not `notes`)
- **Data Types**: Proper decimal precision for scores
- **Indexes**: Unique constraint on jury-candidate combination

## Security Implementation

### Nonce Verification
```php
// Frontend nonce generation
wp_nonce_field('mt_inline_evaluation', 'mt_inline_nonce');

// Backend nonce verification
$this->verify_nonce('mt_inline_evaluation');
```

### Permission Checks
```php
// Check jury member permissions
$this->check_permission('mt_submit_evaluations');

// Verify jury-candidate assignment
$has_assignment = $assignment_repo->exists($jury_member->ID, $candidate_id);
if (!$has_assignment) {
    $this->error('Permission denied');
}
```

### Data Validation
```php
// Score validation
$score = floatval($score);
if ($score < 0 || $score > 10) {
    $this->error('Invalid score value');
}

// Input sanitization
$comments = sanitize_textarea_field($comments);
```

## Error Handling

### AJAX Error Responses
```javascript
error: function(xhr, status, error) {
    console.error('AJAX Error:', {
        status: status,
        error: error,
        responseText: xhr.responseText
    });
    
    // User-friendly error messages
    let errorMessage = 'Network error. Please try again.';
    try {
        if (xhr.responseJSON && xhr.responseJSON.data) {
            errorMessage = xhr.responseJSON.data;
        }
    } catch (e) {
        // Use default message
    }
    
    alert(errorMessage);
}
```

### Backend Error Handling
```php
try {
    $result = $service->save($evaluation_data);
    if ($result) {
        $this->success($response_data);
    } else {
        $this->error('Failed to save evaluation');
    }
} catch (Exception $e) {
    error_log('Evaluation save error: ' . $e->getMessage());
    $this->error('Database error occurred');
}
```

## Performance Optimizations

### Database Queries
- **Efficient Loading**: Single query to load existing evaluations
- **Batch Operations**: Bulk loading of candidate data
- **Indexed Queries**: Proper indexing on frequently queried columns

### Frontend Performance
- **Debounced Updates**: Prevent excessive AJAX calls
- **Cached Data**: Store evaluation data locally
- **Lazy Loading**: Load candidate details on demand

### Cache Management
```php
// Version-based cache busting
define('MT_VERSION', '2.0.10');

// Asset enqueuing with version
wp_enqueue_script(
    'mt-frontend',
    MT_PLUGIN_URL . 'assets/js/frontend.js',
    ['jquery'],
    MT_VERSION,
    true
);
```

## Browser Compatibility

### CSS Compatibility
```css
/* Cross-browser appearance reset */
.mt-score-input {
    -webkit-appearance: none;
    appearance: none;
    border-radius: 4px;
    border: 1px solid #d1d5db;
}

/* Vendor prefix support */
.mt-score-ring-progress {
    stroke-dasharray: 0, 100;
    transition: stroke-dasharray 0.3s ease;
}
```

### JavaScript Compatibility
- **ES6+ Features**: Used with proper polyfills
- **AJAX Support**: jQuery-based for broad compatibility
- **Event Handling**: Cross-browser event delegation

## Testing and Debugging

### AJAX Testing
```php
// Test AJAX endpoint
public function test_ajax() {
    $this->success([
        'message' => 'AJAX is working correctly',
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id()
    ], 'AJAX test successful');
}
```

### Console Debugging
```javascript
// Debug logging
console.log('Sending AJAX request with data:', formData);
console.log('AJAX URL:', mt_ajax.url);
console.log('Save response:', response);
```

### Error Monitoring
- **PHP Error Logging**: Comprehensive error logging
- **JavaScript Console**: Detailed AJAX error reporting
- **User Feedback**: Clear error messages for users

## Troubleshooting

### Common Issues

#### AJAX Test Errors
**Problem**: Console shows "Testing AJAX functionality" errors
**Solution**: 
- Clear browser cache (Ctrl+F5)
- Check plugin version is 2.0.10+
- Verify AJAX endpoints are registered

#### Database Errors
**Problem**: "Unknown column" errors
**Solution**:
- Run database upgrade: `MT_Database_Upgrade::run()`
- Check column names match schema
- Verify field names are consistent

#### Permission Errors
**Problem**: "Permission denied" messages
**Solution**:
- Verify user has jury member role
- Check jury-candidate assignment exists
- Confirm user is logged in

### Debug Steps
1. **Check Console**: Look for JavaScript errors
2. **Verify AJAX**: Test AJAX endpoints manually
3. **Check Database**: Verify table structure
4. **Review Logs**: Check PHP error logs
5. **Test Permissions**: Verify user capabilities

## Usage Instructions

### For Jury Members
1. **Access Dashboard**: Navigate to jury dashboard
2. **View Candidates**: See assigned candidates in 2x5 grid
3. **Adjust Scores**: Use +/- buttons to modify scores
4. **Save Changes**: Click save button for each candidate
5. **Monitor Progress**: Watch mini rings update in real-time

### For Administrators
1. **Monitor Usage**: Check evaluation progress in admin
2. **Troubleshoot Issues**: Use diagnostics page for debugging
3. **Manage Assignments**: Control jury-candidate assignments
4. **Review Data**: Access evaluation data and reports

## Future Enhancements

### Planned Features
- **Bulk Operations**: Save multiple evaluations at once
- **Auto-save**: Automatic saving of changes
- **Offline Support**: Work offline with sync on reconnect
- **Advanced Analytics**: Detailed evaluation insights

### Technical Improvements
- **Real-time Collaboration**: Multiple jury members working simultaneously
- **Advanced Validation**: More sophisticated score validation
- **Performance Monitoring**: Detailed performance metrics
- **Mobile Optimization**: Enhanced mobile experience

---

*Last updated: December 2024* 