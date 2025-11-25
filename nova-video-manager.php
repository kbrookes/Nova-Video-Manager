<?php
/**
 * Plugin Name: Nova Video Manager
 * Plugin URI: https://github.com/kbrookes/Nova-Video-Manager
 * Description: Automatically syncs YouTube videos from a channel and manages them as WordPress content with full metadata support.
 * Version: 0.2.1
 * Author: Kelsey Brookes
 * Author URI: https://lovedlockedloaded.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nova-video-manager
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Update URI: https://github.com/kbrookes/Nova-Video-Manager
 * GitHub Plugin URI: https://github.com/kbrookes/Nova-Video-Manager
 * Primary Branch: main
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'NVM_VERSION', '0.2.1' );
define( 'NVM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NVM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NVM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Nova Video Manager Class
 */
class Nova_Video_Manager {
    
    /**
     * Single instance of the class
     *
     * @var Nova_Video_Manager
     */
    private static $instance = null;
    
    /**
     * Get single instance of the class
     *
     * @return Nova_Video_Manager
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
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'admin_notices', array( $this, 'check_acf_and_show_notice' ) );
        add_action( 'init', array( $this, 'init' ), 15 ); // Priority 15 to ensure ACF is loaded first
        add_action( 'nvm_sync_videos', array( $this, 'run_scheduled_sync' ) );
        add_action( 'update_option_nvm_auto_sync', array( $this, 'handle_auto_sync_change' ), 10, 2 );
        add_action( 'update_option_nvm_sync_frequency', array( $this, 'handle_sync_frequency_change' ), 10, 2 );
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        // Always load class files
        require_once NVM_PLUGIN_DIR . 'includes/class-nvm-post-type.php';
        require_once NVM_PLUGIN_DIR . 'includes/class-nvm-member-post-type.php';
        require_once NVM_PLUGIN_DIR . 'includes/class-nvm-taxonomies.php';
        require_once NVM_PLUGIN_DIR . 'includes/class-nvm-acf-fields.php';
        require_once NVM_PLUGIN_DIR . 'includes/class-nvm-oauth.php';
        require_once NVM_PLUGIN_DIR . 'includes/class-nvm-settings.php';
        require_once NVM_PLUGIN_DIR . 'includes/class-nvm-youtube-api.php';
        require_once NVM_PLUGIN_DIR . 'includes/class-nvm-sync.php';
    }

    /**
     * Check if ACF Pro is active
     *
     * @return bool
     */
    private function is_acf_pro_active() {
        // Check if ACF function exists (most reliable method)
        if ( function_exists( 'acf' ) ) {
            return true;
        }

        // Check if ACF class exists
        if ( class_exists( 'ACF' ) ) {
            return true;
        }

        // Check if acf_get_setting function exists
        if ( function_exists( 'acf_get_setting' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check ACF and show notice if missing
     */
    public function check_acf_and_show_notice() {
        if ( ! $this->is_acf_pro_active() ) {
            $this->acf_missing_notice();
        }
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Always initialize components (ACF check happens in admin notice)
        NVM_Post_Type::get_instance();
        NVM_Member_Post_Type::get_instance();
        NVM_Taxonomies::get_instance();

        // Only initialize ACF-dependent components if ACF is available
        if ( $this->is_acf_pro_active() ) {
            NVM_ACF_Fields::get_instance();
            NVM_OAuth::get_instance();
            NVM_Settings::get_instance();
            NVM_YouTube_API::get_instance();
            NVM_Sync::get_instance();
        }
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'nova-video-manager',
            false,
            dirname( NVM_PLUGIN_BASENAME ) . '/languages'
        );
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Register post types and taxonomies for flush_rewrite_rules
        if ( class_exists( 'ACF' ) ) {
            require_once NVM_PLUGIN_DIR . 'includes/class-nvm-post-type.php';
            require_once NVM_PLUGIN_DIR . 'includes/class-nvm-member-post-type.php';
            require_once NVM_PLUGIN_DIR . 'includes/class-nvm-taxonomies.php';

            NVM_Post_Type::get_instance()->register_post_type();
            NVM_Member_Post_Type::get_instance()->register_post_type();
            NVM_Taxonomies::get_instance()->register_taxonomies();
        }

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set default options
        $this->set_default_options();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        $timestamp = wp_next_scheduled( 'nvm_sync_videos' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'nvm_sync_videos' );
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'nvm_sync_frequency' => 'hourly',
            'nvm_auto_sync' => false,
        );
        
        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value );
            }
        }
    }
    
    /**
     * Display ACF Pro missing notice
     */
    public function acf_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Nova Video Manager requires Advanced Custom Fields Pro to be installed and activated.', 'nova-video-manager' ); ?></p>
        </div>
        <?php
    }

    /**
     * Run scheduled sync
     */
    public function run_scheduled_sync() {
        if ( ! get_option( 'nvm_auto_sync', false ) ) {
            return;
        }

        $sync = NVM_Sync::get_instance();
        $sync->sync_videos();
    }

    /**
     * Handle auto sync setting change
     *
     * @param mixed $old_value Old value
     * @param mixed $new_value New value
     */
    public function handle_auto_sync_change( $old_value, $new_value ) {
        if ( $new_value ) {
            $this->schedule_sync();
        } else {
            $this->unschedule_sync();
        }
    }

    /**
     * Handle sync frequency change
     *
     * @param mixed $old_value Old value
     * @param mixed $new_value New value
     */
    public function handle_sync_frequency_change( $old_value, $new_value ) {
        if ( get_option( 'nvm_auto_sync', false ) ) {
            $this->unschedule_sync();
            $this->schedule_sync();
        }
    }

    /**
     * Schedule sync cron job
     */
    private function schedule_sync() {
        $frequency = get_option( 'nvm_sync_frequency', 'hourly' );

        if ( ! wp_next_scheduled( 'nvm_sync_videos' ) ) {
            wp_schedule_event( time(), $frequency, 'nvm_sync_videos' );
        }
    }

    /**
     * Unschedule sync cron job
     */
    private function unschedule_sync() {
        $timestamp = wp_next_scheduled( 'nvm_sync_videos' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'nvm_sync_videos' );
        }
    }
}

// Initialize the plugin
Nova_Video_Manager::get_instance();

