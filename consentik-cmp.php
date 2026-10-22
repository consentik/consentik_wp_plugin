<?php
/**
 * Plugin Name: Consentik CMP – GDPR/CCPA Cookie Consent Banner
 * Plugin URI: https://consentik.com
 * Description: A WordPress plugin to manage Consentik CMP integration with siteId and instanceId configuration.
 * Version: 1.0.5
 * Author: Consentik
 * License: GPL v2 or later
 * Text Domain: consentik-cmp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
if (!defined('WP_CMP_API')) {
    define('WP_CMP_API', 'https://cmp.consentik.com');
}
// Define plugin constants
define('CONSENTIK_CMP_VERSION', '1.0.5');
define('CONSENTIK_CMP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CONSENTIK_CMP_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Include the main plugin class
require_once CONSENTIK_CMP_PLUGIN_PATH . 'includes/class-consentik-cmp.php';

// Initialize the plugin
function consentik_cmp_init()
{
    new Consentik_CMP();
}

add_action('plugins_loaded', 'consentik_cmp_init');

// Activation hook
register_activation_hook(__FILE__, 'consentik_cmp_activate');
function consentik_cmp_activate()
{
    // Set default options if they don't exist
    if (!get_option('consentik_site_id')) {
        add_option('consentik_site_id', '');
    }
    if (!get_option('consentik_instance_id')) {
        add_option('consentik_instance_id', '');
    }
    if (get_option('consentik_wait_for_update') === false) {
        add_option('consentik_wait_for_update', 500);
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'consentik_cmp_deactivate');
function consentik_cmp_deactivate()
{
    // Clean up if needed
}


add_action('plugins_loaded', function () {
    $plugin_path = plugin_basename(__FILE__);
    add_filter("wp_consent_api_registered_{$plugin_path}", '__return_true');
});

add_filter('wp_get_consent_type', function ($consent_type) {
    return 'optin';
}, 10, 1);


add_action('admin_head', 'consentik_custom_admin_styles');

function consentik_custom_admin_styles()
{
    echo '<style>
        .full-width-row th {
            display: none !important;
        }
        .full-width-row td {
            width: 100% !important;
            padding-left: 0 !important;
        }
    </style>';
}

add_action('wp_head', function () {
    $enableGCM = get_option('consentik_enable_gcm', '');
    $siteId = get_option('consentik_site_id', '');
    $instanceId = get_option('consentik_instance_id', '');

    if ($enableGCM !== 'on' || !$siteId || !$instanceId) return;

    $wait_for_update = (int) get_option('consentik_wait_for_update', 500);
    $gcm_url = WP_CMP_API . '/sites/' . rawurlencode($instanceId) . '/' . rawurlencode($siteId) . '/gcm.js';

    /*
     * Mirrors the official Consentik embed snippet:
     * 1. A synchronous inline all-denied consent default, so script-optimization
     *    plugins (WP Rocket, Autoptimize, NitroPack) cannot defer or reorder it
     *    behind Google tags.
     * 2. A synchronous gcm.js load. gcm.js carries the region-scoped consent
     *    default, the out-of-region granted default, saved-consent restoration,
     *    ads_data_redaction / url_passthrough, and the useDefaultTemplate
     *    handling. It also sets window.__CST_CMP_ALREADY_SET so the copy of the
     *    consent script bundled inside index.js does not fire a second time.
     *    Do NOT set __CST_CMP_ALREADY_SET here: that would suppress gcm.js /
     *    index.js entirely and break region-based defaults.
     */
    ?>
    <script data-cfasync="false">
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('set', 'developer_id.dNjA1Yz', true);
        gtag('consent', 'default', {
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
            analytics_storage: 'denied',
            functionality_storage: 'denied',
            personalization_storage: 'denied',
            security_storage: 'denied',
            wait_for_update: <?php echo $wait_for_update; ?>
        });
        window.__CST_INLINE_DEFAULT_SET = true;
    </script>
    <script data-cfasync="false" src="<?php echo esc_url($gcm_url); ?>"></script>
    <?php
}, -10000000);