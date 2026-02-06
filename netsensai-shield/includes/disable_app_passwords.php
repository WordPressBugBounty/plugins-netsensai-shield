<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Disable Application Passwords.
 *
 * If the 'ns_shield_app_passwords' option is enabled, this function disables
 * the Application Passwords feature by hooking into 'wp_is_application_passwords_available'
 * and returning false.
 *
 * @return void
 */
function ns_shield_disable_app_passwords() {
    if ( get_option( 'ns_shield_app_passwords' ) ) {
        add_filter( 'wp_is_application_passwords_available', '__return_false' );
    }
}
add_action( 'init', 'ns_shield_disable_app_passwords' );
