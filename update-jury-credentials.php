<?php
/**
 * Update Jury Member Credentials Script
 * 
 * This script updates WordPress user accounts with new secure passwords
 * and generic email addresses for the Mobility Trailblazers jury members.
 * 
 * Usage: Run via WP-CLI or include in functions.php temporarily
 * Command: wp eval-file update-jury-credentials.php
 */

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Define jury member credentials
$jury_credentials = [
    [
        'username' => 'nicolas.estrem',
        'email' => 'jury.nicolas@mobility-trailblazers.com',
        'password' => 'Tr@ilBl@z3r#Nic89',
        'display_name' => 'Nicolas',
        'current_email' => 'nicolas.estrem@gmail.com'
    ],
    [
        'username' => 'tobias.tomczak',
        'email' => 'jury.tobias@mobility-trailblazers.com',
        'password' => 'M0b!lityT0b#2K25',
        'display_name' => 'Tobias Tomczak',
        'current_email' => 'tobias.tomczak@tomczak-gross.com'
    ],
    [
        'username' => 'andreas.herrmann',
        'email' => 'jury.andreas@mobility-trailblazers.com',
        'password' => 'H3rrm@nn&Jury#84',
        'display_name' => 'Prof. Dr. Andreas Herrmann',
        'current_email' => 'andreas.herrmann@unisg.ch'
    ],
    [
        'username' => 'torsten.tomczak',
        'email' => 'jury.torsten@mobility-trailblazers.com',
        'password' => 'T0mczak!Award#92',
        'display_name' => 'Prof. em. Dr. Dr. h.c. Torsten Tomczak',
        'current_email' => 'torsten.tomczak@unisg.ch'
    ],
    [
        'username' => 'katja.busch',
        'email' => 'jury.katja@mobility-trailblazers.com',
        'password' => 'K@tja#DHL$2025',
        'display_name' => 'Katja Busch',
        'current_email' => 'katja.busch@dhl.com'
    ],
    [
        'username' => 'astrid.fontaine',
        'email' => 'jury.astrid@mobility-trailblazers.com',
        'password' => 'F0nt@ine!Mob#73',
        'display_name' => 'Dr. Astrid Fontaine',
        'current_email' => 'astrid.fontaine@schaeffler.com'
    ],
    [
        'username' => 'winfried.hermann',
        'email' => 'jury.winfried@mobility-trailblazers.com',
        'password' => 'W!nHerm@nn#Gov45',
        'display_name' => 'Winfried Hermann',
        'current_email' => 'minister@vm.bwl.de'
    ],
    [
        'username' => 'oliver.gassmann',
        'email' => 'jury.oliver@mobility-trailblazers.com',
        'password' => 'G@ssm@nn!Tech#61',
        'display_name' => 'Prof. Dr. Oliver Gassmann',
        'current_email' => 'oliver.gassmann@unisg.ch'
    ],
    [
        'username' => 'peter.grunenfelder',
        'email' => 'jury.peter@mobility-trailblazers.com',
        'password' => 'Gr3n3n!P3ter#88',
        'display_name' => 'Peter Grünenfelder',
        'current_email' => 'peter.gruenenfelder@auto.ch'
    ],
    [
        'username' => 'kjell.gruner',
        'email' => 'jury.kjell@mobility-trailblazers.com',
        'password' => 'Kj3ll!VW#Grn56',
        'display_name' => 'Dr. Kjell Gruner',
        'current_email' => 'kjell.gruner@vw.com'
    ],
    [
        'username' => 'zheng.han',
        'email' => 'jury.zheng@mobility-trailblazers.com',
        'password' => 'Zh3ng!H@n#CN94',
        'display_name' => 'Prof. Dr. Zheng Han',
        'current_email' => 'zheng.han@tongji.edu.cn'
    ],
    [
        'username' => 'wolfgang.jenewein',
        'email' => 'jury.wolfgang@mobility-trailblazers.com',
        'password' => 'J3n3w3!n#W0lf77',
        'display_name' => 'Prof. Dr. Wolfgang Jenewein',
        'current_email' => 'wolfgang.jenewein@jenewein.com'
    ],
    [
        'username' => 'johann.jungwirth',
        'email' => 'jury.johann@mobility-trailblazers.com',
        'password' => 'Jung!w1rth#M0b82',
        'display_name' => 'Johann Jungwirth',
        'current_email' => 'johann.jungwirth@mobileye.com'
    ],
    [
        'username' => 'nikolaus.lang',
        'email' => 'jury.nikolaus@mobility-trailblazers.com',
        'password' => 'N!k0L@ng#BCG69',
        'display_name' => 'Prof. Dr. Nikolaus Lang',
        'current_email' => 'lang.nikolaus@bcg.com'
    ],
    [
        'username' => 'laura.meyer',
        'email' => 'jury.laura@mobility-trailblazers.com',
        'password' => 'L@ur@!M3y3r#HP51',
        'display_name' => 'Laura Meyer',
        'current_email' => 'laura.meyer@hotelplan.com'
    ],
    [
        'username' => 'felix.neureuther',
        'email' => 'jury.felix@mobility-trailblazers.com',
        'password' => 'F3l!x#Sk!N3ur37',
        'display_name' => 'Felix Neureuther',
        'current_email' => 'felix@neureuther.com'
    ],
    [
        'username' => 'philipp.rosler',
        'email' => 'jury.philipp@mobility-trailblazers.com',
        'password' => 'R0sl3r!Ph!l#C0n29',
        'display_name' => 'Dr. Philipp Rösler',
        'current_email' => 'philipp.roesler@consessor.com'
    ],
    [
        'username' => 'helmut.ruhl',
        'email' => 'jury.helmut@mobility-trailblazers.com',
        'password' => 'H3lm!Ruhl#AM@G48',
        'display_name' => 'Helmut Ruhl',
        'current_email' => 'helmut.ruhl@amag.ch'
    ],
    [
        'username' => 'susann.schramm',
        'email' => 'jury.susann@mobility-trailblazers.com',
        'password' => 'Sus@nn!M0t3l#85',
        'display_name' => 'Susann Schramm',
        'current_email' => 'susann.schramm@motel-one.com'
    ],
    [
        'username' => 'jurgen.stackmann',
        'email' => 'jury.jurgen@mobility-trailblazers.com',
        'password' => 'St@ckm@nn!J#Au90',
        'display_name' => 'Jürgen Stackmann',
        'current_email' => 'juergen.stackmann@automotive-expert.com'
    ]
];

