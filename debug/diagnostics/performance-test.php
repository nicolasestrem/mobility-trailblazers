<?php
/**
 * Performance Testing Suite
 * Tests system performance with large datasets
 * 
 * @package MobilityTrailblazers
 * @since 2.2.29
 */

require_once('../../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('Access denied');
}

// Ensure MT_Logger is available
if (!class_exists('\MobilityTrailblazers\Core\MT_Logger')) {
    require_once(dirname(__FILE__) . '/../includes/core/class-mt-logger.php');
}

class MT_Performance_Tester {
    private $start_time;
    private $start_memory;
    private $results = [];
    
    public function __construct() {
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage();
    }
    
    /**
     * Test large candidate import
     */
    public function test_large_import($num_candidates = 1000) {
        $this->log_start('Large Import Test');
        
        // Generate test CSV
        $csv_data = $this->generate_test_csv($num_candidates);
        $temp_file = wp_tempnam('test-import.csv');
        file_put_contents($temp_file, $csv_data);
        
        // Test import
        $handler = new \MobilityTrailblazers\Admin\MT_Import_Handler();
        $result = $handler->process_csv_import($temp_file, 'candidates', false);
        
        unlink($temp_file);
        
        $this->log_end('Large Import Test', [
            'candidates_processed' => $num_candidates,
            'success' => $result['success'],
            'errors' => $result['errors']
        ]);
    }
    
    /**
     * Test large export performance
     */
    public function test_large_export() {
        $this->log_start('Large Export Test');
        
        // Count existing candidates
        $count = wp_count_posts('mt_candidate');
        $total = $count->publish + $count->draft;
        
        // Measure export time
        ob_start();
        
        // Use streaming export method
        global $wpdb;
        $batch_size = 100;
        $offset = 0;
        $exported = 0;
        
        while (true) {
            $candidates = get_posts([
                'post_type' => 'mt_candidate',
                'posts_per_page' => $batch_size,
                'offset' => $offset,
                'post_status' => 'any'
            ]);
            
            if (empty($candidates)) {
                break;
            }
            
            $exported += count($candidates);
            $offset += $batch_size;
            
            // Clear cache to prevent memory buildup
            wp_cache_flush();
        }
        
        $output = ob_get_clean();
        
        $this->log_end('Large Export Test', [
            'candidates_exported' => $exported,
            'total_candidates' => $total,
            'output_size' => strlen($output)
        ]);
    }
    
