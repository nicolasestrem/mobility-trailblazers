# Security Setup Guide - Mobility Trailblazers Plugin

## Quick Start (No Installation Required)

If you just want to run a basic security scan without installing anything:

```bash
run-manual-scan.bat
```

This will run a PHP-based scanner that checks for common security issues.

## Full Security Setup

### Prerequisites

1. **PHP** (required)
   - Download from: https://windows.php.net/download/
   - Add to your system PATH

2. **Composer** (required for full scanning)
   - Download from: https://getcomposer.org/download/
   - Run the Windows installer

### Installation Steps

1. **Install Security Tools**:
   ```bash
   install-and-scan.bat
   ```
   This will:
   - Install PHP CodeSniffer
   - Install WordPress Coding Standards
   - Run comprehensive security scans
   - Save reports to `security-reports/` folder

2. **Alternative: Manual Installation**:
   ```bash
   composer install
   ```

## Available Security Scans

### 1. Manual Scanner (No Dependencies)
```bash
run-manual-scan.bat
```
Checks for:
- Unescaped output
- Missing nonce verification
- SQL injection risks
- Unsanitized input
- Direct file access

### 2. PHP CodeSniffer Scans (After Installation)

**Full Security Scan**:
```bash
composer run security-scan
```

**Check Nonce Verification**:
```bash
composer run check-nonce
```

**Check Output Escaping**:
```bash
composer run check-escaping
```

**Check SQL Queries**:
```bash
composer run check-sql
```

**Auto-fix Issues** (where possible):
```bash
composer run fix-security
```

## Security Reports

All scan reports are saved in the `security-reports/` folder:

- `manual-scan-[timestamp].json` - Manual scanner results
- `general-security.txt` - Overall security issues
- `nonce-verification.txt` - Nonce verification issues
- `output-escaping.txt` - Unescaped output issues
- `sql-injection.txt` - SQL injection risks
- `full-report.json` - Complete detailed report

## Security Issues Already Fixed

The following security issues have already been addressed in version 2.0.14:

### Fixed Files:
1. **templates/admin/dashboard.php** - Added proper output escaping
2. **templates/admin/assignments.php** - Added escaping and debug check
3. **includes/ajax/class-mt-evaluation-ajax.php** - Enhanced nonce verification
4. **includes/admin/class-mt-admin.php** - Fixed input sanitization

### Security Measures Implemented:
- ✅ All output is escaped using WordPress functions
- ✅ All AJAX endpoints verify nonces
- ✅ All user input is sanitized
- ✅ All database queries use prepared statements
- ✅ All files check for direct access
- ✅ Debug code only runs when WP_DEBUG is true

## Troubleshooting

### "PHP is not installed"
- Download PHP from https://windows.php.net/download/
- Extract to C:\php
- Add C:\php to your system PATH
- Restart command prompt

### "Composer is not installed"
- Download Composer from https://getcomposer.org/download/
- Run the installer
- Restart command prompt

### "vendor/bin/phpcs not found"
- Run `composer install` in the plugin directory
- Or use `install-and-scan.bat`

## Security Best Practices

1. **Always escape output**: Use `esc_html()`, `esc_attr()`, `esc_url()`, `esc_js()`
2. **Always verify nonces**: Use `wp_verify_nonce()` or `check_ajax_referer()`
3. **Always sanitize input**: Use `sanitize_text_field()`, `absint()`, etc.
4. **Always use prepared statements**: Use `$wpdb->prepare()` for SQL queries
5. **Always check capabilities**: Use `current_user_can()` before sensitive operations

## Support

For security concerns or vulnerabilities, please contact:
- Plugin Author: Nicolas Estrem
- Repository: [Your repository URL]

## Version History

- **2.0.14** - Security audit and fixes applied (August 8, 2025)
- **2.0.13** - Previous version before security audit
