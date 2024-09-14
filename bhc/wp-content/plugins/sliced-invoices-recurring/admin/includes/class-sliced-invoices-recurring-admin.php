<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Sliced_Invoices_Recurring_Admin
 */
class Sliced_Invoices_Recurring_Admin {
	
	/** @var  object  Instance of this class */
	protected static $instance = null;
	
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
	 *
	 * @version 2.5.0
	 * @since   2.5.0
	 */
	public function __construct() {
		
		add_action( 'add_meta_boxes', array( $this, 'add_recurring_meta_box' ) );
		add_action( 'admin_action_stop_recurring_invoices', array( $this, 'stop_recurring_invoices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_footer', array( $this, 'recurring_settings_form' ) );
		add_action( 'admin_head', array( $this, 'admin_inline_css' ) );
		add_action( 'admin_init', array( $this, 'save_cron_bot_setting' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'admin_inline_js' ) );
		add_action( 'admin_notices', array( $this, 'recurring_admin_notices' ) );
		add_action( 'load-edit.php', array( $this, 'edit_recurring_invoices' ) );
		add_action( 'load-edit.php', array( $this, 'start_recurring_invoices' ) );
		add_action( 'sliced_admin_col_after_invoice_number', array( $this, 'display_next_recurring_date' ) );
		add_action( 'sliced_admin_col_views', array( $this, 'add_recurring_view_link' ) );
		add_filter( 'request', array( $this, 'filter_recurring' ) );
		add_filter( 'sliced_invoice_option_fields', array( $this, 'add_options_fields' ) );
		
	}
	
	
	/**
	 * Add the options to the admin payment settings.
	 *
	 * @since   2.1.5
	 */
	public function add_options_fields( $options ) {
		
		$options['fields'][] = array(
			'name'      => __( 'Recurring Invoices', 'sliced-invoices-recurring' ),
			'desc'      => '',
			'id'        => 'invoice_recurring_title',
			'type'      => 'title',
		);
		$options['fields'][] = array(
			'name'      => __( "Delay Invoice Number Assignment", 'sliced-invoices-recurring' ),
			'desc'      => __( "Don't assign future invoice number until scheduled invoice date", 'sliced-invoices-recurring' ),
			'after_field' => '<p class="cmb2-metabox-description" style="clear: both; max-width: 800px;">' . __( 'Normally future invoice numbers are assigned in sequential order, at the time the invoice is generated.  If you check this box, future invoice numbers will instead be blank, and only assigned once the scheduled date is reached.  Use this if you want your invoice numbers to be not only sequential, but also in chronological order. (May be helpful in certain EU countries)', 'sliced-invoices-recurring' ) . '</p>',
			'type'      => 'checkbox',
			'id'        => 'sliced_recurring_delay_invoice_number',
		);
		$options['fields'][] = array(
			'name'      => __( 'Sliced Cron Bot', 'sliced-invoices-recurring' ),
			'desc'      => __( "Remotely pings your site, ensuring your cron tasks run on schedule", 'sliced-invoices-recurring' ),
			'after_field' => '<p class="cmb2-metabox-description" style="clear: both; max-width: 800px;">' . __( "May help if you're having trouble with recurring invoices not sending on schedule. (requires active license)", 'sliced-invoices-recurring' ) . '</p>',
			'type'      => 'checkbox',
			'id'        => 'sliced_cron_bot',
		);
		
		return $options;
	}
	
	/**
	 * Adds the recurring meta box container.
	 *
	 * @version 2.5.0
	 * @since   1.0.0
	 */
	public function add_recurring_meta_box() {
		
		global $pagenow;
		
		$SR = Sliced_Recurring::get_instance();
		
		// check if we are adding a new invoice
		if ( $pagenow === 'post-new.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'sliced_invoice' ) {
			// add the new invoice meta box
			add_meta_box( 
				'sliced_invoices_recurring',
				sprintf( __( 'Recurring %s', 'sliced-invoices-subscriptions' ), sliced_get_invoice_label_plural() ),
				array( $this, 'render_meta_box_help' ),
				'sliced_invoice',
				'side',
				'low'
			);
		}
		
		// otherwise, we go on...
		// check if we have a published invoice
		$id = isset( $_GET['post'] ) ? intval( $_GET['post'] ) : false;
		if ( ! $id ) {
			return;
		}
		
		// check if part of a deposit/balance
		$has_child  = get_post_meta( $id, '_sliced_deposit_child', true );
		$has_parent = get_post_meta( $id, '_sliced_deposit_parent', true );
		if ( $has_child || $has_parent ) {
			return;
		}
		
		// check if this is a subscription invoice
		$is_subscription = $SR->is_subscription_invoice( $id );
		if ( $is_subscription ) {
			return;
		}
		
		// add the meta box
		add_meta_box( 
			'sliced_invoices_recurring',
			sprintf( __( 'Recurring %s', 'sliced-invoices-recurring' ), sliced_get_invoice_label_plural() ) , array( $this, 'render_meta_box_content' ),
			'sliced_invoice',
			'side', 
			'high'
		);
		
	}
	
	/**
	 * Add a link to view recurring invoices only.
	 *
	 * @since 1.0.0
	 */
	public function add_recurring_view_link( $views ) {
		
		if ( sliced_get_the_type() !== 'invoice' ) {
			return;
		}
		
		$args = array(
			'post_type' => 'sliced_invoice',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => array(
				array(
					'key'       => '_sliced_recurring_number',
					'value'     => '1',
					'compare'   => '=',
				),
			),
		);
		
		$ids = array();
		
		$the_query = new WP_Query( apply_filters( 'sliced_reports_query', $args ) );
		if( $the_query->posts ) :
			foreach ( $the_query->posts as $id ) {
				$ids[] = $id;
			};
		endif;
		
		$count = count( $ids );
		
		$views['recurring'] = "<a href='" . esc_url( add_query_arg( array( 'invoice_type' => 'recurring' ) ) ) . "'>" . __( 'Recurring', 'sliced-invoices-recurring' ) . " <span class='count'>(" . esc_html( $count ) . ")</span></a>";
		
		return $views;
	}
	
	/**
	 * Add inline css to admin area.
	 *
	 * @since 2.4.0
	 */
	public function admin_inline_css() {
		#region admin_inline_css
		if ( sliced_get_the_type() !== 'invoice' ) {
			return;
		}
		?>
		<style type="text/css">
			/* recurring invoices */
			.sliced .recur_next {
				color: #888;
				font-size: 10px;
				letter-spacing: -0.5px;
			}
			.sliced .recur_next .dashicons-clock {
				color: #60ad5d;
				float: left;
				font-size: 15px;
				margin: 4px 2px 0 0;
				width: 15px;
			}
			.sliced .recur_next.scheduled .dashicons-clock {
				color: #ED904E;
			}
			.sliced .recur_next.stopped .dashicons-clock {
				color: #d85c27;
			}
			.sliced .recur_next .recur_date {
				display: block;
				line-height: 10px;
			}
			.sliced tr .title a {
				font-weight: normal;
			}
			.sliced tr .row-title .dashicons-controls-repeat{
				padding: 0 3px 0 0;
			}
			.sliced tr:not(.level-0) .row-title .dashicons-controls-repeat{
				color: #888;
				padding: 0 3px 0 10px;
			}
			.sliced .recurring input.recur_days {
				float: left;
				margin: 0 10px 0 0;
				width: 50px;
			}
			.sliced .recurring .sml-label {
				color: #888;
				font-size: 12px;
				margin: 0 12px 0 2px;
			}
			.sliced .form-field select {
				margin-top: 0;
				min-width: 50px;
			}
		</style>
		<?php
		#endregion admin_inline_css
	}
	
	/**
	 * Add inline js to add dashicons.
	 *
	 * @since 1.0.0
	 */
	public function admin_inline_js() {
		#region admin_inline_js
		if ( sliced_get_the_type() !== 'invoice' ) {
			return;
		}
		?>
			<script type="text/javascript">
				(function( $ ) {
					'use strict';
					
					$(document).ready( function () {
						
						var recurringRow = $( '.recur_next' ).closest( '.type-sliced_invoice' );
						$(recurringRow).find( '.row-title' ).prepend('<span class="dashicons dashicons-controls-repeat"></span>');
						
					});
				})( jQuery );
			</script>
		
		<?php
		#endregion admin_inline_js
	}
	
	/**
	 * Add the next recurring date.
	 *
	 * @since 1.0.0
	 */
	public function display_next_recurring_date() {
		
		if ( sliced_get_the_type() !== 'invoice' ) {
			return;
		}
		
		$SR         = Sliced_Recurring::get_instance();
		$id         = sliced_get_the_id();
		$recur_next = $SR->get_meta( $id, $SR->meta_key['next'] );
		$stopped 	= $SR->get_meta( $id, $SR->meta_key['stopped'] );
		$parent     = wp_get_post_parent_id( $id );
		
		$invoice = get_post( $id );
		$invoice_number = get_post_meta( $id, '_sliced_invoice_number', true );
		
		if ( ! empty( $recur_next ) ) {
			
			if ( $parent == 0 ) {
				
				if ( $stopped != 'yes' ) {
					
					echo '<br><span class="recur_next"><span class="dashicons dashicons-clock"></span>' . 
						__( 'Recurring Started', 'sliced-invoices-recurring' ) . '<br style="clear:both;">' .
						sprintf( __( 'Invoice #%s in the series', 'sliced-invoices-recurring' ), get_post_meta( $id, '_sliced_recurring_number', true ) ) .
					'</span>';
					
				} else {
					
					echo '<br><span class="recur_next stopped"><span class="dashicons dashicons-clock"></span>' . 
						__( 'Recurring Stopped', 'sliced-invoices-recurring' ) . '<br style="clear:both;">' .
						sprintf( __( 'Invoice #%s in the series', 'sliced-invoices-recurring' ), get_post_meta( $id, '_sliced_recurring_number', true ) ) .
					'</span>';
					
				}
				
			} else {
				
				if ( $invoice_number ) {
					echo '<br>';
				}
				
				if ( $invoice->post_status === 'future' ) {
					
					$created = get_post_meta( $id, '_sliced_invoice_created', true );
					if ( version_compare( SLICED_VERSION, '3.8.0', '>=' ) ) {
						$created_date = Sliced_Shared::get_local_date_i18n_from_timestamp( $created );
					} else {
						$created_date = date( get_option('date_format'), $created );
					}
					
					echo '<span class="recur_next scheduled"><span class="dashicons dashicons-clock"></span>' . 
							sprintf( _x( 'Draft until %s', '% = date', 'sliced-invoices-recurring' ), $created_date ) . '<br style="clear:both;">' .
							sprintf( __( 'Invoice #%s in the series', 'sliced-invoices-recurring' ), get_post_meta( $id, '_sliced_recurring_number', true ) ) .
						'</span>';
					
				} else {
					
					echo '<span class="recur_next">' . 
							sprintf( __( 'Invoice #%s in the series', 'sliced-invoices-recurring' ), get_post_meta( $id, '_sliced_recurring_number', true ) ) .
						'</span>';
					
				}
				
			}
			
		}
		
	}
	
	/**
	 * Set the recurring invoice schedule.
	 *
	 * @since 1.0.0
	 */
	public function edit_recurring_invoices() {
		
		if ( sliced_get_the_type() !== 'invoice' ) {
			return;
		}
		
		//Check if our nonce is set.
		if ( ! isset( $_POST['sliced_edit_recurring_nonce'] ) ) {
			return;
		}
		
		if ( ! wp_verify_nonce( $_POST['sliced_edit_recurring_nonce'], 'edit_recurring_invoices' ) ) {
			wp_die( 'Oh no, there was an issue editing the recurring invoice.' );
		}
		
		$SR = Sliced_Recurring::get_instance();
		$parent_id = (int)$_POST['sliced_recurring_invoice_id'];
		// delete any reference to previous stopped invoices
		delete_post_meta( $parent_id, '_sliced_recurring_stopped' );
		
		// delete any current cron jobs for this invoice so we can start again
		$SR->delete_the_cron();
		
		// get post data & work out when we will start the recurrences
		$frequency      = (int) $_POST['sliced_recurring_interval'] . ' ' . esc_html( $_POST['sliced_recurring_timeframe'] );
		$auto_send      = esc_html( $_POST['sliced_recurring_auto_send'] );
		$start_day      = zeroise( (int) $_POST['sliced_recurring_start_day'], 2 );
		$start_mth      = zeroise( (int) $_POST['sliced_recurring_start_month'], 2 );
		$start_year     = (int) $_POST['sliced_recurring_start_year'];
		$start_time     = strtotime( $start_year . '-' . $start_mth . '-' . $start_day . ' ' . $SR->sql_time_plus_one_hour() );
		$next_time      = $SR->next_occurance( (int)$start_time, $frequency );
		
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
		
		// update the required meta data on the parent invoice
		update_post_meta( $parent_id, '_sliced_recurring_next', $start_time );
		
		// get children invoices
		$ids = array();
		$args = array(
			'post_parent' => $parent_id,
			'post_type'   => 'sliced_invoice', 
			'numberposts' => -1,
			'post_status' => 'future' 
		);
		$children = get_children( $args );
		if ( $children ) {
			foreach ( $children as $child ) {
				$ids[] = $child->ID;
				// update meta data on future child invoice(s)
				update_post_meta( $child->ID, '_sliced_invoice_created', $start_time );
				update_post_meta( $child->ID, '_sliced_recurring_next', $next_time );
				$post_date = new DateTime();
				$post_date->setTimestamp( $start_time );
				$post_date->setTimezone( $timezone );
				$args = array(
					'ID'              => $child->ID,
					'post_date'       => $post_date->format( 'Y-m-d H:i:s' ),				  // local timezone timestamp
					'post_date_gmt'   => gmdate( 'Y-m-d H:i:s', $post_date->getTimestamp() ), // UTC timestamp
				);
				wp_update_post( $args );
			}
		}
		$ids[] = $parent_id;
		
		// update meta data on both parent and future children
		foreach ( $ids as $id ) {
			update_post_meta( $id, '_sliced_recurring_frequency', $frequency );
			update_post_meta( $id, '_sliced_recurring_auto_send', $auto_send );
		}
		
		$last_id     = $SR->get_meta( $parent_id, $SR->meta_key['last_id'] );
		$last_number = $SR->get_meta( $parent_id, $SR->meta_key['last_number'] );
		
		// re-create the next scheduled recurring invoice.
		wp_schedule_single_event( $start_time, 'sliced_recurring_invoice', array( (int)$last_id, (int)$last_number, (int)$parent_id ) );
		
	}
	
	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since   2.0.0
	 */
	public function enqueue_scripts() {
		
		if ( sliced_get_the_type() !== 'invoice' ) {
			return;
		}
		
		wp_localize_script( 'sliced-invoices', 'sliced_stop_recurring', array(
			'stop_recurring' => sprintf( __( 'Are you sure you want to stop these recurring %s', 'sliced-invoices' ),  sliced_get_invoice_label_plural() ),
			)
		);
		
	}
	
	/**
	 * Get existing recurring info for the popup form inputs.
	 *
	 * @since 1.0.0
	 */
	private function existing_settings_data( $id ) { 
		
		if( ! $id ) {
			return;
		}
		
		$SR = Sliced_Recurring::get_instance();
		
		$recur_next = $SR->get_meta( $id, $SR->meta_key['next'] );
		$frequency  = $SR->get_meta( $id, $SR->meta_key['frequency'] );
		$auto_send  = $SR->get_meta( $id, $SR->meta_key['auto_send'] );
		
		$day   = $recur_next ? date( 'j', (int) $recur_next ) : '';
		$month = $recur_next ? date( 'n', (int) $recur_next ) : '';
		$year  = $recur_next ? date( 'Y', (int) $recur_next ) : '';
		
		$recur = preg_split( '/ +/', $frequency );
		
		return array(
			'day'        => $day,
			'month'      => $month,
			'year'       => $year,
			'recur_days' => isset( $recur[0] ) ? $recur[0] : '',
			'timeframe'  => isset( $recur[1] ) ? $recur[1] : '',
			'auto_send'  => $auto_send,
		);
	}
	
	/**
	 * Modify the query when only displaying recurring.
	 *
	 * @version 2.5.0
	 * @since   1.0.0
	 */
	public function filter_recurring( $vars ) {
		
		if (
			is_admin()
			&& isset( $_GET['invoice_type'] )
			&& $_GET['invoice_type'] === 'recurring' 
		) {
			$vars = array_merge(
				$vars,
				array(
					'meta_query' => array(
						array(
							'key'     => '_sliced_recurring_number',
							'compare' => 'EXISTS',
						),
					),
				)
			);
		}
		
		return $vars;
	}
	
	/**
	 * Admin notice.
	 *
	 * @version 2.4.8
	 * @since   1.0.0
	 */
	public function recurring_admin_notices() {
		
		global $pagenow;
		
		if ( sliced_get_the_type() !== 'invoice' ) {
			return;
		}
		
		if ( $pagenow === 'edit.php' && isset( $_GET['recurring'] ) && $_GET['recurring'] === 'created' ) {
			echo '<div class="updated">
				<p>' . __( 'Recurring invoice successfully created.', 'sliced-invoices-recurring' ) . '</p>
			</div>';
		}
		
		if ( $pagenow === 'edit.php' && isset( $_GET['recurring'] ) && $_GET['recurring'] === 'stopped' ) {
			echo '<div class="notice notice-info">
				<p>' . __( 'Recurring invoices stopped.', 'sliced-invoices-recurring' ) . '</p>
			</div>';
		}
		
	}
	
	/**
	 * Popup form to create the recurring.
	 *
	 * @since 1.0.0
	 */
	public function recurring_settings_form() {
		#region recurring_settings_form
		global $pagenow;
		
		if ( $pagenow !== 'post.php' || sliced_get_the_type() !== 'invoice' ) {
			return;
		}
		
		$SR         = Sliced_Recurring::get_instance();
		$id         = intval( $_GET['post'] );
		$query_args = array( 'post_type' => 'sliced_invoice', 'recurring' => 'created' );
		$admin_url  = get_admin_url() . 'edit.php';
		$recurring  = $SR->is_recurring_invoice( $id );
		$existing   = $this->existing_settings_data( $id );
		
		?>
		<div id="create_recurring" style="display:none">
			
			<p><strong><?php _e( 'Important!', 'sliced-invoices-recurring' ) ?></strong> <?php _e( 'Please ensure that any changes to this invoice are saved before creating the recurring invoice.', 'sliced-invoices-recurring' ) ?></p>
			
			<form method="POST" action="<?php echo esc_url( add_query_arg( $query_args, $admin_url ) ); ?>">
				
				<table class="form-table recurring">
					<tbody>
						<tr class="form-field">
							<th scope="row">
								<label><?php _e( 'Recur Every', 'sliced-invoices-recurring' ) ?> </label>
							</th>
							<td>
								<input class="recur_days" type="text" name="sliced_recurring_interval" id="sliced_recurring_interval" value="<?php echo $existing['recur_days'] ? $existing['recur_days'] : '7' ?>" />
								<select name="sliced_recurring_timeframe" id="sliced_recurring_timeframe">
									<option value="days" <?php echo $existing['timeframe'] == 'days' ? 'selected="selected"' : ''; ?>><?php _e( 'Day(s)', 'sliced-invoices-recurring' ) ?></option>
									<option value="months" <?php echo $existing['timeframe'] == 'months' ? 'selected="selected"' : ''; ?>><?php _e( 'Month(s)', 'sliced-invoices-recurring' ) ?></option>
									<option value="years" <?php echo $existing['timeframe'] == 'years' ? 'selected="selected"' : ''; ?>><?php _e( 'Year(s)', 'sliced-invoices-recurring' ) ?></option>
								</select>
							</td>
						</tr>
						
						<tr class="form-field">
							<th scope="row">
								<label><?php _e( 'Next Invoice Date', 'sliced-invoices-recurring' ) ?> </label>
							</th>
							<td>
								<span class="select-wrap">
									<select name="sliced_recurring_start_day" id="sliced_recurring_start_day">
										<?php for ($i=1; $i <= 31; $i++) { ?>
											<option value="<?php echo $i ?>" <?php 
											if( ! empty( $existing['day'] ) ) {	
												selected( $existing['day'], $i );
											} else {
												selected( date( 'j', strtotime( '+7 days' ) ), $i );
											}
											?>><?php echo $i ?></option>
										<?php } ?>
									</select>
									<span class="sml-label">Day</span>
								</span>
								
								<span class="select-wrap">
									<select name="sliced_recurring_start_month" id="sliced_recurring_start_month">
										<?php for ($i=1; $i <= 12; $i++) { ?>
											<option value="<?php echo $i ?>" <?php 
											if( ! empty( $existing['month'] ) ) {	
												selected( $existing['month'], $i );
											} else {
												selected( date( 'n', strtotime( '+7 days' ) ), $i );
											}
											?>><?php echo $i ?></option>
										<?php } ?>
									</select>
									<span class="sml-label">Month</span>
								</span>
								
								<span class="select-wrap">
									<select name="sliced_recurring_start_year" id="sliced_recurring_start_year">
										<?php for ($i= date('Y'); $i <= (date('Y') + 5); $i++) { ?>
											<option value="<?php echo $i ?>" <?php 
											if( ! empty( $existing['year'] ) ) {	
												selected( $existing['year'], $i );
											} else {
												selected( date( 'Y', strtotime( '+7 days' ) ), $i );
											}
											?>><?php echo $i ?></option>
										<?php } ?>
									</select>
									<span class="sml-label">Year</span>
								</span>
								<p class="description"><?php _e( 'The next invoice in the series will be published on this date.', 'sliced-invoices-recurring' ) ?></p>
							</td>
						</tr>
						
						<tr class="form-field">
							<th scope="row">
								<label><?php _e( 'Send to Client', 'sliced-invoices-recurring' ) ?> </label>
							</th>
							<td>
								<select name="sliced_recurring_auto_send" id="sliced_recurring_auto_send">
									<option value="yes" <?php selected( $existing['auto_send'], 'yes' ); ?>><?php _e( 'Yes', 'sliced-invoices-recurring' ) ?></option>
									<option value="no" <?php selected( $existing['auto_send'], 'no' ); ?>><?php _e( 'No', 'sliced-invoices-recurring' ) ?></option>
								</select>
								<p class="description"><?php _e( 'Choosing yes will automatically send the \'Invoice Available\' email to the client when the next invoice date is reached.', 'sliced-invoices-recurring' ) ?></p>
							</td>
						</tr>
					</tbody>
				</table>
				
				<input type="hidden" name="sliced_recurring_invoice_id" value="<?php echo (int)$_GET['post'] ?>" />
				<?php  ?>
				
				<?php // if it's already a recurring
				if( $recurring ) { 
					wp_nonce_field( 'edit_recurring_invoices', 'sliced_edit_recurring_nonce' ); 
				} else {
					wp_nonce_field( 'start_recurring_invoices', 'sliced_start_recurring_nonce' ); 
				} ?>
				
				<p><input class="button button-primary button-large" type="submit" name="" value="<?php _e( 'Save These Settings', 'sliced-invoices-recurring' ) ?>"></p>
				
			</form>
			
		</div>
		
		<?php
		#endregion recurring_settings_form
	}
	
	/**
	 * Render Meta Box content.
	 *
	 * @since 1.0.0
	 */
	public function render_meta_box_content() {
		
		$SR = Sliced_Recurring::get_instance();
		$id = intval( $_GET['post'] );
		$recurring = $SR->is_recurring_invoice( $id );
		
		// if we have recurring invoices
		if ( ! empty( $recurring ) ) {
			
			$output = sprintf( __( '<strong>%1s #%2s</strong> in the series</p>', 'sliced-invoices-recurring' ), sliced_get_invoice_label(), $recurring );
			
			// The Parent
			if ( $recurring == '1' ) {
				
				$recur_next = $SR->get_meta( $id, $SR->meta_key['next'] );
				$frequency  = $SR->get_meta( $id, $SR->meta_key['frequency'] );
				$auto_send  = $SR->get_meta( $id, $SR->meta_key['auto_send'] );
				
				$output .= sprintf( _x( '<p>Next %1s will be published on:<br><strong>%2s (in %3s)</strong></p>', '%s = human-readable time difference', 'sliced-invoices-recurring' ), sliced_get_invoice_label(), date( get_option( 'date_format' ), $recur_next ), human_time_diff( $recur_next, current_time( 'timestamp', 1 ) ) );
				
				$output .= sprintf( __( '<p>Recurring frequency is every: <strong>%1s </strong></p>', 'sliced-invoices-recurring' ), $frequency );
				
				$output .= sprintf( __( '<p>Automatically send to client: <strong>%1s </strong></p>', 'sliced-invoices-recurring' ), ucwords( $auto_send ) );
				
				// Buttons
				$stop_url = add_query_arg( array( 'action' => 'stop_recurring_invoices', 'parent_id' => $id, '_wpnonce' => wp_create_nonce( 'stop_recurring' ) ), admin_url( 'admin.php' ) );
				
				$output .= '<a id="stop_recurring" class="button button-small" href="' . esc_url( $stop_url ) . '" title="">' . __( 'Stop Recurring', 'sliced-invoices-recurring' ) . '</a> ';
				
				$output .= '<a class="button button-small thickbox" href="#TB_inline?width=500&height=350&inlineId=create_recurring" title="">' . __( 'Edit Recurring', 'sliced-invoices-recurring' ) . '</a>';
				
			} else {
				
				$output .= '<p>' . sprintf( __( 'To edit the recurring schedule, <a href="%1s">go to the parent %2s</a>.', 'sliced-invoices-recurring' ), get_edit_post_link( wp_get_post_parent_id( $id ) ), sliced_get_invoice_label() ) . '</p>';
				
			}
			
		} else {
			
			$output = '<a class="button button-large thickbox" href="#TB_inline?width=500&height=350&inlineId=create_recurring" title="' . __( 'Recurring Invoice', 'sliced-invoices-recurring' ) . '">' . __( 'Create Recurring Invoices', 'sliced-invoices-recurring' ) . '</a>';
		
		}
		
		echo $output;
		
	}
	
	/**
	 * Render Help Meta Box content.
	 *
	 * @since 2.3.0
	 */
	public function render_meta_box_help() {
		
		$output = '<em>' . __( 'Recurring Invoice options will be displayed after you save this invoice.', 'sliced-invoices-recurring' ) . '</em>';
		
		echo $output;
		
	}
	
	/**
	 * Save Cron Bot setting.
	 *
	 * @since 2.5.0
	 */
	public function save_cron_bot_setting() {
		global $pagenow;
		
		if ( $pagenow == 'admin.php' && ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'sliced_' ) !== false ) && isset( $_POST['submit-cmb'] ) ) {
			if ( isset( $_POST['sliced_cron_bot'] ) && $_POST['sliced_cron_bot'] === "on" ) {
				$this->sliced_cron_bot_toggle( true );
			} else {
				$this->sliced_cron_bot_toggle( false );
			}
		}
	}
	
	/**
	 * Sliced Cron Bot toggler.
	 *
	 * @since 2.1.5
	 */
	public function sliced_cron_bot_toggle ( $on ) {
		
		// retrieve our license key from the DB
		$licenses = get_option( 'sliced_licenses' );
		if ( isset( $licenses['recurring_invoices_license_key'] ) ) {
			$license_key = trim( $licenses['recurring_invoices_license_key'] );
		} else {
			$license_key = '';
		}
		
		if ( $on ) {
			// enable
			wp_remote_post(
				'https://slicedinvoices.com/sliced-cron-bot.php',
				array(
					'blocking' => false,
					'body' => array(
						'url'     => site_url(),
						'license' => $license_key,
						'action'  => 'enable',
					)
				)
			);
		} else {
			// disable
			wp_remote_post(
				'https://slicedinvoices.com/sliced-cron-bot.php',
				array(
					'blocking' => false,
					'body' => array(
						'url'     => site_url(),
						'license' => $license_key,
						'action'  => 'disable',
					)
				)
			);
		}
	}
	
	/**
	 * Set the recurring invoice schedule.
	 *
	 * @since 1.0.0
	 */
	public function start_recurring_invoices() {
		
		if ( sliced_get_the_type() !== 'invoice' ) {
			return;
		}
		
		//Check if our nonce is set.
		if ( ! isset( $_POST['sliced_start_recurring_nonce'] ) ) {
			return;
		}
		
		if ( ! wp_verify_nonce( $_POST['sliced_start_recurring_nonce'], 'start_recurring_invoices' ) ) {
			wp_die( 'Oh no, there was an issue creating the recurring invoice.' );
		}
		
		$SR = Sliced_Recurring::get_instance();
		$id = (int)$_POST['sliced_recurring_invoice_id'];
		// delete any reference to previous stopped invoices
		delete_post_meta( $id, '_sliced_recurring_stopped' );
		//delete_post_meta( $id, '_sliced_log' );
		
		// get post data & work out when we will start the recurrences
		$frequency      = (int) $_POST['sliced_recurring_interval'] . ' ' . esc_html( $_POST['sliced_recurring_timeframe'] );
		$auto_send      = esc_html( $_POST['sliced_recurring_auto_send'] );
		$start_day      = zeroise( (int) $_POST['sliced_recurring_start_day'], 2 );
		$start_mth      = zeroise( (int) $_POST['sliced_recurring_start_month'], 2 );
		$start_year     = (int) $_POST['sliced_recurring_start_year'];
		$start_time     = strtotime( $start_year . '-' . $start_mth . '-' . $start_day . ' ' .  date( 'H:i:s' ) ); // <-- old way
		
		// new more accurate way to get the start time, requires Sliced Invoices 3.8+
		if ( version_compare( SLICED_VERSION, '3.8.0', '>=' ) ) {
			$start_time = Sliced_Shared::get_timestamp_from_local_time(
				$start_year,
				$start_mth,
				$start_day,
				current_time( "H" ),
				current_time( "i" ),
				current_time( "s" )
			);
		}
		
		// update the required meta data on the parent id
		update_post_meta( $id, '_sliced_recurring_frequency', $frequency );
		update_post_meta( $id, '_sliced_recurring_next', $start_time );
		update_post_meta( $id, '_sliced_recurring_number', 1 );
		update_post_meta( $id, '_sliced_recurring_last_number', 1 );
		update_post_meta( $id, '_sliced_recurring_auto_send', $auto_send );
		update_post_meta( $id, '_sliced_recurring_last_id', $id );
		
		// create the first scheduled recurring invoice. Give number 1 as it will be incremented later on
		//wp_schedule_single_event( $start_time, 'sliced_recurring_invoice', array( (int)$id, 1, (int)$id ) );
		$SR->create_recurring_invoice( (int)$id, 1, (int)$id );
		
	}
	
	/**
	 * Deletes a cron event.
	 *
	 * @param string $name The hookname of the event to delete.
	 */
	public function stop_recurring_invoices() {
		
		if ( ! ( isset( $_REQUEST['action'] ) && 'stop_recurring_invoices' == $_REQUEST['action'] ) ) {
			wp_die( 'No invoices to stop!' );
		}
		
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'stop_recurring' ) ) {
			wp_die( 'Ooops, something went wrong, please try again later.' );
		}
		
		// passed the checks, now delete
		$SR = Sliced_Recurring::get_instance();
		$SR->delete_the_cron();
		
		$parent_id  = $_GET['parent_id'] ? (int) $_GET['parent_id'] : $_POST['sliced_recurring_invoice_id']; 
		$last_id    = $SR->get_meta( $parent_id, $SR->meta_key['last_id'] );
		
		wp_delete_post( (int)$last_id );
		
		update_post_meta( (int)$_GET['parent_id'], '_sliced_recurring_stopped', 'yes' );
		
		$query_args = array( 'post_type' => 'sliced_invoice', 'recurring' => 'stopped' );
		$admin_url  = get_admin_url() . 'edit.php';
		wp_redirect( add_query_arg( $query_args, $admin_url ) );
		exit;
		
	}
	
}
