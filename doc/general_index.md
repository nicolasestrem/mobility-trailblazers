# Mobility Trailblazers Platform - General Index

## 📁 Root Files

- **mobility-trailblazers.php** - Main WordPress plugin file that initializes the platform and defines constants
- **README.md** - Complete documentation with features, installation guide, and development workflow
- **composer.json** - PHP dependency management for development tools and code standards
- **.gitignore** - Defines which files to exclude from version control
- **.gitmodules** - Git submodule configuration for the plugin repository
- **CODING_INSTRUCTIONS** - Development standards and workflow guidelines for the project

### Photo Management Scripts (v2.4.0)
- **match-photos.php** - Original photo matching script with basic name matching
- **match-photos-updated.php** - Enhanced photo matching with improved name variation handling
- **direct-photo-attach.php** - Direct ID-based photo attachment for initial candidates
- **direct-photo-attach-complete.php** - Complete photo attachment for all 52 candidates
- **verify-photo-matching.php** - Verification tool for photo attachment status

## 📁 /includes/ - Core PHP Classes

### Core System Files
- **class-mt-autoloader.php** - PSR-4 autoloader for automatic class loading
- **class-mt-plugin.php** - Main plugin initialization and component registration
- **class-mt-activator.php** - Handles plugin activation setup and database creation
- **class-mt-deactivator.php** - Manages plugin deactivation cleanup tasks
- **class-mt-uninstaller.php** - Handles plugin uninstall with optional data preservation (v2.2.13)

### Administrative Interface
- **admin/class-mt-admin.php** - Admin menu setup and page rendering management (v2.3.0 - added Debug Center integration)
- **admin/class-mt-admin-notices.php** - System for displaying admin notifications
- **admin/class-mt-candidate-columns.php** - Custom admin columns and CSV import/export for candidates (v2.2.25 - uses MT_Import_Handler)
- **admin/class-mt-import-export.php** - Comprehensive import/export handler for candidates and jury members (v2.2.25 - uses MT_Import_Handler)
- **admin/class-mt-import-handler.php** - Centralized CSV processing with BOM handling and delimiter detection (v2.2.28 - enhanced CSV parsing)
- **admin/class-mt-error-monitor.php** - Error monitoring and logging system
- **admin/class-mt-debug-manager.php** - Debug script execution management with environment controls (v2.3.0 - complete)
- **admin/class-mt-maintenance-tools.php** - System maintenance operations and database tools (v2.3.0 - complete)
- **admin/class-mt-coaching.php** - Jury coaching dashboard for evaluation management (v2.2.29)

### AJAX Handlers (v2.3.0 - Added Debug Center support)
- **ajax/class-mt-base-ajax.php** - Base class with centralized error logging, response handling, and file validation (v2.2.28 - added validate_upload())
- **ajax/class-mt-evaluation-ajax.php** - Handles evaluation form submissions with enhanced security checks (v2.2.28 - secured debug endpoints)
- **ajax/class-mt-assignment-ajax.php** - Manages jury-candidate assignment operations with consistent error handling
- **ajax/class-mt-admin-ajax.php** - General admin AJAX operations with unified error logging
- **ajax/class-mt-import-ajax.php** - Quick CSV import handler using enhanced validation (v2.2.28 - uses validate_upload())
- **ajax/class-mt-csv-import-ajax.php** - Comprehensive CSV import with progress tracking (v2.2.24)
- **ajax/class-mt-debug-ajax.php** - Debug Center AJAX operations (diagnostics, scripts, maintenance, delete all candidates) (v2.3.5)

### Business Logic Services (v2.3.0 - Added diagnostic service)
- **services/class-mt-evaluation-service.php** - Core evaluation processing and calculation
- **services/class-mt-assignment-service.php** - Assignment creation and management logic (v2.2.29 - added rebalancing)
- **services/class-mt-import-service.php** - Data import processing and validation
- **services/class-mt-diagnostic-service.php** - Comprehensive system health monitoring and diagnostics (v2.3.0 - complete)

### Data Access Layer
- **repositories/class-mt-evaluation-repository.php** - Database operations for evaluations
- **repositories/class-mt-assignment-repository.php** - Database operations for assignments (v2.2.28 - added integrity methods)
- **repositories/class-mt-candidate-repository.php** - Candidate data management
- **repositories/class-mt-audit-log-repository.php** - Audit log data access and filtering

