<?php
/**
 * Debug Permissions
 * 
 * Debug script to check user roles and permissions
 */

echo "<h1>Permissions Debug</h1>\n";

// Check if user is logged in
if (!is_user_logged_in()) {
    echo "<p>❌ User is not logged in</p>\n";
    exit;
}

$user = wp_get_current_user();
echo "<p>✅ User logged in: {$user->user_login} (ID: {$user->ID})</p>\n";

echo "<h2>1. User Roles</h2>\n";
echo "<p>User roles: " . implode(', ', $user->roles) . "</p>\n";

// Check specific roles
$roles_to_check = array('mt-jury-member', 'mt_jury_member', 'administrator', 'editor');
foreach ($roles_to_check as $role) {
    if (in_array($role, $user->roles)) {
        echo "<p>✅ User has role: {$role}</p>\n";
    } else {
        echo "<p>❌ User does NOT have role: {$role}</p>\n";
    }
}

echo "<h2>2. User Capabilities</h2>\n";
$capabilities_to_check = array(
    'mt_submit_evaluations',
    'read',
    'edit_posts',
    'manage_options'
);

foreach ($capabilities_to_check as $cap) {
    if (current_user_can($cap)) {
        echo "<p>✅ User can: {$cap}</p>\n";
    } else {
        echo "<p>❌ User cannot: {$cap}</p>\n";
    }
}

echo "<h2>3. Jury Member Profile</h2>\n";
$jury_member = mt_get_jury_member_by_user_id($user->ID);
if ($jury_member) {
    echo "<p>✅ Jury member profile found (ID: {$jury_member->ID})</p>\n";
    echo "<p>Jury member title: {$jury_member->post_title}</p>\n";
} else {
    echo "<p>❌ Jury member profile not found</p>\n";
}

echo "<h2>4. Nonce Test</h2>\n";
$nonce = wp_create_nonce('mt_jury_nonce');
echo "<p>Nonce created: {$nonce}</p>\n";
echo "<p>Nonce verification: " . (wp_verify_nonce($nonce, 'mt_jury_nonce') ? '✅ Valid' : '❌ Invalid') . "</p>\n";

echo "<h2>5. Permission Callback Test</h2>\n";

// Simulate the permission callback logic
echo "<h3>Step 1: Check if user is logged in</h3>\n";
if (is_user_logged_in()) {
    echo "<p>✅ User is logged in</p>\n";
} else {
    echo "<p>❌ User is not logged in</p>\n";
    exit;
}

echo "<h3>Step 2: Check if user has jury member role</h3>\n";
if (in_array('mt-jury-member', $user->roles)) {
    echo "<p>✅ User has mt-jury-member role</p>\n";
} else {
    echo "<p>❌ User does NOT have mt-jury-member role</p>\n";
    echo "<p>Available roles: " . implode(', ', $user->roles) . "</p>\n";
}

echo "<h3>Step 3: Check nonce verification</h3>\n";
if (wp_verify_nonce($nonce, 'mt_jury_nonce')) {
    echo "<p>✅ Nonce verification passed</p>\n";
} else {
    echo "<p>❌ Nonce verification failed</p>\n";
}

echo "<h2>6. Alternative Role Checks</h2>\n";

// Check if user has any jury-related role
$jury_roles = array('mt-jury-member', 'mt_jury_member', 'jury_member');
$has_jury_role = false;

foreach ($jury_roles as $role) {
    if (in_array($role, $user->roles)) {
        echo "<p>✅ User has jury role: {$role}</p>\n";
        $has_jury_role = true;
    }
}

if (!$has_jury_role) {
    echo "<p>❌ User has no jury-related roles</p>\n";
}

echo "<h2>7. WordPress Role System</h2>\n";
echo "<p>WordPress version: " . get_bloginfo('version') . "</p>\n";
echo "<p>User roles system: " . (function_exists('wp_roles') ? 'Available' : 'Not available') . "</p>\n";

if (function_exists('wp_roles')) {
    $wp_roles = wp_roles();
    echo "<p>Available roles in system:</p>\n";
    echo "<ul>\n";
    foreach ($wp_roles->get_names() as $role_key => $role_name) {
        $highlight = (in_array($role_key, $user->roles)) ? ' style="color: green; font-weight: bold;"' : '';
        echo "<li{$highlight}>{$role_key} - {$role_name}</li>\n";
    }
    echo "</ul>\n";
}

echo "<h2>8. Recommendation</h2>\n";

if (in_array('mt-jury-member', $user->roles)) {
    echo "<p>✅ User has correct role. The issue might be with nonce verification or jury member profile.</p>\n";
} elseif (in_array('mt_jury_member', $user->roles)) {
    echo "<p>⚠️ User has 'mt_jury_member' role but permission callback checks for 'mt-jury-member'. This is the issue!</p>\n";
} elseif (in_array('administrator', $user->roles)) {
    echo "<p>⚠️ User is administrator but doesn't have jury member role. Consider adding jury role or modifying permission callback.</p>\n";
} else {
    echo "<p>❌ User has no jury-related roles. Need to assign appropriate role.</p>\n";
} 