<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Zwraca bezpieczny, znormalizowany slug niestandardowej strony logowania (bez ukośników, lowercase).
 * Fallbackuje do 'mysecurelogin', jeśli slug jest pusty lub zabroniony.
 *
 * @return string
 */
function ns_shield_get_custom_slug() {
    $raw = get_option( 'ns_shield_login_url', '/mysecurelogin/' );

    // Normalizacja
    $slug = is_string( $raw ) ? strtolower( trim( $raw ) ) : '';
    $slug = untrailingslashit( $slug );
    $slug = ltrim( $slug, '/' );

    // Redukcja do bezpiecznych znaków (alfanum + myślniki)
    // Pozostawiamy polskie litery do dalszej transliteracji WordPressa:
    $slug = sanitize_title_with_dashes( $slug );

    // Zabronione wartości i puste
    $forbidden = array(
        '', 'login', 'wp-login', 'login.php', 'wp-login.php', 'wp-admin'
    );
    if ( in_array( $slug, $forbidden, true ) ) {
        $slug = 'mysecurelogin';
    }

    return $slug;
}

/**
 * Krótki helper do odpowiedzi 404 (spójny komunikat/headers).
 */
function ns_shield_die_404() {
    status_header( 404 );
    nocache_headers();
    wp_die(
        esc_html__( '404 Not Found', 'netsensai-shield-pro' ),
        esc_html__( 'Not Found', 'netsensai-shield-pro' ),
        array( 'response' => 404 )
    );
    exit;
}

/**
 * Failsafe: true, jeśli żądanie dotyczy naszej strony logowania
 * (po query var LUB po samej ścieżce URL).
 *
 * @return bool
 */
function ns_shield_is_custom_login_request() {
    if ( intval( get_query_var( 'ns_shield_custom_login' ) ) === 1 ) {
        return true;
    }

    $request_uri = isset( $_SERVER['REQUEST_URI'] )
        ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
        : '';
    $path = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
    $path = rtrim( $path, '/' );

    $custom_path = rtrim( '/' . ns_shield_get_custom_slug(), '/' );

    return ( $path === $custom_path );
}

/**
 * Dodaje regułę rewrite dla niestandardowego adresu logowania.
 */
function ns_shield_add_rewrite_rule() {
    if ( get_option( 'ns_shield_login_url_enabled', false ) ) {
        $custom_slug = ns_shield_get_custom_slug();
        if ( ! empty( $custom_slug ) ) {
            add_rewrite_rule(
                '^' . preg_quote( $custom_slug, '/' ) . '/?$',
                'index.php?ns_shield_custom_login=1',
                'top'
            );
        }
    }
}
add_action( 'init', 'ns_shield_add_rewrite_rule', 1 );

/**
 * Rejestruje zmienną query do rozpoznawania custom login.
 */
function ns_shield_query_vars( $vars ) {
    $vars[] = 'ns_shield_custom_login';
    return $vars;
}
add_filter( 'query_vars', 'ns_shield_query_vars' );

/**
 * Blokuje domyślne URL-e logowania i /wp-admin dla niezalogowanych,
 * z uniknięciem fałszywych 404 na podstronach (Woo/motywy) oraz whitelistą admin-ajax/admin-post.
 */
