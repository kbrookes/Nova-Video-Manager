<?php
/**
 * OAuth 2.0 Handler
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NVM_OAuth Class
 * Handles OAuth 2.0 authentication with YouTube/Google
 */
class NVM_OAuth {
    
    /**
     * Single instance of the class
     *
     * @var NVM_OAuth
     */
    private static $instance = null;
    
    /**
     * Google OAuth endpoints
     */
    const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';
    
    /**
     * OAuth scopes needed
     */
    const SCOPES = 'https://www.googleapis.com/auth/youtube.readonly';
    
    /**
     * Get single instance of the class
     *
     * @return NVM_OAuth
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
        // Run callback early (priority 5) to intercept before page renders
        add_action( 'admin_init', array( $this, 'handle_oauth_callback' ), 5 );
    }
    
    /**
     * Check if OAuth is configured
     *
     * @return bool
     */
    public function is_configured() {
        $client_id = get_option( 'nvm_oauth_client_id' );
        $client_secret = $this->get_decrypted_option( 'nvm_oauth_client_secret' );
        
        return ! empty( $client_id ) && ! empty( $client_secret );
    }
    
    /**
     * Check if we have a valid access token
     *
     * @return bool
     */
    public function is_authenticated() {
        $access_token = $this->get_access_token();
        return ! empty( $access_token );
    }
    
