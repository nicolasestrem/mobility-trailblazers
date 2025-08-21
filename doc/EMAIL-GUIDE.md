# Email Functionality Guide

## Current State (v2.5.38)

As of version 2.5.38, the Mobility Trailblazers plugin has **removed all custom email notification services**. This was done to simplify the codebase and rely on WordPress core functionality.

## What Was Removed

### Completely Removed Features
- `MT_Email_Service` class and all related infrastructure
- Email templates in `templates/emails/` directory
- Scheduled email reminders
- Bulk email operations
- Evaluation reminder emails
- Dashboard coaching email features
- Custom notification system

### Migration Notes
If you had custom email workflows, they need to be reimplemented using:
- WordPress core functions
- Third-party email plugins (e.g., WP Mail SMTP)
- External email services via API

## Current Email Capabilities

### 1. WordPress Welcome Emails
When creating jury members via import, the system uses WordPress's native welcome email:

```php
// In MT_Import_Handler::import_jury_members()
wp_new_user_notification($user_id, null, 'both');
```

This sends:
- Admin notification about new user
- Welcome email to user with login credentials

### 2. Email Data Storage
Email addresses are stored for:

**Jury Members:**
```php
// User email (WordPress user table)
$user_data['user_email'] = $email;

// Also stored as post meta
update_post_meta($post_id, '_mt_jury_email', $email);
```

**Candidates:**
```php
// Stored as post meta only (candidates are not users)
update_post_meta($post_id, '_mt_candidate_email', $email);
```

### 3. Email Validation
Email validation occurs during:

```php
// Import validation
if (!is_email($mapped_data['email'])) {
    throw new Exception(__('Invalid email address', 'mobility-trailblazers'));
}

// Form validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return new WP_Error('invalid_email', __('Invalid email format', 'mobility-trailblazers'));
}
```

## Alternative Communication Methods

Since custom emails are removed, consider these alternatives:

### 1. Admin Notices
Use WordPress admin notices for important messages:
```php
add_action('admin_notices', function() {
    ?>
    <div class="notice notice-info is-dismissible">
        <p><?php _e('Evaluations are due by October 30th', 'mobility-trailblazers'); ?></p>
    </div>
    <?php
});
```

### 2. Dashboard Widgets
Display important information on the jury dashboard:
```php
// Already implemented in jury dashboard
$completion_rate = $evaluation_service->get_completion_rate($jury_id);
if ($completion_rate < 100) {
    echo '<div class="mt-reminder">' . __('Please complete all evaluations', 'mobility-trailblazers') . '</div>';
}
```

### 3. External Email Services
For complex email needs, integrate with:
- **SendGrid** - Via API
- **Mailchimp** - For newsletters
- **WP Mail SMTP** - Enhanced email delivery
- **FluentCRM** - WordPress CRM with email

## Restoring Email Functionality

If you need to restore email capabilities:

### Option 1: Use WordPress Functions
```php
// Simple email
wp_mail(
    $to,
    __('Evaluation Reminder', 'mobility-trailblazers'),
    __('Please complete your evaluations', 'mobility-trailblazers'),
    ['Content-Type: text/html; charset=UTF-8']
);
```

### Option 2: Create Minimal Service
```php
class MT_Simple_Email {
    public static function send_reminder($user_id, $message) {
        $user = get_userdata($user_id);
        if (!$user) return false;
        
        return wp_mail(
            $user->user_email,
            get_option('blogname') . ' - ' . __('Reminder', 'mobility-trailblazers'),
            $message,
            ['Content-Type: text/html; charset=UTF-8']
        );
    }
}
```

### Option 3: Use Action Hooks
```php
// Hook into evaluation events
add_action('mt_evaluation_saved', function($evaluation_id, $jury_id) {
    // Send confirmation using preferred method
    do_action('mt_send_notification', 'evaluation_saved', [
        'evaluation_id' => $evaluation_id,
        'jury_id' => $jury_id
    ]);
}, 10, 2);
```

## Database References

### Tables with Email Fields
```sql
-- WordPress Users table
wp_users.user_email

-- Post Meta
wp_postmeta WHERE meta_key = '_mt_jury_email'
wp_postmeta WHERE meta_key = '_mt_candidate_email'
```

### Cleanup Queries
```sql
-- Remove orphaned email meta
DELETE FROM wp_postmeta 
WHERE meta_key IN ('_mt_email_sent', '_mt_reminder_count', '_mt_last_email_date');

-- Find jury members without email
SELECT p.ID, p.post_title 
FROM wp_posts p
LEFT JOIN wp_postmeta pm ON p.ID = pm.post_id AND pm.meta_key = '_mt_jury_email'
WHERE p.post_type = 'mt_jury_member' 
AND pm.meta_value IS NULL;
```

## Best Practices

1. **Use WordPress Core Functions** - Leverage `wp_mail()` for simple needs
2. **Validate Email Addresses** - Always use `is_email()` or `filter_var()`
3. **Store Emails Consistently** - Use post meta for custom post types
4. **Consider Privacy** - Follow GDPR guidelines for email storage
5. **Log Important Events** - Track when emails would have been sent

## Frequently Asked Questions

### Q: Why were email services removed?
A: To simplify the codebase, reduce maintenance overhead, and avoid email deliverability issues.

### Q: Can I send evaluation reminders?
A: Not automatically. You'll need to implement this using WordPress cron and `wp_mail()` or use an external service.

### Q: What about the email templates?
A: The `templates/emails/` directory exists but is empty. Create your own templates if needed.

### Q: How do I notify jury members?
A: Use the WordPress admin interface to communicate, or integrate with an email marketing service.

## Related Documentation
- [Import/Export Guide](IMPORT-EXPORT-GUIDE.md) - Email handling during imports
- [Developer Guide](developer-guide.md) - Technical architecture
- [CHANGELOG](CHANGELOG.md) - Version history including email removal