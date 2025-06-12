<?php
/**
 * Jury Members Data Import Script
 * Based on PDF documentation pages 6-7
 * 
 * Run this script via WP-CLI or WordPress admin after plugin activation:
 * docker-compose exec wordpress wp eval-file jury-import.php
 */

// Ensure we're in WordPress environment
if (!defined('ABSPATH')) {
    // If running via WP-CLI
    require_once('./wp-config.php');
}

class JuryMemberImporter {
    
    private $jury_members_data = [];
    
    public function __construct() {
        $this->prepare_jury_data();
    }
    
    /**
     * Jury members data from PDF documentation pages 6-7
     */
    private function prepare_jury_data() {
        $this->jury_members_data = [
            // President
            [
                'name' => 'Prof. Dr. Andreas Herrmann',
                'company' => 'Universität St. Gallen',
                'position' => 'Gründer und Direktor des Instituts für Mobilität an der Universität St. Gallen',
                'expertise' => 'Mobility Innovation & Leadership',
                'email' => 'andreas.herrmann@unisg.ch',
                'linkedin' => 'https://www.linkedin.com/in/andreas-herrmann-unisg/',
                'bio' => 'Prof. Dr. Andreas Herrmann ist Gründer und Direktor des Instituts für Mobilität an der Universität St. Gallen. Er ist ein führender Experte für Mobilitätsinnovation und Transformation im DACH-Raum.',
                'is_president' => true,
                'is_vice_president' => false,
                'sort_order' => 1
            ],
            
            // Vice President
            [
                'name' => 'Prof. em. Dr. Dr. h.c. Torsten Tomczak',
                'company' => 'Universität St. Gallen',
                'position' => 'Gründer des Instituts für Mobilität an der Universität St. Gallen',
                'expertise' => 'Marketing & Strategic Management',
                'email' => 'torsten.tomczak@unisg.ch',
                'linkedin' => 'https://www.linkedin.com/in/torsten-tomczak/',
                'bio' => 'Prof. em. Dr. Dr. h.c. Torsten Tomczak ist Gründer des Instituts für Mobilität an der Universität St. Gallen und ein renommierter Experte für strategisches Marketing und Management.',
                'is_president' => false,
                'is_vice_president' => true,
                'sort_order' => 2
            ],
            
            // Schirmherr
            [
                'name' => 'Winfried Hermann',
                'company' => 'Land Baden-Württemberg',
                'position' => 'Verkehrsminister Baden-Württemberg',
                'expertise' => 'Transport Policy & Public Administration',
                'email' => 'minister@vm.bwl.de',
                'linkedin' => '',
                'bio' => 'Winfried Hermann ist Verkehrsminister von Baden-Württemberg und Schirmherr der Mobility Trailblazers Initiative.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 3,
                'special_role' => 'Schirmherr'
            ],
            
            // Jury Members Row 1
            [
                'name' => 'Prof. Dr. Oliver Gassmann',
                'company' => 'Universität St. Gallen',
                'position' => 'Direktor des Instituts für Technologiemanagement an der Universität St. Gallen',
                'expertise' => 'Innovation & Geschäftsmodelle',
                'email' => 'oliver.gassmann@unisg.ch',
                'linkedin' => 'https://www.linkedin.com/in/oliver-gassmann/',
                'bio' => 'Prof. Dr. Oliver Gassmann ist Direktor des Instituts für Technologiemanagement an der Universität St. Gallen und ein führender Experte für Innovation und Geschäftsmodelle.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 4
            ],
            
            [
                'name' => 'Peter Grünenfelder',
                'company' => 'Auto-Schweiz',
                'position' => 'Präsident Auto-Schweiz',
                'expertise' => 'Automobil',
                'email' => 'peter.gruenenfelder@auto.ch',
                'linkedin' => '',
                'bio' => 'Peter Grünenfelder ist Präsident von Auto-Schweiz, dem Verband der Schweizer Automobilimporteure.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 5
            ],
            
            [
                'name' => 'Dr. Kjell Gruner',
                'company' => 'Volkswagen Group of America',
                'position' => 'CEO & President Volkswagen Group of America',
                'expertise' => 'Automobil & USA',
                'email' => 'kjell.gruner@vw.com',
                'linkedin' => 'https://www.linkedin.com/in/kjell-gruner/',
                'bio' => 'Dr. Kjell Gruner ist CEO & President der Volkswagen Group of America und bringt umfassende Erfahrung aus der Automobilindustrie mit.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 6
            ],
            
            [
                'name' => 'Prof. Dr. Zheng Han',
                'company' => 'Tongji University, Shanghai',
                'position' => 'Professor für Innovation & Unternehmertum, Tongji University, Shanghai',
                'expertise' => 'Mobilität & China',
                'email' => 'zheng.han@tongji.edu.cn',
                'linkedin' => '',
                'bio' => 'Prof. Dr. Zheng Han ist Professor für Innovation & Unternehmertum an der Tongji University in Shanghai und Experte für Mobilität in China.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 7
            ],
            
            [
                'name' => 'Prof. Dr. Wolfgang Jenewein',
                'company' => 'Jenewein AG',
                'position' => 'Geschäftsführender Inhaber der Jenewein AG',
                'expertise' => 'Transformation & Leadership',
                'email' => 'wolfgang.jenewein@jenewein.com',
                'linkedin' => 'https://www.linkedin.com/in/wolfgang-jenewein/',
                'bio' => 'Prof. Dr. Wolfgang Jenewein ist Geschäftsführender Inhaber der Jenewein AG und ein renommierter Experte für Transformation und Leadership.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 8
            ],
            
            // Second Row of Jury Members
            [
                'name' => 'Dr. Astrid Fontaine',
                'company' => 'Schaeffler Gruppe',
                'position' => 'Vorständin Schaeffler Gruppe',
                'expertise' => 'HR & Nachhaltigkeit',
                'email' => 'astrid.fontaine@schaeffler.com',
                'linkedin' => 'https://www.linkedin.com/in/astrid-fontaine/',
                'bio' => 'Dr. Astrid Fontaine ist Vorständin der Schaeffler Gruppe und verantwortet die Bereiche HR und Nachhaltigkeit.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 9
            ],
            
            [
                'name' => 'Katja Busch',
                'company' => 'DHL Group',
                'position' => 'Chief Commercial Officer und Head Customer Solutions & Innovation DHL Group',
                'expertise' => 'Customer Solutions & Innovation',
                'email' => 'katja.busch@dhl.com',
                'linkedin' => 'https://www.linkedin.com/in/katja-busch/',
                'bio' => 'Katja Busch ist Chief Commercial Officer und Head Customer Solutions & Innovation bei der DHL Group.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 10
            ],
            
            // Additional Jury Members from Page 7
            [
                'name' => 'Johann Jungwirth',
                'company' => 'Mobileye',
                'position' => 'Executive Vice President Mobileye',
                'expertise' => 'Technologie & Innovation',
                'email' => 'johann.jungwirth@mobileye.com',
                'linkedin' => 'https://www.linkedin.com/in/johann-jungwirth/',
                'bio' => 'Johann Jungwirth ist Executive Vice President bei Mobileye und ein führender Experte für Automotive-Technologie und Innovation.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 11
            ],
            
            [
                'name' => 'Prof. Dr. Nikolaus Lang',
                'company' => 'BCG',
                'position' => 'Founder & Director of BCG\'s Center for Mobility Innovation; Global Leader of the BCG Henderson Institute',
                'expertise' => 'Mobilität & Innovation',
                'email' => 'lang.nikolaus@bcg.com',
                'linkedin' => 'https://www.linkedin.com/in/nikolaus-lang/',
                'bio' => 'Prof. Dr. Nikolaus Lang ist Founder & Director of BCG\'s Center for Mobility Innovation und Global Leader des BCG Henderson Institute.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 12
            ],
            
            [
                'name' => 'Laura Meyer',
                'company' => 'Hotelplan Gruppe',
                'position' => 'CEO Hotelplan Gruppe',
                'expertise' => 'Tourismus & Digitalisierung',
                'email' => 'laura.meyer@hotelplan.com',
                'linkedin' => 'https://www.linkedin.com/in/laura-meyer-hotelplan/',
                'bio' => 'Laura Meyer ist CEO der Hotelplan Gruppe und eine Expertin für Tourismus und Digitalisierung.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 13
            ],
            
            [
                'name' => 'Felix Neureuther',
                'company' => 'Felix Neureuther GmbH',
                'position' => 'Ex-Spitzenathlet und Unternehmer',
                'expertise' => 'High Performance & Nachhaltigkeit',
                'email' => 'felix@neureuther.com',
                'linkedin' => 'https://www.linkedin.com/in/felix-neureuther/',
                'bio' => 'Felix Neureuther ist ehemaliger Spitzenathlet und erfolgreicher Unternehmer mit Fokus auf High Performance und Nachhaltigkeit.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 14
            ],
            
            [
                'name' => 'Dr. Philipp Rösler',
                'company' => 'Consessor AG',
                'position' => 'CEO der Consessor AG, Vize-Kanzler a.D. der Bundesrepublik Deutschland',
                'expertise' => 'Globalisierung & Politik',
                'email' => 'philipp.roesler@consessor.com',
                'linkedin' => 'https://www.linkedin.com/in/philipp-roesler/',
                'bio' => 'Dr. Philipp Rösler ist CEO der Consessor AG und ehemaliger Vize-Kanzler der Bundesrepublik Deutschland.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 15
            ],
            
            // Bottom Row
            [
                'name' => 'Helmut Ruhl',
                'company' => 'AMAG Group',
                'position' => 'CEO der AMAG Group',
                'expertise' => 'Finanzen & Handel',
                'email' => 'helmut.ruhl@amag.ch',
                'linkedin' => 'https://www.linkedin.com/in/helmut-ruhl/',
                'bio' => 'Helmut Ruhl ist CEO der AMAG Group und ein erfahrener Experte in den Bereichen Finanzen und Handel.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 16
            ],
            
            [
                'name' => 'Susann Schramm',
                'company' => 'Motel One',
                'position' => 'CMO Motel One',
                'expertise' => 'Hotellerie & Marketing',
                'email' => 'susann.schramm@motel-one.com',
                'linkedin' => 'https://www.linkedin.com/in/susann-schramm/',
                'bio' => 'Susann Schramm ist CMO von Motel One und eine Expertin für Hotellerie und Marketing.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 17
            ],
            
            [
                'name' => 'Jürgen Stackmann',
                'company' => 'Automotive Industry',
                'position' => 'Automobil-Experte & Ex-Vorstandsvorsitzender SEAT & Ex-Markenvorstand Volkswagen',
                'expertise' => 'Automobil & Vertrieb',
                'email' => 'juergen.stackmann@automotive.com',
                'linkedin' => 'https://www.linkedin.com/in/juergen-stackmann/',
                'bio' => 'Jürgen Stackmann ist ein renommierter Automobil-Experte und ehemaliger Vorstandsvorsitzender von SEAT sowie ehemaliger Markenvorstand bei Volkswagen.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 18
            ],
            
            [
                'name' => 'Dr. Sabine Stock',
                'company' => 'ÖBB-Personenverkehr AG',
                'position' => 'Vorständin der ÖBB-Personenverkehr AG',
                'expertise' => 'Bahn & Technologie',
                'email' => 'sabine.stock@oebb.at',
                'linkedin' => 'https://www.linkedin.com/in/sabine-stock/',
                'bio' => 'Dr. Sabine Stock ist Vorständin der ÖBB-Personenverkehr AG und eine Expertin für Bahn und Technologie.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 19
            ],
            
            [
                'name' => 'Eberhard Weiblen',
                'company' => 'Porsche Consulting',
                'position' => 'Chairman of the Executive Board of Porsche Consulting',
                'expertise' => 'Mobilität & Innovation',
                'email' => 'eberhard.weiblen@porsche-consulting.com',
                'linkedin' => 'https://www.linkedin.com/in/eberhard-weiblen/',
                'bio' => 'Eberhard Weiblen ist Chairman of the Executive Board von Porsche Consulting und ein führender Experte für Mobilität und Innovation.',
                'is_president' => false,
                'is_vice_president' => false,
                'sort_order' => 20
            ]
        ];
    }
    
