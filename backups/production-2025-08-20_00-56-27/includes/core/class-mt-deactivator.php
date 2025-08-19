<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * Plugin Deactivator
 *
 * @package MobilityTrailblazers
 * @since 2.0.0
 */

namespace MobilityTrailblazers\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Deactivator
 *
 * Handles plugin deactivation
 */
class MT_Deactivator {
    
    /**
     * Deactivate the plugin
     *
     * @return void
     */
    public function deactivate() {
        // Clear scheduled hooks
        $this->clear_scheduled_hooks();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear transients
        delete_transient('mt_activation_redirect');
    }
    
    /**
     * Clear scheduled hooks
     *
     * @return void
     */
    private function clear_scheduled_hooks() {
        wp_clear_scheduled_hook('mt_daily_cron');
    }
} 
