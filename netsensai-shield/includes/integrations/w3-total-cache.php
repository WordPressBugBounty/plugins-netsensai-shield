<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * NETSENSAI Shield ↔ W3 Total Cache integration
 *
 * 1) Permanently disable .htaccess writes by W3TC
 * 2) Runtime disable Page Cache UI
 * 3) One-time full flush on first admin page load
 * 4) Flush on settings save
 * 5) Physical cleanup and permanent disable via W3TC API
 */

// 1) Prevent W3TC from writing to .htaccess
add_filter( 'w3tc_can_modify_htaccess', '__return_false', PHP_INT_MAX );

/**
 * Flush and disable Page Cache
 */
function ns_shield_w3tc_full_flush() {
    if ( ! function_exists( 'is_plugin_active' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if ( is_plugin_active( 'w3-total-cache/w3-total-cache.php' ) ) {
        // Load W3TC API
        if ( ! function_exists( 'w3tc_flush_all' ) ) {
            @include_once WP_PLUGIN_DIR . '/w3-total-cache/w3-total-cache-api.php';
        }

        // Flush caches
        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
        }
        if ( function_exists( 'w3tc_pgcache_flush' ) ) {
            w3tc_pgcache_flush();
        }

        // Remove Page Cache section from .htaccess via WP_Filesystem
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        global $wp_filesystem;

        $htaccess_file = ABSPATH . '.htaccess';

        if ( $wp_filesystem->is_writable( $htaccess_file ) ) {
            $contents = $wp_filesystem->get_contents( $htaccess_file );
            if ( false !== $contents ) {
                $pattern = '/# BEGIN W3TC Page Cache[\\s\\S]*?# END W3TC Page Cache/m';
                $new     = preg_replace(
                    $pattern,
                    '# NETSENSAI Shield removed W3TC Page Cache section',
                    $contents
                );
                if ( null !== $new ) {
                    $wp_filesystem->put_contents( $htaccess_file, $new, FS_CHMOD_FILE );
                }
            }
        }

        // Permanent disable in config via API
        if ( function_exists( 'w3tc_config' ) ) {
            $cfg = w3tc_config();
            if ( method_exists( $cfg, 'get_boolean' ) && method_exists( $cfg, 'set' ) ) {
                if ( $cfg->get_boolean( 'pgcache.enabled' ) ) {
                    $cfg->set( 'pgcache.enabled', false );
                    $cfg->save();
                }
            }
        }
    }
}

// 2) Runtime disable of Page Cache UI
add_action( 'admin_footer', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $alert_text = __( 'Netsensai-Shield has disabled the Page Cache to correctly generate dynamic security headers.', 'netsensai-shield' );
    ?>
    <script>
    (function($) {
        var cb     = $('#pgcache__enabled[type=checkbox]'),
            hidden = $('input[name="pgcache__enabled"][type="hidden"]');
        if ( cb.length && hidden.length ) {
            cb.prop('checked', false);
            cb.on('click', function(e) {
                e.preventDefault();
                cb.prop('checked', false);
                hidden.val('0');
                alert(<?php echo wp_json_encode( $alert_text ); ?>);
            });
            $(document).on('submit', 'form', function() {
                hidden.val(cb.prop('checked') ? '1' : '0');
            });
        }
    })(jQuery);
    </script>
    <?php
}, 100 );

// 3) One-time flush on first admin page load
add_action( 'admin_init', function() {
    if ( ! get_option( 'ns_shield_w3tc_flushed' ) && current_user_can( 'manage_options' ) ) {
        ns_shield_w3tc_full_flush();
        update_option( 'ns_shield_w3tc_flushed', true );
    }
}, 10 );

// 4) Flush on settings save
add_action( 'load-settings_page_secure-options', function() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( ! empty( $_GET['settings-updated'] ) && $_GET['settings-updated'] === 'true' ) {
        ns_shield_w3tc_full_flush();
    }
}, 20 );

