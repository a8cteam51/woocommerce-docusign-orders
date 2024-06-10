<?php

/**
 * Embedded DocuSign handler class.
 *
 * @package WPcomSpecialProjects\DocuSignWooCommerceOrders
 */

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders;

use DocuSign\eSign\Configuration;
use DocuSign\eSign\Client\ApiClient;
use DocuSign\eSign\Client\ApiException;
use DocuSign\eSign\Model\Document;
use DocuSign\eSign\Envelope;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Model\Recipients;
use DocuSign\eSign\Model\Signer;
use DocuSign\eSign\Model\SignHere;
use DocuSign\eSign\Model\Tabs;

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
		if ( wpcomsp_dwo_is_development_environment() ) {
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

		Logger::log( 'Authorization URL: ' . $api_url);

		return $api_url;
	}

	/**
	 * Retrieve the URL used for POST requests to obtain access tokens.
	 *
	 * @return string The access token URL.
	 */
	public static function get_token_url() {
		if ( wpcomsp_dwo_is_development_environment() ) {
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
		if ( wpcomsp_dwo_is_development_environment() ) {
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

		$token_url = self::get_token_url();
		$token_args = 			array(
			'headers' => array(
				'Authorization' => 'Basic ' . self::get_authorization_header(),
			),
			'body'    => array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh_token,
			),
		);

		Logger::log( "Refreshing access token. POST Request:\nURL: {$token_url}\nArgs:\n" . print_r($token_args, true) );
		// Retrieve a new access token.
		$token_response = wp_remote_post(
			$token_url,
			$token_args
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
		Logger::log( 'Getting access token' );
		$access_token = get_option( 'docusign_access_token', '' );
		$renew_time   = get_option( 'docusign_renew_time', 0 );

		// The token we have stored is still valid.
		if ( time() < ( $renew_time - 1800 ) && '' !== $access_token ) {
			return $access_token;
		} elseif ( '' !== $access_token ) {
			Logger::log( 'Refreshing token' );
			$access_token = self::get_refresh_token();
		}

		// A successful refresh token was found.
		if ( '' !== $access_token ) {
			return $access_token;
		}

		Logger::log( 'Could not refresh token' );
		if ( '' === self::get_integration_key() || '' === self::get_secret_key() || '' === self::get_authorization_code() ) {
			Logger::log( 'Missing a key, cannot retrieve access token' );
			return false;
		}

		// Retrieve a new access token.
		$token_url = self::get_token_url();
		$token_args = 			array(
			'headers' => array(
				'Authorization' => 'Basic ' . self::get_authorization_header(),
			),
			'body'    => array(
				'grant_type' => 'authorization_code',
				'code'       => self::get_authorization_code(),
			),
		)
		;
		Logger::log( "Retrieving new access token\nURL: {$token_url}\nArgs: " . print_r($token_args, true) );
		$token_response = wp_remote_post(
			$token_url,
			$token_args
		);

		Logger::log( 'Token response: ' . print_r( $token_response, true ) );

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
		Logger::log( ' In get_user_info' );
		$access_token = self::get_access_token();
		Logger::log( 'Access token: ' . $access_token );
		if ( '' === $access_token || false === $access_token ) {
			return false;
		}

		$user_information = get_option( 'docusign_user_information', array() );

		if ( is_array( $user_information ) && isset( $user_information['account_id'] ) ) {
			return $user_information;
		}

		$user_info_url = self::get_user_info_url();
		$user_info_args =
		array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
		));

		Logger::log( "GET Request:\nURL: {$user_info_url}\nArgs:\n" . print_r($user_info_args, true) );

		$token_response = wp_remote_get(
			$user_info_url,
			$user_info_args
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

		Logger::log( 'User information: ' . print_r( $user_information, true ) );
		return $user_information;
	}

	/**
	 * Creates a new envelope definition for the embedded signing process.
	 *
	 * @param array  $args     The arguments for the envelope definition.
	 * @param string $pdf_link URL of the PDF file to be signed.
	 *
	 * @return EnvelopeDefinition | WP_Error The envelope definition object. WP_Error if an error occurred.
	 */
	public static function define_envelope( array $args, string $pdf_link, string $document_name ) {
		# document 1 (pdf) has tag /sn1/
		#
		# The envelope has one recipient.
		# recipient 1 - signer
		#
		# Read the file
		// Logger::log( 'GET Request: Reading file from ' . $pdf_link );
		// $response = wp_remote_get( $pdf_link );
		// if ( is_wp_error( $response ) ) {
		// 	return $response;
		// }
		// Logger::log( 'File read successfully' );
		// $content_bytes       = $response['body'];
		// $base64_file_content = base64_encode( $content_bytes );

		# Create the document model
		// $document = array(
		// 		'documentBase64' => $base64_file_content,
		// 		'name'            => $document_name, # can be different from actual file name
		// 		'fileExtension'  => 'pdf', # many different document types are accepted
		// 		'documentId'     => 1, # a label used to reference the doc
		// );

		# Create the signer recipient model
		$signer =
			array( # The signer
				'email'          => $args['signer_email'],
				'fullName'           => $args['signer_name'],
				'recipientId'   => '1',
				'routingOrder'  => '1',
				# Setting the clientUserId marks the signer as embedded
				'clientUserId' => $args['signer_client_id'],
		);

		# Create a sign_here tab (field on the document)
		$sign_here =
			array( # DocuSign SignHere field/tab
				'anchorString'   => '/sn1/',
				'anchorUnits'    => 'pixels',
				'anchorXOffset' => '20',
				'anchorYOffset' => '10',
		);


		$envelope_definition = 			array(
			'emailSubject' => 'Please sign this document sent from the PHP SDK',
			// 'documents'     => array( $document ),
			'documentsCombinedUri' => $pdf_link,
			# The Recipients object wants arrays for each recipient type
			'recipients'    => array( 'signers' => array( $signer ) ),
			'status'        => 'sent', # requests that the envelope be created and sent.
		);

		// Logger::log( 'Envelope definition: ' . print_r( $envelope_definition, true ) );

		return $envelope_definition;
	}

	/**
	 * Initiates the signature process for a user on a specific product.
	 *
	 * TODO: This will need to be connected to a specific order and line item. The product will determine which PDF they sign.
	 * @param integer $user_id    The user ID initiating the signature.
	 * @param integer $product_id The product ID to be signed.
	 *
	 * @return void
	 */
	public static function initiate_signature( int $user_id, int $product_id ) {
		Logger::log( 'Initiating signature for user ' . $user_id . ' on product ' . $product_id );
		$product       = wc_get_product( $product_id );
		$document_name = $product->get_name();
		Logger::log( 'Document name: ' . $document_name );
		$authentication_method = 'wplogin';

		$pdf_link = Plugin::get_instance()->integrations->woocommerce->get_agreement_link( $product_id );

		$user = get_user_by( 'ID', $user_id );

		$signer_email     = $user->user_email;
		$signer_name      = $user->display_name;
		$signer_client_id = strval( $user_id );

		Logger::log( 'Defining envelope' );

		// Define the envelope
		$envelope_definition = self::define_envelope(
			array(
				'signer_email'     => $signer_email,
				'signer_name'      => $signer_name,
				'signer_client_id' => $signer_client_id,
			),
			$pdf_link,
			$document_name
		);
		if ( is_wp_error( $envelope_definition ) ) {
			Logger::log( 'Error creating envelope definition: ' . $envelope_definition->get_error_message() );
			return $envelope_definition;
		}
		// Logger::log( 'Envelope definition: ' . print_r( $envelope_definition, true ) );
		Logger::log( ' Getting user info' );
		// Instantiate the API client.
		$site_user_info = self::get_user_info();
		// $access_token =  self::get_access_token();
		// $base_path =  $site_user_info['base_url'];
		// $ds_client_id =  self::get_integration_key();
		// $ds_client_secret =  self::get_secret_key();

		$request_url = $site_user_info['base_url'] . '/restapi/v2.1/accounts/' . $site_user_info['account_id'] . '/envelopes';
		$args = 			array(
			'headers' => array(
				'Authorization' => 'Bearer ' . self::get_access_token(),
			),
			'body'    => $envelope_definition,
		);

		Logger::log( "POST Request:\n URL: {$request_url}\nPost data:\n" . print_r($args, true) );
		$envelope_response = wp_remote_post( $request_url, $args );

		Logger::log( 'Envelope response: ' . print_r( $envelope_response, true ) );


		// Logger::log( 'Configuration data: ' . print_r( $config_data, true ) );

		// $config         = new Configuration( $config_data );

		// if ( wpcomsp_dwo_get_settings_data( 'enable_logging' ) ) {
		// 	$config->setDebug(true);
		// }

		// $api_client     = new ApiClient( $config );

		// // Initialize Envelopes API.
		// $envelope_api = new EnvelopesApi( $api_client );

		// Logger::log('Confirming the API Config: ' . print_r($envelope_api->apiClient->getConfig(), true));

		// // Using the DocuSign integration key and the prepared envelope definition, generate and retrieve an envelope ID using the Envelopes:create endpoint.
		// try {
		// 	Logger::log( 'Creating envelope' );
		// 	Logger::log( 'Account ID: ' . $site_user_info['account_id'] );
		// 	Logger::log( 'Envelope definition: ' . print_r( $envelope_definition, true ) );
		// 	$envelope_summary = $envelope_api->createEnvelope( $site_user_info['account_id'], $envelope_definition );
		// } catch ( ApiException $e ) {
		// 	Logger::log( 'Exception creating envelope: ' . $e->getMessage() );
		// 	return $e;
		// }
		// Logger::log( 'Envelope summary: ' . print_r( $envelope_summary, true ) );

		// $recipient_view_request = array(
		// 	'authenticationMethod' => $authentication_method,
		// 	'clientUserId' => wp_get_current_user()->user_email,
		// );

		// $view_url = $api_client->getRecipientView( $site_user_info['account_id'], $envelope_summary->getEnvelopeId(), $recipient_view_request );
		// Logger::log( 'View URL: ' . print_r( $view_url, true ) );
		// var_dump( $view_url );
	}
	// endregion METHODS
}
