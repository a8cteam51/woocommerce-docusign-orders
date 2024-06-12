<?php

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the integration with DocuSign.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
class DocuSign {
	// region FIELDS AND CONSTANTS


	// endregion

	// region METHODS

	/**
	 * Returns true if the integration is active.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  boolean
	 */
	public function is_active(): bool {
		// No requirements for this integration.
		return true;
	}

	/**
	 * Initializes the integration if it's active.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function maybe_initialize(): void {
		if ( ! $this->is_active() ) {
			return;
		}

		$this->initialize();
	}

	/**
	 * Initializes the integration.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	protected function initialize(): void {
		// Will set up hooks later.
	}

	// endregion

	// region HOOKS

	// ADD HOOK AND FILTER METHODS HERE

	// endregion
}
