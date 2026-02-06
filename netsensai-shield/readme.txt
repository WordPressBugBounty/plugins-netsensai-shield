=== NETSENSAI Shield ===
Contributors: rgierlicki
Tags: security, cybersecurity, hardening, wordpress-security, protection
Requires at least: 5.6
Tested up to: 6.8
Stable tag: 1.4.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hardens and protects your site by locking down login, REST API, XML‑RPC, file editor, and applying HTTP security headers.

== Description ==
NETSENSAI Shield offers a range of security features, including:

Changing the login URL to reduce brute force attack risks.

Disabling the REST API (WP API JSON) for non-logged-in users.

Disabling XML-RPC to prevent unauthorized access.

Disabling the WordPress file editor to avoid accidental or malicious changes.

Disabling Application Passwords to block unauthorized API access.

Applying advanced HTTP security headers (e.g., HSTS, X-Frame-Options, Content-Security-Policy).

Integration with W3 Total Cache:

Permanently disable .htaccess writes by W3TC

Runtime disabling of Page Cache UI

One-time full cache flush on first admin page load

Automatic cache flush on Secure Options save

Physical cleanup and permanent disable via the W3TC API

Suppression of Site Health REST API availability notices for non-logged-in users (removes false Site Health errors while maintaining full API blocking).

In addition, the plugin provides helpful user feedback:

Email notifications when the login URL changes – sends a localized HTML email (Polish or English) with your old and new login links, change date and the plugin logo, so you remember to update your bookmarks.

Admin popup when disabling the WP API JSON – displays a friendly modal warning that disabling the REST API may break plugins like WooCommerce or contact forms. The popup includes a purchase link to upgrade to the PRO version if you need this feature without losing functionality.

Scoped styling – the custom colour for the “Save changes” button is now limited to the Secure Options page, so other admin pages keep the default WordPress look.

Promotional banner assistant – notifies administrators of summer discount codes and NETSENSAI Shield PRO features.

The free version provides both core and advanced Level 3 security functionalities. A PRO version offers extended support, additional features, and automatic protection enhancements.

== Installation ==

Download NETSENSAI Shield from the WordPress.org repository or upload the plugin files to /wp-content/plugins/netsensai-shield/.

Activate the plugin on the WordPress Admin Dashboard under Plugins.

Navigate to Settings > Secure Options and configure as needed.

== Frequently Asked Questions ==

= How do I change the WordPress login URL? =
Go to Settings > Secure Options and enter your preferred path in the Change Login URL field.

= How does disabling WP API JSON improve security? =
It reduces exposure of your site’s data via the REST API for non-logged-in users.

= Why disable XML-RPC? =
Disabling XML-RPC helps protect against brute force attacks targeting that protocol.

= What is the effect of disabling the file editor? =
It prevents code modifications via the dashboard, reducing the risk of malicious changes.

= What headers are included in Level 3 security? =
The plugin can apply:

Strict-Transport-Security (HSTS)

X-Frame-Options

X-Content-Type-Options

Content-Security-Policy (CSP)

Referrer-Policy

Permissions-Policy

= How does the W3 Total Cache integration work? =
On activation or settings save, NETSENSAI Shield clears the W3TC cache, disables the Page Cache UI to prevent conflicts, and blocks future .htaccess writes by W3TC.

= How does suppression of the Site Health REST API notice work? =
The plugin removes the default REST API availability test in Site Health for guest users, while still enforcing your REST API blocking settings.

== Screenshots ==

Secure Options Settings Page – intuitive panel for configuring security features.

Custom Login URL Setting – change your login path for improved security.

Disable XML-RPC and WP API JSON – restrict unauthorized access.

Security Headers Settings – activate HTTP headers with one click.

W3TC Integration Panel – view synchronization status with W3 Total Cache.

== Changelog ==

== Changelog ==

= 1.4.9 =
Security: Fixed potential access to the default password reset endpoint (`wp-login.php?action=lostpassword`) by enforcing redirect to the custom login URL.
Security: Improved handling of “checkemail” and other password recovery states to prevent false positives and direct access to `wp-login.php`.
Security: Enhanced server variable sanitization and escaping for full WordPress Coding Standards (WPCS) compliance.
Improvement: Refactored login guard logic for better stability and compatibility with LiteSpeed and custom rewrites.


= 1.4.8 =
Critical bugfix: prevents homepage from being replaced by login screen when custom login slug is empty or invalid. Update strongly recommended.

= 1.4.7 =

Improved custom login URL handling in all WordPress login flows:

* Fixed "Lost your password?" flow – reset form now works correctly under the custom login slug without 404 errors.
* Fixed post-reset confirmation screens (e.g., `?checkemail=confirm`) – users are redirected to the custom login slug instead of hitting 404.
* Added failsafe detection of the custom login page based on request path (works even if rewrite rules are not flushed).
* Extended URL overrides to also catch `network_site_url()` calls – ensures compatibility with Multisite and plugins using network context.
* Added no-cache headers on the custom login page to prevent CDN or browser cache issues with reset links.
* Centralized 404 handling into a helper for cleaner, lighter code.
* Optimized code structure – less repetition, more reliable edge-case handling.

These changes make the password reset and email confirmation flows fully compatible with NETSENSAI Shield’s custom login URL feature.

= 1.4.6 =

Added promotional banner assistant notifying of available discount codes and linking to the PRO version.

Improved styling of the "Save changes" button to keep it scoped only to the Secure Options page.

Minor UI adjustments and JavaScript animation for consistent dismissal effects.

Added localized email notifications: when you change the custom login URL, the plugin sends a friendly HTML email (in Polish or English) detailing the old and new login links, including the change date and logo.

Added WP API JSON warning modal: enabling the “Disable WP API JSON” option now triggers a popup explaining that the REST API is needed for WooCommerce and form plugins, and provides a link to purchase the PRO version for a safe disablement.


= 1.4.5 =

Scripts and styles now enqueue only on Settings → Secure Options (hook_suffix check).

assets/script.js is versioned via filemtime() to bust cache on each update.

Removed legacy, unconditional enqueue—eliminates console errors about missing toggle IDs.

Streamlined admin enqueue logic into a single ns_shield_admin_enqueue_assets() function.

= 1.4.4 =

Fixed custom login URL in password reset flow:

Password reset emails now include the correct custom-slug link with full query parameters.

“Set new password” form action and hidden fields (login, rp_key) now function under the custom URL without 404 errors.

Disabled WP canonical redirects on the custom login page to preserve login/key parameters.

Removed all error_log() debug hooks.

Streamlined site_url and login_form_action filters to catch every wp-login.php occurrence.

= 1.4.3 =

Fixed readme parsing by removing Markdown syntax from the License URI and ensuring a plain URL.

Updated short description to fit 150-character limit.

Removed calls to error_log() flagged by Plugin Check.

= 1.4.2 =

Minor formatting cleanup in readme; bumped version to 1.4.2.

= 1.4.1 =

Compliance updates for WordPress.org (tags, description length); bumped version to 1.4.1.

= 1.4 =

Added integration with W3 Total Cache (cache flushing, UI disable, .htaccess protection) and hides Site Health errors related to the REST API for non-logged-in users.

= 1.3 =

Restored Level 3: Advanced Security features in free version. Improved popup behavior, translations added, Plugin Check compatibility enhanced.

= 1.2 =

Level 3 features were temporarily moved to PRO; version 1.3 restores them.