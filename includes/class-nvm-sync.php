<?php
/**
 * Video Sync Functionality
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NVM_Sync Class
 */
class NVM_Sync {
    
    /**
     * Single instance of the class
     *
     * @var NVM_Sync
     */
    private static $instance = null;
    
    /**
     * YouTube API instance
     *
     * @var NVM_YouTube_API
     */
    private $youtube_api;
    
    /**
     * Get single instance of the class
     *
     * @return NVM_Sync
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
        $this->youtube_api = NVM_YouTube_API::get_instance();
    }
    
    /**
     * Sync videos from YouTube
     *
     * @param int $max_videos Maximum number of videos to sync (0 for all)
     * @param bool $full_sync Whether to do a full sync or incremental sync (default: false = incremental)
     * @return int|WP_Error Number of videos synced or WP_Error on failure
     */
    public function sync_videos( $max_videos = 0, $full_sync = false ) {
        if ( ! $this->youtube_api->is_configured() ) {
            return new WP_Error( 'not_configured', __( 'YouTube API is not configured. Please configure it in settings.', 'nova-video-manager' ) );
        }

        $synced_count = 0;
        $page_token = '';
        $continue = true;

        // For incremental sync, only fetch videos published after last sync
        $published_after = null;
        if ( ! $full_sync ) {
            $last_sync_time = get_option( 'nvm_last_sync_time', 0 );
            if ( $last_sync_time > 0 ) {
                // YouTube API expects RFC 3339 formatted date-time
                // Subtract 1 hour to account for any timezone issues or videos published during last sync
                $published_after = gmdate( 'Y-m-d\TH:i:s\Z', $last_sync_time - 3600 );
                error_log( 'NVM Sync - Incremental sync: fetching videos published after ' . $published_after );
            } else {
                error_log( 'NVM Sync - No previous sync found, doing full sync' );
            }
        } else {
            error_log( 'NVM Sync - Full sync requested' );
        }

        while ( $continue ) {
            // Get videos from YouTube (using uploads playlist)
            $result = $this->youtube_api->get_channel_videos( 50, $page_token, $published_after );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            $videos = $result['videos'];

            if ( empty( $videos ) ) {
                break;
            }

            // Extract video IDs from search results
            // search.list returns items with id.videoId
            $video_ids = array_map( function( $video ) {
                return $video['id']['videoId'];
            }, $videos );

            // Get detailed video information
            $video_details = $this->youtube_api->get_video_details( $video_ids );

            if ( is_wp_error( $video_details ) ) {
                return $video_details;
            }

            // Process each video
            foreach ( $video_details as $video ) {
                $process_result = $this->process_video( $video );

                if ( ! is_wp_error( $process_result ) ) {
                    $synced_count++;
                }

                // Check if we've reached the max
                if ( $max_videos > 0 && $synced_count >= $max_videos ) {
                    $continue = false;
                    break;
                }
            }

            // Check for next page
            if ( empty( $result['nextPageToken'] ) ) {
                $continue = false;
            } else {
                $page_token = $result['nextPageToken'];
            }

            // If max_videos is set and we've reached it, stop
            if ( $max_videos > 0 && $synced_count >= $max_videos ) {
                $continue = false;
            }
        }

        // Update last sync time
        update_option( 'nvm_last_sync_time', time() );

        error_log( 'NVM Sync - Completed: ' . $synced_count . ' videos synced' );

        return $synced_count;
    }
    
    /**
     * Process a single video
     *
     * @param array $video Video data from YouTube API
     * @return int|WP_Error Post ID on success, WP_Error on failure
     */
    private function process_video( $video ) {
        $video_id = $video['id'];
        $snippet = $video['snippet'];
        $content_details = $video['contentDetails'];
        $statistics = isset( $video['statistics'] ) ? $video['statistics'] : array();
        
        // Check if video already exists
        $existing_post = $this->get_post_by_youtube_id( $video_id );
        
        $post_data = array(
            'post_type'    => NVM_Post_Type::POST_TYPE,
            'post_title'   => $snippet['title'],
            'post_status'  => 'publish',
        );
        
        // Only update description if it hasn't been manually modified
        if ( $existing_post ) {
            $description_modified = get_field( 'nvm_description_modified', $existing_post );
            
            if ( ! $description_modified ) {
                $post_data['post_content'] = $snippet['description'];
            }
            
            $post_data['ID'] = $existing_post;
            $post_id = wp_update_post( $post_data );
        } else {
            $post_data['post_content'] = $snippet['description'];
            $post_id = wp_insert_post( $post_data );
        }
        
        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        // Update ACF fields
        update_field( 'nvm_youtube_id', $video_id, $post_id );
        update_field( 'nvm_youtube_url', 'https://www.youtube.com/watch?v=' . $video_id, $post_id );
        update_field( 'nvm_duration', $content_details['duration'], $post_id );
        update_field( 'nvm_published_at', date( 'Y-m-d H:i:s', strtotime( $snippet['publishedAt'] ) ), $post_id );
        update_field( 'nvm_last_synced', date( 'Y-m-d H:i:s' ), $post_id );

        // Update statistics if available
        if ( isset( $statistics['viewCount'] ) ) {
            update_field( 'nvm_view_count', intval( $statistics['viewCount'] ), $post_id );
        }
        if ( isset( $statistics['likeCount'] ) ) {
            update_field( 'nvm_like_count', intval( $statistics['likeCount'] ), $post_id );
        }
        if ( isset( $statistics['commentCount'] ) ) {
            update_field( 'nvm_comment_count', intval( $statistics['commentCount'] ), $post_id );
        }

        // Handle thumbnail
        $this->set_video_thumbnail( $post_id, $snippet['thumbnails'] );

        // Handle tags
        if ( isset( $snippet['tags'] ) && is_array( $snippet['tags'] ) ) {
            $this->set_video_tags( $post_id, $snippet['tags'] );
        }

        // Handle playlists (categories)
        $this->set_video_categories( $post_id, $video_id );

        // Set video type (Short vs Video)
        $this->set_video_type( $post_id, $content_details['duration'] );

        return $post_id;
    }

