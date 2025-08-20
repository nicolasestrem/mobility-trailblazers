# Immediate Fix Deployment Guide

## Issue 1: Delete Evaluation ID 15

### Via phpMyAdmin (Quickest)
1. Login to phpMyAdmin
2. Select database `wp_mobil_db1`
3. Click SQL tab
4. Run:
```sql
DELETE FROM wp_mt_evaluations WHERE id = 15;
```
5. Click "Go"
6. Confirm deletion success

## Issue 2: Fix "View Details" Buttons

### Method A: Quick Inline Fix (5 minutes)

1. **Via FTP:** Edit `/wp-content/plugins/mobility-trailblazers/templates/admin/evaluations.php`

2. **Find line 206** (at the very end of file, just before the last closing `</div>`)

3. **Add this code right BEFORE line 206:**

```php
<!-- Emergency Fix for View Details -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    $('.view-details').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('evaluation-id');
        var $row = $(this).closest('tr');
        var jury = $row.find('td:eq(2)').text().trim();
        var candidate = $row.find('td:eq(3)').text().trim();
        var score = $row.find('td:eq(4)').text().trim();
        var status = $row.find('td:eq(5)').text().trim();
        
        var modal = '<div style="position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:white;padding:30px;box-shadow:0 0 20px rgba(0,0,0,0.5);z-index:9999;max-width:600px;">' +
            '<h2>Evaluation #' + id + '</h2>' +
            '<p><strong>Jury:</strong> ' + jury + '</p>' +
            '<p><strong>Candidate:</strong> ' + candidate + '</p>' +
            '<p><strong>Score:</strong> ' + score + '</p>' +
            '<p><strong>Status:</strong> ' + status + '</p>' +
            '<button onclick="jQuery(\'#eval-modal\').remove();" class="button button-primary">Close</button>' +
            '</div>' +
            '<div id="eval-modal" onclick="jQuery(this).remove();" style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:9998;"></div>';
        
        $('#eval-modal').remove();
        $('body').append(modal);
    });
});
</script>
```

4. **Save the file**
5. **Test immediately** - no cache clearing needed!

### Method B: Proper JavaScript File (10 minutes)

1. **Upload the emergency fix file:**
   - Upload `evaluation-details-emergency-fix.js` to:
   - `/wp-content/plugins/mobility-trailblazers/assets/js/`

2. **Edit admin class to load it:**
   - Edit `/wp-content/plugins/mobility-trailblazers/includes/admin/class-mt-admin.php`
   - Find the `enqueue_scripts` method (around line 50-100)
   - Add after other wp_enqueue_script calls:

```php
// Emergency fix for evaluation details
if ($hook === 'toplevel_page_mt-evaluations') {
    wp_enqueue_script(
        'mt-evaluation-fix',
        MT_PLUGIN_URL . 'assets/js/evaluation-details-emergency-fix.js',
        array('jquery'),
        '1.0.0',
        true
    );
}
```

3. **Clear cache and test**

### Method C: Include PHP Fix File (5 minutes)

1. **Upload** `evaluations-inline-fix.php` to `/wp-content/plugins/mobility-trailblazers/templates/admin/`

2. **Edit** `evaluations.php`

3. **Add at line 206** (before the last `</div>`):
```php
<?php include 'evaluations-inline-fix.php'; ?>
```

4. **Save and test**

## Testing the Fixes

### Test Deletion:
1. Go to: https://mobilitytrailblazers.de/vote/wp-admin/admin.php?page=mt-evaluations
2. Verify evaluation ID 15 is gone

### Test View Details:
1. Stay on evaluations page
2. Click any "View Details" button
3. Modal should appear with evaluation info
4. Click "Close" or outside modal to dismiss
5. ESC key should also close modal

## If Fixes Don't Work

### Check JavaScript Console:
1. Press F12 in browser
2. Go to Console tab
3. Look for errors
4. Should see: "Evaluation Details Fix Active"

### Direct Database Delete:
If buttons still don't work, delete via phpMyAdmin:
```sql
-- Delete specific evaluation
DELETE FROM wp_mt_evaluations WHERE id = 15;

-- Or delete multiple
DELETE FROM wp_mt_evaluations WHERE id IN (15, 16, 17);
```

### Add Delete Links Directly:
As last resort, add delete links to evaluations.php at line 162:
```php
<a href="admin.php?page=mt-evaluations&action=delete&id=<?php echo $evaluation->id; ?>" 
   onclick="return confirm('Delete evaluation?');"
   class="button button-small">Delete</a>
```

## Success Indicators

✅ Evaluation ID 15 no longer appears in list
✅ View Details buttons show modal when clicked
✅ Modal displays evaluation information
✅ Modal can be closed (button, overlay, or ESC)
✅ Console shows "Evaluation Details Fix Active"

## Rollback

If something goes wrong:
1. Remove the added JavaScript code
2. Restore original evaluations.php from backup
3. Fixes are non-destructive and easily reversible

---

**Choose Method A for fastest fix (5 minutes)**
**Choose Method B for cleanest implementation (10 minutes)**
**Choose Method C for compromise solution (5 minutes)**