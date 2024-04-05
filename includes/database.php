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
