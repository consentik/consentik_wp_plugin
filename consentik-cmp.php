<?php
/**
 * Plugin Name: Consentik CMP – GDPR/CCPA Cookie Consent Banner
 * Plugin URI: https://consentik.com
 * Description: A WordPress plugin to manage Consentik CMP integration with siteId and instanceId configuration.
 * Version: 1.0.0
 * Author: Consentik
 * License: GPL v2 or later
 * Text Domain: consentik-cmp
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
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
