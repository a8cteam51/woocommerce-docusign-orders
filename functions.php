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
	$url = Embedded_DocuSign::get_authorization_url();
	?>
	<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">Authorize</a>
	<?php
}

// endregion

//region OTHERS

require WPCOMSP_DWO_PATH . 'includes/assets.php';
require WPCOMSP_DWO_PATH . 'includes/database.php';
require WPCOMSP_DWO_PATH . 'includes/settings.php';

// endregion
