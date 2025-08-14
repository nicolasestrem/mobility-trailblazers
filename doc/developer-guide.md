# Mobility Trailblazers Developer Guide

## JavaScript Architecture

### Overview
The admin JavaScript is organized into modular components that load conditionally based on the current admin page. The main file `assets/js/admin.js` contains both general utilities and page-specific modules.

### Module Structure

#### General Utilities (Loaded on All Admin Pages)
- `initTooltips()` - Initialize tooltip functionality
- `initTabs()` - Tab navigation system
- `initModals()` - Modal dialog handling
- `initConfirmations()` - Confirmation dialogs for destructive actions
- `initAjaxForms()` - AJAX form submissions
- `initMediaUpload()` - WordPress media library integration
- Global utility functions:
  - `mtShowNotification()` - Display admin notices
  - `mtHandleAjaxError()` - Standardized error handling
  - `mtSerializeForm()` - Form data serialization
  - `mtUpdateUrlParam()` / `mtGetUrlParam()` - URL parameter management
  - `mtFormatNumber()` - Number formatting for DACH region
  - `mtDebounce()` - Function debouncing utility
  - `refreshDashboardWidget(widgetId, callback)` - Refresh individual dashboard widget via AJAX (v2.2.28)
  - `refreshDashboardWidgets(widgetIds)` - Refresh multiple widgets at once (v2.2.28)

#### Assignment Management Module (`MTAssignmentManager`) - COMPLETE
Loaded only on the Assignment Management page. Provides full assignment management functionality:

**Core Features:**
- Auto-assignment modal and processing
- Manual assignment interface  
- Individual assignment removal
- Bulk operations (remove, export, reassign)
- Assignment export to CSV
- Real-time filtering and search
- Progress tracking and statistics updates

**Key Methods:**
- `init()` - Entry point, sets up all event handlers and initializes bulk actions
- `bindEvents()` - Attaches all event listeners for buttons, forms, and filters
- `initBulkActions()` - Sets up bulk action checkboxes and handlers

**Modal Management:**
- `showAutoAssignModal()` - Displays auto-assignment configuration dialog
- `showManualAssignModal()` - Opens manual assignment interface
- `submitAutoAssignment()` - Processes auto-assignment with selected method
- `submitManualAssignment()` - Creates assignments for selected candidates

**Assignment Operations:**
- `removeAssignment($button)` - Removes individual assignment with confirmation
- `clearAllAssignments()` - Bulk removal with double confirmation for safety
- `exportAssignments()` - Exports all assignments to CSV via form submission

**Bulk Operations:**
- `toggleBulkActions()` - Shows/hides bulk action interface with checkboxes
- `applyBulkAction()` - Routes to appropriate bulk handler
- `bulkRemoveAssignments(ids)` - Batch removal of selected assignments
- `bulkExportAssignments(ids)` - Export selected assignments to CSV

**Filtering & Search:**
- `filterAssignments(searchTerm)` - Real-time text search across all table data
- `applyFilters()` - Advanced filtering by jury member and evaluation status

**Implementation Status:** ✅ COMPLETE (v2.2.6)
All methods fully implemented with proper error handling, loading states, and user feedback.

#### Bulk Operations Module (`MTBulkOperations`)
Loaded when assignment tables are present. Provides:
- Checkbox selection system
- Bulk actions (remove, reassign, export)
- Selection count tracking
- Modal-based reassignment interface

### Conditional Loading

The system detects the Assignment Management page using multiple checks:
```javascript
if ($('#mt-auto-assign-btn').length > 0 ||          // Auto-assign button
    $('.mt-assignments-table').length > 0 ||         // Assignment table
    $('.mt-assignment-management').length > 0 ||     // Page wrapper
    $('body').hasClass('mobility-trailblazers_page_mt-assignment-management') ||
    window.location.href.includes('mt-assignment-management')) {
    
    // Initialize assignment-specific modules
    MTAssignmentManager.init();
    
    if ($('.mt-assignments-table').length > 0) {
        MTBulkOperations.init();
    }
}
```

### Global Objects

