<?php
/**
 * Functions for interacting with the database.
 *
 * These will be implemented once custom tables are needed.
 */

defined( 'ABSPATH' ) || exit; // @phpstan-ignore-line

/**
 * Returns the current schema version.
 *
 * @return string
 */
function wpcomsp_dwo_get_schema_version(): string {
	return '1.0.0';
}

/**
 * Creates the custom table.
 *
 * This is called when the plugin is activated for the first time.
 *
 * @return void
 */
function wpcomsp_dwo_create_table(): void {
	// global $wpdb;

	// $sql = "CREATE TABLE `{$wpdb->prefix}wpcomsp_dwo_media` (
	//   `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
	//   `attachment_id` bigint(20) NULL,
	//   `url` varchar(2048) NULL
	// ) {$wpdb->get_charset_collate()};";

	// require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	// dbDelta( $sql );
}

/**
 * Resets the table.
 *
 * This is used before a new scan is started.
 *
 * @return void
 */
function wpcomsp_dwo_reset_table(): void {
	// global $wpdb;

	// $wpdb->query( "TRUNCATE TABLE {$wpdb->prefix}wpcomsp_dwo_media" );
}

/**
 * Adds references to the database.
 *
 * @param array $references An array that contains an array of attachment IDs and an array of URLs.
 *
 * @return void
 */
function wpcomsp_dwo_add_references_to_database( array $references ): void {
	// global $wpdb;

	// $table_name = $wpdb->prefix . 'wpcomsp_dwo_media';

	// if ( ! empty( $references['ids'] ) ) {
	//  $id_placeholder = array_fill( 0, count( $references['ids'] ), '(%d)' );

	//  $wpdb->query(
	//      $wpdb->prepare(
	//          "INSERT IGNORE INTO {$table_name} (attachment_id) VALUES " . implode( ',', $id_placeholder ), // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	//          $references['ids']
	//      )
	//  );
	// }

	// if ( ! empty( $references['urls'] ) ) {
	//  $url_placeholder = array_fill( 0, count( $references['urls'] ), '(%s)' );

	//  $wpdb->query(
	//      $wpdb->prepare(
	//          "INSERT IGNORE INTO {$table_name} (url) VALUES " . implode( ',', $url_placeholder ), // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	//          $references['urls']
	//      )
	//  );
	// }
}

/**
 * Checks if the media is being used.
 *
 * Determines this by checking if the attachment ID or URL is in the database.
 *
 * @param integer $id  The attachment ID.
 * @param string  $url The URL of the media.
 *
 * @return boolean
 */
function wpcomsp_dwo_check_is_used( int $id, string $url ): bool {
	// global $wpdb;

	// $media_id = $wpdb->get_var(
	//  $wpdb->prepare(
	//      "SELECT id FROM {$wpdb->prefix}wpcomsp_dwo_media WHERE attachment_id = %d OR url = %s",
	//      $id,
	//      wpcomsp_dwo_clean_url( $url )
	//  )
	// );

	// return (bool) $media_id;
}

/**
 * Returns an array of post types that should be excluded from the scan.
 *
 * @return array
 */
function wpcomsp_dwo_get_excluded_post_types(): array {
	return apply_filters(
		'remove_unused_media_exclude_post_types',
		array(
			'attachment',
			'auto-draft',
			'nav_menu_item',
			'oembed_cache',
			'revision',
			'shop_order',
			'shop_refund',
			'user_request',
		)
	);
}
