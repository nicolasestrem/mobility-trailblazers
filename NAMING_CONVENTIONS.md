# Mobility Trailblazers Plugin - Naming Conventions

This document establishes the naming conventions for the Mobility Trailblazers WordPress plugin to ensure consistency and maintainability across the codebase.

## Table of Contents
1. [PHP Files](#php-files)
2. [Functions](#functions)
3. [CSS Classes](#css-classes)
4. [JavaScript](#javascript)
5. [Database Tables](#database-tables)
6. [WordPress Hooks](#wordpress-hooks)
7. [Constants](#constants)
8. [Migration Strategy](#migration-strategy)

## PHP Files

### Class Files
- **Format**: `class-mt-{name}.php` (lowercase, hyphens)
- **Examples**:
  - `class-mt-ajax-handlers.php`
  - `class-mt-shortcodes.php`
  - `class-mt-jury-system.php`

### Utility Files
- **Format**: `mt-{name}-functions.php`
- **Examples**:
  - `mt-utility-functions.php`
  - `mt-debug-functions.php`
  - `mt-template-functions.php`

### Interface Files
- **Format**: `interface-mt-{name}.php`
- **Examples**:
  - `interface-mt-repository.php`
  - `interface-mt-service.php`

### Admin Views
- **Format**: `{name}-{view}.php`
- **Examples**:
  - `dashboard-main.php`
  - `assignments-list.php`
  - `voting-results.php`

### Template Files
- **Format**: `{type}-{name}.php`
- **Examples**:
  - `shortcode-jury-dashboard.php`
  - `widget-evaluation-stats.php`
  - `email-assignment-notification.php`

## Functions

### Global Functions
- **Format**: `mt_{verb}_{noun}()`
- **Examples**:
  ```php
  mt_get_candidate()
  mt_save_evaluation()
  mt_is_jury_member()
  mt_calculate_total_score()
  ```

### Class Methods
- **Format**: `{verb}_{noun}()` (no prefix inside classes)
- **Examples**:
  ```php
  public function get_candidate()
  private function validate_score()
  protected function render_dashboard()
  ```

### AJAX Handler Methods
- **Format**: `ajax_{action}()`
- **Examples**:
  ```php
  public function ajax_save_evaluation()
  public function ajax_get_candidates()
  public function ajax_reset_votes()
  ```

### Hook Callback Methods
- **Format**: `{verb}_{noun}()` or `on_{event}()`
- **Examples**:
  ```php
  public function register_widgets()
  public function enqueue_scripts()
  public function on_init()
  public function on_save_post()
  ```

## CSS Classes

### BEM Methodology
We adopt the BEM (Block Element Modifier) methodology with `mt-` prefix.

### Block
- **Format**: `.mt-{block}`
- **Examples**:
  ```css
  .mt-dashboard
  .mt-candidate-card
  .mt-evaluation-form
  ```

### Element
- **Format**: `.mt-{block}__{element}`
- **Examples**:
  ```css
  .mt-dashboard__header
  .mt-dashboard__stats
  .mt-candidate-card__image
  .mt-candidate-card__title
  ```

### Modifier
- **Format**: `.mt-{block}--{modifier}` or `.mt-{block}__{element}--{modifier}`
- **Examples**:
  ```css
  .mt-dashboard--loading
  .mt-candidate-card--featured
  .mt-evaluation-form__submit--disabled
  ```

### State Classes
- **Format**: `.is-{state}` or `.has-{state}`
- **Examples**:
  ```css
  .is-active
  .is-loading
  .has-error
  .has-unsaved-changes
  ```

### JavaScript Hook Classes
- **Format**: `.js-{name}` (for JavaScript targeting only, no styling)
- **Examples**:
  ```css
  .js-submit-evaluation
  .js-toggle-menu
  .js-load-more
  ```

## JavaScript

### Variables
- **Format**: camelCase
- **Examples**:
  ```javascript
  let candidateId = 123;
  const evaluationData = {};
  var totalScore = 0;
  ```

### Constants
- **Format**: UPPER_SNAKE_CASE
- **Examples**:
  ```javascript
  const API_ENDPOINT = '/wp-json/mt/v1/';
  const MAX_SCORE = 10;
  const DEBOUNCE_DELAY = 300;
  ```

### Functions
- **Format**: camelCase
- **Examples**:
  ```javascript
  function getCandidateData() {}
  function saveEvaluation() {}
  function calculateTotalScore() {}
  ```

### jQuery Plugin Methods
- **Format**: camelCase with `mt` prefix
- **Examples**:
  ```javascript
  $.fn.mtDashboard = function() {};
  $.fn.mtEvaluationForm = function() {};
  ```

### Event Names
- **Format**: lowercase with dots for namespacing
- **Examples**:
  ```javascript
  'mt.evaluation.saved'
  'mt.dashboard.loaded'
  'mt.candidate.selected'
  ```

## Database Tables

### Table Names
- **Format**: `{wp_prefix}mt_{name}` (lowercase, underscores)
- **Examples**:
  - `wp_mt_votes`
  - `wp_mt_evaluations`
  - `wp_mt_jury_assignments`
  - `wp_mt_candidate_scores`

### Column Names
- **Format**: lowercase with underscores
- **Examples**:
  - `candidate_id`
  - `jury_member_id`
  - `total_score`
  - `created_at`
  - `is_active`

## WordPress Hooks

### Actions
- **Format**: `mt_{verb}_{noun}`
- **Examples**:
  ```php
  do_action('mt_before_save_evaluation', $candidate_id, $scores);
  do_action('mt_after_vote_submitted', $vote_data);
  do_action('mt_jury_member_assigned', $jury_id, $candidate_id);
  ```

### Filters
- **Format**: `mt_{noun}_{context}`
- **Examples**:
  ```php
  apply_filters('mt_evaluation_criteria', $criteria);
  apply_filters('mt_candidate_display_fields', $fields);
  apply_filters('mt_dashboard_stats', $stats, $user_id);
  ```

## Constants

### Plugin Constants
- **Format**: `MT_{NAME}` (uppercase, underscores)
- **Examples**:
  ```php
  define('MT_PLUGIN_VERSION', '1.0.6');
  define('MT_PLUGIN_DIR', plugin_dir_path(__FILE__));
  define('MT_DEBUG_MODE', false);
  ```

### Class Constants
- **Format**: `{NAME}` (uppercase, underscores, no prefix in class context)
- **Examples**:
  ```php
  class MT_Evaluations {
      const STATUS_DRAFT = 'draft';
      const STATUS_SUBMITTED = 'submitted';
      const MAX_SCORE = 10;
  }
  ```

## Migration Strategies

### Deprecated Function Handling
When renaming functions, maintain backward compatibility:

```php
/**
 * Get jury nomenclature
 * 
 * @deprecated 1.0.6 Use mt_get_jury_nomenclature() instead
 */
function get_jury_nomenclature() {
    _deprecated_function(__FUNCTION__, '1.0.6', 'mt_get_jury_nomenclature');
    return mt_get_jury_nomenclature();
}
```

### CSS Class Migration
Use both old and new classes during transition:

```html
<!-- Transition period: use both classes -->
<div class="jury-dashboard mt-jury-dashboard">
    <div class="dashboard-header mt-jury-dashboard__header">
        <!-- content -->
    </div>
</div>
```

### JavaScript Migration
Support both old and new function names:

```javascript
// New function
function mtGetCandidateData() {
    // implementation
}

// Deprecated alias
const getCandidateData = function() {
    console.warn('getCandidateData() is deprecated. Use mtGetCandidateData() instead.');
    return mtGetCandidateData.apply(this, arguments);
};
```

## Enforcement

### Pre-commit Hooks
Use git pre-commit hooks to check naming conventions:
- PHP_CodeSniffer with custom ruleset
- ESLint for JavaScript
- Stylelint for CSS

### Code Review Checklist
- [ ] All new PHP functions follow `mt_` prefix convention
- [ ] CSS classes use `.mt-` prefix and BEM methodology
- [ ] JavaScript variables use camelCase
- [ ] Database tables use `mt_` prefix
- [ ] Constants use `MT_` prefix
- [ ] Deprecated functions have proper warnings
- [ ] Documentation reflects new naming

## Exceptions

The following are acceptable exceptions to the naming rules:

1. **WordPress Core Hooks**: When implementing WordPress core hooks, follow WordPress naming:
   ```php
   public function init() {} // WordPress expects 'init', not 'on_init'
   ```

2. **Third-party Integrations**: When implementing interfaces from third-party plugins:
   ```php
   public function register() {} // If required by external interface
   ```

3. **Legacy Code**: During transition period, some legacy names may remain with deprecation notices.

## Version History

- **1.0.6** - Initial naming convention standardization
- Future versions will strictly enforce these conventions