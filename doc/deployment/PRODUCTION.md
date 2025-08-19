# Production Deployment Guide

## Environment Configuration

The Mobility Trailblazers plugin supports three environments:
- **Development**: Full debugging, all features enabled
- **Staging**: Limited debugging, testing features
- **Production**: Optimized performance, no debug features

## Setting Environment

### Method 1: WordPress Configuration (Recommended)
Add to your `wp-config.php`:
```php
// Set WordPress environment
define('WP_ENVIRONMENT_TYPE', 'production');

// Or explicitly set plugin environment
define('MT_ENVIRONMENT', 'production');
```

### Method 2: Automatic Detection
The plugin automatically detects the environment based on:
1. `MT_ENVIRONMENT` constant (highest priority)
2. `WP_ENVIRONMENT_TYPE` constant
3. `wp_get_environment_type()` function
4. Defaults to 'production' for safety

## Building for Production

### Prerequisites
- PowerShell (Windows) or Bash (Linux/Mac)
- PHP 7.4+
- WordPress 5.8+

### Build Process

#### Windows (PowerShell)
```powershell
# Basic build
.\build-production.ps1

# With options
.\build-production.ps1 -OutputDir ".\dist" -MinifyAssets -CreateZip

# Without vendor dependencies (if already on server)
.\build-production.ps1 -IncludeVendor:$false
```

#### Linux/Mac (Bash)
```bash
# Make script executable
chmod +x build-production.sh

# Run build
./build-production.sh
```

## Production Features

### Disabled in Production:
- Debug menu and tools
- System information display
- Database diagnostics
- Debug scripts execution
- Verbose error logging
- Development AJAX endpoints
- Test data generators
- Migration tools UI

### Enabled in Production:
- Error logging (critical only)
- Performance optimizations
- Asset minification
- Query caching
- CDN support
- Security headers
- Rate limiting
- Automated log cleanup

## Configuration Options

The production configuration is managed by `MT_Config` class:

```php
// Production defaults
[
    'debug_enabled' => false,
    'log_level' => 'ERROR',
    'minify_assets' => true,
    'cache_enabled' => true,
    'cache_expiration' => 7200,
    'security_headers' => true,
    'rate_limiting' => true,
    'enable_cdn' => true,
    'auto_cleanup_logs' => true,
    'log_retention_days' => 7
]
```

## Deployment Checklist

### Pre-deployment:
- [ ] Run build script to create production version
- [ ] Test in staging environment
- [ ] Backup production database
- [ ] Verify all translations are compiled
- [ ] Check PHP version compatibility
- [ ] Review security settings

### Deployment:
- [ ] Upload production build to server
- [ ] Set `WP_ENVIRONMENT_TYPE` to 'production'
- [ ] Clear all caches
- [ ] Verify plugin activation
- [ ] Test core functionality
- [ ] Monitor error logs

### Post-deployment:
- [ ] Verify no debug output visible
- [ ] Check performance metrics
- [ ] Confirm security headers active
- [ ] Test rate limiting
- [ ] Monitor for 24 hours

## Performance Optimizations

### Automatic in Production:
1. **Query Optimization**
   - Cached database queries
   - Optimized batch sizes
   - Reduced query frequency

2. **Asset Optimization**
   - Minified CSS/JS
   - Combined assets
   - CDN delivery

3. **Caching Strategy**
   - 2-hour general cache
   - 10-minute evaluation cache
   - 2-minute voting cache

4. **Resource Management**
   - Increased batch sizes
   - Lazy loading
   - Optimized imports

## Security Considerations

### Production Security Features:
- XSS protection headers
- CSRF token validation
- SQL injection prevention
- Rate limiting (60 requests/minute)
- XML-RPC disabled
- REST API restrictions
- Sanitized error messages

### Monitoring

Monitor these in production:
- PHP error logs: `/wp-content/debug.log`
- Plugin logs: Check `wp_mt_logs` table (ERROR level only)
- Performance: Use Query Monitor plugin
- Security: Check security headers and access logs

## Troubleshooting

### Plugin Not Loading
```php
// Check environment in wp-config.php
var_dump(MT_ENVIRONMENT);
var_dump(WP_ENVIRONMENT_TYPE);
```

### Debug Information Needed
Temporarily enable debugging:
```php
// In wp-config.php (temporary only!)
define('MT_ENVIRONMENT', 'staging');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Performance Issues
1. Check cache settings
2. Verify CDN configuration
3. Review slow query log
4. Check memory usage

## Rollback Procedure

If issues occur:
1. Keep previous version backup
2. Deactivate current plugin
3. Delete plugin folder
4. Upload previous version
5. Reactivate plugin
6. Clear caches

## Version Management

- Development version: Full features, debug enabled
- Production version: Optimized, debug disabled
- Both versions use same database schema
- Configuration determines feature availability

## Support

For production issues:
- Error logs: Check WordPress debug.log
- Plugin logs: ERROR level only in production
- Contact: support@mobility-trailblazers.de
- Documentation: /doc/ directory (development only)

## Important Notes

1. **Never enable debug features in production**
2. **Always test in staging first**
3. **Keep development and production configs separate**
4. **Monitor logs after deployment**
5. **Have rollback plan ready**