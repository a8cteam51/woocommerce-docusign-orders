<?php

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders;

use WPCOMSpecialProjects\DocuSignWooCommerceOrders\Integrations\WooCommerce;
use WPCOMSpecialProjects\DocuSignWooCommerceOrders\Integrations\DocuSign;

defined( 'ABSPATH' ) || exit;

/**
 * Logical node for all integration functionalities.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Integrations {
	// region FIELDS AND CONSTANTS

	/**
	 * The WooCommerce Subscriptions integration instance.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     WooCommerce|null
	 */
	public ?WooCommerce $woocommerce = null;

	/**
	 * The DocuSign integration instance.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     DocuSign|null
	 */
	public ?DocuSign $docusign = null;

	// endregion

	// region METHODS

	/**
	 * Initializes the integrations.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function initialize(): void {
		$this->woocommerce = new WooCommerce();
		$this->woocommerce->maybe_initialize();

		$this->docusign = new DocuSign();
		$this->docusign->maybe_initialize();
	}

	// endregion
}
