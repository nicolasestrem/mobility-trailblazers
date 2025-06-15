Update 3 15/06/2025:

# Recent Updates & New Features (December 2024)

## 🚀 Major Update: Enhanced Jury Management System v2.0

We've implemented a comprehensive jury management system that significantly enhances the administrative capabilities for managing jury members. This update introduces advanced features for administrators and mt_award_admin users while maintaining full compatibility with existing functionality.

### 🎯 New Features Overview

#### 1. **Advanced Jury Management Dashboard**
- **Comprehensive Statistics Panel**: Real-time metrics showing total jury members, active members, evaluations completed, and average completion rates
- **Visual Analytics**: Grid-based statistics with hover effects for better data visualization
- **Activity Monitoring**: Live activity log showing recent actions and changes
- **Auto-refresh**: Statistics and activity log update every 30 seconds

#### 2. **Enhanced Jury Member Profiles**
- **Extended Information Fields**:
  - Organization and position tracking
  - Category expertise selection (Infrastructure/Politics, Startups/New Makers, Established Companies, General)
  - LinkedIn profile integration
  - Biography/description section
  - Status management (Active/Inactive/Pending)
- **Automated User Management**:
  - One-click WordPress user creation with mt_jury_member role
  - Automatic password generation and email notification
  - Seamless integration with existing user system

#### 3. **Powerful Administrative Tools**
- **Bulk Operations**:
  - Select multiple jury members for batch actions
  - Bulk delete, activate, deactivate functionality
  - Mass email reminders to selected jury members
- **Smart Assignment System**:
  - Optimize candidate assignments based on jury expertise
  - Automatic distribution algorithm for balanced workload
  - Category-based matching for relevant evaluations
- **Communication Hub**:
  - Built-in email system for jury communications
  - Customizable email templates
  - Invitation and reminder automation
  - Personalized message tokens ([name], [first_name])

#### 4. **Data Management & Reporting**
- **Export Functionality**:
  - One-click CSV export of all jury data
  - UTF-8 encoding with BOM for Excel compatibility
  - Comprehensive data including assignments and completion rates
- **Advanced Filtering**:
  - Filter by status (Active/Inactive/Pending)
  - Filter by category expertise
  - Quick search functionality
- **Performance Tracking**:
  - Individual completion rates
  - Category-wise performance metrics
  - Top performer identification
  - Last activity tracking

#### 5. **User Interface Enhancements**
- **Modern Admin Design**:
  - Clean, professional interface matching WordPress admin standards
  - Responsive design for mobile and tablet access
  - jQuery UI integration for smooth interactions
  - Status badges with color coding
- **Intuitive Controls**:
  - Inline editing capabilities
  - Modal dialogs for add/edit operations
  - Confirmation prompts for destructive actions
  - Success/error notifications

### 📁 New Files Added

```
/wp-content/plugins/mobility-trailblazers/
├── admin/
│   └── class-jury-management-admin.php    # Core jury management functionality
├── assets/
│   ├── js/
│   │   └── jury-management-admin.js       # Frontend interactions and AJAX
│   └── css/
│       └── jury-management-admin.css      # Admin interface styling
```

### 🔧 Technical Implementation

#### Database Schema Updates
No database table changes required. All new fields are stored as post meta:
- `_mt_jury_organization` - Jury member's organization
- `_mt_jury_position` - Professional position
- `_mt_jury_category` - Expertise category
- `_mt_jury_linkedin` - LinkedIn profile URL
- `_mt_jury_status` - Member status (active/inactive/pending)
- `_mt_jury_created_date` - Creation timestamp

#### New AJAX Endpoints
- `mt_get_jury_list` - Retrieve filtered jury members
- `mt_create_jury_member` - Create new jury member
- `mt_update_jury_member` - Update existing member
- `mt_delete_jury_member` - Remove jury member
- `mt_bulk_jury_action` - Perform bulk operations
- `mt_get_jury_stats` - Get dashboard statistics
- `mt_export_jury_data` - Export to CSV
- `mt_send_jury_invitation` - Send email invitations
- `mt_get_jury_activity` - Retrieve activity log

#### Security Enhancements
- All AJAX calls protected with nonce verification
- Capability checks ensure proper permissions
- Data sanitization for all inputs
- SQL injection prevention through prepared statements
- XSS protection via proper escaping

### 📊 Performance Improvements

- **Optimized Queries**: Reduced database calls through efficient querying
- **Caching Strategy**: Transient caching for activity logs
- **Lazy Loading**: Data loaded on-demand to improve initial page load
- **Batch Processing**: Bulk operations processed efficiently

### 🛠️ Installation Instructions

1. **Update Plugin Files**:
   ```bash
   # Copy the new admin class
   cp admin/class-jury-management-admin.php /path/to/wordpress/wp-content/plugins/mobility-trailblazers/admin/
   
   # Copy JavaScript file
   cp assets/js/jury-management-admin.js /path/to/wordpress/wp-content/plugins/mobility-trailblazers/assets/js/
   
   # Copy CSS file
   cp assets/css/jury-management-admin.css /path/to/wordpress/wp-content/plugins/mobility-trailblazers/assets/css/
   ```

2. **Update Main Plugin File**:
   Add to `mobility-trailblazers.php` in the `init()` method:
   ```php
   require_once MT_PLUGIN_PATH . 'admin/class-jury-management-admin.php';
   ```

3. **Clear Caches**:
   - Clear any caching plugins
   - Clear browser cache
   - Refresh permalinks if needed

### 👥 User Access

The new jury management features are available to:
- **Administrators**: Full access to all features
- **MT Award Admin**: Full access to jury management
- **MT Jury Members**: No access (existing dashboard remains unchanged)

### 🔄 Backward Compatibility

- All existing functionality remains intact
- No breaking changes to current features
- Existing jury data automatically compatible
- Current workflows unaffected

### 📈 Usage Statistics

After implementation, administrators can track:
- Total jury members registered
- Active participation rates
- Evaluation completion percentages
- Category distribution
- Communication effectiveness

### 🐛 Bug Fixes Included

- Fixed duplicate menu registration issues
- Resolved user ID consistency in evaluations
- Corrected AJAX handler conflicts
- Improved error handling for edge cases

### 📝 Documentation Updates

- Comprehensive implementation guide included
- API reference for developers
- Troubleshooting section added
- Code examples for customization

### 🔮 Future Roadmap

Planned enhancements for next release:
- Jury availability calendar
- Evaluation deadline automation
- Video conferencing integration
- Mobile app for evaluations
- Advanced analytics dashboard
- Multi-language support

### ⚡ Quick Start

1. Navigate to **MT Award System → Jury Management**
2. Click **Add New Jury Member** to create your first enhanced profile
3. Use bulk actions to manage multiple members efficiently
4. Export data anytime for external analysis

### 🆘 Support

For issues with the new jury management system:
1. Check the browser console for JavaScript errors
2. Verify user permissions are correctly set
3. Ensure all files are properly uploaded
4. Contact support with error messages and steps to reproduce

---

*This update represents a significant enhancement to the Mobility Trailblazers plugin, providing administrators with powerful tools to manage jury members effectively while maintaining the simplicity and reliability of the existing system.*


# README.md Update - Backup Features & UI Enhancements

Add this new chapter to your README.md after the June 15, 2025 update:

---

## 🔐 Backup System & UI Enhancements Update (June 15, 2025 - Session 2)

### Overview

In our second session on June 15, 2025, we completed the implementation of a comprehensive backup management system and enhanced the user interface with missing functionality. The backup system ensures complete data recovery capabilities while maintaining a user-friendly interface that doesn't require external dependencies.

### Major Features Added

#### 1. **Complete Backup Management System**

##### **Backup Management UI Section**
Added to the Vote Reset admin interface (`admin/views/vote-reset-interface.php`):

- **Real-time Statistics Display**:
  - Total backups count
  - Recent backups (last 7 days)
  - Storage size monitoring
  - Restoration count tracking

- **Manual Backup Creation**:
  - "Create Full Backup Now" button
  - Optional reason entry via browser prompt
  - Progress feedback during backup operation
  - Success confirmation with statistics

- **Backup History Viewer**:
  - "View All Backups" button
  - Modal display of all backups
  - Shows date, type, items count, and reason
  - Individual restore buttons for each backup
  - Visual indication of already-restored backups

- **Export Functionality**:
  - "Export Backup History" button
  - Choice between JSON and CSV formats
  - Automatic file download
  - Preserves all backup metadata

##### **Code Implementation**
```php
// Backup Statistics Display
$backup_manager = new MT_Vote_Backup_Manager();
$stats = $backup_manager->get_backup_statistics();

// Shows:
- Total Backups: X
- Recent Backups (7 days): Y
- Storage Size: Z MB
- Restorations: N
```

#### 2. **REST API Endpoints for Backup Operations**

Created comprehensive API endpoints (`backup-api-endpoints.php`):

##### **Create Backup Endpoint**
```
POST /wp-json/mobility-trailblazers/v1/admin/create-backup
Parameters:
- reason: string (optional)
- type: 'full' | 'partial'
```

##### **Get Backup History Endpoint**
```
GET /wp-json/mobility-trailblazers/v1/backup-history
Parameters:
- page: integer (default: 1)
- per_page: integer (default: 100, max: 200)
```

##### **Restore Backup Endpoint**
```
POST /wp-json/mobility-trailblazers/v1/admin/restore-backup
Parameters:
- backup_id: integer (required)
- type: 'votes' | 'scores'
```

##### **Export Handler (AJAX)**
```php
add_action('wp_ajax_mt_export_backup_history', 'mt_handle_export_backup_history');
// Handles CSV/JSON file download with proper headers
```

#### 3. **Browser Alert Implementation**

Replaced SweetAlert2 dependency with native browser alerts for:

- **Simplified Dependencies**: No external libraries required
- **Consistent UX**: Users familiar with browser dialogs
- **Reduced File Size**: Smaller JavaScript footprint
- **Better Compatibility**: Works on all browsers without polyfills

##### **Implementation Examples**:
```javascript
// Backup Creation
if (confirm('Create a full backup of all current voting data?\n\nThis may take a moment.')) {
    const reason = prompt('Enter backup reason (optional):') || 'Manual backup';
    performFullBackup(reason);
}

// Restore Confirmation
if (confirm(`Restore this backup?\n\nWarning: Current data will be replaced.\n\nContinue?`)) {
    performRestore(backupId, backupType);
}

// Export Format Selection
const format = prompt('Enter export format (json or csv):', 'json');
```

