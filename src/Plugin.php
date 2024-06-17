<?php

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin class.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
class Plugin {
	// region FIELDS AND CONSTANTS

	/**
	 * The integrations component.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     Integrations|null
	 */
	public ?Integrations $integrations = null;

	/**
	 * Embedded DocuSign signatures component.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     Embedded_DocuSign|null
	 */
	public ?Embedded_DocuSign $docusign = null;

	/**
	 * Settings.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @var Settings|null
	 */
	public ?Settings $settings = null;

	/**
	 * Status page.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	public ?Status $status = null;

	// endregion

	// region MAGIC METHODS

	/**
	 * Plugin constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	protected function __construct() {
		/* Empty on purpose. */
	}

	/**
	 * Prevent cloning.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	private function __clone() {
		/* Empty on purpose. */
	}

	/**
	 * Prevent unserializing.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function __wakeup() {
		/* Empty on purpose. */
	}

	// endregion

	// region METHODS

	/**
	 * Returns the singleton instance of the plugin.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  Plugin
	 */
	public static function get_instance(): self {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Returns true if all the plugin's dependencies are met.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   string|null $minimum_wc_version The minimum WooCommerce version required.
	 *
	 * @return  boolean
	 */
	public function is_active( string &$minimum_wc_version = null ): bool {
		// Check if WooCommerce is active.
		$woocommerce_exists = \class_exists( 'WooCommerce' ) && \defined( 'WC_VERSION' );
		if ( ! $woocommerce_exists ) {
			return false;
		}

		// Get the minimum WooCommerce version required from the plugin's header, if needed.
		if ( null === $minimum_wc_version ) {
			$updated_plugin_metadata = \get_plugin_data( \trailingslashit( WP_PLUGIN_DIR ) . WPCOMSP_DWO_BASENAME, false, false );
			if ( ! \array_key_exists( \WC_Plugin_Updates::VERSION_REQUIRED_HEADER, $updated_plugin_metadata ) ) {
				return false;
			}

			$minimum_wc_version = $updated_plugin_metadata[ \WC_Plugin_Updates::VERSION_REQUIRED_HEADER ];
		}

		// Check if WooCommerce version is supported.
		$woocommerce_supported = \version_compare( WC_VERSION, $minimum_wc_version, '>=' );
		if ( ! $woocommerce_supported ) {
			return false;
		}

		// Custom requirements check out, just ensure basic requirements are met.
		return true === WPCOMSP_DWO_REQUIREMENTS;
	}

	/**
	 * Initializes the plugin components.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	protected function initialize(): void {
		$this->integrations = new Integrations();
		$this->integrations->initialize();

		$this->settings = new Settings();
		$this->settings->initialize();

		$this->status = new Status();
		$this->status->initialize();

		$this->docusign = new Embedded_DocuSign();

		$this->maybe_install();
	}

	// endregion

	// region HOOKS

	/**
	 * Initializes the plugin components if WooCommerce is activated.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function maybe_initialize(): void {
		if ( ! $this->is_active( $minimum_wc_version ) ) {
			add_action(
				'admin_notices',
				static function () use ( $minimum_wc_version ) {
					if ( \is_null( $minimum_wc_version ) ) {
						$message = \wp_sprintf(
							/* translators: 1. Plugin name, 2. Plugin version. */
							__( '<strong>%1$s (v%2$s)</strong> requires WooCommerce. Please install and/or activate WooCommerce!', 'wpcomsp-woocommerce-docusign-orders' ),
							WPCOMSP_DWO_METADATA['Name'],
							WPCOMSP_DWO_METADATA['Version']
						);
					} else {
						$message = \wp_sprintf(
							/* translators: 1. Plugin name, 2. Plugin version, 3. Minimum WC version. */
							__( '<strong>%1$s (v%2$s)</strong> requires WooCommerce %3$s or newer. Please install, update, and/or activate WooCommerce!', 'wpcomsp-woocommerce-docusign-orders' ),
							WPCOMSP_DWO_METADATA['Name'],
							WPCOMSP_DWO_METADATA['Version'],
							$minimum_wc_version
						);
					}

					$html_message = \wp_sprintf( '<div class="error notice wpcomsp-woocommerce-docusign-orders-error">%s</div>', wpautop( $message ) );
					echo \wp_kses_post( $html_message );
				}
			);
			return;
		}

		$this->initialize();
	}

		/**
	 * Setups up the custom table and default options the first time the plugin is installed.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function maybe_install(): void {
		$installed_plugin_version = $this->get_installed_plugin_version();
		$installed_schema_version = $this->get_installed_schema_version();

		// Check that installed plugin version isn't 0. If it is, it's not installed.
		if ( version_compare( $installed_plugin_version, '0', '==' ) ) {
			$this->settings->set_default_settings();
		}

		// Installation is complete. Set the installed version.
		update_option( 'wpcomsp_dwo_plugin_version', WPCOMSP_DWO_METADATA['Version'] );
		update_option( 'wpcomsp_dwo_schema_version', wpcomsp_dwo_get_schema_version() );
	}

	/**
	 * Returns the installed version of the plugin.
	 *
	 * The plugin_version option gets set after the plugin is fully installed.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return string The installed version of the plugin or '0' if it hasn't been installed before.
	 */
	public function get_installed_plugin_version(): string {
		$installed_version = get_option( 'wpcomsp_dwo_plugin_version' );

		return ! empty( $installed_version ) ? $installed_version : '0';
	}

	/**
	 * Returns the installed schema version of the database.
	 *
	 * @return string
	 */
	public function get_installed_schema_version(): string {
		$installed_schema = get_option( 'wpcomsp_dwo_schema_version' );

		return ! empty( $installed_schema ) ? $installed_schema : '0';
	}

	// endregion
}
