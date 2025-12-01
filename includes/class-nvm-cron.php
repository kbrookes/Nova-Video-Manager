<?php
/**
 * WP-Cron Scheduling for Auto Sync
 *
 * @package NovaVideoManager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NVM_Cron Class
 * Handles WP-Cron scheduling for automatic video syncing
 */
class NVM_Cron {
    
    /**
     * Cron hook name
     */
    const CRON_HOOK = 'nvm_auto_sync_videos';
    
    /**
     * Single instance of the class
     *
     * @var NVM_Cron
     */
    private static $instance = null;
    
    /**
     * Get single instance of the class
     *
     * @return NVM_Cron
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
        // Register the cron hook
        add_action( self::CRON_HOOK, array( $this, 'run_auto_sync' ) );
        
        // Schedule/unschedule based on settings changes
        add_action( 'update_option_nvm_auto_sync', array( $this, 'handle_auto_sync_toggle' ), 10, 2 );
        add_action( 'update_option_nvm_sync_frequency', array( $this, 'handle_frequency_change' ), 10, 2 );
        
        // Initialize schedule on plugin activation
        $this->maybe_schedule_event();
    }
    
    /**
     * Run the auto sync
     */
    public function run_auto_sync() {
        error_log( 'NVM Cron - Auto sync triggered' );
        
        // Check if auto sync is enabled
        if ( ! get_option( 'nvm_auto_sync', false ) ) {
            error_log( 'NVM Cron - Auto sync is disabled, skipping' );
            return;
        }
        
        // Run the sync
        $sync = NVM_Sync::get_instance();
        $result = $sync->sync_videos();
        
        if ( is_wp_error( $result ) ) {
            error_log( 'NVM Cron - Sync failed: ' . $result->get_error_message() );
        } else {
            error_log( 'NVM Cron - Successfully synced ' . $result . ' videos' );
        }
    }
    
    /**
     * Handle auto sync toggle
     *
     * @param mixed $old_value Old value
     * @param mixed $new_value New value
     */
    public function handle_auto_sync_toggle( $old_value, $new_value ) {
        if ( $new_value ) {
            // Auto sync enabled - schedule event
            $this->schedule_event();
        } else {
            // Auto sync disabled - unschedule event
            $this->unschedule_event();
        }
    }
    
    /**
     * Handle frequency change
     *
     * @param mixed $old_value Old value
     * @param mixed $new_value New value
     */
    public function handle_frequency_change( $old_value, $new_value ) {
        // Only reschedule if auto sync is enabled
        if ( get_option( 'nvm_auto_sync', false ) ) {
            $this->unschedule_event();
            $this->schedule_event();
        }
    }
    
    /**
     * Maybe schedule event if auto sync is enabled
     */
    public function maybe_schedule_event() {
        if ( get_option( 'nvm_auto_sync', false ) && ! wp_next_scheduled( self::CRON_HOOK ) ) {
            $this->schedule_event();
        }
    }
    
    /**
     * Schedule the cron event
     */
    private function schedule_event() {
        $frequency = get_option( 'nvm_sync_frequency', 'hourly' );
        
        // Unschedule first to avoid duplicates
        $this->unschedule_event();
        
        // Schedule the event
        $scheduled = wp_schedule_event( time(), $frequency, self::CRON_HOOK );
        
        if ( $scheduled !== false ) {
            error_log( 'NVM Cron - Scheduled auto sync with frequency: ' . $frequency );
        } else {
            error_log( 'NVM Cron - Failed to schedule auto sync' );
        }
    }
    
    /**
     * Unschedule the cron event
     */
    private function unschedule_event() {
        $timestamp = wp_next_scheduled( self::CRON_HOOK );
        
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::CRON_HOOK );
            error_log( 'NVM Cron - Unscheduled auto sync' );
        }
    }
    
    /**
     * Unschedule all events (for plugin deactivation)
     */
    public static function unschedule_all() {
        wp_clear_scheduled_hook( self::CRON_HOOK );
    }
}

