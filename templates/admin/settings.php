<?php
/**
 * Settings page template.
 *
 * @package WPcomSpecialProjects\Unused_Media_Remover
 */

use WPCOMSpecialProjects\DocuSignWooCommerceOrders\Logger;

/**
 * If we've been redirected from OAuth, capture the returned code.
 */

wpcomsp_dwo_maybe_update_oauth_code();

if ( isset( $_GET['tacotest'] ) ) {
	Logger::log( 'Taco Test Start' );
	$test = WPCOMSpecialProjects\DocuSignWooCommerceOrders\Embedded_DocuSign::initiate_signature( get_current_user_id(), 1291 );

	Logger::log( 'Taco Test result:' );
	Logger::log( print_r( $test, true ) );
	Logger::log( 'Taco Test End' );
}

?>

<div class="wrap">
	<h2><?php esc_html_e( 'DocuSign WooCommerce Orders', 'wpcomsp-woocommerce-docusign-orders' ); ?></h2>

	<form method="post" action="options.php">
		<?php
			settings_fields( wpcomsp_dwo_get_plugin_slug() . '-group' );
			do_settings_sections( wpcomsp_dwo_get_plugin_slug() . '-settings' );
			submit_button();
		?>
	</form>
	<?php wpcomsp_dwo_oauth_button(); ?>
	<a class="button button-primary" href="/wp-admin/options-general.php?page=wpcomsp_woocommerce_docusign_settings&tacotest">Test</a>
</div>
