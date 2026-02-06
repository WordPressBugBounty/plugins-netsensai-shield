<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display a modal popup when the “Disable WP API JSON” option is enabled.
 */
function ns_shield_wpapi_modal_popup() {
    // Inject the modal markup and script only in the admin area.
    if ( ! is_admin() ) {
        return;
    }

    $logo_url = plugins_url( 'assets/ns_logo.png', dirname( __FILE__, 3 ) . '/netsensai-shield.php' );

    // Prepare the HTML for the modal.  We inline basic styles to match the
    // existing popup design without relying on external CSS.
    ?>
    <div id="ns-shield-wpapi-modal" class="ns-shield-modal" style="display:none;">
        <div class="ns-shield-modal-overlay" style="
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        "></div>
        <div class="ns-shield-modal-content" style="
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 20px;
            border: 1px solid #E6DB00;
            z-index: 1001;
            max-width: 500px;
            width: 90%;
            color: #000;
        ">
            <div class="ns-popup-logo-container" style="text-align: center; margin-bottom: 10px;">
                <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr__( 'Netsensai Shield Logo', 'netsensai-shield' ); ?>" style="max-width: 150px; height: auto;" />
            </div>
            <div class="ns-popup-text" style="margin-bottom: 15px; font-size: 14px; line-height: 1.4;">
                <p><?php echo esc_html__( 'Disabling the WP REST API can interfere with plugins such as WooCommerce or contact form plugins. If your site depends on these tools, enabling this setting may cause them to stop functioning.', 'netsensai-shield' ); ?></p>
                <p>
                    <?php echo esc_html__( 'To disable the REST API safely while keeping your plugins working, upgrade to NETSENSAI‑SHIELD PRO.', 'netsensai-shield' ); ?>
                    <a href="https://netsensai.pl/store" target="_blank" rel="noopener noreferrer" style="color: #2271b1; font-weight: bold; text-decoration: underline;">
                        <?php echo esc_html__( 'Purchase now', 'netsensai-shield' ); ?>
                    </a>.
                </p>
            </div>
            <div class="ns-popup-button-container" style="text-align: center;">
                <button id="ns-shield-wpapi-modal-close" class="ns-modal-ok-button" style="
                    background: #E6db00;
                    color: #000;
                    border: none;
                    padding: 10px 20px;
                    cursor: pointer;
                    border-radius: 3px;
                    font-size: 16px;
                ">
                    <?php echo esc_html__( 'OK', 'netsensai-shield' ); ?>
                </button>
            </div>
        </div>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Only run on the NETSENSAI‑Shield settings page.
        if (typeof nsShieldSettings !== 'object' || !nsShieldSettings.pageSlug) {
            return;
        }
        var pageRegex = new RegExp('[?&]page=' + nsShieldSettings.pageSlug + '(?:&|$)');
        if (!pageRegex.test(window.location.search)) {
            return;
        }

        // Locate the WP API JSON checkbox by its name attribute.
        var wpApiCheckbox = document.querySelector('input[name="ns_shield_wp_api_json"]');
        var modal         = document.getElementById('ns-shield-wpapi-modal');
        if (!wpApiCheckbox || !modal) {
            return;
        }
        var overlay = modal.querySelector('.ns-shield-modal-overlay');
        var closeBtn = document.getElementById('ns-shield-wpapi-modal-close');
        var storageKey = 'nsShieldWpApiModalShown';

        function showModal() {
            modal.style.display = 'block';
        }
        function hideModal() {
            modal.style.display = 'none';
        }

        // Show the modal once on initial page load if the option is already enabled.
        if (wpApiCheckbox.checked && !localStorage.getItem(storageKey)) {
            showModal();
        }

        // When the checkbox state changes, show the modal if checked and reset the storage key.
        wpApiCheckbox.addEventListener('change', function () {
            if (this.checked) {
                // Clear previous flag so modal shows again when re‑enabling.
                localStorage.removeItem(storageKey);
                showModal();
            }
        });

        // Dismiss the modal and set a flag in localStorage so it does not show again on reload.
        function dismiss() {
            localStorage.setItem(storageKey, '1');
            hideModal();
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', function (e) {
                e.preventDefault();
                dismiss();
            });
        }
        if (overlay) {
            overlay.addEventListener('click', function () {
                dismiss();
            });
        }
    });
    </script>
    <?php
}

// Hook the modal output into the admin footer so it appears after the settings form.
add_action( 'admin_footer', 'ns_shield_wpapi_modal_popup' );