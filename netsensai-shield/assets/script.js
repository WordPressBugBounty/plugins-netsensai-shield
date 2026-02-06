document.addEventListener('DOMContentLoaded', function () {
    // 0) Only run on the Shield settings page
    if (typeof nsShieldSettings !== 'object' || !nsShieldSettings.pageSlug) {
        return;
    }
    var pageRegex = new RegExp('[?&]page=' + nsShieldSettings.pageSlug + '(?:&|$)');
    if (!pageRegex.test(window.location.search)) {
        return;
    }

    // 1) Hide any inline preload-modal (we handle it in modal_popup.php)
    var inlineModal = document.getElementById('ns-shield-preload-modal');
    if (inlineModal) {
        inlineModal.style.display = 'none';
    }

    // 2) Show/hide text fields based on toggles
    function handleTextFieldVisibility(toggleId, fieldId) {
        var toggle = document.getElementById(toggleId),
            field  = document.getElementById(fieldId);
        if (!toggle || !field) return;
        field.style.display = toggle.checked ? 'block' : 'none';
        toggle.addEventListener('change', function () {
            field.style.display = this.checked ? 'block' : 'none';
        });
    }
    handleTextFieldVisibility('ns_shield_login_url_enabled', 'ns_shield_login_url');
    handleTextFieldVisibility('ns_shield_default_admin',     'admin_login_field');

    // 3) Tooltips on sliders
    function handleTooltip(sliderSelector, tooltipSelector) {
        document.querySelectorAll(sliderSelector).forEach(function (slider) {
            var tooltip =
                slider.parentElement.querySelector(tooltipSelector) ||
                (slider.nextElementSibling &&
                    slider.nextElementSibling.matches(tooltipSelector) &&
                    slider.nextElementSibling) ||
                (slider.parentElement.parentElement &&
                    slider.parentElement.parentElement.querySelector(tooltipSelector));
            if (!tooltip) return;
            ['mouseenter','focus'].forEach(function (evt) {
                slider.addEventListener(evt, function () { tooltip.style.display = 'block'; });
            });
            ['mouseleave','blur'].forEach(function (evt) {
                slider.addEventListener(evt, function () { tooltip.style.display = 'none'; });
            });
        });
    }
    handleTooltip('.slider', '.tooltip');

    // 4) CSP header light/hard toggles
    var lightToggle = document.getElementById('ns_shield_csp_header_light'),
        hardToggle  = document.getElementById('ns_shield_csp_header_hard');
    if (lightToggle && hardToggle) {
        function updateHardToggle() {
            hardToggle.checked  = false;
            hardToggle.disabled = lightToggle.checked;
        }
        updateHardToggle();
        lightToggle.addEventListener('change', updateHardToggle);
        var form = lightToggle.closest('form');
        if (form) {
            form.addEventListener('submit', function () {
                if (lightToggle.checked) {
                    hardToggle.checked = false;
                }
                hardToggle.disabled = false;
            });
        }
    }

    // 5) Remember popup shown state on form submit
    var preloadCb = document.getElementById('ns-shield-preload-checkbox');
    if (preloadCb) {
        var preloadForm = preloadCb.closest('form');
        if (preloadForm) {
            preloadForm.addEventListener('submit', function () {
                var key = preloadCb.checked
                    ? 'nsShieldModalShownEnable'
                    : 'nsShieldModalShownDisable';
                localStorage.setItem(key, '1');
            });
        }
    }
});
