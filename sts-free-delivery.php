<?php
/**
 * Plugin Name: STS Free Delivery Bar
 * Plugin URI: https://shaytechsolutions.com
 * Description: Shows a free shipping progress bar on the cart and checkout pages.
 * Version: 1.0.0
 * Author: Shay Tech Solutions
 * Author URI: https://shaytechsolutions.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sts-free-delivery
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

define( 'STS_FD_VERSION', '1.0.0' );
define( 'STS_FD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'STS_FD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once STS_FD_PLUGIN_DIR . 'includes/class-sts-free-delivery.php';

STS_Free_Delivery::get_instance();
