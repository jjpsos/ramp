<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Sliced_Recurring
 *
 * @package Sliced_Recurring
 */
class Sliced_Recurring {
	
	/** @var object Instance of this class */
	protected static $instance = null;
	
	/** @var array  Meta Keys used by this plugin */
	public $meta_key = array(
		'number'        => '_sliced_recurring_number', 
		'next'          => '_sliced_recurring_next', 
		'frequency'     => '_sliced_recurring_frequency', 
		'last_number'   => '_sliced_recurring_last_number', 
		'last_id'       => '_sliced_recurring_last_id',
		'auto_send'     => '_sliced_recurring_auto_send',
		'stopped'       => '_sliced_recurring_stopped', 
	);
	
	
	/**
	 * Gets the instance of this class, or constructs one if it doesn't exist.
	 */
	public static function get_instance() {
		
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	
	/**
	 * Construct the class.
	 *
	 * Populates our current settings, validates settings, and hooks into all the
	 * appropriate filters/actions we will need.
	 */
	public function __construct() {
		
		load_plugin_textdomain(
			'sliced-invoices-recurring',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
		
		if ( ! $this->validate_settings() ) {
			return;
		}
		
		Sliced_Invoices_Recurring_Admin::get_instance();
		
		add_action( 'future_to_publish', array( $this, 'recurring_auto_send' ), 10, 1 );
		add_action( 'sliced_recurring_invoice', array( $this, 'create_recurring_invoice' ), 10, 3 );
		
	}
	
	
	/**
	 * Get the post meta.
	 *
	 * @version 2.5.0
	 * @since   2.0.0
	 */
	public function get_meta( $id = 0, $key = '', $single = true ) {
		if ( ! $id ) {
			$id = Sliced_Shared::get_item_id();
		}
		$meta = get_post_meta( $id, $key, $single );
		return $meta;
	}
	
	/**
	 * sql_date_now.
	 *
	 * @since 1.0.0
	 */
	public function sql_date_now() {
		return date( 'Y-m-d H:i:s' );
	}
	
	/**
	 * timestamp_now.
	 *
	 * @since 1.0.0
	 */
	public function timestamp_now() {
		return strtotime( $this->sql_date_now() );
	}
	
	/**
	 * sql_time_plus_one_hour.
	 *
	 * @since 1.0.0
	 */
	public function sql_time_plus_one_hour() {
		return date( 'H:i:s', strtotime( $this->sql_date_now() . '+ 1 hour' ) );
	}
	
	/**
	 * Check if this is a recurring invoice.
	 *
	 * @since 1.0.0
	 */
	public function is_recurring_invoice( $id ) {
		$recurring  = $this->get_meta( $id, $this->meta_key['number'] );
		$stopped    = $this->get_meta( $id, $this->meta_key['stopped'] );
		if ( ! empty( $recurring ) && empty( $stopped ) ) { // this is a recurring invoice
			return $recurring;
		} else { // not recurring
			return false;
		}
	}
	
	/**
	 * Check if this is a subscription invoice.
	 *
	 * @since 2.2.0
	 */
	public function is_subscription_invoice( $id ) {
		$subscription_status = get_post_meta( $id, '_sliced_subscription_status', true );
		if ( ! empty( $subscription_status ) ) { // this is a subscription invoice
			return $subscription_status;
		} else { // not subscription
			return false;
		}
	}
	
	/**
	 * Deletes a cron event.
	 *
	 * @param string $name The hookname of the event to delete.
	 */
	public function delete_the_cron() {
		
		// get or post depending on edit or stop
		if ( isset( $_GET['parent_id'] ) ) {
			$parent_id  = intval( $_GET['parent_id'] ); 
		} elseif ( isset( $_POST['sliced_recurring_invoice_id'] ) ) {
			$parent_id  = intval( $_POST['sliced_recurring_invoice_id'] );
		} else {
			return;
		}
		
		$last_id    = $this->get_meta( $parent_id, $this->meta_key['last_id'] );
		$recur_num  = $this->get_meta( $last_id, $this->meta_key['number'] );
		
		$args = array( (int)$last_id, (int)$recur_num, (int)$parent_id );
		$timestamp = wp_next_scheduled( 'sliced_recurring_invoice', $args );
		
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'sliced_recurring_invoice', $args );
		}
		
	}
	