#### `mt_admin` Object
Provides configuration and localization:
```javascript
mt_admin = {
    ajax_url: '/wp-admin/admin-ajax.php',
    nonce: 'security_nonce',
    admin_url: '/wp-admin/',
    i18n: {
        // Localized strings
        confirm_remove_assignment: 'Are you sure?',
        processing: 'Processing...',
        // ... more translations
    }
}
```

### Event Handling Patterns

#### Delegation for Dynamic Content
```javascript
$(document).on('click', '.mt-remove-assignment', (e) => {
    e.preventDefault();
    this.removeAssignment($(e.currentTarget));
});
```

#### Direct Binding for Static Elements
```javascript
$('#mt-auto-assign-btn').on('click', (e) => {
    e.preventDefault();
    this.showAutoAssignModal();
});
```

### AJAX Pattern

Standardized AJAX calls with proper error handling:
```javascript
$.ajax({
    url: mt_admin.ajax_url,
    type: 'POST',
    data: {
        action: 'mt_action_name',
        nonce: mt_admin.nonce,
        // ... additional data
    },
    beforeSend: () => {
        // Disable UI, show loading state
    },
    success: (response) => {
        if (response.success) {
            // Handle success
        } else {
            // Handle application error
        }
    },
    error: (xhr, status, error) => {
        // Handle network/server error
    },
    complete: () => {
        // Re-enable UI
    }
});
```

### Debugging

The code includes extensive console logging for debugging:
- Module initialization confirmations
- Button detection results
- AJAX request/response details
- Page detection logic

To enable verbose logging, check the browser console on page load.

### Best Practices

1. **Encapsulation**: All assignment-specific code is contained within the `MTAssignmentManager` object
2. **Single Entry Point**: Each module has one `init()` method as the entry point
3. **Conditional Loading**: Page-specific code only loads where needed
4. **Consistent Patterns**: Standardized AJAX, event handling, and error management
5. **Localization Ready**: All user-facing strings use the `mt_admin.i18n` object
6. **Graceful Degradation**: Checks for optional libraries (Select2, Datepicker) before use
7. **Memory Management**: Proper cleanup of dynamic elements and event handlers

## Auto-Assignment System

### Overview
The auto-assignment system automatically distributes candidates to jury members for evaluation. Located in `includes/ajax/class-mt-assignment-ajax.php`, it provides two distribution methods: balanced and random.

### Distribution Methods

#### Balanced Distribution
The balanced method ensures fair distribution where:
- Each jury member receives exactly `candidates_per_jury` candidates
- Candidates are distributed evenly across all jury members
- The algorithm prioritizes candidates with fewer existing assignments
- Ensures all candidates receive roughly equal review coverage

**Algorithm:**
1. Tracks assignment count for each candidate
2. Sorts candidates by their current assignment count (ascending)
3. Assigns least-reviewed candidates first to each jury member
4. Continues until each jury member has their quota

#### Random Distribution
The random method provides unpredictable distribution where:
- Each jury member receives exactly `candidates_per_jury` candidates
- Candidates are randomly selected for each jury member
- Efficient single-shuffle algorithm for better performance
- No bias in candidate selection

**Algorithm:**
1. Shuffles entire candidate list once at the beginning
2. Each jury member picks sequentially from the shuffled list
3. Skips already-assigned candidates if not clearing existing
4. Continues until quota is met or candidates exhausted

### Usage

#### AJAX Endpoint
```javascript
// Auto-assign candidates to jury members
jQuery.ajax({
    url: ajaxurl,
    type: 'POST',
    data: {
        action: 'mt_auto_assign',
        method: 'balanced', // or 'random'
        candidates_per_jury: 5,
        clear_existing: 'true', // optional
        nonce: mt_admin_vars.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data.message);
            console.log('Created:', response.data.created);
        } else {
            console.error(response.data);
        }
    }
});
```

#### PHP Implementation
```php
// Direct usage in PHP
$assignment_ajax = new MT_Assignment_Ajax();
$_POST['method'] = 'balanced';
$_POST['candidates_per_jury'] = 5;
$_POST['clear_existing'] = 'false';
$assignment_ajax->auto_assign();
```

