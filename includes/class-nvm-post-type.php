<?php
/**
 * Custom Post Type Registration
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NVM_Post_Type Class
 */
class NVM_Post_Type {
    
    /**
     * Single instance of the class
     *
     * @var NVM_Post_Type
     */
    private static $instance = null;
    
    /**
     * Post type slug
     *
     * @var string
     */
    const POST_TYPE = 'nova_video';
    
    /**
     * Get single instance of the class
     *
     * @return NVM_Post_Type
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register post type immediately (called during init hook already)
        $this->register_post_type();
    }
    
    /**
     * Register the custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Videos', 'Post type general name', 'nova-video-manager' ),
            'singular_name'         => _x( 'Video', 'Post type singular name', 'nova-video-manager' ),
            'menu_name'             => _x( 'Videos', 'Admin Menu text', 'nova-video-manager' ),
            'name_admin_bar'        => _x( 'Video', 'Add New on Toolbar', 'nova-video-manager' ),
            'add_new'               => __( 'Add New', 'nova-video-manager' ),
            'add_new_item'          => __( 'Add New Video', 'nova-video-manager' ),
            'new_item'              => __( 'New Video', 'nova-video-manager' ),
            'edit_item'             => __( 'Edit Video', 'nova-video-manager' ),
            'view_item'             => __( 'View Video', 'nova-video-manager' ),
            'all_items'             => __( 'All Videos', 'nova-video-manager' ),
            'search_items'          => __( 'Search Videos', 'nova-video-manager' ),
            'parent_item_colon'     => __( 'Parent Videos:', 'nova-video-manager' ),
            'not_found'             => __( 'No videos found.', 'nova-video-manager' ),
            'not_found_in_trash'    => __( 'No videos found in Trash.', 'nova-video-manager' ),
            'featured_image'        => _x( 'Video Thumbnail', 'Overrides the "Featured Image" phrase', 'nova-video-manager' ),
            'set_featured_image'    => _x( 'Set video thumbnail', 'Overrides the "Set featured image" phrase', 'nova-video-manager' ),
            'remove_featured_image' => _x( 'Remove video thumbnail', 'Overrides the "Remove featured image" phrase', 'nova-video-manager' ),
            'use_featured_image'    => _x( 'Use as video thumbnail', 'Overrides the "Use as featured image" phrase', 'nova-video-manager' ),
            'archives'              => _x( 'Video archives', 'The post type archive label', 'nova-video-manager' ),
            'insert_into_item'      => _x( 'Insert into video', 'Overrides the "Insert into post" phrase', 'nova-video-manager' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this video', 'Overrides the "Uploaded to this post" phrase', 'nova-video-manager' ),
            'filter_items_list'     => _x( 'Filter videos list', 'Screen reader text for the filter links', 'nova-video-manager' ),
            'items_list_navigation' => _x( 'Videos list navigation', 'Screen reader text for the pagination', 'nova-video-manager' ),
            'items_list'            => _x( 'Videos list', 'Screen reader text for the items list', 'nova-video-manager' ),
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'videos' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 5,
            'menu_icon'          => 'dashicons-video-alt3',
            'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
            'show_in_rest'       => true,
            'taxonomies'         => array( 'nova_video_category', 'nova_video_tag', 'nova_video_type' ),
        );
        
        register_post_type( self::POST_TYPE, $args );
    }
    
    /**
     * Get the post type slug
     *
     * @return string
     */
    public static function get_post_type() {
        return self::POST_TYPE;
    }
}

