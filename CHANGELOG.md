# Changelog

All notable changes to the Mobility Trailblazers plugin will be documented in this file.

## [2.0.1] - 2025-01-14

### Changed
- **JavaScript Refactoring**: Moved evaluations page JavaScript from inline script to modular admin.js
  - Created new `MTEvaluationManager` object for better code organization
  - Removed inline `<script>` block from `templates/admin/evaluations.php`
  - Added conditional initialization to only load on evaluations page
  - Follows same modular pattern as `MTAssignmentManager`

### Technical Details
- Files modified:
  - `/assets/js/admin.js` - Added MTEvaluationManager module
  - `/templates/admin/evaluations.php` - Removed inline JavaScript
- Documentation: `/doc/refactoring-evaluations-js.md`

### Benefits
- Better code maintainability and organization
- JavaScript is now cacheable and minifiable
- Consistent code patterns across admin pages
- Easier debugging with modular structure

---

## [2.0.0] - Previous Release

### Features
- Jury evaluation system
- Assignment management
- Candidate management
- Public voting system
- Admin dashboard