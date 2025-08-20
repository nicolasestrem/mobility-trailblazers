# Production Fix: Evaluations Page Issues

## Issue 1: Delete Evaluation ID 15

### Option A: Via phpMyAdmin (Recommended)
Run this SQL query:

```sql
-- First check the evaluation to confirm
SELECT * FROM wp_mt_evaluations WHERE id = 15;

-- If it's the correct one, delete it
DELETE FROM wp_mt_evaluations WHERE id = 15;

-- Verify it's deleted
SELECT * FROM wp_mt_evaluations WHERE id = 15;
```

### Option B: Delete Multiple Evaluations
If you need to delete multiple test evaluations:

```sql
-- Delete specific IDs
DELETE FROM wp_mt_evaluations WHERE id IN (15, 16, 17);

-- Or delete all evaluations from a specific jury member
DELETE FROM wp_mt_evaluations WHERE jury_member_id = [JURY_ID];

-- Or delete all draft/test evaluations
DELETE FROM wp_mt_evaluations WHERE status = 'draft';
```

## Issue 2: Fix "View Details" Buttons Not Working

The buttons aren't working because the JavaScript isn't loading or there's a JavaScript error. Here's how to fix it:

### Quick Fix via Browser Console
1. Open the evaluations page: https://mobilitytrailblazers.de/vote/wp-admin/admin.php?page=mt-evaluations
2. Open browser console (F12)
3. Paste this code to test if it works:

```javascript
jQuery(document).ready(function($) {
    $('.view-details').on('click', function() {
        var evaluationId = $(this).data('evaluation-id');
        alert('Evaluation ID: ' + evaluationId + '\n\nNote: The details modal functionality needs to be fixed.');
        
        // For now, redirect to the candidate's edit page
        var candidateLink = $(this).closest('tr').find('td:eq(3) a').attr('href');
        if (candidateLink) {
            window.open(candidateLink, '_blank');
        }
    });
});
```

### Permanent Fix

#### Step 1: Check if JavaScript is Loading
In browser console, type:
```javascript
typeof MTEvaluations
```

If it returns "undefined", the script isn't loading.

#### Step 2: Add Emergency Fix
Create a new file via FTP: `/wp-content/plugins/mobility-trailblazers/assets/js/evaluation-details-fix.js`

```javascript
jQuery(document).ready(function($) {
    // Emergency fix for View Details buttons
    $('.view-details').on('click', function(e) {
        e.preventDefault();
        
        var evaluationId = $(this).data('evaluation-id');
        var $row = $(this).closest('tr');
        
        // Get evaluation data from the row
        var juryMember = $row.find('td:eq(2)').text().trim();
        var candidate = $row.find('td:eq(3)').text().trim();
        var totalScore = $row.find('td:eq(4)').text().trim();
        var status = $row.find('td:eq(5)').text().trim();
        var date = $row.find('td:eq(6)').text().trim();
        
        // Create simple modal
        var modalHtml = '<div id="evaluation-modal" style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:30px;box-shadow:0 0 20px rgba(0,0,0,0.5);z-index:9999;max-width:600px;width:90%;">' +
            '<h2>Evaluation Details #' + evaluationId + '</h2>' +
            '<table class="widefat">' +
            '<tr><th>Jury Member:</th><td>' + juryMember + '</td></tr>' +
            '<tr><th>Candidate:</th><td>' + candidate + '</td></tr>' +
            '<tr><th>Total Score:</th><td>' + totalScore + '</td></tr>' +
            '<tr><th>Status:</th><td>' + status + '</td></tr>' +
            '<tr><th>Date:</th><td>' + date + '</td></tr>' +
            '</table>' +
            '<p style="margin-top:20px;">' +
            '<button onclick="jQuery(\'#evaluation-modal, #evaluation-overlay\').remove();" class="button button-primary">Close</button> ' +
            '<button onclick="if(confirm(\'Delete this evaluation?\')) { window.location.href=\'admin.php?page=mt-evaluations&action=delete&id=' + evaluationId + '&_wpnonce=' + MTEvaluations.nonce + '\'; }" class="button button-link-delete">Delete</button>' +
            '</p>' +
            '</div>' +
            '<div id="evaluation-overlay" onclick="jQuery(\'#evaluation-modal, #evaluation-overlay\').remove();" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9998;"></div>';
        
        // Remove any existing modal
        $('#evaluation-modal, #evaluation-overlay').remove();
        
        // Add modal to page
        $('body').append(modalHtml);
    });
    
    // Handle bulk actions
    $('#doaction, #doaction2').on('click', function(e) {
        var action = $(this).prev('select').val();
        
        if (action === 'delete') {
            if (!confirm('Are you sure you want to delete the selected evaluations?')) {
                e.preventDefault();
                return false;
            }
            
            var selected = [];
            $('input[name="evaluation[]"]:checked').each(function() {
                selected.push($(this).val());
            });
            
            if (selected.length > 0) {
                // Create form and submit
                var form = $('<form method="post" action="admin.php?page=mt-evaluations">' +
                    '<input type="hidden" name="action" value="bulk_delete">' +
                    '<input type="hidden" name="evaluations" value="' + selected.join(',') + '">' +
                    '<input type="hidden" name="_wpnonce" value="' + (window.MTEvaluations ? MTEvaluations.nonce : '') + '">' +
                    '</form>');
                $('body').append(form);
                form.submit();
            }
        }
    });
});
```

