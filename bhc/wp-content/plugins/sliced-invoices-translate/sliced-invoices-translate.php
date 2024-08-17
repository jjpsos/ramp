<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Sliced Invoices Translate
 * Plugin URI:        https://slicedinvoices.com/extensions/easy-translate/
 * Description:       Translate text on invoices and quotes without touching any code. Requirements: The Sliced Invoices Plugin
 * Version:           1.3.2
 * Author:            Sliced Invoices
 * Author URI:        https://slicedinvoices.com/
 * Text Domain:       sliced-invoices-translate
 * Domain Path:       /languages
 *
 * -------------------------------------------------------------------------------
 * Copyright 2015-2019 Sliced Apps, Inc.  All rights reserved.
 * -------------------------------------------------------------------------------
 */

// Exit if accessed directly
if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Initialize
 */
define( 'SI_TRANSLATE_VERSION', '1.3.2' );
define( 'SI_TRANSLATE_FILE', __FILE__ );

include( plugin_dir_path( __FILE__ ) . '/updater/plugin-updater.php' ); 

register_activation_hook( __FILE__, array( 'Sliced_Translate', 'sliced_translate_activate' ) ); 
register_deactivation_hook( __FILE__, array( 'Sliced_Translate', 'sliced_translate_deactivate' ) );


/**
 * DATABASE UPDATES
 */
function sliced_translate_db_update() {
	
	$sliced_db_check = get_option('sliced_general');
	if ( ! isset( $sliced_db_check['sliced_translate_version'] ) ) {
		$sliced_db_check['sliced_translate_version'] = '0';
	}
	
	if ( version_compare( $sliced_db_check['sliced_translate_version'], SI_TRANSLATE_VERSION, '>=' ) ) {
		// all good
		return;
	}
	
	// 2017-10-11: update for Easy Translate versions < 1.3.1
	if ( version_compare( $sliced_db_check['sliced_translate_version'], '1.3.1', '<' ) ) {
		$translate = get_option( 'sliced_translate' );
		if ( isset( $translate['page'] ) && ! isset( $translate['page_label'] ) ) {
			$translate['page_label'] = $translate['page'];
			unset( $translate['page'] );
		}
		update_option('sliced_translate', $translate);
	}
	
	// Done
	$sliced_db_check['sliced_translate_version'] = SI_TRANSLATE_VERSION;
	update_option( 'sliced_general', $sliced_db_check );
	
}
add_action( 'sliced_loaded', 'sliced_translate_db_update' );


/**
 * Load plugin textdomain.
 *
 * @since 1.0.0
 */
