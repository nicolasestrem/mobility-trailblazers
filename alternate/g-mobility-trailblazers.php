<?php
/**
 * Plugin Name: ALTERNATE Mobility Trailblazers Platform
 * Description: A custom plugin to manage the jury, voting, candidate workflow, and analytics for the 25 Mobility Trailblazers project.
 * Version: 1.0.2
 * Author: Nico
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
        add_shortcode('mt_voting_form', [$this, 'render_voting_form']);
        add_action('wp_ajax_mt_auto_assign', [$this, 'auto_assign_candidates']);
        add_action('wp_ajax_mt_preview_assign', [$this, 'preview_auto_assignment']);
        add_action('wp_ajax_mt_toggle_vote_status', [$this, 'toggle_vote_status']);
        add_action('admin_post_mt_import_csv', [$this, 'import_csv_candidates']);
        add_action('admin_post_mt_export_csv', [$this, 'export_votes_csv']);
        add_action('admin_post_mt_cleanup_invalid_candidates', [$this, 'cleanup_invalid_candidates']);

        add_action('init', function () {
            global $wpdb;
            if (!get_option('mt_candidates_migrated')) {
                $wpdb->query("UPDATE {$wpdb->posts} SET post_type = 'mt_candidate' WHERE post_type = 'trailblazer_candidate'");
                update_option('mt_candidates_migrated', true);
            }
        });
    }

    public function admin_menu() {
        add_menu_page('Trailblazers', 'Trailblazers', 'manage_options', 'mobility_trailblazers', [$this, 'render_admin_page'], 'dashicons-awards');
    }

    public function render_admin_page() {
        echo '<div class="wrap"><h1>Mobility Trailblazers Admin Tools</h1>';

        echo '<h2>Preview Assignment</h2>
        <form onsubmit="event.preventDefault(); fetchPreview()">
            <label>Round: <input type="number" name="round" id="preview-round" value="1"></label>
            <label>Category: <input type="text" name="category" id="preview-category"></label>
            <button type="submit" class="button">Preview Candidates</button>
        </form>
        <pre id="preview-output"></pre>';

        echo '<h2>Run Assignment</h2>
        <form onsubmit="event.preventDefault(); runAssignment()">
            <label>Round: <input type="number" name="round" id="assign-round" value="1"></label>
            <label>Category: <input type="text" name="category" id="assign-category"></label>
            <button type="submit" class="button button-primary">Auto-Assign Candidates</button>
        </form>
        <div id="assign-message"></div>';

        echo '<hr><h2>Cleanup Invalid Candidates</h2>
        <form method="post" action="' . admin_url('admin-post.php') . '">
            <input type="hidden" name="action" value="mt_cleanup_invalid_candidates">
            <button type="submit" class="button button-danger">Purge Empty Candidate Entries</button>
        </form>';

        echo '<script>
        async function fetchPreview() {
            const round = document.getElementById("preview-round").value;
            const category = document.getElementById("preview-category").value;
            const res = await fetch(`/wp-admin/admin-ajax.php?action=mt_preview_assign&round=${round}&category=${encodeURIComponent(category)}`);
            const data = await res.json();
            document.getElementById("preview-output").textContent = JSON.stringify(data, null, 2);
        }

        async function runAssignment() {
            const round = document.getElementById("assign-round").value;
            const category = document.getElementById("assign-category").value;
            const res = await fetch(`/wp-admin/admin-ajax.php?action=mt_auto_assign&round=${round}&category=${encodeURIComponent(category)}`);
            const data = await res.json();
            document.getElementById("assign-message").textContent = data.message;
        }
        </script></div>';
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

    public function preview_auto_assignment() {
        global $wpdb;
        $round = isset($_GET['round']) ? intval($_GET['round']) : 1;
        $category = sanitize_text_field($_GET['category'] ?? '');

        $jury_members = get_users(['role' => 'jury']);
        $cat_sql = $category ? $wpdb->prepare("AND category = %s", $category) : '';
        $candidates = $wpdb->get_results("SELECT id, name, category FROM {$this->candidates_table} WHERE name IS NOT NULL AND name != '' $cat_sql ORDER BY RAND() LIMIT 100");

        wp_send_json(['preview' => $candidates, 'jury_count' => count($jury_members)]);
    }

    public function cleanup_invalid_candidates() {
        global $wpdb;
        $deleted = $wpdb->query("DELETE FROM {$this->candidates_table} WHERE name IS NULL OR name = ''");
        wp_redirect(admin_url('admin.php?page=mobility_trailblazers&cleanup=' . $deleted));
        exit;
    }

    public function auto_assign_candidates() {
        global $wpdb;
        $round = isset($_GET['round']) ? intval($_GET['round']) : 1;
        $category = sanitize_text_field($_GET['category'] ?? '');

        $jury_members = get_users(['role' => 'jury']);
        $cat_sql = $category ? $wpdb->prepare("AND category = %s", $category) : '';
        $candidates = $wpdb->get_results("SELECT id FROM {$this->candidates_table} WHERE name IS NOT NULL AND name != '' $cat_sql ORDER BY RAND()");
        if (!$candidates) {
            wp_send_json(['message' => '❌ No valid candidates found.']);
        }

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
                $candidate_id = intval($candidates[$candidate_index]->id);
                if (!in_array($candidate_id, $already)) {
                    $assignments[] = $wpdb->prepare("(%d, %d, %d)", $jury->ID, $candidate_id, $round);
                    $assigned_count++;
                }
                $candidate_index++;
            }
        }

        if (!empty($assignments)) {
            $wpdb->query("INSERT INTO {$this->assignments_table} (jury_id, candidate_id, round) VALUES " . implode(",", $assignments));
            wp_send_json(['message' => '✅ Auto-assignment complete for round ' . $round]);
        } else {
            wp_send_json(['message' => 'ℹ️ No new candidates assigned. Perhaps all were already distributed.']);
        }
    }

    public function register_custom_post_types() {
        register_post_type('mt_candidate', [
            'label' => 'Candidates',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-awards',
        ]);

        register_post_type('mt_jury', [
            'label' => 'Jury',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-groups',
        ]);
    }

    public function ensure_roles_exist() {
        if (!get_role('jury')) {
            add_role('jury', 'Jury', ['read' => true]);
        }
        if (!get_role('candidate')) {
            add_role('candidate', 'Candidate', ['read' => true]);
        }
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

    public function render_voting_form() {
        ob_start(); ?>
        <form id="mt-vote-form">
            <label>Pioneer Spirit: <input type="range" name="pioneer_spirit" min="1" max="10" oninput="psValue.value=this.value" /> <output id="psValue">5</output></label><br>
            <label>Innovation Degree: <input type="range" name="innovation_degree" min="1" max="10" oninput="idValue.value=this.value" /> <output id="idValue">5</output></label><br>
            <label>Implementation Power: <input type="range" name="implementation_power" min="1" max="10" oninput="ipValue.value=this.value" /> <output id="ipValue">5</output></label><br>
            <label>Role Model Function: <input type="range" name="role_model_function" min="1" max="10" oninput="rmValue.value=this.value" /> <output id="rmValue">5</output></label><br>
            <input type="hidden" name="candidate_id" value="1">
            <input type="hidden" name="round" value="1">
            <input type="hidden" name="status" value="submitted">
            <button type="submit" onclick="return confirm('Are you sure you want to submit this vote?');">Submit Vote</button>
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

add_action('init', function () {
    MobilityTrailblazers::get_instance();
});
