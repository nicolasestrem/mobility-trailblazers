<?php
/**
 * Verify Assignment Management Fix
 * 
 * This script verifies that the assignment management AJAX functionality is working
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and is admin
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    die('Access denied. Please login as administrator.');
}

// Force initialize plugin AJAX handlers
if (class_exists('MobilityTrailblazers\Core\MT_Plugin')) {
    $plugin = MobilityTrailblazers\Core\MT_Plugin::get_instance();
    // The init method should have already been called, but let's ensure AJAX is ready
}

// Check if handlers are registered
global $wp_filter;
$required_handlers = [
    'wp_ajax_mt_auto_assign',
    'wp_ajax_mt_clear_all_assignments',
    'wp_ajax_mt_export_assignments'
];

$results = [];
foreach ($required_handlers as $handler) {
    $results[$handler] = isset($wp_filter[$handler]) ? 'Registered' : 'NOT Registered';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>MT Assignment Fix Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; }
        table { border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        button { padding: 10px 20px; font-size: 16px; margin: 10px 0; cursor: pointer; }
        .result-box { margin: 20px 0; padding: 15px; border: 1px solid #ddd; background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>Mobility Trailblazers Assignment Management Fix Verification</h1>
    
    <h2>AJAX Handler Registration Status</h2>
    <table>
        <tr>
            <th>Handler</th>
            <th>Status</th>
        </tr>
        <?php foreach ($results as $handler => $status): ?>
        <tr>
            <td><?php echo $handler; ?></td>
            <td class="<?php echo $status === 'Registered' ? 'success' : 'error'; ?>">
                <?php echo $status; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <h2>Test Auto-Assignment AJAX</h2>
    <button id="test-auto-assign">Test Auto-Assign AJAX Call</button>
    <div id="result" class="result-box" style="display: none;"></div>
    
    <h2>Summary</h2>
    <div class="result-box">
        <?php
        $all_registered = !in_array('NOT Registered', $results);
        if ($all_registered) {
            echo '<p class="success">✓ All AJAX handlers are properly registered!</p>';
            echo '<p>The assignment management page should now work correctly.</p>';
        } else {
            echo '<p class="error">✗ Some AJAX handlers are not registered.</p>';
            echo '<p>Please check the plugin initialization.</p>';
        }
        ?>
    </div>
    
    <h2>Next Steps</h2>
    <ul>
        <li>Go to <a href="<?php echo admin_url('admin.php?page=mt-assignments'); ?>">Assignment Management Page</a></li>
        <li>Click the "Auto-Assign" button</li>
        <li>The modal should appear without errors</li>
        <li>Submitting the form should process correctly</li>
    </ul>
    
    <script>
    jQuery(document).ready(function($) {
        $('#test-auto-assign').click(function() {
            var $button = $(this);
            var $result = $('#result');
            
            $button.prop('disabled', true).text('Testing...');
            $result.show().html('<p class="info">Sending AJAX request...</p>');
            
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'mt_auto_assign',
                    nonce: '<?php echo wp_create_nonce('mt_admin_nonce'); ?>',
                    method: 'balanced',
                    candidates_per_jury: 5
                },
                success: function(response) {
                    console.log('Response:', response);
                    if (response.success) {
                        $result.html('<p class="success">✓ AJAX call successful!</p><pre>' + 
                            JSON.stringify(response.data, null, 2) + '</pre>');
                    } else {
                        $result.html('<p class="error">✗ AJAX returned error:</p><pre>' + 
                            JSON.stringify(response, null, 2) + '</pre>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', xhr, status, error);
                    $result.html('<p class="error">✗ AJAX request failed:</p>' +
                        '<p>Status: ' + xhr.status + '</p>' +
                        '<p>Error: ' + error + '</p>' +
                        '<pre>' + xhr.responseText + '</pre>');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Test Auto-Assign AJAX Call');
                }
            });
        });
    });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html> 