### Core Components
- **core/class-mt-database.php** - Database table creation and management
- **core/class-mt-database-upgrade.php** - Handles database schema updates
- **core/class-mt-logger.php** - Error logging and debugging system
- **core/class-mt-plugin.php** - Main plugin initialization (v2.3.0 - added Debug Center loading)
- **core/class-mt-post-types.php** - Registers candidate and jury member post types
- **core/class-mt-taxonomies.php** - Category and tag taxonomy registration
- **core/class-mt-roles.php** - User role and capability management
- **core/class-mt-shortcodes.php** - Platform shortcode implementations
- **core/class-mt-i18n.php** - Internationalization and translation setup
- **core/class-mt-audit-logger.php** - Centralized audit logging for security compliance

### Utility Classes (v2.3.0 - New)
- **utilities/class-mt-database-health.php** - Database health monitoring and analysis
- **utilities/class-mt-system-info.php** - System information gathering and reporting

## 📁 /templates/ - Display Templates

### Admin Templates (v2.3.0 - Added Debug Center)
- **admin/dashboard.php** - Main admin dashboard with statistics
- **admin/evaluations.php** - Evaluation review and management
- **admin/assignments.php** - Jury assignment management page
- **admin/import-export.php** - Data import/export interface
- **admin/settings.php** - Plugin configuration settings including data management options (v2.2.13)
- **admin/diagnostics.php** - System health and debugging tools (deprecated v2.3.0 - redirects to Debug Center)
- **admin/tools.php** - Maintenance tools interface (deprecated v2.3.0 - redirects to Debug Center)
- **admin/error-monitor.php** - Error log viewer and monitoring
- **admin/import-profiles.php** - Profile import wizard interface
- **admin/audit-log.php** - Security audit log viewer with filtering and pagination
- **admin/coaching.php** - Jury coaching dashboard for evaluation tracking (v2.2.29)
- **admin/debug-center.php** - Unified developer tools interface (v2.3.0 - complete)
- **admin/debug-center/tab-diagnostics.php** - System diagnostics tab with health monitoring (v2.3.0 - complete)
- **admin/debug-center/tab-database.php** - Database tools and optimization tab (v2.3.0 - complete)
- **admin/debug-center/tab-scripts.php** - Debug script execution interface (v2.3.0 - complete)
- **admin/debug-center/tab-errors.php** - Error log monitoring and management (v2.3.0 - complete)
- **admin/debug-center/tab-tools.php** - Maintenance operations interface (v2.3.0 - complete)
- **admin/debug-center/tab-info.php** - System information display (v2.3.0 - complete)
- **admin/migrate-profiles.php** - Migration tool for candidate profiles (v2.3.1)
- **admin/generate-samples.php** - Sample data generator for testing (v2.3.1)

### Frontend Templates
- **frontend/jury-dashboard.php** - Main jury member evaluation interface
- **frontend/jury-evaluation-form.php** - Individual candidate evaluation form
- **frontend/candidate-profile.php** - Public candidate profile display
- **frontend/voting-form.php** - Public voting interface
- **frontend/rankings.php** - Display of current candidate rankings
- **frontend/candidates-grid.php** - Basic candidates grid display (v2.0.1)
- **frontend/candidates-grid-enhanced.php** - Modern card-based grid with filtering and search (v2.4.0)
- **frontend/single/single-mt_candidate.php** - Enhanced candidate profile with hero section and criteria cards (v2.2.0, enhanced v2.4.0)
- **frontend/single/single-mt_candidate-backup.php** - Original candidate template backup (v2.1.0)
- **frontend/single/single-mt_jury.php** - Jury member profile with statistics dashboard (v2.4.0)

## 📁 /assets/ - Static Resources

### Stylesheets (v2.3.0 - Added Debug Center styles)
- **css/admin.css** - Admin interface with widget loading states (v2.2.28 - added .mt-widget-loading)
- **css/frontend.css** - Public-facing interface styles
- **css/jury-dashboard.css** - Specific styling for jury evaluation interface
- **css/csv-import.css** - Progress modal and import UI styles (v2.2.24)
- **css/debug-center.css** - Professional Debug Center styling with responsive design (v2.3.0)

### JavaScript (v2.4.0 - Added interactive features)
- **js/admin.js** - Admin functionality with event delegation and widget refresh functions (v2.2.28)
- **js/frontend.js** - Public interface with mt_ajax fallback initialization (v2.2.28)
- **js/jury-evaluation.js** - Evaluation form validation and submission
- **js/charts.js** - Data visualization for statistics and progress
- **js/candidate-import.js** - AJAX-based CSV import with file picker dialog (v2.2.16)
- **js/csv-import.js** - Complete CSV import module with progress tracking (v2.2.24)
- **js/debug-center.js** - Debug Center interactive functionality with AJAX operations (v2.3.0)
- **js/candidate-interactions.js** - Interactive features for candidate profiles and grids (v2.4.0)

