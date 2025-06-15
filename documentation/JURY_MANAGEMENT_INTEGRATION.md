# Jury Management System Integration Guide

## Overview
This integration connects the enhanced jury management system with your existing Mobility Trailblazers plugin, adding powerful jury management capabilities while maintaining full compatibility.

## What's Been Integrated

### 1. Core Plugin Updates (`mobility-trailblazers.php`)

#### Dependencies Loading
- Added `admin/class-jury-management-admin.php` to the load dependencies
- Automatic loading and initialization of the jury management admin class

#### Enhanced Methods Added
- `create_enhanced_jury_member($data)` - Create jury members with extended fields
- `create_jury_wordpress_user($email, $name)` - Auto-create WordPress users
- `send_jury_welcome_email($user_id, $password)` - Welcome email system
- `get_jury_statistics()` - Comprehensive analytics
- `optimize_jury_assignments()` - Smart assignment system
- `send_bulk_jury_email($subject, $message, $jury_ids)` - Bulk communications
- `export_jury_evaluation_report()` - Advanced reporting

#### Menu Integration
- Added "Jury Management" submenu under the main MT Award System menu
- Accessible to users with `manage_options` capability

### 2. New Features Available

#### Enhanced Jury Profiles
- Extended fields: Organization, Position, Category, LinkedIn, Bio
- Status tracking (Active/Inactive)
- WordPress user integration with automatic account creation
- Welcome emails with login credentials

#### Advanced Analytics
- Total jury members and active count
- Category-based completion rates
- Top performer rankings
- Evaluation statistics

#### Smart Assignment System
- Category-based matching (infrastructure, startups, established)
- Load balancing across jury members
- Optimization algorithms for fair distribution

#### Communication Tools
- Bulk email to all or selected jury members
- Message personalization with placeholders
- Delivery tracking

#### Comprehensive Reporting
- Complete evaluation exports
- CSV format with all scoring details
- Jury member and candidate information included

## How to Use

### Creating Enhanced Jury Members

```php
// Example usage in your code
global $mobility_trailblazers_plugin;

$jury_data = array(
    'name' => 'Dr. Sarah Johnson',
    'email' => 'sarah@techcorp.com',
    'organization' => 'TechCorp Industries',
    'position' => 'Head of Innovation',
    'category' => 'infrastructure',
    'linkedin' => 'https://linkedin.com/in/sarahjohnson',
    'bio' => 'Expert in mobility infrastructure with 12 years experience...',
    'create_user' => true // Creates WordPress user account
);

$jury_id = $mobility_trailblazers_plugin->create_enhanced_jury_member($jury_data);
```

### Getting Statistics

```php
$stats = $mobility_trailblazers_plugin->get_jury_statistics();
echo "Active Jury: " . $stats['active_jury'];
echo "Infrastructure Completion: " . $stats['completion_by_category']['infrastructure']['rate'] . "%";
```

### Optimizing Assignments

```php
$result = $mobility_trailblazers_plugin->optimize_jury_assignments();
if ($result['success']) {
    echo "Created " . $result['assignments'] . " optimized assignments";
}
```

### Sending Bulk Emails

```php
$subject = "Evaluation Deadline Reminder";
$message = "Dear [name], please complete your evaluations by Friday...";
$sent = $mobility_trailblazers_plugin->send_bulk_jury_email($subject, $message);
echo "Sent to " . $sent . " jury members";
```

## Admin Interface

### Accessing Jury Management
1. Go to WordPress Admin Dashboard
2. Navigate to "MT Award System" → "Jury Management"
3. Use the enhanced interface to:
   - Add new jury members with extended fields
   - View comprehensive statistics
   - Manage assignments
   - Send bulk communications
   - Export reports

### Key Features in Admin
- **Add Jury Member**: Extended form with all new fields
- **Statistics Dashboard**: Visual analytics and metrics
- **Assignment Manager**: Optimize and track assignments
- **Communication Center**: Bulk email functionality
- **Export Tools**: Generate comprehensive reports

## Database Schema

### New Meta Fields for Jury Members
- `_mt_jury_email` - Contact email
- `_mt_jury_organization` - Company/Institution
- `_mt_jury_position` - Job title
- `_mt_jury_category` - Expertise area
- `_mt_jury_linkedin` - Professional profile
- `_mt_jury_status` - Active/Inactive
- `_mt_jury_created_date` - Registration date
- `_mt_jury_user_id` - WordPress user ID

## Security Features

### User Management
- Secure password generation (12+ characters)
- Proper role assignment (`mt_jury_member`)
- Email validation and sanitization

### Data Protection
- SQL injection prevention
- XSS protection
- Proper capability checks
- Nonce verification for forms

## Compatibility

### Requirements
- WordPress 5.0+
- PHP 7.4+
- Existing Mobility Trailblazers plugin
- MySQL 5.6+

### Tested With
- WordPress 6.x
- PHP 8.x
- Various hosting environments

## Troubleshooting

### Common Issues

**Jury Management menu not showing:**
- Check user has admin privileges
- Verify `class-jury-management-admin.php` exists in `/admin/` folder
- Ensure plugin is activated

**Emails not sending:**
- Check WordPress mail configuration
- Verify SMTP settings
- Test with simple WordPress mail function

**Assignment optimization failing:**
- Ensure active jury members exist
- Check candidate categories are set
- Verify database connectivity

### Debug Mode
Enable WordPress debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Benefits of Integration

### For Administrators
- Streamlined jury member management
- Comprehensive analytics and reporting
- Automated assignment optimization
- Bulk communication tools
- Enhanced security and user management

### For Jury Members
- Professional profile management
- Clear assignment tracking
- Improved user experience
- Automated notifications
- Easy access to evaluation tools

### For the System
- Better data organization
- Improved performance through optimization
- Enhanced security measures
- Scalable architecture
- Future-proof design

## Next Steps

1. **Test the Integration**: Create a test jury member to verify functionality
2. **Import Existing Data**: Migrate current jury members to enhanced format
3. **Configure Settings**: Set up email templates and categories
4. **Train Users**: Familiarize administrators with new features
5. **Monitor Performance**: Track usage and optimization results

## Support

For technical assistance:
1. Check WordPress error logs
2. Verify file permissions
3. Test with minimal plugin setup
4. Review database table structure
5. Contact development team with specific error messages

This integration provides a solid foundation for advanced jury management while maintaining the reliability and functionality of your existing Mobility Trailblazers plugin. 