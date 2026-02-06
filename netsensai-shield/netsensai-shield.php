<?php
/**
 * NETSENSAI Shield
 *
 * Plugin Name: NETSENSAI Shield
 * Plugin URI: https://www.netsensai.pl/store/
 * Description: NETSENSAI Shield is a security plugin designed to enhance WordPress site protection by offering essential security features based on best practice principles.
 * Version: 1.4.9
 * Author: Rafał Gierlicki
 * Author URI: https://www.netsensai.pl
 * Text Domain: netsensai-shield
 * Contributors: netsensai
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Filter admin title so it's never null.
 *
 * @param string $title Admin page title.
 * @return string Modified title.
 */
function ns_shield_fix_admin_title( $title ) {
    return (string) $title;
}
add_filter( 'admin_title', 'ns_shield_fix_admin_title', 0, 1 );

/**
 * Define tooltip function for login URL explanation.
 */
if ( ! function_exists( 'ns_shield_get_login_url_tooltip' ) ) {
    function ns_shield_get_login_url_tooltip() {
        return __(
            'Changing the login URL helps protect your site from brute-force attacks aimed at the default wp-login.php endpoint. If the default URL remains unchanged, attackers could easily target it to attempt password cracking or credential stuffing attacks.',
            'netsensai-shield'
        );
    }
}

/**
 * Include plugin function files.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/login_url_functions.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/disable_wp_api_json.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/disable_xml_rpc.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/disable_app_passwords.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/disable_file_editor.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/apply_security_headers.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/disable_directory_indexing.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/disable_default_admin.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/apply_hsts_header.php';
//require_once plugin_dir_path( __FILE__ ) . 'includes/integrations/class-ns-shield-cache-integrator.php';
// Wczytaj integracje
$integration_dir = plugin_dir_path( __FILE__ ) . 'includes/integrations/';
if ( is_dir( $integration_dir ) ) {
    foreach ( glob( $integration_dir . '*.php' ) as $integration_file ) {
        require_once $integration_file;
    }
}

// Inicjalizacja integratora cache
function ns_shield_init_cache_integrator() {
    new NS_Shield_Cache_Integrator();
}
add_action( 'plugins_loaded', 'ns_shield_init_cache_integrator' );

/**
 * Add settings link on the plugins page.
 *
 * @param array $links Array of action links.
 * @return array Modified links.
 */
function ns_shield_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=secure-options">' . esc_html__( 'Settings', 'netsensai-shield' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ns_shield_settings_link' );

/* =====================
   SETTINGS PAGE
   ===================== */

/**
 * Display the settings page.
 */
