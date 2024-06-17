<?php

/**
 * Status class.
 *
 * @package WPCOMSpecialProjects\DocuSignWooCommerceOrders
 */

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders;

defined( 'ABSPATH' ) || exit; // @phpstan-ignore-line

/**
 * Main Status class.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class Status {

	/**
	 * Status constructor.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
	}

	/**
	 * Initializes the class.
	 *
	 * @since  1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'admin_menu', array( $this, 'add_status_page' ) );
	}

	/**
	 * Adds a settings page.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function add_status_page(): void {
		add_options_page(
			__( 'DocuSign WooCommerce Orders Status', 'wpcomsp-woocommerce-docusign-orders' ),
			__( 'DocuSign Status', 'wpcomsp-woocommerce-docusign-orders' ),
			'manage_options',
			'wpcomsp_woocommerce_docusign_status',
			array( $this, 'display' )
		);
	}

	/**
	 * Displays the settings page.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function display(): void {
		require_once WPCOMSP_DWO_PATH . 'templates/admin/status.php';
	}

}
