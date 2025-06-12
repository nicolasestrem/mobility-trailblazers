<?php
/**
 * Fake Candidates Generator for Mobility Trailblazers Platform
 * 
 * This script generates 500 realistic fake candidates for testing and demonstration
 * purposes, distributed across the three categories defined in the documentation:
 * 1. Established Companies
 * 2. Start-ups & New Makers  
 * 3. Infrastructure/Politics/Public Companies
 * 
 * Usage: Place in wp-content/ and run via WP-CLI:
 * docker exec mobility_wpcli_STAGING wp eval-file wp-content/fake-candidates-generator.php --path="/var/www/html"
 */

// Ensure we're in WordPress environment
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress environment.');
}

/**
 * Comprehensive data sets for generating realistic mobility candidates
 */
function get_candidate_data_sets() {
    return array(
        'established_companies' => array(
            'companies' => array(
                'BMW Group', 'Mercedes-Benz AG', 'Volkswagen AG', 'Audi AG', 'Porsche AG',
                'Deutsche Bahn AG', 'Siemens Mobility', 'Bosch Mobility Solutions', 'Continental AG',
                'ZF Friedrichshafen AG', 'Schaeffler Group', 'MAHLE GmbH', 'Daimler Truck AG',
                'MAN Truck & Bus', 'Scania', 'Lufthansa Group', 'Swiss International Air Lines',
                'Austrian Airlines', 'DHL Group', 'DB Schenker', 'ÖBB', 'SBB', 'BVG Berlin',
                'Münchner Verkehrsgesellschaft', 'Wiener Linien', 'Zurich Airport', 'Munich Airport',
                'Fraport AG', 'AMAG Group', 'Auto-Schweiz', 'Michelin', 'Bridgestone',
                'Shell Deutschland', 'BP Europa', 'TotalEnergies', 'E.ON', 'RWE AG',
                'EnBW', 'Verbund AG', 'BKW Energie AG', 'Swisscom', 'Deutsche Telekom',
                'Vodafone Germany', 'A1 Telekom Austria', 'Sunrise UPC', 'SAP SE',
                'Software AG', 'Infineon Technologies', 'ASML', 'NXP Semiconductors'
            ),
            'positions' => array(
                'Chief Executive Officer', 'Chief Technology Officer', 'Chief Innovation Officer',
                'Head of Mobility Solutions', 'VP Digital Transformation', 'Director of E-Mobility',
                'Head of Autonomous Driving', 'VP Sustainable Transportation', 'Chief Digital Officer',
                'Director of Future Mobility', 'Head of Connected Services', 'VP Smart Cities',
                'Director of Electric Vehicles', 'Head of Logistics Innovation', 'VP Supply Chain',
                'Director of Mobility-as-a-Service', 'Head of Urban Mobility', 'VP Transportation',
                'Director of Fleet Management', 'Head of Green Logistics', 'VP Strategic Partnerships',
                'Director of Mobility Data', 'Head of Customer Experience', 'VP Product Innovation',
                'Director of Sustainability', 'Head of New Business Models', 'VP Corporate Strategy',
                'Director of Emerging Technologies', 'Head of Digital Mobility', 'VP Engineering',
                'Director of Smart Infrastructure', 'Head of Mobility Platforms', 'VP Operations',
                'Director of Future Transport', 'Head of Innovation Lab', 'VP Business Development'
            )
        ),
        'startups_new_makers' => array(
            'companies' => array(
                'Tier Mobility', 'FREE NOW', 'MILES Mobility', 'Share Now', 'WeShare',
                'Lime Germany', 'Bird Germany', 'Voi Technology', 'COUP Mobility',
                'Emmy Sharing', 'MotionTag', 'Clevershuttle', 'BlaBlaCar Germany',
                'FlixBus', 'Omio', 'GoEuro', 'Door2Door', 'Via Transportation',
                'Taxify Austria', 'mytaxi Switzerland', 'Carify', 'Nextbike', 'Call a Bike',
                'Stadtrad Hamburg', 'MVG Rad', 'WienMobil', 'Mobility Cooperative',
                'Catch a Car', 'DriveNow Solutions', 'Car2Go Innovation', 'Zipcar Europe',
                'Getaround Germany', 'Turo Germany', 'SnappCar', 'Drivy Germany',
                'ParkHere', 'PARK NOW', 'EasyPark', 'ParkTag', 'Ampido',
                'ChargePoint Europe', 'Ionity', 'Fastned', 'Allego', 'NewMotion',
                'PlugSurfing', 'Chargemap', 'ElectricFeel', 'eeMobility', 'GridX',
                'sonnen GmbH', 'Tado°', 'KIWI.KI', 'Lilium Aviation', 'Volocopter',
                'e.SAT', 'Urban-Air Port', 'Skyports', 'Bauhaus Luftfahrt', 'Wright Electric Europe'
            ),
            'positions' => array(
                'Founder & CEO', 'Co-Founder & CTO', 'Founder & CPO', 'CEO & Co-Founder',
                'Chief Technology Officer', 'Chief Product Officer', 'Head of Growth',
                'VP Engineering', 'Director of Product', 'Head of Operations',
                'VP Business Development', 'Director of Partnerships', 'Head of Marketing',
                'VP Customer Success', 'Director of Data Science', 'Head of Design',
                'VP Strategy', 'Director of Mobility', 'Head of Innovation',
                'VP Sales', 'Director of Technology', 'Head of Expansion',
                'VP Product Management', 'Director of Platform', 'Head of Research',
                'VP Analytics', 'Director of User Experience', 'Head of Sustainability',
                'VP Ecosystem', 'Director of Community', 'Head of Partnerships',
                'VP International', 'Director of Innovation Lab', 'Head of Future Mobility',
                'VP Digital Strategy', 'Director of Emerging Tech'
            )
        ),
        'infrastructure_politics_public' => array(
            'organizations' => array(
                'Bundesministerium für Verkehr und digitale Infrastruktur',
                'Ministerium für Verkehr Baden-Württemberg', 'Bayerisches Staatsministerium',
                'Senatsverwaltung für Umwelt Berlin', 'Hamburger Verkehrsverbund',
                'Münchener Verkehrs- und Tarifverbund', 'Verkehrsverbund Berlin-Brandenburg',
                'Wiener Linien GmbH', 'Verkehrsverbund Ost-Region', 'Zürcher Verkehrsverbund',
                'Bundesamt für Verkehr (Schweiz)', 'Österreichische Bundesbahnen',
                'DB Netz AG', 'DB Station&Service AG', 'ProRail Netherlands',
                'Autobahn GmbH des Bundes', 'ASFINAG', 'Bundesamt für Strassen ASTRA',
                'Landesverkehrswacht Bayern', 'ADAC', 'ÖAMTC', 'TCS Schweiz',
                'Verkehrsclub Deutschland', 'Verkehrsclub Österreich', 'VCS Schweiz',
                'Bundesverband eMobilität', 'Austrian Mobile Power', 'Swiss eMobility',
                'Nationaler Wasserstoffrat', 'H2 Mobility Deutschland', 'H2 Austria',
                'Hydropole Schweiz', 'Deutsche Energie-Agentur', 'Austrian Energy Agency',
                'Bundesamt für Energie Schweiz', 'Agora Verkehrswende', 'VCÖ Österreich',
                'Transport & Environment', 'UITP Europe', 'ERTICO - ITS Europe',
                'European Commission DG MOVE', 'UNECE Transport Division',
                'International Transport Forum', 'POLIS Network', 'Eurocities',
                'Climate Alliance', 'ICLEI Europe', 'C40 Cities', 'Smart Cities Marketplace'
            ),
            'positions' => array(
                'Staatssekretär für Verkehr', 'Referatsleiter Digitale Mobilität',
                'Abteilungsleiter Verkehrspolitik', 'Geschäftsführer Verkehrsverbund',
                'Direktor Verkehrsbetriebe', 'Vorstand Öffentlicher Verkehr',
                'Bereichsleiter Infrastruktur', 'Projektleiter Smart City',
                'Abteilungsleiter Nachhaltigkeit', 'Referent Elektromobilität',
                'Geschäftsführer Mobilitätsagentur', 'Direktor Verkehrsplanung',
                'Bereichsleiter Innovation', 'Projektmanager Digitalisierung',
                'Abteilungsleiter Klimaschutz', 'Referatsleiter Luftreinhaltung',
                'Geschäftsführer Energieagentur', 'Direktor Forschungsinstitut',
                'Bereichsleiter Technologie', 'Projektleiter Wasserstoff',
                'Abteilungsleiter Förderung', 'Referent EU-Programme',
                'Geschäftsführer Branchenverband', 'Direktor Think Tank',
                'Bereichsleiter Policy', 'Projektmanager Transformation',
                'Abteilungsleiter Strategie', 'Referatsleiter Zukunftstechnologien',
                'Geschäftsführer Cluster', 'Direktor Kompetenzzentrum',
                'Bereichsleiter Kooperation', 'Projektleiter Modellregion',
                'Senator für Verkehr', 'Bürgermeister für Mobilität',
                'Stadtrat für Verkehr', 'Dezernent für Stadtentwicklung'
            )
        ),
        'first_names' => array(
            'Alexander', 'Andreas', 'Anna', 'Astrid', 'Benjamin', 'Birgit', 'Carsten', 'Christine',
            'Daniel', 'Diana', 'Eberhard', 'Elisabeth', 'Felix', 'Franziska', 'Georg', 'Gisela',
            'Hans', 'Heike', 'Helmut', 'Ingrid', 'Jan', 'Julia', 'Jürgen', 'Karin',
            'Klaus', 'Kristina', 'Lars', 'Laura', 'Manfred', 'Maria', 'Martin', 'Martina',
            'Michael', 'Monika', 'Nikolaus', 'Nicole', 'Oliver', 'Petra', 'Peter', 'Renate',
            'Robert', 'Sabine', 'Sebastian', 'Silvia', 'Stefan', 'Susanne', 'Thomas', 'Ulrike',
            'Viktor', 'Veronika', 'Wolfgang', 'Yvonne', 'Maximilian', 'Sophie', 'David', 'Emma',
            'Lukas', 'Lena', 'Florian', 'Sarah', 'Tobias', 'Hannah', 'Marco', 'Katharina',
            'Philipp', 'Lisa', 'Matthias', 'Jennifer', 'Simon', 'Michelle', 'Markus', 'Sandra',
            'Christian', 'Vanessa', 'Patrick', 'Stephanie', 'Ralf', 'Claudia', 'Jochen', 'Tanja'
        ),
        'last_names' => array(
            'Müller', 'Schmidt', 'Schneider', 'Fischer', 'Weber', 'Meyer', 'Wagner', 'Becker',
            'Schulz', 'Hoffmann', 'Koch', 'Richter', 'Klein', 'Wolf', 'Schröder', 'Neumann',
            'Schwarz', 'Zimmermann', 'Braun', 'Krüger', 'Hofmann', 'Hartmann', 'Lange', 'Schmitt',
            'Werner', 'Schmitz', 'Krause', 'Meier', 'Lehmann', 'Schmid', 'Schulze', 'Maier',
            'Köhler', 'Herrmann', 'König', 'Walter', 'Mayer', 'Huber', 'Kaiser', 'Fuchs',
            'Peters', 'Lang', 'Scholz', 'Möller', 'Weiß', 'Jung', 'Hahn', 'Schubert',
            'Winkel', 'Berger', 'Vogel', 'Friedrich', 'Keller', 'Günther', 'Frank', 'Lehmann',
            'Groß', 'Roth', 'Beck', 'Lorenz', 'Baumann', 'Franke', 'Albrecht', 'Ludwig',
            'Winter', 'Kraus', 'Martin', 'Schenk', 'Krämer', 'Vogt', 'Stein', 'Jäger',
            'Otto', 'Sommer', 'Graf', 'Brandt', 'Haas', 'Schuster', 'Brunner', 'Winkler'
        ),
        'locations' => array(
            'München, Deutschland', 'Berlin, Deutschland', 'Hamburg, Deutschland', 'Frankfurt am Main, Deutschland',
            'Stuttgart, Deutschland', 'Düsseldorf, Deutschland', 'Köln, Deutschland', 'Leipzig, Deutschland',
            'Dresden, Deutschland', 'Hannover, Deutschland', 'Nürnberg, Deutschland', 'Mannheim, Deutschland',
            'Wien, Österreich', 'Salzburg, Österreich', 'Graz, Österreich', 'Innsbruck, Österreich',
            'Linz, Österreich', 'Klagenfurt, Österreich', 'Bregenz, Österreich', 'St. Pölten, Österreich',
            'Zürich, Schweiz', 'Basel, Schweiz', 'Genf, Schweiz', 'Bern, Schweiz',
            'Lausanne, Schweiz', 'Winterthur, Schweiz', 'Luzern, Schweiz', 'St. Gallen, Schweiz',
            'Lugano, Schweiz', 'Biel/Bienne, Schweiz', 'Thun, Schweiz', 'Köniz, Schweiz'
        ),
        'innovation_themes' => array(
            'Entwicklung autonomer Fahrzeuge für den urbanen Raum',
            'Implementierung von KI-gestützten Verkehrsflussoptimierungen',
            'Aufbau einer flächendeckenden Ladeinfrastruktur für Elektrofahrzeuge',
            'Einführung von Wasserstoff-Brennstoffzellen-Technologie im ÖPNV',
            'Realisierung einer integrierten Mobility-as-a-Service Plattform',
            'Entwicklung nachhaltiger Logistiklösungen für die letzte Meile',
            'Implementierung von Smart Traffic Management Systemen',
            'Aufbau eines seamless multimodalen Verkehrssystems',
            'Entwicklung von emissionsfreien Lufttaxis für städtische Mobilität',
            'Einführung von digitalen Zwillingen für Verkehrsinfrastruktur',
            'Realisierung von Vehicle-to-Everything (V2X) Kommunikation',
            'Entwicklung nachhaltiger Sharing-Economy-Lösungen',
            'Implementierung von Blockchain-basierten Mobilitätslösungen',
            'Aufbau von intelligenten Parksystemen mit IoT-Integration',
            'Entwicklung von Predictive Analytics für Verkehrsprognosen',
            'Einführung von AR/VR-Technologien in der Fahrzeugentwicklung',
            'Realisierung von klimaneutralen Transportketten',
            'Entwicklung von adaptiven Verkehrsleitsystemen',
            'Implementierung von Edge Computing in der Verkehrssteuerung',
            'Aufbau von nachhaltigen Mikromobilitätslösungen'
        ),
        'courage_stories' => array(
            'Mutige Entscheidung, das traditionelle Geschäftsmodell zu revolutionieren und vollständig auf nachhaltige Mobilität umzustellen',
            'Widerstand gegen etablierte Strukturen und Entwicklung einer disruptiven Technologie trotz interner Kritik',
            'Persönliches Risiko durch Verlassen einer sicheren Position, um ein innovatives Mobilitäts-Startup zu gründen',
            'Durchsetzung einer kontroversen aber zukunftsweisenden Strategie gegen massive Interessensgruppen',
            'Mutige Investition in unerprobte Technologien trotz unsicherer Marktlage und hohem finanziellen Risiko',
            'Öffentliche Positionierung für radikale Verkehrswende trotz politischen und wirtschaftlichen Drucks',
            'Entscheidung, als erste/r im Sektor eine neue nachhaltige Technologie einzuführen',
            'Aufbau einer internationalen Koalition für Mobilitätswandel trotz nationaler Widerstände',
            'Riskante Pivot-Entscheidung von traditioneller zu digitaler Mobilität in kritischer Unternehmensphase',
            'Mutige Transparenz-Initiative zur Offenlegung von Nachhaltigkeitsdaten trotz Wettbewerbsnachteilen',
            'Entscheidung zur kompletten Neuausrichtung der Unternehmensstrategie auf Kreislaufwirtschaft',
            'Aufbau einer kontroversen aber notwendigen öffentlich-privaten Partnerschaft',
            'Persönlicher Einsatz für unpopuläre aber zukunftsweisende Mobilitätspolitik',
            'Riskante technologische Weichenstellung für noch unerprobte Antriebstechnologien',
            'Mutige Entscheidung, traditionelle Lieferketten komplett zu überdenken und neu aufzubauen'
        )
    );
}

