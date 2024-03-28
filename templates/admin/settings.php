<?php
/**
 * Settings page template.
 *
 * @package WPcomSpecialProjects\Unused_Media_Remover
 */

?>

<div class="wrap">
	<h2><?php _e( 'DocuSign WooCommerce Orders', 'wpcomsp-woocommerce-docusign-orders' ); ?></h2>

	<form method="post" action="options.php">
		<?php
			settings_fields( wpcomsp_dwo_get_plugin_slug() . '-group' );
			do_settings_sections( wpcomsp_dwo_get_plugin_slug() . '-settings' );
			submit_button();
		?>
	</form>
</div>