    /**
     * Import all jury members
     */
    public function import_jury_members() {
        echo "Starting jury members import...\n";
        
        $imported = 0;
        $errors = 0;
        
        foreach ($this->jury_members_data as $jury_data) {
            try {
                $result = $this->create_jury_member($jury_data);
                if ($result) {
                    $imported++;
                    echo "✓ Imported: {$jury_data['name']}\n";
                } else {
                    $errors++;
                    echo "✗ Failed to import: {$jury_data['name']}\n";
                }
            } catch (Exception $e) {
                $errors++;
                echo "✗ Error importing {$jury_data['name']}: {$e->getMessage()}\n";
            }
        }
        
        echo "\nImport completed!\n";
        echo "Imported: $imported\n";
        echo "Errors: $errors\n";
        
        return ['imported' => $imported, 'errors' => $errors];
    }
    
    /**
     * Create a single jury member
     */
    private function create_jury_member($data) {
        // Check if jury member already exists
        $existing = get_posts([
            'post_type' => 'mt_jury',
            'title' => $data['name'],
            'post_status' => 'any',
            'numberposts' => 1
        ]);
        
        if (!empty($existing)) {
            echo "  → Already exists, updating...\n";
            $post_id = $existing[0]->ID;
        } else {
            // Create new jury member post
            $post_data = [
                'post_title' => $data['name'],
                'post_content' => $data['bio'],
                'post_type' => 'mt_jury',
                'post_status' => 'publish',
                'menu_order' => $data['sort_order']
            ];
            
            $post_id = wp_insert_post($post_data);
            
            if (is_wp_error($post_id)) {
                throw new Exception("Failed to create post: " . $post_id->get_error_message());
            }
        }
        
        // Add meta fields
        $meta_fields = [
            '_mt_jury_company' => $data['company'],
            '_mt_jury_position' => $data['position'],
            '_mt_jury_expertise' => $data['expertise'],
            '_mt_jury_email' => $data['email'],
            '_mt_jury_linkedin' => $data['linkedin'],
            '_mt_jury_bio' => $data['bio']
        ];
        
        // Add president/vice president flags
        if ($data['is_president']) {
            $meta_fields['_mt_jury_is_president'] = '1';
        }
        
        if ($data['is_vice_president']) {
            $meta_fields['_mt_jury_is_vice_president'] = '1';
        }
        
        // Add special role if exists
        if (isset($data['special_role'])) {
            $meta_fields['_mt_jury_special_role'] = $data['special_role'];
        }
        
        // Update all meta fields
        foreach ($meta_fields as $key => $value) {
            update_post_meta($post_id, $key, $value);
        }
        
        // Set jury member taxonomy if it exists
        $jury_terms = get_terms([
            'taxonomy' => 'mt_jury_role',
            'hide_empty' => false,
        ]);
        
        if (!is_wp_error($jury_terms) && !empty($jury_terms)) {
            if ($data['is_president']) {
                wp_set_post_terms($post_id, ['president'], 'mt_jury_role');
            } elseif ($data['is_vice_president']) {
                wp_set_post_terms($post_id, ['vice-president'], 'mt_jury_role');
            } else {
                wp_set_post_terms($post_id, ['member'], 'mt_jury_role');
            }
        }
        
        return $post_id;
    }
    