function ns_shield_block_default_urls() {
    // Jeśli to nasz custom login – nic nie blokuj tutaj.
    if ( ns_shield_is_custom_login_request() ) {
        return;
    }

    $login_enabled = get_option( 'ns_shield_login_url_enabled', false );
    if ( ! $login_enabled ) {
        return;
    }

    // Bieżąca ścieżka żądania (bez trailing slash)
    $request_uri_full = isset( $_SERVER['REQUEST_URI'] )
        ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
        : '';
    $parsed_url   = wp_parse_url( $request_uri_full );
    $request_path = isset( $parsed_url['path'] ) ? rtrim( $parsed_url['path'], '/' ) : '';

    // Zestaw znanych domyślnych ścieżek logowania WordPress
    $default_login_paths = array( '/wp-login.php','/wp_login.php','/login.php','/wp-login','/wp_login','/login' );
    $is_default_login    = in_array( $request_path, $default_login_paths, true );

    // action=lostpassword/register – blokuj TYLKO na prawdziwym wp-login, nie globalnie (unikamy 404 na front-endzie)
    $action_param = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
    if ( $action_param && in_array( $action_param, array( 'register', 'lostpassword' ), true ) ) {
        if ( $is_default_login ) {
            ns_shield_die_404();
        }
        // jeżeli to nie wp-login – przepuszczamy (np. WooCommerce/motyw).
    }

    // Obsługa prób wejścia na domyślne loginy
    if ( $is_default_login ) {
        // 1) wylogowanie → redirect na nasz slug z loggedout=true
       $loggedout = isset( $_GET['loggedout'] ) ? sanitize_text_field( wp_unslash( $_GET['loggedout'] ) ) : '';
if ( $loggedout === 'true' ) {
    ns_shield_die_404_brand( 'loggedout_on_default_wp_login_strict', array( 'path_norm' => $path ) );
}

        // 2) bezpieczne stany przekieruj na nasz slug z zachowaniem parametrów
        $pass_qs_keys = array( 'checkemail', 'action', 'key', 'login', 'redirect_to', 'reauth', 'wp_lang' );
        $qs = array();
        foreach ( $pass_qs_keys as $k ) {
            if ( isset( $_GET[ $k ] ) ) {
                $qs[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ) );
            }
        }
        if ( ! empty( $qs ) ) {
            wp_redirect( add_query_arg( $qs, home_url( '/' . ns_shield_get_custom_slug() ) ) );
            exit;
        }

        // 3) reszta przypadków → 404
        ns_shield_die_404();
    }

    // /wp-admin dla niezalogowanych – pozwól na publiczne admin-ajax.php i admin-post.php (często używane przez front)
    if ( ! is_user_logged_in() && 0 === strpos( $request_path, '/wp-admin' ) ) {
        if ( preg_match( '#^/wp-admin/(admin-ajax\.php|admin-post\.php)$#', $request_path ) ) {
            return; // whitelista
        }
        ns_shield_die_404();
    }
}
add_action( 'wp_loaded', 'ns_shield_block_default_urls' );

/**
 * Obsługuje wyświetlanie wp-login.php na naszym custom slugu.
 */
function ns_shield_handle_custom_login_page() {
    if ( ns_shield_is_custom_login_request() ) {
        $custom_slug     = ns_shield_get_custom_slug();
        $forbidden_slugs = array( 'login', 'wp-login', 'login.php', 'wp-login.php' );

        if ( in_array( $custom_slug, $forbidden_slugs, true ) ) {
            ns_shield_die_404();
        }

        // Przekazujemy WP właściwy login i czyścimy ewentualne błędy.
        global $user_login, $error;
        $user_login = isset( $_GET['login'] )
            ? sanitize_user( wp_unslash( $_GET['login'] ) )
            : '';
        $error = '';

        require_once ABSPATH . 'wp-login.php';
        exit;
    }
}
add_action( 'template_redirect', 'ns_shield_handle_custom_login_page' );

/**
 * Wyłącz canonical redirect na naszym custom slugu,
 * żeby WP nie zjadał parametrów (login/key itp.).
 */
function ns_shield_disable_canonical_redirect( $redirect_url, $requested_url ) {
    if ( ns_shield_is_custom_login_request() ) {
        return false;
    }
    return $redirect_url;
}
add_filter( 'redirect_canonical', 'ns_shield_disable_canonical_redirect', 10, 2 );

/**
 * Zwraca URL logowania przepięty na nasz custom slug (ZACHOWUJE istniejące query – np. checkemail=confirm).
 *
 * @param string $login_url
 * @param string $redirect
 * @return string
 */
function ns_shield_custom_login_url( $login_url, $redirect ) {
    if ( ! get_option( 'ns_shield_login_url_enabled', false ) ) {
        return $login_url;
    }

    // Wyciągnij istniejące parametry z podanego $login_url (np. ?checkemail=confirm)
    $parts = wp_parse_url( $login_url );
    $args  = array();
    if ( ! empty( $parts['query'] ) ) {
        wp_parse_str( $parts['query'], $args );
    }

    // Jeśli WP podał redirect_to w argumencie filtra, nadpisz/uzupełnij
    if ( ! empty( $redirect ) ) {
        $args['redirect_to'] = $redirect;
    }

    // Złóż URL na customowym slugu z oryginalnymi parametrami
    $base = home_url( '/' . ns_shield_get_custom_slug() );
    return ! empty( $args ) ? add_query_arg( $args, $base ) : $base;
}
add_filter( 'login_url', 'ns_shield_custom_login_url', 10, 2 );