function sliced_translate_load_textdomain() {
    load_plugin_textdomain( 'sliced-invoices-translate', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'sliced_translate_load_textdomain' );


/**
 * Calls the class.
 */
function sliced_call_translate_class() {
    new Sliced_Translate();
}
add_action( 'init', 'sliced_call_translate_class' );    


/** 
 * The Class.
 */
class Sliced_Translate {

    /**
     * @var  object  Instance of this class
     */
    protected static $instance;
	
	public $strings = array();
	public $strings_aliases = array();
	public $strings_extra_info = array();
	

    public function __construct() {
		
		if ( ! $this->validate_settings() ) {
			return;
		}
    
		add_action( 'admin_init', array( $this, 'update_the_status_names' ) );
		add_filter( 'plugin_action_links_sliced-invoices-translate/sliced-invoices-translate.php', array( $this, 'plugin_action_links' ) );
        add_filter( 'sliced_translate_option_fields', array( $this, 'sliced_add_translate_options' ), 1 );
        add_filter( 'gettext', array( $this, 'sliced_translate_some_text' ), 10, 3 );
        add_filter( 'gettext_with_context', array( $this, 'sliced_translate_some_context_text' ), 10 ,4 );
		add_filter ('sanitize_user', array( $this, 'sliced_sanitize_user' ), 10, 3);
		
		$this->load_strings();
		$this->load_strings_aliases();
		$this->load_strings_extra_info();

    }


    public static function get_instance() {
        if ( ! ( self::$instance instanceof self ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
	
	
	/**
	 * Load strings in one place
	 *
	 * Note: even though doing it this way simplifies the code a lot, Poedit cannot detect these strings like this when
	 * we generate the .pot file.  We might need to add them manually to the .pot file, or come up with some other
	 * solution for that.  -DG 2017-07-11
	 *
	 * @since 1.2.0
	 */
	public function load_strings() {
	
		$this->strings['sliced-invoices'] = array(	// domain
			'to' => 'To:',							// key (matches $translate keys) => value (matches $text)
			'from' => 'From:',
			'order_number' => 'Order Number',
			'hrs_qty' => 'Hrs/Qty',					// now included in Sliced Invoices v3.6.0
			'service' => 'Service',					// now included in Sliced Invoices v3.6.0
			'rate_price' => 'Rate/Price',			// now included in Sliced Invoices v3.6.0
			'adjust' => 'Adjust',					// now included in Sliced Invoices v3.6.0
			'sub_total' => 'Sub Total',				// now included in Sliced Invoices v3.6.0
			'discount' => 'Discount',				// now included in Sliced Invoices v3.6.1
			'total' => 'Total',						// now included in Sliced Invoices v3.6.0
			'total_due' => 'Total Due',				// now included in Sliced Invoices v3.6.0
			'page_label' => 'Page',
			'due_date' => 'Due Date',
			'valid_until' => 'Valid Until',
			'invoice_number' => 'Invoice Number',
			'invoice_date' => 'Invoice Date',
			'quote_amount' => 'Quote Amount',
			'quote_number' => 'Quote Number',
			'quote_date' => 'Quote Date',
			'accept_quote' => 'Accept Quote',
			'decline_quote' => 'Decline Quote',
			'decline_quote_reason' => 'Reason for declining',
			'quote_declined' => 'You have declined this quote.',
			'quote_cancelled' => 'This quote has been cancelled.',
			'quote_accepted' => 'You have accepted this quote.',
			'quote_expired' => 'This quote has expired.',
			'pay_this_invoice' => 'Pay This Invoice',
			'pay_amount_payable' => 'Amount Payable',
			'pay_payment_method' => 'Payment Method',
			'pay_now' => 'Pay Now',
			'add_comment' => 'Add a comment',
			'gateway-paypal-label' => 'Pay with PayPal',
			'paypal_subscription_activated' => 'Subscription has been activated!',
			'paypal_payment_received' => 'Payment has been received!',
			'paypal_transaction_id_is' => 'Your PayPal Transaction ID is: ',
			'paypal_click_here_to_return' => 'Click here to return to %s',
			'accepted' => 'Accepted',
			'declined' => 'Declined',
			'sent' => 'Sent',
			'cancelled' => 'Cancelled',
			'expired' => 'Expired',
			'draft' => 'Draft',
			'paid' => 'Paid',
			'unpaid' => 'Unpaid',
			'overdue' => 'Overdue',
		);
		$this->strings['sliced-invoices-client-area'] = array(
			'client-login-user' => 'Email or Username',
			'client-login-password' => 'Password',
			'client-login-remember' => 'Remember Me',
			'client-login-submit' => 'Sign In',
			'client-login-forgot-password' => 'Forgot your password?',
			'client-login-password-reset' => 'Check your email for a link to reset your password.',
			'client-login-password-changed' => 'Your password has been changed. You can sign in now.',
			'client-logout-message' => 'You have signed out. Would you like to sign in again?',
		);
		$this->strings['sliced-invoices-secure'] = array(
			'secure-access-denied-label' => 'Access Denied',
			'secure-access-denied-text' => 'This item has been secured and is only viewable by following the link that sent to you via email.',
		);
	
	}
	
	
	/**
	 * Load strings aliases in one place
	 *
	 * @since 1.2.4
	 */
	public function load_strings_aliases() {
		
		$this->strings_aliases['sliced-invoices'] = array(
			'Accept %s' => 'accept_quote',
			'Decline %s' => 'decline_quote',
			'You have declined this %s.' => 'quote_declined',
			'This %s has been cancelled.' => 'quote_cancelled',
			'You have accepted this %s.' => 'quote_accepted',
			'This %s has expired.' => 'quote_expired',
			'Page ' => 'page_label',
		);
		
	}
	
	
	/**
	 * Load strings extra info in one place
	 *
	 * @since 1.2.3
	 */
	public function load_strings_extra_info() {
	
		$this->strings_extra_info['sliced-invoices'] = array(
			'paypal_subscription_activated' => array(
				'desc' => __( 'Shown on payment confirmation page for PayPal gateway', 'sliced-invoices-translate' ),
			),
			'paypal_payment_received' => array(
				'desc' => __( 'Shown on payment confirmation page for PayPal gateway', 'sliced-invoices-translate' ),
			),
			'paypal_transaction_id_is' => array(
				'desc' => __( 'Shown on payment confirmation page for PayPal gateway', 'sliced-invoices-translate' ),
			),
			'paypal_click_here_to_return' => array(
				'desc' => __( 'Shown on payment confirmation page for PayPal gateway.<br />%s is a placeholder for the word "Invoice".', 'sliced-invoices-translate' ),
			),
		);
		
	}


    /**
     * Update status names (deprecated, will be removed someday)
     */
    public function update_the_status_names() {
        
        if ( ! $_POST )
            return;
        
        // only update on save of translate page
        if( 
			( isset( $_POST['object_id'] ) && $_POST['object_id'] === 'sliced_translate' ) ||
			( isset( $_REQUEST['object_id'] ) && $_REQUEST['object_id'] === 'sliced_translate' )
		) {

            $translate = null;
            $translate['draft'] = isset( $_POST['draft'] ) ? sanitize_text_field( $_POST['draft'] ) : 'Draft';
            $translate['sent'] = isset( $_POST['sent'] ) ? sanitize_text_field( $_POST['sent'] ) : 'Sent';
			$translate['accepted'] = isset( $_POST['accepted'] ) ? sanitize_text_field( $_POST['accepted'] ) : 'Accepted';
            $translate['declined'] = isset( $_POST['declined'] ) ? sanitize_text_field( $_POST['declined'] ) : 'Declined';
            $translate['cancelled'] = isset( $_POST['cancelled'] ) ? sanitize_text_field( $_POST['cancelled'] ) : 'Cancelled';
            $translate['paid'] = isset( $_POST['paid'] ) ? sanitize_text_field( $_POST['paid'] ) : 'Paid';
            $translate['unpaid'] = isset( $_POST['unpaid'] ) ? sanitize_text_field( $_POST['unpaid'] ) : 'Unpaid';
            $translate['overdue'] = isset( $_POST['overdue'] ) ? sanitize_text_field( $_POST['overdue'] ) : 'Overdue';

            $quote_status = array(
                'quote_status' => array(
                    'draft',
                    'sent',
                    'declined',
                    'cancelled',
                )
            );

            foreach ($quote_status as $taxonomy => $terms) {
                foreach ($terms as $term) {
                    $term_data = get_term_by('slug', sanitize_title($term), $taxonomy);
                    if( isset( $term_data->term_id ) ) {
                        $result = wp_update_term((int)$term_data->term_id, $taxonomy, array(
                            //'name' => $translate[sanitize_title($term)],
							// restore terms to standard... we'll translate them at display time instead.
							// someday we can remove these blocks completely.
							'name' => $term,
                        ));
                    }

                }
            }

            $invoice_status = array(
                'invoice_status' => array(
                    'draft',
                    'paid',
                    'unpaid',
                    'overdue',
                    'cancelled',
                )
            );

            foreach ($invoice_status as $taxonomy => $terms) {
                foreach ($terms as $term) {
                    $term_data = get_term_by('slug', sanitize_title($term), $taxonomy);
                    if( isset( $term_data->term_id ) ) {
                        $result = wp_update_term((int)$term_data->term_id, $taxonomy, array(
							//'name' => $translate[sanitize_title($term)],
							// restore terms to standard... we'll translate them at display time instead.
							// someday we can remove these blocks completely.
							'name' => $term,
                        ));
                    }
                }
            }

        }
    }


    /**
     * Add links to plugin page
     *
     * @since   2.0.0
     */
    public function plugin_action_links( $links ) {
       $links[] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=sliced_invoices_settings&tab=translate' ) ) .'">' . __( 'Settings', 'sliced-invoices' ) . '</a>';
       return $links;
    }


    /**
     * Add default options to database.
     *
     * @since 1.0.0
     */
    public static function sliced_translate_activate() {
	
		$ST = new Sliced_Translate();

        $translate = get_option( 'sliced_translate' );
		
		foreach ( $ST->strings as $strings_domain => $strings ) {
				
			if ( $strings_domain === 'sliced-invoices' ) {
			
				foreach ( $strings as $key => $value ) {
				
					$translate[$key] = isset( $translate[$key] ) ? $translate[$key] : $value;
				
				}
				
			}
			
		}

        update_option('sliced_translate', $translate);
		
		$sliced_db_check = get_option('sliced_general');
		$sliced_db_check['sliced_translate_version'] = SI_TRANSLATE_VERSION;
		update_option( 'sliced_general', $sliced_db_check );

    }
	
	
	public static function sliced_translate_deactivate() {
	
		wp_clear_scheduled_hook( 'sliced_invoices_easy_translate_updater' );
		$updater = Sliced_Easy_Translate_Updater::get_instance();
		$updater->updater_notices_clear();
		
	}


    /**
     * Add the options fields.
     *
     * @since 1.0.0
     */
    public function sliced_add_translate_options( $options ) {

        $prefix = 'sliced_';
		
		$translate = get_option( 'sliced_translate' );

		foreach ( $this->strings as $strings_domain => $strings ) {
			
			if ( $strings_domain === 'sliced-invoices' ) {
			
				foreach ( $strings as $key => $value ) {
				
					$options['fields'][] = array(
						'name'      => __( $value, 'sliced-invoices-translate' ),
						'desc'      => isset( $this->strings_extra_info[$strings_domain][$key]['desc'] ) ? $this->strings_extra_info[$strings_domain][$key]['desc'] : '',
						'default'   => '',
						'type'      => 'text',
						'id'        => $key,
						'attributes' => array(
							'class'      => 'i18n-multilingual regular-text',
						),
					);
				
				}
				
			}
			
		}
		
		return $options;

    }



    /**
     * Do the translations.
     *
     * @since 1.0.0
     */
    public function sliced_translate_some_text( $translated_text, $text, $domain ) {

		$static = !(isset($this) && get_class($this) == __CLASS__);
		if ( $static ) { 
			$ST = new Sliced_Translate();
			$data = $ST->strings;
			$aliases = $ST->strings_aliases;
		} else {
			$data = $this->strings;
			$aliases = $this->strings_aliases;
		}
	
		$translate = get_option( 'sliced_translate' );
		
		foreach ( $data as $strings_domain => $strings ) {
			
			if ( $strings_domain === $domain ) {
				
				$key = array_search( $text, $strings, true );
				
				if ( $key && isset( $translate[$key] ) ) {
				
					return $translate[$key];
				
				}
				
				// else check aliases:
				if ( isset( $aliases[$domain][$text] ) && isset( $translate[ $aliases[$domain][$text] ] ) ) {
					
					return $translate[ $aliases[$domain][$text] ];
					
				}
				
			}
			
		}
		
        return $translated_text;

    }

    /**
     * Do the translations with context.
     *
     * @since 1.0.0
     */
    public function sliced_translate_some_context_text( $translated_text, $text, $context, $domain ) {
        
		if ( 'sliced-invoices' == $domain ) :

			$translate = get_option( 'sliced_translate' );

			if ( '%s Number' == $text && 'quote number' == $context ) {
				$translated_text = $translate['quote_number'];
			}
			if ( '%s Number' == $text && 'invoice number' == $context ) {
				$translated_text = $translate['invoice_number'];
			}
			if ( '%s Date' == $text && 'quote date' == $context ) {
				$translated_text = $translate['quote_date'];
			}
			if ( '%s Date' == $text && 'invoice date' == $context ) {
				$translated_text = $translate['invoice_date'];
			}


		endif;

        return $translated_text;

    }
	
	/**
     * Filter for WordPress sanitize_user function to allow non-latin characters in username.
     *
     * @since 1.1.3
     */
	public function sliced_sanitize_user ($username, $raw_username, $strict) {
		//Strip HTML Tags
		$username = wp_strip_all_tags ($raw_username);

		//Remove Accents
		$username = remove_accents ($username);

		//Kill octets
		$username = preg_replace ('|%([a-fA-F0-9][a-fA-F0-9])|', '', $username);

		//Kill entities
		$username = preg_replace ('/&.+?;/', '', $username);

		//Remove Whitespaces
		$username = trim ($username);

		// Consolidate contiguous Whitespaces
		$username = preg_replace ('|\s+|', ' ', $username);

		//Done
		return $username;
	}
	
	
	/**
     * Output requirements not met notice.
     *
     * @since   1.3.2
     */
	public function requirements_not_met_notice() {
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'Sliced Invoices Translate extension cannot find the required <a href="%s">Sliced Invoices plugin</a>. Please make sure the core Sliced Invoices plugin is <a href="%s">installed and activated</a>.', 'sliced-invoices-translate' ), 'https://wordpress.org/plugins/sliced-invoices/', admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}
	
	
	/**
     * Validate settings, make sure all requirements met, etc.
     *
     * @since   1.3.2
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