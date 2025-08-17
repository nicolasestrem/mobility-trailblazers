<?php
/**
 * Test Helper Traits
 *
 * @package MobilityTrailblazers\Tests
 */

namespace MobilityTrailblazers\Tests;

/**
 * Common test helper methods
 */
trait MT_Test_Helpers {

    /**
     * Assert that a value is a valid email
     *
     * @param string $email Email to test
     * @param string $message Optional message
     */
    protected function assertValidEmail($email, $message = '') {
        $this->assertTrue(
            filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
            $message ?: "'{$email}' should be a valid email address"
        );
    }

    /**
     * Assert that a value is a valid URL
     *
     * @param string $url URL to test
     * @param string $message Optional message
     */
    protected function assertValidUrl($url, $message = '') {
        $this->assertTrue(
            filter_var($url, FILTER_VALIDATE_URL) !== false,
            $message ?: "'{$url}' should be a valid URL"
        );
    }

    /**
     * Assert that a database table exists
     *
     * @param string $table Table name (without prefix)
     * @param string $message Optional message
     */
    protected function assertTableExists($table, $message = '') {
        global $wpdb;
        
        $full_table = $wpdb->prefix . $table;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") === $full_table;
        
        $this->assertTrue($exists, $message ?: "Table '{$full_table}' should exist");
    }

    /**
     * Assert that a database table has specific columns
     *
     * @param string $table Table name (without prefix)
     * @param array $columns Expected column names
     * @param string $message Optional message
     */
    protected function assertTableHasColumns($table, $columns, $message = '') {
        global $wpdb;
        
        $full_table = $wpdb->prefix . $table;
        $actual_columns = $wpdb->get_col("DESCRIBE {$full_table}", 0);
        
        foreach ($columns as $column) {
            $this->assertContains(
                $column,
                $actual_columns,
                $message ?: "Table '{$full_table}' should have column '{$column}'"
            );
        }
    }

    /**
     * Assert that a capability exists
     *
     * @param string $cap Capability name
     * @param string $message Optional message
     */
    protected function assertCapabilityExists($cap, $message = '') {
        $roles = wp_roles();
        $exists = false;
        
        foreach ($roles->roles as $role) {
            if (isset($role['capabilities'][$cap])) {
                $exists = true;
                break;
            }
        }
        
        $this->assertTrue($exists, $message ?: "Capability '{$cap}' should exist");
    }

    /**
     * Assert that a shortcode is registered
     *
     * @param string $shortcode Shortcode tag
     * @param string $message Optional message
     */
    protected function assertShortcodeExists($shortcode, $message = '') {
        $this->assertTrue(
            shortcode_exists($shortcode),
            $message ?: "Shortcode '[{$shortcode}]' should be registered"
        );
    }

    /**
     * Assert that an action is registered
     *
     * @param string $action Action name
     * @param string $message Optional message
     */
    protected function assertActionExists($action, $message = '') {
        $this->assertTrue(
            has_action($action),
            $message ?: "Action '{$action}' should be registered"
        );
    }

    /**
     * Assert that a filter is registered
     *
     * @param string $filter Filter name
     * @param string $message Optional message
     */
    protected function assertFilterExists($filter, $message = '') {
        $this->assertTrue(
            has_filter($filter),
            $message ?: "Filter '{$filter}' should be registered"
        );
    }

    /**
     * Assert that a post meta exists
     *
     * @param int $post_id Post ID
     * @param string $meta_key Meta key
     * @param string $message Optional message
     */
    protected function assertPostMetaExists($post_id, $meta_key, $message = '') {
        $this->assertTrue(
            metadata_exists('post', $post_id, $meta_key),
            $message ?: "Post meta '{$meta_key}' should exist for post {$post_id}"
        );
    }

    /**
     * Assert that a user meta exists
     *
     * @param int $user_id User ID
     * @param string $meta_key Meta key
     * @param string $message Optional message
     */
    protected function assertUserMetaExists($user_id, $meta_key, $message = '') {
        $this->assertTrue(
            metadata_exists('user', $user_id, $meta_key),
            $message ?: "User meta '{$meta_key}' should exist for user {$user_id}"
        );
    }

    /**
     * Assert that an option exists
     *
     * @param string $option Option name
     * @param string $message Optional message
     */
    protected function assertOptionExists($option, $message = '') {
        $this->assertNotFalse(
            get_option($option),
            $message ?: "Option '{$option}' should exist"
        );
    }

    /**
     * Assert that a transient exists
     *
     * @param string $transient Transient name
     * @param string $message Optional message
     */
    protected function assertTransientExists($transient, $message = '') {
        $this->assertNotFalse(
            get_transient($transient),
            $message ?: "Transient '{$transient}' should exist"
        );
    }

    /**
     * Assert that a nonce is valid
     *
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @param string $message Optional message
     */
    protected function assertValidNonce($nonce, $action, $message = '') {
        $this->assertNotFalse(
            wp_verify_nonce($nonce, $action),
            $message ?: "Nonce should be valid for action '{$action}'"
        );
    }

    /**
     * Assert array structure
     *
     * @param array $expected Expected keys
     * @param array $actual Actual array
     * @param string $message Optional message
     */
    protected function assertArrayStructure($expected, $actual, $message = '') {
        foreach ($expected as $key) {
            $this->assertArrayHasKey(
                $key,
                $actual,
                $message ?: "Array should have key '{$key}'"
            );
        }
    }

    /**
     * Assert evaluation scores are valid
     *
     * @param array $evaluation Evaluation data
     * @param string $message Optional message
     */
    protected function assertValidEvaluationScores($evaluation, $message = '') {
        for ($i = 1; $i <= 5; $i++) {
            $key = 'criterion_' . $i;
            $this->assertArrayHasKey($key, $evaluation, "Evaluation should have {$key}");
            $this->assertGreaterThanOrEqual(0, $evaluation[$key], "{$key} should be >= 0");
            $this->assertLessThanOrEqual(100, $evaluation[$key], "{$key} should be <= 100");
        }
    }

    /**
     * Get test file path
     *
     * @param string $filename Filename
     * @return string Full path
     */
    protected function get_test_file($filename) {
        return dirname(dirname(__FILE__)) . '/fixtures/' . $filename;
    }

    /**
     * Create test CSV file
     *
     * @param array $data Data rows
     * @param string $filename Filename
     * @return string File path
     */
    protected function create_test_csv($data, $filename = 'test.csv') {
        $file = $this->get_test_file($filename);
        $handle = fopen($file, 'w');
        
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        return $file;
    }

    /**
     * Clean up test files
     *
     * @param string $pattern File pattern
     */
    protected function cleanup_test_files($pattern = '*.csv') {
        $files = glob($this->get_test_file($pattern));
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Measure execution time
     *
     * @param callable $callback Function to measure
     * @return array Result and time
     */
    protected function measure_time($callback) {
        $start = microtime(true);
        $result = $callback();
        $time = microtime(true) - $start;
        
        return [
            'result' => $result,
            'time' => $time,
            'time_ms' => round($time * 1000, 2)
        ];
    }

    /**
     * Measure memory usage
     *
     * @param callable $callback Function to measure
     * @return array Result and memory
     */
    protected function measure_memory($callback) {
        $start = memory_get_usage();
        $result = $callback();
        $memory = memory_get_usage() - $start;
        
        return [
            'result' => $result,
            'memory' => $memory,
            'memory_mb' => round($memory / 1024 / 1024, 2)
        ];
    }
}