<?php

namespace WPCOMSpecialProjects\DocuSignWooCommerceOrders\Integrations;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the integration with WooCommerce.
 *
 * @since   1.0.0
 * @version 1.0.0
 */
class WooCommerce {
	// region FIELDS AND CONSTANTS

	const LOGGER_CONTEXT = array(
		'source' => 'woocommerce-docusign-orders',
	);

	public ?\WC_Logger $logger = null;


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
		// Minimum version is checked at the plugin level.
		return \class_exists( 'WooCommerce' ) && \defined( 'WC_VERSION' );
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
		// Initialize our logger
		$this->logger = new \WC_Logger();
		$this->logger->debug( 'WooCommerce integration initialized', self::LOGGER_CONTEXT );

		// Custom product field for PDF file link.
		add_action( 'woocommerce_product_options_pricing', array( $this, 'add_pdf_link_field_simple_product' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_pdf_link_field_simple_product' ) );
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'add_pdf_link_variation_field' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_pdf_link_variation_field' ), 10, 2 );
	}

	// endregion

	// region HOOKS

	/**
	 * Add agreement link to Simple product.
	 *
	 * @return void
	 */
	public function add_pdf_link_field_simple_product() {
		global $product_object;
		woocommerce_wp_text_input(
			array(
				'id'          => '_docusign_link',
				'label'       => __( 'Agreement PDF URL', 'wpcomsp-woocommerce-docusign-orders' ),
				'class'       => 'short',
				'description' => __( 'The URL for the source PDF of the signed agreement', 'wpcomsp-woocommerce-docusign-orders' ),
				'desc_tip'    => true,
				'value'       => $product_object->get_meta( '_docusign_link' ),
			)
		);
		wp_nonce_field( '_docusign_link_nonce', '_docusign_link_nonce' );
	}

	/**
	 * Save agreement link to Simple product.
	 *
	 * @param integer $post_id The post ID.
	 *
	 * @return void
	 */
	public function save_pdf_link_field_simple_product( $post_id ) {
		if ( wp_verify_nonce( $_POST['_docusign_link_nonce'], '_docusign_link_nonce' ) && isset( $_POST['_docusign_link'] ) ) {
			$product = wc_get_product( intval( $post_id ) );
			$product->update_meta_data( '_docusign_link', sanitize_url( $_POST['_docusign_link'], array( 'https' ) ) );
			$product->save_meta_data();
		}
	}

	/**
	 * Add agreement link to product variations
	 *
	 * @param integer $loop           The loop index.
	 * @param array   $variation_data The variation data.
	 * @param object  $variation      The variation object.
	 *
	 * @return void
	 */
	public function add_pdf_link_variation_field( $loop, $variation_data, $variation ) {
		$variation_product = wc_get_product( $variation->ID );

		woocommerce_wp_text_input(
			array(
				'id'            => '_docusign_link[' . $loop . ']',
				'label'         => __( 'Agreement PDF URL', 'wpcomsp-woocommerce-docusign-orders' ),
				'wrapper_class' => 'form-row form-row-full',
				'description'   => __( 'The URL for the source PDF of the signed agreement', 'wpcomsp-woocommerce-docusign-orders' ),
				'desc_tip'      => true,
				'value'         => $variation_product->get_meta( '_docusign_link' ),
			)
		);

		wp_nonce_field( "_docusign_link_nonce_{$loop}", "_docusign_link_nonce_{$loop}" );
	}

	/**
	 * Save agreement link to product variations
	 *
	 * @param integer $variation_id The variation ID.
	 * @param integer $i            The loop index.
	 *
	 * @return void
	 */
	public function save_pdf_link_variation_field( $variation_id, $i ) {
		if ( wp_verify_nonce( $_POST[ "_docusign_link_nonce_{$i}" ], "_docusign_link_nonce_{$i}" ) && isset( $_POST['_docusign_link'][ $i ] ) ) {
			$variation_product = wc_get_product( $variation_id );
			$variation_product->update_meta_data( '_docusign_link', sanitize_url( $_POST['_docusign_link'][ $i ], array( 'https' ) ) );
			$variation_product->save_meta_data();
		}
	}

	/**
	 * Retrieves the agreement link for a product or variation.
	 *
	 * @param integer $product_id   The product ID.
	 * @param integer $variation_id The variation ID (if applicable).
	 *
	 * @return string The agreement link.
	 */
	public function get_agreement_link( $product_id, $variation_id = 0 ) {
		$product = wc_get_product( $product_id );
		// Logger::log( 'Product: ' . print_r( $product, true ) );

		if ( $product->is_type( 'variation' ) ) {
			$product = wc_get_product( $variation_id );
		}

		$link = $product->get_meta( '_docusign_link' );

		return $link;
	}

	// endregion
}
