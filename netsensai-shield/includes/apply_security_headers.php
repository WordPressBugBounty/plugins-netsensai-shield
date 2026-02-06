<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Apply security headers.
 *
 * This function applies several HTTP security headers to enhance site protection if the
 * 'ns_shield_security_headers' option is enabled. The following headers are applied:
 * - Strict-Transport-Security: Applied only if the connection is secure (HTTPS).
 * - X-Frame-Options: SAMEORIGIN
 * - X-Content-Type-Options: nosniff
 * - Referrer-Policy: no-referrer-when-downgrade
 * - Permissions-Policy: geolocation=(self), microphone=()
 *
 * @return void
 */
function ns_shield_apply_security_headers() {
    if ( get_option( 'ns_shield_security_headers' ) ) {
        // Apply HSTS header only if connection is secure.
        if ( is_ssl() ) {
            header( "Strict-Transport-Security: max-age=31536000; includeSubDomains; preload" );
        }
        header( "X-Frame-Options: SAMEORIGIN" );
        header( "X-Content-Type-Options: nosniff" );
        header( "Referrer-Policy: no-referrer-when-downgrade" );
        header( "Permissions-Policy: geolocation=(self), microphone=()" );
    }
}
add_action( 'send_headers', 'ns_shield_apply_security_headers' );
?>
