<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Send a personalized email notification when the custom login URL changes.
 *
 * This function is hooked to the `update_option_ns_shield_login_url` action, which
 * fires after the value of the `ns_shield_login_url` option has been updated in
 * the WordPress database【573449036948430†L325-L336】. It checks whether the
 * custom login feature is enabled, normalizes the old and new slugs, and
 * constructs an HTML email. The language of the email is determined
 * dynamically based on the current locale (Polish for `pl*` locales, English
 * otherwise). A logo from the plugin’s `assets` directory is included via
 * a remote URL.
 *
 * @param string $old_value   Previous slug saved in the option.
 * @param string $new_value   New slug saved in the option.
 * @param string $option_name Name of the option being updated (unused).
 */
function ns_shield_send_login_url_change_email( $old_value, $new_value, $option_name ) {
    // Only send if the custom login feature is enabled.
    if ( ! get_option( 'ns_shield_login_url_enabled', false ) ) {
        return;
    }

    // Normalize slugs: remove leading/trailing slashes and convert to lowercase.
    $old_slug = untrailingslashit( strtolower( ltrim( $old_value, '/' ) ) );
    $new_slug = untrailingslashit( strtolower( ltrim( $new_value, '/' ) ) );

    // Bail out if the slug hasn't actually changed.
    if ( $old_slug === $new_slug ) {
        return;
    }

    // Construct full URLs for the old and new login endpoints.
    $old_login_url = home_url( '/' . $old_slug . '/' );
    $new_login_url = home_url( '/' . $new_slug . '/' );

    // Email recipient: default to the site administrator's email.
    $to = get_option( 'admin_email' );
    $blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

    // Determine locale – favour determine_locale() for user context; fallback to get_locale().
    $locale    = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
    $is_polish = ( strpos( $locale, 'pl' ) === 0 );

    // Build URL to the logo: two levels up from this file to reach plugin root, then assets/ns_logo.png.
    $logo_url = plugins_url( '../../assets/ns_logo.png', __FILE__ );

    // Prepare subject and message depending on locale.
    if ( $is_polish ) {
        // Polish subject line.
        $subject = sprintf( '[%s] Zmiana niestandardowego adresu logowania', $blogname );

        /*
         * Polish message body (HTML).
         * Logo umieszczamy na górze i zmniejszamy jego szerokość, aby wizualnie nie dominował.
         * Tekst jest bardziej przyjazny i jasny: informujemy użytkownika o zmianie,
         * podajemy nowy i poprzedni link, datę zmiany oraz konkretne zalecenia.
         */
        $message  = '<p style="text-align:center;"><img src="' . esc_url( $logo_url ) . '" alt="NETSENSAI‑SHIELD Logo" style="max-width:120px;height:auto;margin-bottom:10px;" /></p>';
        $message .= '<p>Cześć! Jako Twój wirtualny agent <strong>NETSENSAI‑SHIELD</strong> informuję, że właśnie zmieniłeś adres logowania do panelu WordPress.</p>';
        $message .= '<p><strong>Nowy link do logowania:</strong> ' . esc_url( $new_login_url ) . '<br />';
        $message .= '<strong>Poprzedni link:</strong> ' . esc_url( $old_login_url ) . '<br />';
        $message .= '<strong>Data zmiany:</strong> ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) . '</p>';
        $message .= '<p>Zaktualizuj proszę swoje zakładki i zapisz nowy link w bezpiecznym miejscu, aby uniknąć problemów z logowaniem w przyszłości.</p>';
    } else {
        // English subject line.
        $subject = sprintf( '[%s] Custom login URL changed', $blogname );

        /*
         * English message body (HTML).
         * The logo appears at the top in a smaller size. The wording is friendly and clear,
         * letting the user know what changed, providing both URLs and the change date,
         * and advising them on what to do next.
         */
        $message  = '<p style="text-align:center;"><img src="' . esc_url( $logo_url ) . '" alt="NETSENSAI‑SHIELD Logo" style="max-width:120px;height:auto;margin-bottom:10px;" /></p>';
        $message .= '<p>Hi there! As your <strong>NETSENSAI‑SHIELD</strong> assistant, I wanted to let you know that you&#8217;ve just changed your WordPress login address.</p>';
        $message .= '<p><strong>New login link:</strong> ' . esc_url( $new_login_url ) . '<br />';
        $message .= '<strong>Previous link:</strong> ' . esc_url( $old_login_url ) . '<br />';
        $message .= '<strong>Change date:</strong> ' . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) . '</p>';
        $message .= '<p>Please update your bookmarks and keep this new link in a safe place to avoid any login issues in the future.</p>';
    }

    // Use HTML headers so email clients render the markup correctly.
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    // Send the email using wp_mail()【14522541485779†L120-L132】.
    wp_mail( $to, $subject, $message, $headers );
}

// Hook the function to run after the custom login URL option is updated.
add_action( 'update_option_ns_shield_login_url', 'ns_shield_send_login_url_change_email', 10, 3 );