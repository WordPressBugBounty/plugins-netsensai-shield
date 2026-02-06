<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** =======================
 *  A. Helpery niskiego poziomu
 *  ======================= */

/**
 * Zwraca [ path_norm, method, query_string ] – znormalizowane i zmemowane.
 */
if ( ! function_exists( 'ns_shield_req_parts' ) ) {
	function ns_shield_req_parts(): array {
		static $cache = null;
		if ( $cache !== null ) {
			return $cache;
		}

		$uri_raw = isset( $_SERVER['REQUEST_URI'] )
			? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			: '/';

		$path = (string) wp_parse_url( $uri_raw, PHP_URL_PATH );
		$path = $path === '' ? '/' : $path;

		// normalizacja ścieżki: // → /, urldecode, lowercase, bez trailing slasha (poza "/")
		$path = preg_replace( '#/+#', '/', $path );
		$path = strtolower( urldecode( $path ) );
		if ( $path !== '/' ) {
			$path = rtrim( $path, '/' );
		}

		$method_raw = isset( $_SERVER['REQUEST_METHOD'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			: 'GET';
		$method = strtoupper( $method_raw );

		$qs = isset( $_SERVER['QUERY_STRING'] )
			? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			: '';

		return $cache = array( $path, $method, $qs );
	}
}

/** Czy ścieżka jest pod /wp-admin (prefiks)? */
if ( ! function_exists( 'ns_shield_is_wp_admin_path' ) ) {
	function ns_shield_is_wp_admin_path( string $path ): bool {
		return ( $path === '/wp-admin' ) || ( strpos( $path, '/wp-admin/' ) === 0 );
	}
}

/** Jeden „fabryczny” zestaw login-paths (statyczny cache) */
if ( ! function_exists( 'ns_shield_default_login_paths' ) ) {
	function ns_shield_default_login_paths(): array {
		static $paths = null;
		if ( $paths !== null ) {
			return $paths;
		}
		return $paths = array(
			'/wp-login.php', '/wp_login.php', '/login.php',
			'/wp-login',     '/wp_login',     '/login',
		);
	}
}

/** Logo URL (memo) */
if ( ! function_exists( 'ns_shield_get_logo_url' ) ) {
	function ns_shield_get_logo_url() {
		static $url = null;
		if ( $url !== null ) {
			return $url;
		}
		$main_file = dirname( __DIR__, 2 ) . '/netsensai-shield.php';
		if ( ! file_exists( $main_file ) ) {
			return $url = plugins_url( 'assets/ns_logo.png', dirname( __DIR__, 2 ) . '/netsensai-shield.php' );
		}
		return $url = plugins_url( 'assets/ns_logo.png', $main_file );
	}
}

/** Czy path wygląda jak “login-like” (wp-login/wp_admin/login)? */
if ( ! function_exists( 'ns_shield_is_default_login_path' ) ) {
	function ns_shield_is_default_login_path( string $path ): bool {
		if ( in_array( $path, ns_shield_default_login_paths(), true ) ) {
			return true;
		}
		return ns_shield_is_wp_admin_path( $path );
	}
}

/** =======================
 *  B. Init i nagłówki
 *  ======================= */

function ns_shield_login_url_guard_init() {
	// zdejmiemy legacy blokadę, jeśli istnieje
	remove_action( 'wp_loaded', 'ns_shield_block_default_urls' );
	add_action( 'wp_loaded', 'ns_shield_login_url_guard_blocker' );
}
add_action( 'plugins_loaded', 'ns_shield_login_url_guard_init', 20 );

/** Anty-cache tylko na custom slugu — nazwany hook, aby dało się go zdjąć */
function ns_shield_send_no_cache_headers() {
	if ( function_exists( 'ns_shield_is_custom_login_request' ) && ns_shield_is_custom_login_request() ) {
		header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
		header( 'Pragma: no-cache' );
	}
}
add_action( 'send_headers', 'ns_shield_send_no_cache_headers' );

/** =======================
 *  C. Brandowany 404 (z bezpieczeństwem wyjścia)
 *  ======================= */
function ns_shield_die_404_brand( string $reason = '', array $ctx = array() ) {
	// Przygotuj bezpieczne źródła (przed ewentualnymi logami)
	$req_uri = isset( $_SERVER['REQUEST_URI'] )
		? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		: '';

	if ( defined( 'NS_SHIELD_SOFT_MODE' ) && NS_SHIELD_SOFT_MODE ) {
		if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log(
				'NS-SHIELD soft 404: ' .
				$req_uri .
				' reason=' . $reason . ' ctx=' . wp_json_encode( $ctx, JSON_UNESCAPED_SLASHES )
			);
		}
		return;
	}

	if ( headers_sent() === false ) {
		status_header( 404 );
		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
	}

	list( $path, $method ) = ns_shield_req_parts();

	// Zastępniki za $_SERVER[...] (jedno źródło prawdy)
	$referer = isset( $_SERVER['HTTP_REFERER'] )
		? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		: '';

	// Bezpośrednio przez filter_input – brak ostrzeżenia PHPCS i od razu walidacja IP.
	$ip = filter_input( INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP );
	$ip = $ip ? $ip : '';

	if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf(
			'NS-SHIELD 404 brand: reason=%s path=%s method=%s referer=%s ip=%s ctx=%s',
			$reason ?: '-', $path, $method, ( $referer !== '' ? $referer : '-' ), ( $ip !== '' ? $ip : '-' ),
			wp_json_encode( $ctx, JSON_UNESCAPED_SLASHES )
		) );
	}

	$logo_url_esc  = esc_url( ns_shield_get_logo_url() );
	$is_login_like = ns_shield_is_default_login_path( $path );

	$debug_forced  = ( isset( $_GET['ns_shield_debug'] ) && $_GET['ns_shield_debug'] === '1' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$debug_flag    = ( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) || ( defined( 'NS_SHIELD_DEBUG_404' ) && NS_SHIELD_DEBUG_404 ) || $debug_forced );
	$debug_enabled = ( ! $is_login_like && $debug_flag && ( current_user_can( 'manage_options' ) || $debug_forced ) );

	$title = __( 'Access blocked', 'netsensai-shield-pro' );
	$desc  = __( 'This page is protected by NETSENSAI Shield.', 'netsensai-shield-pro' );

	// ======= OUTPUT (bez łączenia surowych zmiennych) =======
	?><!DOCTYPE html>
	<html lang="<?php echo esc_attr( get_locale() ); ?>">
	<head>
		<meta charset="<?php echo esc_attr( get_bloginfo( 'charset' ) ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<title><?php echo esc_html( get_bloginfo( 'name' ) . ' – ' . $title ); ?></title>
	</head>
	<body style="margin:0;background:#0f1115;color:#e7e9ee;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Helvetica,Arial,sans-serif;display:flex;min-height:100vh;align-items:center;justify-content:center;">
		<div style="text-align:center;padding:32px;max-width:820px;">
			<img src="<?php echo $logo_url_esc; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" alt="NETSENSAI Shield" style="width:96px;height:auto;opacity:.95;" />
			<h1 style="font-size:28px;margin:16px 0 8px"><?php echo esc_html( $title ); ?></h1>
			<p style="font-size:16px;margin:0 0 12px"><?php echo esc_html( $desc ); ?></p>

			<?php if ( $reason !== '' ) : ?>
				<p style="opacity:.75;margin-top:8px"><?php echo esc_html( $reason ); ?></p>
			<?php endif; ?>

			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:inline-block;margin-top:20px;padding:10px 16px;border-radius:10px;text-decoration:none;border:1px solid #2a2f3a;color:#e7e9ee;">
				<?php esc_html_e( 'Go to homepage', 'netsensai-shield-pro' ); ?>
			</a>

			<?php if ( $debug_enabled ) : ?>
				<?php
				$rows = array(
					'Reason'        => ( $reason ?: '-' ),
					'Request path'  => $path,
					'Method'        => $method,
					'Referer'       => ( $referer !== '' ? $referer : '-' ),
					'IP'            => ( $ip !== '' ? $ip : '-' ),
					'Logged in'     => ( is_user_logged_in() ? 'yes' : 'no' ),
				);
				foreach ( $ctx as $k => $v ) {
					if ( is_scalar( $v ) ) {
						$rows[ (string) $k ] = (string) $v;
					}
				}
				?>
				<details style="margin-top:18px">
					<summary style="cursor:pointer"><?php esc_html_e( 'Open debug details', 'netsensai-shield-pro' ); ?></summary>
					<div style="margin-top:12px; text-align:left">
						<table style="width:100%;border-collapse:collapse;font-size:14px">
							<?php foreach ( $rows as $rk => $rv ) : ?>
								<tr>
									<td style="padding:6px 10px;border-bottom:1px solid #2a2f3a;opacity:.8"><?php echo esc_html( $rk ); ?></td>
									<td style="padding:6px 10px;border-bottom:1px solid #2a2f3a;"><?php echo esc_html( $rv ); ?></td>
								</tr>
							<?php endforeach; ?>
						</table>
						<p style="opacity:.7;margin-top:8px"><?php esc_html_e( 'Tip: add ?ns_shield_debug=1 to force this block (admins only).', 'netsensai-shield-pro' ); ?></p>
					</div>
				</details>
			<?php endif; ?>
		</div>
	</body>
	</html>
	<?php
	exit;
}

