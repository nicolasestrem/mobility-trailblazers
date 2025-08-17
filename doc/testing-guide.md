# Mobility Trailblazers Testing Guide
**Version:** 2.5.8  
**Last Updated:** August 17, 2025

## Table of Contents
1. [Testing Infrastructure](#testing-infrastructure)
2. [Running Tests](#running-tests)
3. [Writing Tests](#writing-tests)
4. [Test Coverage](#test-coverage)
5. [Debugging](#debugging)
6. [Continuous Integration](#continuous-integration)

## Testing Infrastructure

### Directory Structure
```
tests/
├── unit/                    # Unit tests
│   ├── Core/               # Core functionality tests
│   └── Services/           # Service layer tests
├── integration/            # Integration tests
├── e2e/                   # End-to-end tests
├── fixtures/              # Test data files
├── helpers/               # Test utilities
│   ├── class-mt-test-case.php
│   ├── class-mt-test-factory.php
│   └── trait-mt-test-helpers.php
├── bootstrap.php          # Test environment setup
└── coverage/              # Coverage reports (generated)
```

### Configuration Files
- **phpunit.xml** - PHPUnit configuration with test suites
- **tests/bootstrap.php** - WordPress test environment initialization

## Running Tests

### Prerequisites
```bash
# Install PHPUnit
composer require --dev phpunit/phpunit ^9.5

# Install WordPress test library
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Execute Tests
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite "Unit Tests"
vendor/bin/phpunit --testsuite "Integration Tests"

# Run specific test file
vendor/bin/phpunit tests/unit/Core/PluginTest.php

# Run specific test method
vendor/bin/phpunit --filter test_plugin_constants_defined

# Run with coverage report
vendor/bin/phpunit --coverage-html tests/coverage
vendor/bin/phpunit --coverage-text
```

### Docker Testing Environment
```bash
# Run tests in Docker container
docker exec mobility_wordpress_dev bash -c "cd /var/www/html/wp-content/plugins/mobility-trailblazers && vendor/bin/phpunit"

# With coverage
docker exec mobility_wordpress_dev bash -c "cd /var/www/html/wp-content/plugins/mobility-trailblazers && vendor/bin/phpunit --coverage-text"
```

## Writing Tests

### Basic Test Structure
```php
<?php
namespace MobilityTrailblazers\Tests\Unit;

use MobilityTrailblazers\Tests\MT_Test_Case;

class ExampleTest extends MT_Test_Case {
    
    public function setUp(): void {
        parent::setUp();
        // Test-specific setup
    }
    
    public function test_example_functionality() {
        // Arrange
        $expected = 'expected_value';
        
        // Act
        $actual = some_function();
        
        // Assert
        $this->assertEquals($expected, $actual);
    }
    
    public function tearDown(): void {
        // Test-specific cleanup
        parent::tearDown();
    }
}
```

### Using Test Factory
```php
// Create test candidates
$candidates = MT_Test_Factory::create_candidates(5);

// Create test jury members
$jury_members = MT_Test_Factory::create_jury_members(3);

// Generate mock data
$evaluation_data = MT_Test_Factory::evaluation([
    'criterion_1' => 85,
    'status' => 'submitted'
]);

// Create CSV test data
$csv_data = MT_Test_Factory::csv_data('candidates', 100);
```

### Custom Assertions
```php
// WordPress-specific assertions
$this->assertTableExists('mt_evaluations');
$this->assertTableHasColumns('mt_evaluations', ['id', 'jury_member_id']);
$this->assertCapabilityExists('mt_submit_evaluations');
$this->assertShortcodeExists('mt_jury_dashboard');
$this->assertActionExists('wp_ajax_mt_save_evaluation');

// Validation assertions
$this->assertValidEmail($email);
$this->assertValidUrl($url);
$this->assertValidNonce($nonce, $action);
$this->assertValidEvaluationScores($evaluation);

// AJAX assertions
$this->assertAjaxSuccess($response);
$this->assertAjaxError($response);
```

### Testing AJAX Handlers
```php
public function test_ajax_evaluation_submission() {
    // Login as jury member
    $this->login_as_jury();
    
    // Mock AJAX request
    $response = $this->mock_ajax_request('mt_save_evaluation', [
        'candidate_id' => $this->candidate_id,
        'criterion_1' => 80,
        'criterion_2' => 75,
        'criterion_3' => 85,
        'criterion_4' => 90,
        'criterion_5' => 70,
        'status' => 'submitted'
    ]);
    
    // Assert response
    $this->assertAjaxSuccess($response);
    $this->assertArrayHasKey('evaluation_id', $response['data']);
}
```

### Testing Database Operations
```php
public function test_database_operations() {
    // Create test data
    $jury_id = $this->create_test_jury_member();
    $candidate_id = $this->create_test_candidate();
    
    // Create assignment
    $assignment_id = $this->create_test_assignment($jury_id, $candidate_id);
    
    // Create evaluation
    $evaluation_id = $this->create_test_evaluation($jury_id, $candidate_id, [
        'criterion_1' => 80,
        'status' => 'submitted'
    ]);
    
    // Verify in database
    global $wpdb;
    $result = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}mt_evaluations WHERE id = %d",
        $evaluation_id
    ));
    
    $this->assertNotNull($result);
    $this->assertEquals(80, $result->criterion_1);
}
```

## Test Coverage

### Coverage Goals
- **Minimum Coverage:** 60%
- **Target Coverage:** 80%
- **Critical Path Coverage:** 100%

### Generating Coverage Reports
```bash
# HTML report
vendor/bin/phpunit --coverage-html tests/coverage

# Text report to console
vendor/bin/phpunit --coverage-text

# Clover XML for CI
vendor/bin/phpunit --coverage-clover coverage.xml
```

### Coverage Analysis
View HTML report: `tests/coverage/index.html`

### Critical Paths to Test
1. **Evaluation Submission**
   - Form validation
   - Score calculation
   - Database storage
   - Email notification

2. **Assignment Management**
   - Auto-assignment algorithm
   - Manual assignment
   - Conflict detection
   - Progress tracking

3. **Import/Export**
   - CSV parsing
   - Data validation
   - Batch processing
   - Error handling

## Debugging

### Debug Mode
```php
// Enable debug mode in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('MT_TESTING', true);
```

### Test Debugging
```bash
# Run tests with verbose output
vendor/bin/phpunit --verbose

# Stop on first failure
vendor/bin/phpunit --stop-on-failure

# Show test execution flow
vendor/bin/phpunit --testdox
```

### Common Issues

#### Issue: Tests Not Finding WordPress
**Solution:** Set `WP_TESTS_DIR` environment variable:
```bash
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
```

#### Issue: Database Connection Failed
**Solution:** Check test database credentials:
```bash
# In tests/bootstrap.php or wp-tests-config.php
define('DB_NAME', 'wordpress_test');
define('DB_USER', 'root');
define('DB_PASSWORD', '');
define('DB_HOST', 'localhost');
```

#### Issue: Class Not Found
**Solution:** Ensure autoloader is loaded:
```php
// In tests/bootstrap.php
require_once dirname(dirname(__FILE__)) . '/includes/core/class-mt-autoloader.php';
MT_Autoloader::register();
```

## Continuous Integration

### GitHub Actions Configuration
```yaml
name: PHPUnit Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:5.7
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
    
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.0'
        coverage: xdebug
    
    - name: Install dependencies
      run: composer install
    
    - name: Setup WordPress Tests
      run: |
        bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest
    
    - name: Run tests
      run: vendor/bin/phpunit --coverage-text
```

### Pre-commit Hooks
```bash
#!/bin/bash
# .git/hooks/pre-commit

# Run tests before commit
vendor/bin/phpunit --testsuite "Unit Tests"
if [ $? -ne 0 ]; then
    echo "Tests failed. Commit aborted."
    exit 1
fi
```

## Best Practices

### Test Organization
1. **One test class per class under test**
2. **Group related tests in test methods**
3. **Use descriptive test method names**
4. **Follow AAA pattern** (Arrange, Act, Assert)

### Test Data Management
1. **Use factories for test data generation**
2. **Clean up after each test**
3. **Don't rely on test execution order**
4. **Use transactions for database tests**

### Performance
1. **Mock external services**
2. **Use data providers for parametrized tests**
3. **Minimize database interactions**
4. **Run slow tests separately**

### Maintenance
1. **Keep tests simple and focused**
2. **Update tests when code changes**
3. **Remove obsolete tests**
4. **Document complex test scenarios**

## Test Checklist

### Before Committing
- [ ] All tests pass locally
- [ ] New features have tests
- [ ] Bug fixes include regression tests
- [ ] Coverage hasn't decreased
- [ ] No hardcoded test data

### Before Release
- [ ] Full test suite passes
- [ ] Integration tests complete
- [ ] Manual testing performed
- [ ] Performance benchmarks met
- [ ] Security tests passed

## Resources

### Documentation
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [WordPress Testing Documentation](https://make.wordpress.org/core/handbook/testing/)
- [WP Mock](https://github.com/10up/wp_mock)

### Tools
- **PHPUnit** - Testing framework
- **Mockery** - Mock object framework
- **Faker** - Fake data generator
- **WordPress Test Suite** - WordPress testing utilities

---

*This guide provides comprehensive testing procedures for maintaining high code quality in the Mobility Trailblazers plugin.*