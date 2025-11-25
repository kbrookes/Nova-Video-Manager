<?php
/**
 * Taxonomies Registration
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NVM_Taxonomies Class
 */
class NVM_Taxonomies {
    
    /**
     * Single instance of the class
     *
     * @var NVM_Taxonomies
     */
    private static $instance = null;
    
    /**
     * Category taxonomy slug
     *
     * @var string
     */
    const CATEGORY_TAXONOMY = 'nova_video_category';
    
    /**
     * Tag taxonomy slug
     *
     * @var string
     */
    const TAG_TAXONOMY = 'nova_video_tag';

    /**
     * Video type taxonomy slug
     *
     * @var string
     */
    const TYPE_TAXONOMY = 'nova_video_type';

    /**
     * Get single instance of the class
     *
     * @return NVM_Taxonomies
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
        add_action( 'init', array( $this, 'register_taxonomies' ) );
    }

    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        $this->register_category_taxonomy();
        $this->register_tag_taxonomy();
        $this->register_type_taxonomy();
    }
    
    /**
     * Register category taxonomy (for YouTube playlists)
     */
    private function register_category_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Video Categories', 'taxonomy general name', 'nova-video-manager' ),
            'singular_name'              => _x( 'Video Category', 'taxonomy singular name', 'nova-video-manager' ),
            'search_items'               => __( 'Search Video Categories', 'nova-video-manager' ),
            'popular_items'              => __( 'Popular Video Categories', 'nova-video-manager' ),
            'all_items'                  => __( 'All Video Categories', 'nova-video-manager' ),
            'parent_item'                => __( 'Parent Video Category', 'nova-video-manager' ),
            'parent_item_colon'          => __( 'Parent Video Category:', 'nova-video-manager' ),
            'edit_item'                  => __( 'Edit Video Category', 'nova-video-manager' ),
            'update_item'                => __( 'Update Video Category', 'nova-video-manager' ),
            'add_new_item'               => __( 'Add New Video Category', 'nova-video-manager' ),
            'new_item_name'              => __( 'New Video Category Name', 'nova-video-manager' ),
            'separate_items_with_commas' => __( 'Separate video categories with commas', 'nova-video-manager' ),
            'add_or_remove_items'        => __( 'Add or remove video categories', 'nova-video-manager' ),
            'choose_from_most_used'      => __( 'Choose from the most used video categories', 'nova-video-manager' ),
            'not_found'                  => __( 'No video categories found.', 'nova-video-manager' ),
            'menu_name'                  => __( 'Categories', 'nova-video-manager' ),
        );
        
        $args = array(
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'video-category' ),
            'show_in_rest'      => true,
        );
        
        register_taxonomy( self::CATEGORY_TAXONOMY, NVM_Post_Type::POST_TYPE, $args );
    }
    
    /**
     * Register tag taxonomy (for YouTube tags)
     */
    private function register_tag_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Video Tags', 'taxonomy general name', 'nova-video-manager' ),
            'singular_name'              => _x( 'Video Tag', 'taxonomy singular name', 'nova-video-manager' ),
            'search_items'               => __( 'Search Video Tags', 'nova-video-manager' ),
            'popular_items'              => __( 'Popular Video Tags', 'nova-video-manager' ),
            'all_items'                  => __( 'All Video Tags', 'nova-video-manager' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Video Tag', 'nova-video-manager' ),
            'update_item'                => __( 'Update Video Tag', 'nova-video-manager' ),
            'add_new_item'               => __( 'Add New Video Tag', 'nova-video-manager' ),
            'new_item_name'              => __( 'New Video Tag Name', 'nova-video-manager' ),
            'separate_items_with_commas' => __( 'Separate video tags with commas', 'nova-video-manager' ),
            'add_or_remove_items'        => __( 'Add or remove video tags', 'nova-video-manager' ),
            'choose_from_most_used'      => __( 'Choose from the most used video tags', 'nova-video-manager' ),
            'not_found'                  => __( 'No video tags found.', 'nova-video-manager' ),
            'menu_name'                  => __( 'Tags', 'nova-video-manager' ),
        );
        
        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'video-tag' ),
            'show_in_rest'      => true,
        );
        
        register_taxonomy( self::TAG_TAXONOMY, NVM_Post_Type::POST_TYPE, $args );
    }
    
    /**
     * Get category taxonomy slug
     *
     * @return string
     */
    public static function get_category_taxonomy() {
        return self::CATEGORY_TAXONOMY;
    }
    
    /**
     * Get tag taxonomy slug
     *
     * @return string
     */
    public static function get_tag_taxonomy() {
        return self::TAG_TAXONOMY;
    }

    /**
     * Register video type taxonomy (for shorts vs regular videos)
     */
    private function register_type_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Video Types', 'taxonomy general name', 'nova-video-manager' ),
            'singular_name'              => _x( 'Video Type', 'taxonomy singular name', 'nova-video-manager' ),
            'search_items'               => __( 'Search Video Types', 'nova-video-manager' ),
            'popular_items'              => __( 'Popular Video Types', 'nova-video-manager' ),
            'all_items'                  => __( 'All Video Types', 'nova-video-manager' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Video Type', 'nova-video-manager' ),
            'update_item'                => __( 'Update Video Type', 'nova-video-manager' ),
            'add_new_item'               => __( 'Add New Video Type', 'nova-video-manager' ),
            'new_item_name'              => __( 'New Video Type Name', 'nova-video-manager' ),
            'separate_items_with_commas' => __( 'Separate video types with commas', 'nova-video-manager' ),
            'add_or_remove_items'        => __( 'Add or remove video types', 'nova-video-manager' ),
            'choose_from_most_used'      => __( 'Choose from the most used video types', 'nova-video-manager' ),
            'not_found'                  => __( 'No video types found.', 'nova-video-manager' ),
            'menu_name'                  => __( 'Video Types', 'nova-video-manager' ),
        );

        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array( 'slug' => 'video-type' ),
            'show_in_rest'      => true,
        );

        register_taxonomy( self::TYPE_TAXONOMY, NVM_Post_Type::POST_TYPE, $args );

        // Register default terms
        $this->register_default_video_types();
    }

    /**
     * Register default video type terms
     */
    private function register_default_video_types() {
        $default_types = array(
            'video' => __( 'Video', 'nova-video-manager' ),
            'short' => __( 'Short', 'nova-video-manager' ),
        );

        foreach ( $default_types as $slug => $name ) {
            if ( ! term_exists( $slug, self::TYPE_TAXONOMY ) ) {
                wp_insert_term( $name, self::TYPE_TAXONOMY, array( 'slug' => $slug ) );
            }
        }
    }

    /**
     * Get video type taxonomy slug
     *
     * @return string
     */
    public static function get_type_taxonomy() {
        return self::TYPE_TAXONOMY;
    }
}