/** Wsteczna kompatybilność */
function ns_shield_render_guard_404( $reason = '' ) {
	ns_shield_die_404_brand( $reason, array( 'origin' => 'render_guard_404' ) );
}

/** =======================
 *  D. Strażnik /wp-admin + wp-login.php
 *  ======================= */

function ns_shield_login_url_guard_blocker() {
	$enabled = get_option( 'ns_shield_login_url_enabled', false );
	if ( ! $enabled ) {
		return;
	}

	if ( function_exists( 'ns_shield_is_custom_login_request' ) && ns_shield_is_custom_login_request() ) {
		return;
	}

	list( $path ) = ns_shield_req_parts();

	$is_default_login = in_array( $path, ns_shield_default_login_paths(), true );

	// /wp-admin dla gościa (z whitelistą)
	if ( ! is_user_logged_in() && ns_shield_is_wp_admin_path( $path ) ) {
		$allow = array( '/wp-admin/admin-ajax.php', '/wp-admin/admin-post.php' );
		$extra = (array) apply_filters( 'ns_shield_wp_admin_allowlist', array() );
		if ( $extra ) {
			$allow = array_unique( array_merge( $allow, array_map( 'strval', $extra ) ) );
		}
		if ( in_array( $path, $allow, true ) ) {
			return;
		}

		ns_shield_die_404_brand( 'guest_wp_admin', array( 'request_path' => $path, 'rule' => 'wp-admin-block' ) );
	}

	// Wejście na domyślne loginy
	if ( $is_default_login ) {

		// 0) specjalny przypadek: checkemail=... → przekieruj na custom slug i ZAKOŃCZ
		if ( isset( $_GET['checkemail'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$val  = sanitize_text_field( wp_unslash( $_GET['checkemail'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$slug = function_exists( 'ns_shield_get_custom_slug' ) ? ns_shield_get_custom_slug() : 'mysecurelogin';
			wp_safe_redirect( add_query_arg( 'checkemail', $val, home_url( '/' . $slug ) ) );
			exit;
		}

		// 1) wylogowanie – redirect na custom
		$loggedout = isset( $_GET['loggedout'] ) ? sanitize_text_field( wp_unslash( $_GET['loggedout'] ) ) : '';
if ( $loggedout === 'true' ) {
    ns_shield_die_404_brand( 'loggedout_on_default_wp_login', array( 'request_path' => $path ) );
}

		// 2) cała reszta wp-login.php → brandowany 404 (stany lostpassword/rp itp. łapie login_init/strict)
		ns_shield_die_404_brand( 'direct_wp_login', array( 'request_path' => $path ) );
	}
}

/**
 * STRICT zapadka na wp-login.php — odpala się podczas strony logowania.
 * Blokuje lostpassword/register/rp/resetpass/checkemail na wp-login.php (w tym //, %2F, itp.).
 */
function ns_shield_lock_default_wp_login() {
	$enabled = get_option( 'ns_shield_login_url_enabled', false );
	if ( ! $enabled ) {
		return;
	}
	if ( function_exists( 'ns_shield_is_custom_login_request' ) && ns_shield_is_custom_login_request() ) {
		return;
	}

	global $pagenow;
	$is_wp_login_page   = ( isset( $pagenow ) && $pagenow === 'wp-login.php' );
	list( $path )       = ns_shield_req_parts();
	$looks_like_login   = (bool) preg_match( '#/(wp-login|wp_login|login)\.php$#i', $path );

	if ( ! $is_wp_login_page && ! $looks_like_login ) {
		return;
	}

	// wylogowanie → redirect na custom
	$loggedout = isset( $_GET['loggedout'] ) ? sanitize_text_field( wp_unslash( $_GET['loggedout'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	if ( $loggedout === 'true' ) {
		$slug = function_exists( 'ns_shield_get_custom_slug' ) ? ns_shield_get_custom_slug() : 'mysecurelogin';
		wp_safe_redirect( home_url( '/' . $slug . '?loggedout=true' ) );
		exit;
	}

	// lostpassword/register/rp/resetpass/checkemail → twardy 404 na DOMYŚLNYM wp-login.php
	$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$blocked_actions = array( 'lostpassword', 'register', 'rp', 'resetpass', 'checkemail' );

	if ( in_array( $action, $blocked_actions, true ) || isset( $_GET['key'] ) || isset( $_GET['login'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		ns_shield_die_404_brand( 'action_on_wp_login_strict', array( 'path_norm' => $path, 'action' => ( $action ?: '-' ) ) );
	}

	// inne wejścia na wp-login.php → 404
	ns_shield_die_404_brand( 'direct_wp_login_strict', array( 'path_norm' => $path ) );
}
add_action( 'login_init', 'ns_shield_lock_default_wp_login', 0 );
