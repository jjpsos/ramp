<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

register_deactivation_hook( SLICED_INVOICES_RECURRING_FILE, 'sliced_invoices_recurring_deactivate' );

function sliced_invoices_recurring_deactivate( $network_wide ) {
	
	wp_clear_scheduled_hook( 'sliced_invoices_recurring_invoices_updater' );
	$updater = Sliced_Recurring_Invoices_Updater::get_instance();
	$updater->updater_notices_clear();
	
	do_action( 'sliced_invoices_recurring_deactivated', $network_wide );
}
