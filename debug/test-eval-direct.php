<?php
/**
 * Direct evaluation form test
 */

// Load WordPress
require_once('../../../../../wp-load.php');

// Check if user is logged in
if (!is_user_logged_in()) {
    wp_die('You must be logged in to access this page.');
}

// Set up variables
$candidate_id = isset($_GET['candidate_id']) ? intval($_GET['candidate_id']) : 4377;
$candidate = get_post($candidate_id);

if (!$candidate || $candidate->post_type !== 'mt_candidate') {
    wp_die('Invalid candidate.');
}

// Mock jury member object
$jury_member = new stdClass();
$jury_member->ID = get_current_user_id(); // Use current user ID directly

// Include plugin files
if (!defined('MT_PLUGIN_DIR')) {
    define('MT_PLUGIN_DIR', dirname(__DIR__) . '/');
}
if (!defined('MT_PLUGIN_URL')) {
    define('MT_PLUGIN_URL', plugins_url('/', dirname(__FILE__)));
}
if (!defined('MT_VERSION')) {
    define('MT_VERSION', '2.5.20.2');
}

// Enqueue styles and scripts
wp_enqueue_style('mt-frontend', MT_PLUGIN_URL . 'assets/css/frontend.css', [], MT_VERSION);
wp_enqueue_style('mt-evaluation-fixes', MT_PLUGIN_URL . 'assets/css/mt-evaluation-fixes.css', [], MT_VERSION);
wp_enqueue_style('dashicons');
wp_enqueue_script('jquery');
wp_enqueue_script('mt-frontend', MT_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], MT_VERSION, true);

// Localize script
wp_localize_script('mt-frontend', 'mt_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('mt_ajax_nonce'),
    'i18n' => [
        'error' => 'An error occurred',
        'success' => 'Success',
        'loading' => 'Loading...',
        'submitting' => 'Submitting...',
        'saving' => 'Saving...',
        'save_as_draft' => 'Save as Draft',
        'submit_evaluation' => 'Submit Evaluation',
        'draft_saved' => 'Draft Saved!',
        'evaluation_submitted' => 'Evaluation submitted successfully!',
        'criteria_evaluated' => 'criteria evaluated',
        'characters' => 'characters',
        'additional_comments' => 'Additional Comments (Optional)',
        'back_to_dashboard' => 'Back to Dashboard'
    ]
]);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php wp_head(); ?>
    <style>
        body {
            background: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }
        .mt-evaluation-page {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        /* Score calculation test styles */
        #mt-total-score {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }
        .mt-score-display {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="mt-jury-dashboard">
        <?php
        // Include the evaluation form template
        include MT_PLUGIN_DIR . 'templates/frontend/jury-evaluation-form.php';
        ?>
    </div>
    
    <?php wp_footer(); ?>
    
    <script>
    jQuery(document).ready(function($) {
        console.log('Page loaded, checking for evaluation form elements...');
        console.log('Score sliders found:', $('.mt-score-slider').length);
        console.log('Total score element:', $('#mt-total-score').length);
        
        // Force initialize after delay
        setTimeout(function() {
            if (typeof MTJuryDashboard !== 'undefined') {
                console.log('Forcing score update...');
                MTJuryDashboard.updateTotalScore();
            }
        }, 1000);
    });
    </script>
</body>
</html>