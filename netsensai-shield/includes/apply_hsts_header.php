<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Apply HSTS Header.
 *
 * Applies the Strict-Transport-Security header if the 'ns_shield_hsts' option is enabled
 * and the current connection is secure (HTTPS). This header instructs browsers to only
 * access the site over HTTPS for a specified period.
 *
 * @return void
 */
function ns_shield_apply_hsts_header() {
    // Apply HSTS only on secure connections.
    if ( is_ssl() && get_option( 'ns_shield_hsts' ) ) {
        header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains; preload' );
    }
}
add_action( 'send_headers', 'ns_shield_apply_hsts_header' );
