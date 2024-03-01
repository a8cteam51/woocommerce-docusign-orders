<?php
/**
 * The DocuSign WooCommerce Orders bootstrap file.
 *
 * @since       1.0.0
 * @version     1.0.0
 * @author      WordPress.com Special Projects
 * @license     GPL-3.0-or-later
 *
 * @noinspection    ALL
 *
 * @wordpress-plugin
 * Plugin Name:             DocuSign WooCommerce Orders
 * Plugin URI:              https://wpspecialprojects.wordpress.com
 * Description:
 * Version:                 1.0.0
 * Requires at least:       5.9
 * Tested up to:            6.4
 * Requires PHP:            7.4
 * Author:                  WordPress.com Special Projects
 * Author URI:              https://wpspecialprojects.wordpress.com
 * License:                 GPL v3 or later
 * License URI:             https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:             wpcomsp-woocommerce-docusign-orders
 * Domain Path:             /languages
 * WC requires at least:    5.9
 * WC tested up to:         7.4
 **/

defined( 'ABSPATH' ) || exit;

// Define plugin constants.
function_exists( 'get_plugin_data' ) || require_once ABSPATH . 'wp-admin/includes/plugin.php';
define( 'WPCOMSP_DWO_METADATA', get_plugin_data( __FILE__, false, false ) );

define( 'WPCOMSP_DWO_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPCOMSP_DWO_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPCOMSP_DWO_URL', plugin_dir_url( __FILE__ ) );

// Load plugin translations so they are available even for the error admin notices.
add_action(
	'init',
	static function () {
		load_plugin_textdomain(
			WPCOMSP_DWO_METADATA['TextDomain'],
			false,
			dirname( WPCOMSP_DWO_BASENAME ) . WPCOMSP_DWO_METADATA['DomainPath']
		);
	}
);

// Load the autoloader.
if ( ! is_file( WPCOMSP_DWO_PATH . '/vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			$message      = __( 'It seems like <strong>DocuSign WooCommerce Orders</strong> is corrupted. Please reinstall!', 'wpcomsp-woocommerce-docusign-orders' );
			$html_message = wp_sprintf( '<div class="error notice wpcomsp-woocommerce-docusign-orders-error">%s</div>', wpautop( $message ) );
			echo wp_kses_post( $html_message );
		}
	);
	return;
}
require_once WPCOMSP_DWO_PATH . '/vendor/autoload.php';

// Initialize the plugin if system requirements check out.
$wpcomsp_dwo_requirements = validate_plugin_requirements( WPCOMSP_DWO_BASENAME );
define( 'WPCOMSP_DWO_REQUIREMENTS', $wpcomsp_dwo_requirements );

if ( $wpcomsp_dwo_requirements instanceof WP_Error ) {
	add_action(
		'admin_notices',
		static function () use ( $wpcomsp_dwo_requirements ) {
			$html_message = wp_sprintf( '<div class="error notice wpcomsp-woocommerce-docusign-orders-error">%s</div>', $wpcomsp_dwo_requirements->get_error_message() );
			echo wp_kses_post( $html_message );
		}
	);
} else {
	require_once WPCOMSP_DWO_PATH . 'functions.php';
	add_action( 'plugins_loaded', array( wpcomsp_dwo_get_plugin_instance(), 'maybe_initialize' ) );
}
