<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display the "Disable Default Admin" toggle along with a text field to change the admin login.
 *
 * This function generates the settings UI for the administrator to disable the default "admin" login
 * and specify a new admin username.
 *
 * @return void
 */
function ns_shield_disable_default_admin() {
    // Odczytujemy wartość opcji (0 lub 1) – musi być zapisana przy użyciu absint.
    $status      = get_option( 'ns_shield_default_admin', 0 );
    $admin_login = get_option( 'ns_shield_new_admin_login', '' );
    ?>
    <div class="disable-admin-container">
        <label class="switch">
            <input type="checkbox" name="ns_shield_default_admin" id="ns_shield_default_admin" value="1" <?php checked( 1, $status, true ); ?>>
            <span class="slider round"></span>
        </label>
        <div class="tooltip" id="tooltip-disable-admin">
            <?php echo esc_html__( 'The default "admin" username is a prime target for attackers. Changing this makes it significantly harder for attackers to exploit common login details. Leaving the default admin username active increases vulnerability to brute force attacks.', 'netsensai-shield' ); ?>
        </div>
        <!-- Kontener z inline style – widoczność zależy od opcji -->
        <div id="admin_login_field" style="display:<?php echo $status ? 'block' : 'none'; ?>;">
            <input type="text"
                   name="ns_shield_new_admin_login"
                   id="ns_shield_new_admin_login"
                   value="<?php echo esc_attr( $admin_login ); ?>"
                   placeholder="<?php echo esc_attr__( 'Enter new admin login', 'netsensai-shield' ); ?>"
                   class="login-url-input">
        </div>
    </div>
    <?php
}

/**
 * Updates the admin username.
 *
 * This function changes the "admin" username to a new value or restores it if the feature is disabled.
 *
 * @global WPDB $wpdb
 * @return void
 */
function ns_shield_update_admin_username() {
    global $wpdb;

    if ( get_option( 'ns_shield_default_admin' ) ) {
        $admin_user = get_user_by( 'login', 'admin' );
        if ( $admin_user ) {
            $new_admin_login = get_option( 'ns_shield_new_admin_login', '' );
            if ( ! empty( $new_admin_login ) ) {
                if ( wp_get_current_user()->user_login === 'admin' ) {
                    update_option( 'ns_shield_admin_login_change_pending', $new_admin_login );
                } else {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $wpdb->update(
                        $wpdb->users,
                        array( 'user_login' => sanitize_user( $new_admin_login ) ),
                        array( 'ID' => $admin_user->ID )
                    );
                    clean_user_cache( $admin_user->ID );
                }
            }
        }
    } else {
        $custom_admin_login = get_option( 'ns_shield_new_admin_login', '' );
        if ( ! empty( $custom_admin_login ) ) {
            $custom_user = get_user_by( 'login', $custom_admin_login );
            if ( $custom_user ) {
                if ( wp_get_current_user()->user_login === $custom_admin_login ) {
                    update_option( 'ns_shield_admin_login_change_pending', 'admin' );
                } else {
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                    $wpdb->update(
                        $wpdb->users,
                        array( 'user_login' => 'admin' ),
                        array( 'ID' => $custom_user->ID )
                    );
                    clean_user_cache( $custom_user->ID );
                }
            }
        }
    }
}
add_action( 'init', 'ns_shield_update_admin_username' );

/**
 * Changes the admin username after logout if there's a pending change.
 *
 * This function checks if there is a pending admin username change and updates it accordingly.
 *
 * @global WPDB $wpdb
 * @return void
 */
function ns_shield_change_admin_username_after_logout() {
    global $wpdb;
    $pending_login_change = get_option( 'ns_shield_admin_login_change_pending', '' );

    if ( ! empty( $pending_login_change ) ) {
        $target_login = ( $pending_login_change === 'admin' )
            ? get_option( 'ns_shield_new_admin_login', '' )
            : 'admin';
        $admin_user = get_user_by( 'login', $target_login );
        if ( $admin_user ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update(
                $wpdb->users,
                array( 'user_login' => sanitize_user( $pending_login_change ) ),
                array( 'ID' => $admin_user->ID )
            );
            clean_user_cache( $admin_user->ID );
            delete_option( 'ns_shield_admin_login_change_pending' );
        }
    }
}
add_action( 'wp_logout', 'ns_shield_change_admin_username_after_logout' );
