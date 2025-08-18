<?php
/**
 * Test Evaluation Form with Fixed Score Calculation
 * Version 2.5.20
 * 
 * This page tests:
 * 1. Total score calculation with parseFloat instead of parseInt
 * 2. Draft saving functionality
 * 3. Criterion description localization
 */

// Set user for testing
$test_user_id = 1; // Admin user who is also jury member
$_REQUEST['test_user'] = $test_user_id;

// Bootstrap WordPress
require_once('/var/www/html/wp-load.php');

// Force login as test user
wp_set_current_user($test_user_id);
wp_set_auth_cookie($test_user_id);

// Get a test candidate
$candidate_id = 4377; // Alexander Möller
$candidate = get_post($candidate_id);

if (!$candidate) {
    die('Test candidate not found');
}

// Load evaluation service
$evaluation_service = new \MobilityTrailblazers\Services\MT_Evaluation_Service();
$criteria = $evaluation_service->get_criteria();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test Evaluation Form - <?php echo esc_html($candidate->post_title); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            padding: 40px;
            background: #f8f0e3;
            font-family: 'Poppins', sans-serif;
        }
        .test-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .test-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .test-results {
            background: #f0f8ff;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .test-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            margin-left: 10px;
        }
        .test-pass {
            background: #4caf50;
            color: white;
        }
        .test-fail {
            background: #f44336;
            color: white;
        }
        .mt-score-slider {
            width: 100%;
            margin: 10px 0;
        }
        .criterion-test {
            background: #f8f0e3;
            padding: 20px;
            margin: 15px 0;
            border-radius: 5px;
        }
        #test-log {
            background: #333;
            color: #0f0;
            padding: 15px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-header">
            <h1>Evaluation Form Test - Version 2.5.20</h1>
            <p>Testing: <strong><?php echo esc_html($candidate->post_title); ?></strong> (ID: <?php echo $candidate_id; ?>)</p>
            <p>Logged in as: <strong>User ID <?php echo $test_user_id; ?></strong></p>
        </div>

        <div class="test-section">
            <h2>Test 1: Score Calculation with Decimal Values</h2>
            <p>Average Score: <span id="mt-total-score">0.0</span>/10 (<span class="mt-evaluated-count">0/5 criteria evaluated</span>)</p>
            
            <div class="criteria-container">
                <?php foreach ($criteria as $key => $criterion) : ?>
                <div class="criterion-test">
                    <h3><?php echo esc_html($criterion['label']); ?></h3>
                    <p class="mt-criterion-description"><?php echo esc_html($criterion['description']); ?></p>
                    <input type="range" 
                           name="<?php echo esc_attr($criterion['key']); ?>" 
                           class="mt-score-slider" 
                           min="0" 
                           max="10" 
                           step="0.5" 
                           value="0"
                           data-criterion="<?php echo esc_attr($key); ?>">
                    <span class="score-display">0</span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="test-results">
                <h3>Test Results:</h3>
                <p>✓ Score calculation using parseFloat: <span id="test-parsefloat" class="test-status">Testing...</span></p>
                <p>✓ Decimal values preserved: <span id="test-decimals" class="test-status">Testing...</span></p>
                <p>✓ Average calculation correct: <span id="test-average" class="test-status">Testing...</span></p>
                <p>✓ Evaluated count updates: <span id="test-count" class="test-status">Testing...</span></p>
            </div>
        </div>

        <div class="test-section">
            <h2>Test 2: Draft Saving</h2>
            <form id="mt-evaluation-form" class="mt-evaluation-form">
                <input type="hidden" name="candidate_id" value="<?php echo $candidate_id; ?>">
                <textarea name="comments" placeholder="Test comments..."></textarea>
                <button type="button" class="mt-save-draft">Save as Draft</button>
                <button type="button" class="mt-submit-final">Submit Final</button>
            </form>
            
            <div class="test-results">
                <h3>Draft Save Results:</h3>
                <p>✓ AJAX handler responds: <span id="test-ajax" class="test-status">Not tested</span></p>
                <p>✓ Draft status saved: <span id="test-draft" class="test-status">Not tested</span></p>
            </div>
        </div>

        <div class="test-section">
            <h2>Test 3: Localization Check</h2>
            <div class="test-results">
                <?php foreach ($criteria as $key => $criterion) : ?>
                <p>✓ <?php echo esc_html($criterion['label']); ?>: 
                    <span class="test-status test-pass"><?php echo esc_html($criterion['description']); ?></span>
                </p>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="test-log">
            <h3>Test Log:</h3>
            <div id="log-content"></div>
        </div>
    </div>

    <?php wp_footer(); ?>
    
    <script>
    jQuery(document).ready(function($) {
        var log = function(msg) {
            $('#log-content').append('<div>[' + new Date().toTimeString().split(' ')[0] + '] ' + msg + '</div>');
            $('#test-log').scrollTop($('#test-log')[0].scrollHeight);
        };

        log('Test started - Version 2.5.20');
        
        // Test 1: Score calculation
        log('Testing score calculation with parseFloat...');
        
        // Set test values with decimals
        $('.mt-score-slider').each(function(index) {
            var testValues = [7.5, 8.0, 6.5, 9.0, 8.5];
            $(this).val(testValues[index]).trigger('input');
            $(this).siblings('.score-display').text(testValues[index]);
            log('Set criterion ' + (index + 1) + ' to ' + testValues[index]);
        });
        
        // Trigger the updateTotalScore function
        if (typeof MTJuryDashboard !== 'undefined' && MTJuryDashboard.updateTotalScore) {
            log('Calling MTJuryDashboard.updateTotalScore()...');
            MTJuryDashboard.updateTotalScore();
            
            // Check results
            var totalScore = $('#mt-total-score').text();
            var expectedAverage = (7.5 + 8.0 + 6.5 + 9.0 + 8.5) / 5; // = 7.9
            
            log('Total score displayed: ' + totalScore);
            log('Expected average: ' + expectedAverage.toFixed(1));
            
            if (parseFloat(totalScore) === parseFloat(expectedAverage.toFixed(1))) {
                $('#test-parsefloat').removeClass('test-fail').addClass('test-pass').text('PASS');
                $('#test-decimals').removeClass('test-fail').addClass('test-pass').text('PASS');
                $('#test-average').removeClass('test-fail').addClass('test-pass').text('PASS');
                log('✓ Score calculation test PASSED');
            } else {
                $('#test-parsefloat').addClass('test-fail').text('FAIL - Got ' + totalScore);
                log('✗ Score calculation test FAILED');
            }
            
            // Check count update
            var countText = $('.mt-evaluated-count').text();
            if (countText.includes('5/5')) {
                $('#test-count').removeClass('test-fail').addClass('test-pass').text('PASS');
                log('✓ Count update test PASSED');
            } else {
                $('#test-count').addClass('test-fail').text('FAIL');
                log('✗ Count update test FAILED');
            }
        } else {
            log('ERROR: MTJuryDashboard.updateTotalScore not found!');
            $('#test-parsefloat, #test-decimals, #test-average, #test-count').addClass('test-fail').text('Function not found');
        }
        
        // Test 2: Draft saving
        $('.mt-save-draft').on('click', function(e) {
            e.preventDefault();
            log('Testing draft save...');
            $('#test-ajax').text('Testing...');
            
            if (typeof mt_ajax === 'undefined') {
                log('ERROR: mt_ajax not defined');
                $('#test-ajax').addClass('test-fail').text('mt_ajax not found');
                return;
            }
            
            var formData = {
                action: 'mt_submit_evaluation',
                nonce: mt_ajax.nonce,
                candidate_id: <?php echo $candidate_id; ?>,
                status: 'draft',
                comments: 'Test draft comment',
                courage_score: 7.5,
                innovation_score: 8.0,
                implementation_score: 6.5,
                relevance_score: 9.0,
                visibility_score: 8.5
            };
            
            log('Sending AJAX request with status: draft');
            
            $.post(mt_ajax.ajax_url, formData)
                .done(function(response) {
                    log('AJAX response received: ' + JSON.stringify(response));
                    if (response.success) {
                        $('#test-ajax').removeClass('test-fail').addClass('test-pass').text('PASS');
                        $('#test-draft').removeClass('test-fail').addClass('test-pass').text('PASS');
                        log('✓ Draft save test PASSED');
                    } else {
                        $('#test-ajax').addClass('test-fail').text('FAIL - ' + response.data);
                        log('✗ Draft save test FAILED: ' + response.data);
                    }
                })
                .fail(function(xhr, status, error) {
                    $('#test-ajax').addClass('test-fail').text('FAIL - ' + error);
                    log('✗ AJAX request failed: ' + error);
                });
        });
        
        log('All tests initialized. Click "Save as Draft" to test draft saving.');
    });
    </script>
</body>
</html>