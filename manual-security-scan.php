<?php
/**
 * Manual Security Scanner for Mobility Trailblazers Plugin
 * 
 * This script performs security checks without requiring phpcs
 * Run from command line: php manual-security-scan.php
 */

// Colors for console output
define('COLOR_RED', "\033[0;31m");
define('COLOR_GREEN', "\033[0;32m");
define('COLOR_YELLOW', "\033[1;33m");
define('COLOR_RESET', "\033[0m");

class SecurityScanner {
    private $issues = [];
    private $warnings = [];
    private $passed = [];
    private $filesScanned = 0;
    
    public function scan($directory) {
        echo "==========================================\n";
        echo "Mobility Trailblazers Manual Security Scan\n";
        echo "==========================================\n\n";
        
        $this->scanDirectory($directory);
        $this->printResults();
    }
    
    private function scanDirectory($dir) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                // Skip vendor and .git directories
                if (strpos($file->getPath(), 'vendor') !== false || 
                    strpos($file->getPath(), '.git') !== false) {
                    continue;
                }
                
                $this->scanFile($file->getPathname());
                $this->filesScanned++;
            }
        }
    }
    
    private function scanFile($filepath) {
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);
        $filename = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $filepath);
        
        // Check for direct access prevention
        if (strpos($content, "if (!defined('ABSPATH'))") === false && 
            strpos($filename, 'mobility-trailblazers.php') === false) {
            $this->issues[] = [
                'file' => $filename,
                'line' => 1,
                'type' => 'Direct Access',
                'message' => 'Missing ABSPATH check'
            ];
        }
        
        // Check for unescaped output
        $this->checkUnescapedOutput($lines, $filename);
        
        // Check for nonce verification
        $this->checkNonceVerification($content, $filename);
        
        // Check for SQL injection
        $this->checkSQLInjection($lines, $filename);
        
        // Check for sanitization
        $this->checkInputSanitization($lines, $filename);
    }
    
    private function checkUnescapedOutput($lines, $filename) {
        $outputPatterns = [
            '/echo\s+\$[\w\[\]\'\"->]+(?!.*esc_|.*sanitize_|.*wp_kses)/',
            '/print\s+\$[\w\[\]\'\"->]+(?!.*esc_|.*sanitize_|.*wp_kses)/',
            '/<\?=\s*\$[\w\[\]\'\"->]+(?!.*esc_|.*sanitize_|.*wp_kses)/',
        ];
        
        foreach ($lines as $lineNum => $line) {
            // Skip if line contains escaping function
            if (preg_match('/(esc_html|esc_attr|esc_url|esc_js|wp_kses|intval|absint|floatval)/', $line)) {
                continue;
            }
            
            foreach ($outputPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $this->warnings[] = [
                        'file' => $filename,
                        'line' => $lineNum + 1,
                        'type' => 'Unescaped Output',
                        'message' => 'Possible unescaped output: ' . trim($line)
                    ];
                }
            }
        }
    }
    
    private function checkNonceVerification($content, $filename) {
        // Check if file has $_POST or $_GET
        if (preg_match('/\$_(POST|GET|REQUEST)/', $content)) {
            // Check if it has nonce verification
            if (!preg_match('/(wp_verify_nonce|check_ajax_referer|verify_nonce)/', $content)) {
                $this->issues[] = [
                    'file' => $filename,
                    'line' => 0,
                    'type' => 'Missing Nonce',
                    'message' => 'File processes user input without nonce verification'
                ];
            }
        }
    }
    
    private function checkSQLInjection($lines, $filename) {
        $sqlPatterns = [
            '/\$wpdb->query\s*\([^)]*\$_(GET|POST|REQUEST)/',
            '/\$wpdb->get_results\s*\([^)]*\$_(GET|POST|REQUEST)/',
            '/\$wpdb->get_var\s*\([^)]*\$_(GET|POST|REQUEST)/',
        ];
        
        foreach ($lines as $lineNum => $line) {
            foreach ($sqlPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    if (!preg_match('/\$wpdb->prepare/', $line)) {
                        $this->issues[] = [
                            'file' => $filename,
                            'line' => $lineNum + 1,
                            'type' => 'SQL Injection Risk',
                            'message' => 'Direct user input in SQL query without prepare()'
                        ];
                    }
                }
            }
        }
    }
    
    private function checkInputSanitization($lines, $filename) {
        $inputPatterns = [
            '/\$[\w]+\s*=\s*\$_(GET|POST|REQUEST)\[/',
        ];
        
        foreach ($lines as $lineNum => $line) {
            foreach ($inputPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    if (!preg_match('/(sanitize_|esc_|intval|absint|floatval|wp_verify)/', $line)) {
                        $this->warnings[] = [
                            'file' => $filename,
                            'line' => $lineNum + 1,
                            'type' => 'Unsanitized Input',
                            'message' => 'User input used without sanitization'
                        ];
                    }
                }
            }
        }
    }
    
    private function printResults() {
        echo "Files Scanned: {$this->filesScanned}\n\n";
        
        if (!empty($this->issues)) {
            echo COLOR_RED . "CRITICAL ISSUES FOUND:\n" . COLOR_RESET;
            echo "----------------------\n";
            foreach ($this->issues as $issue) {
                echo COLOR_RED . "✗ " . COLOR_RESET;
                echo "{$issue['file']}";
                if ($issue['line'] > 0) {
                    echo ":{$issue['line']}";
                }
                echo " - [{$issue['type']}] {$issue['message']}\n";
            }
            echo "\n";
        }
        
        if (!empty($this->warnings)) {
            echo COLOR_YELLOW . "WARNINGS:\n" . COLOR_RESET;
            echo "---------\n";
            $count = 0;
            foreach ($this->warnings as $warning) {
                if ($count++ < 20) { // Limit output
                    echo COLOR_YELLOW . "⚠ " . COLOR_RESET;
                    echo "{$warning['file']}:{$warning['line']} - [{$warning['type']}]\n";
                }
            }
            if (count($this->warnings) > 20) {
                echo "... and " . (count($this->warnings) - 20) . " more warnings\n";
            }
            echo "\n";
        }
        
        // Summary
        echo "==========================================\n";
        echo "SUMMARY:\n";
        echo "--------\n";
        echo "Critical Issues: " . count($this->issues) . "\n";
        echo "Warnings: " . count($this->warnings) . "\n";
        echo "Files Scanned: {$this->filesScanned}\n";
        
        if (empty($this->issues) && empty($this->warnings)) {
            echo COLOR_GREEN . "\n✓ No security issues found!\n" . COLOR_RESET;
        } elseif (empty($this->issues)) {
            echo COLOR_YELLOW . "\n⚠ Some warnings found, but no critical issues.\n" . COLOR_RESET;
        } else {
            echo COLOR_RED . "\n✗ Critical security issues found! Please fix them immediately.\n" . COLOR_RESET;
        }
        
        // Save report
        $this->saveReport();
    }
    
    private function saveReport() {
        $reportDir = __DIR__ . '/security-reports';
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        $report = [
            'scan_date' => date('Y-m-d H:i:s'),
            'files_scanned' => $this->filesScanned,
            'critical_issues' => $this->issues,
            'warnings' => $this->warnings,
            'summary' => [
                'critical_count' => count($this->issues),
                'warning_count' => count($this->warnings)
            ]
        ];
        
        file_put_contents(
            $reportDir . '/manual-scan-' . date('Y-m-d-His') . '.json',
            json_encode($report, JSON_PRETTY_PRINT)
        );
        
        echo "\nDetailed report saved to: security-reports/manual-scan-" . date('Y-m-d-His') . ".json\n";
    }
}

// Run the scanner
$scanner = new SecurityScanner();
$scanner->scan(__DIR__);