function ns_shield_secure_options_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'netsensai-shield' ) );
    }

    // Ścieżka do logo
    $logo_url = plugin_dir_url( __FILE__ ) . 'assets/ns_logo.png';

    // Tworzymy treść dla popupu z możliwością tłumaczenia.
    // Używamy __() dla tekstu zawierającego znaczniki <a> aby linki były działające.
    $enable_html = sprintf(
        '<div class="ns-popup-logo-container">
            <img src="%1$s" alt="%2$s" class="ns-popup-logo" />
        </div>
        <div class="ns-popup-text">
            <p>%3$s</p>
            <p>%4$s</p>
            <p>%5$s</p>
            <p><strong>%6$s</strong> <a href="https://netsensai.pl/store" target="_blank">netsensai.pl/store</a></p>
        </div>
        <div class="ns-popup-button-container">
            <button id="ns-shield-modal-ok" class="ns-modal-ok-button">%7$s</button>
        </div>',
        esc_url( $logo_url ),
        esc_attr__( 'Netsensai Shield Logo', 'netsensai-shield' ),
        // Używamy __() zamiast esc_html__() dla akapitu ze znacznikami <a>
        __( 'Your website now achieves top scores in the most popular security scanners. (Check <a href="https://securityheaders.com" target="_blank">securityheaders.com</a> or <a href="https://observatory.mozilla.org" target="_blank">Mozilla Observatory</a>) Great job!', 'netsensai-shield' ),
        esc_html__( 'But in the PRO Club, we do even more: we detect, block, and support.', 'netsensai-shield' ),
        esc_html__( 'If an attack occurs, you get 3 months of assistance on us!', 'netsensai-shield' ),
        esc_html__( 'Check out:', 'netsensai-shield' ),
        esc_html__( 'OK', 'netsensai-shield' )
    );

    $disable_html = sprintf(
        '<h2 style="text-align:center; font-size:1.3em; margin-bottom:10px; color:#000;">%1$s</h2>
         <p style="font-size:1.1em; line-height:1.4; color:#000;">%2$s</p>',
        esc_html__( 'Oops...', 'netsensai-shield' ),
        esc_html__( 'The option has been disabled.', 'netsensai-shield' )
    );
    ?>
    <div id="netsensai-shield-plugin" class="wrap">

        <!-- PRO version banner -->
        <div style="border: 1px solid #555; padding: 5px; margin: 5px 0; background-color: transparent; text-align: center; color: #fff;">
            <p style="font-size: 1.2em; font-weight: bold; margin-bottom: 5px;">
                <?php esc_html_e( 'Upgrade to NETSENSAI-SHIELD PRO for enhanced protection and advanced features.', 'netsensai-shield' ); ?>
            </p>
            <p>
                <?php esc_html_e( 'Get yours now at:', 'netsensai-shield' ); ?>
                <a href="https://netsensai.pl/store" target="_blank" rel="noopener noreferrer" style="color: #fff; text-decoration: underline;">
                    https://netsensai.pl/store
                </a>
            </p>
        </div>

        <h2><?php echo esc_html__( 'Security Options', 'netsensai-shield' ); ?></h2>

        <!-- Logos display -->
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 10px;">
            <a href="https://www.netsensai.pl/store/" target="_blank" style="display: flex; align-items: center; gap: 20px;">
                <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'assets/ns_logo.png' ); ?>" 
                     alt="<?php echo esc_attr__( 'Netsensai-Shield Logo', 'netsensai-shield' ); ?>" 
                     style="width: 200px; height: auto; margin-bottom: -20px;">
                <img src="<?php echo esc_url( plugin_dir_url( __FILE__ ) . 'assets/netsensai.pl_logo.png' ); ?>" 
                     alt="<?php echo esc_attr__( 'Netsensai-Logo', 'netsensai-shield' ); ?>" 
                     style="width: 220px; height: auto; margin-bottom: -20px;">
            </a>
        </div>

        <!-- Konfiguracja modala -->
        <script type="text/javascript">
            window.nsShieldModalConfig = {
                modalShownFor: '',
                enableContentHTML: <?php echo wp_json_encode( $enable_html ); ?>,
                disableContentHTML: <?php echo wp_json_encode( $disable_html ); ?>
            };
        </script>

        <form method="post" action="options.php">
            <?php
                settings_fields( 'ns_shield_options_group' );
                do_settings_sections( 'secure-options' );
                submit_button();
            ?>
        </form>

        <!-- Include modal popup file from includes/ -->
        <?php
$modal_file = plugin_dir_path( __FILE__ ) . 'includes/modal_popup.php';
if ( file_exists( $modal_file ) ) {
    include $modal_file;
}
// Jeśli plik nie zostanie znaleziony, nie rób nic – error_log() zostało usunięte.
?>
    </div> <!-- End of wrap -->
    <?php
}

/**
 * Add settings menu in the admin.
 */
function ns_shield_secure_options_menu() {
    add_options_page(
        esc_html__( 'Security Options', 'netsensai-shield' ),
        esc_html__( 'Security Options', 'netsensai-shield' ),
        'manage_options',
        'secure-options',
        'ns_shield_secure_options_page'
    );
}
add_action( 'admin_menu', 'ns_shield_secure_options_menu' );

