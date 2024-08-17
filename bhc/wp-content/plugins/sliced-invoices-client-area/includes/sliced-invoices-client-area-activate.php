<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

register_activation_hook( SLICED_INVOICES_CLIENT_AREA_FILE, 'sliced_client_area_activate' );

/**
 * Plugin activation actions.
 *
 * @version 1.7.0
 * @since   1.0.0
 */
function sliced_client_area_activate( $network_wide ) {
	
	$general = get_option( 'sliced_general' );
	
	$page_definitions = array(
		'client-area' => array(
			'title' => __( 'Client Area', 'sliced-invoices-client-area' ),
			'content' => '[sliced-client-area]'
		),
		// 'client-register' => array(
		//     'title' => __( 'Register', 'sliced-invoices-client-area' ),
		//     'content' => '[sliced-client-register]'
		// ),
		'lost-password' => array(
			'title' => __( 'Forgot Your Password?', 'sliced-invoices-client-area' ),
			'content' => '[sliced-lost-password]'
		),
		'password-reset' => array(
			'title' => __( 'Pick a New Password', 'sliced-invoices-client-area' ),
			'content' => '[sliced-password-reset]'
		),
		'edit-profile' => array(
			'title' => __( 'Edit Profile', 'sliced-invoices-client-area' ),
			'content' => '[sliced-edit-profile]'
		)
	);
	
	foreach ( $page_definitions as $slug => $page ) {
		
		// Check that the page doesn't exist already
		$query = new WP_Query( 'pagename=' . $slug . '&post_status=publish' );
		
		if ( $query->have_posts() != true ) {
			// Add the page using the data from the array above
			$id = wp_insert_post(
				array(
					'post_content'   => $page['content'],
					'post_name'      => $slug,
					'post_title'     => $page['title'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'ping_status'    => 'closed',
					'comment_status' => 'closed',
				)
			);
			
			if( $slug == 'client-area' ) {
				$general['client_area_id'] = $id;
			}
			if( $slug == 'edit-profile' ) {
				$general['edit_profile_id'] = $id;
			}
		}
	}
	
	$general['invoice_links'] = array( 'client_area', 'edit_profile', 'logout' );
	$general['client_area_links'] = array( 'client_area', 'edit_profile', 'logout' );
	$general['client_area_enable_authentication'] = 'on';
	$general['block_admin'] = 'on';
	update_option( 'sliced_general', $general );
	
	do_action( 'sliced_invoices_client_area_activated', $network_wide );
	
}
