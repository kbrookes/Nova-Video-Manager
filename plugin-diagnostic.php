<?php
/**
 * Plugin Diagnostic
 * 
 * Access via: yoursite.local/wp-content/plugins/Nova-Video-Manager/plugin-diagnostic.php
 */

// Load WordPress
require_once '../../../wp-load.php';

// Must be admin
if ( ! current_user_can( 'manage_options' ) ) {
    die( 'Access denied' );
}

echo '<h1>Nova Video Manager Diagnostic</h1>';

echo '<h2>Plugin Status:</h2>';
echo '<ul>';
$active_plugins = get_option( 'active_plugins' );
$nvm_active = false;
foreach ( $active_plugins as $plugin ) {
    if ( strpos( $plugin, 'nova-video-manager' ) !== false || strpos( $plugin, 'Nova-Video-Manager' ) !== false ) {
        echo '<li><strong>' . esc_html( $plugin ) . '</strong> - ACTIVE</li>';
        $nvm_active = true;
    }
}
if ( ! $nvm_active ) {
    echo '<li style="color: red;">Nova Video Manager: NOT ACTIVE</li>';
}
echo '</ul>';

echo '<h2>ACF Status:</h2>';
echo '<ul>';
echo '<li>function_exists("acf"): ' . ( function_exists( 'acf' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>class_exists("ACF"): ' . ( class_exists( 'ACF' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>function_exists("acf_get_setting"): ' . ( function_exists( 'acf_get_setting' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>function_exists("acf_add_local_field_group"): ' . ( function_exists( 'acf_add_local_field_group' ) ? 'YES' : 'NO' ) . '</li>';

if ( function_exists( 'acf_get_setting' ) ) {
    echo '<li>ACF Version: ' . esc_html( acf_get_setting( 'version' ) ) . '</li>';
    echo '<li>ACF Pro: ' . ( acf_get_setting( 'pro' ) ? 'YES' : 'NO' ) . '</li>';
}
echo '</ul>';

echo '<h2>Class Loading:</h2>';
echo '<ul>';
echo '<li>class_exists("Nova_Video_Manager"): ' . ( class_exists( 'Nova_Video_Manager' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>class_exists("NVM_Post_Type"): ' . ( class_exists( 'NVM_Post_Type' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>class_exists("NVM_Member_Post_Type"): ' . ( class_exists( 'NVM_Member_Post_Type' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>class_exists("NVM_Taxonomies"): ' . ( class_exists( 'NVM_Taxonomies' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>class_exists("NVM_ACF_Fields"): ' . ( class_exists( 'NVM_ACF_Fields' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>class_exists("NVM_Settings"): ' . ( class_exists( 'NVM_Settings' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>class_exists("NVM_OAuth"): ' . ( class_exists( 'NVM_OAuth' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>class_exists("NVM_YouTube_API"): ' . ( class_exists( 'NVM_YouTube_API' ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>class_exists("NVM_Sync"): ' . ( class_exists( 'NVM_Sync' ) ? 'YES' : 'NO' ) . '</li>';
echo '</ul>';

echo '<h2>Post Types Registered:</h2>';
echo '<ul>';
$post_types = get_post_types( array(), 'objects' );
echo '<li>nova_video registered: ' . ( isset( $post_types['nova_video'] ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>nova_member registered: ' . ( isset( $post_types['nova_member'] ) ? 'YES' : 'NO' ) . '</li>';
echo '</ul>';

echo '<h2>Constants Defined:</h2>';
echo '<ul>';
echo '<li>NVM_VERSION: ' . ( defined( 'NVM_VERSION' ) ? esc_html( NVM_VERSION ) : 'NOT DEFINED' ) . '</li>';
echo '<li>NVM_PLUGIN_DIR: ' . ( defined( 'NVM_PLUGIN_DIR' ) ? esc_html( NVM_PLUGIN_DIR ) : 'NOT DEFINED' ) . '</li>';
echo '<li>NVM_PLUGIN_URL: ' . ( defined( 'NVM_PLUGIN_URL' ) ? esc_html( NVM_PLUGIN_URL ) : 'NOT DEFINED' ) . '</li>';
echo '</ul>';

echo '<h2>File Checks:</h2>';
echo '<ul>';
$files = array(
    'nova-video-manager.php',
    'includes/class-nvm-post-type.php',
    'includes/class-nvm-member-post-type.php',
    'includes/class-nvm-taxonomies.php',
    'includes/class-nvm-acf-fields.php',
    'includes/class-nvm-settings.php',
    'includes/class-nvm-oauth.php',
    'includes/class-nvm-youtube-api.php',
    'includes/class-nvm-sync.php',
);

foreach ( $files as $file ) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists( $path );
    $readable = $exists ? is_readable( $path ) : false;
    echo '<li>' . esc_html( $file ) . ': ' . ( $exists ? 'EXISTS' : '<span style="color: red;">MISSING</span>' );
    if ( $exists ) {
        echo ' (' . ( $readable ? 'readable' : '<span style="color: red;">not readable</span>' ) . ')';
    }
    echo '</li>';
}
echo '</ul>';

echo '<h2>PHP Errors:</h2>';
echo '<p>Check your WordPress debug.log file for errors. Enable debugging by adding this to wp-config.php:</p>';
echo '<pre>define( "WP_DEBUG", true );
define( "WP_DEBUG_LOG", true );
define( "WP_DEBUG_DISPLAY", false );</pre>';

echo '<h2>Try Manual Initialization:</h2>';
if ( class_exists( 'NVM_Post_Type' ) ) {
    echo '<p>Attempting to manually register post types...</p>';
    try {
        NVM_Post_Type::get_instance();
        echo '<p style="color: green;">✓ NVM_Post_Type initialized</p>';
    } catch ( Exception $e ) {
        echo '<p style="color: red;">✗ Error: ' . esc_html( $e->getMessage() ) . '</p>';
    }
}

if ( class_exists( 'NVM_Member_Post_Type' ) ) {
    try {
        NVM_Member_Post_Type::get_instance();
        echo '<p style="color: green;">✓ NVM_Member_Post_Type initialized</p>';
    } catch ( Exception $e ) {
        echo '<p style="color: red;">✗ Error: ' . esc_html( $e->getMessage() ) . '</p>';
    }
}

// Refresh post types
$post_types = get_post_types( array(), 'objects' );
echo '<p>After manual init:</p>';
echo '<ul>';
echo '<li>nova_video registered: ' . ( isset( $post_types['nova_video'] ) ? 'YES' : 'NO' ) . '</li>';
echo '<li>nova_member registered: ' . ( isset( $post_types['nova_member'] ) ? 'YES' : 'NO' ) . '</li>';
echo '</ul>';

