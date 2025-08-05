<?php
/**
 * Generate Sample Candidate Profiles
 * 
 * This script generates sample candidate profiles with German content
 *
 * @package MobilityTrailblazers
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Only run if accessed by admin
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

/**
 * Sample profile data in German
 */
function mt_get_sample_profiles() {
    return [
        [
            'title' => 'Dr. Anna Schmidt',
            'display_name' => 'Dr. Anna Schmidt',
            'organization' => 'Mobility Innovations GmbH',
            'position' => 'CEO & Gründerin',
            'linkedin' => 'https://www.linkedin.com/in/anna-schmidt',
            'website' => 'https://www.mobility-innovations.de',
            'overview' => '<p><strong>Dr. Anna Schmidt</strong> ist eine visionäre Unternehmerin im Bereich der nachhaltigen Mobilität. Nach ihrem Studium der Verkehrswissenschaften an der TU München und ihrer Promotion im Bereich autonomer Fahrsysteme gründete sie 2018 die Mobility Innovations GmbH.</p>
            <p>Unter ihrer Führung entwickelte das Unternehmen innovative Lösungen für die urbane Mikromobilität, darunter ein preisgekröntes E-Scooter-Sharing-System mit integrierter KI-basierter Routenoptimierung. Ihr Engagement für nachhaltige Verkehrslösungen wurde bereits mehrfach ausgezeichnet, unter anderem mit dem Deutschen Mobilitätspreis 2022.</p>
            <p>Dr. Schmidt ist regelmäßige Sprecherin auf internationalen Mobilitätskonferenzen und berät Städte bei der Transformation ihrer Verkehrssysteme. Sie ist Mitglied im Bundesverband Deutsche Startups und engagiert sich im Beirat für nachhaltige Mobilität der Stadt München.</p>',
            'evaluation_criteria' => '<h3>Mut</h3>
            <p>Dr. Schmidt zeigte außergewöhnlichen Mut, als sie ihr sicheres Angestelltenverhältnis bei einem etablierten Automobilhersteller aufgab, um ihr eigenes Startup zu gründen. Trotz anfänglicher Skepsis von Investoren blieb sie ihrer Vision treu und konnte schließlich bedeutende Funding-Runden abschließen.</p>
            
            <h3>Innovation</h3>
            <p>Die von ihr entwickelte KI-basierte Routenoptimierung für E-Scooter ist eine Weltneuheit. Das System reduziert nicht nur Verkehrsstaus, sondern optimiert auch die Batterielaufzeit der Fahrzeuge um bis zu 40%. Drei Patente wurden bereits angemeldet.</p>
            
            <h3>Umsetzung</h3>
            <p>Innerhalb von nur vier Jahren expandierte Mobility Innovations in 12 deutsche Städte und beschäftigt heute über 150 Mitarbeiter. Die Umsetzungsgeschwindigkeit und Skalierbarkeit des Geschäftsmodells gelten als Benchmark in der Branche.</p>
            
            <h3>Relevanz</h3>
            <p>Mit über 2 Millionen Fahrten pro Monat leistet das Unternehmen einen messbaren Beitrag zur Reduzierung des urbanen Autoverkehrs. Studien zeigen, dass 35% der Nutzer ihr Auto zugunsten der Mikromobilitätslösung stehen lassen.</p>
            
            <h3>Sichtbarkeit</h3>
            <p>Dr. Schmidt ist eine der bekanntesten Stimmen der deutschen Mobilitätswende. Mit über 50.000 LinkedIn-Followern, regelmäßigen Medienauftritten und ihrer TEDx-Talk mit über 500.000 Views prägt sie aktiv die öffentliche Diskussion.</p>',
            'personality_motivation' => '<p>Was Dr. Schmidt antreibt, ist ihre tiefe Überzeugung, dass nachhaltige Mobilität der Schlüssel zu lebenswerten Städten ist. "Jede eingesparte Autofahrt ist ein kleiner Sieg für unsere Umwelt", sagt sie oft.</p>
            <p>Kollegen beschreiben sie als inspirierend und hartnäckig zugleich. Ihre Fähigkeit, komplexe technische Konzepte verständlich zu kommunizieren, macht sie zu einer geschätzten Brückenbauerin zwischen Technologie und Gesellschaft.</p>
            <p>Privat ist Dr. Schmidt begeisterte Radfahrerin und hat bereits mehrere Alpenüberquerungen gemeistert. Diese Leidenschaft für nachhaltige Fortbewegung spiegelt sich in ihrer täglichen Arbeit wider. "Mobilität muss Spaß machen und gleichzeitig verantwortungsvoll sein", ist ihr Credo.</p>'
        ],
        [
            'title' => 'Prof. Dr. Michael Weber',
            'display_name' => 'Prof. Dr. Michael Weber',
            'organization' => 'Fraunhofer-Institut für Verkehrs- und Infrastruktursysteme',
            'position' => 'Institutsleiter',
            'linkedin' => 'https://www.linkedin.com/in/prof-weber',
            'website' => 'https://www.ivi.fraunhofer.de',
            'overview' => '<p><strong>Prof. Dr. Michael Weber</strong> ist seit 2015 Leiter des Fraunhofer-Instituts für Verkehrs- und Infrastruktursysteme IVI in Dresden. Der habilitierte Verkehrsingenieur gilt als einer der führenden Experten für intelligente Verkehrssysteme in Europa.</p>
            <p>Nach seinem Studium an der RWTH Aachen und Forschungsaufenthalten am MIT in Boston kehrte er nach Deutschland zurück, wo er zunächst in der Automobilindustrie tätig war. Seine Forschungsschwerpunkte liegen in den Bereichen autonomes Fahren, Verkehrsflussoptimierung und nachhaltige Logistikkonzepte.</p>
            <p>Unter seiner Leitung wurden am Fraunhofer IVI wegweisende Projekte zur Verkehrswende realisiert, darunter das deutschlandweit erste Testfeld für autonome Shuttles im öffentlichen Nahverkehr.</p>',
            'evaluation_criteria' => '<h3>Mut</h3>
            <p>Prof. Weber wagte es, traditionelle Ansätze der Verkehrsplanung in Frage zu stellen und setzte sich früh für radikal neue Konzepte ein. Sein Mut, auch gegen Widerstände aus Politik und Industrie für seine Überzeugungen einzustehen, machte ihn zu einem Vordenker der Branche.</p>
            
            <h3>Innovation</h3>
            <p>Die Entwicklung des "Digital Twin" für Verkehrssysteme revolutionierte die Verkehrsplanung. Diese Innovation ermöglicht es Städten, Verkehrsszenarien virtuell zu testen, bevor kostspielige Infrastrukturmaßnahmen umgesetzt werden.</p>
            
            <h3>Umsetzung</h3>
            <p>Mehr als 30 Städte nutzen bereits die am Institut entwickelten Lösungen. Die erfolgreiche Implementierung des autonomen Shuttle-Services in Dresden dient international als Referenzprojekt.</p>
            
            <h3>Relevanz</h3>
            <p>Die Forschungsarbeiten des Instituts haben direkten Einfluss auf die Verkehrspolitik in Deutschland und Europa. Die entwickelten Standards für autonomes Fahren wurden in die EU-Gesetzgebung übernommen.</p>
            
            <h3>Sichtbarkeit</h3>
            <p>Als gefragter Experte ist Prof. Weber regelmäßig in Fachgremien und Medien präsent. Seine Publikationen wurden über 10.000 Mal zitiert, und er berät die Bundesregierung in Fragen der Mobilitätswende.</p>',
            'personality_motivation' => '<p>Prof. Weber wird von seiner Vision einer vernetzten, nachhaltigen Mobilität angetrieben. "Wir stehen an der Schwelle zu einer neuen Ära der Fortbewegung", betont er immer wieder.</p>
            <p>Trotz seiner wissenschaftlichen Erfolge ist er bodenständig geblieben. Mitarbeiter schätzen seine offene Art und seine Fähigkeit, junge Talente zu fördern. Er hat bereits über 50 Doktoranden betreut.</p>
            <p>In seiner Freizeit engagiert sich Prof. Weber ehrenamtlich in der Verkehrserziehung und hält Vorträge an Schulen. "Die nächste Generation muss verstehen, dass Mobilität mehr ist als nur von A nach B zu kommen", ist seine Überzeugung.</p>'
        ],
        [
            'title' => 'Sarah Müller',
            'display_name' => 'Sarah Müller',
            'organization' => 'Green Mobility Solutions AG',
            'position' => 'Head of Innovation',
            'linkedin' => 'https://www.linkedin.com/in/sarah-mueller-mobility',
            'website' => 'https://www.green-mobility-solutions.com',
            'overview' => '<p><strong>Sarah Müller</strong> ist eine der jüngsten Führungskräfte in der deutschen Mobilitätsbranche. Mit nur 32 Jahren leitet sie die Innovationsabteilung der Green Mobility Solutions AG und verantwortet ein Team von 45 Experten.</p>
            <p>Nach ihrem Doppelstudium in Wirtschaftsingenieurwesen und Umweltwissenschaften sammelte sie erste Erfahrungen bei Tesla in Kalifornien, bevor sie nach Deutschland zurückkehrte. Ihre Expertise liegt in der Entwicklung ganzheitlicher Mobilitätskonzepte, die ökologische und ökonomische Aspekte vereinen.</p>
            <p>Unter ihrer Führung entstanden bahnbrechende Projekte wie das erste CO2-neutrale Carsharing-System Deutschlands und eine Blockchain-basierte Plattform für multimodale Mobilität.</p>',
            'evaluation_criteria' => '<h3>Mut</h3>
            <p>Sarah Müller bewies außergewöhnlichen Mut, als sie mit 28 Jahren die Verantwortung für ein Millionen-Budget übernahm und radikal neue Wege in der Produktentwicklung einschlug. Ihre Entscheidung, auf Blockchain-Technologie zu setzen, war zunächst umstritten, erwies sich aber als wegweisend.</p>
            
            <h3>Innovation</h3>
            <p>Die von ihr initiierte Mobility-as-a-Service-Plattform integriert erstmals alle Verkehrsmittel einer Stadt in einer einzigen App. Die innovative Preisgestaltung belohnt umweltfreundliches Verhalten mit Rabatten.</p>
            
            <h3>Umsetzung</h3>
            <p>Innerhalb von zwei Jahren wurde die Plattform in fünf Großstädten ausgerollt. Über 500.000 aktive Nutzer bestätigen den Erfolg des Konzepts. Die Umsetzungsgeschwindigkeit setzte neue Maßstäbe in der Branche.</p>
            
            <h3>Relevanz</h3>
            <p>Die entwickelten Lösungen tragen messbar zur Verkehrswende bei. Eine unabhängige Studie bestätigte eine CO2-Reduktion von 15% bei regelmäßigen Nutzern der Plattform.</p>
            
            <h3>Sichtbarkeit</h3>
            <p>Als "Young Leader" wurde Sarah Müller vom World Economic Forum ausgezeichnet. Sie ist gefragte Keynote-Speakerin und wurde vom Handelsblatt zu den "Top 40 unter 40" gezählt.</p>',
            'personality_motivation' => '<p>Sarah Müller brennt für die Vision einer klimaneutralen Mobilität. "Wir haben keine Zeit zu verlieren", ist ihr Motto, das sie täglich antreibt, innovative Lösungen voranzutreiben.</p>
            <p>Ihre Energie und Begeisterung sind ansteckend. Teammitglieder beschreiben sie als inspirierend und fordernd zugleich. Sie scheut sich nicht, etablierte Strukturen zu hinterfragen und neue Wege zu gehen.</p>
            <p>Privat lebt sie ihre Überzeugungen: Sie besitzt kein eigenes Auto, nutzt ausschließlich nachhaltige Verkehrsmittel und engagiert sich in ihrer Freizeit für Umweltbildung an Schulen.</p>'
        ]
    ];
}

