<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Sliced Invoices Additional Tax
 * Plugin URI:        https://slicedinvoices.com/extensions/additional-tax/
 * Description:       Adds additional tax fields to quotes and invoices. Requirements: The Sliced Invoices Plugin
 * Version:           1.3.4
 * Author:            Sliced Invoices
 * Author URI:        https://slicedinvoices.com/
 * Text Domain:       sliced-invoices-additional-tax
 * Domain Path:       /languages
 *
 * -------------------------------------------------------------------------------
 * Copyright © 2021 Sliced Software, LLC.  All rights reserved.
 * This software may not be resold, redistributed or otherwise conveyed to a third party.
 * -------------------------------------------------------------------------------
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Initialize
 */
define( 'SI_ADD_TAX_VERSION', '1.3.4' );
define( 'SI_ADD_TAX_DB_VERSION', '2' );
define( 'SI_ADD_TAX_FILE', __FILE__ );

include( plugin_dir_path( __FILE__ ) . '/updater/plugin-updater.php' );

register_activation_hook( __FILE__, array( 'Sliced_Additional_Tax', 'sliced_additional_tax_activate' ) );
register_deactivation_hook( __FILE__, array( 'Sliced_Additional_Tax', 'sliced_additional_tax_deactivate' ) );

function sliced_additional_tax_load_textdomain() {
    load_plugin_textdomain( 'sliced-invoices-additional-tax', false, basename( dirname( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'sliced_additional_tax_load_textdomain' );


/**
 * 2018-03-15: update from DB 1 to DB 2, for Sliced Invoices Additional Tax versions < 1.2.0
 */
function sliced_invoices_db_update_additional_tax() {
	$general = get_option('sliced_general');
	if ( isset( $general['additional_tax_db_version'] ) && $general['additional_tax_db_version'] >= SI_ADD_TAX_DB_VERSION ) {
		// okay
	} else {
		// update needed
		$payments = get_option('sliced_payments');
		$tax = get_option('sliced_tax');
		if ( ! $tax ) {
			$tax = array();
		}
		$tax['sliced_additional_tax_rate'] = isset( $payments['sliced_additional_tax_rate'] ) ? $payments['sliced_additional_tax_rate'] : '';
		$tax['sliced_additional_tax_name'] = isset( $payments['sliced_additional_tax_name'] ) ? $payments['sliced_additional_tax_name'] : '';
		$tax['sliced_additional_tax_type'] = isset( $payments['sliced_additional_tax_type'] ) ? $payments['sliced_additional_tax_type'] : 'normal';
		update_option( 'sliced_tax' , $tax );
		
		// Done
		$general['additional_tax_db_version'] = '2';
		update_option( 'sliced_general', $general );
	}
}
add_action( 'init', 'sliced_invoices_db_update_additional_tax' );


/**
 * Global functions
 *
 * @since 1.0.0
 */
function sliced_get_second_tax_percentage( $id = 0 ) {
	
	global $pagenow;
	
	if ( ! $id ) {
		$id = sliced_get_the_id();
	}
	
	if ( $pagenow == 'post-new.php' ) {
		if ( version_compare( SLICED_VERSION, '3.7.0', '<' ) ) {
			// for Sliced Invoices < 3.7.0
			$tax = get_option( 'sliced_payments' );
		} else {
			// for Sliced Invoices >= 3.7.0
			$tax = get_option( 'sliced_tax' );
		}
		$second_tax = isset( $tax['sliced_additional_tax_rate'] ) ? $tax['sliced_additional_tax_rate'] : false;
	} else {
		$second_tax = get_post_meta( $id, '_sliced_additional_tax_rate', true );
	}
	
	$second_tax = Sliced_Shared::get_raw_number( $second_tax );
	return $second_tax;
}


/**
 * Calls the class.
 */
function sliced_call_additional_tax_class() {
	new Sliced_Additional_Tax();
}
add_action( 'init', 'sliced_call_additional_tax_class' );


/** 
 * The Class.
 */
class Sliced_Additional_Tax {

	/**
     * @var  object  Instance of this class
     */
    protected static $instance;
    
    public function __construct() {
		
		if ( ! $this->validate_settings() ) {
			return;
		}
		
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ), 999 );
		add_action( 'sliced_quote_after_tax', array( $this, 'display_in_totals_output' ) );
		add_action( 'sliced_invoice_after_tax', array( $this, 'display_in_totals_output' ) );
		add_action( 'sliced_invoice_totals', array( $this, 'calculate_additional_tax' ), 10, 2 );
		add_filter( 'sliced_payments_localized_script', array( $this, 'add_to_localized_scripts' ) );
		add_filter( 'sliced_get_tax_name', array( $this, 'set_new_tax_name' ) );
		
		if ( version_compare( SLICED_VERSION, '3.7.0', '<' ) ) {
			// for Sliced Invoices < 3.7.0
			add_action( 'cmb2_init', array( $this, 'add_metaboxes_options_legacy' ) );
			add_filter( 'sliced_payment_option_fields', array( $this, 'add_options_fields_legacy' ) );
		} else {
			// for Sliced Invoices >= 3.7.0
			add_action( 'cmb2_init', array( $this, 'add_metaboxes_options' ) );
			add_filter( 'sliced_tax_option_fields', array( $this, 'add_options_fields' ) );
		}
		
		if ( version_compare( SLICED_VERSION, '3.6.0', '<' ) ) {
			// for Sliced Invoices < 3.6.0
			add_filter( 'sliced_get_line_item_totals', array( $this, 'admin_display_in_totals_output' ) );
		} else {
			// for Sliced Invoices >= 3.6.0
			add_action( 'sliced_after_line_items', array( $this, 'add_line_item_checkbox' ), 10, 2 );
			add_filter( 'sliced_admin_display_totals_tax', array( $this, 'admin_display_in_totals_output' ) );
		}
		
	}
	
	
	public static function get_instance() {
        if ( ! ( self::$instance instanceof self ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
	
	
	public function add_line_item_checkbox( $line_items_group_id, $line_items ) {
		
		$line_items->add_group_field( $line_items_group_id, array(
			'name'        => __( 'Additional Taxable', 'sliced-invoices' ),
			'id'          => 'second_taxable',
			'type'        => 'checkbox',
			'default'     => Sliced_Metaboxes::cmb2_set_checkbox_default_for_new_post( true ),
			'attributes'  => array(
				'class'       => 'item_taxable',
			),
		) );
		
	}
	
	
	/**
	 * for Sliced Invoices >= 3.7.0
	 */
	public function add_metaboxes_options() {

		global $pagenow;
		
		if ( $pagenow == 'post-new.php' || $pagenow == 'post.php' ) {
		
			$tax = get_option( 'sliced_tax' );

			$invoice = cmb2_get_metabox('_sliced_invoice_info' );
			
			$invoice->update_field_property( '_sliced_tax', 'after_row', null );
			
			$invoice->add_field( array(
				'name'          => __( 'Tax Name', 'sliced-invoices' ),
				'desc'          => '',
				'id'            => '_sliced_tax_name',
				'type'          => 'text',
				'default'       => sliced_get_tax_name(),
			) ); 
			$invoice->add_field( array(
				'name'          => __( 'Additional Tax Rate (%)', 'sliced-invoices-additional-tax' ),
				'desc'          => '',
				'id'            => '_sliced_additional_tax_rate',
				'type'          => 'text',
				'default'       => isset( $tax['sliced_additional_tax_rate'] ) ? $tax['sliced_additional_tax_rate'] : '',
				'attributes'    => array(
					'placeholder'   => '10',
					'maxlength'     => '6',
					// 'type'          => 'number',
					// 'step'          => 'any',
				),
			) );            
			$invoice->add_field( array(
				'name'          => __( 'Additional Tax Name', 'sliced-invoices-additional-tax' ),
				'desc'          => '',
				'id'            => '_sliced_additional_tax_name',
				'default'       => isset( $tax['sliced_additional_tax_name'] ) ? $tax['sliced_additional_tax_name'] : '',
				'type'          => 'text',
			) );
			$invoice->add_field( array(
				'name'       => __( 'Additional Tax Type', 'sliced-invoices-additional-tax' ),
				'desc'       => '',
				'default'    => isset( $tax['sliced_additional_tax_type'] ) ? $tax['sliced_additional_tax_type'] : 'normal',
				'type'       => 'select',
				'id'         => '_sliced_additional_tax_type',
				'options'    => array(
					'normal'    => __( 'Normal', 'sliced-invoices-additional-tax' ) . ' ' . __( '(default)', 'sliced-invoices' ),
					'compound'  => __( 'Compound', 'sliced-invoices-additional-tax' ),
				),
				'after_row'  => array( 'Sliced_Metaboxes', 'collapsible_group_after' ),
			) );

			$quote = cmb2_get_metabox('_sliced_quote_info' );
			
			$quote->update_field_property( '_sliced_tax', 'after_row', null );

			$quote->add_field( array(
				'name'          => __( 'Tax Name', 'sliced-invoices' ),
				'desc'          => '',
				'id'            => '_sliced_tax_name',
				'type'          => 'text',
				'default'       => sliced_get_tax_name(),
			) ); 
			$quote->add_field( array(
				'name'          => __( 'Additional Tax Rate (%)', 'sliced-invoices-additional-tax' ),
				'desc'          => '',
				'id'            => '_sliced_additional_tax_rate',
				'type'          => 'text',
				'default'       => isset( $tax['sliced_additional_tax_rate'] ) ? $tax['sliced_additional_tax_rate'] : '',
				'attributes'    => array(
					'placeholder'   => '10',
					'maxlength'     => '6',
					// 'type'          => 'number',
					// 'step'          => 'any',
				),
			) );            
			$quote->add_field( array(
				'name'          => __( 'Additional Tax Name', 'sliced-invoices-additional-tax' ),
				'desc'          => '',
				'id'            => '_sliced_additional_tax_name',
				'default'       => isset( $tax['sliced_additional_tax_name'] ) ? $tax['sliced_additional_tax_name'] : '',
				'type'          => 'text',
			) );
			$quote->add_field( array(
				'name'       => __( 'Additional Tax Type', 'sliced-invoices-additional-tax' ),
				'desc'       => '',
				'default'    => isset( $tax['sliced_additional_tax_type'] ) ? $tax['sliced_additional_tax_type'] : 'normal',
				'type'       => 'select',
				'id'         => '_sliced_additional_tax_type',
				'options'    => array(
					'normal'    => __( 'Normal', 'sliced-invoices-additional-tax' ) . ' ' . __( '(default)', 'sliced-invoices' ),
					'compound'  => __( 'Compound', 'sliced-invoices-additional-tax' ),
				),
				'after_row'  => array( 'Sliced_Metaboxes', 'collapsible_group_after' ),
			) );

		}
		
	}
	
	
	/**
	 * for Sliced Invoices < 3.7.0 (before addition of "tax" settings tab)
	 */
	public function add_metaboxes_options_legacy() {

		global $pagenow;
		
		if ( $pagenow == 'post-new.php' || $pagenow == 'post.php' ) {
		
			$payments = get_option( 'sliced_payments' );

			$invoice = cmb2_get_metabox('_sliced_invoice_info' );

			$invoice->add_field( array(
				'name'          => __( 'Tax Name', 'sliced-invoices' ),
				'desc'          => '',
				'id'            => '_sliced_tax_name',
				'type'          => 'text',
				'default'       => sliced_get_tax_name(),
			) ); 
			$invoice->add_field( array(
				'name'          => __( 'Additional Tax Rate (%)', 'sliced-invoices-additional-tax' ),
				'desc'          => '',
				'id'            => '_sliced_additional_tax_rate',
				'type'          => 'text',
				'default'       => isset( $payments['sliced_additional_tax_rate'] ) ? $payments['sliced_additional_tax_rate'] : '',
				'attributes'    => array(
					'placeholder'   => '10',
					'maxlength'     => '6',
					// 'type'          => 'number',
					// 'step'          => 'any',
				),
			) );            
			$invoice->add_field( array(
				'name'          => __( 'Additional Tax Name', 'sliced-invoices-additional-tax' ),
				'desc'          => '',
				'id'            => '_sliced_additional_tax_name',
				'default'       => isset( $payments['sliced_additional_tax_name'] ) ? $payments['sliced_additional_tax_name'] : '',
				'type'          => 'text',
			) );
			$invoice->add_field( array(
				'name'       => __( 'Additional Tax Type', 'sliced-invoices-additional-tax' ),
				'desc'       => '',
				'default'    => isset( $payments['sliced_additional_tax_type'] ) ? $payments['sliced_additional_tax_type'] : 'normal',
				'type'       => 'select',
				'id'         => '_sliced_additional_tax_type',
				'options'    => array(
					'normal'    => __( 'Normal', 'sliced-invoices-additional-tax' ),
					'compound'  => __( 'Compound', 'sliced-invoices-additional-tax' ),
				)
			) );

			$quote = cmb2_get_metabox('_sliced_quote_info' );

			$quote->add_field( array(
				'name'          => __( 'Tax Name', 'sliced-invoices' ),
				'desc'          => '',
				'id'            => '_sliced_tax_name',
				'type'          => 'text',
				'default'       => sliced_get_tax_name(),
			) ); 
			$quote->add_field( array(
				'name'          => __( 'Additional Tax Rate (%)', 'sliced-invoices-additional-tax' ),
				'desc'          => '',
				'id'            => '_sliced_additional_tax_rate',
				'type'          => 'text',
				'default'       => isset( $payments['sliced_additional_tax_rate'] ) ? $payments['sliced_additional_tax_rate'] : '',
				'attributes'    => array(
					'placeholder'   => '10',
					'maxlength'     => '6',
					// 'type'          => 'number',
					// 'step'          => 'any',
				),
			) );            
			$quote->add_field( array(
				'name'          => __( 'Additional Tax Name', 'sliced-invoices-additional-tax' ),
				'desc'          => '',
				'id'            => '_sliced_additional_tax_name',
				'default'       => isset( $payments['sliced_additional_tax_name'] ) ? $payments['sliced_additional_tax_name'] : '',
				'type'          => 'text',
			) );
			$quote->add_field( array(
				'name'       => __( 'Additional Tax Type', 'sliced-invoices-additional-tax' ),
				'desc'       => '',
				'default'    => isset( $payments['sliced_additional_tax_type'] ) ? $payments['sliced_additional_tax_type'] : 'normal',
				'type'       => 'select',
				'id'         => '_sliced_additional_tax_type',
				'options'    => array(
					'normal'    => __( 'Normal', 'sliced-invoices-additional-tax' ),
					'compound'  => __( 'Compound', 'sliced-invoices-additional-tax' ),
				)
			) );

		}
		
	}
	
	
	/**
	 * for Sliced Invoices >= 3.7.0
	 */
	public function add_options_fields( $options ) {
	
		$options['fields'][] = array(
			'name'       => __( 'Additional Tax Options', 'sliced-invoices-additional-tax' ),
			'desc'       => __( 'Enter global default values here.  You can override these settings on individual quotes or invoices if necessary.', 'sliced-invoices-additional-tax' ),
			'id'         => 'sliced_add_tax_settings_title',
			'type'       => 'title',
		);
		$options['fields'][] = array(
			'name'       => __( 'Additional Tax Rate (%)', 'sliced-invoices-additional-tax' ),
			'desc'       => __( 'Negative values are allowed.  Set to 0 or leave blank for no additional tax.', 'sliced-invoices-additional-tax' ),
			'id'         => 'sliced_additional_tax_rate',
			'type'          => 'text',
			'attributes' => array(
				'maxlength'   => '6',
				// 'type'        => 'number',
				// 'step'        => 'any',
			),
		);
		$options['fields'][] = array(
			'name'       => __( 'Additional Tax Name', 'sliced-invoices-additional-tax' ),
			'desc'       => '',
			'id'         => 'sliced_additional_tax_name',
			'type'       => 'text',
		);
		$options['fields'][] = array(
			'name'       => __( 'Additional Tax Type', 'sliced-invoices-additional-tax' ),
			'desc'       => '',
			'default'    => 'normal',
			'type'       => 'select',
			'id'         => 'sliced_additional_tax_type',
			'options'    => array(
				'normal'    => __( 'Normal (calculates based on sub-total)', 'sliced-invoices-additional-tax' ),
				'compound'  => __( 'Compound (calculates based on sub-total + 1st tax)', 'sliced-invoices-additional-tax' ),
			)
		);

        return $options;

    }
	
	
	/**
	 * for Sliced Invoices < 3.7.0 (before addition of "tax" settings tab)
	 */
    public function add_options_fields_legacy( $options ) {
	
		$new_fields = array();
		
		foreach ( $options['fields'] as $option ) {
		
			if ( $option['id'] === 'title_payment_methods' ) {
				// inserts all this before Payment Methods section:
				$new_fields[] = array(
					'name'       => __( 'Additional Tax Options', 'sliced-invoices-additional-tax' ),
					'desc'       => __( 'Enter global default values here.  You can override these settings on individual quotes or invoices if necessary.', 'sliced-invoices-additional-tax' ),
					'id'         => 'sliced_add_tax_settings_title',
					'type'       => 'title',
				);
				$new_fields[] = array(
					'name'       => __( 'Additional Tax Rate (%)', 'sliced-invoices-additional-tax' ),
					'desc'       => __( 'Negative values are allowed.  Set to 0 or leave blank for no additional tax.', 'sliced-invoices-additional-tax' ),
					'id'         => 'sliced_additional_tax_rate',
					'type'          => 'text',
					'attributes' => array(
						'maxlength'   => '6',
						// 'type'        => 'number',
						// 'step'        => 'any',
					),
				);
				$new_fields[] = array(
					'name'       => __( 'Additional Tax Name', 'sliced-invoices-additional-tax' ),
					'desc'       => '',
					'id'         => 'sliced_additional_tax_name',
					'type'       => 'text',
				);
				$new_fields[] = array(
					'name'       => __( 'Additional Tax Type', 'sliced-invoices-additional-tax' ),
					'desc'       => '',
					'default'    => 'normal',
					'type'       => 'select',
					'id'         => 'sliced_additional_tax_type',
					'options'    => array(
						'normal'    => __( 'Normal (calculates based on sub-total)', 'sliced-invoices-additional-tax' ),
						'compound'  => __( 'Compound (calculates based on sub-total + 1st tax)', 'sliced-invoices-additional-tax' ),
					)
				);
			}
			
			$new_fields[] = $option;
			
		}
		
		$options['fields'] = $new_fields;

        return $options;

    }
	
	
	public function add_to_localized_scripts( $array ) {

		global $pagenow;
		
		$id = sliced_get_the_id();
		
		if ( $pagenow == 'post-new.php' ) {
			if ( version_compare( SLICED_VERSION, '3.7.0', '<' ) ) {
				// for Sliced Invoices < 3.7.0
				$tax = get_option( 'sliced_payments' );
			} else {
				// for Sliced Invoices >= 3.7.0
				$tax = get_option( 'sliced_tax' );
			}
			$second_tax = isset( $tax['sliced_additional_tax_rate'] ) ? $tax['sliced_additional_tax_rate'] : false;
			$additional_tax_type = isset( $tax['sliced_additional_tax_type'] ) ? $tax['sliced_additional_tax_type'] : false;
		} else {
			$second_tax = get_post_meta( $id, '_sliced_additional_tax_rate', true );
			$additional_tax_type = get_post_meta( $id, '_sliced_additional_tax_type', true );
		}
		
		$array['additional_tax'] = Sliced_Shared::get_raw_number( $second_tax );
		$array['additional_tax_type'] = $additional_tax_type;
		return $array;
		
	}
	
	
	public function admin_display_in_totals_output( $output ) {

		global $pagenow;
		
		$id = sliced_get_the_id();
		
		if ( $pagenow == 'post-new.php' ) {
			if ( version_compare( SLICED_VERSION, '3.7.0', '<' ) ) {
				// for Sliced Invoices < 3.7.0
				$tax = get_option( 'sliced_payments' );
			} else {
				// for Sliced Invoices >= 3.7.0
				$tax = get_option( 'sliced_tax' );
			}
			$second_tax_name = isset( $tax['sliced_additional_tax_name'] ) ? $tax['sliced_additional_tax_name'] : false;
		} else {
			$second_tax_name = get_post_meta( $id, '_sliced_additional_tax_name', true );
		}
		
		if ( ! $second_tax_name ) {
			// nothing to do
			return $output;
		}
		
		if ( version_compare( SLICED_VERSION, '3.6.0', '<' ) ) {
		
			// backwards compatibility for Sliced Invoices < 3.6.0			
			$output = '<div class="alignright sliced_totals">';
			$output .= '<h3>' . sprintf( __( '%s Totals', 'sliced-invoices' ), esc_html( sliced_get_label() ) ) .'</h3>';
			$output .= '<div class="sub">' . __( 'Sub Total', 'sliced-invoices' ) . ' <span class="alignright"><span id="sliced_sub_total">0.00</span></span></div>';
			$output .= '<div class="tax">' . sliced_get_tax_name() . ' <span class="alignright"><span id="sliced_tax">0.00</span></span></div>';    
			$output .= '<div class="tax">' . $second_tax_name . ' <span class="alignright"><span id="sliced_additional_tax">0.00</span></span></div>';
			$output .= '<div class="total">' . __( 'Total', 'sliced-invoices' ) . ' <span class="alignright"><span id="sliced_total">0.00</span></span></div>
				</div>';

		} else {
		
			// for Sliced Invoices >= 3.6.0
			$output .= '<div class="tax">' . $second_tax_name . ' <span class="alignright"><span id="sliced_additional_tax">0.00</span></span></div>';
			
		}
		
		return $output;
		
	}
	
	
	/**
     * Admin notices for various things...
     *
     * @since   1.2.0
     */
	public function admin_notices() {
		
		// check just in case we're on < Sliced Invoices v3.5.0
		if ( class_exists( 'Sliced_Admin_Notices' ) ) {
		
			// placeholder for future notices
			
		}
		
	}
	
	public function admin_notices_clear( $exclude = '' ) {
	
		// check just in case we're on < Sliced Invoices v3.5.0
		if ( class_exists( 'Sliced_Admin_Notices' ) ) {
		
			$notices = array(
				//'additional_tax_example',
			);
		
			foreach ( $notices as $notice ) {
				if ( Sliced_Admin_Notices::has_notice( $notice ) && $notice !== $exclude ) {
					Sliced_Admin_Notices::remove_notice( $notice );
				}
			}
			
		}
		
	}
	
	
	public function calculate_additional_tax( $array, $id ) {
	
		$decimals = Sliced_Shared::get_decimals();
		
		$second_tax_type = get_post_meta( $id, '_sliced_additional_tax_type', true );
		if ( ! $second_tax_type ) {
			if ( version_compare( SLICED_VERSION, '3.7.0', '<' ) ) {
				// for Sliced Invoices < 3.7.0
				$tax = get_option( 'sliced_payments' );
			} else {
				// for Sliced Invoices >= 3.7.0
				$tax = get_option( 'sliced_tax' );
			}
			$second_tax_type = isset( $tax['sliced_additional_tax_type'] ) ? $tax['sliced_additional_tax_type'] : false;
		}
		
		if ( version_compare( SLICED_VERSION, '3.6.0', '<' ) ) {
		
			// backwards compatibility for Sliced Invoices < 3.6.0
			if ( $second_tax_type === 'compound' ) {
				$second_tax_amt = ( sliced_get_second_tax_percentage( $id ) / 100 ) * ( $array['sub_total'] + $array['tax'] );
			} else {
				$second_tax_amt = ( sliced_get_second_tax_percentage( $id ) / 100 ) * $array['sub_total'];
			}
			
			$array['second_tax']    = $second_tax_amt;
			$array['total']         = $array['sub_total'] + $second_tax_amt + $array['tax'];
			$array['total_due']     = $array['sub_total'] + $second_tax_amt + $array['tax'];
			
			// patch for Deposit Invoices <= 2.2.0
			if ( isset( $array['original_total'] ) ) {
				if ( $second_tax_type === 'compound' ) {
					$second_tax_amt = ( sliced_get_second_tax_percentage( $id ) / 100 ) * ( $array['original_sub_total'] + $array['original_tax'] );
				} else {
					$second_tax_amt = ( sliced_get_second_tax_percentage( $id ) / 100 ) * $array['original_sub_total'];
				}
				$array['original_total'] = $array['original_total'] + $second_tax_amt;
			}
			
		} else {
			
			$tax_calc_method = 'exclusive';
			if ( version_compare( SLICED_VERSION, '3.7.0', '>=' ) ) {
				$tax_calc_method = Sliced_Shared::get_tax_calc_method( $id );
			}
		
			// for Sliced Invoices >= 3.6.0
			$output = array(
				'sub_total_second_taxable' => 0,
				'second_tax'               => 0,
			);
			if ( $tax_calc_method === 'exclusive' ) {
				$output['_adjustments'] = array(
					array(
						'type'   => 'add',
						'source' => 'second_tax',
						'target' => 'total',
					),
				);
			}

			$items = Sliced_Shared::get_line_items( $id );

			if( ! $items || $items == null || empty( $items ) || empty( $items[0] ) ) {
				// if there are no line items, simply return here
				$array['addons']['additional_tax'] = $output;
				return $array;
			}
			
			// work out the line item totals
			foreach ( $items[0] as $value ) {

				$qty = isset( $value['qty'] ) ? Sliced_Shared::get_raw_number( $value['qty'] ) : 0;
				$amt = isset( $value['amount'] ) ? Sliced_Shared::get_raw_number( $value['amount'] ) : 0;
				$tax = isset( $value['tax'] ) ? Sliced_Shared::get_raw_number( $value['tax'] ) : 0;

				$line_total = Sliced_Shared::get_line_item_sub_total( $qty, $amt, $tax );
				
				if ( isset( $value['second_taxable'] ) && $value['second_taxable'] === 'on' ) {
					$output['sub_total_second_taxable'] = $output['sub_total_second_taxable'] + $line_total;
				}

			}
			
			// account for before-tax discounts, if any (after-tax discounts will be handled by core plugin)
			$discounts = 0;
			$discount_value         = get_post_meta( $id, '_sliced_discount', true );            // for Sliced Invoices >= 3.9.0
			$discount_type          = get_post_meta( $id, '_sliced_discount_type', true );
			$discount_tax_treatment = get_post_meta( $id, '_sliced_discount_tax_treatment', true );
			if ( ! $discount_value ) {
				$discount_value         = get_post_meta( $id, 'sliced_invoice_discount', true ); // for Sliced Invoices < 3.9.0
				$discount_type          = 'amount';
				$discount_tax_treatment = 'after';
			}
			$discount_value = Sliced_Shared::get_raw_number( $discount_value, $id );
			if ( $discount_type === 'percentage' ) {
				$discount_percentage = $discount_value / 100;
			}
			
			if ( $discount_tax_treatment === 'before' ) {
				if ( $discount_type === 'percentage' ) {
					$discounts = round( $output['sub_total_second_taxable'] * $discount_percentage, $decimals );
				} else {
					$discounts = $discount_value;
				}
				$output['sub_total_second_taxable'] = $output['sub_total_second_taxable'] - $discounts;
				if ( $output['sub_total_second_taxable'] < 0 ) {
					$output['sub_total_second_taxable'] = 0;
				}
			}
			
			// add tax, if any
			$tax_percentage = sliced_get_second_tax_percentage( $id ) / 100;
			if ( $tax_calc_method === 'inclusive' ) {
				// europe:
				if ( $second_tax_type === 'compound' ) {
					$tax_basis = $output['sub_total_second_taxable'] - $array['tax'];
				} else {
					$tax_basis = $output['sub_total_second_taxable'];
				}
				$output['second_tax'] = round( $tax_basis - ( $tax_basis / ( 1 + $tax_percentage ) ), $decimals );
			} else {
				// everybody else:
				if ( $second_tax_type === 'compound' ) {
					$tax_basis = $output['sub_total_second_taxable'] + $array['tax'];
				} else {
					$tax_basis = $output['sub_total_second_taxable'];
				}
				$output['second_tax'] = round( $tax_percentage * $tax_basis, $decimals );
			}
			
			$array['addons']['additional_tax'] = $output;
			
		}
		
		return apply_filters( 'sliced_invoices_additional_tax_calculations', $array );
	}
	
	
	public function display_in_totals_output() {

		$id = sliced_get_the_id();
		$totals = Sliced_Shared::get_totals( $id );
		$add_tax = $this->calculate_additional_tax( $totals, $id );
		$second_tax_name = get_post_meta( $id, '_sliced_additional_tax_name', true );
		
		if ( version_compare( SLICED_VERSION, '3.6.0', '<' ) ) {
			// backwards compatibility for Sliced Invoices < 3.6.0
			$second_tax_amt = $add_tax['second_tax'];
		} else {
			// for Sliced Invoices >= 3.6.0
			$second_tax_amt = $add_tax['addons']['additional_tax']['second_tax'];
		}
		
		if( $second_tax_name ) {
		?>
			
			<tr class="row-total">
				<td class="rate"><?php esc_html_e( $second_tax_name ) ?></td>
				<td class="total"><?php esc_html_e( Sliced_Shared::get_formatted_currency( $second_tax_amt ) ) ?></td>
			</tr>

		<?php
		}

	}
	
	
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since   1.0.0
	 */
	public function enqueue_scripts() {
		
		if ( method_exists( 'Sliced_Shared', 'is_sliced_invoices_page' ) && ! Sliced_Shared::is_sliced_invoices_page() ) {
			return;
		}
		
		if ( version_compare( SLICED_VERSION, '3.6.0', '<' ) ) {
			// for Sliced Invoices < 3.6.0
			wp_enqueue_script( 'sliced-additional-tax', plugin_dir_url( __FILE__ ) . 'includes/js/admin-legacy.js', array( 'sliced-invoices' ), SI_ADD_TAX_VERSION );
		} else {
			// for Sliced Invoices >= 3.6.0
			wp_enqueue_script( 'sliced-additional-tax', plugin_dir_url( __FILE__ ) . 'includes/js/admin.js', array( 'sliced-invoices', 'sliced-invoices-decimal' ), SI_ADD_TAX_VERSION );
		}
	}
	
	
	public function set_new_tax_name( $output ) {
		$id = sliced_get_the_id();
		$tax_name = get_post_meta( $id, '_sliced_tax_name', true );
		if( $tax_name ) {
			$output = $tax_name;
		}
		return $output;

	}
	
	
	
    public static function sliced_additional_tax_activate() {

        // reserved for future use

    }
	
	
	public static function sliced_additional_tax_deactivate() {
	
		wp_clear_scheduled_hook( 'sliced_invoices_additional_tax_updater' );
		$main = Sliced_Additional_Tax::get_instance();
		$main->admin_notices_clear();
		$updater = Sliced_Additional_Tax_Updater::get_instance();
		$updater->updater_notices_clear();
		
	}
	
	
	/**
     * Output requirements not met notice.
     *
     * @since   1.3.1
     */
	public function requirements_not_met_notice() {
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'Sliced Invoices Additional Tax extension cannot find the required <a href="%s">Sliced Invoices plugin</a>. Please make sure the core Sliced Invoices plugin is <a href="%s">installed and activated</a>.', 'sliced-invoices-additional-tax' ), 'https://wordpress.org/plugins/sliced-invoices/', admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}
	
	
	/**
     * Validate settings, make sure all requirements met, etc.
     *
     * @since   1.3.1
     */
	public function validate_settings() {
	
		if ( ! class_exists( 'Sliced_Invoices' ) ) {
			
			// Add a dashboard notice.
			add_action( 'all_admin_notices', array( $this, 'requirements_not_met_notice' ) );

			return false;
		}
		
		return true;
	}

}
