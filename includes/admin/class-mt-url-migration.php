<?php
/**
 * URL Field Migration Admin Tool
 * 
 * Temporary admin page for migrating LinkedIn/Website fields
 * Can be removed after successful migration
 * 
 * @package MobilityTrailblazers
 * @since 2.5.39
 */

namespace MobilityTrailblazers\Admin;

class MT_URL_Migration {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_migration_page']);
        add_action('admin_init', [$this, 'handle_migration']);
    }
    
    public function add_migration_page() {
        add_submenu_page(
            'mobility-trailblazers',
            'URL Field Migration',
            'URL Migration',
            'manage_options',
            'mt-url-migration',
            [$this, 'render_migration_page']
        );
    }
    
    public function render_migration_page() {
        ?>
        <div class="wrap">
            <h1>LinkedIn & Website URL Field Migration</h1>
            
            <?php if (isset($_GET['migration_complete'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Migration completed successfully!</strong></p>
                </div>
            <?php endif; ?>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Migration Status</h2>
                <?php $this->show_current_status(); ?>
            </div>
            
            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Run Migration</h2>
                <p>This tool will migrate LinkedIn and Website data from old field names to new field names.</p>
                <p><strong>What it does:</strong></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li>Migrates data from <code>_mt_linkedin</code> to <code>_mt_linkedin_url</code></li>
                    <li>Migrates data from <code>_mt_website</code> to <code>_mt_website_url</code></li>
                    <li>Only migrates if the new field is empty (won't overwrite existing data)</li>
                    <li>Creates a backup before migration</li>
                </ul>
                
                <form method="post" action="">
                    <?php wp_nonce_field('mt_url_migration', 'mt_migration_nonce'); ?>
                    
                    <p>
                        <label>
                            <input type="checkbox" name="dry_run" value="1" checked>
                            <strong>Dry Run</strong> (preview changes without applying them)
                        </label>
                    </p>
                    
                    <p class="submit">
                        <input type="submit" name="run_migration" class="button button-primary" value="Run Migration">
                    </p>
                </form>
                
                <?php if (isset($_POST['run_migration'])): ?>
                    <div style="background: #f0f0f0; padding: 15px; margin-top: 20px; border-radius: 5px;">
                        <h3>Migration Results:</h3>
                        <pre style="background: white; padding: 10px; overflow-x: auto;">
<?php $this->run_migration(isset($_POST['dry_run'])); ?>
                        </pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    private function show_current_status() {
        global $wpdb;
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(DISTINCT post_id) as total_candidates,
                SUM(CASE WHEN meta_key = '_mt_linkedin' AND meta_value != '' THEN 1 ELSE 0 END) as linkedin_old,
                SUM(CASE WHEN meta_key = '_mt_linkedin_url' AND meta_value != '' THEN 1 ELSE 0 END) as linkedin_new,
                SUM(CASE WHEN meta_key = '_mt_website' AND meta_value != '' THEN 1 ELSE 0 END) as website_old,
                SUM(CASE WHEN meta_key = '_mt_website_url' AND meta_value != '' THEN 1 ELSE 0 END) as website_new
            FROM {$wpdb->postmeta} 
            WHERE post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'mt_candidate')
            AND meta_key IN ('_mt_linkedin', '_mt_linkedin_url', '_mt_website', '_mt_website_url')"
        );
        
        ?>
        <table class="widefat" style="margin-top: 10px;">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Old Field Name</th>
                    <th>Records with Data</th>
                    <th>New Field Name</th>
                    <th>Records with Data</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>LinkedIn</strong></td>
                    <td><code>_mt_linkedin</code></td>
                    <td><?php echo $stats->linkedin_old; ?></td>
                    <td><code>_mt_linkedin_url</code></td>
                    <td><?php echo $stats->linkedin_new; ?></td>
                </tr>
                <tr>
                    <td><strong>Website</strong></td>
                    <td><code>_mt_website</code></td>
                    <td><?php echo $stats->website_old; ?></td>
                    <td><code>_mt_website_url</code></td>
                    <td><?php echo $stats->website_new; ?></td>
                </tr>
            </tbody>
        </table>
        <?php
        
        // Check for records needing migration
        $needs_migration = $wpdb->get_results(
            "SELECT 
                p.ID,
                p.post_title,
                old_li.meta_value as old_linkedin,
                new_li.meta_value as new_linkedin,
                old_web.meta_value as old_website,
                new_web.meta_value as new_website
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} old_li ON p.ID = old_li.post_id AND old_li.meta_key = '_mt_linkedin'
            LEFT JOIN {$wpdb->postmeta} new_li ON p.ID = new_li.post_id AND new_li.meta_key = '_mt_linkedin_url'
            LEFT JOIN {$wpdb->postmeta} old_web ON p.ID = old_web.post_id AND old_web.meta_key = '_mt_website'
            LEFT JOIN {$wpdb->postmeta} new_web ON p.ID = new_web.post_id AND new_web.meta_key = '_mt_website_url'
            WHERE p.post_type = 'mt_candidate'
            AND (
                (old_li.meta_value IS NOT NULL AND old_li.meta_value != '' AND (new_li.meta_value IS NULL OR new_li.meta_value = ''))
                OR
                (old_web.meta_value IS NOT NULL AND old_web.meta_value != '' AND (new_web.meta_value IS NULL OR new_web.meta_value = ''))
            )
            LIMIT 10"
        );
        
        if (!empty($needs_migration)) {
            echo '<h3 style="margin-top: 20px;">Records Needing Migration:</h3>';
            echo '<table class="widefat">';
            echo '<thead><tr><th>Candidate</th><th>LinkedIn (Old)</th><th>Website (Old)</th><th>Action Needed</th></tr></thead>';
            echo '<tbody>';
            foreach ($needs_migration as $record) {
                echo '<tr>';
                echo '<td>' . esc_html($record->post_title) . '</td>';
                echo '<td>' . ($record->old_linkedin ? esc_html($record->old_linkedin) : '-') . '</td>';
                echo '<td>' . ($record->old_website ? esc_html($record->old_website) : '-') . '</td>';
                echo '<td>';
                $actions = [];
                if ($record->old_linkedin && !$record->new_linkedin) {
                    $actions[] = 'Migrate LinkedIn';
                }
                if ($record->old_website && !$record->new_website) {
                    $actions[] = 'Migrate Website';
                }
                echo implode(', ', $actions);
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p style="margin-top: 20px; color: green;"><strong>✓ No migration needed - all data is already in the correct fields!</strong></p>';
        }
    }
    
    private function run_migration($dry_run = true) {
        global $wpdb;
        
        if (!$dry_run) {
            // Create backup
            $backup_table = $wpdb->prefix . 'postmeta_backup_' . date('Ymd_His');
            $wpdb->query(
                "CREATE TABLE {$backup_table} AS
                SELECT * FROM {$wpdb->postmeta} 
                WHERE meta_key IN ('_mt_linkedin', '_mt_linkedin_url', '_mt_website', '_mt_website_url')"
            );
            echo "Backup created: {$backup_table}\n\n";
        }
        
        // Get candidates needing migration
        $candidates = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts} 
             WHERE post_type = 'mt_candidate' 
             AND post_status IN ('publish', 'draft')"
        );
        
        $linkedin_migrated = 0;
        $website_migrated = 0;
        
        foreach ($candidates as $candidate) {
            $linkedin_old = get_post_meta($candidate->ID, '_mt_linkedin', true);
            $linkedin_new = get_post_meta($candidate->ID, '_mt_linkedin_url', true);
            $website_old = get_post_meta($candidate->ID, '_mt_website', true);
            $website_new = get_post_meta($candidate->ID, '_mt_website_url', true);
            
            // Migrate LinkedIn if needed
            if (!empty($linkedin_old) && empty($linkedin_new)) {
                if ($dry_run) {
                    echo "[DRY RUN] Would migrate LinkedIn for {$candidate->post_title}: {$linkedin_old}\n";
                } else {
                    update_post_meta($candidate->ID, '_mt_linkedin_url', $linkedin_old);
                    echo "✓ Migrated LinkedIn for {$candidate->post_title}\n";
                }
                $linkedin_migrated++;
            }
            
            // Migrate Website if needed
            if (!empty($website_old) && empty($website_new)) {
                if ($dry_run) {
                    echo "[DRY RUN] Would migrate Website for {$candidate->post_title}: {$website_old}\n";
                } else {
                    update_post_meta($candidate->ID, '_mt_website_url', $website_old);
                    echo "✓ Migrated Website for {$candidate->post_title}\n";
                }
                $website_migrated++;
            }
        }
        
        echo "\n" . str_repeat('-', 50) . "\n";
        if ($dry_run) {
            echo "DRY RUN SUMMARY:\n";
            echo "Would migrate {$linkedin_migrated} LinkedIn fields\n";
            echo "Would migrate {$website_migrated} Website fields\n";
            echo "\nUncheck 'Dry Run' and click 'Run Migration' to apply changes.";
        } else {
            echo "MIGRATION COMPLETE:\n";
            echo "Migrated {$linkedin_migrated} LinkedIn fields\n";
            echo "Migrated {$website_migrated} Website fields\n";
            echo "\n✅ Success! The admin interface should now show all LinkedIn/Website data.";
        }
    }
    
    public function handle_migration() {
        if (!isset($_POST['run_migration'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['mt_migration_nonce'], 'mt_url_migration')) {
            wp_die('Security check failed');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('You do not have permission to run this migration');
        }
    }
}

// Initialize the migration tool
new MT_URL_Migration();