<?php
/**
 * TEMPORARY DIAGNOSTIC SCRIPT
 * Add this to a new file: wp-content/plugins/mobility-trailblazers/diagnostic.php
 * Access via: yourdomain.com/wp-content/plugins/mobility-trailblazers/diagnostic.php
 */

// Bootstrap WordPress
define('WP_USE_THEMES', false);
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Access denied');
}

echo "<h1>Mobility Trailblazers Diagnostic</h1>";

global $wpdb;

// 1. Check if custom post types exist
echo "<h2>1. Post Types Status</h2>";
$candidate_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_candidate' AND post_status = 'publish'");
$jury_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'mt_jury' AND post_status = 'publish'");

echo "<p><strong>Candidates:</strong> {$candidate_count}</p>";
echo "<p><strong>Jury Members:</strong> {$jury_count}</p>";

// 2. Check assignments
echo "<h2>2. Assignment Status</h2>";
$assignments = $wpdb->get_results("
    SELECT p.post_title as candidate_name, j.post_title as jury_name 
    FROM {$wpdb->postmeta} pm
    JOIN {$wpdb->posts} p ON pm.post_id = p.ID
    JOIN {$wpdb->posts} j ON pm.meta_value = j.ID
    WHERE pm.meta_key = '_mt_assigned_jury_member'
    AND p.post_type = 'mt_candidate'
    AND j.post_type = 'mt_jury'
    LIMIT 10
");

if ($assignments) {
    echo "<table border='1'><tr><th>Candidate</th><th>Assigned to Jury</th></tr>";
    foreach ($assignments as $assignment) {
        echo "<tr><td>{$assignment->candidate_name}</td><td>{$assignment->jury_name}</td></tr>";
    }
    echo "</table>";
    echo "<p><strong>Total assignments:</strong> " . count($assignments) . "</p>";
} else {
    echo "<p style='color: red;'><strong>No assignments found!</strong></p>";
    echo "<p>Sample assignment creation:</p>";
    echo "<pre>update_post_meta(CANDIDATE_ID, '_mt_assigned_jury_member', JURY_ID);</pre>";
}

// 3. Check jury-user linking
echo "<h2>3. Jury-User Linking</h2>";
$jury_users = $wpdb->get_results("
    SELECT p.post_title as jury_name, pm.meta_value as user_id_or_email, pm.meta_key
    FROM {$wpdb->posts} p
    JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
    WHERE p.post_type = 'mt_jury'
    AND (pm.meta_key = '_mt_jury_user_id' OR pm.meta_key = '_mt_jury_email')
");

if ($jury_users) {
    echo "<table border='1'><tr><th>Jury Member</th><th>Linked Via</th><th>Value</th></tr>";
    foreach ($jury_users as $link) {
        echo "<tr><td>{$link->jury_name}</td><td>{$link->meta_key}</td><td>{$link->user_id_or_email}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>No jury-user links found!</strong></p>";
}

// 4. Check database tables
echo "<h2>4. Database Tables</h2>";
$required_tables = [
    'mt_candidate_scores',
    'mt_votes', 
    'mt_public_votes'
];

foreach ($required_tables as $table) {
    $full_table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
    
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$full_table_name}");
        echo "<p><strong>{$table}:</strong> ✅ Exists ({$count} records)</p>";
    } else {
        echo "<p><strong>{$table}:</strong> ❌ Missing</p>";
    }
}

// 5. Show current user info
echo "<h2>5. Current User</h2>";
$current_user = wp_get_current_user();
echo "<p><strong>User ID:</strong> {$current_user->ID}</p>";
echo "<p><strong>Email:</strong> {$current_user->user_email}</p>";
echo "<p><strong>Roles:</strong> " . implode(', ', $current_user->roles) . "</p>";

// Check if current user passes the permission check
$current_user_id = get_current_user_id();
$is_jury = false;
$is_admin = current_user_can('manage_options');

// Check if user is a jury member
$jury_post = get_posts(array(
    'post_type' => 'mt_jury',
    'meta_query' => array(
        'relation' => 'OR',
        array(
            'key' => '_mt_jury_email',
            'value' => $current_user->user_email,
            'compare' => '='
        ),
        array(
            'key' => '_mt_jury_user_id', 
            'value' => $current_user_id,
            'compare' => '='
        )
    )
));
$is_jury = !empty($jury_post);

echo "<h3>Permission Check:</h3>";
echo "<p><strong>Is Jury Member:</strong> " . ($is_jury ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>Is Admin:</strong> " . ($is_admin ? '✅ Yes' : '❌ No') . "</p>";
echo "<p><strong>Should see menu:</strong> " . (($is_jury || $is_admin) ? '✅ Yes' : '❌ No') . "</p>";

// Test menu registration directly
echo "<h3>Direct Menu Registration Test:</h3>";
if (isset($_POST['test_menu_registration'])) {
    // Force menu registration
    if (class_exists('MobilityTrailblazersPlugin')) {
        $plugin = new MobilityTrailblazersPlugin();
        $plugin->add_jury_dashboard_menu();
        echo "<div class='notice notice-success'><p>Attempted to register menu directly.</p></div>";
        
        // Check again
        global $submenu;
        if (isset($submenu['mt-award-system'])) {
            $found = false;
            foreach ($submenu['mt-award-system'] as $item) {
                if ($item[2] === 'mt-jury-dashboard') {
                    $found = true;
                    echo "<p style='color: green;'>✅ Menu successfully registered!</p>";
                    break;
                }
            }
            if (!$found) {
                echo "<p style='color: red;'>❌ Menu registration failed</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ MobilityTrailblazersPlugin class not found</p>";
    }
}

// 6. Quick Fix Buttons
echo "<h2>6. Quick Fixes</h2>";

if (isset($_GET['create_test_assignment'])) {
    // Create a test assignment
    $candidates = get_posts(array('post_type' => 'mt_candidate', 'posts_per_page' => 1));
    $jury_members = get_posts(array('post_type' => 'mt_jury', 'posts_per_page' => 1));
    
    if ($candidates && $jury_members) {
        update_post_meta($candidates[0]->ID, '_mt_assigned_jury_member', $jury_members[0]->ID);
        echo "<p style='color: green;'>✅ Test assignment created: {$candidates[0]->post_title} → {$jury_members[0]->post_title}</p>";
    } else {
        echo "<p style='color: red;'>❌ Need at least 1 candidate and 1 jury member</p>";
    }
}

if (isset($_GET['link_current_user'])) {
    $jury_members = get_posts(array('post_type' => 'mt_jury', 'posts_per_page' => 1));
    if ($jury_members) {
        update_post_meta($jury_members[0]->ID, '_mt_jury_user_id', $current_user->ID);
        echo "<p style='color: green;'>✅ Linked current user to jury member: {$jury_members[0]->post_title}</p>";
    }
}

echo "<p><a href='?create_test_assignment=1'>Create Test Assignment</a></p>";
echo "<p><a href='?link_current_user=1'>Link Current User to First Jury Member</a></p>";

// 7. Menu Registration Debug
echo "<h2>7. Menu Registration Debug</h2>";

// Check if the action is hooked
global $wp_filter;
$menu_hook_exists = isset($wp_filter['admin_menu']) && !empty($wp_filter['admin_menu']);
echo "<p><strong>admin_menu hook registered:</strong> " . ($menu_hook_exists ? '✅ Yes' : '❌ No') . "</p>";

// Check if our specific function is in the admin_menu hook
if ($menu_hook_exists) {
    $our_hook_found = false;
    foreach ($wp_filter['admin_menu'] as $priority => $hooks) {
        foreach ($hooks as $hook) {
            if (is_array($hook['function']) && 
                is_object($hook['function'][0]) && 
                get_class($hook['function'][0]) === 'MobilityTrailblazersPlugin' &&
                $hook['function'][1] === 'add_jury_dashboard_menu') {
                $our_hook_found = true;
                echo "<p style='color: green;'>✅ add_jury_dashboard_menu is hooked at priority $priority</p>";
            }
        }
    }
    if (!$our_hook_found) {
        echo "<p style='color: red;'>❌ add_jury_dashboard_menu is NOT found in admin_menu hooks</p>";
    }
}

// Check global submenu
global $submenu;
echo "<h3>Submenus under mt-award-system:</h3>";
if (isset($submenu['mt-award-system'])) {
    echo "<pre>" . print_r($submenu['mt-award-system'], true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ No submenus found for mt-award-system</p>";
}

// Check if the parent menu exists
global $menu;
$parent_exists = false;
foreach ($menu as $item) {
    if ($item[2] === 'mt-award-system') {
        $parent_exists = true;
        break;
    }
}
echo "<p><strong>Parent menu (mt-award-system) exists:</strong> " . ($parent_exists ? '✅ Yes' : '❌ No') . "</p>";

// Alternative: Quick fix to force menu registration
echo "<h3>Quick Fix - Force Menu Registration:</h3>";
echo "<p>Add this code temporarily to test if menu registration works:</p>";
echo "<pre style='background: #f0f0f0; padding: 10px; border: 1px solid #ddd;'>";
echo "// Add this to your theme's functions.php temporarily:
add_action('admin_menu', function() {
    if (current_user_can('manage_options')) {
        add_submenu_page(
            'mt-award-system',
            'My Dashboard (Test)',
            'My Dashboard (Test)',
            'manage_options',
            'mt-jury-dashboard-test',
            function() {
                echo '<h1>Test Dashboard</h1>';
                echo '<p>If you see this, menu registration works!</p>';
            }
        );
    }
}, 99);";
echo "</pre>";

echo "<h2>8. Next Steps</h2>";
echo "<ul>";
echo "<li>✅ Deploy the missing functions to mobility-trailblazers.php</li>";
echo "<li>✅ Copy jury dashboard templates</li>";
if (!$assignments) {
    echo "<li>❌ Create candidate assignments</li>";
}
if (!$jury_users) {
    echo "<li>❌ Link jury members to WordPress users</li>";  
}
$missing_tables = array_filter($required_tables, function($table) use ($wpdb) {
    return !$wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}{$table}'");
});
if ($missing_tables) {
    echo "<li>❌ Create missing database tables: " . implode(', ', $missing_tables) . "</li>";
}
echo "</ul>";
?>