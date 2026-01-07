<?php
/**
 * Settings Page
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NVM_Settings Class
 */
class NVM_Settings {
    
    /**
     * Single instance of the class
     *
     * @var NVM_Settings
     */
    private static $instance = null;
    
    /**
     * Settings page slug
     *
     * @var string
     */
    const PAGE_SLUG = 'nvm-settings';
    
    /**
     * Get single instance of the class
     *
     * @return NVM_Settings
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
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_init', array( $this, 'handle_oauth_disconnect' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'wp_ajax_nvm_manual_sync', array( $this, 'handle_manual_sync' ) );
        add_action( 'wp_ajax_nvm_incremental_sync', array( $this, 'handle_incremental_sync' ) );
    }
    
    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=' . NVM_Post_Type::POST_TYPE,
            __( 'Nova Video Manager Settings', 'nova-video-manager' ),
            __( 'Settings', 'nova-video-manager' ),
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'render_settings_page' )
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register all settings under one group
        register_setting( 'nvm_settings', 'nvm_oauth_client_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'nvm_settings', 'nvm_oauth_client_secret', array(
            'type' => 'string',
            'sanitize_callback' => array( $this, 'sanitize_client_secret' ),
        ) );
        register_setting( 'nvm_settings', 'nvm_youtube_channel_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ) );
        register_setting( 'nvm_settings', 'nvm_auto_sync', array(
            'type' => 'boolean',
            'default' => false,
        ) );
        register_setting( 'nvm_settings', 'nvm_sync_frequency', array(
            'type' => 'string',
            'default' => 'hourly',
        ) );

        // OAuth Section
        add_settings_section(
            'nvm_oauth_section',
            __( 'YouTube OAuth Configuration', 'nova-video-manager' ),
            array( $this, 'render_oauth_section' ),
            'nvm_settings'
        );

        add_settings_field(
            'nvm_oauth_client_id',
            __( 'OAuth Client ID', 'nova-video-manager' ),
            array( $this, 'render_oauth_client_id_field' ),
            'nvm_settings',
            'nvm_oauth_section'
        );

        add_settings_field(
            'nvm_oauth_client_secret',
            __( 'OAuth Client Secret', 'nova-video-manager' ),
            array( $this, 'render_oauth_client_secret_field' ),
            'nvm_settings',
            'nvm_oauth_section'
        );

        add_settings_field(
            'nvm_oauth_status',
            __( 'Authentication Status', 'nova-video-manager' ),
            array( $this, 'render_oauth_status_field' ),
            'nvm_settings',
            'nvm_oauth_section'
        );

        // YouTube Channel Section
        add_settings_section(
            'nvm_youtube_section',
            __( 'YouTube Channel', 'nova-video-manager' ),
            array( $this, 'render_youtube_section' ),
            'nvm_settings'
        );

        add_settings_field(
            'nvm_youtube_channel_id',
            __( 'YouTube Channel ID', 'nova-video-manager' ),
            array( $this, 'render_channel_id_field' ),
            'nvm_settings',
            'nvm_youtube_section'
        );

        // Sync Settings Section
        add_settings_section(
            'nvm_sync_section',
            __( 'Sync Settings', 'nova-video-manager' ),
            array( $this, 'render_sync_section' ),
            'nvm_settings'
        );

        add_settings_field(
            'nvm_auto_sync',
            __( 'Enable Auto Sync', 'nova-video-manager' ),
            array( $this, 'render_auto_sync_field' ),
            'nvm_settings',
            'nvm_sync_section'
        );

        add_settings_field(
            'nvm_sync_frequency',
            __( 'Sync Frequency', 'nova-video-manager' ),
            array( $this, 'render_sync_frequency_field' ),
            'nvm_settings',
            'nvm_sync_section'
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts( $hook ) {
        if ( 'nova_video_page_' . self::PAGE_SLUG !== $hook ) {
            return;
        }
        
        wp_enqueue_style(
            'nvm-admin-styles',
            NVM_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            NVM_VERSION
        );
        
        wp_enqueue_script(
            'nvm-admin-scripts',
            NVM_PLUGIN_URL . 'assets/js/admin.js',
            array( 'jquery' ),
            NVM_VERSION,
            true
        );
        
        wp_localize_script(
            'nvm-admin-scripts',
            'nvmAdmin',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'manualSyncNonce'   => wp_create_nonce( 'nvm_manual_sync' ),
                'incrementalSyncNonce'   => wp_create_nonce( 'nvm_incremental_sync' ),
            )
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'nova-video-manager' ) );
        }

        // Handle settings save
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error(
                'nvm_messages',
                'nvm_message',
                __( 'Settings saved successfully.', 'nova-video-manager' ),
                'updated'
            );
        }

        settings_errors( 'nvm_messages' );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <div class="nvm-settings-container">
                <div class="nvm-settings-main">
                    <form action="options.php" method="post">
                        <?php
                        settings_fields( 'nvm_settings' );
                        do_settings_sections( 'nvm_settings' );
                        submit_button( __( 'Save Settings', 'nova-video-manager' ) );
                        ?>
                    </form>
                </div>

                <div class="nvm-settings-sidebar">
                    <div class="nvm-sync-controls">
                        <h2><?php esc_html_e( 'Manual Sync', 'nova-video-manager' ); ?></h2>
                        <p><?php esc_html_e( 'Manually trigger a sync of videos from YouTube.', 'nova-video-manager' ); ?></p>
                        <div style="margin-bottom: 10px;">
                            <button type="button" id="nvm-incremental-sync-btn" class="button button-secondary" style="margin-right: 5px;">
                                <?php esc_html_e( 'Sync New', 'nova-video-manager' ); ?>
                            </button>
                            <button type="button" id="nvm-full-sync-btn" class="button button-primary">
                                <?php esc_html_e( 'Full Sync', 'nova-video-manager' ); ?>
                            </button>
                        </div>
                        <p class="description">
                            <?php esc_html_e( 'Sync New: Only fetch videos published since last sync (fast)', 'nova-video-manager' ); ?><br>
                            <?php esc_html_e( 'Full Sync: Fetch all videos from your channel (slow)', 'nova-video-manager' ); ?>
                        </p>
                        <div id="nvm-sync-status"></div>
                    </div>

                    <div class="nvm-sync-info">
                        <h3><?php esc_html_e( 'Last Sync', 'nova-video-manager' ); ?></h3>
                        <?php
                        $last_sync = get_option( 'nvm_last_sync_time' );
                        if ( $last_sync ) {
                            echo '<p>' . esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_sync ) ) . '</p>';
                        } else {
                            echo '<p>' . esc_html__( 'Never', 'nova-video-manager' ) . '</p>';
                        }
                        ?>

                        <h3><?php esc_html_e( 'Total Videos', 'nova-video-manager' ); ?></h3>
                        <?php
                        $video_count = wp_count_posts( NVM_Post_Type::POST_TYPE );
                        echo '<p>' . esc_html( $video_count->publish ) . '</p>';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render OAuth section description
     */
    public function render_oauth_section() {
        $redirect_uri = admin_url( 'edit.php?post_type=' . NVM_Post_Type::POST_TYPE . '&page=nvm-settings' );
        $redirect_uri = apply_filters( 'nvm_oauth_redirect_uri', $redirect_uri );

        echo '<p>' . esc_html__( 'Configure OAuth 2.0 credentials to access your YouTube channel data, including unlisted videos and playlists.', 'nova-video-manager' ) . '</p>';
        echo '<p>' . sprintf(
            /* translators: %s: URL to Google Cloud Console */
            esc_html__( 'Create OAuth 2.0 credentials in the %s. Set the redirect URI to: %s', 'nova-video-manager' ),
            '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">' . esc_html__( 'Google Cloud Console', 'nova-video-manager' ) . '</a>',
            '<code>' . esc_html( $redirect_uri ) . '</code>'
        ) . '</p>';
        echo '<p class="description">' . esc_html__( 'Important: The redirect URI in Google Cloud Console must match exactly (including http/https and trailing slashes).', 'nova-video-manager' ) . '</p>';
    }

