<?php
/**
 * Jury Import Script for Mobility Trailblazers
 * 
 * This script imports all 20 jury members from the PDF documentation
 * Pages 6-7 of the Mobility Trailblazers project overview
 * 
 * Usage: Place in wp-content/ and run via WP-CLI:
 * docker-compose exec wordpress wp eval-file wp-content/jury-import.php --allow-root
 */

// Ensure we're in WordPress environment
if (!defined('ABSPATH')) {
    die('This script must be run within WordPress environment.');
}

// Get global $wpdb
global $wpdb;

// Check if assignments table exists
$table_name = $wpdb->prefix . 'mt_assignments';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;

if (!$table_exists) {
    echo "Warning: Assignments table does not exist. Skipping assignment creation.\n";
    echo "Please ensure the Mobility Trailblazers plugin is properly activated.\n\n";
}

// Before importing jury members, ensure the role exists
if (!get_role('mt_jury_member')) {
    add_role('mt_jury_member', __('Jury Member', 'mobility-trailblazers'), array(
        'read' => true,
        'mt_evaluate_candidates' => true,
        'mt_view_assignments' => true,
        'mt_submit_evaluations' => true,
        'upload_files' => true
    ));
}

/**
 * Complete jury member data from PDF pages 6-7
 */
function get_jury_members_data() {
    return array(
        // Page 6 - Top Row
        array(
            'name' => 'Prof. Dr. Andreas Herrmann',
            'title' => 'Präsident',
            'company' => 'Institut für Mobilität an der Universität St. Gallen',
            'position' => 'Gründer und Direktor des Instituts für Mobilität an der Universität St. Gallen',
            'expertise' => 'Mobilität & Leadership',
            'role' => 'president',
            'email' => 'andreas.herrmann@unisg.ch',
            'linkedin' => 'https://www.linkedin.com/in/andreas-herrmann-unisg/',
            'bio' => 'Prof. Dr. Andreas Herrmann ist Gründer und Direktor des Instituts für Mobilität an der Universität St. Gallen. Er ist ein führender Experte für Mobilität und Automotive und leitet das renommierte Institut für Mobilität.'
        ),
        array(
            'name' => 'Prof. em. Dr. Dr. h.c. Torsten Tomczak',
            'title' => 'Vize-Präsident',
            'company' => 'Institut für Mobilität an der Universität St. Gallen',
            'position' => 'Gründer des Instituts für Mobilität an der Universität St. Gallen',
            'expertise' => 'Mobilität & Strategie',
            'role' => 'vice_president',
            'email' => 'torsten.tomczak@unisg.ch',
            'linkedin' => 'https://www.linkedin.com/in/torsten-tomczak/',
            'bio' => 'Prof. em. Dr. Dr. h.c. Torsten Tomczak ist Gründer des Instituts für Mobilität an der Universität St. Gallen und ein anerkannter Experte für strategisches Marketing und Mobilität.'
        ),
        array(
            'name' => 'Katja Busch',
            'title' => 'Customer Solutions & Innovation',
            'company' => 'DHL Group',
            'position' => 'Chief Commercial Officer und Head Customer Solutions & Innovation DHL Group',
            'expertise' => 'Logistics & Innovation',
            'role' => 'member',
            'email' => 'katja.busch@dhl.com',
            'linkedin' => 'https://www.linkedin.com/in/katja-busch/',
            'bio' => 'Katja Busch ist Chief Commercial Officer und Head Customer Solutions & Innovation bei der DHL Group und verantwortet innovative Lösungen in der globalen Logistik.'
        ),
        array(
            'name' => 'Dr. Astrid Fontaine',
            'title' => 'HR & Nachhaltigkeit',
            'company' => 'Schaeffler Gruppe',
            'position' => 'Vorständin Schaeffler Gruppe',
            'expertise' => 'HR & Nachhaltigkeit',
            'role' => 'member',
            'email' => 'astrid.fontaine@schaeffler.com',
            'linkedin' => 'https://www.linkedin.com/in/astrid-fontaine/',
            'bio' => 'Dr. Astrid Fontaine ist Vorständin der Schaeffler Gruppe und verantwortet HR und Nachhaltigkeit in einem der weltweit führenden Automobilzulieferer.'
        ),
        array(
            'name' => 'Winfried Hermann',
            'title' => 'Schirmherr',
            'company' => 'Land Baden-Württemberg',
            'position' => 'Verkehrsminister Baden-Württemberg',
            'expertise' => 'Verkehrspolitik',
            'role' => 'member',
            'email' => 'minister@vm.bwl.de',
            'linkedin' => 'https://www.linkedin.com/in/winfried-hermann/',
            'bio' => 'Winfried Hermann ist Verkehrsminister von Baden-Württemberg und Schirmherr der Mobility Trailblazers Initiative.'
        ),
        
        // Page 6 - Bottom Row
        array(
            'name' => 'Prof. Dr. Oliver Gassmann',
            'title' => 'Innovation & Geschäftsmodelle',
            'company' => 'Universität St. Gallen',
            'position' => 'Direktor des Instituts für Technologiemanagement an der Universität St. Gallen',
            'expertise' => 'Innovation & Technologiemanagement',
            'role' => 'member',
            'email' => 'oliver.gassmann@unisg.ch',
            'linkedin' => 'https://www.linkedin.com/in/oliver-gassmann/',
            'bio' => 'Prof. Dr. Oliver Gassmann ist Direktor des Instituts für Technologiemanagement an der Universität St. Gallen und ein führender Experte für Innovation und Geschäftsmodelle.'
        ),
        array(
            'name' => 'Peter Grünenfelder',
            'title' => 'Automobil',
            'company' => 'Auto-Schweiz',
            'position' => 'Präsident Auto-Schweiz',
            'expertise' => 'Automobilindustrie Schweiz',
            'role' => 'member',
            'email' => 'peter.gruenenfelder@auto.ch',
            'linkedin' => 'https://www.linkedin.com/in/peter-gruenenfelder/',
            'bio' => 'Peter Grünenfelder ist Präsident von Auto-Schweiz, dem Verband der Schweizer Automobilimporteure.'
        ),
        array(
            'name' => 'Dr. Kjell Gruner',
            'title' => 'Automobil & USA',
            'company' => 'Volkswagen Group of America',
            'position' => 'CEO & President Volkswagen Group of America',
            'expertise' => 'Automobilindustrie & USA',
            'role' => 'member',
            'email' => 'kjell.gruner@vw.com',
            'linkedin' => 'https://www.linkedin.com/in/kjell-gruner/',
            'bio' => 'Dr. Kjell Gruner ist CEO & President der Volkswagen Group of America und verantwortet das Nordamerika-Geschäft des Volkswagen Konzerns.'
        ),
        array(
            'name' => 'Prof. Dr. Zheng Han',
            'title' => 'Mobilität & China',
            'company' => 'Tongji University, Shanghai',
            'position' => 'Professor für Innovation & Unternehmertum, Tongji University, Shanghai',
            'expertise' => 'Mobilität & China',
            'role' => 'member',
            'email' => 'zheng.han@tongji.edu.cn',
            'linkedin' => 'https://www.linkedin.com/in/zheng-han/',
            'bio' => 'Prof. Dr. Zheng Han ist Professor für Innovation & Unternehmertum an der Tongji University in Shanghai und Experte für den chinesischen Mobilitätsmarkt.'
        ),
        array(
            'name' => 'Prof. Dr. Wolfgang Jenewein',
            'title' => 'Transformation & Leadership',
            'company' => 'Jenewein AG',
            'position' => 'Geschäftsführender Inhaber der Jenewein AG',
            'expertise' => 'Transformation & Leadership',
            'role' => 'member',
            'email' => 'wolfgang.jenewein@jenewein.com',
            'linkedin' => 'https://www.linkedin.com/in/wolfgang-jenewein/',
            'bio' => 'Prof. Dr. Wolfgang Jenewein ist Geschäftsführender Inhaber der Jenewein AG und ein renommierter Experte für Transformation und Leadership.'
        ),
        
        // Page 7 - Top Row
        array(
            'name' => 'Johann Jungwirth',
            'title' => 'Technologie & Innovation',
            'company' => 'Mobileye',
            'position' => 'Executive Vice President Mobileye',
            'expertise' => 'Technologie & Innovation',
            'role' => 'member',
            'email' => 'johann.jungwirth@mobileye.com',
            'linkedin' => 'https://www.linkedin.com/in/johann-jungwirth/',
            'bio' => 'Johann Jungwirth ist Executive Vice President bei Mobileye und ein führender Experte für autonome Fahrtechnologien und Innovation.'
        ),
        array(
            'name' => 'Prof. Dr. Nikolaus Lang',
            'title' => 'Mobilität & Innovation',
            'company' => 'Boston Consulting Group',
            'position' => 'Founder & Director of BCG\'s Center for Mobility Innovation; Global Leader of the BCG Henderson Institute',
            'expertise' => 'Mobilität & Innovation',
            'role' => 'member',
            'email' => 'lang.nikolaus@bcg.com',
            'linkedin' => 'https://www.linkedin.com/in/nikolaus-lang/',
            'bio' => 'Prof. Dr. Nikolaus Lang ist Founder & Director of BCG\'s Center for Mobility Innovation und Global Leader des BCG Henderson Institute.'
        ),
        array(
            'name' => 'Laura Meyer',
            'title' => 'Tourismus & Digitalisierung',
            'company' => 'Hotelplan Gruppe',
            'position' => 'CEO Hotelplan Gruppe',
            'expertise' => 'Tourismus & Digitalisierung',
            'role' => 'member',
            'email' => 'laura.meyer@hotelplan.com',
            'linkedin' => 'https://www.linkedin.com/in/laura-meyer-hotelplan/',
            'bio' => 'Laura Meyer ist CEO der Hotelplan Gruppe und eine führende Expertin für Tourismus und Digitalisierung.'
        ),
        array(
            'name' => 'Felix Neureuther',
            'title' => 'High Performance & Nachhaltigkeit',
            'company' => 'Felix Neureuther GmbH',
            'position' => 'Ex-Spitzenathlet und Unternehmer',
            'expertise' => 'High Performance & Nachhaltigkeit',
            'role' => 'member',
            'email' => 'felix@neureuther.com',
            'linkedin' => 'https://www.linkedin.com/in/felix-neureuther/',
            'bio' => 'Felix Neureuther ist ehemaliger Spitzenathlet im alpinen Skisport und heute erfolgreicher Unternehmer mit Fokus auf Nachhaltigkeit.'
        ),
        array(
            'name' => 'Dr. Philipp Rösler',
            'title' => 'Globalisierung & Politik',
            'company' => 'Consessor AG',
            'position' => 'CEO der Consessor AG, Vize-Kanzler a.D. der Bundesrepublik Deutschland',
            'expertise' => 'Globalisierung & Politik',
            'role' => 'member',
            'email' => 'philipp.roesler@consessor.com',
            'linkedin' => 'https://www.linkedin.com/in/philipp-roesler/',
            'bio' => 'Dr. Philipp Rösler ist CEO der Consessor AG und ehemaliger Vize-Kanzler der Bundesrepublik Deutschland.'
        ),
        
        // Page 7 - Bottom Row
        array(
            'name' => 'Helmut Ruhl',
            'title' => 'Finanzen & Handel',
            'company' => 'AMAG Group',
            'position' => 'CEO der AMAG Group',
            'expertise' => 'Finanzen & Handel',
            'role' => 'member',
            'email' => 'helmut.ruhl@amag.ch',
            'linkedin' => 'https://www.linkedin.com/in/helmut-ruhl/',
            'bio' => 'Helmut Ruhl ist CEO der AMAG Group, einem der führenden Automobil- und Mobilitätsdienstleister in der Schweiz.'
        ),
        array(
            'name' => 'Susann Schramm',
            'title' => 'Hotellerie & Marketing',
            'company' => 'Motel One',
            'position' => 'CMO Motel One',
            'expertise' => 'Hotellerie & Marketing',
            'role' => 'member',
            'email' => 'susann.schramm@motel-one.com',
            'linkedin' => 'https://www.linkedin.com/in/susann-schramm/',
            'bio' => 'Susann Schramm ist CMO von Motel One und eine führende Expertin für Hotellerie und Marketing.'
        ),
        array(
            'name' => 'Jürgen Stackmann',
            'title' => 'Automobil & Vertrieb',
            'company' => 'Automobil-Experte',
            'position' => 'Automobil-Experte & Ex-Vorstandsvorsitzender SEAT & Ex-Markenvorstand Volkswagen',
            'expertise' => 'Automobil & Vertrieb',
            'role' => 'member',
            'email' => 'juergen.stackmann@automotive-expert.com',
            'linkedin' => 'https://www.linkedin.com/in/juergen-stackmann/',
            'bio' => 'Jürgen Stackmann ist Automobil-Experte und ehemaliger Vorstandsvorsitzender von SEAT sowie Ex-Markenvorstand bei Volkswagen.'
        ),
        array(
            'name' => 'Dr. Sabine Stock',
            'title' => 'Bahn & Technologie',
            'company' => 'ÖBB-Personenverkehr AG',
            'position' => 'Vorständin der ÖBB-Personenverkehr AG',
            'expertise' => 'Bahn & Technologie',
            'role' => 'member',
            'email' => 'sabine.stock@oebb.at',
            'linkedin' => 'https://www.linkedin.com/in/sabine-stock/',
            'bio' => 'Dr. Sabine Stock ist Vorständin der ÖBB-Personenverkehr AG und eine führende Expertin für Bahnverkehr und Technologie.'
        ),
        array(
            'name' => 'Eberhard Weiblen',
            'title' => 'Mobilität & Innovation',
            'company' => 'Porsche Consulting',
            'position' => 'Chairman of the Executive Board of Porsche Consulting',
            'expertise' => 'Mobilität & Innovation',
            'role' => 'member',
            'email' => 'eberhard.weiblen@porsche-consulting.com',
            'linkedin' => 'https://www.linkedin.com/in/eberhard-weiblen/',
            'bio' => 'Eberhard Weiblen ist Chairman of the Executive Board of Porsche Consulting und ein renommierter Experte für Mobilität und Innovation.'
        )
    );
}

