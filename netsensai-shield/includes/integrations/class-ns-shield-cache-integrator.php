<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists( 'NS_Shield_Cache_Integrator' ) ) {

    class NS_Shield_Cache_Integrator {

        protected $htaccess_path;

        public function __construct() {
            // Ścieżka do .htaccess
            $this->htaccess_path = ABSPATH . '.htaccess';

            // Hook po zapisie ustawień
            add_action( 'update_option_ns_shield_settings', array( $this, 'on_settings_updated' ) );
        }

        /**
         * Główna akcja po zapisaniu ustawień.
         */
        public function on_settings_updated() {
            $this->maybe_clear_wpfc_cache();
            $this->maybe_update_htaccess_headers();
        }

        /**
         * Wykrywa i czyści cache WP Fastest Cache, jeśli wtyczka jest aktywna.
         */
        public function maybe_clear_wpfc_cache() {
            if ( class_exists( 'WpFastestCache' ) ) {
                $wpfc = new \WpFastestCache();
                if ( method_exists( $wpfc, 'deleteCache' ) ) {
                    $wpfc->deleteCache();
                }
            }
        }

        /**
         * Aktualizuje nagłówki bezpieczeństwa w pliku .htaccess.
         */
        public function maybe_update_htaccess_headers() {
            // Inicjalizacja WP Filesystem API
            if ( ! function_exists( 'WP_Filesystem' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            WP_Filesystem();
            global $wp_filesystem;

            // Sprawdź, czy możemy zapisywać
            if ( ! $wp_filesystem->is_writable( $this->htaccess_path ) ) {
                return;
            }

            // Pobierz obecny zawartość
            $htaccess = $wp_filesystem->get_contents( $this->htaccess_path );

            $start_marker = '# BEGIN NS_SHIELD_SECURITY_HEADERS';
            $end_marker   = '# END NS_SHIELD_SECURITY_HEADERS';

            // Zbuduj blok nagłówków jako ciąg
            $lines = array(
                $start_marker,
                '<IfModule mod_headers.c>',
                'Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"',
                'Header always set Content-Security-Policy "default-src \'self\';"',
                'Header always set X-Content-Type-Options "nosniff"',
                'Header always set X-XSS-Protection "1; mode=block"',
                'Header always set Referrer-Policy "strict-origin-when-cross-origin"',
                '</IfModule>',
                $end_marker,
            );
            $headers_block = implode( "\n", $lines );

            // Usuń poprzednią wersję bloku (jeśli istnieje)
            $pattern = '/' . preg_quote( $start_marker, '/' ) .
                       '.*?' .
                       preg_quote( $end_marker, '/' ) .
                       '/s';

            if ( preg_match( $pattern, $htaccess ) ) {
                $htaccess = preg_replace( $pattern, $headers_block, $htaccess );
            } else {
                $htaccess .= "\n\n" . $headers_block . "\n";
            }

            // Zapisz zmiany
            $wp_filesystem->put_contents( $this->htaccess_path, $htaccess, FS_CHMOD_FILE );
        }
    }

    // Inicjalizacja integratora
    new NS_Shield_Cache_Integrator();
}
