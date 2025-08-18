<?php
/**
 * Test Ranking Display System
 */

// Bootstrap WordPress
require_once('/var/www/html/wp-load.php');

// Load the utility class
require_once('/var/www/html/wp-content/plugins/mobility-trailblazers/includes/utilities/class-mt-ranking-display.php');

use MobilityTrailblazers\Utilities\MT_Ranking_Display;

// Create some test data
$test_candidates = [
    ['name' => 'Helmut Ruhl', 'organization' => 'AMAG', 'score' => 9.5],
    ['name' => 'Hildegard Müller', 'organization' => 'VDA', 'score' => 9.2],
    ['name' => 'Horst Gräf', 'organization' => 'Jive GmbH', 'score' => 8.8],
    ['name' => 'Johannes Pallasch', 'organization' => 'NOW', 'score' => 8.5],
    ['name' => 'Sarah Fleischer', 'organization' => 'Tobii', 'score' => 8.2],
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Ranking Display</title>
    <link rel="stylesheet" href="/wp-content/plugins/mobility-trailblazers/assets/css/mt-rankings-v2.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 30px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
    </style>
</head>
<body>
    <h1>Ranking Display System Test</h1>
    
    <!-- Test 1: Individual Badges -->
    <div class="test-section">
        <h2>Individual Position Badges</h2>
        <div style="display: flex; gap: 20px; align-items: center;">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <div>
                    <p>Position <?php echo $i; ?>:</p>
                    <?php echo MT_Ranking_Display::get_position_badge($i, [
                        'show_medal' => true,
                        'show_number' => true,
                        'size' => 'medium',
                        'context' => 'default'
                    ]); ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- Test 2: Ranking Table -->
    <div class="test-section">
        <h2>Ranking Table</h2>
        <table class="mt-rankings-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Candidate</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $position = 1;
                foreach ($test_candidates as $candidate): 
                    echo MT_Ranking_Display::get_ranking_row($position, $candidate);
                    $position++;
                endforeach; 
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Test 3: Winner Cards -->
    <div class="test-section">
        <h2>Winner Cards</h2>
        <div class="mt-winners-grid">
            <?php 
            $position = 1;
            foreach (array_slice($test_candidates, 0, 3) as $candidate): 
                $candidate['photo'] = 'https://via.placeholder.com/120';
                $candidate['position'] = 'CEO';
                echo MT_Ranking_Display::get_winner_card($position, $candidate);
                $position++;
            endforeach; 
            ?>
        </div>
    </div>
    
    <!-- Test 4: Different Sizes -->
    <div class="test-section">
        <h2>Badge Sizes</h2>
        <div style="display: flex; gap: 20px; align-items: center;">
            <div>
                <p>Small:</p>
                <?php echo MT_Ranking_Display::get_position_badge(1, [
                    'show_medal' => true,
                    'show_number' => true,
                    'size' => 'small',
                    'context' => 'default'
                ]); ?>
            </div>
            <div>
                <p>Medium:</p>
                <?php echo MT_Ranking_Display::get_position_badge(1, [
                    'show_medal' => true,
                    'show_number' => true,
                    'size' => 'medium',
                    'context' => 'default'
                ]); ?>
            </div>
            <div>
                <p>Large:</p>
                <?php echo MT_Ranking_Display::get_position_badge(1, [
                    'show_medal' => true,
                    'show_number' => true,
                    'size' => 'large',
                    'context' => 'default'
                ]); ?>
            </div>
        </div>
    </div>
</body>
</html>