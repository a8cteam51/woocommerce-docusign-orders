<?php

/**
 * Embedded DocuSign handler class.
 *
 * @package WPcomSpecialProjects\DocuSignWooCommerceOrders
 */

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders;

defined( 'ABSPATH' ) || exit;


/**
 * Implements DocuSign process for embedded signatures.
 *
 * @see https://developers.docusign.com/docs/esign-rest-api/how-to/request-signature-in-app-embedded/
 *
 * @since   1.0.0
 * @version 1.0.0
 */
class Embedded_DocuSign {
	// region FIELDS AND CONSTANTS

	// region FILTERS AND HOOKS

	// region METHODS

	/**
	 * Retrieve the integration key defined as part of the custom
	 * DocuSign application.
	 *
	 * @return string The integration key. Empty if not available.
	 */
	public static function get_integration_key() {
		return wpcomsp_dwo_get_settings_data( 'integration_key' );
	}

	/**
	 * Retrieve the secret key defined as part of the custom
	 * DocuSign application.
	 *
	 * @return string The secret key. Empty if not available.
	 */
	public static function get_secret_key() {
		return wpcomsp_dwo_get_settings_data( 'secret_key' );
	}

	/**
	 * Retrieve the authorization code stored after the initial OAuth request.
	 *
	 * @return string The authorization code. Empty if not available.
	 */
	public static function get_authorization_code() {
		$authorization_code = wpcomsp_dwo_get_settings_data( 'authorization_code' );

		return $authorization_code;
	}

	/**
	 * Retrieve the URL used to perform initial authorization for a user.
	 *
	 * @return string The authorization URL.
	 */
	public static function get_authorization_url() {
		if ( in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
			$base_url = 'https://account-d.docusign.com/oauth/auth';
		} else {
			$base_url = 'https://account.docusign.com/oauth/auth';
		}

		$api_url = add_query_arg(
			array(
				'response_type' => 'code',
				'scope'         => 'signature',
				'client_id'     => self::get_integration_key(),
				'redirect_uri'  => rawurlencode( admin_url( '/options-general.php?page=wpcomsp_woocommerce_docusign_settings' ) ),
			),
			$base_url
		);

		return $api_url;
	}

	/**
	 * Retrieve the URL used for POST requests to obtain access tokens.
	 *
	 * @return string The access token URL.
	 */
	public static function get_token_url() {
		if ( in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
			return 'https://account-d.docusign.com/oauth/token';
		} else {
			return 'https://account.docusign.com/oauth/token';
		}
	}

	/**
	 * Retrieve the URL used for GET requests to obtain user information associated
	 * with an access token.
	 *
	 * @return string The user info URL.
	 */
	public static function get_user_info_url() {
		if ( in_array( wp_get_environment_type(), array( 'local', 'development' ), true ) ) {
			return 'https://account-d.docusign.com/oauth/userinfo';
		} else {
			return 'https://account.docusign.com/oauth/userinfo';
		}
	}

	/**
	 * Generate the authorization header per the DocuSign API specification.
	 *
	 * @return string The authorization header to send with API requests.
	 */
	public static function get_authorization_header() {
		return base64_encode( self::get_integration_key() . ':' . self::get_secret_key() ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Refresh the access token.
	 *
	 * @return boolean|string The refreshed access token. False if not available.
	 */
	public static function get_refresh_token() {
		if ( '' === self::get_integration_key() || '' === self::get_secret_key() ) {
			return false;
		}

		$refresh_token = get_option( 'docusign_refresh_token', '' );

		if ( '' === $refresh_token ) {
			return false;
		}

		// Retrieve a new access token.
		$token_response = wp_remote_post(
			self::get_token_url(),
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . self::get_authorization_header(),
				),
				'body'    => array(
					'grant_type'    => 'refresh_token',
					'refresh_token' => $refresh_token,
				),
			)
		);

		if ( ! is_wp_error( $token_response ) ) {
			$token_data    = json_decode( $token_response['body'] );
			$access_token  = $token_data->access_token;
			$token_type    = $token_data->token_type;
			$refresh_token = $token_data->refresh_token;
			$token_expires = time() + (int) $token_data->expires_in;

			update_option( 'docusign_access_token', $access_token, false );
			update_option( 'docusign_renew_time', $token_expires, false );
			update_option( 'docusign_token_type', $token_type, false );
			update_option( 'docusign_refresh_token', $refresh_token, false );

			return $access_token;
		}

		return '';
	}

	/**
	 * Retrieve the access token used for API calls.
	 *
	 * @return string The access token. Empty if not available.
	 */
	public static function get_access_token() {
		$access_token = get_option( 'docusign_access_token', '' );
		$renew_time   = get_option( 'docusign_renew_time', 0 );

		// The token we have stored is still valid.
		if ( time() < ( $renew_time - 1800 ) && '' !== $access_token ) {
			return $access_token;
		} elseif ( '' !== $access_token ) {
			$access_token = self::get_refresh_token();
		}

		// A successful refresh token was found.
		if ( '' !== $access_token ) {
			return $access_token;
		}

		if ( '' === self::get_integration_key() || '' === self::get_secret_key() || '' === self::get_authorization_code() ) {
			return false;
		}

		// Retrieve a new access token.
		$token_response = wp_remote_post(
			self::get_token_url(),
			array(
				'headers' => array(
					'Authorization' => 'Basic ' . self::get_authorization_header(),
				),
				'body'    => array(
					'grant_type' => 'authorization_code',
					'code'       => self::get_authorization_code(),
				),
			)
		);

		if ( ! is_wp_error( $token_response ) ) {
			$token_data = json_decode( $token_response['body'] );

			// If an invalid grant error returns, we've lost authentication entirely
			// and someone needs to re-authorize through the admin.
			if ( isset( $token_data->error ) && 'invalid_grant' === $token_data->error ) {
				delete_option( 'docusign_authorization_code' );
				delete_option( 'docusign_user_information' );
				delete_option( 'docusign_access_token' );
				delete_option( 'docusign_renew_time' );

				return false;
			}

			$access_token  = $token_data->access_token;
			$token_type    = $token_data->token_type;
			$refresh_token = $token_data->refresh_token;
			$token_expires = time() + (int) $token_data->expires_in;

			update_option( 'docusign_access_token', $access_token, false );
			update_option( 'docusign_renew_time', $token_expires, false );
			update_option( 'docusign_token_type', $token_type, false );
			update_option( 'docusign_refresh_token', $refresh_token, false );

			return $access_token;
		}

		return '';
	}

	/**
	 * Retrieve DocuSign user information.
	 *
	 * @return boolean|array The user information. False if not available.
	 */
	public static function get_user_info() {
		$access_token = self::get_access_token();

		if ( '' === $access_token || false === $access_token ) {
			return false;
		}

		$user_information = get_option( 'docusign_user_information', array() );

		if ( is_array( $user_information ) && isset( $user_information['account_id'] ) ) {
			return $user_information;
		}

		$token_response = wp_remote_get(
			self::get_user_info_url(),
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
			)
		);

		$user_information = array();

		if ( ! is_wp_error( $token_response ) ) {
			$token_data = json_decode( $token_response['body'] );

			if ( isset( $token_data->accounts ) ) {
				$user_information['account_id'] = $token_data->accounts[0]->account_id;
				$user_information['base_url']   = $token_data->accounts[0]->base_uri;
				$user_information['name']       = $token_data->name;

				update_option( 'docusign_user_information', $user_information, false );
			}
		}

		return $user_information;
	}


	// endregion METHODS
}
