# Mobility Trailblazers Plugin Changelog

## Recent Updates

### Core Class Structure Improvements
- Added proper property declarations in `MobilityTrailblazersPlugin` class to fix PHP 8.2+ dynamic property deprecation warnings
- Implemented core classes with proper namespacing:
  - `Evaluation` class for handling candidate evaluations
  - `JuryMember` class for managing jury member functionality
  - `Candidate` class for candidate data management
  - `Statistics` class for gathering and displaying statistics

### Jury Dashboard Enhancements
- Improved shortcode handler with proper attribute handling:
  - Added default values for all attributes
  - Converted string 'yes'/'no' values to proper booleans
  - Added proper type hints and documentation
- Enhanced jury dashboard template:
  - Added proper escaping for all output
  - Improved error handling and user checks
  - Added proper data fetching using core classes
  - Implemented filtering and pagination support
  - Added support for multiple display options:
    - Statistics display
    - Assigned candidates list
    - Evaluation progress
    - Public voting results
    - Round selector
    - Category filter
    - Search functionality
    - Sorting options

### File Structure Changes
- Consolidated frontend JavaScript:
  - Merged functionality from `assets/js/frontend.js` into `assets/frontend.js`
  - Removed duplicate file `assets/js/frontend.js`
  - Maintained all existing functionality while improving code organization

### Database Improvements
- Enhanced database table creation process:
  - Added proper constraint handling
  - Improved table structure for `mt_votes` and `vote_reset_logs`
  - Added unique key for `unique_vote`
  - Implemented proper foreign key constraints

### Elementor Integration
- Improved Elementor widget loading:
  - Added proper checks for Elementor's existence
  - Enhanced widget registration process
  - Fixed class loading issues
  - Improved error handling

## Technical Details

### Property Declarations
```php
class MobilityTrailblazersPlugin {
    private $evaluation;
    private $jury_member;
    private $candidate;
    private $statistics;
    // ... other properties
}
```

### Shortcode Attributes
```php
$atts = shortcode_atts(array(
    'show_stats' => 'yes',
    'show_assignments' => 'yes',
    'show_evaluations' => 'yes',
    'show_public_voting' => 'yes',
    'show_round_selector' => 'yes',
    'show_category_filter' => 'yes',
    'show_search' => 'yes',
    'show_sort' => 'yes',
    'show_pagination' => 'yes',
    'items_per_page' => 10,
    // ... other attributes
), $atts, 'jury_dashboard');
```

### Database Tables
```sql
CREATE TABLE IF NOT EXISTS `wp_mt_votes` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `candidate_id` bigint(20) NOT NULL,
    `jury_member_id` bigint(20) NOT NULL,
    `vote_round` varchar(50) NOT NULL,
    `score` decimal(5,2) NOT NULL,
    `comments` text,
    `created_at` datetime NOT NULL,
    `updated_at` datetime NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_vote` (`candidate_id`, `jury_member_id`, `vote_round`)
);

CREATE TABLE IF NOT EXISTS `wp_vote_reset_logs` (
    `id` bigint(20) NOT NULL AUTO_INCREMENT,
    `initiated_by` bigint(20) NOT NULL,
    `affected_user_id` bigint(20) NOT NULL,
    `reset_type` varchar(50) NOT NULL,
    `reset_reason` text,
    `created_at` datetime NOT NULL,
    PRIMARY KEY (`id`)
);
```

## Usage Examples

### Jury Dashboard Shortcode
```php
[jury_dashboard show_stats="yes" show_assignments="yes" show_evaluations="yes"]
```

### Elementor Widget
```php
add_action('elementor/widgets/widgets_registered', function($widgets_manager) {
    if (class_exists('\Elementor\Widget_Base')) {
        $widgets_manager->register(new \MobilityTrailblazers\Integrations\Elementor\Widgets\JuryDashboardWidget());
    }
});
```

## Security Improvements
- Added proper nonce verification
- Implemented proper capability checks
- Added input sanitization
- Enhanced output escaping
- Improved error handling

## Performance Optimizations
- Consolidated JavaScript files
- Improved database queries
- Enhanced caching mechanisms
- Optimized template loading

## Future Considerations
1. Implement caching for frequently accessed data
2. Add more comprehensive error logging
3. Enhance the statistics gathering system
4. Improve the user interface for better usability
5. Add more customization options for the jury dashboard

## Known Issues
- None currently reported

## Dependencies
- WordPress 5.0+
- PHP 7.4+
- Elementor (optional)
- jQuery (included with WordPress)

## Support
For support, please contact the plugin maintainers or create an issue in the repository. 