	/**
	 * Format the timestamp.
	 *
	 * @since 1.0.0
	 */
	public function next_occurance( $timestamp, $frequency) {
		$next_time = strtotime( date( 'Y-m-d H:i:s', $timestamp  ) . ' + ' . $frequency );
		return $next_time;
	}
	
	/**
	 * Create the next recurring invoice in the series
	 *
	 * @version 2.4.9
	 * @since   1.0.0
	 */
	public function create_recurring_invoice( $id, $number, $parent_id ) {
		
		global $wpdb;
		
		// get the last invoice post object so we can copy the data
		$last_invoice = get_post( $id );
		
		// if the last invoice or the parent invoice were deleted, clean up
		$parent = get_post( $parent_id );
		if ( ! $last_invoice || ! $parent || $parent->post_status === "trash" ) {
			$this->clear_obsolete_crons( $parent_id );
			exit;
		}
		
		// calculate the next occurance
		$frequency  = $this->get_meta( $last_invoice->ID, $this->meta_key['frequency'] );
		$next       = $this->get_meta( $last_invoice->ID, $this->meta_key['next'] );
		$next_time  = $this->next_occurance( (int)$next, $frequency );
		
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
		
		$post_date = new DateTime();
		$post_date->setTimestamp( $next );
		$post_date->setTimezone( $timezone );
		
		// Arguments for the new invoice
		$args = array(
			'post_title'    => $last_invoice->post_title,
			'post_content'  => $last_invoice->post_content,
			'post_author'   => $last_invoice->post_author,
			'post_status'   => 'future',
			'post_type'     => 'sliced_invoice',
			'post_parent'   => $parent_id,
			'post_password' => $last_invoice->post_password,
			'post_date'     => $post_date->format( 'Y-m-d H:i:s' ),                 // local timezone timestamp
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $post_date->getTimestamp() ), // UTC timestamp
		);
		
		// Insert the new recurring invoice into the database
		$new_invoice_id = wp_insert_post( $args );
		do_action( 'publish_sliced_invoice', $new_invoice_id, get_post( $new_invoice_id ) );
		
		/*
		 * get all current post terms ad set them to the new post draft
		 */
		wp_set_object_terms($new_invoice_id, 'draft', 'invoice_status', false);
		
