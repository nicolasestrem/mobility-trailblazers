# Test Setup Guide - Mobility Trailblazers

## WordPress Test Environment Setup

Since there's currently no admin account, you'll need to set up the test environment first.




 Successfully Committed and Pushed! 🎉

  The comprehensive Playwright E2E test suite has been:
  - ✅ Committed with detailed documentation
  - ✅ Pushed to the main branch on GitHub

  Commit Details:
  - Commit hash: cb32f4f
  - 19 files added
  - 7,273 lines of test code and documentation

  Repository URL: https://github.com/nicolasestrem/mobility-trailblazers

  The test suite is now available in the repository and ready for:
  1. Installation with npm install
  2. Running tests with npm test
  3. Environment-specific testing (local, staging, production)
  4. Continuous integration setup if needed


### Option 1: Create Admin Account via WP-CLI (Recommended)

If you have WP-CLI access:

```bash
# Create admin user
wp user create testadmin admin@test.local --role=administrator --user_pass=TestAdmin123!

# Create jury member user
wp user create jurymember1 jury1@test.local --role=mt_jury_member --user_pass=JuryTest123!

# Create jury admin user
wp user create juryadmin juryadmin@test.local --role=mt_jury_admin --user_pass=JuryAdmin123!
```

### Option 2: Create Admin Account via Database

Direct database method:

```sql
-- Insert admin user
INSERT INTO wp_users (user_login, user_pass, user_nicename, user_email, user_status)
VALUES ('testadmin', MD5('TestAdmin123!'), 'testadmin', 'admin@test.local', 0);

-- Get the user ID
SET @user_id = LAST_INSERT_ID();

-- Set admin capabilities
INSERT INTO wp_usermeta (user_id, meta_key, meta_value)
VALUES (@user_id, 'wp_capabilities', 'a:1:{s:13:"administrator";b:1;}');

INSERT INTO wp_usermeta (user_id, meta_key, meta_value)
VALUES (@user_id, 'wp_user_level', '10');
```

### Option 3: WordPress Installation Reset

If this is a fresh install:

```bash
# Navigate to WordPress directory
cd /path/to/wordpress

# Run WordPress installation
wp core install --url="http://localhost" --title="MT Test Site" --admin_user="testadmin" --admin_password="TestAdmin123!" --admin_email="admin@test.local"
```

### Option 4: Manual WordPress Setup

1. Navigate to `http://localhost/wp-admin/install.php`
2. Follow WordPress setup wizard
3. Create admin account:
   - Username: `testadmin`
   - Password: `TestAdmin123!`
   - Email: `admin@test.local`

## Environment Configuration

### 1. Create `.env` File

Create `.env` in the project root:

```env
# WordPress Admin (you'll create this)
ADMIN_USERNAME=testadmin
ADMIN_PASSWORD=TestAdmin123!

# Jury Member (optional - tests can create this)
JURY_USERNAME=jurymember1
JURY_PASSWORD=JuryTest123!

# Jury Admin (optional - tests can create this)
JURY_ADMIN_USERNAME=juryadmin
JURY_ADMIN_PASSWORD=JuryAdmin123!

# Base URLs for different environments
LOCAL_BASE_URL=http://localhost
STAGING_BASE_URL=http://localhost:8080
PRODUCTION_BASE_URL=https://mobilitytrailblazers.de
```

### 2. Verify Plugin is Active

```bash
# Check if MT plugin is active
wp plugin list | grep mobility-trailblazers

# Activate if needed
wp plugin activate mobility-trailblazers
```

### 3. Create Test Data

The test suite can create its own test data, but you can pre-populate:

```bash
# Create sample candidates
wp post create --post_type=mt_candidate --post_title="Test Candidate 1" --post_status=publish
wp post create --post_type=mt_candidate --post_title="Test Candidate 2" --post_status=publish

# Or run the test data creation script
node tests/create-test-data.js
```

## Test Environment Verification

### 1. Check WordPress Access

```bash
# Test WordPress is accessible
curl -I http://localhost
# Should return 200 OK
```

### 2. Test Admin Login

