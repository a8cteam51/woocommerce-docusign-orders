<?php
/**
 * Functions for managing settings.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns saved settings data.
 *
 * @param string $key The settings key to return data for.
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 * @return mixed
 */
function wpcomsp_dwo_get_settings_data( string $key = '' ) {
	$settings = new \WPcomSpecialProjects\DocuSignWooCommerceOrders\Settings();

	return $settings->get_settings_data( $key );
}

/**
 * Updates settings data.

 * @param string $key   The settings key to return data for.
 * @param mixed  $value The value to update the settings key with.
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 * @return void
 */
function wpcomsp_dwo_update_settings_data( string $key, $value ): void {
	$settings = new \WPcomSpecialProjects\DocuSignWooCommerceOrders\Settings();

	$settings->update_settings_data( $key, $value );
}