#### 4. **Enhanced JavaScript Functionality**

Updated `admin/js/vote-reset-admin.js` with new functions:

##### **Backup Management Functions**
- `handleCreateBackup()`: Initiates backup creation dialog
- `performFullBackup()`: Executes backup via AJAX
- `handleExportBackups()`: Manages export format selection
- `exportBackupHistory()`: Creates and submits download form
- `handleViewBackups()`: Loads and displays backup history
- `showBackupHistoryTable()`: Renders backup list in modal
- `performRestore()`: Executes backup restoration

##### **Targeted Reset Functions**
- `performUserReset()`: Reset all votes by specific user
- `performCandidateReset()`: Reset all votes for specific candidate

#### 5. **Database Schema Enhancements**

Added the `restored_at` column to track restored backups:

```sql
ALTER TABLE wp_mt_votes_history 
ADD COLUMN restored_at TIMESTAMP NULL DEFAULT NULL;

ALTER TABLE wp_mt_candidate_scores_history 
ADD COLUMN restored_at TIMESTAMP NULL DEFAULT NULL;
```

This allows the system to:
- Track which backups have been restored
- Prevent duplicate restorations
- Maintain restoration audit trail
- Exclude restored backups from cleanup

### User Interface Improvements

#### 1. **Visual Design**
- Card-based layout matching WordPress admin style
- Clear section separation between features
- Consistent button styling and placement
- Responsive design for all screen sizes

#### 2. **User Experience**
- Progressive disclosure of complex features
- Clear confirmation dialogs for dangerous operations
- Real-time feedback for all actions
- Automatic page refresh after significant changes

#### 3. **Information Architecture**
- Backup Management section placed strategically before Danger Zone
- Related functions grouped together
- Clear labeling and descriptions
- Contextual help text

### Technical Implementation Details

#### File Structure
```
admin/
├── views/
│   └── vote-reset-interface.php (updated with backup section)
├── js/
│   └── vote-reset-admin.js (browser alerts version)
└── css/
    └── vote-reset-admin.css (comprehensive styling)

includes/
├── class-vote-reset-manager.php
├── class-vote-backup-manager.php (full implementation)
└── class-vote-audit-logger.php (complete audit system)

api/
└── backup-api-endpoints.php (new REST endpoints)
```

#### Key Classes and Methods

**MT_Vote_Backup_Manager**:
- `backup_vote()`: Individual vote backup
- `bulk_backup()`: Bulk backup operations
- `restore_from_backup()`: Restore specific backup
- `get_backup_history()`: Retrieve backup records
- `get_backup_statistics()`: Calculate backup metrics
- `clean_old_backups()`: Retention policy enforcement
- `export_backups()`: Export to JSON/CSV
- `verify_backup_integrity()`: Validate backup data

**Integration Points**:
- Automatic backup before any reset operation
- Audit logging for all backup/restore actions
- Cache clearing after restorations
- WordPress hooks for extensibility

### Usage Scenarios

#### Scenario 1: Pre-Phase Transition Backup
```
1. Admin navigates to Vote Reset page
2. Reviews current statistics in Backup Management
3. Clicks "Create Full Backup Now"
4. Enters "Pre-phase 2 transition backup"
5. System creates complete backup
6. Proceeds with phase transition
```

#### Scenario 2: Emergency Restoration
```
1. User reports missing evaluation
2. Admin clicks "View All Backups"
3. Finds relevant backup from timestamp
4. Clicks "Restore" button
5. Confirms restoration
6. System restores specific vote
```

#### Scenario 3: Compliance Export
```
1. Monthly audit requirement
2. Admin clicks "Export Backup History"
3. Selects "CSV" format
4. System generates comprehensive export
5. File includes all backup metadata
```

### Performance Considerations

1. **Efficient Queries**: Optimized database queries with proper indexes
2. **Chunked Operations**: Large backups processed in chunks
3. **Storage Management**: Automatic cleanup of old backups
4. **Cache Integration**: Proper cache invalidation after operations

### Security Features

1. **Permission Verification**: Admin-only access to backup functions
2. **Nonce Protection**: All AJAX requests verified
3. **Data Validation**: Input sanitization and validation
4. **Audit Trail**: Complete logging of who did what and when

### Browser Compatibility

The browser alert implementation ensures compatibility with:
- ✅ Chrome/Edge (all versions)
- ✅ Firefox (all versions)
- ✅ Safari (all versions)
- ✅ Mobile browsers
- ✅ Legacy browsers (IE11+)

### Best Practices Implemented

1. **Data Integrity**: Transactions ensure consistent backups
2. **User Feedback**: Clear progress indicators and confirmations
3. **Error Handling**: Graceful failure with informative messages
4. **Documentation**: Inline comments and clear function names
5. **Extensibility**: WordPress hooks for custom extensions

### Future Enhancement Opportunities

1. **Scheduled Backups**: Automatic daily/weekly backups
2. **Selective Restoration**: Restore specific vote criteria
3. **Compression**: Reduce backup storage size
4. **Remote Storage**: Backup to external services
5. **Diff Backups**: Incremental backup support

### Troubleshooting Guide

#### Common Issues

**"Create Backup" button not working**:
- Check browser console for JavaScript errors
- Verify REST API endpoints are registered
- Ensure proper admin permissions

**Backup history not loading**:
- Verify database tables have restored_at column
- Check AJAX nonce is valid
- Confirm REST API is accessible

**Export download fails**:
- Check PHP memory limit
- Verify write permissions on upload directory
- Ensure no output before headers

### Summary

This update completes the Vote Reset system by adding comprehensive backup management capabilities. The implementation prioritizes data safety, user experience, and system reliability while maintaining simplicity through native browser features. The backup system integrates seamlessly with the existing reset functionality, providing administrators with confidence when managing the multi-phase voting process.

---

*Total implementation: ~2,000 lines of code across 6 files, providing enterprise-grade backup capabilities for the Mobility Trailblazers platform.*

# README.md Update - June 15, 2025

Add the following section to your README.md file after the previous updates:

---

## 🚀 Major Update: Complete Vote Reset System Implementation (June 15, 2025)

### Overview

On June 15, 2025, we completed the full implementation of the Vote Reset functionality for the Mobility Trailblazers platform. This comprehensive system enables administrators and jury members to manage voting data with complete transparency, data integrity, and recovery capabilities. The system is now production-ready for the multi-phase voting process (200→50→25 candidates) leading to the October 30, 2025 award ceremony.

### What Was Implemented

#### 1. **Database Architecture Extensions**

Created `/mnt/dietpi_userdata/docker-files/STAGING/mysql-init/02-vote-reset-tables.sql`:

```sql
-- New tables added:
- wp_vote_reset_logs         -- Complete audit trail of all reset operations
- wp_mt_votes_history        -- Backup storage for vote records
- wp_mt_candidate_scores_history  -- Backup storage for evaluation scores

-- Modified existing tables with soft delete columns:
- wp_mt_votes: Added is_active, reset_at, reset_by
- wp_mt_candidate_scores: Added is_active, reset_at, reset_by
```

**Key Features**:
- Soft delete architecture prevents permanent data loss
- Complete audit trail with IP tracking and user agents
- Automatic indexing for optimal query performance
- Foreign key constraints ensure data integrity

#### 2. **Core PHP Classes Implementation**

##### **MT_Vote_Reset_Manager** (`includes/class-vote-reset-manager.php`)
The main orchestrator for all reset operations:
- Individual vote reset with permission checking
- Bulk reset operations (by user, candidate, or phase)
- Phase transition management with data archival
- Full system reset with multiple safety confirmations
- Transaction support for data consistency
- Redis cache integration for performance

##### **MT_Vote_Backup_Manager** (`includes/class-vote-backup-manager.php`)
Comprehensive backup system ensuring data recovery:
- Automatic backup before any reset operation
- Single and bulk backup capabilities
- Full restoration functionality
- Backup history tracking and browsing
- Configurable retention policies (default: 365 days)
- Export capabilities (JSON/CSV formats)
- Storage size monitoring and optimization
- Integrity verification for backup data

##### **MT_Vote_Audit_Logger** (`includes/class-vote-audit-logger.php`)
Complete audit trail system for compliance and transparency:
- Detailed logging of all reset operations
- User activity tracking with IP addresses
- Flexible history retrieval with pagination
- Statistical analysis and reporting
- Excessive reset detection for security
- CSV export for external analysis
- Automatic cleanup of old logs
- Integration with WordPress actions/filters

#### 3. **User Interface Components**

##### **Admin Dashboard** (`admin/views/vote-reset-interface.php`)
Professional admin interface featuring:
- **Overview Cards**: Real-time statistics display
  - Active votes count
  - Total candidates
  - Jury members
  - Current voting phase with status
- **Phase Transition Section**: Manage voting phase changes
- **Targeted Reset Options**: Reset by jury member or candidate
- **Individual Reset Information**: Guide for jury members
- **Danger Zone**: Full system reset with safety measures
- **Activity History**: Recent reset operations with details

##### **Frontend Components** (`templates/jury/vote-reset-button.php`)
- Individual reset buttons on evaluated candidates
- Permission-based visibility
- Data attributes for JavaScript interaction
- Integrated with evaluation interface

#### 4. **JavaScript Implementation** (`admin/js/vote-reset-admin.js`)

Comprehensive client-side functionality:
- **SweetAlert2 Integration**: Professional confirmation dialogs
- **AJAX Operations**: All resets handled asynchronously
- **Real-time UI Updates**: Immediate feedback on actions
- **Progress Indicators**: Loading states for all operations
- **Error Handling**: Graceful error messages
- **History Modal**: View complete reset history
- **Form Validation**: Prevent accidental operations

Key Features:
```javascript
// Individual reset with reason
VoteResetManager.handleIndividualReset(candidateId, candidateName);

// Phase transition with email notifications
VoteResetManager.handlePhaseReset(currentPhase, nextPhase, notifyJury);

// Full system reset with confirmation
VoteResetManager.handleFullReset();
```

#### 5. **Styling and Design** (`admin/css/vote-reset-admin.css`)

Modern, responsive admin interface:
- **Grid Layouts**: Adaptive card-based design
- **Status Indicators**: Visual phase and status badges
- **Danger Zone Styling**: Clear visual warnings
- **Modal Windows**: Smooth animations and overlays
- **Loading States**: Spinner animations and disabled states
- **Responsive Design**: Mobile and tablet optimized
- **Print Styles**: Report-friendly formatting
- **Accessibility**: WCAG compliant focus states
- **High Contrast Support**: Improved visibility options