		// duplicate post metas
		$post_metas = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id=%d",
				$parent_id
			)
		);
		if ( $post_metas && count( $post_metas ) ) {
			$sql_query = "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES ";
			$sql_values = array();
			foreach ( $post_metas as $post_meta ) {
				$meta_key = esc_sql( $post_meta->meta_key );
				$meta_value = esc_sql( $post_meta->meta_value );
				if ( ! in_array( $meta_key, array( '_sliced_log', '_sliced_number', '_sliced_payment', '_sliced_invoice_email_sent', '_sliced_quote_email_sent' ) ) ) {
					$sql_values[]= "($new_invoice_id, '$meta_key', '$meta_value')";
				}
			}
			$sql_query .= implode( ',', $sql_values );
			$wpdb->query( $sql_query );
		}
		
		// update post meta on new invoice with new values
		update_post_meta( $new_invoice_id, '_sliced_recurring_number', $number + 1 );
		update_post_meta( $new_invoice_id, '_sliced_recurring_next', $next_time );
		update_post_meta( $new_invoice_id, '_sliced_invoice_created', $next );
		
		// update the invoice number
		$invoice_options = get_option( 'sliced_invoices' );
		if (
			isset( $invoice_options['sliced_recurring_delay_invoice_number'] )
			&& $invoice_options['sliced_recurring_delay_invoice_number'] === 'on'
		) {
			delete_post_meta( $new_invoice_id, '_sliced_invoice_number' );
		} else {
			$prefix = get_post_meta( $new_invoice_id, '_sliced_invoice_prefix', true );
			$inv_number = sliced_get_next_invoice_number();
			$suffix = get_post_meta( $new_invoice_id, '_sliced_invoice_suffix', true );
			update_post_meta( $new_invoice_id, '_sliced_invoice_number', $inv_number );
			update_post_meta( $new_invoice_id, '_sliced_number', $prefix . $inv_number . $suffix );
			Sliced_Invoice::update_invoice_number( $new_invoice_id );
		}
		
		// update post meta on parent invoice
		update_post_meta( $parent_id, '_sliced_recurring_last_number', $number + 1 );
		update_post_meta( $parent_id, '_sliced_recurring_next', $next );
		update_post_meta( $parent_id, '_sliced_recurring_last_id', $new_invoice_id );
		
		// if a due date is set, update
		$due = get_post_meta( $last_invoice->ID, '_sliced_invoice_due', true );
		if ( ! empty( $due ) ) {
			$next_due  = $this->next_occurance( (int)$due, $frequency );
			update_post_meta( $new_invoice_id, '_sliced_invoice_due', $next_due );
		}
		
		// start the process again
		wp_schedule_single_event( $next, 'sliced_recurring_invoice', array( (int)$new_invoice_id, $number + 1, (int)$parent_id ) );
		
	}
	
	/**
	 * Auto send when scheduled invoice publishes.
	 *
	 * @since 2.1.4
	 */
	public function recurring_auto_send( $post ) {
		
		// was it a recurring invoice that was published, and not some other kind of post?
		if ( $this->is_recurring_invoice( $post->ID ) ) {
			
			// first, do we need to assign the invoice number?
			$invoice_options = get_option( 'sliced_invoices' );
			if (
				isset( $invoice_options['sliced_recurring_delay_invoice_number'] )
				&& $invoice_options['sliced_recurring_delay_invoice_number'] === 'on'
				&& $post->_sliced_invoice_number === ''
			) {
				$prefix = get_post_meta( $post->ID, '_sliced_invoice_prefix', true );
				$number = sliced_get_next_invoice_number();
				$suffix = get_post_meta( $post->ID, '_sliced_invoice_suffix', true );
				update_post_meta( $post->ID, '_sliced_invoice_number', $number );
				update_post_meta( $post->ID, '_sliced_number', $prefix . $number . $suffix );
				Sliced_Invoice::update_invoice_number( $post->ID );
			}
			
			$parent_id = $post->post_parent;
			
			// send the notification if enabled
			$auto_send  = get_post_meta( $parent_id, '_sliced_recurring_auto_send', true );
			if( $auto_send == 'yes' ) {
				$send = new Sliced_Notifications;
				$send->send_the_invoice( $post->ID );
			}
			
			
		}
		
	}
	
	/**
	 * Clean up obsolete crons from trashed invoices etc, avoids "ghost" duplicates
	 *
	 * @since 2.1.5
	 */
	public function clear_obsolete_crons( $parent_id ) {
		$crons = _get_cron_array();
		if ( empty( $crons ) ) {
			return;
		}
		foreach( $crons as $timestamp => $cron ) {
			$obsolete = false;
			if ( ! empty( $cron['sliced_recurring_invoice'] ) )  {
				foreach( $cron['sliced_recurring_invoice'] as $hash => $details ) {
					if ( $details['args'][2] == $parent_id ) {
						$obsolete = true;
					}
				}
				if ( $obsolete ) {
					unset( $crons[$timestamp]['sliced_recurring_invoice'] );
				}
			}
			if ( empty( $crons[$timestamp] ) ) {
				unset( $crons[$timestamp] );
			}
		}
		_set_cron_array( $crons );
	}
	
	/**
	 * Output requirements not met notice.
	 *
	 * @since   2.4.5
	 */
	public function requirements_not_met_notice() {
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'Sliced Invoices Recurring extension cannot find the required <a href="%s">Sliced Invoices plugin</a>. Please make sure the core Sliced Invoices plugin is <a href="%s">installed and activated</a>.', 'sliced-invoices-recurring' ), 'https://wordpress.org/plugins/sliced-invoices/', admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}
	
	/**
	 * Validate settings, make sure all requirements met, etc.
	 *
	 * @version 2.4.8
	 * @since   2.4.5
	 */
	public function validate_settings() {
		
		if ( ! class_exists( 'Sliced_Invoices' ) ) {
			
			// Add a dashboard notice.
			add_action( 'admin_notices', array( $this, 'requirements_not_met_notice' ) );
			
			return false;
		}
		
		return true;
	}
	
}