/**
 * Generate sample candidates
 */
function mt_generate_sample_candidates() {
    echo '<div class="wrap">';
    echo '<h1>Generate Sample Candidate Profiles</h1>';
    
    if (isset($_POST['generate_samples']) && wp_verify_nonce($_POST['_wpnonce'], 'mt_generate_samples')) {
        $samples = mt_get_sample_profiles();
        $created = 0;
        $errors = 0;
        
        echo '<h2>Creating Sample Candidates...</h2>';
        echo '<ol>';
        
        foreach ($samples as $sample) {
            echo '<li>';
            echo 'Creating: <strong>' . esc_html($sample['title']) . '</strong>... ';
            
            // Create the candidate post
            $post_id = wp_insert_post([
                'post_title' => $sample['title'],
                'post_type' => 'mt_candidate',
                'post_status' => 'publish',
                'post_content' => $sample['overview'] // Use overview as main content too
            ]);
            
            if ($post_id && !is_wp_error($post_id)) {
                // Add all meta fields
                update_post_meta($post_id, '_mt_display_name', $sample['display_name']);
                update_post_meta($post_id, '_mt_organization', $sample['organization']);
                update_post_meta($post_id, '_mt_position', $sample['position']);
                update_post_meta($post_id, '_mt_linkedin', $sample['linkedin']);
                update_post_meta($post_id, '_mt_website', $sample['website']);
                update_post_meta($post_id, '_mt_overview', $sample['overview']);
                update_post_meta($post_id, '_mt_evaluation_criteria', $sample['evaluation_criteria']);
                update_post_meta($post_id, '_mt_personality_motivation', $sample['personality_motivation']);
                
                // Add to a sample category
                $category = term_exists('Innovation Leaders', 'mt_award_category');
                if (!$category) {
                    $category = wp_insert_term('Innovation Leaders', 'mt_award_category');
                }
                if ($category && !is_wp_error($category)) {
                    wp_set_post_terms($post_id, [$category['term_id']], 'mt_award_category');
                }
                
                echo '<span style="color: green;">✓ Created (ID: ' . $post_id . ')</span>';
                echo ' <a href="' . get_edit_post_link($post_id) . '" target="_blank">Edit</a>';
                echo ' | <a href="' . get_permalink($post_id) . '" target="_blank">View</a>';
                $created++;
            } else {
                echo '<span style="color: red;">✗ Failed</span>';
                $errors++;
            }
            
            echo '</li>';
        }
        
        echo '</ol>';
        
        echo '<div class="notice notice-' . ($errors === 0 ? 'success' : 'warning') . '">';
        echo '<p><strong>Generation Complete!</strong></p>';
        echo '<ul>';
        echo '<li>Successfully created: ' . $created . ' candidates</li>';
        if ($errors > 0) {
            echo '<li>Failed: ' . $errors . ' candidates</li>';
        }
        echo '</ul>';
        echo '</div>';
        
        echo '<p>';
        echo '<a href="' . admin_url('edit.php?post_type=mt_candidate') . '" class="button button-primary">View All Candidates</a> ';
        echo '<a href="' . wp_get_referer() . '" class="button">Back</a>';
        echo '</p>';
        
    } else {
        // Show confirmation form
        ?>
        <div class="card">
            <h2>What This Will Create</h2>
            <p>This will generate 3 sample candidate profiles with full German content including:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li><strong>Dr. Anna Schmidt</strong> - CEO & Gründerin, Mobility Innovations GmbH</li>
                <li><strong>Prof. Dr. Michael Weber</strong> - Institutsleiter, Fraunhofer IVI</li>
                <li><strong>Sarah Müller</strong> - Head of Innovation, Green Mobility Solutions AG</li>
            </ul>
            <p>Each profile will include:</p>
            <ul style="list-style-type: disc; margin-left: 20px;">
                <li>Complete biographical overview (Überblick)</li>
                <li>Detailed evaluation by all 5 criteria (Mut, Innovation, Umsetzung, Relevanz, Sichtbarkeit)</li>
                <li>Personality and motivation section</li>
                <li>LinkedIn and website URLs</li>
            </ul>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('mt_generate_samples'); ?>
            <p>
                <input type="submit" name="generate_samples" class="button button-primary" 
                       value="Generate Sample Candidates" 
                       onclick="return confirm('This will create 3 new candidate posts. Continue?');" />
                <a href="<?php echo admin_url('admin.php?page=mobility-trailblazers'); ?>" class="button">Cancel</a>
            </p>
        </form>
        
        <div class="card">
            <h2>Note</h2>
            <p>These are fictional profiles created for demonstration purposes. You can edit or delete them at any time from the Candidates page.</p>
        </div>
        <?php
    }
    
    echo '</div>';
}

// Run the generator
mt_generate_sample_candidates();
?>
