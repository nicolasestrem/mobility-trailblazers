# Mobility Trailblazers - Security Architecture Specification

**Version:** 1.0.0  
**Last Updated:** 2025-01-22  
**Status:** Complete

## Table of Contents
1. [Security Overview](#security-overview)
2. [Authentication & Authorization](#authentication--authorization)
3. [Input Validation & Sanitization](#input-validation--sanitization)
4. [Output Escaping](#output-escaping)
5. [AJAX Security](#ajax-security)
6. [Database Security](#database-security)
7. [File Upload Security](#file-upload-security)
8. [Session Management](#session-management)
9. [Audit Logging](#audit-logging)
10. [Security Checklist](#security-checklist)

## Security Overview

The Mobility Trailblazers plugin implements defense-in-depth security with multiple layers of protection following OWASP best practices and WordPress security guidelines.

### Security Principles
- **Least Privilege**: Users have minimum necessary permissions
- **Defense in Depth**: Multiple security layers
- **Fail Secure**: Defaults to secure state on errors
- **Input Validation**: Never trust user input
- **Output Escaping**: Always escape output
- **Audit Trail**: Log all sensitive operations

### Threat Model
```yaml
Primary Threats:
  - SQL Injection: Database manipulation
  - XSS (Cross-Site Scripting): JavaScript injection
  - CSRF (Cross-Site Request Forgery): Unauthorized actions
  - Privilege Escalation: Unauthorized access
  - Information Disclosure: Data leakage
  - File Upload Attacks: Malicious files
  - Brute Force: Login attempts
```

## Authentication & Authorization

### User Roles and Capabilities

```php
<?php
namespace MobilityTrailblazers\Core;

class MT_Roles {
    /**
     * Add custom roles
     */
    public function add_roles() {
        // Jury Member Role
        add_role('mt_jury_member', __('Jury Member', 'mobility-trailblazers'), [
            'read' => true,
            'mt_submit_evaluations' => true,
            'mt_view_assigned_candidates' => true,
            'mt_view_own_evaluations' => true,
            'upload_files' => true
        ]);
        
        // Jury Admin Role
        add_role('mt_jury_admin', __('Jury Admin', 'mobility-trailblazers'), [
            'read' => true,
            'mt_view_all_evaluations' => true,
            'mt_manage_assignments' => true,
            'mt_export_evaluations' => true,
            'mt_view_reports' => true,
            'mt_manage_jury_members' => true,
            'upload_files' => true
        ]);
    }
    
    /**
     * Add capabilities to existing roles
     */
    public function add_capabilities() {
        // Administrator gets all capabilities
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('mt_manage_system');
            $admin->add_cap('mt_import_candidates');
            $admin->add_cap('mt_export_data');
            $admin->add_cap('mt_view_audit_log');
            $admin->add_cap('mt_manage_settings');
            
            // All jury capabilities
            $admin->add_cap('mt_submit_evaluations');
            $admin->add_cap('mt_view_all_evaluations');
            $admin->add_cap('mt_manage_assignments');
            $admin->add_cap('mt_manage_jury_members');
        }
        
        // Editor can manage content
        $editor = get_role('editor');
        if ($editor) {
            $editor->add_cap('mt_view_candidates');
            $editor->add_cap('mt_edit_candidates');
            $editor->add_cap('mt_view_reports');
        }
    }
}
```

### Capability Checking

```php
<?php
/**
 * Check user permissions before operations
 */
class MT_Security {
    /**
     * Check if user can submit evaluations
     */
    public static function can_submit_evaluation($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'mt_submit_evaluations');
    }
    
    /**
     * Check if user can manage assignments
     */
    public static function can_manage_assignments($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        return user_can($user_id, 'mt_manage_assignments') || 
               user_can($user_id, 'mt_manage_system');
    }
    
    /**
     * Check if user can access candidate
     */
    public static function can_access_candidate($candidate_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Admins can access all
        if (user_can($user_id, 'mt_manage_system')) {
            return true;
        }
        
        // Check if jury member is assigned
        if (user_can($user_id, 'mt_submit_evaluations')) {
            global $wpdb;
            $table = $wpdb->prefix . 'mt_jury_assignments';
            
            $assignment = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} 
                WHERE jury_member_id = %d AND candidate_id = %d",
                $user_id,
                $candidate_id
            ));
            
            return !empty($assignment);
        }
        
        return false;
    }
}
```

## Input Validation & Sanitization

### Comprehensive Input Sanitization

```php
<?php
namespace MobilityTrailblazers\Security;

class MT_Input_Validator {
    /**
     * Sanitize text input
     */
    public static function sanitize_text($input) {
        return sanitize_text_field($input);
    }
    
    /**
     * Sanitize textarea input
     */
    public static function sanitize_textarea($input) {
        return sanitize_textarea_field($input);
    }
    
    /**
     * Sanitize email
     */
    public static function sanitize_email($email) {
        $sanitized = sanitize_email($email);
        
        if (!is_email($sanitized)) {
            return false;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize URL
     */
    public static function sanitize_url($url) {
        // Add protocol if missing
        if (!empty($url) && !preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        $sanitized = esc_url_raw($url);
        
        // Validate URL format
        if (!filter_var($sanitized, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        return $sanitized;
    }
    
    /**
     * Sanitize and validate score (0-10 with 0.5 increments)
     */
    public static function sanitize_score($score) {
        $score = floatval($score);
        
        // Validate range
        if ($score < 0 || $score > 10) {
            return false;
        }
        
        // Round to nearest 0.5
        $score = round($score * 2) / 2;
        
        return $score;
    }
    
    /**
     * Sanitize HTML with allowed tags
     */
    public static function sanitize_html($html) {
        $allowed_html = [
            'a' => [
                'href' => [],
                'title' => [],
                'target' => [],
                'rel' => []
            ],
            'br' => [],
            'em' => [],
            'strong' => [],
            'p' => ['class' => []],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'blockquote' => ['cite' => []],
        ];
        
        return wp_kses($html, $allowed_html);
    }
    
    /**
     * Validate and sanitize array of IDs
     */
    public static function sanitize_id_array($ids) {
        if (!is_array($ids)) {
            return [];
        }
        
        return array_map('intval', array_filter($ids, 'is_numeric'));
    }
    
    /**
     * Validate status values
     */
    public static function validate_status($status, $allowed = []) {
        if (empty($allowed)) {
            $allowed = ['draft', 'completed', 'submitted'];
        }
        
        if (!in_array($status, $allowed, true)) {
            return false;
        }
        
        return $status;
    }
}
```

### Form Validation Example

```php
<?php
/**
 * Validate evaluation form submission
 */
class MT_Evaluation_Validator {
    private $errors = [];
    
    public function validate_submission($data) {
        $this->errors = [];
        
        // Required fields
        if (empty($data['candidate_id'])) {
            $this->errors[] = __('Candidate ID is required', 'mobility-trailblazers');
        } else {
            $data['candidate_id'] = intval($data['candidate_id']);
        }
        
        // Validate scores
        $score_fields = ['courage', 'innovation', 'implementation', 'relevance', 'visibility'];
        foreach ($score_fields as $field) {
            $key = $field . '_score';
            if (isset($data[$key])) {
                $score = MT_Input_Validator::sanitize_score($data[$key]);
                if ($score === false) {
                    $this->errors[] = sprintf(
                        __('%s must be between 0 and 10', 'mobility-trailblazers'),
                        ucfirst($field)
                    );
                } else {
                    $data[$key] = $score;
                }
            }
        }
        
        // Sanitize comments
        if (isset($data['comments'])) {
            $data['comments'] = MT_Input_Validator::sanitize_textarea($data['comments']);
        }
        
        // Validate status
        if (isset($data['status'])) {
            $status = MT_Input_Validator::validate_status($data['status']);
            if ($status === false) {
                $this->errors[] = __('Invalid status value', 'mobility-trailblazers');
            } else {
                $data['status'] = $status;
            }
        }
        
        return empty($this->errors) ? $data : false;
    }
    
    public function get_errors() {
        return $this->errors;
    }
}
```

## Output Escaping

### Escaping Functions

```php
<?php
/**
 * Output escaping helpers
 */
class MT_Output_Escaper {
    /**
     * Escape HTML output
     */
    public static function html($text) {
        return esc_html($text);
    }
    
    /**
     * Escape attribute output
     */
    public static function attr($text) {
        return esc_attr($text);
    }
    
    /**
     * Escape URL output
     */
    public static function url($url) {
        return esc_url($url);
    }
    
    /**
     * Escape JavaScript output
     */
    public static function js($text) {
        return esc_js($text);
    }
    
    /**
     * Escape textarea content
     */
    public static function textarea($text) {
        return esc_textarea($text);
    }
    
    /**
     * Output safe HTML with allowed tags
     */
    public static function kses($html, $allowed_html = null) {
        if ($allowed_html === null) {
            $allowed_html = wp_kses_allowed_html('post');
        }
        
        return wp_kses($html, $allowed_html);
    }
}
```

### Template Escaping Examples

```php
<!-- Always escape output in templates -->
<div class="mt-candidate-card" data-id="<?php echo esc_attr($candidate->ID); ?>">
    <h3><?php echo esc_html($candidate->post_title); ?></h3>
    
    <div class="mt-organization">
        <?php echo esc_html(get_post_meta($candidate->ID, '_mt_organization', true)); ?>
    </div>
    
    <div class="mt-links">
        <?php 
        $website = get_post_meta($candidate->ID, '_mt_website_url', true);
        if ($website) : ?>
            <a href="<?php echo esc_url($website); ?>" 
               target="_blank" 
               rel="noopener noreferrer">
                <?php esc_html_e('Website', 'mobility-trailblazers'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <div class="mt-description">
        <?php 
        // For content with HTML, use wp_kses
        echo wp_kses_post(get_post_meta($candidate->ID, '_mt_description', true)); 
        ?>
    </div>
    
    <script>
        // Escape for JavaScript context
        var candidateName = '<?php echo esc_js($candidate->post_title); ?>';
    </script>
</div>
```

## AJAX Security

### Base AJAX Handler with Security

```php
<?php
namespace MobilityTrailblazers\Ajax;

abstract class MT_Base_Ajax {
    /**
     * Verify nonce for AJAX requests
     */
    protected function verify_nonce($nonce_name = 'mt_ajax_nonce') {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        
        if (!wp_verify_nonce($nonce, $nonce_name)) {
            MT_Logger::security_event('Nonce verification failed', [
                'action' => $_REQUEST['action'] ?? 'unknown',
                'user_id' => get_current_user_id(),
                'ip' => $_SERVER['REMOTE_ADDR']
            ]);
            
            $this->error(__('Security check failed', 'mobility-trailblazers'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Check user permission
     */
    protected function check_permission($capability) {
        if (!current_user_can($capability)) {
            MT_Logger::security_event('Permission denied', [
                'capability' => $capability,
                'user_id' => get_current_user_id(),
                'action' => $_REQUEST['action'] ?? 'unknown'
            ]);
            
            $this->error(__('Insufficient permissions', 'mobility-trailblazers'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate request method
     */
    protected function validate_method($method = 'POST') {
        if ($_SERVER['REQUEST_METHOD'] !== $method) {
            $this->error(__('Invalid request method', 'mobility-trailblazers'));
            return false;
        }
        
        return true;
    }
    
    /**
     * Get and sanitize parameter
     */
    protected function get_param($key, $default = null) {
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
        
        return $default;
    }
    
    /**
     * Get sanitized text parameter
     */
    protected function get_text_param($key, $default = '') {
        return sanitize_text_field($this->get_param($key, $default));
    }
    
    /**
     * Get sanitized integer parameter
     */
    protected function get_int_param($key, $default = 0) {
        return intval($this->get_param($key, $default));
    }
    
    /**
     * Send error response
     */
    protected function error($message = '', $data = null) {
        wp_send_json_error([
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Send success response
     */
    protected function success($data = null, $message = '') {
        wp_send_json_success([
            'message' => $message,
            'data' => $data
        ]);
    }
}
```

### AJAX Handler Implementation

```php
<?php
class MT_Evaluation_Ajax extends MT_Base_Ajax {
    /**
     * Initialize AJAX handlers
     */
    public function init() {
        add_action('wp_ajax_mt_submit_evaluation', [$this, 'submit_evaluation']);
        add_action('wp_ajax_mt_save_draft', [$this, 'save_draft']);
        add_action('wp_ajax_mt_get_evaluation', [$this, 'get_evaluation']);
    }
    
    /**
     * Submit evaluation
     */
    public function submit_evaluation() {
        // Security checks
        if (!$this->verify_nonce()) {
            return;
        }
        
        if (!$this->validate_method('POST')) {
            return;
        }
        
        if (!$this->check_permission('mt_submit_evaluations')) {
            return;
        }
        
        // Get and validate data
        $candidate_id = $this->get_int_param('candidate_id');
        $jury_member_id = get_current_user_id();
        
        // Verify assignment exists
        if (!MT_Security::can_access_candidate($candidate_id, $jury_member_id)) {
            $this->error(__('You are not assigned to this candidate', 'mobility-trailblazers'));
            return;
        }
        
        // Collect and validate scores
        $data = [
            'jury_member_id' => $jury_member_id,
            'candidate_id' => $candidate_id,
            'courage_score' => MT_Input_Validator::sanitize_score($this->get_param('courage_score')),
            'innovation_score' => MT_Input_Validator::sanitize_score($this->get_param('innovation_score')),
            'implementation_score' => MT_Input_Validator::sanitize_score($this->get_param('implementation_score')),
            'relevance_score' => MT_Input_Validator::sanitize_score($this->get_param('relevance_score')),
            'visibility_score' => MT_Input_Validator::sanitize_score($this->get_param('visibility_score')),
            'comments' => MT_Input_Validator::sanitize_textarea($this->get_param('comments')),
            'status' => 'submitted'
        ];
        
        // Validate all scores are present
        foreach (['courage', 'innovation', 'implementation', 'relevance', 'visibility'] as $criterion) {
            if ($data[$criterion . '_score'] === false) {
                $this->error(sprintf(
                    __('Invalid %s score', 'mobility-trailblazers'),
                    $criterion
                ));
                return;
            }
        }
        
        // Save evaluation
        try {
            $service = MT_Plugin::container()->make('MT_Evaluation_Service');
            $result = $service->submit_final($data);
            
            if ($result) {
                // Log successful submission
                MT_Audit_Logger::log('evaluation_submitted', [
                    'jury_member_id' => $jury_member_id,
                    'candidate_id' => $candidate_id,
                    'evaluation_id' => $result
                ]);
                
                $this->success([
                    'evaluation_id' => $result,
                    'message' => __('Evaluation submitted successfully', 'mobility-trailblazers')
                ]);
            } else {
                $this->error(__('Failed to save evaluation', 'mobility-trailblazers'));
            }
        } catch (Exception $e) {
            MT_Logger::error('Evaluation submission failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->error(__('An error occurred. Please try again.', 'mobility-trailblazers'));
        }
    }
}
```

### JavaScript AJAX Security

```javascript
// Secure AJAX request pattern
var MTAjax = {
    nonce: mt_ajax.nonce,
    
    /**
     * Send secure AJAX request
     */
    request: function(action, data, callback) {
        // Always include nonce
        data.action = action;
        data.nonce = this.nonce;
        
        jQuery.ajax({
            url: mt_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: data,
            beforeSend: function(xhr) {
                // Add custom header for additional security
                xhr.setRequestHeader('X-MT-Request', 'AJAX');
            },
            success: function(response) {
                if (response.success) {
                    if (callback) {
                        callback(response.data);
                    }
                } else {
                    MTAjax.handleError(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                MTAjax.handleError('Network error: ' + error);
            }
        });
    },
    
    /**
     * Handle errors
     */
    handleError: function(message) {
        console.error('AJAX Error:', message);
        // Show user-friendly error message
        alert(message || 'An error occurred. Please try again.');
    }
};
```

## Database Security

### Prepared Statements

```php
<?php
/**
 * Always use prepared statements for database queries
 */
class MT_Database_Security {
    /**
     * Safe query with prepared statement
     */
    public static function get_evaluation($jury_id, $candidate_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_evaluations';
        
        // Always use prepare() for dynamic values
        $query = $wpdb->prepare(
            "SELECT * FROM {$table} 
            WHERE jury_member_id = %d 
            AND candidate_id = %d",
            $jury_id,
            $candidate_id
        );
        
        return $wpdb->get_row($query);
    }
    
    /**
     * Safe insert with data validation
     */
    public static function insert_evaluation($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_evaluations';
        
        // Sanitize data
        $insert_data = [
            'jury_member_id' => intval($data['jury_member_id']),
            'candidate_id' => intval($data['candidate_id']),
            'courage_score' => intval($data['courage_score'] * 2), // Store as 0-20
            'innovation_score' => intval($data['innovation_score'] * 2),
            'implementation_score' => intval($data['implementation_score'] * 2),
            'relevance_score' => intval($data['relevance_score'] * 2),
            'visibility_score' => intval($data['visibility_score'] * 2),
            'total_score' => floatval($data['total_score']),
            'comments' => sanitize_textarea_field($data['comments']),
            'status' => sanitize_text_field($data['status'])
        ];
        
        // Define formats for each field
        $formats = [
            '%d', // jury_member_id
            '%d', // candidate_id
            '%d', // courage_score
            '%d', // innovation_score
            '%d', // implementation_score
            '%d', // relevance_score
            '%d', // visibility_score
            '%f', // total_score
            '%s', // comments
            '%s'  // status
        ];
        
        $result = $wpdb->insert($table, $insert_data, $formats);
        
        if ($result === false) {
            MT_Logger::error('Database insert failed', [
                'error' => $wpdb->last_error,
                'query' => $wpdb->last_query
            ]);
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Safe update with validation
     */
    public static function update_evaluation($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_evaluations';
        
        // Build update data dynamically
        $update_data = [];
        $formats = [];
        
        // Only update provided fields
        if (isset($data['status'])) {
            $update_data['status'] = sanitize_text_field($data['status']);
            $formats[] = '%s';
        }
        
        if (isset($data['comments'])) {
            $update_data['comments'] = sanitize_textarea_field($data['comments']);
            $formats[] = '%s';
        }
        
        // Add updated timestamp
        $update_data['updated_at'] = current_time('mysql');
        $formats[] = '%s';
        
        $result = $wpdb->update(
            $table,
            $update_data,
            ['id' => $id],
            $formats,
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Prevent SQL injection in dynamic queries
     */
    public static function search_candidates($search_term, $category = null) {
        global $wpdb;
        
        // Sanitize search term
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        // Build query with proper escaping
        $query = "SELECT p.*, pm.meta_value as organization
                 FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                    AND pm.meta_key = '_mt_organization'
                 WHERE p.post_type = 'mt_candidate'
                 AND p.post_status = 'publish'
                 AND (p.post_title LIKE %s OR pm.meta_value LIKE %s)";
        
        $query_args = [$search_term, $search_term];
        
        // Add category filter if provided
        if ($category) {
            $query .= " AND EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm2 
                WHERE pm2.post_id = p.ID 
                AND pm2.meta_key = '_mt_category_type' 
                AND pm2.meta_value = %s
            )";
            $query_args[] = sanitize_text_field($category);
        }
        
        $query .= " ORDER BY p.post_title ASC LIMIT 100";
        
        // Use prepare with variable arguments
        $prepared_query = $wpdb->prepare($query, $query_args);
        
        return $wpdb->get_results($prepared_query);
    }
}
```

## File Upload Security

### Secure File Upload Handler

```php
<?php
class MT_File_Upload_Security {
    /**
     * Allowed file types for different contexts
     */
    private static $allowed_types = [
        'import' => ['csv', 'xlsx', 'xls'],
        'photo' => ['jpg', 'jpeg', 'png', 'webp'],
        'document' => ['pdf', 'doc', 'docx']
    ];
    
    /**
     * Validate and handle file upload
     */
    public static function handle_upload($file, $context = 'import') {
        // Check if upload error occurred
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', self::get_upload_error_message($file['error']));
        }
        
        // Validate file size (10MB max)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('File size exceeds 10MB limit', 'mobility-trailblazers'));
        }
        
        // Validate file extension
        $file_info = pathinfo($file['name']);
        $extension = strtolower($file_info['extension']);
        
        if (!in_array($extension, self::$allowed_types[$context])) {
            return new WP_Error('invalid_type', sprintf(
                __('File type %s not allowed for %s', 'mobility-trailblazers'),
                $extension,
                $context
            ));
        }
        
        // Validate MIME type
        $mime_type = self::get_mime_type($file['tmp_name']);
        if (!self::validate_mime_type($mime_type, $extension)) {
            return new WP_Error('mime_mismatch', __('File type does not match extension', 'mobility-trailblazers'));
        }
        
        // Check for malicious content
        if (!self::scan_file_content($file['tmp_name'], $context)) {
            return new WP_Error('malicious_content', __('File contains potentially malicious content', 'mobility-trailblazers'));
        }
        
        // Generate safe filename
        $safe_filename = self::generate_safe_filename($file['name']);
        
        // Move to upload directory
        $upload_dir = wp_upload_dir();
        $target_dir = $upload_dir['basedir'] . '/mt-uploads/' . $context . '/';
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            wp_mkdir_p($target_dir);
            
            // Add .htaccess to prevent direct execution
            file_put_contents($target_dir . '.htaccess', "Options -Indexes\n<FilesMatch '\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|sh|cgi)$'>\n    deny from all\n</FilesMatch>");
        }
        
        $target_file = $target_dir . $safe_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $target_file)) {
            return new WP_Error('move_failed', __('Failed to save uploaded file', 'mobility-trailblazers'));
        }
        
        // Set proper permissions
        chmod($target_file, 0644);
        
        // Log upload
        MT_Audit_Logger::log('file_uploaded', [
            'filename' => $safe_filename,
            'context' => $context,
            'size' => $file['size'],
            'user_id' => get_current_user_id()
        ]);
        
        return [
            'file' => $target_file,
            'url' => $upload_dir['baseurl'] . '/mt-uploads/' . $context . '/' . $safe_filename,
            'name' => $safe_filename,
            'size' => $file['size']
        ];
    }
    
    /**
     * Get real MIME type
     */
    private static function get_mime_type($file_path) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        return $mime_type;
    }
    
    /**
     * Validate MIME type matches extension
     */
    private static function validate_mime_type($mime_type, $extension) {
        $mime_map = [
            'csv' => ['text/csv', 'text/plain'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'xls' => ['application/vnd.ms-excel'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'pdf' => ['application/pdf']
        ];
        
        return isset($mime_map[$extension]) && in_array($mime_type, $mime_map[$extension]);
    }
    
    /**
     * Scan file for malicious content
     */
    private static function scan_file_content($file_path, $context) {
        $content = file_get_contents($file_path);
        
        // Check for PHP tags
        if (preg_match('/<\?php|<\?=/i', $content)) {
            return false;
        }
        
        // Check for JavaScript in CSV/Excel files
        if ($context === 'import') {
            if (preg_match('/<script|javascript:|onerror=/i', $content)) {
                return false;
            }
        }
        
        // Check for executable content
        $dangerous_patterns = [
            '/eval\s*\(/i',
            '/base64_decode/i',
            '/system\s*\(/i',
            '/exec\s*\(/i',
            '/passthru\s*\(/i',
            '/shell_exec\s*\(/i'
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Generate safe filename
     */
    private static function generate_safe_filename($filename) {
        $info = pathinfo($filename);
        $name = sanitize_file_name($info['filename']);
        $ext = strtolower($info['extension']);
        
        // Add timestamp to prevent conflicts
        $timestamp = time();
        $random = wp_rand(1000, 9999);
        
        return "{$name}_{$timestamp}_{$random}.{$ext}";
    }
}
```

## Session Management

### Session Security

```php
<?php
class MT_Session_Security {
    /**
     * Initialize secure session
     */
    public static function init_session() {
        if (!session_id()) {
            // Set secure session parameters
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => is_ssl(),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            session_start();
        }
        
        // Regenerate session ID on login
        if (!isset($_SESSION['mt_session_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['mt_session_initiated'] = true;
            $_SESSION['mt_session_ip'] = $_SERVER['REMOTE_ADDR'];
            $_SESSION['mt_session_ua'] = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // Validate session
        self::validate_session();
    }
    
    /**
     * Validate session integrity
     */
    private static function validate_session() {
        // Check IP address (optional, may cause issues with mobile users)
        if (isset($_SESSION['mt_session_ip']) && $_SESSION['mt_session_ip'] !== $_SERVER['REMOTE_ADDR']) {
            self::destroy_session();
            wp_die(__('Session security validation failed', 'mobility-trailblazers'));
        }
        
        // Check user agent
        if (isset($_SESSION['mt_session_ua']) && $_SESSION['mt_session_ua'] !== $_SERVER['HTTP_USER_AGENT']) {
            self::destroy_session();
            wp_die(__('Session security validation failed', 'mobility-trailblazers'));
        }
        
        // Check session timeout (30 minutes)
        if (isset($_SESSION['mt_last_activity']) && (time() - $_SESSION['mt_last_activity'] > 1800)) {
            self::destroy_session();
            wp_redirect(wp_login_url());
            exit;
        }
        
        $_SESSION['mt_last_activity'] = time();
    }
    
    /**
     * Destroy session securely
     */
    public static function destroy_session() {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
        }
        
        session_destroy();
    }
}
```

## Audit Logging

### Comprehensive Audit Logger

```php
<?php
class MT_Audit_Logger {
    /**
     * Log security-related events
     */
    public static function log($action, $data = []) {
        global $wpdb;
        $table = $wpdb->prefix . 'mt_audit_log';
        
        $log_data = [
            'user_id' => get_current_user_id(),
            'action' => sanitize_text_field($action),
            'object_type' => sanitize_text_field($data['object_type'] ?? 'system'),
            'object_id' => intval($data['object_id'] ?? 0),
            'details' => wp_json_encode($data),
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'created_at' => current_time('mysql')
        ];
        
        $wpdb->insert($table, $log_data);
    }
    
    /**
     * Log security events
     */
    public static function security_event($message, $context = []) {
        self::log('security_event', array_merge([
            'message' => $message,
            'severity' => 'warning'
        ], $context));
        
        // Also log to error log for monitoring
        error_log(sprintf(
            '[MT Security] %s - User: %d - IP: %s',
            $message,
            get_current_user_id(),
            $_SERVER['REMOTE_ADDR']
        ));
    }
    
    /**
     * Log failed login attempts
     */
    public static function log_failed_login($username) {
        self::log('failed_login', [
            'username' => $username,
            'object_type' => 'authentication',
            'severity' => 'warning'
        ]);
    }
    
    /**
     * Log privilege escalation attempts
     */
    public static function log_privilege_escalation($attempted_capability) {
        self::log('privilege_escalation_attempt', [
            'capability' => $attempted_capability,
            'object_type' => 'authorization',
            'severity' => 'critical'
        ]);
    }
}
```

## Security Checklist

### Development Phase
- [ ] Input validation on all user inputs
- [ ] Output escaping in all templates
- [ ] Prepared statements for all database queries
- [ ] Nonce verification on all forms and AJAX
- [ ] Capability checks before operations
- [ ] File upload validation and scanning
- [ ] Session security implementation
- [ ] Audit logging for sensitive operations

### Pre-Deployment
- [ ] Security code review
- [ ] Penetration testing
- [ ] SQL injection testing
- [ ] XSS vulnerability scan
- [ ] CSRF protection verification
- [ ] File permission review
- [ ] Error message review (no sensitive info)
- [ ] SSL/HTTPS enforcement

### Production
- [ ] Regular security updates
- [ ] Monitor audit logs
- [ ] Review failed login attempts
- [ ] Check for unusual activity patterns
- [ ] Regular backup verification
- [ ] Incident response plan
- [ ] Security patch management
- [ ] Regular security audits

### Security Headers
```php
// Add security headers
add_action('send_headers', function() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (is_ssl()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
});
```

---

**Next Document**: [05-plugin-structure.md](05-plugin-structure.md) - Complete plugin file structure and organization