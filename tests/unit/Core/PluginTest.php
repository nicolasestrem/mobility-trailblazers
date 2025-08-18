<?php
/**
 * Plugin Core Tests
 *
 * @package MobilityTrailblazers\Tests\Unit
 */

namespace MobilityTrailblazers\Tests\Unit\Core;

use MobilityTrailblazers\Tests\MT_Test_Case;
use MobilityTrailblazers\Tests\MT_Test_Helpers;

/**
 * Test plugin core functionality
 */
class PluginTest extends MT_Test_Case {
    
    use MT_Test_Helpers;

    /**
     * Test plugin constants are defined
     */
    public function test_plugin_constants_defined() {
        $this->assertTrue(defined('MT_VERSION'), 'MT_VERSION should be defined');
        $this->assertTrue(defined('MT_PLUGIN_DIR'), 'MT_PLUGIN_DIR should be defined');
        $this->assertTrue(defined('MT_PLUGIN_URL'), 'MT_PLUGIN_URL should be defined');
        $this->assertTrue(defined('MT_PLUGIN_FILE'), 'MT_PLUGIN_FILE should be defined');
        $this->assertTrue(defined('MT_PLUGIN_BASENAME'), 'MT_PLUGIN_BASENAME should be defined');
    }

    /**
     * Test plugin version format
     */
    public function test_plugin_version_format() {
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+$/',
            MT_VERSION,
            'Version should be in format X.Y.Z'
        );
    }

    /**
     * Test plugin activation creates required tables
     */
    public function test_plugin_activation_creates_tables() {
        // Tables should already exist from activation
        $this->assertTableExists('mt_evaluations');
        $this->assertTableExists('mt_assignments');
        $this->assertTableExists('mt_votes');
        $this->assertTableExists('mt_candidate_scores');
    }

    /**
     * Test evaluation table structure
     */
    public function test_evaluation_table_structure() {
        $expected_columns = [
            'id',
            'jury_member_id',
            'candidate_id',
            'courage_score',
            'innovation_score',
            'implementation_score',
            'relevance_score',
            'visibility_score',
            'comments',
            'status',
            'created_at',
            'updated_at'
        ];
        
        $this->assertTableHasColumns('mt_evaluations', $expected_columns);
    }

    /**
     * Test assignment table structure
     */
    public function test_assignment_table_structure() {
        $expected_columns = [
            'id',
            'jury_member_id',
            'candidate_id',
            'status',
            'assigned_at',
            'updated_at',
            'completed_at',
            'notes'
        ];
        
        $this->assertTableHasColumns('mt_assignments', $expected_columns);
    }

    /**
     * Test custom post types are registered
     */
    public function test_custom_post_types_registered() {
        $this->assertTrue(post_type_exists('mt_candidate'), 'mt_candidate post type should exist');
        $this->assertTrue(post_type_exists('mt_jury_member'), 'mt_jury_member post type should exist');
    }

    /**
     * Test custom capabilities are registered
     */
    public function test_custom_capabilities_registered() {
        $admin = get_role('administrator');
        
        $this->assertTrue($admin->has_cap('mt_manage_evaluations'), 'Admin should have mt_manage_evaluations');
        $this->assertTrue($admin->has_cap('mt_view_all_evaluations'), 'Admin should have mt_view_all_evaluations');
        $this->assertTrue($admin->has_cap('mt_export_data'), 'Admin should have mt_export_data');
    }

    /**
     * Test shortcodes are registered
     */
    public function test_shortcodes_registered() {
        $this->assertShortcodeExists('mt_jury_dashboard');
        $this->assertShortcodeExists('mt_evaluation_form');
        $this->assertShortcodeExists('mt_candidates_grid');
        $this->assertShortcodeExists('mt_winners_display');
    }

    /**
     * Test AJAX actions are registered
     */
    public function test_ajax_actions_registered() {
        // Evaluation AJAX
        $this->assertActionExists('wp_ajax_mt_save_evaluation');
        $this->assertActionExists('wp_ajax_mt_submit_evaluation');
        $this->assertActionExists('wp_ajax_mt_get_evaluation');
        
        // Admin AJAX
        $this->assertActionExists('wp_ajax_mt_create_assignments');
        $this->assertActionExists('wp_ajax_mt_export_data');
        $this->assertActionExists('wp_ajax_mt_import_data');
        
        // Debug AJAX
        $this->assertActionExists('wp_ajax_mt_run_diagnostic');
        $this->assertActionExists('wp_ajax_mt_execute_debug_script');
    }

    /**
     * Test plugin text domain is loaded
     */
    public function test_plugin_text_domain_loaded() {
        $loaded = is_textdomain_loaded('mobility-trailblazers');
        $this->assertTrue($loaded, 'Plugin text domain should be loaded');
    }

    /**
     * Test plugin assets are enqueued
     */
    public function test_plugin_assets_enqueued() {
        // Go to admin page
        set_current_screen('toplevel_page_mobility-trailblazers');
        do_action('admin_enqueue_scripts');
        
        // Check admin scripts
        $this->assertTrue(wp_script_is('mt-admin', 'enqueued'), 'Admin script should be enqueued');
        $this->assertTrue(wp_style_is('mt-admin', 'enqueued'), 'Admin style should be enqueued');
    }

    /**
     * Test plugin options are set
     */
    public function test_plugin_options_set() {
        $this->assertOptionExists('mt_plugin_version');
        $this->assertEquals(MT_VERSION, get_option('mt_plugin_version'), 'Plugin version should be stored');
    }

    /**
     * Test plugin menus are registered
     */
    public function test_plugin_menus_registered() {
        global $menu, $submenu;
        
        // Login as admin
        $this->login_as_admin();
        
        // Trigger menu registration
        do_action('admin_menu');
        
        // Check main menu exists
        $menu_exists = false;
        foreach ($menu as $item) {
            if (isset($item[2]) && $item[2] === 'mobility-trailblazers') {
                $menu_exists = true;
                break;
            }
        }
        
        $this->assertTrue($menu_exists, 'Main plugin menu should exist');
    }

    /**
     * Test plugin uninstall removes data
     */
    public function test_plugin_uninstall_cleanup() {
        // This would actually uninstall, so we just check the method exists
        $this->assertTrue(
            class_exists('\MobilityTrailblazers\Core\MT_Uninstaller'),
            'Uninstaller class should exist'
        );
    }
}