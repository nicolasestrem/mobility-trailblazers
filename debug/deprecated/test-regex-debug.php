<?php
/**
 * Debug regex patterns
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$test_description = "Mut & Pioniergeist: Als Oberbürgermeister wagt er mutige Schritte zur Transformation der städtischen Mobilität und setzt innovative Konzepte gegen traditionelle Widerstände durch. Innovationsgrad: Seine Ansätze zur Bürgerbeteiligung und partizipativen Stadtplanung revolutionieren die Art, wie Mobilitätsprojekte entwickelt und umgesetzt werden. Umsetzungskraft & Wirkung: Unter seiner Führung wurden bereits mehrere wegweisende Mobilitätsprojekte realisiert, die Wuppertal zu einem Vorbild für andere Städte machen. Relevanz für die Mobilitätswende: Seine Politik fokussiert konsequent auf nachhaltige Verkehrslösungen und die Reduzierung des motorisierten Individualverkehrs. Vorbildfunktion & Sichtbarkeit: Als prominenter Verfechter der Verkehrswende ist er national und international als Experte anerkannt und inspiriert andere Städte. Persönlichkeit & Motivation: Seine wissenschaftliche Expertise kombiniert mit politischem Gestaltungswillen macht ihn zu einem authentischen Akteur der Transformation.";

?>
<div class="wrap">
    <h1>Debug Regex Patterns</h1>
    
    <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ccc;">
        <h2>Test Different Regex Approaches</h2>
        
        <?php
        // Test 1: Current approach (failing)
        echo '<h3>1. Current Approach (with combined lookahead):</h3>';
        $all_labels = 'Mut\s*&\s*Pioniergeist:|Innovationsgrad:|Umsetzungskraft\s*&\s*Wirkung:|Relevanz\s*für\s*die\s*Mobilitätswende:|Vorbildfunktion\s*&\s*Sichtbarkeit:|Persönlichkeit\s*&\s*Motivation:';
        
        $pattern1 = '/Mut\s*&\s*Pioniergeist:\s*(.+?)(?=' . $all_labels . '|$)/isu';
        
        if (preg_match($pattern1, $test_description, $matches)) {
            echo '<p style="color: green;">✓ Matched: ' . substr($matches[1], 0, 50) . '...</p>';
        } else {
            echo '<p style="color: red;">✗ Failed to match</p>';
            // Debug why it failed
            echo '<p>Testing simpler pattern...</p>';
            if (preg_match('/Mut\s*&\s*Pioniergeist:/', $test_description, $matches)) {
                echo '<p style="color: blue;">Label found at position: ' . strpos($test_description, $matches[0]) . '</p>';
            }
        }
        
        // Test 2: Simpler approach
        echo '<h3>2. Simpler Approach (match until next known label):</h3>';
        
        // Split by any of the known labels
        $sections = preg_split('/(Mut\s*&\s*Pioniergeist:|Innovationsgrad:|Umsetzungskraft\s*&\s*Wirkung:|Relevanz\s*für\s*die\s*Mobilitätswende:|Vorbildfunktion\s*&\s*Sichtbarkeit:|Persönlichkeit\s*&\s*Motivation:)/u', $test_description, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        echo '<p>Split into ' . count($sections) . ' sections</p>';
        
        $parsed = [];
        $label_map = [
            'Mut & Pioniergeist:' => '_mt_evaluation_courage',
            'Innovationsgrad:' => '_mt_evaluation_innovation',
            'Umsetzungskraft & Wirkung:' => '_mt_evaluation_implementation',
            'Relevanz für die Mobilitätswende:' => '_mt_evaluation_relevance',
            'Vorbildfunktion & Sichtbarkeit:' => '_mt_evaluation_visibility',
            'Persönlichkeit & Motivation:' => '_mt_evaluation_personality'
        ];
        
        for ($i = 1; $i < count($sections); $i += 2) {
            $label = trim($sections[$i]);
            $content = isset($sections[$i + 1]) ? trim($sections[$i + 1]) : '';
            
            // Normalize the label
            $normalized_label = preg_replace('/\s+/', ' ', $label);
            
            foreach ($label_map as $expected => $field) {
                if (stripos($normalized_label, str_replace(':', '', $expected)) !== false) {
                    $parsed[$field] = $content;
                    break;
                }
            }
        }
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Field</th><th>Status</th><th>Value</th></tr></thead>';
        echo '<tbody>';
        foreach ($label_map as $label => $field) {
            $has_value = isset($parsed[$field]) && !empty($parsed[$field]);
            echo '<tr>';
            echo '<td>' . $field . '</td>';
            echo '<td>' . ($has_value ? '<span style="color: green;">✓</span>' : '<span style="color: red;">✗</span>') . '</td>';
            echo '<td>' . ($has_value ? substr($parsed[$field], 0, 50) . '...' : 'Not found') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        
        // Test 3: Even simpler - just look for each pattern individually
        echo '<h3>3. Individual Pattern Matching (no lookahead):</h3>';
        
        $simple_patterns = [
            '_mt_evaluation_courage' => '/Mut\s*&\s*Pioniergeist:\s*([^.]+(?:\.[^.]+)*?)(?=\s*(?:Innovationsgrad:|Umsetzungskraft|Relevanz|Vorbildfunktion|Persönlichkeit|$))/isu',
            '_mt_evaluation_innovation' => '/Innovationsgrad:\s*([^.]+(?:\.[^.]+)*?)(?=\s*(?:Mut|Umsetzungskraft|Relevanz|Vorbildfunktion|Persönlichkeit|$))/isu',
            '_mt_evaluation_implementation' => '/Umsetzungskraft\s*&\s*Wirkung:\s*([^.]+(?:\.[^.]+)*?)(?=\s*(?:Mut|Innovationsgrad|Relevanz|Vorbildfunktion|Persönlichkeit|$))/isu',
            '_mt_evaluation_relevance' => '/Relevanz\s*für\s*die\s*Mobilitätswende:\s*([^.]+(?:\.[^.]+)*?)(?=\s*(?:Mut|Innovationsgrad|Umsetzungskraft|Vorbildfunktion|Persönlichkeit|$))/isu',
            '_mt_evaluation_visibility' => '/Vorbildfunktion\s*&\s*Sichtbarkeit:\s*([^.]+(?:\.[^.]+)*?)(?=\s*(?:Mut|Innovationsgrad|Umsetzungskraft|Relevanz|Persönlichkeit|$))/isu',
            '_mt_evaluation_personality' => '/Persönlichkeit\s*&\s*Motivation:\s*(.+)$/isu'
        ];
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Field</th><th>Pattern Test</th><th>Extracted</th></tr></thead>';
        echo '<tbody>';
        foreach ($simple_patterns as $field => $pattern) {
            $matched = preg_match($pattern, $test_description, $matches);
            echo '<tr>';
            echo '<td>' . $field . '</td>';
            echo '<td>' . ($matched ? '<span style="color: green;">✓ Matched</span>' : '<span style="color: red;">✗ Failed</span>') . '</td>';
            echo '<td>' . ($matched ? substr($matches[1], 0, 50) . '...' : '-') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        
        ?>
        
        <h3>4. Best Working Solution:</h3>
        <?php
        // Use the split approach which seems to work best
        function parse_evaluation_criteria_fixed($description) {
            $criteria = [
                '_mt_evaluation_courage' => '',
                '_mt_evaluation_innovation' => '',
                '_mt_evaluation_implementation' => '',
                '_mt_evaluation_relevance' => '',
                '_mt_evaluation_visibility' => '',
                '_mt_evaluation_personality' => ''
            ];
            
            if (empty($description)) {
                return $criteria;
            }
            
            // Split by labels
            $sections = preg_split('/(Mut\s*&\s*Pioniergeist:|Innovationsgrad:|Umsetzungskraft\s*&\s*Wirkung:|Relevanz\s*für\s*die\s*Mobilitätswende:|Vorbildfunktion\s*&\s*Sichtbarkeit:|Persönlichkeit\s*&\s*Motivation:)/u', $description, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
            
            $label_map = [
                'Mut & Pioniergeist' => '_mt_evaluation_courage',
                'Innovationsgrad' => '_mt_evaluation_innovation',
                'Umsetzungskraft & Wirkung' => '_mt_evaluation_implementation',
                'Relevanz für die Mobilitätswende' => '_mt_evaluation_relevance',
                'Vorbildfunktion & Sichtbarkeit' => '_mt_evaluation_visibility',
                'Persönlichkeit & Motivation' => '_mt_evaluation_personality'
            ];
            
            for ($i = 0; $i < count($sections) - 1; $i++) {
                $section = trim($sections[$i]);
                // Remove trailing colon
                $section_clean = rtrim($section, ':');
                
                foreach ($label_map as $label => $field) {
                    if (stripos($section_clean, $label) !== false && isset($sections[$i + 1])) {
                        $content = trim($sections[$i + 1]);
                        // Clean up the content
                        $content = preg_replace('/\s+/', ' ', $content);
                        $content = trim($content, " \t\n\r\0\x0B.,;:");
                        $criteria[$field] = $content;
                        break;
                    }
                }
            }
            
            return $criteria;
        }
        
        $fixed_result = parse_evaluation_criteria_fixed($test_description);
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Field</th><th>Status</th><th>Extracted Value</th></tr></thead>';
        echo '<tbody>';
        foreach ($fixed_result as $field => $value) {
            echo '<tr>';
            echo '<td><code>' . $field . '</code></td>';
            echo '<td>' . (!empty($value) ? '<span style="color: green;">✓ Success</span>' : '<span style="color: red;">✗ Empty</span>') . '</td>';
            echo '<td>' . (!empty($value) ? esc_html(substr($value, 0, 70)) . '...' : '-') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        ?>
    </div>
</div>