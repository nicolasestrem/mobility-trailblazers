<?php
/**
 * Plugin Name: ALTERNATE Mobility Trailblazers Platform
 * Description: A custom plugin to manage the jury, voting, candidate workflow, and analytics for the 25 Mobility Trailblazers project.
 * Version: 1.0.1
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

        add_action('init', [$this, 'register_custom_post_types']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('rest_api_init', [$this, 'register_api_endpoints']);
        add_shortcode('mt_voting_form', [$this, 'render_voting_form']);
        add_action('wp_ajax_mt_auto_assign', [$this, 'auto_assign_candidates']);
        add_action('wp_ajax_mt_toggle_vote_status', [$this, 'toggle_vote_status']);
        add_action('admin_post_mt_import_csv', [$this, 'import_csv_candidates']);
        add_action('admin_post_mt_export_csv', [$this, 'export_votes_csv']);
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

    public function register_custom_post_types() {
        add_action('admin_menu', function () {
            register_post_type('mt_candidate', [
                'label' => 'Candidates',
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'mobility_trailblazers',
                'capability_type' => 'post',
                'capabilities' => [
                    'edit_post' => 'read',
                    'read_post' => 'read',
                    'delete_post' => 'delete_posts',
                    'edit_posts' => 'edit_posts',
                    'edit_others_posts' => 'edit_others_posts',
                    'publish_posts' => 'publish_posts',
                    'read_private_posts' => 'read_private_posts'
                ],
                'map_meta_cap' => true,
                'supports' => ['title', 'editor'],
                'menu_icon' => 'dashicons-awards',
            ]);
        }, 99);
    }

    public function admin_menu() {
        add_menu_page('Trailblazers', 'Trailblazers', 'manage_options', 'mobility_trailblazers', [$this, 'render_admin'], 'dashicons-awards');
        add_submenu_page('mobility_trailblazers', 'Assignments', 'Assignments', 'manage_options', 'mobility_assignments', [$this, 'render_assignments_page']);
        add_submenu_page('mobility_trailblazers', 'Votes', 'Votes', 'manage_options', 'mobility_votes', [$this, 'render_votes_page']);
    }

    public function render_admin() {
        echo '<div class="wrap"><h1>Mobility Trailblazers Dashboard</h1>
        <p>Use the sidebar to manage candidates, assignments, and votes.</p>

        <h2>Import Candidates (CSV)</h2>
        <form method="post" enctype="multipart/form-data" action="' . admin_url('admin-post.php') . '">
            <input type="hidden" name="action" value="mt_import_csv">
            <input type="file" name="csv" accept=".csv" required />
            <input type="submit" value="Import CSV" class="button button-primary" />
        </form>

        <h2>Export Votes to CSV</h2>
        <form method="post" action="' . admin_url('admin-post.php') . '">
            <input type="hidden" name="action" value="mt_export_csv">
            <input type="submit" value="Export Votes" class="button" />
        </form></div>';
    }

    public function render_assignments_page() {
        global $wpdb;
        $round = isset($_GET['round']) ? intval($_GET['round']) : 1;
        $results = $wpdb->get_results("SELECT u.display_name, c.name AS candidate_name, a.round FROM {$this->assignments_table} a JOIN {$wpdb->users} u ON a.jury_id = u.ID JOIN {$this->candidates_table} c ON a.candidate_id = c.id WHERE a.round = $round ORDER BY u.display_name");

        echo '<div class="wrap"><h1>Assignment Management</h1>
        <label for="assignment-round">Select Round:</label>
        <select id="assignment-round">
            <option value="1"' . ($round === 1 ? ' selected' : '') . '>Round 1</option>
            <option value="2"' . ($round === 2 ? ' selected' : '') . '>Round 2</option>
        </select>
        <button id="auto-assign">Auto Assign Candidates</button>
        <h2>Current Assignments (Round ' . $round . ')</h2>
        <table class="widefat">
        <thead><tr><th>Jury Member</th><th>Candidate</th><th>Round</th></tr></thead><tbody>';

        foreach ($results as $row) {
            echo "<tr><td>{$row->display_name}</td><td>{$row->candidate_name}</td><td>{$row->round}</td></tr>";
        }

        echo '</tbody></table>
        <script>
        document.getElementById("auto-assign").addEventListener("click", async () => {
            const round = document.getElementById("assignment-round").value;
            const res = await fetch(`/wp-admin/admin-ajax.php?action=mt_auto_assign&round=${round}`);
            const json = await res.json();
            alert(json.message);
            location.reload();
        });
        document.getElementById("assignment-round").addEventListener("change", function() {
            const round = this.value;
            window.location = `?page=mobility_assignments&round=${round}`;
        });
        </script></div>';
    }

    public function render_votes_page() {
        global $wpdb;
        $votes = $wpdb->get_results("SELECT v.*, c.name AS candidate_name, u.display_name FROM {$this->votes_table} v JOIN {$this->candidates_table} c ON v.candidate_id = c.id JOIN {$wpdb->users} u ON v.jury_id = u.ID ORDER BY v.updated_at DESC LIMIT 100");

        echo '<div class="wrap"><h1>Votes Overview</h1><table class="widefat"><thead><tr><th>Jury</th><th>Candidate</th><th>Scores</th><th>Status</th><th>Actions</th></tr></thead><tbody>';

        foreach ($votes as $vote) {
            echo '<tr><td>' . esc_html($vote->display_name) . '</td><td>' . esc_html($vote->candidate_name) . '</td><td>' .
                "PS: $vote->pioneer_spirit | ID: $vote->innovation_degree | IP: $vote->implementation_power | RM: $vote->role_model_function" . '</td><td>' . $vote->status . '</td>';
            echo '<td><button class="toggle-status" data-id="' . $vote->id . '">Toggle Status</button></td></tr>';
        }

        echo '</tbody></table><script>
        document.querySelectorAll(".toggle-status").forEach(btn => {
            btn.addEventListener("click", async () => {
                const id = btn.getAttribute("data-id");
                const res = await fetch(`/wp-admin/admin-ajax.php?action=mt_toggle_vote_status&id=${id}`);
                const json = await res.json();
                alert(json.message);
                location.reload();
            });
        });
        </script></div>';
    }

    public function toggle_vote_status() {
        global $wpdb;
        $id = intval($_GET['id']);
        $current = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$this->votes_table} WHERE id = %d", $id));
        $new = $current === 'final' ? 'draft' : 'final';
        $wpdb->update($this->votes_table, ['status' => $new], ['id' => $id]);
        wp_send_json(['message' => "Status updated to $new"]);
    }

    public function import_csv_candidates() {
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            wp_die('Upload failed.');
        }
        $csv = fopen($_FILES['csv']['tmp_name'], 'r');
        global $wpdb;
        while (($row = fgetcsv($csv)) !== false) {
            $wpdb->insert($this->candidates_table, [
                'name' => sanitize_text_field($row[0]),
                'category' => sanitize_text_field($row[1]),
                'organization' => sanitize_text_field($row[2]),
                'profile' => sanitize_textarea_field($row[3])
            ]);
        }
        fclose($csv);
        wp_redirect(admin_url('admin.php?page=mobility_trailblazers'));
        exit;
    }

    public function export_votes_csv() {
        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->votes_table}", ARRAY_A);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=votes_export.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, array_keys($rows[0]));
        foreach ($rows as $row) fputcsv($out, $row);
        fclose($out);
        exit;
    }

    public function register_api_endpoints() {
        register_rest_route('mobility/v1', '/candidates', [
            'methods' => 'GET',
            'callback' => [$this, 'get_candidates'],
            'permission_callback' => '__return_true'
        ]);

        register_rest_route('mobility/v1', '/vote', [
            'methods' => 'POST',
            'callback' => [$this, 'submit_vote'],
            'permission_callback' => function () {
                return current_user_can('read');
            }
        ]);
    }

    public function get_candidates() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->candidates_table}");
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

    public function auto_assign_candidates() {
        global $wpdb;
        $round = isset($_GET['round']) ? intval($_GET['round']) : 1;
        $jury_members = get_users(['role' => 'jury']);
        $candidates = $wpdb->get_results("SELECT id FROM {$this->candidates_table} ORDER BY RAND()");

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

    public function render_voting_form() {
        ob_start(); ?>
        <form id="mt-vote-form">
            <label>Pioneer Spirit: <input type="range" name="pioneer_spirit" min="1" max="10" /></label><br>
            <label>Innovation Degree: <input type="range" name="innovation_degree" min="1" max="10" /></label><br>
            <label>Implementation Power: <input type="range" name="implementation_power" min="1" max="10" /></label><br>
            <label>Role Model Function: <input type="range" name="role_model_function" min="1" max="10" /></label><br>
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
}

MobilityTrailblazers::get_instance();
