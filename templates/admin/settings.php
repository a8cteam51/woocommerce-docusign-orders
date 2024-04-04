<?php
/**
 * Settings page template.
 *
 * @package WPcomSpecialProjects\Unused_Media_Remover
 */

/**
 * If we've been redirected from OAuth, capture the returned code.
 */

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- This is a redirect from DocuSign. We can't send the nonce through.
if ( isset( $_GET['code'] ) ) {
	wpcomsp_dwo_update_settings_data( 'authorization_code', sanitize_text_field( wp_unslash( $_GET['code'] ) ) );
}
// phpcs:enable WordPress.Security.NonceVerification.Recommended

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
</div>
