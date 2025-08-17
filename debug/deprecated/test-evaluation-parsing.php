<?php
/**
 * Test script for evaluation criteria parsing
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Test the parsing function
$test_description = "Mut & Pioniergeist: Als Oberbürgermeister wagt er mutige Schritte zur Transformation der städtischen Mobilität und setzt innovative Konzepte gegen traditionelle Widerstände durch. Innovationsgrad: Seine Ansätze zur Bürgerbeteiligung und partizipativen Stadtplanung revolutionieren die Art, wie Mobilitätsprojekte entwickelt und umgesetzt werden. Umsetzungskraft & Wirkung: Unter seiner Führung wurden bereits mehrere wegweisende Mobilitätsprojekte realisiert, die Wuppertal zu einem Vorbild für andere Städte machen. Relevanz für die Mobilitätswende: Seine Politik fokussiert konsequent auf nachhaltige Verkehrslösungen und die Reduzierung des motorisierten Individualverkehrs. Vorbildfunktion & Sichtbarkeit: Als prominenter Verfechter der Verkehrswende ist er national und international als Experte anerkannt und inspiriert andere Städte. Persönlichkeit & Motivation: Seine wissenschaftliche Expertise kombiniert mit politischem Gestaltungswillen macht ihn zu einem authentischen Akteur der Transformation.";

?>
<div class="wrap">
    <h1>Test Evaluation Criteria Parsing</h1>
    
    <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2>Original Description:</h2>
        <div style="background: #f5f5f5; padding: 10px; margin: 10px 0; border: 1px solid #ddd;">
            <pre style="white-space: pre-wrap;"><?php echo esc_html($test_description); ?></pre>
        </div>
        
        <h2>Parsed Evaluation Criteria:</h2>
        <?php
        // Parse the criteria
        $parsed = \MobilityTrailblazers\Admin\MT_Import_Handler::parse_evaluation_criteria($test_description);
        
        $labels = [
            '_mt_evaluation_courage' => 'Mut & Pioniergeist',
            '_mt_evaluation_innovation' => 'Innovationsgrad',
            '_mt_evaluation_implementation' => 'Umsetzungskraft & Wirkung',
            '_mt_evaluation_relevance' => 'Relevanz für die Mobilitätswende',
            '_mt_evaluation_visibility' => 'Vorbildfunktion & Sichtbarkeit',
            '_mt_evaluation_personality' => 'Persönlichkeit & Motivation'
        ];
        ?>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 30%;">Field</th>
                    <th style="width: 20%;">Label</th>
                    <th style="width: 10%;">Status</th>
                    <th style="width: 40%;">Extracted Text</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($labels as $key => $label) {
                    $value = isset($parsed[$key]) ? $parsed[$key] : '';
                    $status = !empty($value) ? '<span style="color: green;">✓ OK</span>' : '<span style="color: red;">✗ EMPTY</span>';
                    
                    // Truncate for display
                    $display_value = $value;
                    if (strlen($display_value) > 100) {
                        $display_value = substr($display_value, 0, 100) . '...';
                    }
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($key); ?></code></td>
                        <td><?php echo esc_html($label); ?></td>
                        <td><?php echo $status; ?></td>
                        <td><?php echo esc_html($display_value); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        
        <h3>Summary:</h3>
        <?php
        $filled = array_filter($parsed, function($v) { return !empty($v); });
        $empty = array_filter($parsed, function($v) { return empty($v); });
        ?>
        <ul>
            <li><strong>Successfully parsed:</strong> <?php echo count($filled); ?> / <?php echo count($parsed); ?> fields</li>
            <li><strong>Empty fields:</strong> <?php echo count($empty); ?></li>
        </ul>
        
        <?php if (count($empty) > 0): ?>
            <div class="notice notice-error">
                <p><strong>Failed to parse these fields:</strong></p>
                <ul>
                    <?php foreach ($empty as $key => $value): ?>
                        <li><code><?php echo esc_html($key); ?></code> (<?php echo esc_html($labels[$key]); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php else: ?>
            <div class="notice notice-success">
                <p><strong>All fields parsed successfully!</strong></p>
            </div>
        <?php endif; ?>
        
        <h3>Raw Parsed Data:</h3>
        <pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">
<?php print_r($parsed); ?>
        </pre>
        
        <h3>Test Top 50 Status Parsing:</h3>
        <?php
        $test_values = [
            'Top 50: Yes' => 'Should be "yes"',
            'Top 50: No' => 'Should be "no"',
            'Yes' => 'Should be "yes"',
            'Ja' => 'Should be "yes"',
            '1' => 'Should be "yes"',
            'true' => 'Should be "yes"',
            'No' => 'Should be "no"',
            'false' => 'Should be "no"'
        ];
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Input Value</th>
                    <th>Expected</th>
                    <th>Actual Result</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($test_values as $input => $expected) {
                    // Simulate the parsing logic
                    $clean_value = preg_replace('/^top\s*50\s*:\s*/i', '', $input);
                    $clean_value = strtolower(trim($clean_value));
                    $result = in_array($clean_value, ['ja', 'yes', '1', 'true', 'top 50', 'top50']) ? 'yes' : 'no';
                    
                    $expected_result = (strpos(strtolower($expected), '"yes"') !== false) ? 'yes' : 'no';
                    $status = ($result === $expected_result) ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>';
                    ?>
                    <tr>
                        <td><code><?php echo esc_html($input); ?></code></td>
                        <td><?php echo esc_html($expected); ?></td>
                        <td><code><?php echo esc_html($result); ?></code></td>
                        <td><?php echo $status; ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>