    /**
     * Create WordPress user accounts for jury members with email access
     */
    public function create_jury_user_accounts() {
        echo "Creating WordPress user accounts for jury members...\n";
        
        $created = 0;
        $errors = 0;
        
        foreach ($this->jury_members_data as $jury_data) {
            if (empty($jury_data['email'])) {
                continue;
            }
            
            try {
                // Check if user already exists
                $existing_user = get_user_by('email', $jury_data['email']);
                
                if (!$existing_user) {
                    // Create username from name
                    $username = $this->generate_username($jury_data['name']);
                    
                    // Generate random password
                    $password = wp_generate_password(12, false);
                    
                    // Create user
                    $user_id = wp_create_user($username, $password, $jury_data['email']);
                    
                    if (is_wp_error($user_id)) {
                        throw new Exception("Failed to create user: " . $user_id->get_error_message());
                    }
                    
                    // Set user role
                    $user = new WP_User($user_id);
                    $user->set_role('author'); // Authors can edit posts
                    
                    // Add jury-specific capabilities
                    $user->add_cap('mt_evaluate_candidates');
                    $user->add_cap('mt_view_jury_dashboard');
                    
                    // Update user meta
                    update_user_meta($user_id, 'first_name', explode(' ', $jury_data['name'])[0]);
                    update_user_meta($user_id, 'last_name', substr($jury_data['name'], strpos($jury_data['name'], ' ') + 1));
                    update_user_meta($user_id, 'description', $jury_data['bio']);
                    update_user_meta($user_id, 'mt_jury_member', true);
                    update_user_meta($user_id, 'mt_jury_company', $jury_data['company']);
                    update_user_meta($user_id, 'mt_jury_expertise', $jury_data['expertise']);
                    
                    $created++;
                    echo "✓ Created user account for: {$jury_data['name']} (Username: $username)\n";
                    
                    // Store credentials for later use
                    update_user_meta($user_id, 'mt_initial_password', $password);
                    
                } else {
                    echo "  → User account already exists for: {$jury_data['name']}\n";
                    
                    // Update existing user meta
                    $user_id = $existing_user->ID;
                    update_user_meta($user_id, 'mt_jury_member', true);
                    update_user_meta($user_id, 'mt_jury_company', $jury_data['company']);
                    update_user_meta($user_id, 'mt_jury_expertise', $jury_data['expertise']);
                    
                    // Add capabilities if missing
                    $existing_user->add_cap('mt_evaluate_candidates');
                    $existing_user->add_cap('mt_view_jury_dashboard');
                }
                
            } catch (Exception $e) {
                $errors++;
                echo "✗ Error creating user for {$jury_data['name']}: {$e->getMessage()}\n";
            }
        }
        
        echo "\nUser account creation completed!\n";
        echo "Created: $created\n";
        echo "Errors: $errors\n";
        
        return ['created' => $created, 'errors' => $errors];
    }
    
