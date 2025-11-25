<?php
/**
 * Member Post Type
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NVM_Member_Post_Type Class
 * Registers the Member custom post type
 */
class NVM_Member_Post_Type {
    
    /**
     * Post type slug
     */
    const POST_TYPE = 'nova_member';
    
    /**
     * Single instance of the class
     *
     * @var NVM_Member_Post_Type
     */
    private static $instance = null;
    
    /**
     * Get single instance of the class
     *
     * @return NVM_Member_Post_Type
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

        add_action( 'acf/init', array( $this, 'register_acf_fields' ) );

        // Add custom columns to admin list
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_custom_columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_custom_columns' ), 10, 2 );
    }
    
    /**
     * Register the Member post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x( 'Members', 'Post Type General Name', 'nova-video-manager' ),
            'singular_name'         => _x( 'Member', 'Post Type Singular Name', 'nova-video-manager' ),
            'menu_name'             => __( 'Members', 'nova-video-manager' ),
            'name_admin_bar'        => __( 'Member', 'nova-video-manager' ),
            'all_items'             => __( 'All Members', 'nova-video-manager' ),
            'add_new_item'          => __( 'Add New Member', 'nova-video-manager' ),
            'add_new'               => __( 'Add New', 'nova-video-manager' ),
            'new_item'              => __( 'New Member', 'nova-video-manager' ),
            'edit_item'             => __( 'Edit Member', 'nova-video-manager' ),
            'update_item'           => __( 'Update Member', 'nova-video-manager' ),
            'view_item'             => __( 'View Member', 'nova-video-manager' ),
            'view_items'            => __( 'View Members', 'nova-video-manager' ),
            'search_items'          => __( 'Search Members', 'nova-video-manager' ),
            'not_found'             => __( 'Not found', 'nova-video-manager' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'nova-video-manager' ),
        );
        
        $args = array(
            'label'                 => __( 'Member', 'nova-video-manager' ),
            'description'           => __( 'Community members who appear in videos', 'nova-video-manager' ),
            'labels'                => $labels,
            'supports'              => array( 'title' ), // Only title (member name)
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'edit.php?post_type=' . NVM_Post_Type::POST_TYPE,
            'menu_position'         => 20,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type( self::POST_TYPE, $args );
    }
    
    /**
     * Register ACF fields for members
     */
    public function register_acf_fields() {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }
        
        acf_add_local_field_group( array(
            'key' => 'group_nvm_member',
            'title' => 'Member Information',
            'fields' => array(
                array(
                    'key' => 'field_nvm_member_profile_url',
                    'label' => 'Profile URL',
                    'name' => 'nvm_member_profile_url',
                    'type' => 'url',
                    'instructions' => 'Link to member\'s bio/profile on another site or Circle.so',
                    'required' => 0,
                    'show_in_rest' => 1, // Enable REST API for Bricks
                ),
                array(
                    'key' => 'field_nvm_member_circle_category',
                    'label' => 'Circle Category',
                    'name' => 'nvm_member_circle_category',
                    'type' => 'text',
                    'instructions' => 'Circle.so category or group',
                    'required' => 0,
                    'show_in_rest' => 1, // Enable REST API for Bricks
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => self::POST_TYPE,
                    ),
                ),
            ),
        ) );
    }

    /**
     * Add custom columns to Members list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     */
    public function add_custom_columns( $columns ) {
        // Insert new columns after title
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( 'title' === $key ) {
                $new_columns['profile_url'] = __( 'Profile URL', 'nova-video-manager' );
                $new_columns['circle_category'] = __( 'Circle Category', 'nova-video-manager' );
            }
        }
        return $new_columns;
    }

    /**
     * Render custom column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     */
    public function render_custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'profile_url':
                $url = get_field( 'nvm_member_profile_url', $post_id );
                if ( $url ) {
                    echo '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $url ) . '</a>';
                } else {
                    echo '—';
                }
                break;

            case 'circle_category':
                $category = get_field( 'nvm_member_circle_category', $post_id );
                echo $category ? esc_html( $category ) : '—';
                break;
        }
    }
}