### Parameters

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `method` | string | 'balanced' | Distribution method: 'balanced' or 'random' |
| `candidates_per_jury` | int | 10 | Number of candidates each jury member should evaluate (1-50) |
| `clear_existing` | string | 'false' | Whether to clear all existing assignments first |

### Edge Cases

The system handles several edge cases:

1. **Insufficient Candidates**: When there aren't enough candidates to give each jury member their full quota
   - System assigns as many as possible
   - Logs warnings about incomplete assignments
   - Returns partial success with detailed error messages

2. **Existing Assignments**: When not clearing existing assignments
   - Skips already-assigned candidate-jury pairs
   - Counts existing assignments toward jury member quotas
   - Maintains assignment integrity

3. **No Candidates or Jury Members**: 
   - Returns appropriate error messages
   - Prevents empty operations

### Error Handling

The system provides detailed error reporting:
- Security check failures (nonce verification)
- Permission denied (non-admin users)
- No jury members or candidates found
- Individual assignment creation failures
- Insufficient candidates warnings

### Logging

Comprehensive logging for debugging:
```
MT Auto Assign: Starting auto-assignment
MT Auto Assign: method=balanced, candidates_per_jury=5
MT Auto Assign: Found 10 jury members
MT Auto Assign: Found 50 candidates
MT Auto Assign: Using distribution method: balanced
MT Auto Assign: Balanced - Total assignments needed: 50
MT Auto Assign: Balanced - Reviews per candidate: 1
MT Auto Assign: Assigned candidate 123 to jury 456
MT Auto Assign: Completed - 50 assignments created, 0 errors
```

### Performance Considerations

- **Balanced Method**: O(n × m) where n = jury members, m = candidates
- **Random Method**: O(n × m) with single shuffle operation
- Database queries are optimized with bulk operations where possible
- Existing assignment checks use indexed lookups

### Database Schema

The assignments are stored in the `wp_mt_jury_assignments` table:
```sql
CREATE TABLE wp_mt_jury_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    jury_member_id INT NOT NULL,
    candidate_id INT NOT NULL,
    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT,
    UNIQUE KEY unique_assignment (jury_member_id, candidate_id),
    KEY idx_jury_member (jury_member_id),
    KEY idx_candidate (candidate_id)
);
```

### Security

All operations include:
- Nonce verification using `mt_admin_nonce`
- Capability check for `manage_options`
- Input sanitization for all parameters
- Prepared statements for database queries

### Customization Hooks

## CSV Import System (v2.2.24)

### Architecture Overview

The CSV import system consists of three main components:

1. **MT_Import_Handler** - Core CSV processing engine
2. **MT_CSV_Import_Ajax** - AJAX handler with security and validation  
3. **MTCSVImport** - JavaScript module with UI and progress tracking

### Import Handler Class

Located at `includes/admin/class-mt-import-handler.php`

#### Key Methods

```php
// Main import method
public function process_csv_import($file, $import_type, $update_existing = false)

// Type-specific processors
private function import_jury_members($data, $update_existing, &$results)
private function import_candidates($data, $update_existing, &$results)

// CSV parsing utilities
private function parse_csv_file($file)
private function detect_delimiter($sample)
private function map_headers($headers)
```

#### Field Mapping

The system supports flexible field mapping for German headers:

```php
// Candidates field mapping
$field_mapping = [
    'ID' => 'id',
    'Name' => 'name',
    'Organisation' => 'organisation',
    'Position' => 'position',
    'LinkedIn-Link' => 'linkedin',
    'Webseite' => 'website',
    'Article about coming of age' => 'article',
    'Description' => 'description',
    'Category' => 'category',
    'Status' => 'status'
];
```

### AJAX Handler

Located at `includes/ajax/class-mt-csv-import-ajax.php`

#### Security Features

1. **Nonce Verification**: Uses `mt_ajax_nonce` for CSRF protection
2. **Capability Checks**: Requires `manage_options` or `edit_posts`
3. **File Validation**: 
   - Type checking (CSV, TXT)
   - Size limits (10MB max)
   - MIME type validation
4. **Input Sanitization**: All user inputs sanitized

#### Progress Tracking

