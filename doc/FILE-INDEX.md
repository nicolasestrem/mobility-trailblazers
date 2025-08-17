# Mobility Trailblazers - Complete File Index
*Last Updated: August 17, 2025 | Version 2.5.0*

## Table of Contents
1. [Root Directory](#root-directory)
2. [Core PHP Classes (/includes/)](#core-php-classes-includes)
3. [Templates (/templates/)](#templates-templates)
4. [Assets (/assets/)](#assets-assets)
5. [Debug Tools (/debug/)](#debug-tools-debug)
6. [Data Files (/data/)](#data-files-data)
7. [Tools & Utilities (/tools/)](#tools--utilities-tools)
8. [Photos (/Photos_candidates/)](#photos-photos_candidates)
9. [Documentation (/doc/)](#documentation-doc)
10. [Infrastructure](#infrastructure)

---

## Root Directory

### Main Plugin Files
- **mobility-trailblazers.php** - Main WordPress plugin file, initializes platform
- **uninstall.php** - Handles plugin uninstallation with data preservation options
- **README.md** - Complete documentation with installation and features
- **CHANGELOG.md** - Version history and release notes
- **composer.json** - PHP dependency management
- **.gitignore** - Version control exclusions
- **.distignore** - Distribution exclusions for releases

### Photo Management Scripts
- **match-photos.php** - Original photo matching with basic name matching
- **match-photos-updated.php** - Enhanced matching with name variations
- **direct-photo-attach.php** - Direct ID-based attachment (initial)
- **direct-photo-attach-complete.php** - Complete attachment for all 52 candidates
- **verify-photo-matching.php** - Verification tool for photo status

---

## Core PHP Classes (/includes/)

### /includes/core/
System initialization and setup:
- **class-mt-autoloader.php** - PSR-4 autoloader
- **class-mt-plugin.php** - Main plugin initialization
- **class-mt-activator.php** - Plugin activation and database setup
- **class-mt-deactivator.php** - Deactivation cleanup
- **class-mt-uninstaller.php** - Data removal with preservation options
- **class-mt-database.php** - Database table management
- **class-mt-database-upgrade.php** - Schema updates
- **class-mt-logger.php** - Error logging system
- **class-mt-post-types.php** - Custom post type registration
- **class-mt-taxonomies.php** - Taxonomy registration
- **class-mt-roles.php** - User roles and capabilities
- **class-mt-shortcodes.php** - Shortcode implementations
- **class-mt-i18n.php** - Internationalization
- **class-mt-audit-logger.php** - Security audit logging

### /includes/admin/
Administrative interface:
- **class-mt-admin.php** - Admin menu and page rendering
- **class-mt-admin-notices.php** - Admin notifications
- **class-mt-candidate-columns.php** - Custom columns and import
- **class-mt-import-export.php** - Import/export handler
- **class-mt-import-handler.php** - Centralized CSV processing
- **class-mt-error-monitor.php** - Error monitoring system
- **class-mt-debug-manager.php** - Debug script management
- **class-mt-maintenance-tools.php** - System maintenance
- **class-mt-coaching.php** - Jury coaching dashboard

### /includes/ajax/
AJAX handlers:
- **class-mt-base-ajax.php** - Base class with security
- **class-mt-evaluation-ajax.php** - Evaluation submissions
- **class-mt-assignment-ajax.php** - Assignment operations
- **class-mt-admin-ajax.php** - General admin operations
- **class-mt-import-ajax.php** - Quick CSV import
- **class-mt-csv-import-ajax.php** - Progress import
- **class-mt-debug-ajax.php** - Debug Center operations

### /includes/services/
Business logic:
- **class-mt-evaluation-service.php** - Evaluation processing
- **class-mt-assignment-service.php** - Assignment logic
- **class-mt-import-service.php** - Import processing
- **class-mt-diagnostic-service.php** - System diagnostics

### /includes/repositories/
Data access layer:
- **class-mt-evaluation-repository.php** - Evaluation database ops
- **class-mt-assignment-repository.php** - Assignment database ops
- **class-mt-candidate-repository.php** - Candidate management
- **class-mt-audit-log-repository.php** - Audit log access

### /includes/utilities/
Utility classes:
- **class-mt-database-health.php** - Database monitoring
- **class-mt-system-info.php** - System information

---

## Templates (/templates/)

### /templates/admin/
Administrative templates:
- **assignments.php** - Assignment management interface (Updated 2.5.7)
- **assignments-modals.php** - New modal implementation for assignments (Added 2.5.7)

### /templates/admin/
Admin interface templates:
- **dashboard.php** - Main dashboard
- **dashboard-widget.php** - Dashboard widget
- **evaluations.php** - Evaluation management
- **assignments.php** - Assignment management
- **import-export.php** - Import/export interface
- **import-profiles.php** - Profile import wizard
- **settings.php** - Plugin settings
- **audit-log.php** - Audit log viewer
- **coaching.php** - Coaching dashboard
- **debug-center.php** - Debug Center main
- **migrate-profiles.php** - Migration tool
- **generate-samples.php** - Sample generator

### /templates/admin/debug-center/
Debug Center tabs:
- **tab-diagnostics.php** - System diagnostics
- **tab-database.php** - Database tools
- **tab-scripts.php** - Script runner
- **tab-errors.php** - Error monitor
- **tab-tools.php** - Maintenance tools
- **tab-info.php** - System information

### /templates/frontend/
Public templates:
- **jury-dashboard.php** - Jury evaluation interface
- **jury-evaluation-form.php** - Evaluation form
- **candidate-profile.php** - Candidate display
- **voting-form.php** - Voting interface
- **rankings.php** - Rankings display
- **candidates-grid.php** - Basic grid
- **candidates-grid-enhanced.php** - Modern grid

### /templates/frontend/single/
Single post templates:
- **single-mt_candidate.php** - Enhanced candidate profile
- **single-mt_candidate-backup.php** - Original backup
- **single-mt_jury.php** - Jury member profile

---

## Assets (/assets/)

### /assets/css/
Stylesheets:
- **admin.css** - Admin interface styles
- **frontend.css** - Public interface styles
- **jury-dashboard.css** - Jury interface styles
- **csv-import.css** - Import UI styles
- **debug-center.css** - Debug Center styles
- **enhanced-candidate-profile.css** - Enhanced candidate profile styling
- **candidate-profile-fixes.css** - Layout and spacing fixes (v2.4.2)
- **design-improvements-2025.css** - Comprehensive UI/UX improvements (v2.5.0)
- **mt-modal-fix.css** - Modal positioning and visibility fixes (v2.5.7)

### /assets/js/
JavaScript files:
- **admin.js** - Admin functionality
- **frontend.js** - Public interface
- **jury-evaluation.js** - Evaluation forms
- **charts.js** - Data visualization
- **candidate-import.js** - AJAX CSV import
- **csv-import.js** - Progress import
- **debug-center.js** - Debug Center UI
- **candidate-interactions.js** - Interactive features
- **design-enhancements.js** - Animations and interactive enhancements (v2.5.0)
- **mt-assignments.js** - Enhanced assignment modal handling (v2.5.7)
- **mt-modal-force.js** - Force modal visibility script (v2.5.7)
- **mt-modal-debug.js** - Modal debugging utilities (v2.5.7)

### /assets/images/
Image files:
- **logo.png** - Platform logo
- **icons/** - UI icons directory

### /assets/sample-candidates.csv
Sample CSV file for imports

---

## Debug Tools (/debug/)

### Configuration Files
- **registry.json** - Script metadata and controls
- **README.md** - Debug script guidelines

### /debug/generators/
Test data generation:
- **fake-candidates-generator.php** - Test candidates
- **generate-sample-profiles.php** - Sample profiles

### /debug/migrations/
Data migrations:
- **migrate-candidate-profiles.php** - Profile migration
- **migrate-jury-posts.php** - Jury migration

### /debug/diagnostics/
System checks:
- **check-jury-status.php** - Jury verification
- **test-db-connection.php** - Database testing
- **check-schneidewind-import.php** - Import check
- **performance-test.php** - Performance benchmarks

### /debug/repairs/
Fix utilities:
- **fix-database.php** - Database repairs
- **fix-assignments.php** - Assignment fixes

### /debug/deprecated/
Old scripts (reference only):
- **test-regex-debug.php** - Regex patterns
- **fix-existing-evaluations.php** - Legacy fixes
- **direct-fix-evaluations.php** - Old repairs
- **final-fix-evaluations.php** - Final fixes
- **test-evaluation-parsing.php** - Parsing tests

### Standalone Debug Scripts
- **jury-import.php** - Jury import utility
- **test-import-handler.php** - Import testing
- **test-profile-system.php** - Profile testing

---

## Data Files (/data/)

### /data/templates/
CSV templates:
- **candidates.csv** - Candidate import template
- **jury-members.csv** - Jury import template
- **jury_members.csv** - Alternative naming

---

## Tools & Utilities (/tools/)

### Conversion Tools
- **excel-to-csv-converter.html** - Browser-based Excel converter
- **excel-to-csv-converter.php** - PHP Excel converter
- **parse-criteria.php** - Parse evaluation criteria

---

## Photos (/Photos_candidates/)

### /Photos_candidates/webp/
52 candidate photos in WebP format:
- AlexanderMöller.webp
- AndreasHerrmann.webp
- AndreasKnie.webp
- ... (49 more photos)
- GüntherSchuh.webp

### Mapping File
- **mobility_trailblazers_candidates.csv** - Photo-to-candidate mapping

---

## Documentation (/doc/)

### New Consolidated Documentation (v2.4.1)
- **MASTER-DEVELOPER-GUIDE.md** - Complete technical reference
- **IMPORT-EXPORT-GUIDE.md** - Import/export procedures
- **DEBUG-CENTER-COMPLETE.md** - Debug Center documentation
- **UI-PHOTO-ENHANCEMENT-GUIDE.md** - UI/UX guide
- **PROJECT-STATUS.md** - Current status and timeline
- **FILE-INDEX.md** - This file
- **CHANGELOG.md** - Version history

### /doc/archive/
Original documentation files (archived):
- ajax-csv-import-guide.md
- candidate-photo-integration.md
- csv-import-guide.md
- debug-center-guide.md
- debug-center-technical.md
- debug-center-troubleshooting.md
- debug-plan-10min.md
- developer-guide.md
- developer-guide-v2.2.28.md
- excel-import-guide.md
- general_index.md
- import-consolidation-v2.2.25.md
- jury-grid-display-fix.md
- mt-debug-center-guide.md
- photo-integration-guide.md
- platform-timeline-august-2025.md
- refactoring-evaluations-js.md
- session-summary-2025-08-16.md

---

## Infrastructure

### Docker Configuration
- **docker/docker-compose.yml** - Container orchestration
- **docker/Dockerfile** - WordPress container
- **docker/nginx/default.conf** - Web server config

### Komodo Management
- **komodo/stacks.json** - Stack management
- **komodo/deployment.yml** - Deployment automation

---

## File Statistics

### Total Files by Type
- **PHP Classes**: 75+ files
- **Templates**: 35+ files
- **JavaScript**: 9 files
- **CSS**: 5 files
- **Documentation**: 7 consolidated (from 19)
- **Debug Scripts**: 20+ files
- **Photos**: 52 WebP images

### Lines of Code
- **PHP**: ~20,000 lines
- **JavaScript**: ~3,000 lines
- **CSS**: ~2,000 lines
- **Documentation**: ~5,000 lines

### Directory Structure Summary
```
mobility-trailblazers/
├── includes/           # PHP classes (75+ files)
├── templates/          # Display templates (35+ files)
├── assets/            # CSS, JS, images
├── debug/             # Debug scripts (20+ files)
├── data/              # Data templates
├── tools/             # Utility scripts
├── Photos_candidates/ # Candidate photos (52 files)
├── doc/               # Documentation (7 consolidated)
└── [root files]       # Plugin initialization
```

---

## Key Integration Files

### WordPress Hooks
- **mobility-trailblazers.php** - Action/filter registration
- **class-mt-plugin.php** - Hook initialization
- **class-mt-shortcodes.php** - Shortcode registration

### AJAX Endpoints
- All AJAX handlers in `/includes/ajax/`
- Routes through `wp-admin/admin-ajax.php`

### Database Tables
- Created by **class-mt-activator.php**
- Managed by **class-mt-database.php**
- Upgraded by **class-mt-database-upgrade.php**

### User Interface
- Admin styles: **admin.css**
- Frontend styles: **frontend.css**
- Admin scripts: **admin.js**
- Frontend scripts: **frontend.js**

---

## Version Control

### Git-Tracked
- All PHP, JS, CSS files
- Documentation
- Templates
- Configuration files

### Git-Ignored (.gitignore)
- `/vendor/` - Composer dependencies
- `/node_modules/` - NPM packages
- `*.log` - Log files
- `.env` - Environment variables
- `/cache/` - Cache files

### Distribution Excluded (.distignore)
- `/debug/` - Debug scripts
- `/tools/` - Development tools
- `*.md` - Documentation
- `.git*` - Git files
- Tests and development artifacts

---

*End of File Index*
