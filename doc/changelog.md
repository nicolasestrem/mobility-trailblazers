# Mobility Trailblazers Changelog

## [2.2.3] - 2025-08-12

### Fixed
- **Frontend JavaScript Scope Issue**: Fixed "getI18nText is not defined" error in Jury Rankings Table
  - Made `getI18nText` function globally accessible by attaching it to the window object
  - Resolved scope issue between multiple IIFE closures in frontend.js
  - Fixed vote modification arrows functionality in the evaluation table
  - Maintained backward compatibility with all existing code

### Technical Details
- The issue occurred because `getI18nText` was defined inside the first IIFE closure and wasn't accessible to the second IIFE that handles Jury Rankings Table interactions
- Solution: Attached the function to `window.getI18nText` making it globally available while keeping a local reference for the first closure
- Affected functionality: Vote adjustment arrows in the jury evaluation table were throwing console errors when clicked
- No functionality changes - only scope accessibility fix

## [2.2.2] - 2025-08-11

### Refactored
- **Admin JavaScript Module Architecture**: Restructured `assets/js/admin.js` for better maintainability and performance
  - Encapsulated assignment-specific functionality into dedicated `MTAssignmentManager` object
  - Implemented conditional loading - assignment modules only initialize on Assignment Management page
  - Consolidated multiple `$(document).ready()` calls into single main initialization
  - Separated general utilities from page-specific modules

### Improved  
- **Code Organization**:
  - Assignment Management logic now fully contained in `MTAssignmentManager` object with single `init()` entry point
  - Bulk Operations logic encapsulated in `MTBulkOperations` object
  - General utilities (tooltips, modals, tabs) remain globally available for all admin pages
  - Clear separation of concerns between different functional areas

- **Performance**:
  - Reduced memory footprint by only loading assignment-specific code when needed
  - Eliminated potential conflicts from global scope pollution
  - Faster page loads on non-assignment admin pages

- **Maintainability**:
  - Single source of truth for assignment page detection logic
  - Easier debugging with modular structure
  - Better code reusability and testability
  - Consistent initialization pattern across all modules

### Technical Details
- Assignment page detection checks for multiple indicators:
  - Presence of `#mt-auto-assign-btn` button
  - Existence of `.mt-assignments-table` element  
  - `.mt-assignment-management` wrapper class
  - Body class `mobility-trailblazers_page_mt-assignment-management`
  - URL containing "mt-assignment-management"
- No functionality changes - pure refactoring for code quality
- Maintains backward compatibility with all existing features
- Preserves all event bindings and AJAX interactions

## [2.2.1] - 2025-08-11

### Fixed
- **Auto-Assignment Algorithm Refactoring**: Complete rewrite of the auto-assignment functionality in `class-mt-assignment-ajax.php`
  - Fixed "Balanced" distribution logic to ensure fair and even distribution of candidates across jury members
  - Fixed "Random" distribution to be truly random and more efficient
  - Improved performance by eliminating redundant shuffling operations

### Changed
- **Balanced Distribution Method**:
  - Now tracks assignment counts for each candidate to ensure even review coverage
  - Prioritizes candidates with fewer existing assignments
  - Each jury member receives exactly the specified number of candidates
  - Ensures all candidates get roughly equal number of reviews

- **Random Distribution Method**:
  - Implements true randomization by shuffling candidate list once at the beginning
  - Each jury member randomly selects from the pre-shuffled list
  - Significantly improved performance (O(n) instead of O(n²))
  - Properly respects the candidates_per_jury limit

### Improved
- **Edge Case Handling**:
  - Better handling of scenarios with insufficient candidates
  - Proper tracking of existing assignments when not clearing
  - Clear warning messages when jury members cannot receive full allocation
  
- **Debugging and Logging**:
  - Added detailed logging at key decision points
  - Logs distribution method, candidate/jury counts, and assignment progress
  - Warning logs for edge cases and insufficient candidates
  - Final statistics logging for troubleshooting

### Technical Details
- Maintained all existing security checks (nonce verification, capability checks)
- Preserved backward compatibility with existing AJAX endpoints
- No database schema changes required
- Code follows WordPress coding standards and plugin conventions
- Replaced direct SQL queries with repository methods for better maintainability

## [2.2.0] - 2025-08-01

### Added
- Enhanced CSV Import System with intelligent field mapping
- Bilingual Support for English and German CSV headers
- Import Validation with dry-run mode and duplicate detection
- CSV Formatter Tool for data preparation

## Previous Versions
See README.md for earlier version history
