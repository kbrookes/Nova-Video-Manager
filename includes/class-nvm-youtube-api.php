<?php
/**
 * YouTube API Integration
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NVM_YouTube_API Class
 */
class NVM_YouTube_API {
    
    /**
     * Single instance of the class
     *
     * @var NVM_YouTube_API
     */
    private static $instance = null;
    
    /**
     * YouTube API base URL
     *
     * @var string
     */
    const API_BASE_URL = 'https://www.googleapis.com/youtube/v3';
    
    /**
     * OAuth instance
     *
     * @var NVM_OAuth
     */
    private $oauth;

    /**
     * Channel ID
     *
     * @var string
     */
    private $channel_id;

    /**
     * Uploads playlist ID cache
     *
     * @var string
     */
    private $uploads_playlist_id;

    /**
     * Get single instance of the class
     *
     * @return NVM_YouTube_API
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
        $this->oauth = NVM_OAuth::get_instance();
        $this->channel_id = get_option( 'nvm_youtube_channel_id', '' );
    }

    /**
     * Check if API is configured
     *
     * @return bool
     */
    public function is_configured() {
        return $this->oauth->is_authenticated() && ! empty( $this->channel_id );
    }
    
    /**
     * Get videos from channel using uploads playlist
     *
     * @param int $max_results Maximum number of results to fetch
     * @param string $page_token Page token for pagination
     * @param string|null $published_after RFC 3339 formatted date-time to fetch videos published after this time
     * @return array|WP_Error Array of videos or WP_Error on failure
     */
    public function get_channel_videos( $max_results = 50, $page_token = '', $published_after = null ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'not_configured', __( 'YouTube API is not configured. Please authenticate with OAuth.', 'nova-video-manager' ) );
        }

        // Debug: Log the channel ID being used
        error_log( 'NVM YouTube API - Using channel ID: ' . $this->channel_id );

        // IMPORTANT: search.list with channelId doesn't work reliably with OAuth - it returns videos from other channels!
        // Instead, we use the channel's "uploads" playlist which contains ALL videos (public, unlisted, private)
        // This is the recommended approach per YouTube API documentation

        // Get the uploads playlist ID for this channel
        $uploads_playlist_id = $this->get_uploads_playlist_id();

        if ( is_wp_error( $uploads_playlist_id ) ) {
            return $uploads_playlist_id;
        }

        error_log( 'NVM YouTube API - Using uploads playlist ID: ' . $uploads_playlist_id );

        // Use playlistItems.list to get videos from the uploads playlist
        $params = array(
            'part' => 'snippet',
            'playlistId' => $uploads_playlist_id,
            'maxResults' => min( $max_results, 50 ), // YouTube API max is 50
        );

        if ( ! empty( $page_token ) ) {
            $params['pageToken'] = $page_token;
        }

        $url = add_query_arg( $params, self::API_BASE_URL . '/playlistItems' );

        // Debug: Log the full API URL (without access token)
        error_log( 'NVM YouTube API - PlaylistItems URL: ' . $url );

        // Make authenticated request
        $response = $this->make_authenticated_request( $url );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: %s: error message from YouTube API */
                    __( 'YouTube API Error: %s', 'nova-video-manager' ),
                    $data['error']['message']
                )
            );
        }

        if ( ! isset( $data['items'] ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid response from YouTube API.', 'nova-video-manager' ) );
        }

        error_log( 'NVM YouTube API - Fetched ' . count( $data['items'] ) . ' videos from uploads playlist' );

        // playlistItems.list returns items with snippet.resourceId.videoId instead of id.videoId
        // We need to transform the response to match what the sync expects (search.list format)
        $videos = array();
        foreach ( $data['items'] as $item ) {
            // Skip if publishedAfter filter is set and video is older
            if ( ! empty( $published_after ) ) {
                $published_at = strtotime( $item['snippet']['publishedAt'] );
                $filter_time = strtotime( $published_after );
                if ( $published_at < $filter_time ) {
                    continue; // Skip this video
                }
            }

            // Transform playlistItem format to search.list format
            // search.list returns: { id: { videoId: "xxx" }, snippet: {...} }
            // playlistItems.list returns: { snippet: { resourceId: { videoId: "xxx" }, ... } }
            $video = array(
                'id' => array(
                    'videoId' => $item['snippet']['resourceId']['videoId'],
                ),
                'snippet' => $item['snippet'],
            );

            $videos[] = $video;

            // Debug: Log video details
            $video_title = isset( $item['snippet']['title'] ) ? $item['snippet']['title'] : 'UNKNOWN';
            $video_channel_id = isset( $item['snippet']['channelId'] ) ? $item['snippet']['channelId'] : 'UNKNOWN';
            error_log( 'NVM YouTube API - Video: "' . $video_title . '" from channel: ' . $video_channel_id );

            // Alert if video is from wrong channel (should never happen with uploads playlist)
            if ( $video_channel_id !== $this->channel_id ) {
                error_log( 'NVM YouTube API - WARNING: Video from DIFFERENT channel! Expected: ' . $this->channel_id . ', Got: ' . $video_channel_id );
            }
        }

        return array(
            'videos' => $videos,
            'nextPageToken' => isset( $data['nextPageToken'] ) ? $data['nextPageToken'] : '',
            'totalResults' => isset( $data['pageInfo']['totalResults'] ) ? $data['pageInfo']['totalResults'] : 0,
        );
    }
    
    /**
     * Get detailed video information
     *
     * @param array $video_ids Array of video IDs
     * @return array|WP_Error Array of video details or WP_Error on failure
     */
    public function get_video_details( $video_ids ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'not_configured', __( 'YouTube API is not configured.', 'nova-video-manager' ) );
        }

        if ( empty( $video_ids ) ) {
            return array();
        }

        $params = array(
            'part' => 'snippet,contentDetails,statistics,status',
            'id' => implode( ',', $video_ids ),
        );

        $url = add_query_arg( $params, self::API_BASE_URL . '/videos' );

        $response = $this->make_authenticated_request( $url );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: %s: error message from YouTube API */
                    __( 'YouTube API Error: %s', 'nova-video-manager' ),
                    $data['error']['message']
                )
            );
        }

        if ( ! isset( $data['items'] ) ) {
            return new WP_Error( 'invalid_response', __( 'Invalid response from YouTube API.', 'nova-video-manager' ) );
        }

        return $data['items'];
    }

    /**
     * Get playlists for a video
     *
     * @param string $video_id Video ID
     * @return array|WP_Error Array of playlists or WP_Error on failure
     */
    public function get_video_playlists( $video_id ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'not_configured', __( 'YouTube API is not configured.', 'nova-video-manager' ) );
        }

        // First, get all playlists for the channel
        $params = array(
            'part' => 'snippet',
            'channelId' => $this->channel_id,
            'maxResults' => 50,
        );

        $url = add_query_arg( $params, self::API_BASE_URL . '/playlists' );

        $response = $this->make_authenticated_request( $url );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: %s: error message from YouTube API */
                    __( 'YouTube API Error: %s', 'nova-video-manager' ),
                    $data['error']['message']
                )
            );
        }

        if ( ! isset( $data['items'] ) ) {
            return array();
        }

        $playlists = array();

        // Check each playlist to see if it contains the video
        foreach ( $data['items'] as $playlist ) {
            $playlist_id = $playlist['id'];

            // Check if video is in this playlist
            $check_params = array(
                'part' => 'snippet',
                'playlistId' => $playlist_id,
                'videoId' => $video_id,
            );

            $check_url = add_query_arg( $check_params, self::API_BASE_URL . '/playlistItems' );
            $check_response = $this->make_authenticated_request( $check_url );

            if ( ! is_wp_error( $check_response ) ) {
                $check_body = wp_remote_retrieve_body( $check_response );
                $check_data = json_decode( $check_body, true );

                if ( isset( $check_data['items'] ) && ! empty( $check_data['items'] ) ) {
                    $playlists[] = array(
                        'id' => $playlist_id,
                        'title' => $playlist['snippet']['title'],
                    );
                }
            }
        }

        return $playlists;
    }

    /**
     * Get channel's uploads playlist ID
     *
     * @return string|WP_Error Uploads playlist ID or WP_Error on failure
     */
    private function get_uploads_playlist_id() {
        // Check cache
        if ( ! empty( $this->uploads_playlist_id ) ) {
            return $this->uploads_playlist_id;
        }

        // Check transient cache
        $cached = get_transient( 'nvm_uploads_playlist_id_' . $this->channel_id );
        if ( $cached ) {
            $this->uploads_playlist_id = $cached;
            return $cached;
        }

        // Fetch from API
        $params = array(
            'part' => 'contentDetails',
            'id' => $this->channel_id,
        );

        $url = add_query_arg( $params, self::API_BASE_URL . '/channels' );

        $response = $this->make_authenticated_request( $url );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( isset( $data['error'] ) ) {
            return new WP_Error(
                'api_error',
                sprintf(
                    /* translators: %s: error message from YouTube API */
                    __( 'YouTube API Error: %s', 'nova-video-manager' ),
                    $data['error']['message']
                )
            );
        }

        if ( ! isset( $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ) ) {
            return new WP_Error( 'no_uploads_playlist', __( 'Could not find uploads playlist for channel.', 'nova-video-manager' ) );
        }

        $uploads_playlist_id = $data['items'][0]['contentDetails']['relatedPlaylists']['uploads'];

        // Cache for 24 hours
        set_transient( 'nvm_uploads_playlist_id_' . $this->channel_id, $uploads_playlist_id, DAY_IN_SECONDS );

        $this->uploads_playlist_id = $uploads_playlist_id;

        return $uploads_playlist_id;
    }

    /**
     * Make authenticated API request with OAuth token
     *
     * @param string $url API endpoint URL
     * @return array|WP_Error Response array or WP_Error on failure
     */
    private function make_authenticated_request( $url ) {
        $access_token = $this->oauth->get_access_token();

        if ( is_wp_error( $access_token ) ) {
            return $access_token;
        }

        $response = wp_remote_get( $url, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ) );

        return $response;
    }

    /**
     * Parse video duration from ISO 8601 format to seconds
     *
     * @param string $duration ISO 8601 duration string
     * @return int Duration in seconds
     */
    public function parse_duration( $duration ) {
        $interval = new DateInterval( $duration );
        return ( $interval->h * 3600 ) + ( $interval->i * 60 ) + $interval->s;
    }

    /**
     * Format duration in human-readable format
     *
     * @param string $duration ISO 8601 duration string
     * @return string Formatted duration (e.g., "1:23:45" or "12:34")
     */
    public function format_duration( $duration ) {
        $seconds = $this->parse_duration( $duration );

        $hours = floor( $seconds / 3600 );
        $minutes = floor( ( $seconds % 3600 ) / 60 );
        $secs = $seconds % 60;

        if ( $hours > 0 ) {
            return sprintf( '%d:%02d:%02d', $hours, $minutes, $secs );
        } else {
            return sprintf( '%d:%02d', $minutes, $secs );
        }
    }
}

