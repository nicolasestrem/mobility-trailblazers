# Dynamic Table Rankings Implementation Guide

## Overview
The Mobility Trailblazers platform now features a fully interactive table-based rankings system for jury members to efficiently evaluate and score candidates.

## Current Implementation Status ✅

### Already Implemented (Core Features)
1. **Table Structure** - Complete HTML table with all necessary columns
2. **Inline Editing** - Direct score input in table cells  
3. **Real-time Calculations** - Automatic total score updates
4. **AJAX Saving** - Individual row saving without page reload
5. **Visual Feedback** - Color coding and state indicators
6. **Backend Support** - Full AJAX handler for inline saves

## New Enhancements 🚀

### Files Created
1. **`assets/js/table-rankings-enhancements.js`** - Advanced JavaScript features
2. **`assets/css/table-rankings-enhanced.css`** - Enhanced styling

### Features Added

#### 1. Keyboard Navigation
- **Arrow Keys**: Navigate between score inputs (↑↓←→)
- **Tab/Shift+Tab**: Move between fields
- **Enter**: Save current row
- **Escape**: Revert changes
- **+/-**: Quick score adjustment by 0.5

#### 2. Auto-Save
- Automatically saves changes after 2 seconds of inactivity
- Visual indicator when auto-save is pending
- Prevents data loss from forgetting to save

#### 3. Batch Operations
- **Save All Changes** button for multiple unsaved rows
- Shows progress indicator (e.g., "Saving... 3/5")
- Efficient bulk saving

#### 4. Export Functionality
- Export rankings to CSV format
- Includes all scores and totals
- Filename includes current date

#### 5. Enhanced Visual Feedback
- Score change animations (+0.5, -1.0 indicators)
- Smooth transitions for total updates
- Row highlighting on save
- Improved tooltips for criteria headers

#### 6. Data Validation
- Enforces min/max score limits (0-10)
- Step validation (0.5 increments)
- Visual indicators for invalid inputs

## Integration Instructions

### Step 1: Enqueue the New Files
Add to `includes/core/class-mt-plugin.php` in the `enqueue_frontend_scripts()` method:

```php
// After existing frontend.js enqueue
wp_enqueue_script(
    'mt-table-enhancements',
    MT_PLUGIN_URL . 'assets/js/table-rankings-enhancements.js',
    ['jquery', 'mt-frontend'],
    MT_VERSION,
    true
);

// After existing frontend.css enqueue  
wp_enqueue_style(
    'mt-table-enhanced',
    MT_PLUGIN_URL . 'assets/css/table-rankings-enhanced.css',
    ['mt-frontend'],
    MT_VERSION
);
```

### Step 2: Update Localization (Optional)
Add these strings to the `wp_localize_script` call in `class-mt-plugin.php`:

```php
'save_all_changes' => __('Save All Changes', 'mobility-trailblazers'),
'export_rankings' => __('Export Rankings', 'mobility-trailblazers'),
'saving_progress' => __('Saving...', 'mobility-trailblazers'),
'revert_changes' => __('Revert Changes', 'mobility-trailblazers'),
```

### Step 3: Test the Features
1. Navigate to the jury dashboard
2. Look for the rankings table
3. Test keyboard navigation with arrow keys
4. Edit multiple scores and watch for auto-save
5. Try the export button
6. Test batch save with multiple changes

## Usage Guide for Jury Members

### Basic Operations
- **Click** any score field to edit
- **Type** a number between 0-10 (0.5 steps allowed)
- **Tab** to move to next field
- **Enter** to save the row

### Keyboard Shortcuts
- **Arrow Keys**: Navigate the table
- **+/- Keys**: Adjust score by 0.5
- **Enter**: Save current row
- **Escape**: Cancel changes
- **Ctrl+S**: Save all changes (when implemented)

### Visual Indicators
- **Green Background**: High scores (8-10)
- **Yellow Background**: Medium scores (4-7)
- **Orange Background**: Low scores (0-3)
- **Blue Highlight**: Unsaved changes
- **Green Flash**: Successfully saved

## Performance Considerations

### Optimizations Implemented
- Debounced auto-save (2-second delay)
- Efficient DOM updates
- Single AJAX request per row
- Minimal re-renders

### Browser Compatibility
- Chrome 90+ ✅
- Firefox 88+ ✅
- Safari 14+ ✅
- Edge 90+ ✅

## Troubleshooting

### Common Issues

1. **Scores not saving**
   - Check browser console for errors
   - Verify nonce is being passed
   - Ensure user has `mt_submit_evaluations` capability

2. **Auto-save not working**
   - Check if JavaScript file is loaded
   - Verify no JavaScript errors in console
   - Test with different browser

3. **Export not downloading**
   - Check browser download settings
   - Try different browser
   - Verify CSV generation in console

### Debug Mode
Enable debug mode by adding to `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## Future Enhancements (Roadmap)

### Phase 1 (Next Sprint)
- [ ] Column sorting by clicking headers
- [ ] Filter by score ranges
- [ ] Bulk score operations (set all to X)
- [ ] Undo/Redo functionality

### Phase 2
- [ ] Real-time collaboration indicators
- [ ] Comments per criterion
- [ ] Score history tracking
- [ ] Advanced analytics dashboard

### Phase 3
- [ ] AI-powered score suggestions
- [ ] Comparative analysis tools
- [ ] Custom evaluation templates
- [ ] Multi-language support

## API Reference

### JavaScript Events
The table fires custom events for integration:

```javascript
// Triggered when a row is saved
$(document).on('mt:row:saved', function(e, data) {
    console.log('Row saved:', data.candidateId, data.totalScore);
});

// Triggered when auto-save activates
$(document).on('mt:autosave:start', function(e, data) {
    console.log('Auto-saving row:', data.candidateId);
});

// Triggered on export
$(document).on('mt:export:complete', function(e, data) {
    console.log('Exported rows:', data.rowCount);
});
```

### AJAX Endpoints

#### Save Inline Evaluation
- **Action**: `mt_save_inline_evaluation`
- **Method**: POST
- **Parameters**:
  - `candidate_id` (int)
  - `scores` (array)
  - `nonce` (string)
- **Response**: JSON with success status and total score

## Support

For issues or questions:
1. Check the browser console for errors
2. Review the debug log at `/wp-content/debug.log`
3. Contact the development team

## Credits

Developed as part of the Mobility Trailblazers 2025 Award Platform
- Version: 2.0.14
- Last Updated: December 2024
- Framework: WordPress 5.8+
- Dependencies: jQuery 3.6+