    /**
     * Render OAuth Client ID field
     */
    public function render_oauth_client_id_field() {
        $value = get_option( 'nvm_oauth_client_id', '' );
        ?>
        <input type="text"
               name="nvm_oauth_client_id"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text"
               placeholder="<?php esc_attr_e( 'Your OAuth 2.0 Client ID', 'nova-video-manager' ); ?>" />
        <p class="description">
            <?php esc_html_e( 'OAuth 2.0 Client ID from Google Cloud Console', 'nova-video-manager' ); ?>
        </p>
        <?php
    }

    /**
     * Render OAuth Client Secret field
     */
    public function render_oauth_client_secret_field() {
        $oauth = NVM_OAuth::get_instance();
        $has_secret = ! empty( get_option( 'nvm_oauth_client_secret' ) );
        ?>
        <input type="password"
               name="nvm_oauth_client_secret"
               value=""
               class="regular-text"
               placeholder="<?php echo $has_secret ? esc_attr__( '••••••••••••', 'nova-video-manager' ) : esc_attr__( 'Your OAuth 2.0 Client Secret', 'nova-video-manager' ); ?>" />
        <p class="description">
            <?php
            if ( $has_secret ) {
                esc_html_e( 'Client secret is set (encrypted). Leave blank to keep current secret.', 'nova-video-manager' );
            } else {
                esc_html_e( 'OAuth 2.0 Client Secret from Google Cloud Console', 'nova-video-manager' );
            }
            ?>
        </p>
        <?php
    }

