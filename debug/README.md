# Debug Scripts Directory

This directory contains debug and utility scripts for the Mobility Trailblazers plugin.

## ⚠️ IMPORTANT WARNING

These scripts are for development and debugging purposes only. They should **NEVER** be executed on a production site without careful review and understanding of their functionality.

## Current Scripts Status

### Active/Current Scripts
- `check-jury-status.php` - Check jury member status and assignments
- `check-schneidewind-import.php` - Verify specific candidate import
- `test-db-connection.php` - Test database connectivity

### Migration Scripts (Keep for Reference)
- `migrate-candidate-profiles.php` - Used for migrating candidate profiles
- `migrate-jury-posts.php` - Used for migrating jury member posts
- `fix-database.php` - Database structure fixes
- `fix-assignments.php` - Assignment data corrections

### Outdated/Deprecated Scripts
⚠️ These scripts contain old logic that may not match current implementation:
- `test-regex-debug.php` - Old regex patterns, current implementation differs
- `fix-existing-evaluations.php` - Old evaluation parsing logic, superseded by new parser
- `direct-fix-evaluations.php` - Outdated evaluation fix
- `final-fix-evaluations.php` - Outdated evaluation fix
- `test-evaluation-parsing.php` - Old parsing logic

### Development/Testing Scripts
- `fake-candidates-generator.php` - Generate test candidate data
- `generate-sample-profiles.php` - Generate sample profile data
- `test-profile-system.php` - Test profile functionality
- `jury-import.php` - Jury member import utility

## Usage Guidelines

1. **Always backup your database** before running any debug script
2. **Review the script code** before execution to understand what it does
3. **Test in development environment first**
4. **Check script compatibility** with current plugin version
5. **Update this README** when adding new scripts or changing script status

## Security Notes

- These scripts should not be accessible via web browser in production
- Consider adding `.htaccess` or server-level protection
- Remove unnecessary scripts before deployment
- Never commit sensitive data or credentials in debug scripts

## Last Updated
January 2025 - Plugin Version 2.0.0