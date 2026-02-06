<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Modyfikuje meta‑linki wiersza wtyczki NETSENSAI‑Shield na stronie Wtyczki.
 *
 * Usuwa domyślny link „View details”, dodaje link do prezentacji PDF
 * (wersja PL lub EN w zależności od ustawionego języka WordPressa) oraz
 * link „Oceń wtyczkę” z gwiazdkami, który prowadzi do formularza recenzji
 * na stronie WordPress.org.  Funkcja usuwa również duplikaty tych linków,
 * jeśli już istnieją w tablicy.
 *
 * @param array  $links Istniejące meta‑linki wtyczki.
 * @param string $file  Ścieżka do pliku wtyczki, względna wobec katalogu plugins.
 *
 * @return array Zmieniona tablica linków.
 */
function ns_shield_custom_row_meta( $links, $file ) {
    // Modyfikujemy tylko wiersz wtyczki netsensai‑shield/netsensai‑shield.php.
    if ( 'netsensai-shield/netsensai-shield.php' !== $file ) {
        return $links;
    }

    // 1) Usuń domyślny link „View details” (zawiera parametr plugin-information).
    foreach ( $links as $i => $link ) {
        if ( strpos( $link, 'plugin-information' ) !== false ) {
            unset( $links[ $i ] );
        }
    }

    // 2) Wybierz odpowiedni link do prezentacji w zależności od języka.
    // Jeśli bieżąca lokalizacja zaczyna się od "pl", używamy polskiej prezentacji,
    // w przeciwnym razie angielskiej.
    $locale    = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
    $is_polish = ( strpos( $locale, 'pl' ) === 0 );
    $presentation_url = $is_polish
        ? 'https://netsensai.pl/wp-content/uploads/2025/04/NS_prezentacja.pdf'
        : 'https://netsensai.pl/wp-content/uploads/2025/06/NETSENSAI–Shield_presentation_en.pdf';

    // Tekst linku „Prezentacja/Presentation” tłumaczony w domenie netsensai‑shield.
    $presentation_text = esc_html__( 'Presentation', 'netsensai-shield' );
    $links[]           = sprintf(
        '<a href="%s" target="_blank">%s</a>',
        esc_url( $presentation_url ),
        $presentation_text
    );

    // 3) Przygotuj link „Oceń wtyczkę” z gwiazdkami.
    $rate_text = esc_html__( 'Rate the plugin', 'netsensai-shield' );
    $rate_link = sprintf(
        '<a href="%s" class="ns-rate-link" target="_blank">%s <span class="stars">★★★★★</span></a>',
        esc_url( 'https://wordpress.org/support/plugin/netsensai-shield/reviews/#new-post' ),
        $rate_text
    );

    // 4) Usuń ewentualne duplikaty linków „Rate the plugin”.
    foreach ( $links as $i => $link ) {
        if ( strpos( $link, $rate_text ) !== false ) {
            unset( $links[ $i ] );
        }
    }

    // 5) Dodaj link do recenzji na koniec listy.
    $links[] = $rate_link;

    return $links;
}
add_filter( 'plugin_row_meta', 'ns_shield_custom_row_meta', 10, 2 );

/**
 * Dodaje styl CSS dla gwiazdek w linku „Oceń wtyczkę”.
 *
 * Gwiazdki otrzymują ciemnozłoty kolor, a po najechaniu myszą dodawany jest
 * lekki efekt poświaty.  Linkowi usuwamy podkreślenie, aby pasował do innych
 * meta‑linków w wierszu wtyczki.
 */
function ns_shield_rate_link_styles() {
    $css = '
        .ns-rate-link .stars {
            color: #b8860b;
        }
        .ns-rate-link {
            text-decoration: none;
        }
        .ns-rate-link:hover .stars {
            text-shadow: 0 0 2px #fff;
        }
    ';
    wp_add_inline_style( 'wp-admin', $css );
}
add_action( 'admin_enqueue_scripts', 'ns_shield_rate_link_styles' );