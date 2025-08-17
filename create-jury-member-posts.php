<?php
/**
 * Create Jury Member Posts and Link to User Accounts
 * 
 * This script creates jury_member custom posts and links them to WordPress users
 * Also ensures no password change is required on first login
 */

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    require_once('../../../wp-load.php');
}

// Jury member data with descriptions
$jury_members_data = [
    [
        'user_login' => 'Nicolas',
        'display_name' => 'Nicolas Estrem',
        'title' => 'Platform Administrator',
        'organization' => 'Mobility Trailblazers',
        'bio' => 'Platform administrator and technical lead for the Mobility Trailblazers award program.',
        'expertise' => 'Technology, Platform Development, Award Management'
    ],
    [
        'user_login' => 'Tobias',
        'display_name' => 'Tobias Tomczak',
        'title' => 'Co-Founder',
        'organization' => 'Tomczak & Gross',
        'bio' => 'Strategic advisor and co-founder specializing in mobility transformation.',
        'expertise' => 'Strategy, Business Development, Mobility Innovation'
    ],
    [
        'user_login' => '..andreas.herr