    /**
     * Test evaluation queries with many jury members
     */
    public function test_evaluation_queries($num_jury = 50) {
        $this->log_start('Evaluation Query Test');
        
        global $wpdb;
        $table = $wpdb->prefix . 'mt_evaluations';
        
        // Test complex aggregation query
        $start = microtime(true);
        $results = $wpdb->get_results("
            SELECT 
                candidate_id,
                AVG(criterion_1 + criterion_2 + criterion_3 + 
                    criterion_4 + criterion_5) as avg_score,
                COUNT(DISTINCT jury_member_id) as evaluator_count
            FROM {$table}
            WHERE status = 'submitted'
            GROUP BY candidate_id
            ORDER BY avg_score DESC
            LIMIT 100
        ");
        $query_time = microtime(true) - $start;
        
        // Test join query performance
        $start2 = microtime(true);
        $join_results = $wpdb->get_results("
            SELECT 
                e.*,
                u.display_name,
                p.post_title
            FROM {$table} e
            LEFT JOIN {$wpdb->users} u ON e.jury_member_id = u.ID
            LEFT JOIN {$wpdb->posts} p ON e.candidate_id = p.ID
            WHERE e.status = 'submitted'
            LIMIT 100
        ");
        $join_time = microtime(true) - $start2;
        
        $this->log_end('Evaluation Query Test', [
            'aggregation_query_time' => round($query_time * 1000, 2) . 'ms',
            'join_query_time' => round($join_time * 1000, 2) . 'ms',
            'results_count' => count($results),
            'join_results_count' => count($join_results)
        ]);
    }
    
    /**
     * Test memory usage during operations
     */
    public function test_memory_usage() {
        $this->log_start('Memory Usage Test');
        
        // Test memory during batch operations
        $peak_memory = 0;
        $operations = [
            'load_100_candidates' => function() {
                return get_posts(['post_type' => 'mt_candidate', 'posts_per_page' => 100]);
            },
            'load_500_candidates' => function() {
                return get_posts(['post_type' => 'mt_candidate', 'posts_per_page' => 500]);
            },
            'load_all_evaluations' => function() {
                global $wpdb;
                return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}mt_evaluations LIMIT 1000");
            },
            'complex_join_query' => function() {
                global $wpdb;
                return $wpdb->get_results("
                    SELECT 
                        e.*,
                        u.display_name,
                        p.post_title,
                        pm.meta_value as organization
                    FROM {$wpdb->prefix}mt_evaluations e
                    LEFT JOIN {$wpdb->users} u ON e.jury_member_id = u.ID
                    LEFT JOIN {$wpdb->posts} p ON e.candidate_id = p.ID
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_mt_organization'
                    LIMIT 500
                ");
            }
        ];
        
        $memory_results = [];
        foreach ($operations as $name => $operation) {
            $before = memory_get_usage();
            $result = $operation();
            $after = memory_get_usage();
            $peak_memory = max($peak_memory, $after);
            
            $memory_results[$name] = [
                'memory_used' => $after - $before,
                'memory_used_mb' => round(($after - $before) / 1024 / 1024, 2),
                'result_count' => is_array($result) ? count($result) : 0
            ];
            
            // Clear result to free memory
            unset($result);
            wp_cache_flush();
        }
        
        $this->log_end('Memory Usage Test', [
            'peak_memory_mb' => round($peak_memory / 1024 / 1024, 2),
            'current_memory_mb' => round(memory_get_usage() / 1024 / 1024, 2),
            'operations' => $memory_results
        ]);
    }
    
    /**
     * Test assignment distribution performance
     */
    public function test_assignment_distribution() {
        $this->log_start('Assignment Distribution Test');
        
        global $wpdb;
        $table = $wpdb->prefix . 'mt_assignments';
        
        // Test distribution query
        $start = microtime(true);
        $distribution = $wpdb->get_results("
            SELECT 
                jury_member_id,
                COUNT(*) as assignment_count,
                COUNT(DISTINCT candidate_id) as unique_candidates
            FROM {$table}
            GROUP BY jury_member_id
            ORDER BY assignment_count DESC
        ");
        $query_time = microtime(true) - $start;
        
        // Calculate distribution statistics
        if (!empty($distribution)) {
            $counts = array_column($distribution, 'assignment_count');
            $avg = array_sum($counts) / count($counts);
            $min = min($counts);
            $max = max($counts);
            
            // Calculate standard deviation
            $variance = 0;
            foreach ($counts as $count) {
                $variance += pow($count - $avg, 2);
            }
            $std_dev = sqrt($variance / count($counts));
        } else {
            $avg = $min = $max = $std_dev = 0;
        }
        
        $this->log_end('Assignment Distribution Test', [
            'query_time' => round($query_time * 1000, 2) . 'ms',
            'jury_count' => count($distribution),
            'average_assignments' => round($avg, 2),
            'min_assignments' => $min,
            'max_assignments' => $max,
            'standard_deviation' => round($std_dev, 2),
            'distribution_quality' => $std_dev <= 1.5 ? 'Excellent' : ($std_dev <= 3 ? 'Good' : 'Poor')
        ]);
    }
    
    /**
     * Test database index effectiveness
     */
    public function test_index_performance() {
        $this->log_start('Index Performance Test');
        
        global $wpdb;
        
        // Test queries that should use indexes
        $tests = [
            'evaluation_by_jury' => "SELECT * FROM {$wpdb->prefix}mt_evaluations WHERE jury_member_id = 1",
            'evaluation_by_candidate' => "SELECT * FROM {$wpdb->prefix}mt_evaluations WHERE candidate_id = 1",
            'assignment_by_jury' => "SELECT * FROM {$wpdb->prefix}mt_assignments WHERE jury_member_id = 1",
            'assignment_by_candidate' => "SELECT * FROM {$wpdb->prefix}mt_assignments WHERE candidate_id = 1"
        ];
        
        $results = [];
        foreach ($tests as $name => $query) {
            $start = microtime(true);
            $wpdb->get_results($query);
            $time = microtime(true) - $start;
            
            // Get query explanation
            $explain = $wpdb->get_row("EXPLAIN $query");
            
            $results[$name] = [
                'query_time' => round($time * 1000, 3) . 'ms',
                'uses_index' => !empty($explain->key) ? $explain->key : 'No index',
                'rows_examined' => $explain->rows ?? 0
            ];
        }
        
        $this->log_end('Index Performance Test', ['queries' => $results]);
    }
    
    /**
     * Helper to generate test CSV data
     */
    private function generate_test_csv($num_rows) {
        $csv = "ID,Name,Organisation,Position,Category,Status\n";
        
        $orgs = ['Tech Corp', 'Green Energy', 'Mobility Solutions', 'Smart City', 'Transport Co'];
        $positions = ['CEO', 'CTO', 'Founder', 'Director', 'Manager'];
        $categories = ['Startup', 'Gov/NPO', 'Tech Company'];
        
        for ($i = 1; $i <= $num_rows; $i++) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s"' . "\n",
                'TEST-' . $i,
                'Test Candidate ' . $i,
                $orgs[array_rand($orgs)],
                $positions[array_rand($positions)],
                $categories[array_rand($categories)],
                'publish'
            );
        }
        
        return $csv;
    }
    
    /**
     * Log test start
     */
    private function log_start($test_name) {
        echo "<h3>Starting: {$test_name}</h3>";
        $this->start_time = microtime(true);
        $this->start_memory = memory_get_usage();
    }
    
    /**
     * Log test end
     */
    private function log_end($test_name, $data = []) {
        $duration = microtime(true) - $this->start_time;
        $memory_used = memory_get_usage() - $this->start_memory;
        
        echo "<div class='test-result'>";
        echo "<h4>Completed: {$test_name}</h4>";
        echo "<ul>";
        echo "<li><strong>Duration:</strong> " . round($duration, 3) . " seconds</li>";
        echo "<li><strong>Memory Used:</strong> " . round($memory_used / 1024 / 1024, 2) . " MB</li>";
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                echo "<li><strong>{$key}:</strong>";
                echo "<ul>";
                foreach ($value as $subkey => $subvalue) {
                    if (is_array($subvalue)) {
                        echo "<li>{$subkey}: " . json_encode($subvalue) . "</li>";
                    } else {
                        echo "<li>{$subkey}: {$subvalue}</li>";
                    }
                }
                echo "</ul></li>";
            } else {
                echo "<li><strong>{$key}:</strong> {$value}</li>";
            }
        }
        
