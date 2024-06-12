<?php

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders\Integrations;

use WPCOMSpecialProjects\DocuSignWooCommerceOrders\Settings;
use WPCOMSpecialProjects\DocuSignWooCommerceOrders\Integrations\WooCommerce;
use DocuSign\eSign\Client\ApiClient;
use DocuSign\Services\SignatureClientService;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the integration with DocuSign.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
class DocuSign {
	// region FIELDS AND CONSTANTS

	private ?Settings $settings = null;
	private ?ApiClient $api_client = null;
	private ?WooCommerce $woocommerce = null;

	private $authorization = array();

	private $oauth_base_path = '';
	private $customer_redirect_url = '';
	private $admin_redirect_url = '';

	const OAUTH_SCOPES = 'signature impersonation';

	// endregion

	// region METHODS

	/**
	 * Returns true if the integration is active.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  boolean
	 */
	public function is_active(): bool {
		// No requirements for this integration.
		return true;
	}

	/**
	 * Initializes the integration if it's active.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	public function maybe_initialize(): void {
		if ( ! $this->is_active() ) {
			return;
		}

		$this->initialize();
	}

	/**
	 * Initializes the integration.
	 *
	 * @since   1.0.0
	 * @version 1.0.0
	 *
	 * @return  void
	 */
	protected function initialize(): void {
		$this->woocommerce = new WooCommerce();
		$this->settings = new Settings();
		$this->api_client = new ApiClient();

		$this->api_client->getOAuth()->setOAuthBasePath( $this->get_oauth_base_path() );

		$authorization_option = get_option( 'wpcomsp_dwo_docusign_authorization', array() );

		$this->authorization = array(
			'is_authorized'           => $authorization_option['is_authorized'] ?? false,
			'authorization_timestamp' => $authorization_option['authorization_timestamp'] ?? 0,
			'account_id'              => $authorization_option['account_id'] ?? '',
			'access_token'            => $authorization_option['access_token'] ?? '',
			'access_token_expiration' => $authorization_option['access_token_expiration'] ?? 0,
		);

		$this->admin_redirect_url = admin_url( 'options-general.php?page=wpcomsp_woocommerce_docusign_settings' );
	}

	/**
	 * Gets the oAuth base path based on the current environment.
	 *
	 * @return string
	 */
	public function get_oauth_base_path(): string {
		if ( wpcomsp_dwo_is_development_environment() ) {
			return 'account-d.docusign.com';
		} else {
			return 'account.docusign.com';
		}
	}

	/**
	 * Gets the authorization URL.
	 *
	 * @return string
	 */
	public function get_authorization_url(): string {
		return 'https://' . $this->get_oauth_base_path() . '/oauth/auth?'
			. http_build_query( array(
				'response_type' => 'code',
				'scope'         => self::OAUTH_SCOPES,
				'client_id'     => $this->get_integration_key(),
				'redirect_uri'  => $this->admin_redirect_url,
			));
	}

	/**
	 * Checks if the DocuSign API has been authorized.

	 * @return array
	 */
	public function is_authorized(): array {
		return $this->authorization['is_authorized'];
	}

	/**
	 * Confirms the authorization of the DocuSign API.
	 *
	 * @return boolean
	 */
	public function confirm_authorization(): bool {
		// TODO: Implement this method.
		return $this->authorization['is_authorized'];
	}

	/**
	 * Returns the account ID for the authorized DocuSign account.
	 *
	 * @return string
	 */
	public function get_account_id(): string {
		return $this->authorization['account_id'];
	}

	/**
	 * Gets status of access token
	 *
	 * @return boolean True if access token is set and not expired. False otherwise.
	 */
	public function is_access_token_valid(): bool {
		return ! empty( $this->authorization['access_token'] ) && $this->authorization['access_token_expiration'] > time();
	}

	/**
	 * Gets the expiration time of the access token.
	 *
	 * @return int The expiration timestamp of the access token.
	 */
	public function get_access_token_expiration(): int {
		return $this->authorization['access_token_expiration'];
	}

	/**
	 * Gets the access token. If the token is expired, it will attempt to refresh it.
	 *
	 * @return array|bool The access token or false if it couldn't be refreshed.
	 */
	public function get_access_token() {
		if ( ! $this->is_access_token_valid() ) {
			return $this->retrieve_access_token();
		}

		return $this->authorization['access_token'];
	}

	/**
	 * Retrieves a new access token from DocuSign.
	 *
	 * @return array|bool The access token or false if it couldn't be retrieved.
	 */
	public function retrieve_access_token() {
		try {
			$response = $this->api_client->requestJWTUserToken(
				$this->get_integration_key(),
				$this->get_user_id(),
				$this->get_rsa_key(),
				self::OAUTH_SCOPES
			);
		} catch ( \Throwable $e ) {
			if ( str_contains( $e->getMessage(), 'consent_required' )) {
				return false;
			}
			$this->woocommerce->logger->error( 'Error retrieving access token: ' . $e->getMessage() );
		}
// TODO: We need to save this information to the database.
		return $response[0]['access_token'] ?? false;
	}

	/**
	 * Gets the integration key
	 *
	 * @return string
	 */
	public function get_integration_key(): string {
		return $this->settings->get_settings_data('integration_key');
	}

	/**
	 * Gets the impersonated user ID
	 *
	 * @return string
	 */
	public function get_user_id(): string {
		return $this->settings->get_settings_data('user_id');
	}

	/**
	 * Gets the private RSA key
	 *
	 * @return string
	 */
	public function get_rsa_key(): string {
		return $this->settings->get_settings_data('rsa_key');
	}


	// endregion

	// region HOOKS

	// ADD HOOK AND FILTER METHODS HERE

	// endregion
}
