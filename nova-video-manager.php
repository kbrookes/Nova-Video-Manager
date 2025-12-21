<?php
/**
 * Plugin Name: Nova Video Manager
 * Plugin URI: https://github.com/kbrookes/Nova-Video-Manager
 * Description: Automatically syncs YouTube videos from a channel and manages them as WordPress content with full metadata support.
 * Version: 0.3.3
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
define( 'NVM_VERSION', '0.3.3' );
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
        require_once NVM_PLUGIN_DIR . 'includes/class-nvm-cron.php';
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
        error_log( 'NVM - init() called at priority 15' );

        // Always initialize components (ACF check happens in admin notice)
        NVM_Post_Type::get_instance();
        NVM_Member_Post_Type::get_instance();
        NVM_Taxonomies::get_instance();

        // Only initialize ACF-dependent components if ACF is available
        $acf_active = $this->is_acf_pro_active();
        error_log( 'NVM - ACF Pro active: ' . ( $acf_active ? 'yes' : 'no' ) );

        if ( $acf_active ) {
            error_log( 'NVM - Initializing ACF-dependent components...' );
            NVM_ACF_Fields::get_instance();
            NVM_OAuth::get_instance();
            NVM_Settings::get_instance();
            NVM_YouTube_API::get_instance();
            NVM_Sync::get_instance();
            NVM_Cron::get_instance();
            error_log( 'NVM - ACF-dependent components initialized' );
        } else {
            error_log( 'NVM - Skipping ACF-dependent components (ACF not active)' );
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
        NVM_Cron::unschedule_all();

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
}

// Initialize the plugin
Nova_Video_Manager::get_instance();