Uses WordPress transients for progress updates:

```php
$progress_key = 'mt_import_progress_' . get_current_user_id();
set_transient($progress_key, [
    'status' => 'processing',
    'message' => __('Processing CSV file...'),
    'percentage' => 10
], 300);
```

### JavaScript Module

Located at `assets/js/csv-import.js`

#### Features

1. **Progress Modal**: Real-time visual feedback
2. **File Validation**: Client-side pre-validation
3. **AJAX Upload**: FormData API for file uploads
4. **Error Display**: Row-specific error reporting

#### Key Methods

```javascript
MTCSVImport = {
    init: function() {},
    handleAjaxImport: function(form) {},
    startImport: function(formData) {},
    validateFile: function(e) {},
    showProgressModal: function() {},
    updateProgress: function(percentage, message) {}
}
```

### Import Process Flow

1. **File Selection**: User selects CSV file
2. **Client Validation**: JavaScript validates file type/size
3. **AJAX Upload**: File sent via FormData
4. **Server Validation**: PHP validates security and file
5. **CSV Parsing**: BOM detection, delimiter detection
6. **Data Processing**: Row-by-row import with validation
7. **Progress Updates**: Real-time progress via transients
8. **Result Display**: Success/error summary with details

### UTF-8 and BOM Handling

The system automatically handles UTF-8 encoding:

```php
// BOM detection and removal
$bom = fread($handle, 3);
if ($bom !== "\xEF\xBB\xBF") {
    rewind($handle);
}

// Header cleaning
$headers = array_map(function($header) {
    return trim(str_replace("\xEF\xBB\xBF", '', $header));
}, $headers);
```

### Error Handling

Comprehensive error tracking:

```php
$results = [
    'success' => 0,
    'updated' => 0,
    'skipped' => 0,
    'errors' => 0,
    'error_details' => [],
    'messages' => []
];

// Row-specific errors
$results['error_details'][] = [
    'row' => $row_num,
    'error' => 'Specific error message'
];
```

### Template System

CSV templates located in `data/templates/`:

- `candidates.csv` - German headers with sample data
- `jury-members.csv` - Standard jury member format
- Fallback generation if files missing

### Script Enqueuing

Scripts loaded conditionally on import/export page:

```php
if (isset($_GET['page']) && $_GET['page'] === 'mt-import-export') {
    wp_enqueue_style('mt-csv-import', ...);
    wp_enqueue_script('mt-csv-import', ...);
    wp_localize_script('mt-csv-import', 'mt_csv_import', [...]);
}
```

### WP-CLI Integration

Support for command-line imports:

```bash
wp eval "
\$handler = new \\MobilityTrailblazers\\Admin\\MT_Import_Handler();
\$result = \$handler->process_csv_import('/path/to/file.csv', 'candidates', false);
print_r(\$result);
"
```

### Import System Architecture (v2.2.25)

The import system has been consolidated into 4 main classes:

#### 1. MT_Import_Handler
**Purpose**: Core import processing engine
- CSV parsing and validation
- BOM detection and delimiter detection  
- Field mapping for candidates and jury members
- parse_evaluation_criteria() for German text parsing
- Single source of truth for all import operations

#### 2. MT_Import_Export
**Purpose**: Admin UI and export functionality
- Handles Import/Export admin page
- Form submissions and validation
- Template downloads
- Export operations for candidates, evaluations, assignments

#### 3. MT_CSV_Import_Ajax
**Purpose**: Modern AJAX import with progress tracking
- Real-time progress updates
- File upload via FormData API
- Comprehensive error reporting
- Uses MT_Import_Handler for processing

#### 4. MT_Import_Ajax  
**Purpose**: Quick import for candidates page
- Simple file picker interface
- Lightweight AJAX import
- Uses MT_Import_Handler for processing

### Deprecated Classes (v2.2.25)

The following classes have been removed:
- **MT_Profile_Importer** - Legacy importer, not used
- **MT_Enhanced_Profile_Importer** - Functionality moved to MT_Import_Handler