/**
 * Register settings and fields.
 */
function ns_shield_secure_options_settings() {
    register_setting( 'ns_shield_options_group', 'ns_shield_login_url', 'sanitize_text_field' );
    register_setting( 'ns_shield_options_group', 'ns_shield_login_url_enabled', 'sanitize_text_field' );
    register_setting( 'ns_shield_options_group', 'ns_shield_wp_api_json', 'sanitize_text_field' );
    register_setting( 'ns_shield_options_group', 'ns_shield_xml_rpc', 'sanitize_text_field' );
    register_setting( 'ns_shield_options_group', 'ns_shield_file_editor', 'sanitize_text_field' );
    register_setting( 'ns_shield_options_group', 'ns_shield_app_passwords', 'absint' );
    register_setting( 'ns_shield_options_group', 'ns_shield_security_headers', 'sanitize_text_field' );
    register_setting( 'ns_shield_options_group', 'ns_shield_directory_indexing', 'sanitize_text_field' );
    register_setting( 'ns_shield_options_group', 'ns_shield_default_admin', 'absint' );
    register_setting( 'ns_shield_options_group', 'ns_shield_new_admin_login', 'sanitize_text_field' );
    register_setting( 'ns_shield_options_group', 'ns_shield_hsts', 'sanitize_text_field' );
    register_setting( 'ns_shield_options_group', 'ns_shield_debug', 'sanitize_text_field' );
    // Register new settings for CSP headers.
    register_setting( 'ns_shield_options_group', 'ns_shield_csp_header_light', 'intval' );
    register_setting( 'ns_shield_options_group', 'ns_shield_csp_header_hard', 'ns_shield_sanitize_csp_hard' );

    // Level 1: Basic Security (login URL options etc.)
    add_settings_section( 'ns_shield_level_1', esc_html__( 'Level 1: Basic Security', 'netsensai-shield' ), null, 'secure-options' );
    add_settings_field( 'ns_shield_login_url', esc_html__( 'Change Login URL', 'netsensai-shield' ), 'ns_shield_change_login_url', 'secure-options', 'ns_shield_level_1' );
    add_settings_field( 'ns_shield_default_admin', esc_html__( 'Disable Default Admin', 'netsensai-shield' ), 'ns_shield_disable_default_admin', 'secure-options', 'ns_shield_level_1' );
    add_settings_field( 'ns_shield_xml_rpc', esc_html__( 'Disable XML-RPC', 'netsensai-shield' ), 'ns_shield_field_xml_rpc', 'secure-options', 'ns_shield_level_1' );

    // Level 2: Intermediate Security
    add_settings_section( 'ns_shield_level_2', esc_html__( 'Level 2: Intermediate Security', 'netsensai-shield' ), null, 'secure-options' );
    add_settings_field( 'ns_shield_wp_api_json', esc_html__( 'Disable WP API JSON', 'netsensai-shield' ), 'ns_shield_field_wp_api_json', 'secure-options', 'ns_shield_level_2' );
    add_settings_field( 'ns_shield_file_editor', esc_html__( 'Disable File Editor', 'netsensai-shield' ), 'ns_shield_field_file_editor', 'secure-options', 'ns_shield_level_2' );
    add_settings_field( 'ns_shield_app_passwords', esc_html__( 'Disable WordPress Application Passwords', 'netsensai-shield' ), 'ns_shield_field_app_passwords', 'secure-options', 'ns_shield_level_2' );

    // Level 3: Advanced Security
    add_settings_section( 'ns_shield_level_3', esc_html__( 'Level 3: Advanced Security', 'netsensai-shield' ), null, 'secure-options' );
    add_settings_field( 'ns_shield_directory_indexing', esc_html__( 'Disable Directory Indexing', 'netsensai-shield' ), 'ns_shield_field_directory_indexing', 'secure-options', 'ns_shield_level_3' );
    add_settings_field( 'ns_shield_hsts', esc_html__( 'Enable HSTS', 'netsensai-shield' ), 'ns_shield_field_hsts', 'secure-options', 'ns_shield_level_3' );
    add_settings_field( 'ns_shield_security_headers', esc_html__( 'Apply Security Headers', 'netsensai-shield' ), 'ns_shield_field_security_headers', 'secure-options', 'ns_shield_level_3' );
}
add_action( 'admin_init', 'ns_shield_secure_options_settings' );

