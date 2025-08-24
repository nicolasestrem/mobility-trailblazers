# Mobility Trailblazers - Playwright Test Suite

Comprehensive end-to-end testing suite for the Mobility Trailblazers WordPress plugin using Playwright.

## Overview

This test suite provides comprehensive coverage for the Mobility Trailblazers plugin (v2.5.40), which manages 490+ candidates, 24 jury members, evaluations, and the complete award selection process for the October 30, 2025 award ceremony.

## Test Structure

### Test Categories

1. **Authentication & Login** (`auth-login.spec.ts`, `auth.setup.ts`)
   - WordPress admin login
   - User role access control
   - Session management
   - Stored authentication states for reuse
   - AJAX authentication

2. **Navigation** (`navigation.spec.ts`)
   - Admin menu navigation
   - Breadcrumb navigation
   - Mobile navigation
   - Error page handling
   - Multi-language support (English/German)

3. **Jury Evaluation Workflow** (`jury-evaluation.spec.ts`)
   - Evaluation form access
   - Score input and validation
   - Form submission workflow
   - Error handling and recovery
   - Comments removed per Issue #25

4. **Candidate Management** (`candidate-management.spec.ts`)
   - Candidate CRUD operations
   - Meta field management
   - Category assignment
   - Frontend display

5. **Assignment Management** (`assignment-management.spec.ts`, `assignment-management-simple.spec.ts`)
   - Auto-assignment system
   - Manual assignment interface
   - Assignment table management
   - Bulk operations
   - Statistics dashboard

6. **Import/Export** (`import-export.spec.ts`)
   - CSV/Excel import functionality
   - Export format options
   - Data validation
   - Error handling

7. **Responsive Design & Accessibility** (`responsive-accessibility.spec.ts`)
   - Mobile responsiveness
   - WCAG compliance
   - Screen reader support
   - Cross-browser compatibility

8. **Database Tests** (`database-tables.spec.ts`) ✅ NEW
   - Custom table structure verification
   - wp_mt_evaluations table integrity
   - wp_mt_jury_assignments constraints
   - wp_mt_audit_log functionality
   - wp_mt_error_log verification
   - Index performance checks

9. **Translation Tests** (`german-translations.spec.ts`) ✅ NEW
   - German .po/.mo file verification
   - UI translation testing
   - Label consistency checks
   - Multi-language support validation

10. **Performance Tests** (`performance-load.spec.ts`) ✅ NEW
    - Load testing for 490+ candidates
    - Page load time benchmarks
    - Concurrent user simulation
    - Resource usage monitoring
    - AJAX response time testing

11. **Security Tests** (`security-vulnerabilities.spec.ts`) ✅ NEW
    - SQL injection prevention
    - XSS vulnerability checks
    - CSRF token validation
    - Nonce verification testing
    - Input sanitization validation

12. **Elementor Widget Tests** (`elementor-widgets.spec.ts`) ✅ NEW
    - Widget registration verification
    - Shortcode functionality
    - Preview rendering tests
    - Widget settings validation

13. **Debug Center Tests** (`debug-center-admin.spec.ts`) ✅ NEW
    - Debug Center accessibility
    - System information display
    - Error log viewing
    - Database health checks
    - Activity monitoring

## Test Environments

### Local Development
```bash
npm run test:local
```
- Base URL: `http://localhost`
- Verbose logging and debugging
- Slower timeouts for development

### Staging
```bash
npm run test:staging
```
- Base URL: `http://localhost:8080`
- Docker environment testing
- Production-like conditions

### Production (Read-only)
```bash
npm run test:production
```
- Base URL: `https://mobilitytrailblazers.de`
- Only safe, read-only tests
- Performance and accessibility checks

## Setup and Installation

### Prerequisites
- Node.js 16+
- WordPress installation with Mobility Trailblazers plugin
- Test data (candidates, jury members, assignments)

### Installation
```bash
# Install dependencies
npm install

# Install Playwright browsers
npm run test:install

# Run tests
npm test
```

### Environment Variables

