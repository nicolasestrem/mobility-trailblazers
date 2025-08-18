<?php
/**
 * Staging Diagnostic - Check what's actually happening
 */

// Bootstrap WordPress
require_once('/home/mobilitytrailblazers/public_html/vote/wp-load.php');

// No auth check - we need to see what's happening

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staging Diagnostic - v2.5.20.2</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #ddd;
        }
        .good { color: green; font-weight: bold; }
        .bad { color: red; font-weight: bold; }
        .code { 
            background: #333; 
            color: #0f0; 
            padding: 10px; 
            overflow-x: auto;
            white-space: pre;
        }
    </style>
</head>
<body>
    <h1>Staging Diagnostic for v2.5.20.2</h1>
    
    <div class="section">
        <h2>1. Plugin Version Check</h2>
        <?php
        $plugin_file = '/home/mobilitytrailblazers/public_html/vote/wp-content/plugins/mobility-trailblazers/mobility-trailblazers.php';
        if (file_exists($plugin_file)) {
            $plugin_content = file_get_contents($plugin_file);
            preg_match('/Version:\s*(.+)$/m', $plugin_content, $matches);
            $version = $matches[1] ?? 'Unknown';
            
            if ($version === '2.5.20.2') {
                echo '<span class="good">✓ Version is correct: ' . $version . '</span><br>';
            } else {
                echo '<span class="bad">✗ Wrong version: ' . $version . ' (should be 2.5.20.2)</span><br>';
            }
            
            // Check MT_VERSION constant
            preg_match("/define\('MT_VERSION',\s*'(.+?)'\)/", $plugin_content, $matches);
            $mt_version = $matches[1] ?? 'Unknown';
            echo 'MT_VERSION constant: ' . $mt_version . '<br>';
        } else {
            echo '<span class="bad">✗ Plugin file not found!</span>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>2. JavaScript File Check</h2>
        <?php
        $js_file = '/home/mobilitytrailblazers/public_html/vote/wp-content/plugins/mobility-trailblazers/assets/js/frontend.js';
        if (file_exists($js_file)) {
            $js_content = file_get_contents($js_file);
            echo 'File size: ' . number_format(filesize($js_file)) . ' bytes<br>';
            echo 'Last modified: ' . date('Y-m-d H:i:s', filemtime($js_file)) . '<br>';
            
            // Check for parseFloat fix
            if (strpos($js_content, 'parseFloat') !== false) {
                echo '<span class="good">✓ Contains parseFloat fix</span><br>';
                
                // Show the actual updateTotalScore function
                preg_match('/updateTotalScore:\s*function\(\)\s*{([^}]+{[^}]+}[^}]+)}/s', $js_content, $matches);
                if ($matches) {
                    echo '<div class="code">updateTotalScore function found:
' . htmlspecialchars(substr($matches[0], 0, 500)) . '...</div>';
                }
            } else {
                echo '<span class="bad">✗ Missing parseFloat fix!</span><br>';
            }
            
            // Check for fallback initialization
            if (strpos($js_content, 'setTimeout(function() {') !== false && strpos($js_content, 'MTJuryDashboard.updateTotalScore()') !== false) {
                echo '<span class="good">✓ Contains fallback initialization</span><br>';
            } else {
                echo '<span class="bad">✗ Missing fallback initialization!</span><br>';
            }
        } else {
            echo '<span class="bad">✗ frontend.js not found!</span>';
        }
        ?>
    </div>
    
    <div class="section">
        <h2>3. CSS Files Check</h2>
        <?php
        $css_files = [
            'assets/css/enhanced-candidate-profile.css' => '#004C5F',
            'assets/css/mt-evaluation-fixes.css' => 'mt-candidate-body'
        ];
        
        foreach ($css_files as $file => $check_string) {
            $full_path = '/home/mobilitytrailblazers/public_html/vote/wp-content/plugins/mobility-trailblazers/' . $file;
            echo '<strong>' . $file . ':</strong> ';
            
            if (file_exists($full_path)) {
                $content = file_get_contents($full_path);
                if (strpos($content, $check_string) !== false) {
                    echo '<span class="good">✓ Contains "' . $check_string . '"</span><br>';
                } else {
                    echo '<span class="bad">✗ Missing "' . $check_string . '"</span><br>';
                }
                echo 'Size: ' . number_format(filesize($full_path)) . ' bytes, Modified: ' . date('Y-m-d H:i:s', filemtime($full_path)) . '<br>';
            } else {
                echo '<span class="bad">✗ File not found!</span><br>';
            }
        }
        ?>
    </div>
    
    <div class="section">
        <h2>4. Test Evaluation Form</h2>
        <p>This should update the score when you move sliders:</p>
        
        <div style="background: #f8f0e3; padding: 20px; border-radius: 8px;">
            <h3>Average Score: <span id="mt-total-score" style="color: #004C5F; font-size: 24px;">0.0</span>/10 
                <span class="mt-evaluated-count">(0/5 criteria evaluated)</span>
            </h3>
            
            <?php 
            $criteria = ['courage', 'innovation', 'implementation', 'relevance', 'visibility'];
            foreach ($criteria as $i => $criterion): 
            ?>
            <div style="margin: 15px 0;">
                <label><?php echo ucfirst($criterion); ?>:</label><br>
                <input type="range" class="mt-score-slider" name="<?php echo $criterion; ?>_score" 
                       min="0" max="10" step="0.5" value="0" style="width: 100%;">
                <span class="score-value">0</span>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Load jQuery and our script -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script>
        // Inline version of updateTotalScore for testing
        function testUpdateTotalScore() {
            var total = 0;
            var count = 0;
            var nonZeroCount = 0;
            
            $('.mt-score-slider').each(function() {
                var value = parseFloat($(this).val());
                if (!isNaN(value)) {
                    total += value;
                    count++;
                    if (value > 0) nonZeroCount++;
                }
            });
            
            var average = count > 0 ? (total / count).toFixed(1) : '0.0';
            $('#mt-total-score').text(average);
            $('.mt-evaluated-count').text('(' + nonZeroCount + '/5 criteria evaluated)');
        }
        
        $(document).ready(function() {
            console.log('Test script loaded');
            
            // Update on slider change
            $('.mt-score-slider').on('input', function() {
                $(this).next('.score-value').text($(this).val());
                testUpdateTotalScore();
            });
            
            // Initial update
            testUpdateTotalScore();
        });
        </script>
    </div>
    
    <div class="section">
        <h2>5. Check Loaded Scripts on Actual Page</h2>
        <p>To check what's actually loading on the evaluation page:</p>
        <ol>
            <li>Go to: <a href="https://mobilitytrailblazers.de/vote/jury-dashboard/?evaluate=4377" target="_blank">Evaluation Page</a></li>
            <li>Open browser console (F12)</li>
            <li>Run: <code>console.log(typeof MTJuryDashboard)</code></li>
            <li>Run: <code>$('.mt-score-slider').length</code></li>
            <li>Run: <code>$('#mt-total-score').length</code></li>
        </ol>
    </div>
    
    <div class="section">
        <h2>6. WordPress Script Queue</h2>
        <?php
        global $wp_scripts;
        $mt_scripts = [];
        if (isset($wp_scripts->registered)) {
            foreach ($wp_scripts->registered as $handle => $script) {
                if (strpos($handle, 'mt-') === 0 || strpos($script->src, 'mobility-trailblazers') !== false) {
                    $mt_scripts[$handle] = $script->src . ' (ver: ' . $script->ver . ')';
                }
            }
        }
        
        if (empty($mt_scripts)) {
            echo '<span class="bad">No MT scripts registered!</span>';
        } else {
            echo '<strong>Registered MT Scripts:</strong><br>';
            foreach ($mt_scripts as $handle => $src) {
                echo $handle . ': ' . $src . '<br>';
            }
        }
        ?>
    </div>
</body>
</html>