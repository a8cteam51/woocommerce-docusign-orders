<?php

defined( 'ABSPATH' ) || exit;

use WPCOMSpecialProjects\DocuSignWooCommerceOrders\Plugin;
use WPCOMSpecialProjects\DocuSignWooCommerceOrders\Logger;
use WPCOMSpecialProjects\DocuSignWooCommerceOrders\Embedded_DocuSign;

// region

/**
 * Returns the plugin's main class instance.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  Plugin
 */
function wpcomsp_dwo_get_plugin_instance(): Plugin {
	return Plugin::get_instance();
}

/**
 * Returns the plugin's slug.
 *
 * @since   1.0.0
 * @version 1.0.0
 *
 * @return  string
 */
function wpcomsp_dwo_get_plugin_slug(): string {
	return sanitize_key( WPCOMSP_DWO_METADATA['TextDomain'] );
}

/**
 * Renders the OAuth button on the settings page
 *
 * @since 1.0.0.
 * @version 1.0.0
 *
 * @return void
 */
function wpcomsp_dwo_oauth_button() {
	$auth_code = wpcomsp_dwo_get_settings_data( 'authorization_code' );

	if ( empty( $auth_code ) ) {
		$url = Embedded_DocuSign::get_authorization_url();
		?>
	<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">Authorize</a>
		<?php
	} else {
		?>
	<a class="button button-primary" href="<?php echo esc_url( admin_url( 'options-general.php?page=wpcomsp_woocommerce_docusign_settings&revoke=1' ) ); ?>">Revoke</a>
		<?php
	}
}

/**
 * Add oAuth code or remove it, depending on URL parameters.
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 * @return void
 */
function wpcomsp_dwo_maybe_update_oauth_code() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- This is a redirect from DocuSign. We can't send the nonce through.
	if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
		wpcomsp_dwo_update_settings_data( 'authorization_code', sanitize_text_field( wp_unslash( $_GET['code'] ) ) );
	} elseif ( isset( $_GET['revoke'] ) ) {
		Logger::log( 'Revoking OAuth' );
		wpcomsp_dwo_update_settings_data( 'authorization_code', '' );
	}
	// phpcs:enable WordPress.Security.NonceVerification.Recommended
}

/**
 * Returns true if the plugin is running on a development DocuSign environment.
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 * @return boolean
 */
function wpcomsp_dwo_is_development_environment(): bool {
	$environment = wpcomsp_dwo_get_settings_data( 'environment' );
	Logger::log( 'DocuSign Environment: ' . $environment );
	return empty( $environment ) || 'development' === $environment;
}

// endregion

//region OTHERS

require WPCOMSP_DWO_PATH . 'includes/assets.php';
require WPCOMSP_DWO_PATH . 'includes/database.php';
require WPCOMSP_DWO_PATH . 'includes/settings.php';

// endregion