/**
 * Main import function
 */
function import_mobility_trailblazers_jury() {
    $jury_members = get_jury_members_data();
    $imported_count = 0;
    $errors = array();
    
    echo "Starting Mobility Trailblazers Jury Import...\n";
    echo "Total jury members to import: " . count($jury_members) . "\n\n";
    
    foreach ($jury_members as $index => $member) {
        try {
            echo "Importing jury member " . ($index + 1) . ": " . $member['name'] . "\n";
            
            // Check if jury member already exists
            $existing_post = get_posts(array(
                'post_type' => 'mt_jury_member',
                'meta_query' => array(
                    array(
                        'key' => '_mt_jury_email',
                        'value' => $member['email']
                    )
                ),
                'posts_per_page' => 1
            ));
            
            if (!empty($existing_post)) {
                echo "  → Jury member already exists (ID: " . $existing_post[0]->ID . "), updating...\n";
                $post_id = $existing_post[0]->ID;
                
                // Update existing post
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_title' => $member['name'],
                    'post_content' => $member['bio'],
                    'post_excerpt' => substr($member['bio'], 0, 150) . '...',
                    'post_status' => 'publish'
                ));
            } else {
                // Create new jury member post
                $post_id = wp_insert_post(array(
                    'post_type' => 'mt_jury_member',
                    'post_title' => $member['name'],
                    'post_content' => $member['bio'],
                    'post_excerpt' => substr($member['bio'], 0, 150) . '...',
                    'post_status' => 'publish',
                    'post_author' => 1
                ));
                
                if (is_wp_error($post_id)) {
                    throw new Exception('Failed to create post: ' . $post_id->get_error_message());
                }
                
                echo "  → Created jury member post (ID: $post_id)\n";
            }
            
            // Update all meta fields
            update_post_meta($post_id, '_mt_jury_company', $member['company']);
            update_post_meta($post_id, '_mt_jury_position', $member['position']);
            update_post_meta($post_id, '_mt_jury_expertise', $member['expertise']);
            update_post_meta($post_id, '_mt_jury_bio', $member['bio']);
            update_post_meta($post_id, '_mt_jury_email', $member['email']);
            update_post_meta($post_id, '_mt_jury_linkedin', $member['linkedin']);
            
            // Set role-specific flags
            if ($member['role'] === 'president') {
                update_post_meta($post_id, '_mt_jury_is_president', 1);
                delete_post_meta($post_id, '_mt_jury_is_vice_president');
                echo "  → Set as President\n";
            } elseif ($member['role'] === 'vice_president') {
                update_post_meta($post_id, '_mt_jury_is_vice_president', 1);
                delete_post_meta($post_id, '_mt_jury_is_president');
                echo "  → Set as Vice President\n";
            } else {
                delete_post_meta($post_id, '_mt_jury_is_president');
                delete_post_meta($post_id, '_mt_jury_is_vice_president');
                echo "  → Set as Member\n";
            }
            
            // Create or update WordPress user account
            $user_login = sanitize_user(strtolower(str_replace(' ', '.', $member['name'])));
            $user_login = str_replace(array('prof.', 'dr.', 'em.', 'h.c.'), '', $user_login);
            $user_login = preg_replace('/[^a-z0-9.]/', '', $user_login);
            
            $existing_user = get_user_by('email', $member['email']);
            
            if ($existing_user) {
                echo "  → User account already exists (ID: " . $existing_user->ID . ")\n";
                $user_id = $existing_user->ID;
                
                // Update user meta
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => $member['name'],
                    'first_name' => explode(' ', $member['name'])[0],
                    'last_name' => implode(' ', array_slice(explode(' ', $member['name']), 1))
                ));
            } else {
                // Create new user
                $user_id = wp_create_user($user_login, wp_generate_password(12), $member['email']);
                
                if (is_wp_error($user_id)) {
                    throw new Exception('Failed to create user: ' . $user_id->get_error_message());
                }
                
                echo "  → Created user account (ID: $user_id, Login: $user_login)\n";
                
                // Update user details
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => $member['name'],
                    'first_name' => explode(' ', $member['name'])[0],
                    'last_name' => implode(' ', array_slice(explode(' ', $member['name']), 1)),
                    'role' => 'mt_jury_member'
                ));
                
                // Send welcome email (optional)
                $reset_link = wp_lostpassword_url();
                $subject = 'Welcome to Mobility Trailblazers Jury Platform';
                $message = "Dear " . $member['name'] . ",\n\n";
                $message .= "Welcome to the Mobility Trailblazers jury platform!\n\n";
                $message .= "Your account details:\n";
                $message .= "Username: " . $user_login . "\n";
                $message .= "Email: " . $member['email'] . "\n\n";
                $message .= "Please set your password using this link: " . $reset_link . "\n\n";
                $message .= "Best regards,\nMobility Trailblazers Team";
                
                wp_mail($member['email'], $subject, $message);
                echo "  → Welcome email sent\n";
            }
            
            // Link user to jury post
            update_post_meta($post_id, '_mt_jury_user_id', $user_id);
            update_user_meta($user_id, '_mt_jury_post_id', $post_id);
            
            // Create initial assignments for the jury member
            if ($table_exists) {
                $candidates = get_posts(array(
                    'post_type' => 'mt_candidate',
                    'posts_per_page' => 10, // Assign 10 candidates per jury member
                    'orderby' => 'rand'
                ));
                
                if (!empty($candidates)) {
                    foreach ($candidates as $candidate) {
                        $result = $wpdb->insert(
                            $wpdb->prefix . 'mt_assignments',
                            array(
                                'candidate_id' => $candidate->ID,
                                'jury_member_id' => $user_id,
                                'status' => 'pending'
                            ),
                            array('%d', '%d', '%s')
                        );
                        
                        if ($result === false) {
                            echo "  → Warning: Failed to create assignment for candidate {$candidate->ID}\n";
                        }
                    }
                    echo "  → Created " . count($candidates) . " initial assignments\n";
                } else {
                    echo "  → No candidates available for assignment\n";
                }
            } else {
                echo "  → Skipping assignment creation (table not available)\n";
            }
            
            $imported_count++;
            echo "  ✓ Import completed successfully\n\n";
            
        } catch (Exception $e) {
            $error_msg = "Error importing " . $member['name'] . ": " . $e->getMessage();
            $errors[] = $error_msg;
            echo "  ✗ " . $error_msg . "\n\n";
        }
    }
    
    // Import summary
    echo "=== IMPORT SUMMARY ===\n";
    echo "Total jury members processed: " . count($jury_members) . "\n";
    echo "Successfully imported: $imported_count\n";
    echo "Errors: " . count($errors) . "\n";
    
    if (!empty($errors)) {
        echo "\nErrors encountered:\n";
        foreach ($errors as $error) {
            echo "- $error\n";
        }
    }
    
    echo "\n=== JURY ROLES SUMMARY ===\n";
    echo "President: Prof. Dr. Andreas Herrmann\n";
    echo "Vice President: Prof. em. Dr. Dr. h.c. Torsten Tomczak\n";
    echo "Members: " . ($imported_count - 2) . "\n";
    
    echo "\n=== VERIFICATION ===\n";
    $jury_count_obj = wp_count_posts('mt_jury_member');
    $jury_count = isset($jury_count_obj->publish) ? $jury_count_obj->publish : 0;
    echo "Total jury posts in database: $jury_count\n";
    
    // Verify president and vice president
    $presidents = get_posts(array(
        'post_type' => 'mt_jury_member',
        'meta_query' => array(
            array(
                'key' => '_mt_jury_is_president',
                'value' => '1'
            )
        )
    ));
    
    $vice_presidents = get_posts(array(
        'post_type' => 'mt_jury_member',
        'meta_query' => array(
            array(
                'key' => '_mt_jury_is_vice_president',
                'value' => '1'
            )
        )
    ));
    
    echo "Presidents found: " . count($presidents) . "\n";
    echo "Vice Presidents found: " . count($vice_presidents) . "\n";
    
    if (count($presidents) === 1) {
        echo "  ✓ President: " . $presidents[0]->post_title . "\n";
    }
    
    if (count($vice_presidents) === 1) {
        echo "  ✓ Vice President: " . $vice_presidents[0]->post_title . "\n";
    }
    
    echo "\nJury import completed!\n";
}

// Run the import
if (!function_exists('wp_create_user')) {
    die("WordPress user functions not available. Please run this script through WP-CLI.\n");
}

import_mobility_trailblazers_jury();

?>