#### Step 3: Enqueue the Fix
Add this to the main plugin file or admin class via FTP:

In `/wp-content/plugins/mobility-trailblazers/includes/admin/class-mt-admin.php`, find the `enqueue_scripts` method and add:

```php
// Emergency fix for evaluation details
wp_enqueue_script(
    'mt-evaluation-details-fix',
    MT_PLUGIN_URL . 'assets/js/evaluation-details-fix.js',
    array('jquery'),
    '1.0.0',
    true
);

// Pass nonce for security
wp_localize_script('mt-evaluation-details-fix', 'MTEvaluations', array(
    'nonce' => wp_create_nonce('mt_evaluations_nonce'),
    'ajaxurl' => admin_url('admin-ajax.php')
));
```

### Alternative: Add Delete Links Directly

If you just need to delete evaluations quickly, add this to the evaluations.php template at line 162 (after View Details button):

```php
<a href="<?php echo wp_nonce_url(admin_url('admin.php?page=mt-evaluations&action=delete&id=' . $evaluation->id), 'delete_evaluation_' . $evaluation->id); ?>" 
   class="button button-small"
   onclick="return confirm('Delete this evaluation?');">
    <?php _e('Delete', 'mobility-trailblazers'); ?>
</a>
```

## Quick Database Commands

### View All Evaluations
```sql
SELECT 
    e.id,
    e.jury_member_id,
    jm.post_title as jury_name,
    e.candidate_id,
    c.post_title as candidate_name,
    e.total_score,
    e.status,
    e.updated_at
FROM wp_mt_evaluations e
LEFT JOIN wp_posts jm ON e.jury_member_id = jm.ID
LEFT JOIN wp_posts c ON e.candidate_id = c.ID
ORDER BY e.id DESC;
```

### Delete Test/Duplicate Evaluations
```sql
-- Delete duplicates (keep only the latest)
DELETE e1 FROM wp_mt_evaluations e1
INNER JOIN wp_mt_evaluations e2 
WHERE e1.jury_member_id = e2.jury_member_id 
AND e1.candidate_id = e2.candidate_id
AND e1.id < e2.id;

-- Delete all draft evaluations
DELETE FROM wp_mt_evaluations WHERE status = 'draft';

-- Delete evaluations with low scores (likely tests)
DELETE FROM wp_mt_evaluations WHERE total_score < 10;
```

### Export Evaluations for Backup
```sql
SELECT * FROM wp_mt_evaluations 
INTO OUTFILE '/tmp/evaluations_backup.csv'
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

## After Fixes

1. Clear browser cache (Ctrl+F5)
2. Clear WordPress cache
3. Test the View Details buttons
4. Verify the deleted evaluation is gone

The JavaScript fix will provide a working modal for viewing and deleting evaluations until the proper AJAX functionality can be restored.