Migration guide:
```php
// Old way (deprecated)
MT_Enhanced_Profile_Importer::import_csv($file, $options);
MT_Enhanced_Profile_Importer::parse_evaluation_criteria($description);

// New way (v2.2.25+)
$handler = new MT_Import_Handler();
$handler->process_csv_import($file, 'candidates', $update_existing);
MT_Import_Handler::parse_evaluation_criteria($description);
```

### Customization Hooks

While the current implementation doesn't include filters, you can extend functionality by:
1. Subclassing `MT_Assignment_Ajax`
2. Adding filters in your custom implementation
3. Using the `MT_Assignment_Repository` methods directly

### Testing

To test the auto-assignment system:

1. **Create test data**:
   ```sql
   -- Add test jury members and candidates
   -- Ensure they have 'publish' status
   ```

2. **Test balanced distribution**:
   - Should evenly distribute candidates
   - Each jury member gets exact quota
   - Candidates with fewer assignments prioritized

3. **Test random distribution**:
   - Results should vary between runs
   - Each jury member gets exact quota (if possible)
   - No predictable pattern

4. **Test edge cases**:
   - Empty jury members list
   - Empty candidates list
   - More jury members than candidates
   - Existing assignments present

### Troubleshooting

Common issues and solutions:

1. **No assignments created**
   - Check if candidates/jury members exist and are published
   - Verify user has `manage_options` capability
   - Check browser console for AJAX errors

2. **Uneven distribution**
   - Ensure using 'balanced' method
   - Check for existing assignments if not clearing
   - Verify sufficient candidates available

3. **Performance issues**
   - Consider batch processing for large datasets
   - Check database indexes are properly created
   - Monitor query performance in debug log

### Version History

- **v2.2.12** (2025-08-12): Extended audit logging coverage
  - Added audit logging for bulk evaluation status changes
  - Enhanced assignment removal logging with full context
  - All critical actions now tracked in audit trail

- **v2.2.11** (2025-08-12): Code standardization and cleanup
  - Consolidated duplicate assignment removal methods
  - Verified database integrity for bulk operations

- **v2.2.10** (2025-08-12): Dashboard widget synchronization
  - Dynamic evaluation count from database
  - Added Recent Evaluations section to widget

- **v2.2.1** (2025-08-11): Complete refactoring of auto-assignment algorithms
  - Fixed balanced distribution logic
  - Implemented true random distribution
  - Improved performance and error handling
  - Added comprehensive logging

- **v2.0.0** (2024-01): Initial implementation
  - Basic round-robin assignment

## Dashboard Widget Synchronization

### Overview
The admin dashboard widget provides a quick overview of platform statistics and recent activity. As of v2.2.10, it's fully synchronized with the main dashboard.

### Data Sources
Both the main dashboard and widget use the same repository methods:
- `MT_Evaluation_Repository::get_statistics()` for evaluation counts
- `MT_Evaluation_Repository::find_all()` for recent evaluations
- WordPress post counts for candidates and jury members

### Widget Features
- **Statistics Grid**: Displays total candidates, jury members, and evaluations
- **Recent Candidates**: Shows 5 most recently added candidates
- **Recent Jury Members**: Lists 5 newest jury members
- **Recent Evaluations**: Displays 5 latest evaluation submissions with jury → candidate mapping
- **Quick Actions**: Shortcut buttons for common tasks

### Implementation
Located in `templates/admin/dashboard-widget.php`, the widget:
1. Fetches real-time statistics from repositories
2. Uses consistent data formatting with main dashboard
3. Provides three-column responsive layout
4. Updates automatically when dashboard refreshes

## Audit Logging System

### Overview
The platform includes comprehensive audit logging for compliance and security tracking. All critical actions are logged with full context.

### Logged Actions

#### Evaluation Actions
- `evaluation_submitted` - When evaluation is finalized
- `evaluation_saved_draft` - Draft saves
- `evaluation_approved` - Admin approval
- `evaluation_rejected` - Admin rejection
- `evaluation_reset` - Status reset to draft
- `evaluation_deleted` - Evaluation removal

#### Assignment Actions
- `assignment_created` - New assignment
- `assignment_removed` - Assignment deletion (with full context)
- `bulk_assignments_created` - Bulk assignment operations