```bash
# Test admin credentials work
curl -X POST http://localhost/wp-login.php \
  -d "log=testadmin&pwd=TestAdmin123!" \
  -c cookies.txt

# Check if logged in successfully
curl -b cookies.txt http://localhost/wp-admin/ | grep "Dashboard"
```

### 3. Verify Plugin Pages

```bash
# Check MT plugin pages are accessible
curl -b cookies.txt "http://localhost/wp-admin/admin.php?page=mt-assignments"
curl -b cookies.txt "http://localhost/wp-admin/edit.php?post_type=mt_candidate"
```

## Modified Test Configuration

### No-Admin Test Mode

If you prefer to run tests without creating admin accounts, use the no-admin configuration:

```bash
# Run tests in no-admin mode
npm run test:no-admin
```

This will:
- Skip authentication-required tests
- Test only public functionality
- Focus on frontend and basic accessibility
- Test plugin activation/deactivation if possible

### Limited Access Testing

You can test with limited user accounts:

```javascript
// In playwright.config.ts
use: {
  // Skip auth storage if no admin
  storageState: process.env.ADMIN_USERNAME ? 'tests/.auth/admin.json' : undefined,
}
```

## Alternative Test Approaches

### 1. Mock Authentication

```javascript
// Mock WordPress admin for testing
await page.route('**/wp-admin/**', async (route) => {
  // Mock admin interface
  await route.fulfill({
    status: 200,
    contentType: 'text/html',
    body: mockAdminHTML
  });
});
```

### 2. Direct Database Testing

```javascript
// Test database directly without WordPress auth
import { mysql } from './utils/database';

test('database operations work', async () => {
  const candidates = await mysql.query('SELECT * FROM wp_posts WHERE post_type = "mt_candidate"');
  expect(candidates.length).toBeGreaterThan(0);
});
```

### 3. API Testing Only

```javascript
// Test WordPress REST API without admin UI
test('REST API works', async ({ request }) => {
  const response = await request.get('/wp-json/wp/v2/mt_candidate');
  expect(response.ok()).toBeTruthy();
});
```

## Quick Start (No Admin Account)

If you want to run tests immediately without setting up admin:

```bash
# 1. Install dependencies
npm install

# 2. Install Playwright browsers
npx playwright install

# 3. Run public tests only
npm run test:public

# 4. Or run with automatic user creation
npm run test:auto-setup
```

The `test:auto-setup` command will:
1. Check if admin exists
2. Create admin user if needed
3. Create test data
4. Run full test suite
5. Clean up test data after

## Troubleshooting

### WordPress Not Accessible

```bash
# Check if WordPress is running
curl -I http://localhost

# If using Docker
docker-compose ps
docker-compose up -d

# If using XAMPP/LAMP
systemctl status apache2
systemctl status mysql
```

### Database Connection Issues

```bash
# Test database connection
wp db check

# Create database if needed
mysql -u root -p -e "CREATE DATABASE wordpress_test;"
```

### Plugin Not Found

```bash
# Check plugin exists
ls wp-content/plugins/ | grep mobility

# Verify plugin files
ls wp-content/plugins/mobility-trailblazers/

# Check for plugin errors
wp plugin status mobility-trailblazers
```

### User Creation Fails

```bash
# Check user table exists
wp db query "DESCRIBE wp_users;"

# Manually create user
wp user create testadmin admin@test.local --role=administrator --prompt=user_pass
```

## Recommended Setup Flow

1. **Start WordPress** (XAMPP, Docker, or server)
2. **Verify access** to `http://localhost`
3. **Create admin user** (method 1, 2, 3, or 4 above)
4. **Activate MT plugin** if not active
5. **Create `.env` file** with credentials
6. **Run test setup**: `npm run test:install`
7. **Verify with simple test**: `npm run test:auth-only`
8. **Run full suite**: `npm test`

## Test Data Requirements

### Minimum Test Data

- 1 Administrator user
- 1 Jury member user (optional, can be created by tests)
- 3-5 Sample candidates (can be created by tests)
- MT plugin activated and configured

### Optional Test Data

- Multiple jury members
- Existing evaluations
- Assignments between jury and candidates
- Custom categories and meta data

---

**Next Steps**: Choose your preferred setup method and create the admin account, then the test suite will be able to run comprehensive tests of the Mobility Trailblazers plugin.