<?php
/**
 * Backward compatibility functions for Mobility Trailblazers
 * 
 * This file contains deprecated function wrappers to maintain
 * backward compatibility during the transition to new naming conventions.
 * 
 * @package MobilityTrailblazers
 * @since 1.0.6
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get jury nomenclature (base or plural)
 * 
 * @deprecated 1.0.6 Use mt_get_jury_nomenclature() instead
 * @param bool $plural Whether to return plural form
 * @return string
 */
function mt_get_jury_nomenclature($plural = false) {
    _deprecated_function(__FUNCTION__, '1.0.6', 'mt_get_jury_nomenclature');
    return mt_get_jury_nomenclature($plural);
}

/**
 * Get jury member user meta key
 * 
 * @deprecated 1.0.6 Use mt_get_jury_member_meta_key() instead
 * @return string
 */
function mt_get_jury_member_meta_key() {
    _deprecated_function(__FUNCTION__, '1.0.6', 'mt_get_jury_member_meta_key');
    return mt_get_jury_member_meta_key();
}

/**
 * Get evaluation criteria
 * 
 * @deprecated 1.0.6 Use mt_get_evaluation_criteria() instead
 * @return array
 */
function mt_get_evaluation_criteria() {
    _deprecated_function(__FUNCTION__, '1.0.6', 'mt_get_evaluation_criteria');
    return mt_get_evaluation_criteria();
}
