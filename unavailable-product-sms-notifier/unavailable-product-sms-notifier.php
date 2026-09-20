<?php
/**
 * Plugin Name: Unavailable Product SMS Notifier
 * Plugin URI:  https://github.com/alirzaghlmpr/unavalable-product-sms-sender
 * Description: Lets customers request SMS notification when an out-of-stock WooCommerce product becomes available again.
 * Version:     1.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author:      alirzaghlmpr
 * License:     GPL-2.0-or-later
 * Text Domain: upsn
 */

defined( 'ABSPATH' ) || exit;

define( 'UPSN_VERSION', '1.1.0' );
define( 'UPSN_PATH',    plugin_dir_path( __FILE__ ) );
define( 'UPSN_URL',     plugin_dir_url( __FILE__ ) );
define( 'UPSN_TABLE',   'upsn_notify_requests' );

// ── Dependency check ────────────────────────────────────────────────────────
add_action( 'admin_notices', 'upsn_woocommerce_missing_notice' );
function upsn_woocommerce_missing_notice() {
    if ( class_exists( 'WooCommerce' ) ) {
        return;
    }
    echo '<div class="notice notice-error"><p>'
        . esc_html__( 'Unavailable Product SMS Notifier requires WooCommerce to be installed and active.', 'upsn' )
        . '</p></div>';
}

function upsn_is_woocommerce_active(): bool {
    return class_exists( 'WooCommerce' );
}

/**
 * Debug logger. Writes to the PHP error log only when WP_DEBUG is enabled,
 * so production logs aren't flooded with SMS payloads (which contain PII).
 */
function upsn_log( string $message ): void {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        error_log( $message );
    }
}

// ── Activation / Deactivation ────────────────────────────────────────────────
register_activation_hook( __FILE__, function () {
    require_once UPSN_PATH . 'includes/class-database.php';
    UPSN_Database::create_table();
} );

register_deactivation_hook( __FILE__, function () {
    // intentionally leave data intact on deactivation
} );

// ── Bootstrap ────────────────────────────────────────────────────────────────
add_action( 'plugins_loaded', 'upsn_init' );
function upsn_init() {
    load_plugin_textdomain( 'upsn', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    if ( ! upsn_is_woocommerce_active() ) {
        return;
    }

    require_once UPSN_PATH . 'includes/class-database.php';
    require_once UPSN_PATH . 'includes/gateways/class-gateway-base.php';
    require_once UPSN_PATH . 'includes/class-sms-sender.php';
    require_once UPSN_PATH . 'includes/class-frontend.php';
    require_once UPSN_PATH . 'includes/class-request-handler.php';
    require_once UPSN_PATH . 'includes/class-stock-watcher.php';
    require_once UPSN_PATH . 'admin/class-admin-panel.php';
    require_once UPSN_PATH . 'admin/class-settings.php';

    UPSN_Settings::init();
    UPSN_Frontend::init();
    UPSN_Request_Handler::init();
    UPSN_Stock_Watcher::init();
    UPSN_Admin_Panel::init();
}