    /**
     * Generate username from full name
     */
    private function generate_username($name) {
        // Remove titles and clean name
        $clean_name = preg_replace('/^(Prof\.|Dr\.|Prof\. Dr\.|Prof\. em\.|Prof\. em\. Dr\.|Prof\. em\. Dr\. Dr\. h\.c\.|Dr\. Dr\. h\.c\.)\s+/', '', $name);
        
        // Split into parts
        $parts = explode(' ', $clean_name);
        
        // Create username: first name + last name (lowercase, no special chars)
        $first = strtolower($parts[0]);
        $last = strtolower(end($parts));
        
        // Remove special characters
        $first = preg_replace('/[^a-z0-9]/', '', $first);
        $last = preg_replace('/[^a-z0-9]/', '', $last);
        
        $username = $first . '.' . $last;
        
        // Make sure username is unique
        $counter = 1;
        $original_username = $username;
        
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Send welcome emails to jury members
     */
    public function send_welcome_emails() {
        echo "Sending welcome emails to jury members...\n";
        
        $sent = 0;
        $errors = 0;
        
        // Get all users with jury member capability
        $jury_users = get_users([
            'meta_key' => 'mt_jury_member',
            'meta_value' => true
        ]);
        
        foreach ($jury_users as $user) {
            try {
                $initial_password = get_user_meta($user->ID, 'mt_initial_password', true);
                
                if ($initial_password) {
                    $subject = 'Welcome to Mobility Trailblazers Jury Portal';
                    $message = $this->get_welcome_email_template($user, $initial_password);
                    
                    $headers = [
                        'Content-Type: text/html; charset=UTF-8',
                        'From: Mobility Trailblazers <noreply@mobility-trailblazers.org>'
                    ];
                    
                    if (wp_mail($user->user_email, $subject, $message, $headers)) {
                        $sent++;
                        echo "✓ Welcome email sent to: {$user->display_name}\n";
                        
                        // Remove the initial password from meta
                        delete_user_meta($user->ID, 'mt_initial_password');
                    } else {
                        throw new Exception("Failed to send email");
                    }
                }
            } catch (Exception $e) {
                $errors++;
                echo "✗ Error sending email to {$user->display_name}: {$e->getMessage()}\n";
            }
        }
        
        echo "\nWelcome email sending completed!\n";
        echo "Sent: $sent\n";
        echo "Errors: $errors\n";
        
        return ['sent' => $sent, 'errors' => $errors];
    }
    
    /**
     * Get welcome email template
     */
    private function get_welcome_email_template($user, $password) {
        $site_url = home_url();
        $admin_url = admin_url();
        
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #2c5282; color: white; padding: 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .credentials { background-color: #f7fafc; padding: 20px; border-radius: 8px; margin: 20px 0; }
                .button { background-color: #2c5282; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
                .footer { background-color: #f7fafc; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>25 Mobility Trailblazers in 25</h1>
                <p>Welcome to the Jury Portal</p>
            </div>
            
            <div class='content'>
                <h2>Dear {$user->display_name},</h2>
                
                <p>Welcome to the Mobility Trailblazers initiative! We are honored to have you as a jury member for the \"25 Mobility Trailblazers in 25\" award.</p>
                
                <p>Your expertise in the mobility sector will be invaluable in identifying and recognizing the most innovative and courageous trailblazers in the DACH region.</p>
                
                <div class='credentials'>
                    <h3>Your Login Credentials:</h3>
                    <p><strong>Website:</strong> <a href='{$site_url}'>{$site_url}</a></p>
                    <p><strong>Username:</strong> {$user->user_login}</p>
                    <p><strong>Password:</strong> {$password}</p>
                    <p><strong>Admin Access:</strong> <a href='{$admin_url}'>{$admin_url}</a></p>
                </div>
                
                <p><strong>Important:</strong> Please change your password after your first login for security reasons.</p>
                
                <h3>Your Role as Jury Member:</h3>
                <ul>
                    <li>Evaluate candidates based on our 5 criteria framework</li>
                    <li>Provide scores and feedback for candidate assessments</li>
                    <li>Participate in the final selection process</li>
                    <li>Attend the award ceremony on October 30, 2025 in Berlin</li>
                </ul>
                
                <h3>Evaluation Criteria:</h3>
                <ol>
                    <li><strong>Mut & Pioniergeist</strong> - Courage & Pioneer Spirit</li>
                    <li><strong>Innovationsgrad</strong> - Innovation Degree</li>
                    <li><strong>Umsetzungskraft & Wirkung</strong> - Implementation & Impact</li>
                    <li><strong>Relevanz für Mobilitätswende</strong> - Mobility Transformation Relevance</li>
                    <li><strong>Vorbildfunktion & Sichtbarkeit</strong> - Role Model & Visibility</li>
                </ol>
                
                <a href='{$admin_url}admin.php?page=mt-jury-evaluation' class='button'>Access Jury Portal</a>
                
                <p>If you have any questions or technical issues, please don't hesitate to contact us.</p>
                
                <p>Thank you for your commitment to advancing mobility innovation in the DACH region!</p>
                
                <p>Best regards,<br>
                The Mobility Trailblazers Team<br>
                Institut für Mobilität, Universität St. Gallen</p>
            </div>
            
            <div class='footer'>
                <p>© 2025 Mobility Trailblazers | Institut für Mobilität, Universität St. Gallen</p>
                <p>This email was sent to {$user->user_email}</p>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Create jury role taxonomy terms
     */
    public function create_jury_taxonomy_terms() {
        echo "Creating jury taxonomy terms...\n";
        
        // Check if taxonomy exists
        if (!taxonomy_exists('mt_jury_role')) {
            echo "Jury role taxonomy doesn't exist yet. Will be created by plugin activation.\n";
            return;
        }
        
        $terms = [
            [
                'name' => 'President',
                'slug' => 'president',
                'description' => 'Jury President'
            ],
            [
                'name' => 'Vice President', 
                'slug' => 'vice-president',
                'description' => 'Jury Vice President'
            ],
            [
                'name' => 'Member',
                'slug' => 'member', 
                'description' => 'Jury Member'
            ],
            [
                'name' => 'Schirmherr',
                'slug' => 'schirmherr',
                'description' => 'Patron/Sponsor'
            ]
        ];
        
        foreach ($terms as $term_data) {
            $result = wp_insert_term(
                $term_data['name'],
                'mt_jury_role',
                [
                    'slug' => $term_data['slug'],
                    'description' => $term_data['description']
                ]
            );
            
            if (is_wp_error($result)) {
                if ($result->get_error_code() !== 'term_exists') {
                    echo "✗ Error creating term {$term_data['name']}: " . $result->get_error_message() . "\n";
                } else {
                    echo "  → Term already exists: {$term_data['name']}\n";
                }
            } else {
                echo "✓ Created term: {$term_data['name']}\n";
            }
        }
    }
    
    /**
     * Generate jury credentials report
     */
    public function generate_credentials_report() {
        echo "Generating jury credentials report...\n";
        
        $jury_users = get_users([
            'meta_key' => 'mt_jury_member',
            'meta_value' => true
        ]);
        
        $report = "MOBILITY TRAILBLAZERS JURY CREDENTIALS REPORT\n";
        $report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
        $report .= str_repeat("=", 60) . "\n\n";
        
        foreach ($jury_users as $user) {
            $company = get_user_meta($user->ID, 'mt_jury_company', true);
            $expertise = get_user_meta($user->ID, 'mt_jury_expertise', true);
            
            $report .= "Name: {$user->display_name}\n";
            $report .= "Email: {$user->user_email}\n";
            $report .= "Username: {$user->user_login}\n";
            $report .= "Company: {$company}\n";
            $report .= "Expertise: {$expertise}\n";
            $report .= "Registration Date: {$user->user_registered}\n";
            $report .= str_repeat("-", 40) . "\n\n";
        }
        
        // Save to file
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/jury-credentials-report.txt';
        
        if (file_put_contents($file_path, $report)) {
            echo "✓ Credentials report saved to: {$file_path}\n";
            echo "Total jury members: " . count($jury_users) . "\n";
        } else {
            echo "✗ Failed to save credentials report\n";
        }
        
        return $file_path;
    }
    
    /**
     * Run complete import process
     */
    public function run_complete_import() {
        echo "=== MOBILITY TRAILBLAZERS JURY IMPORT ===\n";
        echo "Starting complete jury setup process...\n\n";
        
        // Step 1: Create taxonomy terms
        $this->create_jury_taxonomy_terms();
        echo "\n";
        
        // Step 2: Import jury members
        $import_result = $this->import_jury_members();
        echo "\n";
        
        // Step 3: Create user accounts
        $user_result = $this->create_jury_user_accounts();
        echo "\n";
        
        // Step 4: Generate credentials report
        $report_path = $this->generate_credentials_report();
        echo "\n";
        
        // Summary
        echo "=== IMPORT SUMMARY ===\n";
        echo "Jury Members Imported: {$import_result['imported']}\n";
        echo "User Accounts Created: {$user_result['created']}\n";
        echo "Total Errors: " . ($import_result['errors'] + $user_result['errors']) . "\n";
        echo "Credentials Report: {$report_path}\n";
        echo "\nNext Steps:\n";
        echo "1. Review the credentials report\n";
        echo "2. Send welcome emails manually or run send_welcome_emails()\n";
        echo "3. Test jury portal access\n";
        echo "4. Configure plugin settings\n";
        
        return [
            'jury_imported' => $import_result['imported'],
            'users_created' => $user_result['created'],
            'total_errors' => $import_result['errors'] + $user_result['errors'],
            'report_path' => $report_path
        ];
    }
}

// Execute the import if running via WP-CLI or direct execution
if (defined('WP_CLI') && WP_CLI) {
    // Running via WP-CLI
    $importer = new JuryMemberImporter();
    $result = $importer->run_complete_import();
    
    WP_CLI::success("Jury import completed successfully!");
    WP_CLI::line("Imported: {$result['jury_imported']} jury members");
    WP_CLI::line("Created: {$result['users_created']} user accounts");
    
} elseif (isset($_GET['run_jury_import']) && current_user_can('manage_options')) {
    // Running via web interface (admin only)
    echo "<pre>";
    $importer = new JuryMemberImporter();
    $result = $importer->run_complete_import();
    echo "</pre>";
    
} else {
    // Just define the class for later use
    // echo "JuryMemberImporter class loaded. Use run_complete_import() to execute.\n";
}

/**
 * Helper function to run import from WordPress admin
 * Add this to functions.php or call directly
 */
function mt_run_jury_import() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized access');
    }
    
    $importer = new JuryMemberImporter();
    return $importer->run_complete_import();
}

/**
 * Add admin menu item for jury import (optional)
 */
add_action('admin_menu', function() {
    if (current_user_can('manage_options')) {
        add_submenu_page(
            'mt-award-system',
            'Import Jury Members',
            'Import Jury',
            'manage_options',
            'mt-import-jury',
            function() {
                echo '<div class="wrap">';
                echo '<h1>Import Jury Members</h1>';
                
                if (isset($_POST['run_import'])) {
                    echo '<div style="background: #f0f0f0; padding: 20px; font-family: monospace;">';
                    $importer = new JuryMemberImporter();
                    $result = $importer->run_complete_import();
                    echo '</div>';
                } else {
                    echo '<p>This will import all jury members from the documentation and create WordPress user accounts.</p>';
                    echo '<form method="post">';
                    echo '<input type="hidden" name="run_import" value="1">';
                    echo '<p><input type="submit" class="button button-primary" value="Import Jury Members"></p>';
                    echo '</form>';
                }
                
                echo '</div>';
            }
        );
    }
});

?>