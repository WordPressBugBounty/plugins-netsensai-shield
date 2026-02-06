<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Disable Directory Indexing Based on Plugin Setting.
 *
 * This function checks the 'ns_shield_directory_indexing' option. If directory indexing
 * is disabled, it prepends the "Options -Indexes" directive to the .htaccess file to prevent
 * directory listing. If the option is disabled, it removes the directive.
 *
 * The function uses the WP_Filesystem API to safely read and write to the .htaccess file.
 *
 * @return void
 */
function ns_shield_disable_directory_indexing() {
    global $wp_filesystem;

    // Initialize the WordPress filesystem if it is not already set up.
    if ( empty( $wp_filesystem ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    // Retrieve the option setting for directory indexing (default: false).
    $is_directory_indexing_disabled = get_option( 'ns_shield_directory_indexing', false );

    // Define the path to the .htaccess file and the directive to disable directory indexing.
    $htaccess_file = ABSPATH . '.htaccess';
    $htaccess_code = "Options -Indexes\n";

    if ( $is_directory_indexing_disabled ) {
        // If directory indexing should be disabled, add the directive if it's not present.
        if ( $wp_filesystem->exists( $htaccess_file ) && $wp_filesystem->is_writable( $htaccess_file ) ) {
            $htaccess_content = $wp_filesystem->get_contents( $htaccess_file );
            if ( strpos( $htaccess_content, 'Options -Indexes' ) === false ) {
                $wp_filesystem->put_contents( $htaccess_file, $htaccess_code . $htaccess_content, FS_CHMOD_FILE );
            }
        } else {
            // Log error if .htaccess is missing or not writable.
            if ( function_exists( 'ns_shield_debug_log' ) ) {
                ns_shield_debug_log( 'The .htaccess file does not exist or is not writable.' );
            }
        }
    } else {
        // If directory indexing is enabled, remove the "Options -Indexes" directive.
        if ( $wp_filesystem->exists( $htaccess_file ) && $wp_filesystem->is_writable( $htaccess_file ) ) {
            $htaccess_content = $wp_filesystem->get_contents( $htaccess_file );
            $updated_content = str_replace( "Options -Indexes\n", '', $htaccess_content );
            $wp_filesystem->put_contents( $htaccess_file, $updated_content, FS_CHMOD_FILE );
        }
    }
}
add_action( 'init', 'ns_shield_disable_directory_indexing' );
