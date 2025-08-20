<?php
/**
 * Backward Compatibility Facade
 *
 * Provides backward compatibility for legacy code
 * while migrating to dependency injection
 *
 * @package MobilityTrailblazers
 * @since 2.6.0
 */

namespace MobilityTrailblazers\Legacy;

use MobilityTrailblazers\Core\MT_Plugin;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Backward_Compatibility
 *
 * Facade for accessing services the old way while migration is in progress
 */
class MT_Backward_Compatibility {
    
    /**
     * Get Evaluation Service instance
     *
     * @return \MobilityTrailblazers\Services\MT_Evaluation_Service
     */
    public static function get_evaluation_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
    }
    
    /**
     * Get Assignment Service instance
     *
     * @return \MobilityTrailblazers\Services\MT_Assignment_Service
     */
    public static function get_assignment_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Assignment_Service');
    }
    
    /**
     * Get Evaluation Repository instance
     *
     * @return \MobilityTrailblazers\Repositories\MT_Evaluation_Repository
     */
    public static function get_evaluation_repository() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Repositories\MT_Evaluation_Repository');
    }
    
    /**
     * Get Assignment Repository instance
     *
     * @return \MobilityTrailblazers\Repositories\MT_Assignment_Repository
     */
    public static function get_assignment_repository() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Repositories\MT_Assignment_Repository');
    }
    
    /**
     * Get Candidate Repository instance
     *
     * @return \MobilityTrailblazers\Repositories\MT_Candidate_Repository
     */
    public static function get_candidate_repository() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Repositories\MT_Candidate_Repository');
    }
    
    /**
     * Get Audit Log Repository instance
     *
     * @return \MobilityTrailblazers\Repositories\MT_Audit_Log_Repository
     */
    public static function get_audit_log_repository() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Repositories\MT_Audit_Log_Repository');
    }
    
    /**
     * Get Diagnostic Service instance
     *
     * @return \MobilityTrailblazers\Services\MT_Diagnostic_Service
     */
    public static function get_diagnostic_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Diagnostic_Service');
    }
    
    /**
     * Get Candidate Import Service instance
     *
     * @return \MobilityTrailblazers\Services\MT_Candidate_Import_Service
     */
    public static function get_candidate_import_service() {
        $container = MT_Plugin::container();
        return $container->make('MobilityTrailblazers\Services\MT_Candidate_Import_Service');
    }
}