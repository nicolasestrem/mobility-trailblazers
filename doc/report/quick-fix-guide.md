# Quick Fix Guide - Critical Issues

## 🔴 PRIORITY 1: SQL Injection (Fix NOW)

### File: `includes/core/class-mt-database-upgrade.php`
**Lines to fix**: 68, 72, 76, 84, 88, 105-113, 126

Replace all direct queries:
```php
// OLD (VULNERABLE)
$wpdb->query("ALTER TABLE {$wpdb->prefix}mt_evaluations ADD COLUMN ...");

// NEW (SECURE) 
$table = esc_sql($wpdb->prefix . 'mt_evaluations');
$wpdb->query("ALTER TABLE `$table` ADD COLUMN ...");
```

### File: `includes/repositories/class-mt-evaluation-repository.php`
**Lines to fix**: 710-715

```php
// OLD (VULNERABLE)
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%mt_cache%'");

// NEW (SECURE)
$wpdb->query($wpdb->prepare(
    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
    '%mt_cache%'
));
```

## 🔴 PRIORITY 2: Permission Bypass

### File: `includes/ajax/class-mt-evaluation-ajax.php`
**Lines to fix**: 669-684

```php
// ADD THIS CHECK
if (!$this->assignment_repository->has_assignment($jury_id, $candidate_id)) {
    if (!current_user_can('administrator')) {
        wp_send_json_error(['message' => 'No assignment for this candidate']);
        return;
    }
}
```

## 🔴 PRIORITY 3: Version Alignment

### File: `mobility-trailblazers.php`
**Line 6**: Change to `Version: 2.5.34`
**Line 40**: Keep as `define('MT_VERSION', '2.5.34');`

### File: `CLAUDE.md`
**Line 5**: Update to `**CURRENT VERSION**: 2.5.34`

## 🔴 PRIORITY 4: Database Indexes

Run these SQL commands:
```sql
ALTER TABLE wp_mt_evaluations 
ADD INDEX idx_jury_candidate (jury_id, candidate_id);

ALTER TABLE wp_mt_evaluations 
ADD INDEX idx_candidate_scores (candidate_id, total_score);

ALTER TABLE wp_mt_assignments 
ADD INDEX idx_jury_assignments (jury_id, assigned_date);
```

## 🟡 PRIORITY 5: N+1 Query Fix

### File: `includes/admin/class-mt-admin.php`
**Lines**: 213-225

```php
// OLD (N+1 Problem)
foreach ($candidates as $candidate) {
    $meta = get_post_meta($candidate->ID);
}

// NEW (Optimized)
$candidate_ids = wp_list_pluck($candidates, 'ID');
$all_meta = $wpdb->get_results(
    "SELECT post_id, meta_key, meta_value 
     FROM {$wpdb->postmeta} 
     WHERE post_id IN (" . implode(',', $candidate_ids) . ")"
);
```

## 🟡 PRIORITY 6: Add Touch Events

### File: `assets/js/admin.js`
Add after line 1:
```javascript
// Mobile touch support
if ('ontouchstart' in window) {
    $(document).on('touchstart', '.mt-evaluation-slider', function(e) {
        $(this).trigger('mousedown', e);
    });
}
```

## Testing Commands

After fixes, run:
```bash
# Check for SQL issues
grep -r "wpdb->query" includes/ --include="*.php" | grep -v "prepare"

# Check permissions
grep -r "current_user_can" includes/ajax/ --include="*.php"

# Test database
wp db query "SHOW INDEX FROM wp_mt_evaluations"

# Check version
grep -r "2\.5\." . --include="*.php" --include="*.md"
```

## Verification Checklist

- [ ] All direct SQL queries use prepared statements
- [ ] AJAX handlers check permissions properly  
- [ ] Version numbers consistent across all files
- [ ] Database indexes created
- [ ] N+1 queries eliminated
- [ ] Touch events added for mobile
- [ ] Tested with 200+ candidates
- [ ] Page loads under 2 seconds

**CRITICAL**: Deploy these fixes immediately to prevent security breaches!