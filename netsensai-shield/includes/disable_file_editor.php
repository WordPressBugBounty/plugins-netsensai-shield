<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Disable the built-in file editor in WordPress.
 *
 * This function disables the theme and plugin file editors in the WordPress dashboard
 * if the 'ns_shield_file_editor' option is enabled. Note that installing and deleting plugins remain allowed.
 *
 * @return void
 */
function ns_shield_disable_file_editor() {
    if ( get_option( 'ns_shield_file_editor' ) ) {
        // Disable file editing if not already defined.
        if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
            define( 'DISALLOW_FILE_EDIT', true );
        }
    }
}
add_action( 'init', 'ns_shield_disable_file_editor' );

