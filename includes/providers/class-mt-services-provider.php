<?php
/**
 * Services Provider
 *
 * Registers all service classes with the container
 *
 * @package MobilityTrailblazers
 * @since 2.6.0
 */

namespace MobilityTrailblazers\Providers;

use MobilityTrailblazers\Core\MT_Service_Provider;
use MobilityTrailblazers\Services\MT_Evaluation_Service;
use MobilityTrailblazers\Services\MT_Assignment_Service;
use MobilityTrailblazers\Services\MT_Candidate_Import_Service;
use MobilityTrailblazers\Services\MT_Diagnostic_Service;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MT_Services_Provider
 *
 * Handles registration of all service dependencies
 */
class MT_Services_Provider extends MT_Service_Provider {
    
    /**
     * Register services
     *
     * @return void
     */
    public function register() {
        // Register services
        // Services are registered as singletons with proper dependency injection
        // Repositories are injected from the container for clean separation of concerns
        
        // Evaluation Service
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Evaluation_Service',
            function($container) {
                // Use dependency injection with repositories from container
                $evaluation_repository = $container->make('MobilityTrailblazers\Interfaces\MT_Evaluation_Repository_Interface');
                $assignment_repository = $container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface');
                
                return new MT_Evaluation_Service($evaluation_repository, $assignment_repository);
            }
        );
        
        // Also bind to interface
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Evaluation_Service_Interface',
            function($container) {
                return $container->make('MobilityTrailblazers\Services\MT_Evaluation_Service');
            }
        );
        
        // Assignment Service
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Assignment_Service',
            function($container) {
                // Use dependency injection with repository from container
                $assignment_repository = $container->make('MobilityTrailblazers\Interfaces\MT_Assignment_Repository_Interface');
                
                return new MT_Assignment_Service($assignment_repository);
            }
        );
        
        // Also bind to interface
        $this->singleton(
            'MobilityTrailblazers\Interfaces\MT_Assignment_Service_Interface',
            function($container) {
                return $container->make('MobilityTrailblazers\Services\MT_Assignment_Service');
            }
        );
        
        // Candidate Import Service
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Candidate_Import_Service',
            function($container) {
                return new MT_Candidate_Import_Service();
            }
        );
        
        // Diagnostic Service
        $this->singleton(
            'MobilityTrailblazers\Services\MT_Diagnostic_Service',
            function($container) {
                return new MT_Diagnostic_Service();
            }
        );
    }
}