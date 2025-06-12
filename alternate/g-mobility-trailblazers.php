<?php
/**
 * Plugin Name: ALTERNATE Mobility Trailblazers Platform
 * Description: A custom plugin to manage the jury, voting, candidate workflow, and analytics for the 25 Mobility Trailblazers project.
 * Version: 1.1.0
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

        add_action('wp_ajax_mt_auto_assign', [$this, 'auto_assign_candidates']);
        add_action('wp_ajax_mt_preview_assignments', [$this, 'preview_auto_assignment']);
        add_action('wp_ajax_mt_cleanup_invalids', [$this, 'cleanup_invalid_candidates']);
        add_shortcode('mt_voting_interface', [$this, 'render_voting_form']);
        add_shortcode('mt_candidate_grid', [$this, 'shortcode_candidate_grid']);
        add_shortcode('mt_jury_dashboard', [$this, 'shortcode_jury_dashboard']);
        add_shortcode('mt_voting_progress', [$this, 'shortcode_voting_progress']);
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
        add_submenu_page('mobility_trailblazers', 'Assignments', 'Assignments', 'manage_options', 'mobility_assignments', [$this, 'render_assignments_page']);
        add_submenu_page('mobility_trailblazers', 'Votes', 'Votes', 'manage_options', 'mobility_votes', [$this, 'render_votes_page']);
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

        echo '<hr><h2>Preview Assignment</h2><button id="preview-assignment" class="button">Preview</button><div id="preview-output"></div>';

        echo '<hr><h2>Usage Examples</h2><ul style="background:#fff;padding:1em;border:1px solid #ccc;">
        <li><code>[mt_candidate_grid]</code> - Display candidates in a grid layout</li>
        <li><code>[mt_voting_interface]</code> - Jury voting interface (jury members only)</li>
        <li><code>[mt_jury_dashboard]</code> - Jury member dashboard (jury members only)</li>
        <li><code>[mt_voting_progress]</code> - Display voting progress and stats</li>
        </ul>';

        echo '<script>
        document.getElementById("preview-assignment").addEventListener("click", async () => {
            const res = await fetch("/wp-admin/admin-ajax.php?action=mt_preview_assignments");
            const json = await res.json();
            document.getElementById("preview-output").innerHTML = `<pre>${JSON.stringify(json.preview, null, 2)}</pre>`;
        });
        </script>';
        echo '</div>';
    }

    public function render_assignments_page() {
        global $wpdb;
        echo '<div class="wrap"><h1>Jury Assignments</h1>';
        
        $assignments = $wpdb->get_results("
            SELECT a.*, c.name as candidate_name, u.display_name as jury_name 
            FROM {$this->assignments_table} a 
            JOIN {$this->candidates_table} c ON a.candidate_id = c.id 
            JOIN {$wpdb->users} u ON a.jury_id = u.ID 
            ORDER BY a.round DESC, u.display_name
        ");

        echo '<table class="widefat"><thead><tr>
            <th>Jury Member</th>
            <th>Candidate</th>
            <th>Round</th>
            <th>Assigned At</th>
        </tr></thead><tbody>';

        foreach ($assignments as $row) {
            echo "<tr>
                <td>" . esc_html($row->jury_name) . "</td>
                <td>" . esc_html($row->candidate_name) . "</td>
                <td>" . esc_html($row->round) . "</td>
                <td>" . esc_html($row->assigned_at) . "</td>
            </tr>";
        }
        echo '</tbody></table></div>';
    }

    public function render_votes_page() {
        global $wpdb;
        echo '<div class="wrap"><h1>Voting Results</h1>';
        
        $votes = $wpdb->get_results("
            SELECT v.*, c.name as candidate_name, u.display_name as jury_name 
            FROM {$this->votes_table} v 
            JOIN {$this->candidates_table} c ON v.candidate_id = c.id 
            JOIN {$wpdb->users} u ON v.jury_id = u.ID 
            ORDER BY v.round DESC, v.status, c.name
        ");

        echo '<table class="widefat"><thead><tr>
            <th>Jury Member</th>
            <th>Candidate</th>
            <th>Round</th>
            <th>Pioneer Spirit</th>
            <th>Innovation</th>
            <th>Implementation</th>
            <th>Role Model</th>
            <th>Status</th>
            <th>Updated</th>
        </tr></thead><tbody>';

        foreach ($votes as $row) {
            echo "<tr>
                <td>" . esc_html($row->jury_name) . "</td>
                <td>" . esc_html($row->candidate_name) . "</td>
                <td>" . esc_html($row->round) . "</td>
                <td>" . esc_html($row->pioneer_spirit) . "</td>
                <td>" . esc_html($row->innovation_degree) . "</td>
                <td>" . esc_html($row->implementation_power) . "</td>
                <td>" . esc_html($row->role_model_function) . "</td>
                <td>" . esc_html($row->status) . "</td>
                <td>" . esc_html($row->updated_at) . "</td>
            </tr>";
        }
        echo '</tbody></table></div>';
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

    public function auto_assign_candidates() {
        global $wpdb;
        $round = isset($_GET['round']) ? intval($_GET['round']) : 1;
        $jury_members = get_users(['role' => 'jury']);
        $candidates = $wpdb->get_results("SELECT id FROM {$this->candidates_table} WHERE name IS NOT NULL AND name != '' ORDER BY RAND()");

        $assignments = [];
        $candidate_index = 0;
        $candidates_per_jury = 10;

        foreach ($jury_members as $jury) {
            $already = $wpdb->get_col($wpdb->prepare(
                "SELECT candidate_id FROM {$this->assignments_table} WHERE jury_id = %d AND round = %d",
                $jury->ID, $round
            ));

            $assigned_count = 0;
            while ($assigned_count < $candidates_per_jury && isset($candidates[$candidate_index])) {
                $candidate_id = $candidates[$candidate_index]->id;
                if (!in_array($candidate_id, $already)) {
                    $assignments[] = $wpdb->prepare("(%d, %d, %d)", $jury->ID, $candidate_id, $round);
                    $assigned_count++;
                }
                $candidate_index++;
            }
        }

        if (!empty($assignments)) {
            $wpdb->query("INSERT INTO {$this->assignments_table} (jury_id, candidate_id, round) VALUES " . implode(",", $assignments));
        }

        wp_send_json(['message' => 'Auto-assignment complete for round ' . $round]);
    }

    public function preview_auto_assignment() {
        global $wpdb;
        $jury_members = get_users(['role' => 'jury']);
        $candidates = $wpdb->get_results("SELECT id, name FROM {$this->candidates_table} WHERE name IS NOT NULL AND name != '' ORDER BY RAND()");

        $candidate_index = 0;
        $candidates_per_jury = 10;
        $preview = [];

        foreach ($jury_members as $jury) {
            $assigned = [];
            for ($i = 0; $i < $candidates_per_jury && isset($candidates[$candidate_index]); $i++) {
                $assigned[] = $candidates[$candidate_index]->name;
                $candidate_index++;
            }
            $preview[$jury->display_name] = $assigned;
        }

        wp_send_json(['preview' => $preview]);
    }

    public function cleanup_invalid_candidates() {
        global $wpdb;
        $wpdb->query("DELETE FROM {$this->candidates_table} WHERE name IS NULL OR TRIM(name) = ''");
        wp_send_json(['status' => 'ok']);
    }

    public function render_voting_form() {
        ob_start(); ?>
        <form id="mt-vote-form">
            <label>Pioneer Spirit: <input type="range" name="pioneer_spirit" min="1" max="10" oninput="this.nextElementSibling.value = this.value"><output>5</output></label><br>
            <label>Innovation Degree: <input type="range" name="innovation_degree" min="1" max="10" oninput="this.nextElementSibling.value = this.value"><output>5</output></label><br>
            <label>Implementation Power: <input type="range" name="implementation_power" min="1" max="10" oninput="this.nextElementSibling.value = this.value"><output>5</output></label><br>
            <label>Role Model Function: <input type="range" name="role_model_function" min="1" max="10" oninput="this.nextElementSibling.value = this.value"><output>5</output></label><br>
            <input type="hidden" name="candidate_id" value="1">
            <input type="hidden" name="round" value="1">
            <input type="hidden" name="status" value="submitted">
            <button type="submit">Submit Vote</button>
        </form>
        <script>
        document.getElementById('mt-vote-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = e.target;
            const data = Object.fromEntries(new FormData(form));
            const res = await fetch('/wp-json/mobility/v1/vote', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await res.json();
            alert('Vote submitted');
        });
        </script>
        <?php return ob_get_clean();
    }

    public function shortcode_candidate_grid() {
        $candidates = get_posts(['post_type' => 'mt_candidate', 'numberposts' => -1]);
        $out = '<div class="candidate-grid" style="display:flex;flex-wrap:wrap;gap:1em">';
        foreach ($candidates as $c) {
            $out .= '<div style="border:1px solid #ccc;padding:1em;width:200px">';
            $out .= '<h3>' . esc_html($c->post_title) . '</h3>';
            $out .= '<p>' . esc_html(get_post_field('post_content', $c->ID)) . '</p>';
            $out .= '</div>';
        }
        $out .= '</div>';
        return $out;
    }

    public function shortcode_jury_dashboard() {
        if (!current_user_can('jury')) return '<p>You do not have permission to view this.</p>';
        global $wpdb;
        $jury_id = get_current_user_id();
        $assignments = $wpdb->get_results($wpdb->prepare("SELECT c.name FROM {$this->assignments_table} a JOIN {$this->candidates_table} c ON a.candidate_id = c.id WHERE a.jury_id = %d", $jury_id));
        if (!$assignments) return '<p>No candidates assigned.</p>';
        $out = '<ul>';
        foreach ($assignments as $row) {
            $out .= '<li>' . esc_html($row->name) . '</li>';
        }
        $out .= '</ul>';
        return $out;
    }

    public function shortcode_voting_progress() {
        global $wpdb;
        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->votes_table}");
        $finalized = $wpdb->get_var("SELECT COUNT(*) FROM {$this->votes_table} WHERE status = 'final'");
        return "<p>Votes submitted: $total<br>Finalized: $finalized</p>";
    }

    public function register_api_endpoints() {
        register_rest_route('mobility/v1', '/vote', [
            'methods' => 'POST',
            'callback' => [$this, 'submit_vote'],
            'permission_callback' => function () {
                return current_user_can('read');
            }
        ]);
    }

    public function submit_vote($request) {
        global $wpdb;
        $data = $request->get_json_params();

        $wpdb->insert($this->votes_table, [
            'jury_id' => get_current_user_id(),
            'candidate_id' => intval($data['candidate_id']),
            'pioneer_spirit' => intval($data['pioneer_spirit']),
            'innovation_degree' => intval($data['innovation_degree']),
            'implementation_power' => intval($data['implementation_power']),
            'role_model_function' => intval($data['role_model_function']),
            'round' => intval($data['round']),
            'status' => sanitize_text_field($data['status'])
        ]);

        return ['status' => 'success'];
    }
}

add_action('plugins_loaded', function () {
    MobilityTrailblazers::get_instance();
});