    /**
     * Get post by YouTube video ID
     *
     * @param string $youtube_id YouTube video ID
     * @return int|false Post ID if found, false otherwise
     */
    private function get_post_by_youtube_id( $youtube_id ) {
        $args = array(
            'post_type'      => NVM_Post_Type::POST_TYPE,
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'   => 'nvm_youtube_id',
                    'value' => $youtube_id,
                ),
            ),
            'fields'         => 'ids',
        );

        $posts = get_posts( $args );

        return ! empty( $posts ) ? $posts[0] : false;
    }

    /**
     * Set video thumbnail from YouTube thumbnails
     *
     * @param int   $post_id Post ID
     * @param array $thumbnails Thumbnails array from YouTube API
     * @return int|false Attachment ID on success, false on failure
     */
    private function set_video_thumbnail( $post_id, $thumbnails ) {
        // Get the highest quality thumbnail available
        $thumbnail_url = '';

        if ( isset( $thumbnails['maxres']['url'] ) ) {
            $thumbnail_url = $thumbnails['maxres']['url'];
        } elseif ( isset( $thumbnails['high']['url'] ) ) {
            $thumbnail_url = $thumbnails['high']['url'];
        } elseif ( isset( $thumbnails['medium']['url'] ) ) {
            $thumbnail_url = $thumbnails['medium']['url'];
        } elseif ( isset( $thumbnails['default']['url'] ) ) {
            $thumbnail_url = $thumbnails['default']['url'];
        }

        if ( empty( $thumbnail_url ) ) {
            return false;
        }

        // Check if thumbnail already exists
        $existing_thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $existing_thumbnail_id ) {
            // Check if the URL matches
            $existing_url = get_post_meta( $existing_thumbnail_id, '_nvm_thumbnail_url', true );
            if ( $existing_url === $thumbnail_url ) {
                return $existing_thumbnail_id; // No need to update
            }
        }

        // Download and attach the thumbnail
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_sideload_image( $thumbnail_url, $post_id, '', 'id' );

        if ( is_wp_error( $attachment_id ) ) {
            return false;
        }

        // Store the thumbnail URL for future comparison
        update_post_meta( $attachment_id, '_nvm_thumbnail_url', $thumbnail_url );

        // Set as featured image
        set_post_thumbnail( $post_id, $attachment_id );

        return $attachment_id;
    }

    /**
     * Set video tags
     *
     * @param int   $post_id Post ID
     * @param array $tags Array of tag strings
     */
    private function set_video_tags( $post_id, $tags ) {
        if ( empty( $tags ) ) {
            return;
        }

        $tag_ids = array();

        foreach ( $tags as $tag_name ) {
            $term = term_exists( $tag_name, NVM_Taxonomies::TAG_TAXONOMY );

            if ( ! $term ) {
                $term = wp_insert_term( $tag_name, NVM_Taxonomies::TAG_TAXONOMY );
            }

            if ( ! is_wp_error( $term ) ) {
                $tag_ids[] = intval( $term['term_id'] );
            }
        }

        if ( ! empty( $tag_ids ) ) {
            wp_set_object_terms( $post_id, $tag_ids, NVM_Taxonomies::TAG_TAXONOMY );
        }
    }

    /**
     * Set video categories from playlists
     *
     * @param int    $post_id Post ID
     * @param string $video_id YouTube video ID
     */
    private function set_video_categories( $post_id, $video_id ) {
        $playlists = $this->youtube_api->get_video_playlists( $video_id );

        if ( is_wp_error( $playlists ) || empty( $playlists ) ) {
            return;
        }

        $category_ids = array();

        foreach ( $playlists as $playlist ) {
            $term = term_exists( $playlist['title'], NVM_Taxonomies::CATEGORY_TAXONOMY );

            if ( ! $term ) {
                $term = wp_insert_term( $playlist['title'], NVM_Taxonomies::CATEGORY_TAXONOMY );
            }

            if ( ! is_wp_error( $term ) ) {
                $category_ids[] = intval( $term['term_id'] );
            }
        }

        if ( ! empty( $category_ids ) ) {
            wp_set_object_terms( $post_id, $category_ids, NVM_Taxonomies::CATEGORY_TAXONOMY );
        }
    }

    /**
     * Set video type based on duration (Short vs Video)
     * YouTube Shorts are 60 seconds or less
     *
     * @param int    $post_id Post ID
     * @param string $duration ISO 8601 duration string
     */
    private function set_video_type( $post_id, $duration ) {
        // Parse duration to seconds
        $seconds = $this->youtube_api->parse_duration( $duration );

        // YouTube Shorts are 60 seconds or less
        $type_slug = ( $seconds <= 60 ) ? 'short' : 'video';

        // Get the term
        $term = term_exists( $type_slug, NVM_Taxonomies::TYPE_TAXONOMY );

        if ( $term ) {
            wp_set_object_terms( $post_id, intval( $term['term_id'] ), NVM_Taxonomies::TYPE_TAXONOMY );
        }
    }
}

