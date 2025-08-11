# Mobility Trailblazers Changelog

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

## [2.2.0] - 2025-08-01

### Added
- Enhanced CSV Import System with intelligent field mapping
- Bilingual Support for English and German CSV headers
- Import Validation with dry-run mode and duplicate detection
- CSV Formatter Tool for data preparation

## Previous Versions
See README.md for earlier version history