#### Profile Actions
- `candidate_updated` - Candidate profile changes
- `jury_member_updated` - Jury member modifications

### Audit Log Details
Each log entry captures:
- User ID and username who performed action
- Action type and timestamp
- Object type and ID affected
- Additional context in JSON format
- Before/after states for changes

### Usage Example
```php
use MobilityTrailblazers\Core\MT_Audit_Logger;

// Log an action with context
MT_Audit_Logger::log(
    'evaluation_approved',
    'evaluation',
    $evaluation_id,
    [
        'jury_member_id' => $evaluation->jury_member_id,
        'candidate_id' => $evaluation->candidate_id,
        'previous_status' => 'draft',
        'new_status' => 'approved',
        'score' => $evaluation->score
    ]
);
```

### Viewing Audit Logs
Access via **Mobility Trailblazers → Audit Logs** in admin menu:
- Filter by user, action, object type, date range
- Sort by any column
- View detailed JSON data for each entry
- Export functionality for compliance reports

## User Roles and Capabilities

### Role Definitions

#### Administrator
Full platform access with all capabilities:
- `mt_manage_evaluations`
- `mt_manage_assignments`
- `mt_manage_settings`
- `mt_view_reports`
- `mt_export_data`
- `mt_view_audit_logs`

#### Jury Admin (`mt_jury_admin`)
Intermediate role for delegation (v2.2.9):
- `mt_view_all_evaluations` - View all evaluation data
- `mt_manage_assignments` - Create/remove assignments
- `mt_view_reports` - Access reporting features
- `mt_export_data` - Export capabilities

#### Jury Member (`mt_jury_member`)
Limited to evaluation duties:
- `mt_submit_evaluation` - Submit evaluations
- `mt_view_own_evaluations` - View their evaluations

### Capability Checks
All AJAX handlers use standardized capability checking:
```php
// In AJAX handler
$this->check_permission('mt_manage_assignments');

// Direct check
if (current_user_can('mt_export_data')) {
    // Allow export
}
```

### Assignment Management

#### Database Integrity
The `assigned_by` field is automatically populated:
- Single assignments: Set in `create()` method
- Bulk assignments: Set in `bulk_create()` method
- Always uses `get_current_user_id()`

#### Removal Standardization
As of v2.2.11, assignment removal is standardized:
- Single method `remove_assignment()` handles all deletions
- Accepts `assignment_id` parameter
- Captures full context before deletion for audit log
- Includes jury/candidate names in audit trail

## CSV Import System (v2.2.16)

### Overview
The platform provides a comprehensive CSV import system for candidate data with AJAX-based file upload, German text parsing, and extensive validation.

### Architecture

#### Frontend Components
- **JavaScript Module**: `assets/js/candidate-import.js`
  - File picker dialog using native browser file selection
  - AJAX file upload with FormData API
  - Real-time progress overlay
  - Success/error reporting with statistics

#### Backend Components
- **AJAX Handler**: `includes/ajax/class-mt-import-ajax.php`
  - Extends `MT_Base_Ajax` for consistent security
  - Comprehensive file validation
  - Integration with import service
  - Audit trail logging

- **Import Service**: `includes/admin/class-mt-enhanced-profile-importer.php`
  - CSV parsing with UTF-8 support
  - Field mapping system
  - German text extraction
  - URL validation and normalization

### CSV Format

#### Required Columns (German)
```csv
ID,Name,Organisation,Position,LinkedIn-Link,Webseite,Article about coming of age,Description,Category,Status
```

#### Field Mapping
| CSV Column | Meta Field | Type |
|------------|------------|------|
| ID | `_mt_candidate_id` | String |
| Name | `post_title` | Post field |
| Organisation | `_mt_organization` | Meta |
| Position | `_mt_position` | Meta |
| LinkedIn-Link | `_mt_linkedin_url` | URL |
| Webseite | `_mt_website_url` | URL |
| Article about coming of age | `_mt_article_url` | URL |
| Description | `post_content` | Post field |
| Category | `_mt_category_type` | Enum (Startup/Gov/Tech) |
| Status | `_mt_top_50_status` | Boolean |