/**
 * Przepina link "Nie pamiętam hasła" na nasz custom slug.
 *
 * @param string $lost_url
 * @param string $redirect
 * @return string
 */
function ns_shield_custom_lostpassword_url( $lost_url, $redirect ) {
    if ( ! get_option( 'ns_shield_login_url_enabled', false ) ) {
        return $lost_url;
    }
    $url = home_url( '/' . ns_shield_get_custom_slug() );
    $url = add_query_arg( 'action', 'lostpassword', $url );
    if ( ! empty( $redirect ) ) {
        $url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
    }
    return $url;
}
add_filter( 'lostpassword_url', 'ns_shield_custom_lostpassword_url', 10, 2 );

/**
 * (Opcjonalnie) przepina URL rejestracji, jeśli gdzieś jest używany.
 *
 * @param string $register_url
 * @return string
 */
function ns_shield_custom_register_url( $register_url ) {
    if ( ! get_option( 'ns_shield_login_url_enabled', false ) ) {
        return $register_url;
    }
    $url = home_url( '/' . ns_shield_get_custom_slug() );
    return add_query_arg( 'action', 'register', $url );
}
add_filter( 'register_url', 'ns_shield_custom_register_url', 10 );

/**
 * Modyfikuje URL-e wp-login.php generowane przez site_url() i network_site_url().
 * Uwaga: podpis funkcji ma parametry opcjonalne, by działał z oboma filtrami.
 *
 * @param string      $url
 * @param string      $path
 * @param string|null $orig_scheme
 * @param int|null    $blog_id
 * @return string
 */
function ns_shield_override_wp_login_url( $url, $path = '', $orig_scheme = null, $blog_id = null ) {
    if (
        get_option( 'ns_shield_login_url_enabled', false )
        && is_string( $path )
        && preg_match( '#^wp-login(\.php)?#', $path )
    ) {
        $query = '';
        if ( false !== ( $pos = strpos( $path, '?' ) ) ) {
            $query = substr( $path, $pos ); // zaczyna się od "?"
        }
        return home_url( '/' . ns_shield_get_custom_slug() . $query );
    }
    return $url;
}
add_filter( 'site_url', 'ns_shield_override_wp_login_url', 10, 4 );
add_filter( 'network_site_url', 'ns_shield_override_wp_login_url', 10, 3 ); // Multisite/Network

/**
 * Podmienia w mailu każdy wp-login.php?... na /<custom-slug>?...
 */
function ns_shield_custom_retrieve_password_message( $message, $key, $user_login, $user_data ) {
    if ( ! get_option( 'ns_shield_login_url_enabled', false ) ) {
        return $message;
    }

    $slug = ns_shield_get_custom_slug();

    $message = preg_replace_callback(
        '#https?://[^/]+/wp-login\.php(\?[^\\s]+)#',
        function( $m ) use ( $slug ) {
            return home_url( '/' . $slug . $m[1] );
        },
        $message
    );

    return $message;
}
add_filter( 'retrieve_password_message', 'ns_shield_custom_retrieve_password_message', 10, 4 );

/**
 * Po udanym logowaniu kieruje do /wp-admin.
 */
function ns_shield_custom_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
    if ( get_option( 'ns_shield_login_url_enabled', false ) && ! is_wp_error( $user ) ) {
        return admin_url();
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'ns_shield_custom_login_redirect', 10, 3 );

/**
 * Po wylogowaniu przekierowuje na nasz custom slug z loggedout=true.
 */
function ns_shield_custom_logout_url( $redirect_to, $requested_redirect_to, $user ) {
    if ( get_option( 'ns_shield_login_url_enabled', false ) ) {
        return home_url( '/' . ns_shield_get_custom_slug() . '?loggedout=true' );
    }
    return home_url( '/wp-login.php?loggedout=true' );
}
add_filter( 'logout_redirect', 'ns_shield_custom_logout_url', 10, 3 );

