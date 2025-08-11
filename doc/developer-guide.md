# Mobility Trailblazers Developer Guide

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
| `candidates_per_jury` | int | 5 | Number of candidates each jury member should evaluate |
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

- **v2.2.1** (2025-08-11): Complete refactoring of auto-assignment algorithms
  - Fixed balanced distribution logic
  - Implemented true random distribution
  - Improved performance and error handling
  - Added comprehensive logging

- **v2.0.0** (2024-01): Initial implementation
  - Basic round-robin assignment
  - Simple random selection
