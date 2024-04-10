<?php

/**
 * Settings class.
 *
 * @package WPCOMSpecialProjects\DocuSignWooCommerceOrders
 */

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders;

defined( 'ABSPATH' ) || exit; // @phpstan-ignore-line

/**
 * Main Settings class.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
class Settings {

	private string $slug;

	private array $fields;

	/**
	 * Settings constructor.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->slug   = wpcomsp_dwo_get_plugin_slug();
		$this->fields = $this->get_settings_fields();
	}

	/**
	 * Initializes the class.
	 *
	 * @since  1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function initialize(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_fields' ) );
	}

	/**
	 * Adds a settings page.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'DocuSign WooCommerce Orders Settings', 'wpcomsp-woocommerce-docusign-orders' ),
			__( 'DocuSign for Woo', 'wpcomsp-woocommerce-docusign-orders' ),
			'manage_options',
			'wpcomsp_woocommerce_docusign_settings',
			array( $this, 'display_settings' )
		);
	}

	/**
	 * Displays the settings page.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function display_settings(): void {
		require_once WPCOMSP_DWO_PATH . 'templates/admin/settings.php';
	}

	/**
	 * Registers fields on the settings page
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function register_fields(): void {
		add_settings_section(
			$this->slug . '-group',
			__( 'Settings', 'wpcomsp-woocommerce-docusign-orders' ),
			'',
			$this->slug . '-settings'
		);

		foreach ( $this->fields as $field ) {
			add_settings_field(
				$this->slug . '-' . $field['id'],
				$field['label'],
				array(
					$this,
					'render_settings_field',
				),
				$this->slug . '-settings',
				$this->slug . '-group',
				$field
			);
		}

		register_setting( $this->slug . '-group', $this->slug . '-settings', array( 'sanitize_callback' => array( $this, 'sanitize_settings' ) ) );
	}

	/**
	 * Renders the fields
	 *
	 * @param array $args The field arguments.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function render_settings_field( $args ): void {
		$wp_data_value = get_option( $this->slug . '-settings', array() );

		Logger::log( 'Rendering settings field: ' . $args['id'] );

		if ( $wp_data_value && isset( $wp_data_value[ $args['setting'] ] ) ) {
			$wp_data_value = $wp_data_value[ $args['setting'] ];
		} else {
			$wp_data_value = array();
		}

		switch ( $args['type'] ) {
			case 'checkbox':
				echo wp_kses(
					'<input
						type="checkbox"
						id="' . esc_attr( $args['id'] ) . '"
						name="' . esc_attr( $args['name'] ) . '"
						' . checked( $args['value'], $wp_data_value, false ) . '
						value="' . esc_attr( $args['value'] ) . '">'
						. '<label for="' . esc_attr( $args['id'] ) . '">' . wp_kses_post( $args['description'] ) . '</label>',
					array(
						'input' => array(
							'type'    => array(),
							'id'      => array(),
							'name'    => array(),
							'value'   => array(),
							'checked' => array(),
						),
						'label' => array(
							'for' => array(),
						),
					)
				);
				break;
			case 'number':
				echo wp_kses(
					'<input
						type="number"
						id="' . esc_attr( $args['id'] ) . '"
						name="' . esc_attr( $args['name'] ) . '"
						value="' . esc_attr( $wp_data_value ) . '">'
						. '<p class="description">' . wp_kses_post( $args['description'] ) . '</p>',
					array(
						'input' => array(
							'type'  => array(),
							'id'    => array(),
							'name'  => array(),
							'value' => array(),
						),
						'p'     => array(
							'class' => array(),
						),
					)
				);
				break;
			case 'select':
			case 'multiselect':
				echo wp_kses(
					'<select
						id="' . esc_attr( $args['id'] ) . '"
						name="' . esc_attr( $args['name'] ) . '"' .
						( 'multiselect' === $args['type'] ? ' multiple="multiple"' : '' ) . '>',
					array(
						'select' => array(
							'id'       => array(),
							'name'     => array(),
							'multiple' => array(),
						),
					)
				);

				foreach ( $args['options'] as $key => $value ) {
					if ( 'multiselect' === $args['type'] ) {
						$selected = selected( in_array( $key, $wp_data_value, true ), true, false );
					} else {
						$selected = selected( $key, $wp_data_value, false );
					}

					echo wp_kses(
						'<option
							value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>',
						array(
							'option' => array(
								'value'    => array(),
								'selected' => array(),
							),
						)
					);
				}

				echo wp_kses(
					'</select><p class="description">' . wp_kses_post( $args['description'] ) . '</p>',
					array(
						'select' => array(),
						'p'      => array(
							'class' => array(),
						),
					)
				);
				break;
			case 'color_picker':
				echo wp_kses(
					'<input
						type="text"
						class="color-picker"
						id="' . esc_attr( $args['id'] ) . '"
						name="' . esc_attr( $args['name'] ) . '"
						value="' . sanitize_text_field( $wp_data_value[ $args['value'] ] ?? '' ) . '">',
					array(
						'input' => array(
							'type'  => array(),
							'class' => array(),
							'id'    => array(),
							'name'  => array(),
							'value' => array(),
						),
					)
				);
				break;
			case 'text':
				echo wp_kses(
					'<input
						type="text"
						id="' . esc_attr( $args['id'] ) . '"
						name="' . esc_attr( $args['name'] ) . '"
						size="' . esc_attr( $args['size'] ?? 30 ) . '"
						value="' . esc_attr( $wp_data_value ) . '">'
						. '<p class="description">' . wp_kses_post( $args['description'] ) . '</p>',
					array(
						'input' => array(
							'type'  => array(),
							'id'    => array(),
							'name'  => array(),
							'size'  => array(),
							'value' => array(),
						),
						'p'     => array(
							'class' => array(),
						),
					)
				);
				break;
			case 'readonly':
				echo wp_kses(
					'<input
						type="text"
						id="' . esc_attr( $args['id'] ) . '"
						name="' . esc_attr( $args['name'] ) . '"
						size="' . esc_attr( $args['size'] ?? 30 ) . '"
						value="' . ( $wp_data_value ? esc_attr( $wp_data_value ) : 'Not Set' ) . '"
						readonly>'
						. '<p class="description">' . wp_kses_post( $args['description'] ) . '</p>',
					array(
						'input' => array(
							'type'     => array(),
							'id'       => array(),
							'name'     => array(),
							'size'     => array(),
							'value'    => array(),
							'readonly' => array(),
						),
						'p'     => array(
							'class' => array(),
						),
					)
				);
				break;
		}
	}

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
	public function get_settings_data( string $key = '' ) {
		$settings = get_option( $this->slug . '-settings' );

		if ( ! empty( $key ) ) {
			return $settings[ $key ] ?? array();
		}

		return $settings ?? array();
	}

	/**
	 * Updates the settings data for a specific key.
	 *
	 * @param string $key   The settings key to update.
	 * @param mixed  $value The value to set.
	 *
	 * @since  1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function update_settings_data( string $key, $value ): void {
		Logger::log( 'Updating settings data for ' . $key . ' to ' . $value );
		$plugin_settings = $this->get_settings_data();

		$plugin_settings[ $key ] = $value;

		update_option( $this->slug . '-settings', $plugin_settings );
	}

	/**
	 * Returns the settings fields.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return array
	 */
	private function get_settings_fields(): array {
		$fields = array(
			array(
				'label'       => __( 'Environment', 'wpcomsp-woocommerce-docusign-orders' ),
				'type'        => 'select',
				'setting'     => 'environment',
				'id'          => $this->slug . '-settings[environment]',
				'name'        => $this->slug . '-settings[environment]',
				'options'     => $this->get_environment_options(),
				'description' => __( 'The DocuSign environment to connect to.', 'wpcomsp-woocommerce-docusign-orders' ),
			),
			array(
				'label'       => __( 'Integration Key', 'wpcomsp-woocommerce-docusign-orders' ),
				'type'        => 'text',
				'setting'     => 'integration_key',
				'id'          => $this->slug . '-settings[integration_key]',
				'name'        => $this->slug . '-settings[integration_key]',
				'size'        => 40,
				'description' => __( 'The integration key provided by DocuSign. (Integrations > Apps and Keys)', 'wpcomsp-woocommerce-docusign-orders' ),
			),
			array(
				'label'       => __( 'Secret Key', 'wpcomsp-woocommerce-docusign-orders' ),
				'type'        => 'text',
				'setting'     => 'secret_key',
				'id'          => $this->slug . '-settings[secret_key]',
				'name'        => $this->slug . '-settings[secret_key]',
				'size'        => 40,
				'description' => __( 'The secret key provided by DocuSign. (Integrations > Apps and Keys > locate your app > Actions > Edit > Add Secret Key)', 'wpcomsp-woocommerce-docusign-orders' ),
			),
			array(
				'label'       => __( 'Authorization Code', 'wpcomsp-woocommerce-docusign-orders' ),
				'type'        => 'readonly',
				'setting'     => 'authorization_code',
				'id'          => $this->slug . '-settings[authorization_code]',
				'name'        => $this->slug . '-settings[authorization_code]',
				'size'        => 40,
				'description' => __( 'The authorization code provided by DocuSign. Authorize or disconnect the site below.', 'wpcomsp-woocommerce-docusign-orders' ),
			),
			array(
				'label'       => __( 'Enable Logging', 'wpcomsp-woocommerce-docusign-orders' ),
				'type'        => 'checkbox',
				'setting'     => 'enable_logging',
				'value'       => '1',
				'id'          => $this->slug . '-settings[enable_logging]',
				'name'        => $this->slug . '-settings[enable_logging]',
				'description' => __( 'Log the actions the plugin takes', 'wpcomsp-woocommerce-docusign-orders' ),
			),
		);

		return apply_filters( 'wpcomsp_woocommerce_docusign_settings_fields', $fields );
	}

	/**
	 * Sanitizes the settings.
	 *
	 * @param array $settings The settings to sanitize.
	 *
	 * @return array
	 */
	public function sanitize_settings( array $settings ): array {
		$environment_options            = $this->get_environment_options();
		$settings['integration_key']    = sanitize_text_field( $settings['integration_key'] );
		$settings['secret_key']         = sanitize_text_field( $settings['secret_key'] );
		$settings['authorization_code'] = sanitize_text_field( $settings['authorization_code'] );
		$settings['enable_logging']     = absint( $settings['enable_logging'] );

		if ( ! array_key_exists( $settings['environment'], $environment_options ) ) {
			$settings['environment'] = 'development';
		}

		return $settings;
	}

	/**
	 * Sets the default settings for the plugin
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function set_default_settings(): void {
		$environment = in_array( wp_get_environment_type(), array( 'development', 'staging', 'local' ), true ) ? 'development' : 'production';
		$defaults    = array(
			'environment'        => $environment,
			'integration_key'    => '',
			'secret_key'         => '',
			'authorization_code' => '',
			'enable_logging'     => '0',
		);

		update_option( $this->slug . '-settings', $defaults );
	}

	/**
	 * Returns the options for the environment setting.
	 *
	 * @return array
	 */
	private function get_environment_options(): array {
		return apply_filters(
			'wpcomsp_woocommerce_docusign_environment',
			array(
				'development' => __( 'Development', 'wpcomsp-woocommerce-docusign-orders' ),
				'production'  => __( 'Production', 'wpcomsp-woocommerce-docusign-orders' ),
			)
		);
	}
}
