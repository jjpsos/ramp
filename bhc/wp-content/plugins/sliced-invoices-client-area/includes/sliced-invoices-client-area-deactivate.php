<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

register_deactivation_hook( SLICED_INVOICES_CLIENT_AREA_FILE, 'sliced_client_area_deactivate' );

/**
 * Plugin deactivation actions.
 *
 * @version 1.7.0
 * @since   1.0.0
 */
function sliced_client_area_deactivate( $network_wide ) {
	
	wp_clear_scheduled_hook( 'sliced_invoices_client_area_updater' );
	$updater = Sliced_Client_Area_Updater::get_instance();
	$updater->updater_notices_clear();
	
	do_action( 'sliced_invoices_client_area_deactivated', $network_wide );
	
}
