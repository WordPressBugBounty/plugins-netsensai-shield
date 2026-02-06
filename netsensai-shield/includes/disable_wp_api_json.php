<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Disable WP API JSON for unauthenticated users except for allowed paths.
 *
 * This function retrieves the setting for "Disable WP API JSON" and, if enabled and the user is not logged in,
 * adds a filter to the REST API authentication errors. The filter checks the REQUEST_URI and allows access if
 * it matches one of the specified allowed paths. Otherwise, it returns a 401 error.
 *
 * @return void
 */
function ns_shield_disable_wp_api_json() {
    // Get the setting for disabling WP API JSON.
    $is_api_json_disabled = get_option( 'ns_shield_wp_api_json', false );

    if ( $is_api_json_disabled && ! is_user_logged_in() ) {
        add_filter( 'rest_authentication_errors', function( $result ) {
            // Define allowed paths.
            $allowed_paths = array( '/wp-json/edd/', '/platnosc/' );

            // Safely retrieve and sanitize the REQUEST_URI.
            $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

            // Check if the REQUEST_URI contains any allowed path.
            foreach ( $allowed_paths as $path ) {
                if ( strpos( $request_uri, $path ) !== false ) {
                    return $result; // Allow access for allowed paths.
                }
            }

            // Return an error if the user is not logged in and the requested path is not allowed.
            return new WP_Error( 'rest_not_logged_in', 'You are not currently logged in.', array( 'status' => 401 ) );
        } );
    }
}
add_action( 'rest_api_init', 'ns_shield_disable_wp_api_json' );