/**
 * Generate a single realistic candidate
 */
function generate_candidate($category, $data_sets, $index) {
    $first_name = $data_sets['first_names'][array_rand($data_sets['first_names'])];
    $last_name = $data_sets['last_names'][array_rand($data_sets['last_names'])];
    $name = $first_name . ' ' . $last_name;
    
    // Generate title prefix randomly
    $title_prefixes = array('', '', '', 'Dr. ', 'Prof. ', 'Prof. Dr. ');
    $title_prefix = $title_prefixes[array_rand($title_prefixes)];
    $full_name = $title_prefix . $name;
    
    $location = $data_sets['locations'][array_rand($data_sets['locations'])];
    $innovation = $data_sets['innovation_themes'][array_rand($data_sets['innovation_themes'])];
    $courage_story = $data_sets['courage_stories'][array_rand($data_sets['courage_stories'])];
    
    // Category-specific data
    switch ($category) {
        case 'established-companies':
            $company = $data_sets['established_companies']['companies'][array_rand($data_sets['established_companies']['companies'])];
            $position = $data_sets['established_companies']['positions'][array_rand($data_sets['established_companies']['positions'])];
            $category_slug = 'established-companies';
            break;
            
        case 'startups-new-makers':
            $company = $data_sets['startups_new_makers']['companies'][array_rand($data_sets['startups_new_makers']['companies'])];
            $position = $data_sets['startups_new_makers']['positions'][array_rand($data_sets['startups_new_makers']['positions'])];
            $category_slug = 'startups-new-makers';
            break;
            
        case 'infrastructure-politics-public':
            $company = $data_sets['infrastructure_politics_public']['organizations'][array_rand($data_sets['infrastructure_politics_public']['organizations'])];
            $position = $data_sets['infrastructure_politics_public']['positions'][array_rand($data_sets['infrastructure_politics_public']['positions'])];
            $category_slug = 'infrastructure-politics-public';
            break;
    }
    
    // Generate email
    $email_name = strtolower(str_replace(array(' ', '.', 'ä', 'ö', 'ü', 'ß'), array('', '', 'ae', 'oe', 'ue', 'ss'), $name));
    $email_domain = strtolower(str_replace(array(' ', 'ä', 'ö', 'ü', 'ß', '&', '-', '.'), array('', 'ae', 'oe', 'ue', 'ss', '', '', ''), $company));
    $email = $email_name . '@' . substr($email_domain, 0, 15) . '.com';
    
    // Generate LinkedIn URL
    $linkedin_name = strtolower(str_replace(array(' ', '.', 'ä', 'ö', 'ü', 'ß'), array('-', '', 'ae', 'oe', 'ue', 'ss'), $name));
    $linkedin = 'https://www.linkedin.com/in/' . $linkedin_name . '-' . rand(100, 999);
    
    // Generate website (for some candidates)
    $website = '';
    if (rand(1, 3) == 1) { // 33% chance of having a personal website
        $website_name = strtolower(str_replace(array(' ', '.', 'ä', 'ö', 'ü', 'ß'), array('', '', 'ae', 'oe', 'ue', 'ss'), $name));
        $website = 'https://www.' . $website_name . '.com';
    }
    
    // Generate impact metrics
    $impact_metrics = array(
        'Reduktion der CO2-Emissionen um ' . rand(15, 60) . '% in ' . rand(1, 4) . ' Jahren',
        'Steigerung der Effizienz um ' . rand(20, 80) . '% durch innovative Technologien',
        'Aufbau eines Netzwerks von über ' . number_format(rand(50, 5000), 0, ',', '.') . ' Partnern',
        'Einsparung von ' . rand(2, 50) . ' Millionen Euro durch Optimierungsmaßnahmen',
        'Schaffung von ' . rand(50, 2000) . ' neuen Arbeitsplätzen im Mobilitätssektor',
        'Implementierung in ' . rand(5, 100) . ' Städten im DACH-Raum',
        'Verbesserung der Nutzerexperience um ' . rand(30, 90) . '% gemessen an Kundenzufriedenheit',
        'Reduzierung der Transportzeit um durchschnittlich ' . rand(10, 45) . ' Minuten',
        'Erhöhung der Verkehrssicherheit um ' . rand(25, 70) . '% in betroffenen Bereichen'
    );
    
    $selected_metrics = array_rand($impact_metrics, rand(2, 4));
    $impact_description = '';
    foreach ($selected_metrics as $metric_index) {
        $impact_description .= $impact_metrics[$metric_index] . '. ';
    }
    
    // Generate comprehensive bio
    $bio = "Als " . $position . " bei " . $company . " prägt " . $first_name . " " . $last_name . " maßgeblich die Zukunft der Mobilität im DACH-Raum. " .
           "Mit über " . rand(8, 25) . " Jahren Erfahrung in der Mobilitätsbranche hat " . ($title_prefix ? 'sie/er' : $first_name) . " " .
           "bereits mehrere wegweisende Projekte erfolgreich umgesetzt. " .
           $innovation . " steht im Zentrum der aktuellen Arbeit. " .
           "Durch " . strtolower($courage_story) . " hat " . $first_name . " bewiesen, dass nachhaltiger Wandel möglich ist. " .
           "Die Expertise umfasst digitale Transformation, nachhaltige Technologien und strategische Partnerschaften. " .
           $impact_description . "Mit dem Standort " . $location . " ist " . $first_name . " " .
           "bestens vernetzt im regionalen Mobilitäts-Ökosystem und treibt grenzüberschreitende Kooperationen voran.";
    
    // Generate excerpt (first 150 characters of bio)
    $excerpt = substr($bio, 0, 150) . '...';
    
    return array(
        'name' => $full_name,
        'company' => $company,
        'position' => $position,
        'location' => $location,
        'email' => $email,
        'linkedin' => $linkedin,
        'website' => $website,
        'innovation_description' => $innovation,
        'impact_metrics' => trim($impact_description),
        'courage_story' => $courage_story,
        'bio' => $bio,
        'excerpt' => $excerpt,
        'category_slug' => $category_slug
    );
}

