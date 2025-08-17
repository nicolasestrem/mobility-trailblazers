<?php
/**
 * PHPUnit Bootstrap File
 *
 * @package MobilityTrailblazers\Tests
 */

// Define test environment
define('MT_TESTING', true);
define('WP_DEBUG', true);

// Get WordPress tests directory
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    // Try to find it in common locations
    $possible_paths = [
        '/tmp/wordpress-tests-lib',
        '../../../wordpress-tests-lib',
        '../../../../tests/phpunit',
        '/usr/local/src/wordpress-tests-lib',
    ];

    foreach ($possible_paths as $path) {
        if (file_exists($path . '/includes/functions.php')) {
            $_tests_dir = $path;
            break;
        }
    }
}

if (!$_tests_dir || !file_exists($_tests_dir . '/includes/functions.php')) {
    echo "Could not find WordPress test library. Please set WP_TESTS_DIR environment variable.\n";
    exit(1);
}

// Give access to tests_add_filter() function
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested
 */
function _manually_load_plugin() {
    require dirname(dirname(__FILE__)) . '/mobility-trailblazers.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment
require $_tests_dir . '/includes/bootstrap.php';

// Load test helpers
require_once dirname(__FILE__) . '/helpers/class-mt-test-case.php';
require_once dirname(__FILE__) . '/helpers/class-mt-test-factory.php';
require_once dirname(__FILE__) . '/helpers/trait-mt-test-helpers.php';

// Activate plugin
activate_plugin('mobility-trailblazers/mobility-trailblazers.php');

echo "Mobility Trailblazers Test Suite Bootstrap Complete\n\n";