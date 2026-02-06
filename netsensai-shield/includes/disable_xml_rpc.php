<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Disable XML-RPC and block direct access to xmlrpc.php.
 *
 * If the "Disable XML-RPC" option is enabled, this function disables the XML-RPC functionality,
 * blocks direct access to xmlrpc.php by sending a 403 Forbidden header, and modifies the .htaccess file
 * to deny access to xmlrpc.php. If the option is disabled, any previously added block is removed.
 *
 * @return void
 */
function ns_shield_disable_xml_rpc() {
    global $wp_filesystem;

    // Initialize the filesystem if not yet set up.
    if ( empty( $wp_filesystem ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();
    }

    // Check if the "Disable XML-RPC" switch is enabled.
    $is_xmlrpc_disabled = get_option( 'ns_shield_xml_rpc', false );

    if ( $is_xmlrpc_disabled ) {
        // Disable XML-RPC functionality.
        add_filter( 'xmlrpc_enabled', '__return_false' );

        // Block direct access to xmlrpc.php.
        add_action( 'init', function() {
            if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), 'xmlrpc.php' ) !== false ) {
                header( 'HTTP/1.1 403 Forbidden' );
                $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
                if ( stripos( $user_agent, 'curl' ) !== false ) {
                    exit( 'Protected by Netsensai Shield' );
                } else {
                    exit( 'XML-RPC is disabled on this site.' );
                }
            }
        } );

        // Edit .htaccess to block xmlrpc.php.
        $htaccess_file = ABSPATH . '.htaccess';
        $htaccess_code = "<Files xmlrpc.php>\n    order deny,allow\n    deny from all\n</Files>\n";

        if ( $wp_filesystem->exists( $htaccess_file ) && $wp_filesystem->is_writable( $htaccess_file ) ) {
            $htaccess_content = $wp_filesystem->get_contents( $htaccess_file );
            if ( strpos( $htaccess_content, 'xmlrpc.php' ) === false ) {
                $wp_filesystem->put_contents( $htaccess_file, $htaccess_code . $htaccess_content, FS_CHMOD_FILE );
            }
        }
    } else {
        // If the switch is disabled, remove any xmlrpc.php block from .htaccess.
        $htaccess_file = ABSPATH . '.htaccess';
        $htaccess_code = "<Files xmlrpc.php>\n    order deny,allow\n    deny from all\n</Files>\n";

        if ( $wp_filesystem->exists( $htaccess_file ) && $wp_filesystem->is_writable( $htaccess_file ) ) {
            $htaccess_content = $wp_filesystem->get_contents( $htaccess_file );
            $updated_content = str_replace( $htaccess_code, '', $htaccess_content );
            $wp_filesystem->put_contents( $htaccess_file, $updated_content, FS_CHMOD_FILE );
        }
    }
}
add_action( 'init', 'ns_shield_disable_xml_rpc' );
