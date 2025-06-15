# Installation Guide

## Quick Start (Without Composer)

The plugin is designed to work immediately after activation, even without Composer. Follow these steps:

### 1. Basic Installation

1. **Upload** the plugin files to `/wp-content/plugins/mobility-trailblazers/`
2. **Activate** the plugin through WordPress admin
3. **Access** the plugin via "MT Award System" in the admin menu

The plugin will automatically detect that Composer is not available and use manual loading.

### 2. What Works Immediately

- ✅ Basic admin interface with system status
- ✅ Plugin activation without errors
- ✅ Jury management (if files are present)
- ✅ Vote reset functionality (if files are present)
- ✅ Elementor integration (if files are present)
- ✅ AJAX fixes (if files are present)
- ✅ Graceful fallback when components are missing

### 3. Full Installation (With Composer)

For full functionality, install Composer dependencies:

```bash
cd /path/to/wp-content/plugins/mobility-trailblazers/
composer install --no-dev --optimize-autoloader
```

After running Composer, deactivate and reactivate the plugin to load all components.

## System Requirements

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+

## Troubleshooting

### Plugin Won't Activate
- Check PHP error logs
- Ensure minimum requirements are met
- Verify file permissions

### Missing Features
- Some features require Composer installation
- Check the admin dashboard for system status
- Refer to the migration guide for upgrading from old versions

### Database Issues
- The plugin creates tables automatically on activation
- If tables are missing, deactivate and reactivate the plugin
- Check WordPress database permissions

## Getting Help

1. Check the system status in the admin dashboard
2. Review the migration guide if upgrading
3. Enable WordPress debug mode for detailed error information
4. Check the plugin documentation

## Next Steps

After installation:

1. **Configure Settings**: Visit MT Award System → Settings
2. **Add Jury Members**: Use the Jury Management interface
3. **Import Candidates**: Add candidates through the WordPress admin
4. **Set Up Assignments**: Configure jury-candidate assignments
5. **Begin Evaluations**: Jury members can start evaluating candidates

The plugin is designed to be flexible and work with whatever components are available in your installation. 