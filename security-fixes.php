<?php
/**
 * Security Fixes for Mobility Trailblazers Plugin
 * 
 * This file contains security fixes for:
 * 1. Unescaped output (XSS vulnerabilities)
 * 2. Missing nonce verification
 * 3. SQL injection prevention
 * 4. Permission checks
 * 
 * @package MobilityTrailblazers
 * @since 2.0.14
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * IDENTIFIED SECURITY ISSUES AND FIXES:
 * 
 * CRITICAL ISSUES:
 * 
 * 1. UNESCAPED OUTPUT IN TEMPLATES:
 *    - templates/admin/dashboard.php - Lines with unescaped $eval_stats, $assign_stats
 *    - templates/admin/assignments.php - Multiple unescaped outputs
 *    - templates/admin/evaluations.php - Unescaped evaluation data
 *    - templates/frontend/jury-dashboard.php - Unescaped user data
 * 
 * 2. MISSING NONCE VERIFICATION:
 *    - includes/admin/class-mt-admin.php - Some AJAX handlers missing nonce checks
 *    - includes/ajax/class-mt-evaluation-ajax.php - Inconsistent nonce verification
 *    - includes/ajax/class-mt-assignment-ajax.php - Some methods missing verification
 * 
 * 3. SQL INJECTION RISKS:
 *    - Direct $_GET and $_POST usage without proper sanitization
 *    - Repository classes need prepared statements
 * 
 * 4. INSUFFICIENT PERMISSION CHECKS:
 *    - Some AJAX handlers don't check capabilities properly
 */

// FIXES TO BE APPLIED:

echo "===== SECURITY FIXES TO APPLY =====\n\n";

echo "1. FILE: templates/admin/dashboard.php\n";
echo "   ISSUES: Unescaped output\n";
echo "   FIXES:\n";
?>

<!-- FIX 1: templates/admin/dashboard.php -->
<!-- REPLACE LINE 28-31 (unescaped stats) -->
OLD:
<h3><?php echo $eval_stats['total']; ?></h3>
NEW:
<h3><?php echo esc_html($eval_stats['total']); ?></h3>

<!-- REPLACE LINE 32-35 -->
OLD:
<h3><?php echo $eval_stats['completed']; ?></h3>
NEW:
<h3><?php echo esc_html($eval_stats['completed']); ?></h3>

<!-- REPLACE LINE 36-39 -->
OLD:
<h3><?php echo $eval_stats['drafts']; ?></h3>
NEW:
<h3><?php echo esc_html($eval_stats['drafts']); ?></h3>

<?php
echo "\n2. FILE: templates/admin/assignments.php\n";
echo "   ISSUES: Missing nonce verification in inline JavaScript, unescaped output\n";
echo "   FIXES:\n";
?>

<!-- FIX 2: templates/admin/assignments.php -->
<!-- ADD after line 50 (Debug section should be removed in production) -->
<?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
<!-- Debug section only in development -->
<?php endif; ?>

<!-- REPLACE inline testAjax() function (lines 60-77) -->
OLD:
function testAjax() {
    console.log('Testing AJAX...');
    jQuery.ajax({
        url: '<?php echo admin_url('admin-ajax.php'); ?>',
NEW:
function testAjax() {
    if (!confirm('This is a debug function. Continue?')) return;
    console.log('Testing AJAX...');
    jQuery.ajax({
        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',

<?php
echo "\n3. FILE: includes/ajax/class-mt-evaluation-ajax.php\n";
echo "   ISSUES: Inconsistent nonce verification, unvalidated input\n";
echo "   FIXES:\n";
?>

<!-- FIX 3: includes/ajax/class-mt-evaluation-ajax.php -->
<!-- ADD proper nonce verification to all methods -->
REPLACE submit_evaluation() method beginning:
OLD:
public function submit_evaluation() {
    $this->verify_nonce();
    $this->check_permission('mt_submit_evaluations');
    
NEW:
public function submit_evaluation() {
    // Verify nonce with proper error handling
    if (!$this->verify_nonce()) {
        wp_send_json_error(__('Security check failed. Please refresh the page and try again.', 'mobility-trailblazers'));
        return;
    }
    
    // Check permissions
    if (!$this->check_permission('mt_submit_evaluations')) {
        wp_send_json_error(__('You do not have permission to submit evaluations.', 'mobility-trailblazers'));
        return;
    }

<?php
echo "\n4. FILE: includes/ajax/class-mt-assignment-ajax.php\n";
echo "   ISSUES: SQL injection risks, missing sanitization\n";
echo "   FIXES:\n";
?>

<!-- FIX 4: includes/ajax/class-mt-assignment-ajax.php -->
<!-- SANITIZE all input parameters -->
REPLACE in export_assignments() method:
OLD:
$assignments = $assignment_repo->find_all();
NEW:
// Sanitize any filter parameters
$filters = [];
if (isset($_POST['filter_status'])) {
    $filters['status'] = sanitize_text_field($_POST['filter_status']);
}
if (isset($_POST['filter_jury'])) {
    $filters['jury_member_id'] = absint($_POST['filter_jury']);
}
$assignments = $assignment_repo->find_all($filters);

<?php
echo "\n5. FILE: includes/admin/class-mt-admin.php\n";
echo "   ISSUES: Direct $_GET usage without sanitization\n";
echo "   FIXES:\n";
?>

<!-- FIX 5: includes/admin/class-mt-admin.php -->
<!-- SANITIZE all $_GET parameters -->
REPLACE in render_evaluations_page():
OLD:
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
NEW:
$page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$page = max(1, $page);

REPLACE:
OLD:
if (isset($_GET['evaluation_id'])) {
    $this->render_single_evaluation();
NEW:
if (isset($_GET['evaluation_id'])) {
    $evaluation_id = absint($_GET['evaluation_id']);
    if ($evaluation_id > 0) {
        $this->render_single_evaluation($evaluation_id);
    } else {
        wp_die(__('Invalid evaluation ID.', 'mobility-trailblazers'));
    }

<?php

echo "\n\n===== APPLYING FIXES AUTOMATICALLY =====\n\n";

// Now let's apply the fixes programmatically