Test credentials are configured in `tests/.env.test`:

```env
# Admin credentials
ADMIN_USERNAME=testadmin
ADMIN_PASSWORD=testadmin123

# Jury member credentials
JURY_USERNAME=jurymember1
JURY_PASSWORD=JuryTest123!

# Jury admin credentials
JURY_ADMIN_USERNAME=juryadmin
JURY_ADMIN_PASSWORD=JuryAdmin123!
```

## Test Configuration

### Browsers Tested
- **Desktop**: Chrome, Firefox, Safari
- **Mobile**: Chrome Mobile, Safari Mobile
- **Tablet**: iPad Pro

### Authentication States
Tests use persistent authentication states stored in `tests/.auth/`:
- `admin.json` - WordPress administrator
- `jury.json` - Jury member with evaluation permissions
- `jury-admin.json` - Jury administrator with assignment permissions

## Test Data Management

### Test Fixtures
Located in `tests/fixtures/test-data.ts`:
- Sample candidates (5 test profiles)
- Sample jury members (3 test users)
- Evaluation data generators

### Test Data Creation
The global setup automatically creates test data if needed:
```typescript
import { createTestData } from './fixtures/test-data';
await createTestData(page, baseURL);
```

### Test Data Cleanup
```typescript
import { cleanupTestData } from './fixtures/test-data';
await cleanupTestData(page, baseURL);
```

## Test Utilities

### Helper Classes
Located in `tests/utils/test-helpers.ts`:

- **WordPressAdmin** - Admin interface navigation
- **JuryDashboard** - Jury dashboard interactions
- **EvaluationForm** - Evaluation form handling
- **AssignmentManager** - Assignment management
- **AjaxHelper** - AJAX request monitoring
- **ResponsiveHelper** - Multi-device testing
- **AccessibilityHelper** - WCAG compliance checks

### Example Usage
```typescript
import { JuryDashboard, EvaluationForm } from './utils/test-helpers';

test('jury can complete evaluation', async ({ page }) => {
  const jury = new JuryDashboard(page);
  const evaluation = new EvaluationForm(page);
  
  await jury.navigate();
  await jury.clickEvaluateButton('candidate-1');
  
  await evaluation.fillEvaluation({
    criterion1: 8.5,
    criterion2: 9.0,
    criterion3: 7.5,
    criterion4: 8.0,
    criterion5: 9.5,
    comments: 'Excellent innovation'
  });
  
  await evaluation.submitEvaluation();
});
```

## Running Tests

### All Tests
```bash
npm test
```

### Specific Test Files
```bash
npx playwright test auth-login.spec.ts
npx playwright test jury-evaluation.spec.ts --headed
```

### With UI Mode
```bash
npm run test:ui
```

### Debug Mode
```bash
npm run test:debug
```

### Specific Browser
```bash
npx playwright test --project=chromium
npx playwright test --project="Mobile Chrome"
```

## Test Reports

### HTML Report
```bash
npm run test:report
```
Generated in `test-results/html-report/`

### Test Results
- **HTML Report**: Visual test results with screenshots
- **JUnit XML**: For CI integration (`test-results/junit.xml`)
- **JSON Report**: Machine-readable results (`test-results/results.json`)

## Continuous Integration

### GitHub Actions Integration
The test suite is designed for CI environments:

```yaml
- name: Run Playwright tests
  run: |
    npm ci
    npx playwright install
    npm run test:staging
```

### Docker Integration
Tests can run against Docker environments:
```bash
docker-compose up -d
npm run test:staging
```

## Troubleshooting

### Common Issues

1. **Authentication Failures**
   - Verify credentials in `tests/.env.test` file
   - Check WordPress user roles and capabilities
   - Ensure nonce verification is working
   - Run `npx playwright test auth.setup.ts` to regenerate auth states

2. **Test Data Issues**
   - Run test data creation manually
   - Verify database connections
   - Check for existing test data conflicts

3. **Timeout Issues**
   - Increase timeout values in config
   - Check network connectivity
   - Verify WordPress is responding

