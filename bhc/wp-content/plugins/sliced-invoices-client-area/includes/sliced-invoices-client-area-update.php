<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ==============================================================================
 * DATABASE UPDATES
 * ==============================================================================
 *
 * History:
 * 2016-09-27: DB 2, for Sliced Invoices Client Area versions < 1.1.8
 */
function sliced_invoices_db_update_client_area() {
	$general = get_option('sliced_general');
	if ( isset( $general['client_area_db_version'] ) && $general['client_area_db_version'] >= SLICED_INVOICES_CLIENT_AREA_DB_VERSION ) {
		// okay
	} else {
		// update needed
		$general['client_area_enable_authentication'] = 'on';
		
		// Done
		$general['client_area_db_version'] = '2';
		update_option( 'sliced_general', $general );
	}
}
add_action( 'init', 'sliced_invoices_db_update_client_area' );
