<?php

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders;

use WPCOMSpecialProjects\DocuSignWooCommerceOrders\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Handles debug logger for the plugin.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
final class Logger {
	// region FIELDS AND CONSTANTS

	/**
	 * The logger instance.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @var     Logger|null
	 */
	private static ?Logger $instance = null;

	// endregion

	// region METHODS

	/**
	 * Initializes the logger.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public static function initialize(): void {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
	}

	/**
	 * Logs a message.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @param   string  $message  The message to log.
	 *
	 * @return  void
	 */
	public static function log( string $message ): void {
		if ( wpcomsp_dwo_get_settings_data( 'enable_logging' ) ) {
			error_log( 'Log from ' . WPCOMSP_DWO_METADATA['Name'] . ":\n{$message}" );
		}
	}

	// endregion

	// region SPECIAL METHODS

	/**
	 * Logger constructor.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 */
	private function __construct() {
	}

	// endregion
}
