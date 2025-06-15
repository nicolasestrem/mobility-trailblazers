# Migration Guide: Old to New Architecture

## Pre-Migration Checklist

### 1. Backup Everything
```bash
# Database backup
mysqldump -u username -p database_name > backup.sql

# Files backup
tar -czf plugin-backup.tar.gz wp-content/plugins/mobility-trailblazers/
```

### 2. Document Current State
- [ ] List of active candidates
- [ ] List of jury members
- [ ] Current voting phase
- [ ] Any custom modifications
- [ ] Third-party integrations

### 3. Test Environment Setup
- [ ] Create staging site
- [ ] Copy production data
- [ ] Test migration process

## Migration Process

### Step 1: Deactivate Old Plugin
```php
// In WordPress admin
Plugins → Mobility Trailblazers → Deactivate
```

### Step 2: Replace Files
```bash
# Remove old files (keep backup!)
rm -rf wp-content/plugins/mobility-trailblazers/

# Upload new files
# Extract new plugin to wp-content/plugins/mobility-trailblazers/
```

### Step 3: Activate New Plugin
```php
// The new activator will:
// - Update database schema
// - Migrate existing data
// - Set up new roles and capabilities
// - Create missing tables
```

### Step 4: Verify Migration
- [ ] Check candidates are visible
- [ ] Verify jury members exist
- [ ] Test evaluation functionality
- [ ] Confirm assignments work
- [ ] Validate vote reset system

## Data Migration

### Automatic Migrations
The new version includes automatic migration for:
- Candidate data
- Jury member information
- Voting records
- User roles and capabilities

### Manual Steps Required
1. **Custom Fields**: May need remapping
2. **Template Overrides**: Update to new structure
3. **Custom Code**: Update function calls
4. **Asset URLs**: Update any hardcoded paths

## Troubleshooting

### Common Issues
1. **Database Errors**: Run activation again
2. **Missing Data**: Check migration logs
3. **Permission Issues**: Verify user roles
4. **Asset Loading**: Clear caches

### Recovery Plan
If migration fails:
1. Deactivate new plugin
2. Restore old plugin files
3. Restore database backup
4. Reactivate old plugin
5. Contact support

## Post-Migration Tasks

### 1. Update Customizations
- Review custom templates
- Update any custom CSS/JS
- Test third-party integrations

### 2. User Training
- New admin interface
- Updated jury dashboard
- Enhanced features

### 3. Performance Optimization
- Enable caching
- Optimize database
- Update server configuration

## Support

For migration assistance:
- Check diagnostic page: Admin → MT Award System → Diagnostic
- Review error logs: wp-content/debug.log
- Contact support with migration logs 