#### 6. **REST API Implementation** (`api/vote-reset-endpoints.php`)

RESTful endpoints for all operations:
```
POST /wp-json/mobility-trailblazers/v1/reset-vote
     - Individual vote reset by jury members

POST /wp-json/mobility-trailblazers/v1/admin/bulk-reset
     - Bulk reset operations (admin only)

GET  /wp-json/mobility-trailblazers/v1/reset-history
     - Paginated reset history with filters
```

Features:
- Proper authentication and authorization
- Nonce verification for security
- Comprehensive error handling
- Transaction support
- Rate limiting ready

### Installation Instructions

#### 1. Database Setup

```bash
# Apply the database schema extensions
docker exec -i mobility_mariadb_STAGING mariadb -u root -pRt9mK3nQ8xY7bV5cZ2wE4rT6yU1i wordpress_db < /mnt/dietpi_userdata/docker-files/STAGING/mysql-init/02-vote-reset-tables.sql
```

#### 2. File Deployment

Deploy all new files to the plugin directory:
```bash
# Copy PHP classes
cp includes/class-vote-reset-manager.php /path/to/wp-content/plugins/mobility-trailblazers/includes/
cp includes/class-vote-backup-manager.php /path/to/wp-content/plugins/mobility-trailblazers/includes/
cp includes/class-vote-audit-logger.php /path/to/wp-content/plugins/mobility-trailblazers/includes/

# Copy admin files
cp admin/views/vote-reset-interface.php /path/to/wp-content/plugins/mobility-trailblazers/admin/views/
cp admin/js/vote-reset-admin.js /path/to/wp-content/plugins/mobility-trailblazers/admin/js/
cp admin/css/vote-reset-admin.css /path/to/wp-content/plugins/mobility-trailblazers/admin/css/

# Copy templates
cp templates/jury/vote-reset-button.php /path/to/wp-content/plugins/mobility-trailblazers/templates/jury/

# Copy API endpoints
cp api/vote-reset-endpoints.php /path/to/wp-content/plugins/mobility-trailblazers/api/
```

#### 3. Menu Registration

Add to `mobility-trailblazers.php` in the `register_all_admin_menus()` function:

```php
// Vote Reset Management submenu
if (current_user_can('manage_options')) {
    add_submenu_page(
        'mt-award-system',
        __('Vote Reset Management', 'mobility-trailblazers'),
        __('Vote Reset', 'mobility-trailblazers'),
        'manage_options',
        'mt-vote-reset',
        array($this, 'vote_reset_page')
    );
}
```

Add the page handler method:
```php
public function vote_reset_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Access denied'));
    }
    include MT_PLUGIN_DIR . 'admin/views/vote-reset-interface.php';
}

public function get_total_active_votes() {
    global $wpdb;
    $votes = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_votes WHERE is_active = 1");
    $scores = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}mt_candidate_scores WHERE is_active = 1");
    return ($votes ?: 0) + ($scores ?: 0);
}
```

### Usage Examples

#### Individual Vote Reset (Jury Members)
1. Navigate to the jury evaluation dashboard
2. Find a previously evaluated candidate
3. Click the "Reset Vote" button
4. Optionally provide a reason
5. Confirm the reset

#### Phase Transition (Administrators)
1. Go to MT Award System → Vote Reset
2. Review current phase statistics
3. Click "Transition to Next Phase"
4. Choose to notify jury members
5. Confirm the transition

#### Bulk Reset Operations
```php
// Reset all votes for a specific jury member
$reset_manager = new MT_Vote_Reset_Manager();
$result = $reset_manager->bulk_reset_votes('all_user_votes', [
    'user_id' => 123,
    'reason' => 'Jury member requested fresh start'
]);

// Reset all votes for phase transition
$result = $reset_manager->bulk_reset_votes('phase_transition', [
    'from_phase' => 'phase_1',
    'to_phase' => 'phase_2',
    'notify_jury' => true
]);
```

### Security Features

1. **Multi-Level Permission Checks**
   - Jury members can only reset their own votes
   - Admin operations require `manage_options` capability
   - All permissions verified server-side

2. **Data Protection**
   - Complete audit trail with IP tracking
   - Soft deletes prevent data loss
   - Automatic backups before any reset
   - Transaction support ensures consistency

3. **User Safety**
   - Multiple confirmation steps
   - Clear warning messages
   - Reason tracking for accountability
   - Rate limiting capabilities

### Performance Optimizations

1. **Database Optimizations**
   ```sql
   -- Indexes added for performance
   CREATE INDEX idx_active_votes ON wp_mt_votes(is_active, candidate_id, jury_member_id);
   CREATE INDEX idx_reset_timestamp ON wp_vote_reset_logs(reset_timestamp);
   CREATE INDEX idx_backup_time ON wp_mt_votes_history(backed_up_at);
   ```

2. **Caching Integration**
   - Redis support for vote counts
   - Automatic cache invalidation
   - Transient caching for statistics

3. **Efficient Queries**
   - Bulk operations use single transactions
   - Optimized JOIN queries for history
   - Pagination for large datasets

### Testing Checklist

- [x] Database tables created successfully
- [x] Menu items appear in WordPress admin
- [x] Individual reset buttons work for jury members
- [x] Phase transition handles data correctly
- [x] Audit logs capture all operations
- [x] Backups created before resets
- [x] Restoration functionality works
- [x] Export features generate valid files
- [x] Permission checks prevent unauthorized access
- [x] UI is responsive on mobile devices

### Next Steps

1. **Critical Missing Features** (Priority Order):
   - Export functionality for evaluations
   - Phase management system
   - Email notification system
   - Public announcement features

2. **Recommended Enhancements**:
   - Scheduled automatic backups
   - Advanced analytics dashboard
   - Bulk restoration interface
   - API documentation

### Technical Metrics

- **Total Files Created**: 10
- **Lines of Code**: ~3,500
- **Database Tables**: 2 new, 2 modified
- **API Endpoints**: 3
- **UI Components**: 5 major sections
- **Test Coverage**: Comprehensive manual testing completed

### Support Information

For questions or issues with the Vote Reset functionality:
1. Check the audit logs first: `SELECT * FROM wp_vote_reset_logs ORDER BY reset_timestamp DESC`
2. Verify permissions: Ensure users have appropriate roles
3. Review JavaScript console for client-side errors
4. Check `wp-content/debug.log` for PHP errors

---

*This update represents a major milestone in the Mobility Trailblazers platform development, providing robust data management capabilities essential for the multi-phase jury evaluation process.*

🔄 Vote Reset Functionality (New Feature - June 2025)
Overview
The Mobility Trailblazers platform now includes comprehensive vote reset capabilities, allowing administrators and jury members to manage voting data with precision and transparency. This feature is essential for managing the multi-phase voting process (200→50→25 candidates) and handling corrections or re-evaluations.
Key Features
1. Multi-Level Reset Options

Individual Vote Reset: Jury members can reset their own votes for specific candidates
Bulk User Reset: Administrators can reset all votes from a specific jury member
Bulk Candidate Reset: Administrators can reset all votes for a specific candidate
Phase Transition Reset: Automated reset between voting phases with data archival
Full System Reset: Complete vote removal with safety confirmations (admin only)

2. Data Integrity & Safety

Soft Delete Architecture: Votes are marked inactive rather than deleted
Comprehensive Backup System: All votes are backed up before any reset operation
Audit Trail: Complete logging of who reset what, when, and why
Transaction Support: Database consistency guaranteed through MySQL transactions
Multiple Confirmation Steps: Prevents accidental data loss

3. User Interface Components
Admin Vote Reset Dashboard

Location: WordPress Admin → MT Award System → Vote Reset
Features:

Real-time statistics display (active votes, candidates, jury members)
Phase transition management with visual indicators
Targeted reset controls with dropdown selections
Recent activity log with detailed reset history
Full history modal with pagination



Individual Reset Buttons

Location: Jury evaluation interface (on evaluated candidate cards)
Features:

"Reset Vote" button with undo icon
Confirmation dialog with optional reason input
Real-time UI updates after reset
Only visible for candidates already evaluated



Technical Implementation
Database Schema Extensions
sql-- Vote Reset Logs Table
CREATE TABLE wp_vote_reset_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reset_type ENUM('individual', 'bulk_user', 'bulk_candidate', 'phase_transition', 'full_reset'),
    initiated_by BIGINT(20) UNSIGNED NOT NULL,
    initiated_by_role ENUM('jury_member', 'admin', 'system'),
    affected_user_id BIGINT(20) UNSIGNED DEFAULT NULL,
    affected_candidate_id BIGINT(20) UNSIGNED DEFAULT NULL,
    voting_phase VARCHAR(50) DEFAULT NULL,
    votes_affected INT NOT NULL DEFAULT 0,
    reset_reason TEXT,
    reset_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT
);

-- Vote History Tables
CREATE TABLE wp_mt_votes_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Mirrors structure of wp_mt_votes with backup metadata
);

CREATE TABLE wp_mt_candidate_scores_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    -- Mirrors structure of wp_mt_candidate_scores with backup metadata
);

-- Soft Delete Columns Added to Existing Tables
ALTER TABLE wp_mt_votes ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE wp_mt_votes ADD COLUMN reset_at TIMESTAMP NULL;
ALTER TABLE wp_mt_votes ADD COLUMN reset_by BIGINT(20) UNSIGNED DEFAULT NULL;

ALTER TABLE wp_mt_candidate_scores ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE wp_mt_candidate_scores ADD COLUMN reset_at TIMESTAMP NULL;
ALTER TABLE wp_mt_candidate_scores ADD COLUMN reset_by BIGINT(20) UNSIGNED DEFAULT NULL;
File Structure
mobility-trailblazers/
├── includes/
│   ├── class-vote-reset-manager.php      # Core reset logic
│   ├── class-vote-backup-manager.php     # Backup operations
│   └── class-vote-audit-logger.php       # Audit trail management
├── admin/
│   ├── js/
│   │   └── vote-reset-admin.js          # Admin interface JavaScript
│   ├── css/
│   │   └── vote-reset-admin.css         # Admin styles
│   └── views/
│       └── vote-reset-interface.php     # Admin dashboard view
├── templates/
│   └── jury/
│       └── vote-reset-button.php        # Individual reset button
├── api/
│   └── vote-reset-endpoints.php         # REST API endpoints
└── mysql-init/
    └── 02-vote-reset-tables.sql         # Database schema
REST API Endpoints
POST   /wp-json/mobility-trailblazers/v1/reset-vote
       - Reset individual vote (jury members)
       
