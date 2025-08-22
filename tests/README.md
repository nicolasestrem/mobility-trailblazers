# Mobility Trailblazers - Playwright Test Suite

Comprehensive end-to-end testing suite for the Mobility Trailblazers WordPress plugin using Playwright.

## Overview

This test suite provides comprehensive coverage for the Mobility Trailblazers plugin (v2.5.40), which manages 490+ candidates, 24 jury members, evaluations, and the complete award selection process for the October 30, 2025 award ceremony.

## Test Structure

### Test Categories

1. **Authentication & Login** (`auth-login.spec.ts`)
   - WordPress admin login
   - User role access control
   - Session management
   - AJAX authentication

2. **Navigation** (`navigation.spec.ts`)
   - Admin menu navigation
   - Breadcrumb navigation
   - Mobile navigation
   - Error page handling

3. **Jury Evaluation Workflow** (`jury-evaluation.spec.ts`)
   - Evaluation form access
   - Score input and validation
   - Form submission workflow
   - Error handling and recovery

4. **Candidate Management** (`candidate-management.spec.ts`)
   - Candidate CRUD operations
   - Meta field management
   - Category assignment
   - Frontend display

5. **Assignment Management** (`assignment-management.spec.ts`)
   - Auto-assignment system
   - Manual assignment interface
   - Assignment table management
   - Bulk operations

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

Create a `.env` file in the project root:

```env
# Admin credentials
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your_admin_password

# Jury member credentials
JURY_USERNAME=jury1
JURY_PASSWORD=jury_member_password

# Jury admin credentials
JURY_ADMIN_USERNAME=juryadmin
JURY_ADMIN_PASSWORD=jury_admin_password
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
   - Verify credentials in `.env` file
   - Check WordPress user roles and capabilities
   - Ensure nonce verification is working

2. **Test Data Issues**
   - Run test data creation manually
   - Verify database connections
   - Check for existing test data conflicts

3. **Timeout Issues**
   - Increase timeout values in config
   - Check network connectivity
   - Verify WordPress is responding

4. **Mobile Test Failures**
   - Verify responsive design implementation
   - Check viewport configuration
   - Test touch interactions manually

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
- Custom post types and meta fields

### Database Testing
- Tests use WordPress test database
- Automatic table creation and cleanup
- Transaction-based test isolation where possible

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