/* =====================
   FIELD CALLBACKS
   ===================== */

function ns_shield_field_xml_rpc() {
    $checked = get_option( 'ns_shield_xml_rpc', 0 ) ? 'checked' : '';
    echo '<label class="switch">';
    echo '<input type="checkbox" name="ns_shield_xml_rpc" value="1" ' . esc_attr( $checked ) . '>';
    echo '<span class="slider round"></span>';
    echo '<div class="tooltip">' .
         esc_html__(
            'Disabling XML-RPC blocks unauthorized remote access attempts, which can enhance security. If XML-RPC remains enabled, hackers might attempt to perform DDoS attacks by sending multiple requests or brute-force password attacks to gain control over your site.',
            'netsensai-shield'
         ) .
         '</div>';
    echo '</label>';
}

function ns_shield_field_wp_api_json() {
    $checked = get_option( 'ns_shield_wp_api_json', 0 ) ? 'checked' : '';
    echo '<label class="switch">';
    echo '<input type="checkbox" name="ns_shield_wp_api_json" value="1" ' . esc_attr( $checked ) . '>';
    echo '<span class="slider round"></span>';
    echo '<div class="tooltip">' .
         esc_html__(
            'Disabling WP API JSON can protect your site from unauthorized access to sensitive data through the API. If left enabled, hackers may exploit WP API JSON to gather information about your site’s structure or perform enumeration attacks on users, which can lead to brute-force attacks.',
            'netsensai-shield'
         ) .
         '</div>';
    echo '</label>';
}

function ns_shield_field_file_editor() {
    $checked = get_option( 'ns_shield_file_editor', 0 ) ? 'checked' : '';
    echo '<label class="switch">';
    echo '<input type="checkbox" name="ns_shield_file_editor" value="1" ' . esc_attr( $checked ) . '>';
    echo '<span class="slider round"></span>';
    echo '<div class="tooltip">' .
         esc_html__(
            'Disabling the file editor in the WP dashboard prevents unauthorized or accidental code changes. If left enabled, attackers who gain access to your admin panel could inject malicious code into your theme or plugin files, leading to a defacement of the site or the deployment of malware.',
            'netsensai-shield'
         ) .
         '</div>';
    echo '</label>';
}

function ns_shield_field_app_passwords() {
    $checked = get_option( 'ns_shield_app_passwords', 0 ) ? 'checked' : '';
    echo '<label class="switch">';
    echo '<input type="checkbox" name="ns_shield_app_passwords" value="1" ' . esc_attr( $checked ) . '>';
    echo '<span class="slider round"></span>';
    echo '<div class="tooltip">' .
         esc_html__(
            'Disabling application passwords secures against creating unauthorized accesses to your site’s API. If left enabled, hackers may exploit application passwords to gain persistent access to your site, enabling them to execute unauthorized API requests or even escalate their privileges.',
            'netsensai-shield'
         ) .
         '</div>';
    echo '</label>';
}

function ns_shield_field_security_headers() {
    $checked = get_option( 'ns_shield_security_headers', 0 ) ? 'checked' : '';
    echo '<label class="switch">';
    echo '<input type="checkbox" name="ns_shield_security_headers" id="ns-shield-preload-checkbox" value="1" ' . esc_attr( $checked ) . '>';
    echo '<span class="slider round"></span>';
    echo '<div class="tooltip">' .
         esc_html__(
            'Applying security headers can protect your site from XSS attacks and other threats. Without them, your site could be vulnerable to cross-site scripting (XSS) attacks or clickjacking, allowing attackers to steal sensitive data or trick users into executing malicious actions.',
            'netsensai-shield'
         ) .
         '</div>';
    echo '</label>';
}

