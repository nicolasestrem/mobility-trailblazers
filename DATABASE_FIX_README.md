# Database Fix Instructions

## Issues Fixed

This update addresses two critical errors in the Mobility Trailblazers plugin:

1. **Missing `mt_is_jury_member()` function** - This function was being called but didn't exist
2. **Missing database table `wp_mt_jury_assignments`** - The database table wasn't created during plugin activation

## How to Fix

### Option 1: Automatic Fix (Recommended)

The plugin now includes automatic database checking and fixing. Simply:

1. Deactivate the plugin
2. Reactivate the plugin
3. The missing tables will be created automatically

### Option 2: Manual Fix via Admin Panel

1. Go to WordPress Admin → MT Award System → Database Fix
2. Click "Create Missing Tables" button
3. Verify all tables show as "Exists"

### Option 3: Command Line Fix

If you have command line access:

```bash
# Navigate to WordPress root directory
cd /path/to/wordpress

# Run the fix script
php wp-content/plugins/mobility-trailblazers/fix-database.php
```

### Option 4: Manual Database Creation

If the above methods don't work, you can manually trigger the database creation:

1. Add this code to your theme's `functions.php` file temporarily:

```php
// Temporary database fix - remove after running once
add_action('init', function() {
    if (current_user_can('manage_options')) {
        require_once WP_PLUGIN_DIR . '/mobility-trailblazers/includes/class-database.php';
        $database = new MT_Database();
        $database->force_create_tables();
    }
});
```

2. Visit any page on your site while logged in as an administrator
3. Remove the code from `functions.php`

## What Was Fixed

### 1. Added Missing Function

The `mt_is_jury_member()` function was added to `includes/mt-utility-functions.php`. This function:

- Checks if a user has the jury member role
- Checks if a user has jury member capabilities
- Checks if a user is associated with a jury member post
- Returns `true` if the user is a jury member, `false` otherwise

### 2. Enhanced Database Management

- Added automatic database table checking on plugin initialization
- Added a `force_create_tables()` method to the database class
- Added database diagnostic functions
- Created an admin page for database management

### 3. Improved Error Handling

- The `mt_get_assigned_candidates()` function now has a fallback method that works even if the database table doesn't exist
- Added comprehensive database status checking
- Added user-friendly error messages

## Database Tables Created

The following tables will be created:

- `wp_mt_jury_assignments` - Stores jury member assignments to candidates
- `wp_mt_evaluations` - Stores jury member evaluations of candidates
- `wp_mt_votes` - Stores voting data
- `wp_mt_candidate_scores` - Stores candidate scoring data
- `wp_vote_reset_logs` - Stores vote reset logs
- `wp_mt_vote_backups` - Stores vote backup data

## Verification

After running the fix, you can verify everything is working by:

1. Checking the Database Fix admin page shows all tables as "Exists"
2. Ensuring no PHP errors appear in the error log
3. Testing jury member functionality (if you have jury members set up)

## Troubleshooting

If you still encounter issues:

1. Check your WordPress error log for specific error messages
2. Ensure your database user has CREATE TABLE permissions
3. Verify the plugin files are properly uploaded and not corrupted
4. Try deactivating other plugins temporarily to check for conflicts

## Support

If you continue to have issues, please:

1. Check the WordPress error log for specific error messages
2. Verify your WordPress and PHP versions meet the plugin requirements
3. Contact support with the specific error messages you're seeing 