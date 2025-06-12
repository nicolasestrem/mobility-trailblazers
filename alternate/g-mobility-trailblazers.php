<?php
/**
 * Plugin Name: ALTERNATE Mobility Trailblazers Platform
 * Description: A custom plugin to manage the jury, voting, candidate workflow, and analytics for the 25 Mobility Trailblazers project.
 * Version: 1.0.5
 * Author: Nicolas Estrem
 */

if (!defined('ABSPATH')) exit;

class MobilityTrailblazers {
    private static $instance = null;
    private $votes_table;
    private $assignments_table;
    private $candidates_table;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $this->votes_table = $prefix . 'mt_votes';
        $this->assignments_table = $prefix . 'mt_jury_assignments';
        $this->candidates_table = $prefix . 'mt_candidates';

        register_activation_hook(__FILE__, [$this, 'install']);

        add_action('init', [$this, 'ensure_roles_exist']);
        add_action('init', [$this, 'register_custom_post_types']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
        add_action('init', [$this, 'migrate_old_post_types']);
    }

    public function ensure_roles_exist() {
        if (!get_role('jury')) {
            add_role('jury', 'Jury', ['read' => true]);
        }
        if (!get_role('candidate')) {
            add_role('candidate', 'Candidate', ['read' => true]);
        }
    }

    public function register_custom_post_types() {
        register_post_type('mt_candidate', [
            'label' => 'Candidates',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'mobility_trailblazers',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'supports' => ['title', 'editor', 'custom-fields'],
            'menu_position' => 5,
        ]);

        register_post_type('mt_jury', [
            'label' => 'Jury Members',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'mobility_trailblazers',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'supports' => ['title', 'custom-fields'],
            'menu_position' => 6,
        ]);
    }

    public function migrate_old_post_types() {
        global $wpdb;
        if (!get_option('mt_candidates_migrated')) {
            $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'mt_candidate' WHERE post_type = 'trailblazer_candidate'");
            update_option('mt_candidates_migrated', true);
        }
    }

    public function admin_menu() {
        add_menu_page('Trailblazers', 'Trailblazers', 'manage_options', 'mobility_trailblazers', [$this, 'render_dashboard_page'], 'dashicons-awards');
        add_submenu_page('mobility_trailblazers', 'Dashboard', 'Dashboard', 'manage_options', 'mobility_trailblazers', [$this, 'render_dashboard_page']);
    }

    public function render_dashboard_page() {
        global $wpdb;

        $total_candidates = $wpdb->get_var("SELECT COUNT(*) FROM {$this->candidates_table} WHERE name IS NOT NULL AND name != ''");
        $jury_count = count(get_users(['role' => 'jury']));

        $categories = $wpdb->get_results("SELECT category, COUNT(*) as count FROM {$this->candidates_table} WHERE category IS NOT NULL AND category != '' GROUP BY category HAVING count > 0");

        echo '<div class="wrap"><h1>Mobility Trailblazers Dashboard</h1>';
        echo "<h2>Total Candidates</h2><p style='font-size:24px; color:#0073aa;'>$total_candidates</p>";
        echo "<h2>Jury Members</h2><p style='font-size:24px; color:#00aa55;'>$jury_count</p>";

        echo '<h2>Candidates by Category</h2><table class="widefat"><thead><tr><th>Category</th><th>Count</th></tr></thead><tbody>';
        foreach ($categories as $cat) {
            echo "<tr><td>" . esc_html($cat->category) . "</td><td>{$cat->count}</td></tr>";
        }
        echo '</tbody></table>';

        echo '<hr><h2>Usage Examples</h2><p>You can use these shortcodes to embed platform elements:</p><ul style="background:#fff;padding:1em;border:1px solid #ccc;">
        <li><code>[mt_candidate_grid]</code> - Display candidates in a grid layout</li>
        <li><code>[mt_voting_interface]</code> - Jury voting interface (jury members only)</li>
        <li><code>[mt_jury_dashboard]</code> - Jury member dashboard (jury members only)</li>
        <li><code>[mt_voting_progress]</code> - Display voting progress and stats</li>
        </ul>';
        echo '</div>';
    }

    public function install() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql1 = "CREATE TABLE {$this->candidates_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            category VARCHAR(100),
            organization VARCHAR(255),
            profile TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        $sql2 = "CREATE TABLE {$this->assignments_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            jury_id BIGINT UNSIGNED,
            candidate_id BIGINT UNSIGNED,
            round INT,
            assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        $sql3 = "CREATE TABLE {$this->votes_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            jury_id BIGINT UNSIGNED,
            candidate_id BIGINT UNSIGNED,
            pioneer_spirit TINYINT,
            innovation_degree TINYINT,
            implementation_power TINYINT,
            role_model_function TINYINT,
            round INT,
            status ENUM('draft','submitted','final') DEFAULT 'draft',
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) $charset_collate;";

        dbDelta([$sql1, $sql2, $sql3]);
    }

    public function register_api_endpoints() {
        // You can add your REST endpoints here later if needed
    }
}

add_action('plugins_loaded', function () {
    MobilityTrailblazers::get_instance();
});