4. **Mobile Test Failures**
   - Note: WordPress admin is not fully responsive
   - Tests use stored authentication to avoid login issues
   - Check viewport configuration
   - Test touch interactions manually

5. **Language Issues**
   - Tests support both English and German interfaces
   - Check WordPress locale settings
   - Verify .mo files are compiled from .po files

### Debug Information

Enable verbose logging:
```bash
DEBUG=pw:api npm test
```

Capture screenshots on failure:
```bash
npx playwright test --screenshot=only-on-failure
```

Record video of test runs:
```bash
npx playwright test --video=retain-on-failure
```

## Best Practices

### Test Organization
- Group related tests in describe blocks
- Use descriptive test names
- Keep tests independent and atomic
- Use page object patterns for complex interactions

### Performance
- Use parallel execution where possible
- Minimize test data setup/teardown
- Cache authentication states
- Use selective test running for development

### Maintenance
- Update test data regularly
- Review and update selectors as UI changes
- Monitor test flakiness and investigate failures
- Keep test documentation current

## Contributing

### Adding New Tests
1. Follow existing test structure and naming conventions
2. Use helper classes for common operations
3. Include proper error handling and cleanup
4. Test across multiple browsers and devices
5. Document complex test scenarios

### Test Review Checklist
- [ ] Tests cover happy path and error conditions
- [ ] Mobile responsiveness tested
- [ ] Accessibility requirements checked
- [ ] Performance implications considered
- [ ] Cross-browser compatibility verified
- [ ] Test data properly managed
- [ ] Documentation updated

## Security Considerations

### Test Data Security
- Never commit real user credentials
- Use test-specific data only
- Clean up sensitive test data after runs
- Restrict production test access

### Safe Testing Practices
- Use dry-run modes where available
- Test against staging environments
- Avoid modifying production data
- Implement proper test isolation

## WordPress Integration

### Plugin Compatibility
Tests are designed to work with:
- WordPress 5.8+
- Mobility Trailblazers plugin v2.5.40+
- Standard WordPress admin interface
- Custom post types (mt_candidate, mt_jury_member)
- Custom database tables:
  - wp_mt_evaluations (evaluation scores and comments)
  - wp_mt_jury_assignments (jury-candidate mappings)
  - wp_mt_audit_log (activity tracking)
  - wp_mt_error_log (error logging)
- German localization (de_DE)

### Database Testing
- Tests use WordPress test database
- Automatic table creation and cleanup
- Transaction-based test isolation where possible
- Custom table structure verification
- Data integrity and constraint testing

## Performance Benchmarks

### Expected Load Times
- Admin pages: < 3 seconds
- Jury dashboard: < 2 seconds
- Evaluation form: < 2 seconds
- Import/export: < 5 seconds (small files)

### Resource Usage
- Memory: < 512MB during test execution
- Network: Minimal external requests
- Storage: < 100MB for test artifacts

## Test Coverage Status

### ✅ Completed (All 7 requested tasks)
1. ✅ Authentication Setup - Updated with correct credentials
2. ✅ Database Tests - Custom tables verification
3. ✅ Translation Tests - German .po/.mo validation
4. ✅ Performance Tests - 490+ candidates load testing
5. ✅ Security Tests - SQL injection, XSS prevention
6. ✅ Elementor Widget Tests - Shortcode integration
7. ✅ Debug Center Tests - Admin debugging tools

### Known Limitations
- Mobile/tablet tests may have issues with WordPress admin interface (not fully responsive)
- Tests use stored authentication states to avoid repeated login issues
- Language support includes both English and German interfaces

## Future Enhancements

### Planned Additions
- Visual regression testing
- API endpoint testing
- Load testing for high-volume scenarios
- Integration with external services
- Automated accessibility scanning

### Monitoring Integration
- Performance monitoring alerts
- Failure notification systems
- Test result analytics
- Automated reporting dashboards

---

For additional support or questions about the test suite, refer to the main plugin documentation in `/doc/` or contact the development team.