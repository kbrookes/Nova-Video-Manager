<?php
/**
 * ACF Diagnostic Tool
 * 
 * Access this file directly in your browser to check ACF field registration
 * Example: https://cuppa.tv/wp-content/plugins/Nova-Video-Manager/acf-diagnostic.php
 */

// Load WordPress
require_once '../../../wp-load.php';

// Security check - only allow admins
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized' );
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>ACF Diagnostic - Nova Video Manager</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; padding: 20px; background: #f0f0f1; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        h1 { color: #1d2327; border-bottom: 2px solid #2271b1; padding-bottom: 10px; }
        h2 { color: #2271b1; margin-top: 30px; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; }
        pre { background: #f6f7f7; padding: 15px; border-radius: 4px; overflow-x: auto; }
        code { background: #f6f7f7; padding: 2px 6px; border-radius: 3px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f6f7f7; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 ACF Diagnostic - Nova Video Manager</h1>
        
        <h2>ACF Status</h2>
        <?php
        $acf_active = function_exists( 'acf' ) || class_exists( 'ACF' );
        $acf_version = function_exists( 'acf_get_setting' ) ? acf_get_setting( 'version' ) : 'Unknown';
        $acf_pro = defined( 'ACF_PRO' ) && ACF_PRO;
        ?>
        <div class="status <?php echo $acf_active ? 'success' : 'error'; ?>">
            <strong>ACF Active:</strong> <?php echo $acf_active ? '✓ YES' : '✗ NO'; ?>
        </div>
        <div class="status info">
            <strong>ACF Version:</strong> <?php echo esc_html( $acf_version ); ?>
        </div>
        <div class="status <?php echo $acf_pro ? 'success' : 'warning'; ?>">
            <strong>ACF Pro:</strong> <?php echo $acf_pro ? '✓ YES' : '✗ NO (Free version)'; ?>
        </div>

        <h2>Plugin Status</h2>
        <?php
        $plugin_active = class_exists( 'Nova_Video_Manager' );
        $nvm_acf_fields_loaded = class_exists( 'NVM_ACF_Fields' );
        ?>
        <div class="status <?php echo $plugin_active ? 'success' : 'error'; ?>">
            <strong>Nova Video Manager Active:</strong> <?php echo $plugin_active ? '✓ YES' : '✗ NO'; ?>
        </div>
        <div class="status <?php echo $nvm_acf_fields_loaded ? 'success' : 'error'; ?>">
            <strong>NVM_ACF_Fields Class Loaded:</strong> <?php echo $nvm_acf_fields_loaded ? '✓ YES' : '✗ NO'; ?>
        </div>

        <h2>Registered ACF Field Groups</h2>
        <?php
        if ( function_exists( 'acf_get_field_groups' ) ) {
            $field_groups = acf_get_field_groups();
            
            if ( empty( $field_groups ) ) {
                echo '<div class="status warning"><strong>No ACF field groups found!</strong></div>';
            } else {
                echo '<table>';
                echo '<thead><tr><th>Key</th><th>Title</th><th>Location</th><th>Fields</th></tr></thead>';
                echo '<tbody>';
                
                foreach ( $field_groups as $group ) {
                    $fields = acf_get_fields( $group['key'] );
                    $field_count = is_array( $fields ) ? count( $fields ) : 0;
                    
                    echo '<tr>';
                    echo '<td><code>' . esc_html( $group['key'] ) . '</code></td>';
                    echo '<td>' . esc_html( $group['title'] ) . '</td>';
                    echo '<td>';
                    if ( isset( $group['location'] ) && is_array( $group['location'] ) ) {
                        foreach ( $group['location'] as $location_rule ) {
                            if ( is_array( $location_rule ) ) {
                                foreach ( $location_rule as $rule ) {
                                    echo esc_html( $rule['param'] . ' ' . $rule['operator'] . ' ' . $rule['value'] ) . '<br>';
                                }
                            }
                        }
                    }
                    echo '</td>';
                    echo '<td>' . $field_count . ' fields</td>';
                    echo '</tr>';
                }
                
                echo '</tbody></table>';
            }
            
            // Show NVM-specific field groups
            echo '<h3>Nova Video Manager Field Groups</h3>';
            $nvm_groups = array_filter( $field_groups, function( $group ) {
                return strpos( $group['key'], 'group_nvm_' ) === 0;
            } );
            
            if ( empty( $nvm_groups ) ) {
                echo '<div class="status error"><strong>⚠️ No Nova Video Manager field groups found!</strong><br>';
                echo 'Expected groups: <code>group_nvm_video_metadata</code>, <code>group_nvm_community</code></div>';
            } else {
                echo '<div class="status success"><strong>✓ Found ' . count( $nvm_groups ) . ' Nova Video Manager field group(s)</strong></div>';
                
                foreach ( $nvm_groups as $group ) {
                    echo '<h4>' . esc_html( $group['title'] ) . ' (<code>' . esc_html( $group['key'] ) . '</code>)</h4>';
                    $fields = acf_get_fields( $group['key'] );
                    
                    if ( $fields ) {
                        echo '<table>';
                        echo '<thead><tr><th>Field Key</th><th>Label</th><th>Name</th><th>Type</th><th>REST API</th></tr></thead>';
                        echo '<tbody>';
                        
                        foreach ( $fields as $field ) {
                            $show_in_rest = isset( $field['show_in_rest'] ) && $field['show_in_rest'] ? '✓' : '✗';
                            echo '<tr>';
                            echo '<td><code>' . esc_html( $field['key'] ) . '</code></td>';
                            echo '<td>' . esc_html( $field['label'] ) . '</td>';
                            echo '<td><code>' . esc_html( $field['name'] ) . '</code></td>';
                            echo '<td>' . esc_html( $field['type'] ) . '</td>';
                            echo '<td>' . $show_in_rest . '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody></table>';
                    }
                }
            }
        } else {
            echo '<div class="status error"><strong>ACF function acf_get_field_groups() not available!</strong></div>';
        }
        ?>

        <h2>Recommendations</h2>
        <?php if ( ! $acf_active ): ?>
            <div class="status error">
                <strong>⚠️ ACF is not active!</strong><br>
                Please install and activate Advanced Custom Fields Pro.
            </div>
        <?php elseif ( empty( $nvm_groups ) ): ?>
            <div class="status warning">
                <strong>⚠️ ACF field groups not registered!</strong><br>
                Possible causes:
                <ul>
                    <li>The plugin's <code>init</code> hook hasn't fired yet</li>
                    <li>The <code>acf/init</code> hook hasn't fired yet</li>
                    <li>There's a PHP error preventing field registration</li>
                </ul>
                Try:
                <ol>
                    <li>Deactivate and reactivate the Nova Video Manager plugin</li>
                    <li>Check your PHP error logs</li>
                    <li>Make sure ACF Pro is activated before Nova Video Manager</li>
                </ol>
            </div>
        <?php else: ?>
            <div class="status success">
                <strong>✓ Everything looks good!</strong><br>
                ACF field groups are properly registered. If you're not seeing fields in the admin, try:
                <ul>
                    <li>Clear your browser cache</li>
                    <li>Edit a video post and check if the field groups appear</li>
                    <li>Check if the post type matches: <code>nova_video</code></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

