<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display a promotional banner in the admin area.
 * The banner is dismissible and remembers the user's choice via user meta.
 */
function ns_shield_admin_promo_banner() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

// Pobranie informacji o aktualnym ekranie
    $screen = get_current_screen();
    $allowed = array(
        'dashboard',                   // Kokpit
        'plugins',                     // Zainstalowane wtyczki
        'settings_page_secure-options', // Panel ustawień Netsensai-Shield
    );
    if ( ! in_array( $screen->id, $allowed, true ) ) {
        return;
    }

    $dismissed = get_user_meta( get_current_user_id(), 'ns_shield_promo_banner_dismissed', true );
    if ( $dismissed ) {
        return;
    }

    $coupon_code = 'SAVE_SUMMER_2025';
    $heading     = esc_html__( 'Summer Netsensai‑Shield PRO Sale!', 'netsensai-shield' );
    $subtitle    = sprintf(
        /* translators: %s is the coupon code wrapped in a styled <span> tag. */
        __( 'Protect your website or store with us forever — upgrade to PRO and enjoy 15%% off! Use coupon code %s at checkout. Offer valid until 31 August 2025.', 'netsensai-shield' ),
        '<span class="ns-shield-coupon" data-code="' . esc_attr( $coupon_code ) . '" style="background:#333333;color:#E6DB00;padding:2px 4px;border-radius:3px;cursor:pointer;">' . esc_html( $coupon_code ) . '</span>'
    );
    $cta_text = esc_html__( 'Grab 15% off now', 'netsensai-shield' );
    $cta_link = esc_url( 'https://netsensai.pl/store' );
    $nonce    = wp_create_nonce( 'ns_shield_promo_dismiss' );

    // Build the logo URL relative to the main plugin file.
    $logo_url = plugins_url( 'assets/ns_logo.png', dirname( __FILE__, 3 ) . '/netsensai-shield.php' );
    ?>
    <div class="notice notice-info ns-shield-promo-banner" style="position:relative;padding:20px 30px;margin-top:20px;background:#fff;border-left:4px solid #e6db00;">
        <div style="display:flex;flex-wrap:wrap;align-items:center;">
            <!-- Left column: logo and discount text side-by-side -->
            <div style="flex:0 0 150px;display:flex;align-items:center;gap:4px;margin-right:10px;">
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr__( 'Netsensai Shield Logo', 'netsensai-shield' ); ?>" style="width:120px;height:120px;">
                <div style="display:flex;flex-direction:column;line-height:1;margin-left:4px;">
                    <span style="font-size:36px;font-weight:bold;color:#e6115e;">15%</span>
                    <span style="font-size:24px;font-weight:bold;color:#e6115e;margin-top:-4px;">OFF</span>
                </div>
            </div>
            <!-- Right column: headline, message and CTA -->
            <div style="flex:1 1 auto;padding-left:12px;">
                <h3 style="margin:0 0 5px;"><?php echo esc_html( $heading ); ?></h3>
                <p style="margin:0 0 10px;font-size:16px;"><?php echo wp_kses_post( $subtitle ); ?></p>
                <a href="<?php echo esc_url( $cta_link ); ?>" target="_blank" rel="noopener noreferrer"
                   style="display:inline-block;padding:8px 14px;background:#2271b1;color:#fff;border-radius:3px;text-decoration:none;font-weight:bold;">
                    <?php echo esc_html( $cta_text ); ?>
                </a>
            </div>
        </div><!-- end flex container -->
        <!-- Dismiss button outside flex container -->
        <button type="button" class="notice-dismiss ns-shield-promo-dismiss" style="position:absolute;top:8px;right:8px;">
            <span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'netsensai-shield' ); ?></span>
        </button>
    </div><!-- end notice container -->

    <script>
    (function($) {
        var dismissBtn = document.querySelector('.ns-shield-promo-dismiss');
        if (dismissBtn) {
            dismissBtn.addEventListener('click', function () {
                var data = {
                    action: 'ns_shield_dismiss_promo_banner',
                    nonce: '<?php echo esc_js( $nonce ); ?>'
                };
                fetch(ajaxurl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams(data)
                });

                // fadeOut effect
                $(dismissBtn).closest('.ns-shield-promo-banner').fadeOut();
            });
        }

        document.querySelectorAll('.ns-shield-coupon').forEach(function(el) {
            el.addEventListener('click', function() {
                var code = el.dataset.code || el.textContent;
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(code).then(function() {
                        alert('<?php echo esc_js( __( 'Coupon code copied to clipboard!', 'netsensai-shield' ) ); ?>');
                    });
                }
            });
        });
    })(jQuery);
    </script>
    <?php
}
add_action( 'admin_notices', 'ns_shield_admin_promo_banner' );

/**
 * AJAX handler to dismiss the promo banner.
 */
function ns_shield_dismiss_promo_banner() {
    check_ajax_referer( 'ns_shield_promo_dismiss', 'nonce' );
    update_user_meta( get_current_user_id(), 'ns_shield_promo_banner_dismissed', 1 );
    wp_send_json_success();
}
add_action( 'wp_ajax_ns_shield_dismiss_promo_banner', 'ns_shield_dismiss_promo_banner' );
