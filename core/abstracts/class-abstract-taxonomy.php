<?php
/**
 * Abstract Taxonomy Class
 *
 * @package MobilityTrailblazers
 * @subpackage Core\Abstracts
 */

namespace MobilityTrailblazers\Core\Abstracts;

use MobilityTrailblazers\Core\Interfaces\Registrable;

/**
 * Abstract class for custom taxonomies
 */
abstract class Abstract_Taxonomy implements Registrable {
    
    /**
     * Taxonomy key
     *
     * @var string
     */
    protected $taxonomy;
    
    /**
     * Object types this taxonomy applies to
     *
     * @var array
     */
    protected $object_types = array();
    
    /**
     * Taxonomy arguments
     *
     * @var array
     */
    protected $args = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->taxonomy = $this->get_taxonomy();
        $this->object_types = $this->get_object_types();
        $this->args = $this->get_args();
    }
    
    /**
     * Register the taxonomy
     *
     * @return void
     */
    public function register() {
        register_taxonomy($this->taxonomy, $this->object_types, $this->args);
        $this->init_hooks();
    }
    
    /**
     * Get the taxonomy key
     *
     * @return string
     */
    abstract protected function get_taxonomy();
    
    /**
     * Get the object types this taxonomy applies to
     *
     * @return array
     */
    abstract protected function get_object_types();
    
    /**
     * Get the taxonomy arguments
     *
     * @return array
     */
    abstract protected function get_args();
    
    /**
     * Initialize hooks specific to this taxonomy
     *
     * @return void
     */
    protected function init_hooks() {
        // Override in child classes
    }
    
    /**
     * Get default taxonomy arguments
     *
     * @return array
     */
    protected function get_default_args() {
        return array(
            'hierarchical' => true,
            'labels' => $this->get_labels(),
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $this->taxonomy),
            'show_in_rest' => true,
        );
    }
    
    /**
     * Get taxonomy labels
     *
     * @return array
     */
    protected function get_labels() {
        $singular = $this->get_singular_label();
        $plural = $this->get_plural_label();
        
        return array(
            'name' => $plural,
            'singular_name' => $singular,
            'search_items' => sprintf(__('Search %s', 'mobility-trailblazers'), $plural),
            'all_items' => sprintf(__('All %s', 'mobility-trailblazers'), $plural),
            'parent_item' => sprintf(__('Parent %s', 'mobility-trailblazers'), $singular),
            'parent_item_colon' => sprintf(__('Parent %s:', 'mobility-trailblazers'), $singular),
            'edit_item' => sprintf(__('Edit %s', 'mobility-trailblazers'), $singular),
            'update_item' => sprintf(__('Update %s', 'mobility-trailblazers'), $singular),
            'add_new_item' => sprintf(__('Add New %s', 'mobility-trailblazers'), $singular),
            'new_item_name' => sprintf(__('New %s Name', 'mobility-trailblazers'), $singular),
            'menu_name' => $plural,
        );
    }
    
    /**
     * Get singular label
     *
     * @return string
     */
    abstract protected function get_singular_label();
    
    /**
     * Get plural label
     *
     * @return string
     */
    abstract protected function get_plural_label();
} 