// Function to update or create user
function update_or_create_jury_member($member_data) {
    // First try to find user by current email or username
    $user = get_user_by('email', $member_data['current_email']);
    if (!$user) {
        $user = get_user_by('login', $member_data['username']);
    }
    
    if ($user) {
        // Update existing user
        $user_id = $user->ID;
        
        // Update user data
        $userdata = [
            'ID' => $user_id,
            'user_email' => $member_data['email'],
            'display_name' => $member_data['display_name'],
            'user_pass' => $member_data['password']
        ];
        
        $result = wp_update_user($userdata);
        
        if (is_wp_error($result)) {
            echo "Error updating user {$member_data['username']}: " . $result->get_error_message() . "\n";
            return false;
        }
        
        // Ensure user has jury member role
        $user = new WP_User($user_id);
        if (!in_array('mt_jury_member', $user->roles)) {
            $user->add_role('mt_jury_member');
        }
        
        echo "✅ Updated user: {$member_data['username']} (ID: {$user_id})\n";
        return true;
        
    } else {
        // Create new user
        $userdata = [
            'user_login' => $member_data['username'],
            'user_email' => $member_data['email'],
            'user_pass' => $member_data['password'],
            'display_name' => $member_data['display_name'],
            'role' => 'mt_jury_member'
        ];
        
        $user_id = wp_insert_user($userdata);
        
        if (is_wp_error($user_id)) {
            echo "Error creating user {$member_data['username']}: " . $user_id->get_error_message() . "\n";
            return false;
        }
        
        echo "✅ Created new user: {$member_data['username']} (ID: {$user_id})\n";
        return true;
    }
}

// Main execution
echo "========================================\n";
echo "Mobility Trailblazers Jury Credential Update\n";
echo "========================================\n\n";

$success_count = 0;
$error_count = 0;

foreach ($jury_credentials as $member) {
    if (update_or_create_jury_member($member)) {
        $success_count++;
    } else {
        $error_count++;
    }
}

echo "\n========================================\n";
echo "Update Complete!\n";
echo "Success: {$success_count} users\n";
echo "Errors: {$error_count} users\n";
echo "========================================\n";

// Store contact mapping for reference
$contact_mapping = [];
foreach ($jury_credentials as $member) {
    $contact_mapping[$member['username']] = [
        'generic_email' => $member['email'],
        'contact_email' => $member['current_email']
    ];
}

// Save contact mapping to database for reference
update_option('mt_jury_contact_mapping', $contact_mapping);
echo "\nContact mapping saved to database.\n";