    /**
     * Get authorization URL
     *
     * @return string|WP_Error
     */
    public function get_authorization_url() {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'not_configured', __( 'OAuth credentials not configured.', 'nova-video-manager' ) );
        }

        $client_id = get_option( 'nvm_oauth_client_id' );
        $redirect_uri = $this->get_redirect_uri();
        $state = $this->generate_state();

        // Store state for validation (30 minutes to allow for slow authorization)
        // Use user-specific transient to avoid conflicts
        $user_id = get_current_user_id();
        set_transient( 'nvm_oauth_state_' . $user_id, $state, 1800 ); // 30 minutes

        $params = array(
            'client_id'     => $client_id,
            'redirect_uri'  => $redirect_uri,
            'response_type' => 'code',
            'scope'         => self::SCOPES,
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        );

        return self::AUTH_URL . '?' . http_build_query( $params );
    }
    
    /**
     * Get redirect URI
     *
     * @return string
     */
    private function get_redirect_uri() {
        return admin_url( 'edit.php?post_type=' . NVM_Post_Type::POST_TYPE . '&page=nvm-settings' );
    }
    
    /**
     * Generate state parameter for CSRF protection
     *
     * @return string
     */
    private function generate_state() {
        return wp_generate_password( 32, false );
    }
    
    /**
     * Handle OAuth callback
     */
    public function handle_oauth_callback() {
        // Check if this is an OAuth callback
        if ( ! isset( $_GET['code'] ) || ! isset( $_GET['state'] ) ) {
            return;
        }

        // Verify we're on the right page
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== 'nvm-settings' ) {
            return;
        }

        // Debug logging
        error_log( 'NVM OAuth Callback - User logged in: ' . ( is_user_logged_in() ? 'yes' : 'no' ) );
        error_log( 'NVM OAuth Callback - User ID: ' . get_current_user_id() );
        error_log( 'NVM OAuth Callback - Can manage options: ' . ( current_user_can( 'manage_options' ) ? 'yes' : 'no' ) );

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_die( esc_html__( 'You must be logged in to complete OAuth authorization. Please log in and try again.', 'nova-video-manager' ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to configure OAuth settings.', 'nova-video-manager' ) );
        }

        // Verify state parameter
        $state = sanitize_text_field( $_GET['state'] );
        $user_id = get_current_user_id();
        $stored_state = get_transient( 'nvm_oauth_state_' . $user_id );

        error_log( 'NVM OAuth - State received: ' . $state );
        error_log( 'NVM OAuth - State stored: ' . ( $stored_state ? $stored_state : 'NONE' ) );
        error_log( 'NVM OAuth - States match: ' . ( $state === $stored_state ? 'yes' : 'no' ) );

        if ( ! $stored_state || $state !== $stored_state ) {
            // Debug info for troubleshooting
            $debug_msg = sprintf(
                'State validation failed. Stored: %s, Received: %s, User ID: %d',
                $stored_state ? 'exists' : 'missing',
                $state ? 'exists' : 'missing',
                $user_id
            );
            error_log( 'NVM OAuth Error: ' . $debug_msg );
            wp_die( esc_html__( 'Invalid state parameter. Please try connecting again.', 'nova-video-manager' ) );
        }

        error_log( 'NVM OAuth - State validation passed' );

        // Delete state transient
        delete_transient( 'nvm_oauth_state_' . $user_id );

        // Exchange authorization code for tokens
        $code = sanitize_text_field( $_GET['code'] );
        error_log( 'NVM OAuth - Exchanging code for tokens...' );
        $result = $this->exchange_code_for_tokens( $code );

        if ( is_wp_error( $result ) ) {
            error_log( 'NVM OAuth Error - Token exchange failed: ' . $result->get_error_message() );
            wp_die( esc_html( $result->get_error_message() ) );
        }

        error_log( 'NVM OAuth - Token exchange successful, redirecting...' );

        // Redirect to settings page without query params
        wp_safe_redirect( admin_url( 'edit.php?post_type=' . NVM_Post_Type::POST_TYPE . '&page=nvm-settings&oauth=success' ) );
        exit;
    }

    /**
     * Exchange authorization code for access and refresh tokens
     *
     * @param string $code Authorization code
     * @return bool|WP_Error
     */
    private function exchange_code_for_tokens( $code ) {
        $client_id = get_option( 'nvm_oauth_client_id' );
        $client_secret = $this->get_decrypted_option( 'nvm_oauth_client_secret' );
        $redirect_uri = $this->get_redirect_uri();

        error_log( 'NVM OAuth - Client ID: ' . substr( $client_id, 0, 20 ) . '...' );
        error_log( 'NVM OAuth - Client Secret exists: ' . ( ! empty( $client_secret ) ? 'yes' : 'no' ) );
        error_log( 'NVM OAuth - Client Secret (first 10 chars): ' . substr( $client_secret, 0, 10 ) . '...' );
        error_log( 'NVM OAuth - Client Secret length: ' . strlen( $client_secret ) );
        error_log( 'NVM OAuth - Redirect URI: ' . $redirect_uri );

        $response = wp_remote_post( self::TOKEN_URL, array(
            'body' => array(
                'code'          => $code,
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => $redirect_uri,
                'grant_type'    => 'authorization_code',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            error_log( 'NVM OAuth - WP Error: ' . $response->get_error_message() );
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        error_log( 'NVM OAuth - Response code: ' . $response_code );
        error_log( 'NVM OAuth - Response body: ' . wp_remote_retrieve_body( $response ) );

        if ( isset( $body['error'] ) ) {
            $error_msg = $body['error_description'] ?? $body['error'];
            error_log( 'NVM OAuth - Google error: ' . $error_msg );
            return new WP_Error( 'oauth_error', $error_msg );
        }

        // Store tokens securely
        $this->store_tokens( $body );

        return true;
    }

    /**
     * Store OAuth tokens
     *
     * @param array $tokens Token data from OAuth response
     */
    private function store_tokens( $tokens ) {
        if ( isset( $tokens['access_token'] ) ) {
            $this->set_encrypted_option( 'nvm_oauth_access_token', $tokens['access_token'] );
        }

        if ( isset( $tokens['refresh_token'] ) ) {
            $this->set_encrypted_option( 'nvm_oauth_refresh_token', $tokens['refresh_token'] );
        }

        if ( isset( $tokens['expires_in'] ) ) {
            $expires_at = time() + intval( $tokens['expires_in'] );
            update_option( 'nvm_oauth_expires_at', $expires_at );
        }

        // Store authentication timestamp
        update_option( 'nvm_oauth_authenticated_at', time() );
    }

    /**
     * Get access token (refresh if needed)
     *
     * @return string|false
     */
    public function get_access_token() {
        $access_token = $this->get_decrypted_option( 'nvm_oauth_access_token' );
        $expires_at = get_option( 'nvm_oauth_expires_at', 0 );

        // Check if token is expired or about to expire (5 minute buffer)
        if ( $expires_at && ( time() + 300 ) >= $expires_at ) {
            $this->refresh_access_token();
            $access_token = $this->get_decrypted_option( 'nvm_oauth_access_token' );
        }

        return $access_token;
    }

    /**
     * Refresh access token using refresh token
     *
     * @return bool|WP_Error
     */
    private function refresh_access_token() {
        $refresh_token = $this->get_decrypted_option( 'nvm_oauth_refresh_token' );

        if ( ! $refresh_token ) {
            return new WP_Error( 'no_refresh_token', __( 'No refresh token available.', 'nova-video-manager' ) );
        }

        $client_id = get_option( 'nvm_oauth_client_id' );
        $client_secret = $this->get_decrypted_option( 'nvm_oauth_client_secret' );

        $response = wp_remote_post( self::TOKEN_URL, array(
            'body' => array(
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
                'grant_type'    => 'refresh_token',
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( isset( $body['error'] ) ) {
            return new WP_Error( 'refresh_error', $body['error_description'] ?? $body['error'] );
        }

        // Store new access token
        $this->store_tokens( $body );

        return true;
    }

    /**
     * Disconnect/revoke OAuth access
     *
     * @return bool|WP_Error
     */
    public function disconnect() {
        $access_token = $this->get_decrypted_option( 'nvm_oauth_access_token' );

        if ( $access_token ) {
            // Revoke token with Google
            wp_remote_post( self::REVOKE_URL, array(
                'body' => array(
                    'token' => $access_token,
                ),
            ) );
        }

        // Delete all OAuth data
        delete_option( 'nvm_oauth_access_token' );
        delete_option( 'nvm_oauth_refresh_token' );
        delete_option( 'nvm_oauth_expires_at' );
        delete_option( 'nvm_oauth_authenticated_at' );

        return true;
    }

    /**
     * Encrypt and store an option
     *
     * @param string $option_name Option name
     * @param string $value Value to encrypt and store
     */
    private function set_encrypted_option( $option_name, $value ) {
        $encrypted = $this->encrypt( $value );
        update_option( $option_name, $encrypted );
    }

    /**
     * Get and decrypt an option
     *
     * @param string $option_name Option name
     * @return string|false
     */
    private function get_decrypted_option( $option_name ) {
        $encrypted = get_option( $option_name );

        if ( ! $encrypted ) {
            return false;
        }

        return $this->decrypt( $encrypted );
    }

    /**
     * Encrypt a value
     *
     * @param string $value Value to encrypt
     * @return string
     */
    private function encrypt( $value ) {
        // Use WordPress salts as encryption key
        $key = $this->get_encryption_key();

        // Use openssl for encryption
        $iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
        $iv = openssl_random_pseudo_bytes( $iv_length );

        $encrypted = openssl_encrypt( $value, 'aes-256-cbc', $key, 0, $iv );

        // Combine IV and encrypted data
        return base64_encode( $iv . $encrypted );
    }

    /**
     * Decrypt a value
     *
     * @param string $encrypted Encrypted value
     * @return string|false
     */
    private function decrypt( $encrypted ) {
        $key = $this->get_encryption_key();

        $data = base64_decode( $encrypted );

        if ( ! $data ) {
            return false;
        }

        $iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
        $iv = substr( $data, 0, $iv_length );
        $encrypted_data = substr( $data, $iv_length );

        return openssl_decrypt( $encrypted_data, 'aes-256-cbc', $key, 0, $iv );
    }

    /**
     * Get encryption key from WordPress salts
     *
     * @return string
     */
    private function get_encryption_key() {
        // Use WordPress authentication salts as encryption key
        return hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY );
    }
}