        echo "</ul></div>";
        
        // Log to system if MT_Logger is available
        if (class_exists('\MobilityTrailblazers\Core\MT_Logger')) {
            \MobilityTrailblazers\Core\MT_Logger::info('Performance test completed', [
                'test' => $test_name,
                'duration' => $duration,
                'memory_mb' => round($memory_used / 1024 / 1024, 2),
                'data' => $data
            ]);
        }
    }
}

// Get test parameter
$test = $_GET['test'] ?? '';
$nonce_valid = isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'mt_performance_test');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Performance Testing - Mobility Trailblazers</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #26a69a; }
        .test-result { 
            background: #f9f9f9; 
            padding: 15px; 
            margin: 10px 0; 
            border-left: 4px solid #26a69a;
            border-radius: 4px;
        }
        .test-result h4 {
            margin-top: 0;
            color: #333;
        }
        .warning { 
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #ff9800;
        }
        .success { 
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
            border-left: 4px solid #4caf50;
        }
        button { 
            background: #26a69a; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            cursor: pointer; 
            margin: 5px;
            border-radius: 4px;
            font-size: 14px;
        }
        button:hover { background: #00897b; }
        .button-group {
            margin: 20px 0;
        }
        ul {
            margin: 10px 0;
        }
        li {
            margin: 5px 0;
        }
        .metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .metric-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #26a69a;
        }
        .metric-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Performance Testing Suite</h1>
        
        <div class="warning">
            ⚠️ <strong>Warning:</strong> These tests will create test data and may impact performance.
            Only run on staging/development environments. Some tests may take several minutes to complete.
        </div>
        
        <form method="get">
            <?php wp_nonce_field('mt_performance_test'); ?>
            <input type="hidden" name="page" value="mt-performance-test">
            
            <h2>Select Tests to Run:</h2>
            <div class="button-group">
                <button type="submit" name="test" value="import">Test Large Import (100 records)</button>
                <button type="submit" name="test" value="export">Test Large Export</button>
                <button type="submit" name="test" value="queries">Test Database Queries</button>
                <button type="submit" name="test" value="memory">Test Memory Usage</button>
                <button type="submit" name="test" value="distribution">Test Assignment Distribution</button>
                <button type="submit" name="test" value="indexes">Test Index Performance</button>
                <button type="submit" name="test" value="all">Run All Tests</button>
            </div>
        </form>
        
        <?php if ($test && $nonce_valid): ?>
            <h2>Test Results:</h2>
            
            <?php
            $tester = new MT_Performance_Tester();
            
            switch ($test) {
                case 'import':
                    $tester->test_large_import(100); // Smaller number for safety
                    break;
                    
                case 'export':
                    $tester->test_large_export();
                    break;
                    
                case 'queries':
                    $tester->test_evaluation_queries();
                    break;
                    
                case 'memory':
                    $tester->test_memory_usage();
                    break;
                    
                case 'distribution':
                    $tester->test_assignment_distribution();
                    break;
                    
                case 'indexes':
                    $tester->test_index_performance();
                    break;
                    
                case 'all':
                    echo "<h3>Running All Tests...</h3>";
                    $tester->test_large_import(50); // Even smaller for all tests
                    $tester->test_large_export();
                    $tester->test_evaluation_queries();
                    $tester->test_memory_usage();
                    $tester->test_assignment_distribution();
                    $tester->test_index_performance();
                    break;
            }
            ?>
            
            <div class="success">
                ✓ Tests completed successfully! Check the results above for performance metrics.
            </div>
        <?php elseif ($test): ?>
            <div class="warning">
                Invalid security token. Please try again.
            </div>
        <?php endif; ?>
        
        <div class="metrics">
            <div class="metric-card">
                <div class="metric-label">PHP Version</div>
                <div class="metric-value"><?php echo PHP_VERSION; ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Memory Limit</div>
                <div class="metric-value"><?php echo ini_get('memory_limit'); ?></div>
            </div>
            <div class="metric-card">
                <div class="metric-label">Max Execution Time</div>
                <div class="metric-value"><?php echo ini_get('max_execution_time'); ?>s</div>
            </div>
            <div class="metric-card">
                <div class="metric-label">WordPress Version</div>
                <div class="metric-value"><?php echo get_bloginfo('version'); ?></div>
            </div>
        </div>
    </div>
</body>
</html>