### Images
- **images/logo.png** - Platform logo and branding assets
- **images/icons/** - Interface icons and UI elements

### Data Files
- **sample-candidates.csv** - Example CSV file for candidate import (v2.2.15 - German format)

## 📁 /data/ - Data Templates and Files (v2.2.24)

### CSV Templates
- **templates/candidates.csv** - Template for candidate imports with German headers
- **templates/jury-members.csv** - Template for jury member imports
- **templates/jury_members.csv** - Alternative naming for compatibility

## 📁 /languages/ - Internationalization

- **mobility-trailblazers.pot** - Translation template file
- **mobility-trailblazers-de_DE.po** - German translations
- **mobility-trailblazers-de_DE.mo** - Compiled German translations

## 📁 /doc/ - Documentation (v2.3.2 - Added troubleshooting guide)

### Developer Documentation
- **developer-guide.md** - Complete development guide with architecture (v2.3.0 - updated with Debug Center)
- **changelog.md** - Version history and release notes (v2.3.2 - script output fixes)
- **general_index.md** - Comprehensive file and component index (v2.3.2 - updated)
- **debug-center-guide.md** - Complete Debug Center documentation and usage guide (v2.3.2 - added known issues)
- **debug-center-troubleshooting.md** - Troubleshooting guide for Debug Center issues (v2.3.2 - new)

### Feature Documentation
- **csv-import-guide.md** - CSV import functionality documentation
- **ajax-csv-import-guide.md** - AJAX-based CSV import guide
- **photo-integration-guide.md** - Complete photo management and UI enhancement documentation (v2.4.0)
- **import-consolidation-v2.2.25.md** - Import system consolidation documentation
- **debug-plan-10min.md** - Debug tools planning document

## 📁 /tools/ - Utility Scripts (v2.4.0)

- **parse-criteria.php** - Parse and structure evaluation criteria into individual meta fields

## 📁 /debug/ - Development Tools (v2.3.0 - Reorganized)

### Script Organization
- **registry.json** - Script metadata and environment controls (v2.3.0)
- **README.md** - Debug script usage guidelines and warnings

### Categorized Scripts (v2.3.0)
- **generators/** - Test data generation scripts
  - **fake-candidates-generator.php** - Creates test candidate data
  - **generate-sample-profiles.php** - Sample profile generation
  
- **migrations/** - Data migration scripts
  - **migrate-candidate-profiles.php** - Profile structure migration
  - **migrate-jury-posts.php** - Jury member post migration
  
- **diagnostics/** - System diagnostic scripts
  - **check-jury-status.php** - Jury status verification
  - **test-db-connection.php** - Database connectivity testing
  - **check-schneidewind-import.php** - Specific import verification
  - **performance-test.php** - Performance benchmarking
  
- **repairs/** - Data repair utilities
  - **fix-database.php** - Database structure repairs
  - **fix-assignments.php** - Assignment data fixes
  
- **deprecated/** - Old scripts for reference only
  - **test-regex-debug.php** - Outdated regex patterns
  - **fix-existing-evaluations.php** - Old evaluation fixes
  - **direct-fix-evaluations.php** - Legacy evaluation repair
  - **final-fix-evaluations.php** - Legacy evaluation repair
  - **test-evaluation-parsing.php** - Old parsing logic

### Standalone Scripts
- **jury-import.php** - Jury member import utility
- **test-import-handler.php** - Import functionality testing
- **test-profile-system.php** - Profile system testing

## 📁 /infrastructure/ - Deployment Configuration

### Docker Setup
- **docker/docker-compose.yml** - Container orchestration configuration
- **docker/Dockerfile** - WordPress container definition
- **docker/nginx/default.conf** - Web server configuration

### Komodo Management
- **komodo/stacks.json** - Stack management configuration
- **komodo/deployment.yml** - Deployment automation settings

## 📁 External Folders

### Documentation (../../Documentation/)
- **Project-Management/** - Business documents and stakeholder reports
- **Technical/** - Architecture diagrams and technical specifications
- **User-Manual/** - End-user guides and training materials

### Backups (../../Backups/)
- **Database/** - Regular database backup files
- **Code/** - Version snapshots and rollback points

### Assets (../../Assets/)
- **Templates/** - Communication materials and content templates  
- **Branding/** - Logo files and brand guidelines

## 🔑 Key Integration Points

1. **WordPress Hooks** - Plugin integrates via standard WP action/filter system
2. **AJAX Endpoints** - All AJAX calls route through wp-admin/admin-ajax.php
3. **Database Tables** - Custom tables prefixed with 'mt_' for data isolation (includes audit logging)
4. **User Roles** - Extends WordPress roles with custom capabilities
5. **Shortcodes** - [mt_jury_dashboard], [mt_voting_form], [mt_rankings]

## 🏗️ Architecture Pattern

The platform follows a **Repository-Service-Controller** pattern:
- **Controllers** (AJAX handlers) - Handle requests and responses
- **Services** - Contain business logic and validation
- **Repositories** - Manage database operations
- **Templates** - Present data to users

## 🔒 Security Features

- Nonce verification on all forms and AJAX calls
- Capability checking for user permissions with role-based access control
- Input sanitization using WordPress functions
- SQL injection prevention via prepared statements
- XSS protection through output escaping
- Comprehensive audit logging for all critical actions
- Full traceability with before/after state tracking

## 📊 Recent Updates (v2.2.10 - v2.2.12)

### Dashboard Enhancements
- **Widget Synchronization** - Dashboard widget now shows real-time evaluation statistics
- **Recent Evaluations** - Widget displays latest 5 evaluations with jury → candidate mapping
- **Consistent Data Sources** - Both main dashboard and widget use same repository methods

### Security & Compliance
- **Extended Audit Logging** - All bulk operations and status changes now logged
- **Assignment Tracking** - Full context captured before deletion including names
- **User Attribution** - All actions tracked with performing user ID

### Code Quality
- **Method Consolidation** - Removed duplicate assignment removal methods
- **Database Integrity** - Verified assigned_by field population in all operations
- **Standardized Permissions** - Consistent capability checks across all AJAX handlers

### User Roles
- **Jury Admin Role** - Intermediate role with assignment management capabilities
- **Granular Permissions** - Fine-tuned capabilities for different user types
- **Delegation Support** - Admins can delegate specific tasks without full access

### Data Management (v2.2.13)
- **Uninstall Options** - Administrator control over data preservation vs deletion
- **Settings Protection** - Strong visual warnings for destructive operations
- **Complete Data Removal** - Optional removal of all plugin data on uninstall

### Error Handling (v2.2.13)
- **Standardized AJAX Responses** - All handlers use base class error/success methods
- **Centralized Logging** - Automatic error logging with context via MT_Logger
- **Consistent Format** - Uniform error response structure across platform

## 📊 Recent Updates (v2.2.15 - v2.2.16)

### CSV Import System (v2.2.16)
- **AJAX-Based Import** - JavaScript file picker with real-time progress overlay
- **German Text Parsing** - Regex extraction of evaluation criteria from Description field
- **Enhanced Security** - MIME type validation, file size limits, comprehensive logging
- **Field Mapping** - Exact CSV column to meta field mapping with German support

### Candidate Management (v2.2.15)
- **Menu Consolidation** - Removed duplicate candidates admin page
- **Custom Columns** - Import ID, Organization, Category with icons, Top 50 status
- **Sortable Lists** - All custom columns support sorting
- **Export Enhancement** - Full UTF-8 support with BOM for Excel

## 📊 Recent Updates (v2.2.25)

### Import System Consolidation
- **Reduced Complexity** - Consolidated from 7 import files to 4 with clear separation
- **Single Source of Truth** - All import logic now in MT_Import_Handler class
- **Removed Duplicates** - Deleted unused MT_Profile_Importer and MT_Enhanced_Profile_Importer
- **Method Migration** - Moved parse_evaluation_criteria() to MT_Import_Handler
- **Updated References** - All import operations now use consolidated handler
- **Cleaner Architecture** - Each file has specific purpose without overlap

## 📊 Recent Updates (v2.2.24)

### Complete CSV Import System
- **AJAX Import Handler** - Real-time progress tracking with visual feedback
- **Import Handler Class** - Centralized CSV processing for all import types
- **Progress Modal UI** - Professional import interface with animated progress bars
- **Dual Import Methods** - Standard form and AJAX-based imports
- **Template System** - CSV templates with UTF-8 BOM for Excel compatibility
- **Jury Member Import** - Full support for importing jury members with user creation
- **Error Handling** - Row-by-row error reporting with detailed messages
- **File Validation** - Pre-upload validation for size, type, and format

---
*Last Updated: August 2025 | Version 2.2.25*