<?php
/**
 * Plugin Name:       ACO WooCommerce Order Export
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       A plugin to export orders from a WooCommerce store in CSV and PDF formats.
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Salmanul Haris
 * Author URI:        https://author.example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://example.com/my-plugin/
 * Text Domain:       aco-woo-order-export
 * Domain Path:       /languages
 * Requires Plugins:  WooCommerce
 */

defined('ABSPATH') || exit;

define('ACO_ORDER_EXPORT_VERSION', '1.0.0');
define('ACO_ORDER_EXPORT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACO_ORDER_EXPORT_PLUGIN_URL', plugin_dir_url(__FILE__));

if ( ! class_exists( 'ACO_WC_Order_Export' ) ) {

    class ACO_WC_Order_Export {

        public function __construct() {
            $this->init();
            add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'add_settings_link' ] );
        }

        private function init() {
            require_once ACO_ORDER_EXPORT_PLUGIN_DIR . '/includes/class-wc-order-export-api.php';
            require_once ACO_ORDER_EXPORT_PLUGIN_DIR . '/includes/class-wc-order-export-admin.php';
            new WC_Order_Export_Admin();
            new WC_Order_Export_API();
            do_action( 'wc_order_export_loaded' );
        }

        public function add_settings_link( $links ) {
            $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=aco-woo-order-export' ) ) . '">' . __( 'Settings', 'aco-woo-order-export' ) . '</a>';
            array_unshift( $links, $settings_link );
            return $links;
        }
    }

    add_action( 'plugins_loaded', function() {
        new ACO_WC_Order_Export();
    });
}