    /**
     * Render OAuth status and connect button
     */
    public function render_oauth_status_field() {
        $oauth = NVM_OAuth::get_instance();

        if ( $oauth->is_authenticated() ) {
            $authenticated_at = get_option( 'nvm_oauth_authenticated_at' );
            $token_status = $oauth->get_token_status();
            ?>
            <div class="nvm-oauth-status nvm-oauth-connected">
                <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                <strong><?php esc_html_e( 'Connected to YouTube', 'nova-video-manager' ); ?></strong>
                <?php if ( $authenticated_at ) : ?>
                    <p class="description">
                        <?php
                        /* translators: %s: formatted date/time */
                        printf( esc_html__( 'Authenticated on %s', 'nova-video-manager' ), esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $authenticated_at ) ) );
                        ?>
                    </p>
                <?php endif; ?>

                <?php if ( ! empty( $token_status['warning'] ) ) : ?>
                    <div class="notice notice-warning inline" style="margin: 10px 0; padding: 8px 12px;">
                        <p style="margin: 0;">
                            <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                            <strong><?php esc_html_e( 'Warning:', 'nova-video-manager' ); ?></strong>
                            <?php echo esc_html( $token_status['warning'] ); ?>
                        </p>
                        <p style="margin: 5px 0 0 0;">
                            <?php esc_html_e( 'If you experience sync issues, disconnect and reconnect to refresh your authentication.', 'nova-video-manager' ); ?>
                        </p>
                    </div>
                <?php endif; ?>

