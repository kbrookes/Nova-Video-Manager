<?php
/**
 * ACF Field Groups
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NVM_ACF_Fields Class
 */
class NVM_ACF_Fields {
    
    /**
     * Single instance of the class
     *
     * @var NVM_ACF_Fields
     */
    private static $instance = null;
    
    /**
     * Get single instance of the class
     *
     * @return NVM_ACF_Fields
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
        add_action( 'acf/init', array( $this, 'register_field_groups' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_relationship_scripts' ) );
    }
    
    /**
     * Register ACF field groups
     */
    public function register_field_groups() {
        if ( ! function_exists( 'acf_add_local_field_group' ) ) {
            return;
        }
        
        $this->register_video_metadata_fields();
        $this->register_community_fields();
    }
    
    /**
     * Register video metadata field group
     */
    private function register_video_metadata_fields() {
        acf_add_local_field_group( array(
            'key'      => 'group_nvm_video_metadata',
            'title'    => __( 'Video Metadata', 'nova-video-manager' ),
            'fields'   => array(
                array(
                    'key'           => 'field_nvm_youtube_id',
                    'label'         => __( 'YouTube Video ID', 'nova-video-manager' ),
                    'name'          => 'nvm_youtube_id',
                    'type'          => 'text',
                    'required'      => 1,
                    'readonly'      => 1,
                    'instructions'  => __( 'The unique YouTube video ID.', 'nova-video-manager' ),
                ),
                array(
                    'key'           => 'field_nvm_youtube_url',
                    'label'         => __( 'YouTube URL', 'nova-video-manager' ),
                    'name'          => 'nvm_youtube_url',
                    'type'          => 'url',
                    'required'      => 1,
                    'readonly'      => 1,
                    'instructions'  => __( 'The full YouTube video URL.', 'nova-video-manager' ),
                ),
                array(
                    'key'           => 'field_nvm_duration',
                    'label'         => __( 'Duration', 'nova-video-manager' ),
                    'name'          => 'nvm_duration',
                    'type'          => 'text',
                    'readonly'      => 1,
                    'instructions'  => __( 'Video duration in ISO 8601 format.', 'nova-video-manager' ),
                ),
                array(
                    'key'           => 'field_nvm_published_at',
                    'label'         => __( 'Published Date', 'nova-video-manager' ),
                    'name'          => 'nvm_published_at',
                    'type'          => 'date_time_picker',
                    'readonly'      => 1,
                    'display_format' => 'F j, Y g:i a',
                    'return_format' => 'Y-m-d H:i:s',
                    'instructions'  => __( 'Original YouTube publish date.', 'nova-video-manager' ),
                ),
                array(
                    'key'           => 'field_nvm_view_count',
                    'label'         => __( 'View Count', 'nova-video-manager' ),
                    'name'          => 'nvm_view_count',
                    'type'          => 'number',
                    'readonly'      => 1,
                    'instructions'  => __( 'Number of views on YouTube.', 'nova-video-manager' ),
                ),
                array(
                    'key'           => 'field_nvm_like_count',
                    'label'         => __( 'Like Count', 'nova-video-manager' ),
                    'name'          => 'nvm_like_count',
                    'type'          => 'number',
                    'readonly'      => 1,
                    'instructions'  => __( 'Number of likes on YouTube.', 'nova-video-manager' ),
                ),
                array(
                    'key'           => 'field_nvm_comment_count',
                    'label'         => __( 'Comment Count', 'nova-video-manager' ),
                    'name'          => 'nvm_comment_count',
                    'type'          => 'number',
                    'readonly'      => 1,
                    'instructions'  => __( 'Number of comments on YouTube.', 'nova-video-manager' ),
                ),
                array(
                    'key'           => 'field_nvm_last_synced',
                    'label'         => __( 'Last Synced', 'nova-video-manager' ),
                    'name'          => 'nvm_last_synced',
                    'type'          => 'date_time_picker',
                    'readonly'      => 1,
                    'display_format' => 'F j, Y g:i a',
                    'return_format' => 'Y-m-d H:i:s',
                    'instructions'  => __( 'Last time this video was synced from YouTube.', 'nova-video-manager' ),
                ),
                array(
                    'key'           => 'field_nvm_description_modified',
                    'label'         => __( 'Description Manually Modified', 'nova-video-manager' ),
                    'name'          => 'nvm_description_modified',
                    'type'          => 'true_false',
                    'default_value' => 0,
                    'ui'            => 1,
                    'instructions'  => __( 'Check this to prevent the description from being overwritten during sync.', 'nova-video-manager' ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => NVM_Post_Type::POST_TYPE,
                    ),
                ),
            ),
            'menu_order'            => 0,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
        ) );
    }

    /**
     * Register community/user assignment field group
     */
    private function register_community_fields() {
        acf_add_local_field_group( array(
            'key'      => 'group_nvm_community',
            'title'    => __( 'Community Members', 'nova-video-manager' ),
            'fields'   => array(
                array(
                    'key'           => 'field_nvm_featured_members',
                    'label'         => __( 'Featured Community Members', 'nova-video-manager' ),
                    'name'          => 'nvm_featured_members',
                    'type'          => 'relationship',
                    'instructions'  => __( 'Select existing members or click "+ Add New Member" to create a new one.', 'nova-video-manager' ),
                    'post_type'     => array( NVM_Member_Post_Type::POST_TYPE ),
                    'filters'       => array( 'search' ),
                    'return_format' => 'id',
                    'min'           => 0,
                    'max'           => '',
                    'elements'      => '', // Allow all default elements including add new button
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => NVM_Post_Type::POST_TYPE,
                    ),
                ),
            ),
            'menu_order'            => 1,
            'position'              => 'normal',
            'style'                 => 'default',
            'label_placement'       => 'top',
            'instruction_placement' => 'label',
        ) );
    }

    /**
     * Enqueue scripts for relationship field
     */
    public function enqueue_relationship_scripts( $hook ) {
        // Only load on video edit pages
        global $post_type;
        if ( 'nova_video' !== $post_type || ( 'post.php' !== $hook && 'post-new.php' !== $hook ) ) {
            return;
        }

        wp_enqueue_script(
            'nvm-relationship-field',
            NVM_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery', 'acf-input' ),
            NVM_VERSION,
            true
        );

        wp_localize_script(
            'nvm-relationship-field',
            'nvmAdmin',
            array(
                'newMemberUrl' => admin_url( 'post-new.php?post_type=' . NVM_Member_Post_Type::POST_TYPE ),
            )
        );
    }
}
