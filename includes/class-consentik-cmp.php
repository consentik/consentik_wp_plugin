<?php
/**
 * Main Consentik CMP Plugin Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class Consentik_CMP
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_head', array($this, 'add_consentik_script'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu()
    {
        $icon = 'dashicons-admin-generic';
        $svg_path = CONSENTIK_CMP_PLUGIN_PATH . 'assets/icon.svg';
        if (file_exists($svg_path)) {
            $svg_contents = file_get_contents($svg_path);
            if ($svg_contents !== false) {
                $icon = 'data:image/svg+xml;base64,' . base64_encode($svg_contents);
            }
        }

        add_menu_page(
            'Consentik CMP',
            'Consentik CMP',
            'manage_options',
            'consentik-cmp-settings',
            array($this, 'admin_page'),
            $icon,
            65
        );
    }

    /**
     * Initialize admin settings
     */
    public function admin_init()
    {
        register_setting('consentik_cmp_settings', 'consentik_site_id', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));

        register_setting('consentik_cmp_settings', 'consentik_instance_id', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));

        add_settings_section(
            'consentik_cmp_section',
            'Consentik CMP Configuration',
            array($this, 'settings_section_callback'),
            'consentik-cmp-settings'
        );

        add_settings_field(
            'consentik_site_id',
            'Site ID',
            array($this, 'site_id_field_callback'),
            'consentik-cmp-settings',
            'consentik_cmp_section'
        );

        add_settings_field(
            'consentik_instance_id',
            'Instance ID',
            array($this, 'instance_id_field_callback'),
            'consentik-cmp-settings',
            'consentik_cmp_section'
        );

        add_action('admin_notices', function () {
            $screen = get_current_screen();
            if ( $screen->id !== 'toplevel_page_consentik-cmp-settings' ) {
                return;
            }
            if (!defined('WP_CONSENT_API_VERSION') && !class_exists('WP_Consent_API')) {
                $pluginURL = admin_url('plugin-install.php?s=wp-consent-api&tab=search&type=term');
                echo '<div class="notice notice-warning is-dismissible">
                <p>We recommend installing the free <a href="' . esc_attr($pluginURL) . '">WP Consent API plugin</a>. This ensures your website properly respects user consent preferences and helps you maintain compliance with GDPR, CCPA, and other privacy regulations across all your plugins.</p>
            </div>';
            }
        });
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback()
    {
        $siteId = get_option('consentik_site_id', '');
        echo '<p>Configure your Consentik CMP integration settings below.</p>';
        echo '<p>How do you get a Site ID and Instance ID? <a style="font-weight: bold" target="_blank" href="https://cmp.consentik.com/app/' . esc_attr($siteId) . '">Check your site’s Consentik Dashboard here</a>. </p>';
    }

    /**
     * Site ID field callback
     */
    public function site_id_field_callback()
    {
        $value = get_option('consentik_site_id', '');
        echo '<input type="text" name="consentik_site_id" value="' . esc_attr($value) . '" class="regular-text" placeholder="Enter your Site ID" />';
        echo '<p class="description">Enter your Consentik Site ID.</p>';
    }

    /**
     * Instance ID field callback
     */
    public function instance_id_field_callback()
    {
        $value = get_option('consentik_instance_id', '');
        echo '<input type="text" name="consentik_instance_id" value="' . esc_attr($value) . '" class="regular-text" placeholder="Enter your Instance ID" />';
        echo '<p class="description">Enter your Consentik Instance ID.</p>';
    }

    /**
     * Admin page content
     */
    public function admin_page()
    {
        ?>
        <div class="wrap">
            <h1>Consentik CMP Settings</h1>
            <p>This page helps you integrate Consentik CMP into your WordPress site — no coding required.</p>
            <form method="post" action="options.php">
                <?php
                settings_fields('consentik_cmp_settings');
                do_settings_sections('consentik-cmp-settings');
                echo "<p>Haven’t got a Site ID and Instance ID yet? <a href='https://cmp.consentik.com/admin/register'>Create them here!</a></p>";
                submit_button();
                ?>
            </form>

            <div class="consentik-cmp-info">
                <h2>Integration Status</h2>
                <?php
                $site_id = get_option('consentik_site_id', '');
                $instance_id = get_option('consentik_instance_id', '');

                if (!empty($site_id) && !empty($instance_id)) {
                    echo '<div class="notice notice-success"><p><strong>✓ Integration Active:</strong> Consentik script will be loaded on your website.</p></div>';
                    echo 'Consentik CMP already added';
                } else {
                    echo '<div class="notice notice-warning"><p><strong>⚠ Integration Inactive:</strong> Please configure both Site ID and Instance ID to activate the integration.</p></div>';
                    echo 'Consentik CMP is not activated';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Add Consentik script to wp_head
     */
//    public function add_consentik_script()
//    {
//        $site_id = get_option('consentik_site_id', '');
//        $instance_id = get_option('consentik_instance_id', '');
//
//        // Only add script if both IDs are configured
//        if (empty($site_id) || empty($instance_id)) {
//            return;
//        }
//
//        echo '<!-- Consentik script -->' . "\n";
//        echo '<script>!function(e,t,n,s,i,c){const a=t.getElementsByTagName(n)[0],d=t.createElement(n);d.id="cst-package",d.async=!0,d.src="https://cmp.consentik.com/sites/' . esc_js($instance_id) . '/' . esc_js($site_id) . '/index.js?v=' . esc_js(time()) . '",a.parentNode.insertBefore(d,a)}(window,document,"script");</script>' . "\n";
//        echo '<!-- End Consentik script -->' . "\n";
//    }
    public function add_consentik_script()
    {
        $site_id = get_option('consentik_site_id', '');
        $instance_id = get_option('consentik_instance_id', '');

        if (empty($site_id) || empty($instance_id)) {
            return;
        }

        $script_url = "https://cmp.consentik.com/sites/" . esc_attr($instance_id) . "/" . esc_attr($site_id) . "/index.js?v=" . time();

        wp_enqueue_script('consentik-cmp-js', $script_url, array(), CONSENTIK_CMP_VERSION, false);

        wp_add_inline_script('consentik-cmp-js', "window.wp_consent_type = 'optin';", 'before');

        wp_add_inline_script('consentik-cmp-js', "document.dispatchEvent(new CustomEvent('wp_consent_type_defined'));", 'after');

    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook)
    {
        if ($hook !== 'toplevel_page_consentik-cmp-settings') {
            return;
        }

        wp_enqueue_style(
            'consentik-cmp-admin',
            CONSENTIK_CMP_PLUGIN_URL . 'assets/admin.css',
            array(),
            CONSENTIK_CMP_VERSION
        );
    }
}
