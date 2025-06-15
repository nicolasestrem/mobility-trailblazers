<?php
$orphan_users = get_users(array('role' => 'mt_jury_member'));
$create_missing = true; // Set to true to create jury posts for users without matches

if ($create_missing && count($unlinked_users) > 0) {
    echo "Creating jury member posts for unmatched users...\n";
// ... existing code ...
} 