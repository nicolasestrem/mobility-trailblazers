# Jury Username Fix - Implementation Guide

## Issue Summary
All jury members on the production site (https://mobilitytrailblazers.de/vote/) have dots (1-5 dots) prepended to their usernames in the database. This prevents them from logging in with their expected usernames.

### Examples:
- `.....torsten.tomczak` instead of `torsten.tomczak`
- `....andreas.herrmann` instead of `andreas.herrmann`
- `...oliver.gassmann` instead of `oliver.gassmann`

## Root Cause
The usernames were corrupted during data import/migration. The local development database has the correct usernames without dots.

## Solution Overview
Two solutions have been created to fix this issue:

### 1. SQL Script Solution
**File:** `doc/fix-jury-usernames-comprehensive-2025-08-20.sql`

This comprehensive SQL script:
- Analyzes all affected users
- Creates backup tables
- Removes leading dots from usernames
- Updates user_nicename to match
- Preserves all relationships (evaluations, assignments, posts)
- Includes rollback script if needed

### 2. PHP Admin Tool Solution (RECOMMENDED)
**File:** `tools/fix-jury-usernames.php`

A user-friendly web interface that:
- Shows all affected users in a table
- Displays statistics (evaluations, posts per user)
- Creates backups with one click
- Applies the fix safely with WordPress functions
- Clears all caches automatically
- Shows before/after usernames clearly

## How to Apply the Fix

### Option A: Using the PHP Tool (Recommended)
1. Upload `tools/fix-jury-usernames.php` to the production server
2. Access it via browser: `https://mobilitytrailblazers.de/vote/wp-content/plugins/mobility-trailblazers/tools/fix-jury-usernames.php`
3. Login as WordPress admin
4. Click "Create Backup" first
5. Review the affected users table
6. Click "Apply Fix" to remove the dots
7. Notify jury members of their new usernames

### Option B: Using SQL Script
1. Connect to production database:
   ```bash
   mysql -D wp_mobil_db1 -u wp_mobil_1 -pwA7fRFSyrqrr97vF -h j3a4.your-database.de
   ```
2. Run the SQL script step by step
3. Create backups first (Step 2 in the script)
4. Apply the fix (Step 4 in the script)
5. Verify the results (Step 5 in the script)

## Important Notes

### Before Applying:
- Schedule during low-traffic hours
- Notify IT team about the maintenance
- Have database backup ready

### After Applying:
1. **User Communication:** Send email to all jury members with:
   - Their new username (without dots)
   - Instructions to reset password if needed
   - Support contact information

2. **Clear Caches:**
   - WordPress object cache
   - Any CDN cache
   - Browser caches (instruct users)

3. **Testing:**
   - Test login with at least one fixed username
   - Verify evaluations are still accessible
   - Check jury dashboard functionality

4. **Monitoring:**
   - Watch error logs for 24 hours
   - Be ready to rollback if issues arise
   - Keep backup tables for at least 1 week

## Affected Users (from production)
All jury members with role `mt_jury_member` are affected. The dots need to be removed from their usernames.

## Data Integrity
✅ **Safe to apply** because:
- WordPress uses user IDs (not usernames) for all relationships
- Evaluations table uses `jury_member_id` (numeric ID)
- Posts table uses `post_author` (numeric ID)
- User meta uses `user_id` (numeric ID)

## Rollback Plan
If issues occur after applying the fix:
1. Use the rollback SQL in the script (Step 7)
2. Or restore from backup tables created
3. Clear all caches again
4. Notify users to use old usernames with dots

## Contact for Issues
If you encounter any problems:
1. Check `wp-content/debug.log` for errors
2. Review the backup tables in database
3. Contact development team with error details

## Timeline
- **Issue Discovered:** August 20, 2025
- **Solution Created:** August 20, 2025
- **Recommended Application:** As soon as possible during low-traffic hours
- **User Notification:** Immediately after successful application

---
*Document created: August 20, 2025*
*Platform: Mobility Trailblazers Award Platform*
