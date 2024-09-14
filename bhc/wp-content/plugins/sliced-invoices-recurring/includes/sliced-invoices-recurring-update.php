<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/* ==============================================================================
 * DATABASE UPDATES
 * ==============================================================================
 *
 * History:
 * 2019-11-27: update from DB 1 to DB 2, for Sliced Invoices Recurring versions < 2.4.6
 */
function sliced_invoices_recurring_db_update() {
	
	global $post;
	
	$general = get_option('sliced_general');
	if ( isset( $general['recurring_db_version'] ) && $general['recurring_db_version'] >= SLICED_INVOICES_RECURRING_DB_VERSION ) {
		// okay
	} else {
		// update needed
		
		if (
			! defined( SLICED_VERSION )
			|| ! version_compare( SLICED_VERSION, '3.8.7', '>=' ) 
		) {
			// we can't do the following until we have Sliced Invoices 3.8.7 or newer in place.
			return;
		}
		
		// setup for post_date in local timezone
		$timezone_setting = get_option( 'timezone_string' );
		if ( ! $timezone_setting > '' ) {
			$timezone_setting = get_option( 'gmt_offset' );
			if ( floatval( $timezone_setting > 0 ) ) {
				$timezone_setting = '+' . $timezone_setting;
			}
		}
		if( ! $timezone_setting ) { // if set to "UTC+0" in WordPress it returns "0", but DateTimeZone doesn't recognize this
			$timezone_setting = 'UTC';
		}
		try {
			$timezone = new DateTimeZone( $timezone_setting );
		} catch (Exception $e) {
			// worst case scenario
			$timezone = new DateTimeZone( 'UTC' );			
		}
		
		// invoices:
		$args = array(
			'post_type' => 'sliced_invoice',
			'posts_per_page' => -1,
			'post_status' => 'future',
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) { 
			while ( $query->have_posts() ) {
				$query->the_post();
				$created = intval( get_post_meta( $post->ID, '_sliced_invoice_created', true ) );
				if ( $created > 0 ) {
					$post_date = new DateTime();
					$post_date->setTimestamp( $created );
					$post_date->setTimezone( $timezone );
					$args = array(
						'ID'              => $post->ID,
						'post_date'       => $post_date->format( 'Y-m-d H:i:s' ),				  // local timezone timestamp
						'post_date_gmt'   => gmdate( 'Y-m-d H:i:s', $post_date->getTimestamp() ), // UTC timestamp
					);
					wp_update_post( $args );
					check_and_publish_future_post( $post->ID );
				}
			}
		}
		wp_reset_postdata();
		
		// Done
		$general['recurring_db_version'] = SLICED_INVOICES_RECURRING_DB_VERSION;
		update_option( 'sliced_general', $general );
	}
	
}
add_action( 'init', 'sliced_invoices_recurring_db_update' );