/**
 * Main function to generate and import 500 candidates
 */
function generate_500_candidates() {
    $data_sets = get_candidate_data_sets();
    $imported_count = 0;
    $errors = array();
    
    echo "Starting generation of 500 Mobility Trailblazers candidates...\n";
    echo "Distribution: 200 Established Companies, 200 Start-ups & New Makers, 100 Infrastructure/Politics/Public\n\n";
    
    // Distribution: 200 established, 200 startups, 100 infrastructure
    $categories = array(
        'established-companies' => 200,
        'startups-new-makers' => 200,
        'infrastructure-politics-public' => 100
    );
    
    $overall_index = 1;
    
    foreach ($categories as $category => $count) {
        echo "Generating {$count} candidates for category: {$category}\n";
        
        for ($i = 1; $i <= $count; $i++) {
            try {
                $candidate = generate_candidate($category, $data_sets, $i);
                
                echo "Creating candidate {$overall_index}/500: {$candidate['name']} ({$candidate['company']})\n";
                
                // Create WordPress post
                $post_id = wp_insert_post(array(
                    'post_type' => 'mt_candidate',
                    'post_title' => $candidate['name'],
                    'post_content' => $candidate['bio'],
                    'post_excerpt' => $candidate['excerpt'],
                    'post_status' => 'publish',
                    'post_author' => 1,
                    'meta_input' => array(
                        '_mt_company' => $candidate['company'],
                        '_mt_position' => $candidate['position'],
                        '_mt_location' => $candidate['location'],
                        '_mt_email' => $candidate['email'],
                        '_mt_linkedin' => $candidate['linkedin'],
                        '_mt_website' => $candidate['website'],
                        '_mt_innovation_description' => $candidate['innovation_description'],
                        '_mt_impact_metrics' => $candidate['impact_metrics'],
                        '_mt_courage_story' => $candidate['courage_story']
                    )
                ));
                
                if (is_wp_error($post_id)) {
                    throw new Exception('Failed to create post: ' . $post_id->get_error_message());
                }
                
                // Assign to category
                wp_set_post_terms($post_id, array($candidate['category_slug']), 'mt_category');
                
                // Assign to current award year
                wp_set_post_terms($post_id, array('2025'), 'mt_award_year');
                
                // Randomly assign status (weighted towards longlist)
                $status_weights = array(
                    'longlist' => 70,      // 70% longlist
                    'shortlist' => 20,     // 20% shortlist  
                    'finalist' => 8,       // 8% finalist
                    'winner' => 2          // 2% winner
                );
                
                $random_num = rand(1, 100);
                $cumulative = 0;
                $selected_status = 'longlist';
                
                foreach ($status_weights as $status => $weight) {
                    $cumulative += $weight;
                    if ($random_num <= $cumulative) {
                        $selected_status = $status;
                        break;
                    }
                }
                
                wp_set_post_terms($post_id, array($selected_status), 'mt_status');
                
                $imported_count++;
                $overall_index++;
                
                // Add small delay to prevent overwhelming the system
                if ($overall_index % 50 == 0) {
                    echo "  → Processed {$overall_index} candidates, taking a short break...\n";
                    sleep(1);
                }
                
            } catch (Exception $e) {
                $error_msg = "Error creating candidate {$overall_index}: " . $e->getMessage();
                $errors[] = $error_msg;
                echo "  ✗ " . $error_msg . "\n";
                $overall_index++;
            }
        }
        
        echo "Completed {$category}: {$count} candidates processed\n\n";
    }
    
    // Generate summary statistics
    echo "=== GENERATION SUMMARY ===\n";
    echo "Total candidates processed: 500\n";
    echo "Successfully created: {$imported_count}\n";
    echo "Errors: " . count($errors) . "\n\n";
    
    if (!empty($errors)) {
        echo "Errors encountered:\n";
        foreach (array_slice($errors, 0, 10) as $error) { // Show only first 10 errors
            echo "- {$error}\n";
        }
        if (count($errors) > 10) {
            echo "... and " . (count($errors) - 10) . " more errors\n";
        }
        echo "\n";
    }
    
    // Verification
    echo "=== VERIFICATION ===\n";
    
    // Count by category
    foreach ($categories as $category => $expected_count) {
        $actual_count = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'mt_category',
                    'field' => 'slug',
                    'terms' => $category
                )
            )
        ));
        echo "Category '{$category}': " . count($actual_count) . " candidates\n";
    }
    
    // Count by status
    $all_statuses = array('longlist', 'shortlist', 'finalist', 'winner');
    echo "\nStatus distribution:\n";
    foreach ($all_statuses as $status) {
        $status_count = get_posts(array(
            'post_type' => 'mt_candidate',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'mt_status',
                    'field' => 'slug',
                    'terms' => $status
                )
            )
        ));
        echo "Status '{$status}': " . count($status_count) . " candidates\n";
    }
    
    // Total verification
    $total_count = wp_count_posts('mt_candidate')->publish;
    echo "\nTotal published candidates in database: {$total_count}\n";
    
    echo "\n=== NEXT STEPS ===\n";
    echo "1. Visit WordPress admin to view candidates: MT Award System → Candidates\n";
    echo "2. Test jury evaluation interface with generated candidates\n";
    echo "3. Test public voting with shortcoded candidate displays\n";
    echo "4. Verify all candidate metadata is properly displayed\n";
    echo "5. Test filtering and search functionality\n";
    
    echo "\nCandidate generation completed successfully!\n";
}

// Run the generation only if MT plugin is active
if (!function_exists('wp_count_posts')) {
    die("WordPress post functions not available. Please run this script through WP-CLI.\n");
}

// Check if Mobility Trailblazers plugin is active
if (!post_type_exists('mt_candidate')) {
    die("Mobility Trailblazers plugin not active or candidate post type not registered.\n");
}

generate_500_candidates();

?>