/**
 * FIELD CALLBACK: Directory Indexing.
 */
function ns_shield_field_directory_indexing() {
    $checked = get_option( 'ns_shield_directory_indexing', 0 ) ? 'checked' : '';
    echo '<label class="switch">';
    echo '<input type="checkbox" name="ns_shield_directory_indexing" value="1" ' . esc_attr( $checked ) . '>';
    echo '<span class="slider round"></span>';
    echo '<div class="tooltip">' .
         esc_html__(
            'Directory indexing allows attackers to list and access files in directories that lack an index file, exposing sensitive files and configurations. If directory indexing is not disabled, attackers can execute Directory Traversal attacks, gaining access to configuration files, logs, or even databases.',
            'netsensai-shield'
         ) .
         '</div>';
    echo '</label>';
}

/**
 * FIELD CALLBACK: HSTS.
 */
function ns_shield_field_hsts() {
    $checked = get_option( 'ns_shield_hsts', 0 ) ? 'checked' : '';
    echo '<label class="switch">';
    echo '<input type="checkbox" name="ns_shield_hsts" value="1" ' . esc_attr( $checked ) . '>';
    echo '<span class="slider round"></span>';
    echo '<div class="tooltip">' .
         esc_html__(
            'HTTP Strict Transport Security (HSTS) enforces HTTPS, ensuring that all communication between the browser and the server is encrypted. This is critical for protecting sensitive user data and preventing man-in-the-middle attacks.',
            'netsensai-shield'
         ) .
         '</div>';
    echo '</label>';
}

/**
 * Flush rewrite rules after settings update.
 */
function ns_shield_flush_rewrite_rules_on_settings_update() {
    if ( is_admin() && isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) {
        flush_rewrite_rules();
    }
}
add_action( 'admin_init', 'ns_shield_flush_rewrite_rules_on_settings_update', 20 );

/**
 * Flush rewrite rules on plugin activation.
 */
function ns_shield_activation_flush() {
    if ( function_exists( 'ns_shield_add_rewrite_rule' ) ) {
        ns_shield_add_rewrite_rule();
    }
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ns_shield_activation_flush' );

/**
 * Flush rewrite rules on plugin deactivation.
 */
function ns_shield_deactivation_flush() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'ns_shield_deactivation_flush' );


// Wskazanie uruchomienia pliku script.js
function ns_shield_admin_enqueue_assets( $hook_suffix ) {
    // tylko na /wp-admin/options-general.php?page=secure-options
    if ( 'settings_page_secure-options' !== $hook_suffix ) {
        return;
    }

    // a) Styl
    wp_enqueue_style(
        'ns_shield-style',
        plugin_dir_url( __FILE__ ) . 'assets/style.css',
        [],
        filemtime( plugin_dir_path( __FILE__ ) . 'assets/style.css' )
    );

    // b) Skrypt z filemtime jako wersją
    $script_path = plugin_dir_path( __FILE__ ) . 'assets/script.js';
    $ver = file_exists( $script_path ) ? filemtime( $script_path ) : false;
    wp_enqueue_script(
        'ns_shield-script',
        plugin_dir_url( __FILE__ ) . 'assets/script.js',
        ['jquery'],
        $ver,
        true
    );

    // c) Przekazanie ustawień do JS
    wp_localize_script( 'ns_shield-script', 'nsShieldSettings', [
        'pageSlug' => 'secure-options',
    ] );
}
add_action( 'admin_enqueue_scripts', 'ns_shield_admin_enqueue_assets' );


?>