### German Text Parsing

The system extracts evaluation criteria from the Description field using regex patterns:

```php
// Evaluation criteria patterns
$patterns = [
    '_mt_evaluation_courage' => '/Mut\s*&\s*Pioniergeist:\s*(.+?)(?=(?:Innovationsgrad:|...|$))/isu',
    '_mt_evaluation_innovation' => '/Innovationsgrad:\s*(.+?)(?=(?:Umsetzungsstärke:|...|$))/isu',
    '_mt_evaluation_implementation' => '/Umsetzungsstärke:\s*(.+?)(?=(?:Relevanz\s*&\s*Impact:|...|$))/isu',
    '_mt_evaluation_relevance' => '/Relevanz\s*&\s*Impact:\s*(.+?)(?=(?:Sichtbarkeit\s*&\s*Reichweite:|...|$))/isu',
    '_mt_evaluation_visibility' => '/Sichtbarkeit\s*&\s*Reichweite:\s*(.+?)$/isu'
];
```

### JavaScript Implementation

#### File Upload Handler
```javascript
jQuery('#mt-import-candidates').on('click', function(e) {
    e.preventDefault();
    
    // Create file input
    var fileInput = jQuery('<input type="file" accept=".csv" />');
    
    fileInput.on('change', function(e) {
        var file = e.target.files[0];
        
        // Validate file
        if (!file || !file.name.endsWith('.csv')) {
            alert(mt_import.i18n.invalid_file_type);
            return;
        }
        
        // Upload via AJAX
        var formData = new FormData();
        formData.append('action', 'mt_import_candidates');
        formData.append('csv_file', file);
        formData.append('nonce', mt_import.nonce);
        
        jQuery.ajax({
            url: mt_import.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                // Handle response
            }
        });
    });
    
    fileInput.click();
});
```

### Security Features

#### File Validation
1. **File Type Check**: Extension and MIME type validation
2. **Size Limit**: Maximum 10MB per file
3. **MIME Types Allowed**:
   - text/csv
   - text/plain
   - application/csv
   - application/vnd.ms-excel

#### Permission Checks
- Nonce verification: `mt_ajax_nonce`
- Capability check: `edit_posts` or `import`
- User context logging for audit trail

### Error Handling

The system provides detailed error reporting at multiple levels:

#### Row-Level Errors
```php
$result['error_details'][] = [
    'row' => $row_number,
    'error' => 'Invalid email format',
    'data' => $row_data
];
```

#### Import Statistics
```json
{
    "success": true,
    "data": {
        "imported": 25,
        "updated": 10,
        "skipped": 5,
        "errors": 2,
        "error_details": [...]
    }
}
```

### Custom Columns Display

The `MT_Candidate_Columns` class adds custom columns to the candidates list:

#### Column Definitions
- **Import ID**: Displays with code styling
- **Organization**: Company/institution name
- **Position**: Role/title
- **Category**: Color-coded with icons
  - Startup: Green lightbulb
  - Gov: Blue building
  - Tech: Red desktop
- **Top 50**: Checkmark indicator
- **Links**: Icons for LinkedIn, Website, Article

### Performance Considerations

1. **File Size**: Limited to 10MB to prevent timeouts
2. **Batch Processing**: Processes rows sequentially
3. **Memory Management**: Clears buffers after each row
4. **Database Operations**: Uses WordPress post functions for caching

### Testing the Import

#### Sample CSV Data
```csv
ID,Name,Organisation,Position,LinkedIn-Link,Webseite,Article about coming of age,Description,Category,Status
MT001,Max Müller,TechStart GmbH,CEO,https://linkedin.com/in/maxmueller,https://techstart.de,https://article.com/max,"Mut & Pioniergeist: Revolutionäre Ideen...",Startup,Top50
```

#### Validation Checklist
- [ ] File uploads successfully
- [ ] Progress overlay appears
- [ ] German characters preserved (ä, ö, ü, ß)
- [ ] URLs validated and normalized
- [ ] Categories mapped correctly
- [ ] Evaluation criteria extracted
- [ ] Import statistics displayed
- [ ] Error details shown for failures

### Troubleshooting