                <p>
                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=' . NVM_Post_Type::POST_TYPE . '&page=nvm-settings&action=nvm_oauth_disconnect' ), 'nvm_oauth_disconnect' ) ); ?>"
                       class="button button-secondary"
                       onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to disconnect from YouTube?', 'nova-video-manager' ); ?>');">
                        <?php esc_html_e( 'Disconnect', 'nova-video-manager' ); ?>
                    </a>
                </p>
            </div>
            <?php
        } elseif ( $oauth->is_configured() ) {
            $auth_url = $oauth->get_authorization_url();
            if ( ! is_wp_error( $auth_url ) ) {
                ?>
                <div class="nvm-oauth-status nvm-oauth-disconnected">
                    <span class="dashicons dashicons-warning" style="color: #f0b849;"></span>
                    <strong><?php esc_html_e( 'Not Connected', 'nova-video-manager' ); ?></strong>
                    <p class="description"><?php esc_html_e( 'Click the button below to connect to your YouTube account.', 'nova-video-manager' ); ?></p>
                    <p>
                        <a href="<?php echo esc_url( $auth_url ); ?>" class="button button-primary">
                            <?php esc_html_e( 'Connect to YouTube', 'nova-video-manager' ); ?>
                        </a>
                    </p>
                </div>
                <?php
            }
        } else {
            ?>
            <div class="nvm-oauth-status nvm-oauth-not-configured">
                <span class="dashicons dashicons-info" style="color: #72aee6;"></span>
                <strong><?php esc_html_e( 'Not Configured', 'nova-video-manager' ); ?></strong>
                <p class="description"><?php esc_html_e( 'Enter your OAuth Client ID and Client Secret above, then save settings.', 'nova-video-manager' ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Sanitize client secret (encrypt if provided)
     *
     * @param string $value Client secret value
     * @return string
     */
    public function sanitize_client_secret( $value ) {
        // If empty, keep existing value
        if ( empty( $value ) ) {
            return get_option( 'nvm_oauth_client_secret', '' );
        }

        // Sanitize and store the value directly (OAuth class will handle encryption when retrieving)
        // Actually, we need to encrypt it here for storage
        $sanitized = sanitize_text_field( $value );

        // Use the same encryption method as OAuth class
        $key = hash( 'sha256', AUTH_KEY . SECURE_AUTH_KEY . LOGGED_IN_KEY . NONCE_KEY );
        $iv_length = openssl_cipher_iv_length( 'aes-256-cbc' );
        $iv = openssl_random_pseudo_bytes( $iv_length );
        $encrypted = openssl_encrypt( $sanitized, 'aes-256-cbc', $key, 0, $iv );

        return base64_encode( $iv . $encrypted );
    }

    /**
     * Render YouTube section description
     */
    public function render_youtube_section() {
        echo '<p>' . esc_html__( 'Specify which YouTube channel to sync videos from.', 'nova-video-manager' ) . '</p>';
    }

    /**
     * Render API key field
     */
    public function render_api_key_field() {
        $value = get_option( 'nvm_youtube_api_key', '' );
        ?>
        <input type="text"
               name="nvm_youtube_api_key"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text"
               placeholder="<?php esc_attr_e( 'Enter your YouTube API key', 'nova-video-manager' ); ?>">
        <p class="description">
            <?php esc_html_e( 'Your YouTube Data API v3 key.', 'nova-video-manager' ); ?>
        </p>
        <?php
    }

    /**
     * Render channel ID field
     */
    public function render_channel_id_field() {
        $value = get_option( 'nvm_youtube_channel_id', '' );
        ?>
        <input type="text"
               name="nvm_youtube_channel_id"
               value="<?php echo esc_attr( $value ); ?>"
               class="regular-text"
               placeholder="<?php esc_attr_e( 'Enter your YouTube channel ID', 'nova-video-manager' ); ?>">
        <p class="description">
            <?php esc_html_e( 'The ID of the YouTube channel to sync videos from.', 'nova-video-manager' ); ?>
        </p>
        <?php
    }

    /**
     * Render sync section description
     */
    public function render_sync_section() {
        echo '<p>' . esc_html__( 'Configure how often videos should be synced from YouTube.', 'nova-video-manager' ) . '</p>';
    }

    /**
     * Render auto sync field
     */
    public function render_auto_sync_field() {
        $value = get_option( 'nvm_auto_sync', false );
        ?>
        <label>
            <input type="checkbox"
                   name="nvm_auto_sync"
                   value="1"
                   <?php checked( $value, true ); ?>>
            <?php esc_html_e( 'Automatically sync videos on a schedule', 'nova-video-manager' ); ?>
        </label>
        <?php
    }

    /**
     * Render sync frequency field
     */
    public function render_sync_frequency_field() {
        $value = get_option( 'nvm_sync_frequency', 'hourly' );
        $schedules = array(
            'hourly'     => __( 'Hourly', 'nova-video-manager' ),
            'twicedaily' => __( 'Twice Daily', 'nova-video-manager' ),
            'daily'      => __( 'Daily', 'nova-video-manager' ),
        );
        ?>
        <select name="nvm_sync_frequency">
            <?php foreach ( $schedules as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $value, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Handle manual sync AJAX request (full sync)
     */
    public function handle_manual_sync() {
        check_ajax_referer( 'nvm_manual_sync', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'nova-video-manager' ) ) );
        }

        // Manual sync is always a full sync (not incremental)
        $sync = NVM_Sync::get_instance();
        $result = $sync->sync_videos( 0, true );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: %d: number of videos synced */
                __( 'Full sync completed: %d videos synced.', 'nova-video-manager' ),
                $result
            )
        ) );
    }

    /**
     * Handle incremental sync AJAX request
     */
    public function handle_incremental_sync() {
        check_ajax_referer( 'nvm_incremental_sync', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'nova-video-manager' ) ) );
        }

        // Incremental sync - only fetch new videos
        $sync = NVM_Sync::get_instance();
        $result = $sync->sync_videos( 0, false );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ) );
        }

        wp_send_json_success( array(
            'message' => sprintf(
                /* translators: %d: number of videos synced */
                __( 'Incremental sync completed: %d new videos synced.', 'nova-video-manager' ),
                $result
            )
        ) );
    }

    /**
     * Handle OAuth disconnect action
     */
    public function handle_oauth_disconnect() {
        // Check if this is a disconnect request
        if ( ! isset( $_GET['action'] ) || $_GET['action'] !== 'nvm_oauth_disconnect' ) {
            return;
        }

        // Verify we're on the right page
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::PAGE_SLUG ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'nvm_oauth_disconnect' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'nova-video-manager' ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'nova-video-manager' ) );
        }

        // Disconnect OAuth
        $oauth = NVM_OAuth::get_instance();
        $oauth->disconnect();

        // Redirect back to settings page
        wp_safe_redirect( admin_url( 'edit.php?post_type=' . NVM_Post_Type::POST_TYPE . '&page=' . self::PAGE_SLUG . '&oauth=disconnected' ) );
        exit;
    }
}
