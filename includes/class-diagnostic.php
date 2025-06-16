<?php
namespace MobilityTrailblazers;

class Diagnostic {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Initialize diagnostic functionality
        add_action('admin_menu', array($this, 'add_diagnostic_menu'));
        add_action('admin_init', array($this, 'register_diagnostic_settings'));
    }

    public function add_diagnostic_menu() {
        add_menu_page(
            'Mobility Trailblazers Diagnostic',
            'MT Diagnostic',
            'manage_options',
            'mt-diagnostic',
            array($this, 'render_diagnostic_page'),
            'dashicons-search',
            100
        );
    }

    public function register_diagnostic_settings() {
        // Register diagnostic settings
    }

    public function render_diagnostic_page() {
        // Render diagnostic page
        echo '<div class="wrap">';
        echo '<h1>Mobility Trailblazers Diagnostic</h1>';
        echo '<p>This page provides diagnostic information about the Mobility Trailblazers plugin installation and configuration.</p>';
        
        // Add some basic styling
        echo '<style>
            .mt-diagnostic-section { margin-bottom: 30px; }
            .mt-diagnostic-section h2 { border-bottom: 1px solid #ccc; padding-bottom: 10px; }
            .mt-diagnostic-section table { margin-top: 15px; }
            .mt-diagnostic-section .widefat th { background-color: #f1f1f1; }
            .mt-diagnostic-section .widefat td { padding: 8px 10px; }
        </style>';
        
        echo '<div id="mt-diagnostic-content">';
        $this->run_diagnostics();
        echo '</div>';
        echo '</div>';
    }

    private function run_diagnostics() {
        // Run diagnostic checks
        echo '<div class="mt-diagnostic-section">';
        $this->check_database_tables();
        echo '</div>';
        
        echo '<div class="mt-diagnostic-section">';
        $this->check_file_permissions();
        echo '</div>';
        
        echo '<div class="mt-diagnostic-section">';
        $this->check_plugin_dependencies();
        echo '</div>';
        
        echo '<div class="mt-diagnostic-section">';
        $this->check_plugin_configuration();
        echo '</div>';
        
        echo '<div class="mt-diagnostic-section">';
        $this->check_system_info();
        echo '</div>';
        
        echo '<div class="mt-diagnostic-section">';
        $this->check_jury_sync();
        echo '</div>';
    }

    private function check_database_tables() {
        global $wpdb;
        
        echo '<h2>Database Tables</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Table Name</th><th>Status</th><th>Records</th></tr></thead>';
        echo '<tbody>';
        
        $tables = array(
            'mt_votes' => 'Votes',
            'mt_vote_backups' => 'Vote Backups',
            'vote_reset_logs' => 'Vote Reset Logs',
            'mt_vote_audit_log' => 'Vote Audit Log',
            'mt_candidate_scores' => 'Candidate Scores'
        );
        
        foreach ($tables as $table_suffix => $table_label) {
            $table_name = $wpdb->prefix . $table_suffix;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
            
            if ($table_exists) {
                $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                echo '<tr>';
                echo '<td>' . esc_html($table_label) . ' (' . esc_html($table_name) . ')</td>';
                echo '<td><span style="color: green;">✓ Exists</span></td>';
                echo '<td>' . esc_html($count) . ' records</td>';
                echo '</tr>';
            } else {
                echo '<tr>';
                echo '<td>' . esc_html($table_label) . ' (' . esc_html($table_name) . ')</td>';
                echo '<td><span style="color: red;">✗ Missing</span></td>';
                echo '<td>-</td>';
                echo '</tr>';
            }
        }
        
        echo '</tbody></table>';
    }

    private function check_file_permissions() {
        echo '<h2>File Permissions</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>File/Directory</th><th>Status</th><th>Permissions</th></tr></thead>';
        echo '<tbody>';
        
        $paths = array(
            MT_PLUGIN_PATH => 'Plugin Directory',
            MT_PLUGIN_PATH . 'assets/' => 'Assets Directory',
            MT_PLUGIN_PATH . 'assets/css/' => 'CSS Directory',
            MT_PLUGIN_PATH . 'assets/js/' => 'JS Directory',
            MT_PLUGIN_PATH . 'templates/' => 'Templates Directory',
            MT_PLUGIN_PATH . 'includes/' => 'Includes Directory'
        );
        
        foreach ($paths as $path => $label) {
            if (file_exists($path)) {
                $perms = substr(sprintf('%o', fileperms($path)), -4);
                $readable = is_readable($path);
                $writable = is_writable($path);
                
                echo '<tr>';
                echo '<td>' . esc_html($label) . '</td>';
                
                if ($readable && $writable) {
                    echo '<td><span style="color: green;">✓ OK</span></td>';
                } elseif ($readable) {
                    echo '<td><span style="color: orange;">⚠ Read Only</span></td>';
                } else {
                    echo '<td><span style="color: red;">✗ No Access</span></td>';
                }
                
                echo '<td>' . esc_html($perms) . '</td>';
                echo '</tr>';
            } else {
                echo '<tr>';
                echo '<td>' . esc_html($label) . '</td>';
                echo '<td><span style="color: red;">✗ Missing</span></td>';
                echo '<td>-</td>';
                echo '</tr>';
            }
        }
        
        echo '</tbody></table>';
    }

    private function check_plugin_dependencies() {
        echo '<h2>Plugin Dependencies</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Dependency</th><th>Status</th><th>Version</th></tr></thead>';
        echo '<tbody>';
        
        // Check WordPress version
        $wp_version = get_bloginfo('version');
        $min_wp_version = '5.0';
        echo '<tr>';
        echo '<td>WordPress</td>';
        if (version_compare($wp_version, $min_wp_version, '>=')) {
            echo '<td><span style="color: green;">✓ Compatible</span></td>';
        } else {
            echo '<td><span style="color: red;">✗ Too Old</span></td>';
        }
        echo '<td>' . esc_html($wp_version) . ' (min: ' . esc_html($min_wp_version) . ')</td>';
        echo '</tr>';
        
        // Check PHP version
        $php_version = PHP_VERSION;
        $min_php_version = '7.4';
        echo '<tr>';
        echo '<td>PHP</td>';
        if (version_compare($php_version, $min_php_version, '>=')) {
            echo '<td><span style="color: green;">✓ Compatible</span></td>';
        } else {
            echo '<td><span style="color: red;">✗ Too Old</span></td>';
        }
        echo '<td>' . esc_html($php_version) . ' (min: ' . esc_html($min_php_version) . ')</td>';
        echo '</tr>';
        
        // Check MySQL version
        global $wpdb;
        $mysql_version = $wpdb->get_var("SELECT VERSION()");
        echo '<tr>';
        echo '<td>MySQL</td>';
        echo '<td><span style="color: green;">✓ Available</span></td>';
        echo '<td>' . esc_html($mysql_version) . '</td>';
        echo '</tr>';
        
        // Check required PHP extensions
        $extensions = array('json', 'mysqli', 'curl');
        foreach ($extensions as $ext) {
            echo '<tr>';
            echo '<td>PHP Extension: ' . esc_html($ext) . '</td>';
            if (extension_loaded($ext)) {
                echo '<td><span style="color: green;">✓ Loaded</span></td>';
                echo '<td>Available</td>';
            } else {
                echo '<td><span style="color: red;">✗ Missing</span></td>';
                echo '<td>Not Available</td>';
            }
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function check_plugin_configuration() {
        echo '<h2>Plugin Configuration</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Setting</th><th>Value</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        
        // Check plugin version
        echo '<tr>';
        echo '<td>Plugin Version</td>';
        echo '<td>' . esc_html(MT_PLUGIN_VERSION) . '</td>';
        echo '<td><span style="color: green;">✓ OK</span></td>';
        echo '</tr>';
        
        // Check database version
        $db_version = get_option('mt_db_version', '1.0.0');
        echo '<tr>';
        echo '<td>Database Version</td>';
        echo '<td>' . esc_html($db_version) . '</td>';
        if (version_compare($db_version, MT_PLUGIN_VERSION, '>=')) {
            echo '<td><span style="color: green;">✓ Up to Date</span></td>';
        } else {
            echo '<td><span style="color: orange;">⚠ Needs Update</span></td>';
        }
        echo '</tr>';
        
        // Check post types
        $post_types = array('mt_candidate', 'mt_jury');
        foreach ($post_types as $post_type) {
            echo '<tr>';
            echo '<td>Post Type: ' . esc_html($post_type) . '</td>';
            if (post_type_exists($post_type)) {
                $count = wp_count_posts($post_type);
                echo '<td>' . esc_html($count->publish) . ' published posts</td>';
                echo '<td><span style="color: green;">✓ Registered</span></td>';
            } else {
                echo '<td>Not registered</td>';
                echo '<td><span style="color: red;">✗ Missing</span></td>';
            }
            echo '</tr>';
        }
        
        // Check taxonomies
        $taxonomies = array('mt_category', 'mt_phase', 'mt_status', 'mt_award_year');
        foreach ($taxonomies as $taxonomy) {
            echo '<tr>';
            echo '<td>Taxonomy: ' . esc_html($taxonomy) . '</td>';
            if (taxonomy_exists($taxonomy)) {
                $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => false));
                $term_count = is_wp_error($terms) ? 0 : count($terms);
                echo '<td>' . esc_html($term_count) . ' terms</td>';
                echo '<td><span style="color: green;">✓ Registered</span></td>';
            } else {
                echo '<td>Not registered</td>';
                echo '<td><span style="color: red;">✗ Missing</span></td>';
            }
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    private function check_system_info() {
        echo '<h2>System Information</h2>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Property</th><th>Value</th></tr></thead>';
        echo '<tbody>';
        
        $info = array(
            'Site URL' => get_site_url(),
            'Home URL' => get_home_url(),
            'WordPress Debug' => WP_DEBUG ? 'Enabled' : 'Disabled',
            'WordPress Debug Log' => WP_DEBUG_LOG ? 'Enabled' : 'Disabled',
            'Memory Limit' => ini_get('memory_limit'),
            'Max Execution Time' => ini_get('max_execution_time') . ' seconds',
            'Upload Max Filesize' => ini_get('upload_max_filesize'),
            'Post Max Size' => ini_get('post_max_size'),
            'Plugin Path' => MT_PLUGIN_PATH,
            'Plugin URL' => MT_PLUGIN_URL,
            'Active Theme' => wp_get_theme()->get('Name'),
            'Active Plugins' => count(get_option('active_plugins', array()))
        );
        
        foreach ($info as $label => $value) {
            echo '<tr>';
            echo '<td>' . esc_html($label) . '</td>';
            echo '<td>' . esc_html($value) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    /**
     * Check jury synchronization status
     */
    private function check_jury_sync() {
        if (class_exists('\MobilityTrailblazers\JurySync')) {
            $jury_sync = \MobilityTrailblazers\JurySync::get_instance();
            $jury_sync->check_jury_sync_status();
        } else {
            echo '<h2>Jury Synchronization Status</h2>';
            echo '<p><span style="color: red;">✗ JurySync class not available</span></p>';
        }
    }
} 