<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function sliced_user_items_ids( $type ) {
	$client_area = new Sliced_Client_Area;
	$output = $client_area->user_items_query( $type );
	return $output;
}

function sliced_get_client_totals( $type, $status ) {
	
	$client_area = new Sliced_Client_Area;
	$ids = $client_area->user_items_query( $type );
	
	$totals = array();
	if( $ids ) :
		foreach ($ids as $id) {
			$the_status = get_the_terms( $id, $type . '_status' );
				
			if( isset( $the_status[0]->slug ) && !empty( $the_status[0]->slug ) ) {
				if( $the_status[0]->slug == $status || ( is_array($status) && in_array($the_status[0]->slug, $status ) ) ) {
					$totals[] = sliced_get_invoice_total_raw( $id );
				}
			}
		}
	endif;
	
	$total  = Sliced_Shared::get_formatted_currency( array_sum( $totals ) );
	$count  = count( $totals );
	
	return array( 
		'totals' => $total,
		'count' => $count,
	);
	
}

function sliced_get_quote_totals( $type ) {
	$total = sliced_get_client_totals( 'quote', $type ); 
	return $total['totals'];
}

function sliced_get_invoice_totals( $type ) {
	$total = sliced_get_client_totals( 'invoice', $type ); 
	return $total['totals'];
}

function sliced_get_quote_count( $type ) {
	$total = sliced_get_client_totals( 'quote', $type ); 
	return $total['count'];
}

function sliced_get_invoice_count( $type ) {
	$total = sliced_get_client_totals( 'invoice', $type ); 
	return $total['count'];
}

function sliced_client_area_permalink() {
	$general = get_option( 'sliced_general' );
	return get_permalink( $general['client_area_id'] );
}

function sliced_request_quote_permalink() {
	$general = get_option( 'sliced_general' );
	return get_permalink( $general['request_quote_id'] );
}

function sliced_edit_profile_permalink() {
	$general = get_option( 'sliced_general' );
	return get_permalink( $general['edit_profile_id'] );
}

function sliced_get_client_label( $label_id, $default_text ) {
	// Deprecated. For compatibility with Easy Translate Extension < v2.0.0. Will be removed soon.
	$translate = get_option( 'sliced_translate' );
	$label = ( isset( $translate[$label_id] ) && class_exists( 'Sliced_Translate' ) ) ? $translate[$label_id] : __( $default_text, 'sliced-invoices-client-area');
	return apply_filters( 'sliced_get_client_label', $label );
}

function sliced_client_area_hide_quotes() {
	$quotes = get_option( 'sliced_quotes' );
	if ( isset( $quotes['client_area_hide_quotes'] ) && $quotes['client_area_hide_quotes'] == 'on' ) {
		return true;
	} else {
		return false;
	}
}

function sliced_client_area_hide_invoices() {
	$invoices = get_option( 'sliced_invoices' );
	if ( isset( $invoices['client_area_hide_invoices'] ) && $invoices['client_area_hide_invoices'] == 'on' ) {
		return true;
	} else {
		return false;
	}
}