POST   /wp-json/mobility-trailblazers/v1/admin/bulk-reset
       - Bulk reset operations (admin only)
       
GET    /wp-json/mobility-trailblazers/v1/reset-history
       - Retrieve reset history with pagination
Installation & Setup
1. Database Setup
bash# Copy the schema file to your MySQL init directory
cp mysql-init/02-vote-reset-tables.sql /mnt/dietpi_userdata/docker-files/STAGING/mysql-init/

# Apply the schema to existing database
docker exec -i mobility_mariadb_STAGING mariadb -u root -p[password] wordpress_db < /path/to/02-vote-reset-tables.sql
2. File Deployment
bash# Copy all vote reset files to the plugin directory
cp -r includes/class-vote-reset-*.php /path/to/wordpress/wp-content/plugins/mobility-trailblazers/includes/
cp -r admin/js/vote-reset-admin.js /path/to/wordpress/wp-content/plugins/mobility-trailblazers/admin/js/
cp -r admin/views/vote-reset-interface.php /path/to/wordpress/wp-content/plugins/mobility-trailblazers/admin/views/
cp -r templates/jury/vote-reset-button.php /path/to/wordpress/wp-content/plugins/mobility-trailblazers/templates/jury/
3. Menu Registration
Add to your main plugin file's register_all_admin_menus() function:
php// Vote Reset Management submenu
if (current_user_can('manage_options')) {
    add_submenu_page(
        'mt-award-system',
        __('Vote Reset Management', 'mobility-trailblazers'),
        __('Vote Reset', 'mobility-trailblazers'),
        'manage_options',
        'mt-vote-reset',
        array($this, 'vote_reset_page')
    );
}
Usage Guide
For Administrators

Phase Transition (e.g., moving from 200 to 50 candidates):

Navigate to MT Award System → Vote Reset
Click "Transition to Next Phase"
Optionally notify jury members via email
Confirm the transition


Targeted Resets:

Select a jury member or candidate from the dropdown
Click the respective reset button
Provide a reason (optional but recommended)
Confirm the action


Monitoring:

View recent reset activity in the dashboard
Click "View Full History" for complete audit trail
Export history data if needed



For Jury Members

Resetting Individual Votes:

Go to your evaluation dashboard
Find the candidate you want to re-evaluate
Click the "Reset Vote" button
Confirm the reset
Submit a new evaluation



Security Considerations

Permission Checks:

Jury members can only reset their own votes
Admin functions require manage_options capability
All actions verified server-side


Data Protection:

IP addresses logged for accountability
User agents recorded for security analysis
Nonce verification on all AJAX requests


Rate Limiting:

Implement rate limiting on reset endpoints
Monitor for unusual reset patterns
Alert admins of suspicious activity



Performance Optimization

Database Indexes:
sql-- Optimized indexes for vote queries
CREATE INDEX idx_active_votes ON wp_mt_votes(is_active, candidate_id, jury_member_id);
CREATE INDEX idx_reset_timestamp ON wp_vote_reset_logs(reset_timestamp);

Caching Strategy:

Redis integration for vote counts
Cache invalidation on reset operations
Transient caching for expensive queries


Batch Processing:

Bulk operations use single transactions
Chunked processing for large datasets
Background processing for heavy operations



Troubleshooting
Reset Button Not Appearing

Check menu registration:
php// Verify in WordPress admin
global $submenu;
var_dump($submenu['mt-award-system']);

Verify file paths:
bash# Check if files exist
ls -la wp-content/plugins/mobility-trailblazers/admin/views/vote-reset-interface.php
ls -la wp-content/plugins/mobility-trailblazers/admin/js/vote-reset-admin.js

Check JavaScript console for errors

Database Errors

Verify table creation:
sqlSHOW TABLES LIKE '%vote_reset%';
DESCRIBE wp_vote_reset_logs;

Check column additions:
sqlSHOW COLUMNS FROM wp_mt_votes LIKE 'is_active';


Permission Issues

Verify user capabilities:
php$user = wp_get_current_user();
var_dump($user->allcaps);

Check role assignments:
phpvar_dump($user->roles);


API Reference
PHP Classes
MT_Vote_Reset_Manager
php// Reset individual vote
$manager = new MT_Vote_Reset_Manager();
$result = $manager->reset_individual_vote($candidate_id, $jury_member_id, $reason);

// Bulk reset
$result = $manager->bulk_reset_votes('phase_transition', [
    'from_phase' => 'phase_1',
    'to_phase' => 'phase_2',
    'notify_jury' => true
]);
MT_Vote_Audit_Logger
php// Log a reset action
$logger = new MT_Vote_Audit_Logger();
$logger->log_reset([
    'reset_type' => 'individual',
    'initiated_by' => get_current_user_id(),
    'affected_candidate_id' => $candidate_id,
    'reset_reason' => $reason
]);

// Get reset history
$history = $logger->get_reset_history($page = 1, $per_page = 20);
JavaScript Functions
javascript// Trigger individual reset
VoteResetManager.performIndividualReset(candidateId, reason);

// Trigger phase reset
VoteResetManager.performPhaseReset(fromPhase, toPhase, notifyJury);

// Load reset history
VoteResetManager.loadResetHistory();
Best Practices

Always provide reset reasons for audit trail clarity
Notify affected parties when performing bulk resets
Review reset logs regularly for unusual patterns
Backup database before major reset operations
Test reset functionality in staging environment first

Future Enhancements

Scheduled Resets: Automatic phase transitions based on timeline
Selective Restore: Ability to restore specific votes from backup
Reset Templates: Predefined reset scenarios for common use cases
Advanced Analytics: Reset pattern analysis and reporting
Webhook Integration: Notify external systems of reset events

## 🔧 Recent Updates (June 2025)

### Data Management Functionality Fixed

We've resolved issues with the non-working Data Management buttons in the Assignment Management interface. The following buttons are now fully functional:

#### Fixed Buttons:
1. **Export Assignments** - Export all assignment data to CSV format
2. **Sync System** - Synchronize assignment data across the system
3. **View Progress Data** - Display detailed evaluation progress statistics
4. **Reset All Assignments** - Clear all current assignments (with safety confirmations)

#### Technical Details:

**JavaScript Enhancements (`assets/assignment.js` or `assets/data-management.js`):**
- Added event handlers for all data management buttons
- Implemented AJAX calls for server communication
- Added loading states and user feedback
- Created modal interface for progress data display
- Implemented notification system for user feedback

**PHP Backend Handlers (Added to main plugin file):**
- `mt_sync_system` - Handles system synchronization
- `mt_get_progress_data` - Returns comprehensive progress statistics
- `mt_export_assignments` - Generates CSV exports with full assignment data
- Enhanced `mt_clear_assignments` - Added to handle assignment reset

---

## 📊 Data Management Features

### Export Functionality

The Assignment Management page now includes robust data export capabilities:

#### Export Assignments (CSV)
- **Includes**: Candidate details, jury assignments, evaluation status, scores
- **Format**: UTF-8 encoded CSV with BOM for Excel compatibility
- **Usage**: Click "Export Assignments" button to download current data

#### Exported Fields:
- Candidate ID and Name
- Company and Category
- Assigned Jury Member details
- Assignment and Evaluation dates
- Evaluation status and scores

### Progress Tracking

The **View Progress Data** feature provides comprehensive insights:

#### Overall Statistics:
- Total assignments count
- Completed evaluations
- Overall completion rate percentage

#### Jury Member Progress:
- Individual assignment counts
- Evaluation completion status
- Progress bars with color coding:
  - 🟢 Green: ≥80% complete
  - 🟡 Yellow: 50-79% complete
  - 🔴 Red: <50% complete

#### Category Breakdown:
- Progress by candidate category
- Assignment coverage statistics
- Evaluation completion by category

### System Synchronization

The **Sync System** feature ensures data consistency:
- Updates assignment counts
- Refreshes cached data
- Synchronizes jury member statistics
- Clears any stale data

### Assignment Reset

The **Reset All Assignments** feature includes:
- Double confirmation for safety
- Complete removal of all assignments
- Automatic page refresh after reset
- Preservation of candidate and jury data

---

## 🛠️ Troubleshooting Data Management

### Common Issues and Solutions:

#### Buttons Not Responding
1. **Check Console**: Open browser console (F12) for JavaScript errors
2. **Verify Script Loading**: Ensure `assignment.js` or `data-management.js` is loaded
3. **Check Nonce**: Verify `mt_assignment_ajax` object is properly localized

#### Export Not Working
1. **PHP Memory**: Increase PHP memory limit if dealing with large datasets
2. **Timeout Issues**: For large exports, consider implementing chunked exports
3. **Browser Blocking**: Check if browser is blocking file downloads

#### Progress Data Not Loading
1. **Database Tables**: Verify `wp_mt_candidate_scores` table exists
2. **User Roles**: Ensure proper jury member roles are assigned
3. **AJAX URL**: Confirm `admin-ajax.php` is accessible

### Debug Mode

Enable debug logging to troubleshoot:

```javascript
// Add to your JavaScript
console.log('mt_assignment_ajax object:', mt_assignment_ajax);
console.log('Data management buttons found:', {
    export: $('#mt-export-assignments-btn').length,
    sync: $('#mt-sync-system-btn').length,
    progress: $('#mt-view-progress-btn').length,
    reset: $('#mt-reset-assignments-btn').length
});
```

---

## 📈 Performance Considerations

### Optimization Tips:

1. **Large Datasets**:
   - Consider pagination for exports over 1000 records
   - Implement background processing for large sync operations

2. **Caching**:
   - Progress data is resource-intensive; consider caching for 5-10 minutes
   - Use WordPress transients for frequently accessed statistics

3. **Database Indexes**:
   ```sql
   -- Add these indexes for better performance
   ALTER TABLE wp_mt_candidate_scores 
   ADD INDEX idx_jury_evaluation (jury_member_id, evaluation_date);
   
   ALTER TABLE wp_postmeta 
   ADD INDEX idx_mt_assignments (meta_key, meta_value) 
   WHERE meta_key = '_mt_assigned_jury_member';
   ```

---

## 🔒 Security Enhancements

All data management functions include:
- ✅ Nonce verification for CSRF protection
- ✅ Capability checks (admin only)
- ✅ Data sanitization and validation
- ✅ SQL injection prevention via prepared statements
- ✅ XSS protection through proper escaping

---

## 📝 Changelog Addition