#### Common Issues

1. **"Invalid file type" error**
   - Ensure file has .csv extension
   - Check MIME type is text/csv or text/plain

2. **German characters corrupted**
   - Save CSV as UTF-8 with BOM
   - Use Excel's "CSV UTF-8" format

3. **URLs not importing**
   - Ensure URLs start with http:// or https://
   - System auto-adds https:// if missing

4. **Categories not recognized**
   - Use exact values: Startup, Gov, Tech
   - Case-insensitive matching

### Extending the Import

#### Adding Custom Fields
1. Update field mapping in `get_field_mapping()`
2. Add validation in `validate_row()`
3. Update meta field saving in `import_csv()`

#### Custom Validation
```php
// Add to MT_Enhanced_Profile_Importer
public static function validate_custom_field($value) {
    // Custom validation logic
    return $validated_value;
}
```

## Plugin Settings

### Data Management Settings (v2.2.13)

The plugin now includes comprehensive data management controls in the Settings page:

#### Remove Data on Uninstall
Location: **Mobility Trailblazers → Settings → Data Management**

This critical setting controls whether plugin data is preserved or deleted when uninstalling:
- **Default**: Unchecked (data preserved)
- **Option Name**: `mt_remove_data_on_uninstall`
- **Warning Level**: Strong visual warning with red text and warning icon

When enabled, the following data will be permanently deleted on uninstall:
- All candidate profiles and information
- All jury member profiles  
- All evaluations and scores
- All assignments and relationships
- All audit logs and history
- All custom database tables (mt_*)
- All plugin settings and configurations
- All uploaded files in custom directories
- All scheduled cron events
- All transients and cache data

Implementation in `uninstall.php`:
```php
// Check if data should be removed
if (get_option('mt_remove_data_on_uninstall', '0') === '1') {
    // Delete all plugin data
    \MobilityTrailblazers\Core\MT_Uninstaller::remove_all_data();
} else {
    // Only clear temporary data
    wp_clear_scheduled_hook('mt_cleanup_error_logs');
    // Clear transients only
}
```

The `remove_all_data()` method performs comprehensive cleanup:
1. Removes all custom post types and meta data
2. Drops all database tables
3. Deletes all plugin options (mt_* prefix and specific known options)
4. Removes user roles (mt_jury_member, mt_jury_admin)
5. Removes all custom capabilities from existing roles
6. Deletes uploaded files from /wp-content/uploads/mobility-trailblazers/
7. Clears all scheduled events
8. Removes all transients and site transients

### Settings Structure

All settings are stored as WordPress options with the `mt_` prefix:
- `mt_criteria_weights` - Evaluation criteria importance weights
- `mt_dashboard_settings` - Dashboard customization options
- `mt_candidate_presentation` - Candidate display settings
- `mt_default_language` - Default platform language
- `mt_enable_language_switcher` - Language switcher visibility
- `mt_auto_detect_language` - Browser language detection
- `mt_evaluations_per_page` - Pagination settings
- `mt_remove_data_on_uninstall` - Data deletion on uninstall

## AJAX Error Handling Standardization (v2.2.13)

### Overview
All AJAX handlers now use standardized error handling through the base class `MT_Base_Ajax`.

### Implementation
Instead of direct `wp_send_json_error()` calls, all handlers use:
```php
// For errors
$this->error($message, $additional_data);

// For success
$this->success($data, $message);
```

### Benefits
1. **Centralized Logging**: All errors automatically logged via MT_Logger
2. **Consistent Format**: Uniform error response structure
3. **Better Debugging**: Error context includes user ID, action, timestamp
4. **Maintainability**: Single point of control for error handling

### Affected Classes
- `MT_Admin_Ajax`: 1 instance standardized
- `MT_Assignment_Ajax`: 12 instances standardized
- `MT_Evaluation_Ajax`: 3 instances standardized

### Error Response Format
```json
{
    "success": false,
    "data": {
        "message": "Error message",
        "additional_data": {...}
    }
}
```

### Logging
All AJAX errors are logged with:
- Action name
- User ID
- Error message
- Additional context data
- Timestamp
- Request parameters (sanitized)

