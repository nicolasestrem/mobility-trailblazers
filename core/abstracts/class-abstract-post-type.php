<?php
/**
 * Abstract Post Type Class
 *
 * @package MobilityTrailblazers
 * @subpackage Core\Abstracts
 */

namespace MobilityTrailblazers\Core\Abstracts;

use MobilityTrailblazers\Core\Interfaces\Registrable;

/**
 * Abstract class for custom post types
 */
abstract class Abstract_Post_Type implements Registrable {
    
    /**
     * Post type key
     *
     * @var string
     */
    protected $post_type;
    
    /**
     * Post type arguments
     *
     * @var array
     */
    protected $args = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->post_type = $this->get_post_type();
        $this->args = $this->get_args();
    }
    
    /**
     * Register the post type
     *
     * @return void
     */
    public function register() {
        register_post_type($this->post_type, $this->args);
        $this->add_meta_boxes();
        $this->init_hooks();
    }
    
    /**
     * Get the post type key
     *
     * @return string
     */
    abstract protected function get_post_type();
    
    /**
     * Get the post type arguments
     *
     * @return array
     */
    abstract protected function get_args();
    
    /**
     * Add meta boxes for this post type
     *
     * @return void
     */
    protected function add_meta_boxes() {
        // Override in child classes
    }
    
    /**
     * Initialize hooks specific to this post type
     *
     * @return void
     */
    protected function init_hooks() {
        // Override in child classes
    }
    
    /**
     * Get default post type arguments
     *
     * @return array
     */
    protected function get_default_args() {
        return array(
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => null,
            'supports' => array('title', 'editor', 'thumbnail'),
            'show_in_rest' => true,
        );
    }
} 