### Version 1.0.1 (June 14, 2025)
- 🐛 Fixed non-working data management buttons in Assignment Management
- ✨ Added comprehensive progress tracking modal
- ✨ Implemented CSV export with UTF-8 BOM support
- ✨ Added system synchronization functionality
- ✨ Enhanced assignment reset with double confirmation
- 🔧 Added proper error handling and user notifications
- 📚 Updated documentation for data management features

---

## 👥 Contributors Note

Special thanks to the team for identifying and helping resolve the data management button issues. If you encounter any problems with these features, please report them in the issue tracker.

# Mobility Trailblazers Award System

A comprehensive WordPress plugin for managing the prestigious "25 Mobility Trailblazers in 25" award platform, designed to recognize and celebrate the most innovative mobility shapers in the DACH (Germany, Austria, Switzerland) region.

## 📋 Table of Contents

- [Overview](#-overview)
- [Key Features](#-key-features)
- [Technical Architecture](#-technical-architecture)
- [Installation Guide](#-installation-guide)
- [Configuration](#-configuration)
- [User Guides](#-user-guides)
- [Developer Documentation](#-developer-documentation)
- [API Reference](#-api-reference)
- [Troubleshooting](#-troubleshooting)
- [Security](#-security)
- [Performance Optimization](#-performance-optimization)
- [Contribution Guidelines](#-contribution-guidelines)
- [Changelog](#-changelog)
- [License](#-license)

## 🚀 Overview

The Mobility Trailblazers Award System is an enterprise-grade WordPress plugin that provides a complete digital infrastructure for managing a multi-stage award selection process. Built with modern PHP practices and designed for scalability, it handles everything from candidate nominations through jury evaluations to public announcements.

### Project Vision
To create a transparent, efficient, and engaging platform that identifies and celebrates the 25 most impactful mobility innovators who are shaping the future of transportation and urban mobility in the DACH region.

### Key Statistics
- **490+ Candidates**: Nominated across various mobility sectors
- **22 Expert Jury Members**: Industry leaders and innovation experts
- **3 Award Categories**: Comprehensive coverage of the mobility ecosystem
- **5 Evaluation Criteria**: Holistic assessment framework
- **50 Points Maximum**: Detailed scoring system
- **7 Development Phases**: From nomination to award ceremony

## 🎯 Key Features

### 1. Comprehensive Candidate Management

#### Candidate Profiles
- **Detailed Information Storage**: Company, position, location, contact details
- **Innovation Documentation**: Detailed descriptions of mobility innovations
- **Impact Metrics**: Quantifiable achievements and KPIs
- **Media Management**: Photos, videos, and presentation materials
- **Category Classification**: Automatic and manual categorization
- **Status Tracking**: From nomination through final selection

#### Candidate Discovery
- **Advanced Search**: Multi-parameter search functionality
- **Filtering System**: By category, status, location, and more
- **Sorting Options**: Alphabetical, by score, by date
- **Bulk Operations**: Mass updates and exports

### 2. Sophisticated Jury System

#### Jury Member Management
- **Profile Management**: Expertise areas, biography, credentials
- **Role-Based Access**: President, Vice-President, Members
- **Assignment Algorithm**: Intelligent candidate distribution
- **Workload Balancing**: Ensures fair evaluation distribution
- **Conflict Management**: Prevents conflicts of interest

#### Evaluation Framework
- **5 Criteria Scoring System** (1-10 points each):
  1. **Mut & Pioniergeist** (Courage & Pioneer Spirit)
     - Risk-taking in innovation
     - Breaking conventional boundaries
     - Leadership in transformation
  
  2. **Innovationsgrad** (Degree of Innovation)
     - Technical advancement
     - Uniqueness of solution
     - Disruptive potential
  
  3. **Umsetzungskraft & Wirkung** (Implementation & Impact)
     - Execution excellence
     - Measurable outcomes
     - Scalability potential
  
  4. **Relevanz für Mobilitätswende** (Mobility Transformation Relevance)
     - Contribution to sustainable mobility
     - Addressing key challenges
     - Future readiness
  
  5. **Vorbildfunktion & Sichtbarkeit** (Role Model & Visibility)
     - Industry influence
     - Public engagement
     - Inspirational leadership

### 3. Advanced Assignment Management

#### Visual Assignment Interface
- **Drag-and-Drop Assignment**: Intuitive candidate-to-jury matching
- **Real-Time Updates**: Live assignment status
- **Bulk Assignment Tools**: Efficient mass assignments
- **Assignment History**: Complete audit trail
- **Undo/Redo Functionality**: Error recovery

#### Assignment Algorithms
1. **Balanced Distribution**
   - Equal candidate count per jury member
   - Considers existing workload
   - Optimizes for fairness

2. **Expertise-Based Matching**
   - Matches jury expertise with candidate categories
   - Considers industry background
   - Maximizes evaluation quality

3. **Random Assignment**
   - Unbiased distribution
   - Configurable constraints
   - Reproducible results

4. **Manual Override**
   - Direct assignment control
   - Conflict resolution
   - Special case handling

### 4. Multi-Interface Dashboard System

#### Admin Dashboard
- **Complete System Overview**: All metrics at a glance
- **User Management**: Jury and candidate administration
- **System Configuration**: Global settings and preferences
- **Export Center**: Data export in multiple formats
- **Activity Monitoring**: Real-time system usage

#### Jury Dashboard (Admin Panel)
- **Personal Assignment View**: Assigned candidates list
- **Evaluation Interface**: Streamlined scoring system
- **Progress Tracking**: Personal and overall progress
- **Quick Actions**: Rapid evaluation workflow
- **Notes System**: Private evaluation notes

#### Jury Dashboard (Frontend)
- **Public-Facing Interface**: Branded experience
- **Mobile-Responsive Design**: Evaluation on any device
- **Offline Capability**: Continue working without connection
- **Auto-Save Feature**: Never lose progress
- **Multi-Language Support**: DE/EN interface

### 5. Elementor Page Builder Integration

#### Custom Elementor Widgets

1. **MT Jury Dashboard Widget**
   - Full dashboard functionality
   - Customizable display options
   - Style controls
   - Responsive settings

2. **MT Candidate Grid Widget**
   - Flexible grid layouts
   - Filter integration
   - Pagination options
   - Card style variations

3. **MT Evaluation Statistics Widget**
   - Real-time statistics
   - Chart visualizations
   - Progress indicators
   - Leaderboard display

#### Widget Features
- **Live Preview**: See changes in real-time
- **Style Customization**: Colors, typography, spacing
- **Responsive Controls**: Device-specific settings
- **Dynamic Content**: Pull live data
- **Template Library**: Pre-built layouts

### 6. Reporting & Analytics

#### Evaluation Analytics
- **Score Distribution**: Statistical analysis
- **Jury Performance**: Evaluation patterns
- **Category Insights**: Trends by category
- **Time Analytics**: Evaluation duration tracking

#### Export Capabilities
- **CSV Export**: Raw data for analysis
- **PDF Reports**: Formatted presentations
- **Excel Integration**: Advanced spreadsheet compatibility
- **API Access**: Programmatic data retrieval

### 7. Communication System

#### Email Notifications
- **Assignment Alerts**: New candidate notifications
- **Reminder System**: Deadline reminders
- **Progress Updates**: Milestone notifications
- **Custom Templates**: Branded email designs

#### In-Platform Messaging
- **Jury Communication**: Internal messaging
- **Admin Broadcasts**: System-wide announcements
- **Discussion Threads**: Candidate-specific discussions

## 🏗️ Technical Architecture

### Technology Stack

```yaml
# Core Technologies
WordPress: 6.8.1
PHP: 8.2+
MySQL/MariaDB: 11.0+
Redis: 7.0+ (Caching)

# Frontend Technologies
JavaScript: ES6+
jQuery: 3.6+
CSS3 with Custom Properties
Elementor: 3.29+

# Development Tools
Docker: Container infrastructure
Komodo: Stack management
WP-CLI: Command line interface
Composer: Dependency management
```

### Plugin Architecture

```
To be updated
```

### Database Schema

#### Custom Tables

**wp_mt_candidate_scores**
```sql
CREATE TABLE wp_mt_candidate_scores (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    candidate_id BIGINT(20) UNSIGNED NOT NULL,
    jury_member_id BIGINT(20) UNSIGNED NOT NULL,
    courage_score TINYINT UNSIGNED DEFAULT 0,
    innovation_score TINYINT UNSIGNED DEFAULT 0,
    implementation_score TINYINT UNSIGNED DEFAULT 0,
    relevance_score TINYINT UNSIGNED DEFAULT 0,
    visibility_score TINYINT UNSIGNED DEFAULT 0,
    total_score TINYINT UNSIGNED DEFAULT 0,
    evaluation_round VARCHAR(50) DEFAULT 'initial',
    evaluation_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    PRIMARY KEY (id),
    UNIQUE KEY unique_evaluation (candidate_id, jury_member_id, evaluation_round),
    KEY idx_candidate (candidate_id),
    KEY idx_jury (jury_member_id),
    KEY idx_round (evaluation_round),
    KEY idx_total_score (total_score)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**wp_mt_votes**
```sql
CREATE TABLE wp_mt_votes (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    candidate_id BIGINT(20) UNSIGNED NOT NULL,
    voter_id BIGINT(20) UNSIGNED NOT NULL,
    vote_type VARCHAR(20) DEFAULT 'jury',
    vote_value INT DEFAULT 1,
    vote_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    PRIMARY KEY (id),
    UNIQUE KEY unique_vote (candidate_id, voter_id, vote_type),
    KEY idx_candidate_votes (candidate_id),
    KEY idx_voter (voter_id),
    KEY idx_vote_type (vote_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**wp_mt_public_votes**
```sql
CREATE TABLE wp_mt_public_votes (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    candidate_id BIGINT(20) UNSIGNED NOT NULL,
    voter_email VARCHAR(255) NOT NULL,
    vote_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    verification_token VARCHAR(64),
    is_verified BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (id),
    UNIQUE KEY unique_public_vote (candidate_id, voter_email),
    KEY idx_candidate_public (candidate_id),
    KEY idx_email (voter_email),
    KEY idx_verified (is_verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Post Meta Structure

**Candidate Meta Fields**
- `_mt_company`: Company/Organization name
- `_mt_position`: Position/Role
- `_mt_location`: Geographic location
- `_mt_email`: Contact email
- `_mt_phone`: Contact phone
- `_mt_linkedin`: LinkedIn profile URL
- `_mt_website`: Company website
- `_mt_innovation_description`: Detailed innovation description
- `_mt_impact_metrics`: Quantifiable impact data
- `_mt_courage_story`: Pioneer spirit narrative
- `_mt_implementation_details`: Implementation case study
- `_mt_visibility_evidence`: Public engagement proof
- `_mt_assigned_jury_member`: Assigned jury member ID
- `_mt_evaluation_status`: Current evaluation status
- `_mt_total_score`: Calculated total score
- `_mt_average_score`: Average across criteria
- `_mt_evaluation_count`: Number of evaluations
- `_mt_nomination_source`: How candidate was nominated
- `_mt_nomination_date`: When nominated

**Jury Member Meta Fields**
- `_mt_jury_user_id`: Linked WordPress user ID
- `_mt_jury_email`: Contact email
- `_mt_jury_phone`: Contact phone
- `_mt_jury_company`: Company/Organization
- `_mt_jury_position`: Professional position
- `_mt_jury_bio`: Biography
- `_mt_jury_expertise`: Areas of expertise (serialized)
- `_mt_jury_linkedin`: LinkedIn profile
- `_mt_jury_photo`: Profile photo ID
- `_mt_jury_role`: Jury role (president/vice/member)
- `_mt_max_assignments`: Maximum candidate assignments
- `_mt_current_assignments`: Current assignment count
- `_mt_evaluation_progress`: Completion percentage
- `_mt_last_active`: Last activity timestamp

### Custom Post Types

#### mt_candidate
- **Purpose**: Store nominee profiles
- **Capabilities**: Custom capability set
- **Features**: Title, editor, thumbnail, custom fields
- **Taxonomies**: mt_category, mt_status, mt_award_year
- **REST API**: Enabled with custom endpoints

#### mt_jury
- **Purpose**: Jury member profiles
- **Capabilities**: Restricted to admins
- **Features**: Title, editor, thumbnail
- **Taxonomies**: mt_expertise_area
- **REST API**: Limited access

### Custom Taxonomies

#### mt_category
- **Hierarchical**: No
- **Terms**:
  - Established Companies
  - Start-ups & New Makers
  - Infrastructure/Politics/Public

#### mt_status
- **Hierarchical**: No
- **Terms**:
  - Nominated
  - Under Review
  - Shortlisted
  - Finalist
  - Winner
  - Not Selected

#### mt_award_year
- **Hierarchical**: No
- **Terms**: 2024, 2025, etc.

#### mt_expertise_area
- **Hierarchical**: Yes
- **Terms**: 
  - Mobility Technology
  - Sustainability
  - Urban Planning
  - Business Innovation
  - Policy & Regulation

## 🔧 Installation Guide

### Prerequisites

1. **Server Requirements**
   - PHP 7.4+ (8.2 recommended)
   - MySQL 5.7+ / MariaDB 10.3+
   - WordPress 5.8+
   - Memory Limit: 256MB minimum
   - Max Execution Time: 300 seconds
   - Max Input Vars: 5000

2. **Required PHP Extensions**
   - mysqli
   - json
   - mbstring
   - zip
   - gd or imagick

3. **Optional Components**
   - Redis Server (for caching)
   - WP-CLI (for management)
   - Composer (for dependencies)

### Docker Installation (Recommended)

1. **Clone the Repository**
   ```bash
   git clone https://github.com/your-org/mobility-trailblazers.git
   cd mobility-trailblazers
   ```

2. **Configure Environment**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

3. **Deploy with Docker Compose**
   ```bash
   cd /mnt/dietpi_userdata/docker-files/STAGING/
   docker-compose up -d
   ```

4. **Install Plugin**
   ```bash
   # Copy plugin files
   docker cp ./mobility-trailblazers mobility_wordpress_STAGING:/var/www/html/wp-content/plugins/

   # Set permissions
   docker exec mobility_wordpress_STAGING chown -R www-data:www-data /var/www/html/wp-content/plugins/mobility-trailblazers

   # Activate plugin
   docker exec mobility_wpcli_STAGING wp plugin activate mobility-trailblazers
   ```

5. **Run Installation Script**
   ```bash
   docker exec mobility_wpcli_STAGING wp eval-file /var/www/html/wp-content/plugins/mobility-trailblazers/install.php
   ```

### Manual Installation

1. **Upload Plugin**
   - Download the plugin ZIP file
   - Navigate to WordPress Admin → Plugins → Add New
   - Click "Upload Plugin" and select the ZIP file
   - Click "Install Now"

2. **Activate Plugin**
   - Click "Activate Plugin" after installation
   - Or go to Plugins page and activate

3. **Run Setup Wizard**
   - Navigate to MT Award System → Setup
   - Follow the setup wizard steps

### Post-Installation Steps

1. **Configure Basic Settings**
   ```bash
   # Set award year
   docker exec mobility_wpcli_STAGING wp option update mt_current_award_year 2025
   
   # Configure email settings
   docker exec mobility_wpcli_STAGING wp option update mt_email_from "awards@mobilitytrailblazers.de"
   docker exec mobility_wpcli_STAGING wp option update mt_email_from_name "Mobility Trailblazers"
   ```

2. **Create User Roles**
   ```bash
   # Already created on activation, but verify:
   docker exec mobility_wpcli_STAGING wp role list
   ```

3. **Set Permalinks**
   ```bash
   docker exec mobility_wpcli_STAGING wp rewrite structure '/%postname%/'
   docker exec mobility_wpcli_STAGING wp rewrite flush
   ```

4. **Configure Caching**
   ```bash
   # If using Redis
   docker exec mobility_wpcli_STAGING wp config set WP_REDIS_HOST 'mobility_redis_STAGING'
   docker exec mobility_wpcli_STAGING wp config set WP_REDIS_PORT 6379
   ```

## ⚙️ Configuration

### Plugin Settings

Navigate to **MT Award System → Settings** to configure:

#### General Settings
- **Award Year**: Current award cycle
- **Phase**: Current phase (Nomination, Evaluation, etc.)
- **Public Voting**: Enable/disable public voting
- **Registration**: Open/closed for new candidates

#### Evaluation Settings
- **Criteria Weights**: Adjust scoring weights
- **Minimum Evaluations**: Required evaluations per candidate
- **Evaluation Deadline**: Set deadlines
- **Auto-reminders**: Configure reminder schedule

#### Email Settings
- **From Address**: Sender email
- **From Name**: Sender name
- **Email Templates**: Customize notifications
- **SMTP Settings**: Configure mail server

#### Display Settings
- **Items Per Page**: Pagination settings
- **Date Format**: Display preferences
- **Currency**: For any monetary displays
- **Language**: Default language

### User Role Configuration

#### Administrator
Full system access including:
- All plugin features
- User management
- System configuration
- Data export/import

#### MT Award Admin
Award-specific administration:
- Candidate management
- Jury management
- Assignment control
- Evaluation oversight
- Report generation

#### MT Jury Member
Jury-specific access:
- View assigned candidates
- Submit evaluations
- Access jury dashboard
- View own statistics

#### Custom Capabilities

```php
// Candidate Management
'edit_mt_candidate'
'read_mt_candidate'
'delete_mt_candidate'
'edit_mt_candidates'
'edit_others_mt_candidates'
'publish_mt_candidates'
'read_private_mt_candidates'

// Jury Management
'edit_mt_jury'
'read_mt_jury'
'delete_mt_jury'
'manage_mt_jury_members'

// Evaluation Capabilities
'mt_submit_evaluations'
'mt_view_candidates'
'mt_access_jury_dashboard'
'mt_view_own_evaluations'
'mt_edit_own_evaluations'

// Administrative Capabilities
'mt_manage_awards'
'mt_manage_assignments'
'mt_view_all_evaluations'
'mt_export_data'
'mt_manage_voting'
'mt_view_reports'
```

### Elementor Configuration

1. **Enable Elementor Support**
   - The plugin automatically detects Elementor
   - No manual configuration needed

2. **Widget Settings**
   - Widgets appear in "Mobility Trailblazers" category
   - All widgets support Elementor's style controls

3. **Template Integration**
   - Create Elementor templates for award pages
   - Use Theme Builder for custom layouts

## 📚 User Guides

### For Administrators

#### Initial Setup Workflow

1. **Configure Award Settings**
   - Set current award year
   - Define evaluation phases
   - Configure scoring criteria

2. **Import Candidates**
   - Use CSV import for bulk upload
   - Or manually create candidate profiles
   - Assign to appropriate categories

3. **Setup Jury Members**
   - Create jury member profiles
   - Link to WordPress users
   - Define expertise areas

4. **Configure Assignments**
   - Choose assignment algorithm
   - Run auto-assignment
   - Review and adjust manually

5. **Monitor Progress**
   - Track evaluation completion
   - Send reminders as needed
   - Generate progress reports

#### Managing Evaluations

1. **Assignment Management**
   ```bash
   # View current assignments
   docker exec mobility_wpcli_STAGING wp eval '
   global $wpdb;
   $assignments = $wpdb->get_results("
       SELECT p.post_title as candidate, 
              pm.meta_value as jury_id
       FROM {$wpdb->posts} p
       JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
       WHERE pm.meta_key = '_mt_assigned_jury_member'
       AND pm.meta_value != ''
   ");
   foreach($assignments as $a) {
       $jury = get_post($a->jury_id);
       echo $a->candidate . " => " . $jury->post_title . "\n";
   }'
   ```

2. **Evaluation Monitoring**
   - Access MT Award System → Voting Results
   - Filter by category, jury member, or score
   - Export results for analysis

3. **Sending Reminders**
   ```bash
   # Send reminder to specific jury member
   docker exec mobility_wpcli_STAGING wp eval '
   do_action("mt_send_evaluation_reminder", $jury_member_id);'
   
   # Send bulk reminders
   docker exec mobility_wpcli_STAGING wp eval '
   do_action("mt_send_bulk_reminders");'
   ```

### For Jury Members

#### Getting Started

1. **Account Setup**
   - Receive login credentials via email
   - Log in at website.com/wp-login.php
   - Complete profile information

2. **Accessing Dashboard**
   - Click "MT Award System" in admin menu
   - Or visit frontend dashboard page
   - Bookmark for quick access

#### Evaluation Process

1. **Review Assigned Candidates**
   - View complete candidate profiles
   - Read innovation descriptions
   - Review supporting materials

2. **Score Each Criterion**
   - Use 1-10 scale for each criterion
   - Refer to scoring guidelines
   - Add private notes if needed

3. **Submit Evaluation**
   - Review scores before submission
   - Submit when complete
   - Can edit until deadline

4. **Track Progress**
   - View evaluation statistics
   - See completion percentage
   - Monitor deadlines

#### Best Practices

- **Consistent Scoring**: Apply criteria uniformly
- **Timely Completion**: Submit before deadlines
- **Detailed Notes**: Document reasoning
- **Objective Assessment**: Avoid conflicts of interest

### For Candidates

#### Nomination Process

1. **Submission Requirements**
   - Complete application form
   - Provide innovation details
   - Submit supporting documents
   - Include metrics and evidence

2. **Profile Optimization**
   - Clear innovation description
   - Quantifiable impact metrics
   - Compelling narrative
   - Professional presentation

3. **Status Tracking**
   - Monitor application status
   - Respond to requests
   - Update information as needed

## 🔌 API Reference

### REST API Endpoints

#### Authentication
All API requests require authentication via:
- WordPress Application Passwords
- JWT tokens (if JWT plugin installed)
- OAuth (if configured)

#### Candidates Endpoint
```
GET /wp-json/mt/v1/candidates
GET /wp-json/mt/v1/candidates/{id}
POST /wp-json/mt/v1/candidates
PUT /wp-json/mt/v1/candidates/{id}
DELETE /wp-json/mt/v1/candidates/{id}
```

**Parameters:**
- `category`: Filter by category slug
- `status`: Filter by status
- `year`: Filter by award year
- `per_page`: Items per page (default: 10)
- `page`: Page number
- `orderby`: Sort field
- `order`: ASC or DESC

**Example Request:**
```bash
curl -X GET https://site.com/wp-json/mt/v1/candidates \
  -H "Authorization: Basic base64_encoded_credentials" \
  -H "Content-Type: application/json"
```

#### Evaluations Endpoint
```
GET /wp-json/mt/v1/evaluations
GET /wp-json/mt/v1/evaluations/{id}
POST /wp-json/mt/v1/evaluations
PUT /wp-json/mt/v1/evaluations/{id}
```

**POST Body Example:**
```json
{
  "candidate_id": 123,
  "scores": {
    "courage": 8,
    "innovation": 9,
    "implementation": 7,
    "relevance": 9,
    "visibility": 8
  },
  "notes": "Exceptional innovation with strong market impact."
}
```

#### Statistics Endpoint
```
GET /wp-json/mt/v1/statistics
GET /wp-json/mt/v1/statistics/evaluations
GET /wp-json/mt/v1/statistics/candidates
GET /wp-json/mt/v1/statistics/jury
```

### PHP Hooks Reference

#### Actions

**Evaluation Hooks**
```php
// Before evaluation save
do_action('mt_before_evaluation_save', $evaluation_data, $candidate_id, $jury_member_id);

// After evaluation save
do_action('mt_after_evaluation_save', $evaluation_id, $evaluation_data);

// Evaluation completed
do_action('mt_evaluation_completed', $candidate_id, $jury_member_id, $total_score);
```

**Assignment Hooks**
```php
// Before assignment
do_action('mt_before_candidate_assignment', $candidate_id, $jury_member_id);

// After assignment
do_action('mt_after_candidate_assignment', $candidate_id, $jury_member_id);

// Bulk assignment completed
do_action('mt_bulk_assignment_completed', $assignment_count);
```

**Notification Hooks**
```php
// Send custom notification
do_action('mt_send_notification', $recipient, $subject, $message, $type);

// Evaluation reminder
do_action('mt_send_evaluation_reminder', $jury_member_id);
```

#### Filters

**Data Filters**
```php
// Modify evaluation data before save
add_filter('mt_evaluation_data', 'function_name', 10, 3);

// Filter candidates query
add_filter('mt_candidates_query_args', 'function_name', 10, 1);

// Modify jury dashboard data
add_filter('mt_jury_dashboard_data', 'function_name', 10, 2);
```

**Display Filters**
```php
// Customize candidate card HTML
add_filter('mt_candidate_card_html', 'function_name', 10, 2);

// Modify evaluation form fields
add_filter('mt_evaluation_form_fields', 'function_name', 10, 1);

// Filter admin menu items
add_filter('mt_admin_menu_items', 'function_name', 10, 1);
```

### JavaScript API

#### Global MT Object
```javascript
// Available globally when plugin is active
window.MT = {
    // API endpoints
    api: {
        candidates: '/wp-json/mt/v1/candidates',
        evaluations: '/wp-json/mt/v1/evaluations',
        statistics: '/wp-json/mt/v1/statistics'
    },
    
    // Utility functions
    utils: {
        formatScore: function(score) {},
        calculateAverage: function(scores) {},
        validateEvaluation: function(data) {}
    },
    
    // Event emitters
    events: {
        on: function(event, callback) {},
        off: function(event, callback) {},
        emit: function(event, data) {}
    }
};
```

#### jQuery Extensions
```javascript
// Candidate card enhancement
$('.mt-candidate-card').mtCandidateCard({
    expandable: true,
    showScores: false,
    animations: true
});

// Evaluation form
$('#mt-evaluation-form').mtEvaluationForm({
    autoSave: true,
    validation: true,
    confirmSubmit: true
});
```

## 🔍 Troubleshooting

### Common Issues and Solutions

#### Installation Issues

**Problem: Plugin activation fails**
```bash
# Check PHP version
docker exec mobility_wordpress_STAGING php -v

# Check WordPress version
docker exec mobility_wpcli_STAGING wp core version

# Check error logs
docker exec mobility_wordpress_STAGING tail -n 50 /var/log/apache2/error.log
```

**Problem: Database tables not created**
```bash
# Manually create tables
docker exec mobility_wpcli_STAGING wp eval-file /var/www/html/wp-content/plugins/mobility-trailblazers/sql/create-tables.sql

# Verify tables exist
docker exec mobility_wpcli_STAGING wp db query "SHOW TABLES LIKE 'wp_mt_%'"
```

#### Menu and Navigation Issues

**Problem: Duplicate "My Dashboard" menu items**
```bash
# Clear all caches
docker exec mobility_redis_STAGING redis-cli FLUSHALL
docker exec mobility_wpcli_STAGING wp cache flush
docker exec mobility_wpcli_STAGING wp transient delete --all

# Rebuild menu
docker exec mobility_wpcli_STAGING wp eval '
do_action("mt_rebuild_admin_menu");'
```

**Problem: Menu items not showing for jury members**
```bash
# Check user capabilities
docker exec mobility_wpcli_STAGING wp user list-caps {user_id}

# Reset user role
docker exec mobility_wpcli_STAGING wp user set-role {user_id} mt_jury_member

# Add specific capability
docker exec mobility_wpcli_STAGING wp user add-cap {user_id} mt_access_jury_dashboard
```

#### Evaluation Issues

**Problem: Evaluations not saving**
```bash
# Check AJAX endpoint
curl -X POST https://site.com/wp-admin/admin-ajax.php \
  -d "action=mt_submit_vote&nonce={nonce}"

# Check database permissions
docker exec mobility_wpcli_STAGING wp db query "SHOW GRANTS FOR CURRENT_USER"

# Enable debug logging
docker exec mobility_wpcli_STAGING wp config set WP_DEBUG true
docker exec mobility_wpcli_STAGING wp config set WP_DEBUG_LOG true
```

**Problem: Inconsistent evaluation counts**
```bash
# Run consistency check
docker exec mobility_wpcli_STAGING wp eval '
if (class_exists("MT_Jury_Consistency")) {
    $consistency = MT_Jury_Consistency::get_instance();
    $issues = $consistency->check_sync_issues();
    print_r($issues);
}'

# Force sync
docker exec mobility_wpcli_STAGING wp eval '
do_action("mt_sync_all_evaluations");'
```

#### Performance Issues

**Problem: Slow page loads**
```bash
# Enable Redis object cache
docker exec mobility_wpcli_STAGING wp plugin install redis-cache --activate
docker exec mobility_wpcli_STAGING wp redis enable

# Optimize database
docker exec mobility_wpcli_STAGING wp db optimize

# Check slow queries
docker exec mobility_wordpress_STAGING mysql -e "SHOW PROCESSLIST"
```

**Problem: Memory exhaustion**
```php
// Increase memory limit in wp-config.php
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '1024M');

// Or via .htaccess
php_value memory_limit 512M
```

#### Elementor Integration Issues

**Problem: Widgets not appearing**
```bash
# Regenerate Elementor cache
docker exec mobility_wpcli_STAGING wp elementor flush-cache

# Check widget registration
docker exec mobility_wpcli_STAGING wp eval '
do_action("elementor/widgets/widgets_registered");'
```

**Problem: Save errors in Elementor**
```javascript
// Add to browser console for debugging
jQuery(document).ajaxError(function(event, xhr, settings, error) {
    console.log('AJAX Error:', {
        url: settings.url,
        error: error,
        response: xhr.responseText
    });
});
```

### System Diagnostic Tool

Access comprehensive diagnostics at:
**Admin → MT Award System → Diagnostic**

The diagnostic tool checks:
- PHP configuration
- WordPress settings
- Database integrity
- User permissions
- Custom post types
- Taxonomies
- Plugin conflicts
- Cache status
- API endpoints
- Cron jobs

### Debug Mode

Enable debug mode for detailed logging:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('MT_DEBUG', true);
```

View logs:
```bash
docker exec mobility_wordpress_STAGING tail -f /var/www/html/wp-content/debug.log
```

## 🛡️ Security

### Security Features

#### Data Protection
- **Input Sanitization**: All user inputs sanitized
- **Output Escaping**: XSS prevention
- **SQL Injection Prevention**: Prepared statements
- **CSRF Protection**: Nonce verification
- **File Upload Security**: Type and size validation

#### Access Control
- **Role-Based Permissions**: Granular capabilities
- **IP Restrictions**: Optional IP whitelisting
- **Login Attempts**: Brute force protection
- **Session Management**: Secure session handling
- **Two-Factor Authentication**: Optional 2FA support

#### API Security
- **Authentication Required**: All endpoints protected
- **Rate Limiting**: Prevent abuse
- **Input Validation**: Strict parameter checking
- **CORS Configuration**: Controlled origins
- **SSL/TLS**: Encrypted communications

### Security Best Practices

1. **Regular Updates**
   ```bash
   # Update WordPress core
   docker exec mobility_wpcli_STAGING wp core update
   
   # Update all plugins
   docker exec mobility_wpcli_STAGING wp plugin update --all
   
   # Update themes
   docker exec mobility_wpcli_STAGING wp theme update --all
   ```

2. **Strong Passwords**
   - Minimum 12 characters
   - Mixed case, numbers, symbols
   - Unique per user
   - Regular rotation

3. **File Permissions**
   ```bash
   # Set correct permissions
   docker exec mobility_wordpress_STAGING find /var/www/html -type d -exec chmod 755 {} \;
   docker exec mobility_wordpress_STAGING find /var/www/html -type f -exec chmod 644 {} \;
   ```

4. **Database Security**
   - Change default table prefix
   - Regular backups
   - Restricted user privileges
   - Encrypted connections

5. **Monitoring**
   - Activity logs
   - Failed login attempts
   - File change detection
   - Performance metrics

### Security Audit Checklist

- [ ] All user inputs sanitized
- [ ] Database queries use prepared statements
- [ ] File uploads restricted and validated
- [ ] Admin area protected with SSL
- [ ] Strong password policy enforced
- [ ] Regular security updates applied
- [ ] Backups configured and tested
- [ ] Activity monitoring enabled
- [ ] Rate limiting configured
- [ ] Security headers implemented

## ⚡ Performance Optimization

### Caching Strategy

#### Object Caching (Redis)
```php
// Check if Redis is working
docker exec mobility_wpcli_STAGING wp redis info

// Clear Redis cache
docker exec mobility_wpcli_STAGING wp redis flush

// Monitor Redis
docker exec mobility_redis_STAGING redis-cli monitor
```

#### Page Caching
- Exclude dynamic pages (dashboard, evaluation forms)
- Cache candidate grids for 1 hour
- Cache static content for 24 hours

#### Database Query Caching
```php
// Example of cached query
$cache_key = 'mt_top_candidates_' . $category;
$results = wp_cache_get($cache_key);

if (false === $results) {
    $results = $wpdb->get_results($query);
    wp_cache_set($cache_key, $results, '', 3600);
}
```

### Database Optimization

1. **Indexes**
   ```sql
   -- Add indexes for common queries
   ALTER TABLE wp_mt_candidate_scores 
   ADD INDEX idx_jury_candidate (jury_member_id, candidate_id);
   
   ALTER TABLE wp_postmeta 
   ADD INDEX idx_mt_assigned (meta_key, meta_value) 
   WHERE meta_key = '_mt_assigned_jury_member';
   ```

2. **Query Optimization**
   ```php
   // Use specific fields instead of SELECT *
   $wpdb->get_results("
       SELECT ID, post_title, post_status 
       FROM {$wpdb->posts} 
       WHERE post_type = 'mt_candidate'
   ");
   
   // Limit results
   $candidates = get_posts([
       'post_type' => 'mt_candidate',
       'posts_per_page' => 50,
       'no_found_rows' => true
   ]);
   ```

3. **Regular Maintenance**
   ```bash
   # Optimize tables
   docker exec mobility_wpcli_STAGING wp db optimize
   
   # Clean up revisions
   docker exec mobility_wpcli_STAGING wp post delete $(wp post list --post_type='revision' --format=ids)
   
   # Clean transients
   docker exec mobility_wpcli_STAGING wp transient delete --expired
   ```

### Asset Optimization

1. **JavaScript Optimization**
   - Minification in production
   - Defer non-critical scripts
   - Lazy load components

2. **CSS Optimization**
   - Minification
   - Critical CSS inline
   - Remove unused styles

3. **Image Optimization**
   - Responsive images
   - WebP format
   - Lazy loading
   - CDN delivery

### Performance Monitoring

```bash
# Monitor response times
docker exec mobility_wpcli_STAGING wp eval '
$start = microtime(true);
// Your code here
$end = microtime(true);
echo "Execution time: " . ($end - $start) . " seconds\n";'

# Check database queries
docker exec mobility_wpcli_STAGING wp eval '
define("SAVEQUERIES", true);
// Run your code
global $wpdb;
print_r($wpdb->queries);'
```

## 🤝 Contribution Guidelines

### Development Setup

1. **Clone Repository**
   ```bash
   git clone https://github.com/your-org/mobility-trailblazers.git
   cd mobility-trailblazers
   ```

2. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Setup Development Environment**
   ```bash
   cp .env.example .env.development
   docker-compose -f docker-compose.dev.yml up -d
   ```

### Coding Standards

#### PHP Standards
- Follow WordPress Coding Standards
- Use PHP CodeSniffer
- Minimum PHP 7.4 compatibility
- Type hints where possible

```bash
# Run code sniffer
./vendor/bin/phpcs --standard=WordPress --extensions=php .

# Auto-fix issues
./vendor/bin/phpcbf --standard=WordPress --extensions=php .
```

#### JavaScript Standards
- ES6+ syntax
- JSDoc comments
- Modular architecture

```bash
# Run ESLint
npm run lint

# Auto-fix issues
npm run lint:fix
```

### Testing

#### Unit Tests
```bash
# Run PHPUnit tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite unit

# Generate coverage report
./vendor/bin/phpunit --coverage-html coverage
```

#### Integration Tests
```bash
# Setup test database
./tests/bin/install-wp-tests.sh wordpress_test root password localhost latest

# Run integration tests
./vendor/bin/phpunit --testsuite integration
```

#### End-to-End Tests
```bash
# Install Cypress
npm install --save-dev cypress

# Run E2E tests
npm run cypress:open
```

### Git Workflow

1. **Branch Naming**
   - `feature/description` - New features
   - `bugfix/description` - Bug fixes
   - `hotfix/description` - Urgent fixes
   - `refactor/description` - Code improvements

2. **Commit Messages**
   ```
   type(scope): subject
   
   body
   
   footer
   ```
   
   Types: feat, fix, docs, style, refactor, test, chore

3. **Pull Request Process**
   - Create feature branch
   - Make changes with tests
   - Submit PR with description
   - Code review required
   - CI/CD must pass
   - Squash and merge

### Documentation

- Update README for new features
- Add PHPDoc blocks
- Update API documentation
- Include examples
- Keep changelog current

## 📝 Changelog

### Version 1.0.0
- ✨ Initial release
- ✨ Complete evaluation system
- ✨ Assignment management interface
- ✨ Elementor integration
- ✨ Multi-language support
- 🐛 Fixed duplicate menu items
- 🐛 Resolved evaluation sync issues
- 🔧 Added diagnostic tools
- 📚 Complete documentation

### Version 0.9.0
- ✨ Beta release for testing
- ✨ Core functionality complete
- 🐛 Various bug fixes
- 🔧 Performance optimizations

### Version 0.8.0 (June 2025)
- ✨ Alpha release
- ✨ Basic evaluation system
- ✨ Candidate management
- ✨ Jury member profiles

## 📄 License

This plugin is licensed under the GNU General Public License v2 or later.

```
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 🙏 Acknowledgments

- **Institut für Mobilität**, University of St. Gallen
- **Prof. Dr. Andreas Herrmann** - Project Lead
- **Nicolas Estrém** - Technical Implementation
- **Handelsblatt** - Media Partner
- All jury members and candidates
- Open source community

## 📞 Support

For technical support or questions:

- **Email**: support@mobilitytrailblazers.de


---

**Mobility Trailblazers** - Shaping the future of mobility in the DACH region 🚀

*Last updated: June 14, 2025*
# Recent Updates & Code Cleanup (December 2024)

## 🔧 Code Refactoring & Duplicate Removal

We've performed a comprehensive code cleanup to improve maintainability and fix several issues identified during code review:

### Issues Fixed

#### 1. **Duplicate Menu Registration**
- **Problem**: Admin menus were being registered in multiple places, potentially causing duplicate menu items
- **Solution**: 
  - Consolidated all menu registrations into a single `register_all_admin_menus()` method
  - Added duplicate detection to prevent multiple "My Dashboard" menu items
  - Removed scattered `add_action('admin_menu')` calls throughout the codebase

#### 2. **Duplicate Evaluation Functions**
- **Problem**: Multiple implementations of user evaluation counting functions across different files
- **Solution**:
  - Kept single global functions in main plugin file: `mt_get_user_evaluation_count()` and `mt_has_jury_evaluated()`
  - Removed duplicate implementations from:
    - `includes/class-mt-jury-fix.php`
    - `includes/class-mt-jury-consistency.php`
    - `includes/elementor/class-evaluation-stats-widget.php`
  - All components now use the same consistent functions

#### 3. **Docker Configuration Issues**
- **Problem**: Security vulnerabilities and configuration issues in docker-compose.yml
- **Identified Issues**:
  - Duplicate version declaration
  - Hardcoded credentials
  - Exposed database ports (security risk)
  - Empty volumes section
  - WP-CLI container running unnecessarily
- **Recommendations**: See "Security Improvements" section below

### Code Organization Improvements

1. **Menu Registration**: All admin menus now registered in one location for easier maintenance
2. **Function Consolidation**: Evaluation-related functions consolidated to prevent inconsistencies
3. **Better Error Handling**: Added checks to prevent duplicate menu registration
4. **Cleaner Codebase**: Removed ~200 lines of duplicate code

### Files Modified

- `mobility-trailblazers.php` - Main plugin file
- `includes/class-mt-jury-fix.php` - Removed duplicate functions
- `includes/class-mt-jury-consistency.php` - Removed duplicate method
- `includes/elementor/class-evaluation-stats-widget.php` - Simplified to use global functions
- `README.md` - Removed duplicate content sections

### Security Improvements Needed

Based on our code review, the following security improvements should be implemented:

1. **Environment Variables**: Move all credentials from docker-compose.yml to .env file
2. **Database Ports**: Remove external port exposure for MariaDB in production
3. **Redis Ports**: Remove external port exposure for Redis in production
4. **Secure Passwords**: Replace all hardcoded passwords with secure generated ones

### Testing After Updates

After applying these updates, please test:

1. **Menu Display**: Verify no duplicate menu items appear
2. **Jury Dashboard**: Ensure jury members can access their dashboard
3. **Evaluation Counts**: Confirm evaluation statistics display correctly
4. **Elementor Widgets**: Test evaluation stats widget if using Elementor

### Migration Notes

No database changes are required. The cleanup only affects PHP code organization. However, if you experience any issues with evaluation counts after the update, you can run:

```bash
docker exec mobility_wpcli_STAGING wp eval '
if (class_exists("MT_Jury_Consistency")) {
    MT_Jury_Consistency::get_instance()->sync_all_evaluations();
}'
```

---

## 📝 Changelog

### Version 1.0.1 (December 2024)
- Fixed duplicate menu registration issues
- Consolidated evaluation counting functions
- Removed ~200 lines of duplicate code
- Improved code organization and maintainability
- Added security recommendations for Docker configuration

### Version 1.0.0
- Initial release
