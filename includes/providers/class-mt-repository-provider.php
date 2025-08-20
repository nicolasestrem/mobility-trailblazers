<?php
/**
 * Repository Service Provider
 *
 * Registers all repository classes with the container
 *
 * @package MobilityTrailblazers
 * @since 2.6.0
 */

namespace MobilityTrailblazers\Providers;

use MobilityTrailblazers\Core\MT_Service_Provider;
use MobilityTrailblazers\Repositories\MT_Evaluation_Repository;
use MobilityTrailblazers\Repositories\MT_Assignment_Repository;
use MobilityTrailblazers\Repositories\MT_Candidate_Repository;
use MobilityTrailblazers\Repositories\MT_Audit_Log_Repository;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Repository_Provider
 *
 * Handles registration of all repository dependencies
 */
class MT_Repository_Provider extends MT_Service_Provider {
    
    /**
     * Register repository services
     *
     * @return void
     */
    public function register() {
        // Register repositories as singletons
        // They should maintain state across the application
        
        // Evaluation Repository
        $this->singleton(
            'MobilityTrailblazers\Repositories\MT_Evaluation_Repository',
            function($container) {
                return new MT_Evaluation_Repository();
            }
        );
        
        // Also bind to interface if it exists
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface',
            function($container) {
                return $container->make('MobilityTrailblazers\Repositories\MT_Evaluation_Repository');
            }
        );
        
        // Assignment Repository
        $this->singleton(
            'MobilityTrailblazers\Repositories\MT_Assignment_Repository',
            function($container) {
                return new MT_Assignment_Repository();
            }
        );
        
        // Also bind to interface if it exists
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface',
            function($container) {
                return $container->make('MobilityTrailblazers\Repositories\MT_Assignment_Repository');
            }
        );
        
        // Candidate Repository
        $this->singleton(
            'MobilityTrailblazers\Repositories\MT_Candidate_Repository',
            function($container) {
                return new MT_Candidate_Repository();
            }
        );
        
        // Also bind to interface if it exists
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Candidate_Repository_Interface',
            function($container) {
                return $container->make('MobilityTrailblazers\Repositories\MT_Candidate_Repository');
            }
        );
        
        // Audit Log Repository
        $this->singleton(
            'MobilityTrailblazers\Repositories\MT_Audit_Log_Repository',
            function($container) {
                return new MT_Audit_Log_Repository();
            }
        );
        
        // Also bind to interface if it exists
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Audit_Log_Repository_Interface',
            function($container) {
                return $container->make('MobilityTrailblazers\Repositories\MT_Audit_Log_Repository');
            }
        );
    }
}