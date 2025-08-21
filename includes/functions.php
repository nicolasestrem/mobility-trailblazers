<?php
// GPL 2.0 or later. See LICENSE. Copyright (c) 2025 Nicolas Estrem

/**
 * Global utility functions for Mobility Trailblazers plugin
 *
 * @package MobilityTrailblazers
 * @since 2.5.37
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get translated status string
 *
 * @param string $status Raw status value
 * @return string Translated status
 */
function mt_get_translated_status($status) {
    $statuses = [
        'draft' => __('Draft', 'mobility-trailblazers'),
        'submitted' => __('Submitted', 'mobility-trailblazers'),
        'completed' => __('Completed', 'mobility-trailblazers'),
        'approved' => __('Approved', 'mobility-trailblazers'),
        'rejected' => __('Rejected', 'mobility-trailblazers')
    ];
    
    return isset($statuses[$status]) ? $statuses[$status] : ucfirst($status);
}