/**
 * NOWE: przekierowanie wp-login.php?checkemail=... na custom slug.
 * Uruchamiane tylko na stronie logowania (login_init), gdy WP i pluggable są już gotowe.
 */
function ns_shield_redirect_checkemail_on_login() {
    if ( ! get_option( 'ns_shield_login_url_enabled', false ) ) {
        return;
    }
    // nie przekierowuj, jeśli już jesteśmy na customowym slugu
    if ( ns_shield_is_custom_login_request() ) {
        return;
    }
    // tylko gdy przychodzi query checkemail=...
    if ( isset( $_GET['checkemail'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $val  = sanitize_text_field( wp_unslash( $_GET['checkemail'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $slug = ns_shield_get_custom_slug();
        $url  = add_query_arg( 'checkemail', $val, home_url( '/' . $slug ) );
        wp_safe_redirect( $url );
        exit;
    }
}
add_action( 'login_init', 'ns_shield_redirect_checkemail_on_login', 0 );

/**
 * Wyłącza autocomplete w formularzu logowania.
 */
function ns_shield_disable_autocomplete() {
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var loginForm = document.getElementById("loginform");
            if (loginForm) {
                loginForm.setAttribute("autocomplete", "off");
            }
        });
    </script>';
}
add_action( 'login_form', 'ns_shield_disable_autocomplete' );

/**
 * Dodaje nonce do formularza logowania.
 */
function ns_shield_add_login_nonce() {
    wp_nonce_field( 'login_nonce' );
}
add_action( 'login_form', 'ns_shield_add_login_nonce' );

/**
 * Wymusza action dla formularzy login/reset na nasz slug
 * z pełnym zachowaniem query string (action, key, login, wp_lang itd.).
 */
function ns_shield_login_form_action( $action ) {
    if ( ! get_option( 'ns_shield_login_url_enabled', false ) ) {
        return $action;
    }
    $slug  = ns_shield_get_custom_slug();
    $query = '';
    if ( ! empty( $_SERVER['QUERY_STRING'] ) ) {
        $query = '?' . sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) );
    }
    return home_url( '/' . $slug . $query );
}
add_filter( 'login_form_action', 'ns_shield_login_form_action', 10, 1 );

/**
 * Ustaw nagłówki anty-cache wyłącznie na naszym slugu (stabilność przy CDN/WAF).
 */
add_action( 'send_headers', function () {
    if ( ns_shield_is_custom_login_request() ) {
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Pragma: no-cache' );
    }
});

/** Załadowanie strażnika URL */
require_once __DIR__ . '/integrations/login-url-guard.php';

/**
 * Render przełącznika i pola slug w ustawieniach wtyczki.
 */
function ns_shield_change_login_url() {
    $status    = get_option( 'ns_shield_login_url_enabled', false );
    $login_url = get_option( 'ns_shield_login_url', '/mysecurelogin/' );
    ?>
    <div class="change-url-container">
        <label class="switch">
            <input type="checkbox"
                   name="ns_shield_login_url_enabled"
                   id="ns_shield_login_url_enabled"
                   value="1" <?php checked( 1, $status, true ); ?>>
            <span class="slider round"></span>
        </label>
        <div class="tooltip" id="tooltip-change-login-url">
            <?php echo esc_html( ns_shield_get_login_url_tooltip() ); ?>
        </div>
        <input type="text"
               name="ns_shield_login_url"
               id="ns_shield_login_url"
               value="<?php echo esc_attr( $login_url ); ?>"
               placeholder="<?php echo esc_attr__( 'Enter new login URL', 'netsensai-shield-pro' ); ?>"
               class="login-url-input"
               style="display:<?php echo $status ? 'block' : 'none'; ?>;" />
    </div>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var checkbox = document.getElementById("ns_shield_login_url_enabled"),
            field    = document.getElementById("ns_shield_login_url");
        if (checkbox && field) {
            field.style.display = checkbox.checked ? "block" : "none";
            checkbox.addEventListener("change", function() {
                field.style.display = this.checked ? "block" : "none";
            });
        }
    });
    </script>
    <?php
}
