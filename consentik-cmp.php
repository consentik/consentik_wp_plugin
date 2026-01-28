<?php
/**
 * Plugin Name: Consentik CMP – GDPR/CCPA Cookie Consent Banner
 * Plugin URI: https://consentik.com
 * Description: A WordPress plugin to manage Consentik CMP integration with siteId and instanceId configuration.
 * Version: 1.0.1
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
define('CONSENTIK_CMP_VERSION', '1.0.0');
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


    $api = WP_CMP_API . "/sites/$instanceId/$siteId/index.json";
    $response = wp_remote_get($api, ['timeout' => 15, 'sslverify' => false]);
    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
    }

    $config = [
        'ad_storage' => 'denied',
        'analytics_storage' => 'denied',
        'ad_user_data' => 'denied',
        'ad_personalization' => 'denied',
        'security_storage' => 'denied',
        'functionality_storage' => 'denied',
        'personalization_storage' => 'denied',
        'ads_data_redaction' => false,
        'url_passthrough' => false,
    ];

    if (isset($data->integrate->googleConsentMode)) {
        $consentMode = $data->integrate->googleConsentMode;
        if ($consentMode->useDefaultTemplate) {
            echo '<script>console.log("CMP PLUGIN PAUSED CAUSE USE TEMPLATE")</script>';
            return;
        }

        $enabled = $consentMode->enabled;

        if ($enabled) {
            $config['ad_storage'] = $consentMode->ad_storage ? 'granted' : 'denied';
            $config['analytics_storage'] = $consentMode->analytics_storage ? 'granted' : 'denied';
            $config['ad_user_data'] = $consentMode->ad_user_data ? 'granted' : 'denied';
            $config['ad_personalization'] = $consentMode->ad_personalization ? 'granted' : 'denied';
            $config['security_storage'] = $consentMode->security_storage ? 'granted' : 'denied';
            $config['functionality_storage'] = $consentMode->functionality_storage ? 'granted' : 'denied';
            $config['personalization_storage'] = $consentMode->personalization_storage ? 'granted' : 'denied';
            $config['ads_data_redaction'] = $consentMode->ads_data_redaction;
            $config['url_passthrough'] = $consentMode->url_passthrough;
        }
    }

    ?>
    <script data-cfasync="false">
        console.log('WP DEFAULT WORKING..')
        window.dataLayer = window.dataLayer || [];
        const config = JSON.parse('<?php echo json_encode($config)?>');

        window.__CST_CMP_ALREADY_SET = true;

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag("consent", "default", {
            ad_storage: config.ad_storage,
            analytics_storage: config.analytics_storage,
            ad_user_data: config.ad_user_data,
            ad_personalization: config.ad_personalization,
            security_storage: config.security_storage,
            functionality_storage: config.functionality_storage,
            personalization_storage: config.personalization_storage,
            wait_for_update: 500
        });
        gtag('set', 'developer_id.dNjA1Yz', true);
        if (config.ads_data_redaction) {
            gtag('set', 'ads_data_redaction', config.ads_data_redaction);
        }
        if (config.url_passthrough) {
            gtag('set', 'url_passthrough', config.url_passthrough);
        }
    </script>
    <?php
}, -10000000);