<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

/**
 * Calls the class.
 */
function sliced_call_recurring_invoices_updater_class() {
    return Sliced_Recurring_Invoices_Updater::get_instance();
}
add_action('sliced_loaded', 'sliced_call_recurring_invoices_updater_class', 1);


class Sliced_Recurring_Invoices_Updater {

	private $store_url   = 'http://slicedinvoices.com';
	private $name        = 'Recurring Invoices Extension';
	private $download_id = '2420';
	private $version     = SLICED_INVOICES_RECURRING_VERSION;
	private $slug        = 'recurring_invoices';

	private $key_name    = 'recurring_invoices_license_key';
	private $status_name = 'recurring_invoices_license_status';
	private $error_name  = 'recurring_invoices_license_error';
    
    private $license_key    = '';
    private $license_status = '';
	private $license_error  = '';
	
	protected static $single_instance = null;

    public function __construct() {

  		if ( ! class_exists( 'Sliced_Plugin_Updater', false ) ) {
			include( plugin_dir_path( __FILE__ ) . '/class-base-updater.php' );
		}

    	// retrieve our license key info from the DB
		$licenses = get_option( 'sliced_licenses' );
		if ( isset( $licenses[ $this->key_name ] ) ) {
			$this->license_key = trim( $licenses[ $this->key_name ] );
		}
		if ( isset( $licenses[ $this->status_name ] ) ) {
			$this->license_status = trim( $licenses[ $this->status_name ] );
		}
		if ( isset( $licenses[ $this->error_name ] ) ) {
			$this->license_error = trim( $licenses[ $this->error_name ] );
		}

		// hooks
        add_filter( 'sliced_licenses_option_fields', array( $this, 'license_field' ), 1 );
		add_action( 'admin_init', array( $this, 'plugin_updater' ), 0 );
		add_action( 'admin_init', array( $this, 'activate_license' ) );
		add_action( 'admin_init', array( $this, 'deactivate_license' ) );
		add_action( 'admin_init', array( $this, 'updater_notices' ) );
		add_action( 'sliced_invoices_'. $this->slug . '_updater', array( $this, 'check_license' ) );
		
		// check cron
		if ( ! wp_next_scheduled ( 'sliced_invoices_'. $this->slug . '_updater' ) ) {
			wp_schedule_event( time(), 'daily', 'sliced_invoices_'. $this->slug . '_updater' );
		}
		
    }
	
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}


	public function plugin_updater() {
		// setup the updater
		new Sliced_Plugin_Updater( $this->store_url, SLICED_INVOICES_RECURRING_FILE, array(
				'version'   => $this->version, // current version number
				'license'   => $this->license_key, // license key (used get_option above to retrieve from DB)
				'item_name' => $this->name, // name of this plugin
				'author'    => 'Sliced Invoices', // author of this plugin
				'beta'		=> false,
			)
		);

	}


	public function license_field( $options ) {
		$options['fields'][] = array(
			'name'        => $this->name,
			'desc'        => __( 'Enter the License Key for this extension', 'sliced-invoices-recurring' ),
			'id'          => $this->key_name,
			'type'        => 'text',
			'default'     => $this->license_key,
			'render_row_cb' => array( $this, 'license_field_render_row_cb' ),
		);

		return $options;
	}
	
	
	public function license_field_render_row_cb( $field_args, $field ) {
		$id          = $field->args( 'id' );
		$label       = $field->args( 'name' );
		$name        = $field->args( '_name' );
		$value       = $field->escaped_value();
		$description = $field->args( 'description' );
		?>	
		<div class="cmb-row cmb-type-text cmb2-id-<?php echo str_replace( '_', '-', $this->slug ); ?>-license-key table-layout">
			<div class="cmb-th">
				<label for="<?php echo $this->slug; ?>_license_key"><?php echo $this->name; ?></label>
			</div>
			<div class="cmb-td">
				<input type="text" class="regular-text" name="<?php echo $this->slug; ?>_license_key" id="<?php echo $this->slug; ?>_license_key" value="<?php echo $value; ?>">
				<p class="cmb2-metabox-description"><?php _e( 'Enter the License Key for this extension', 'sliced-invoices-recurring' ); ?></p>
				<?php $this->after_field( $field_args, $field ); ?>
			</div>
		</div>
		<?php
	}

	public function after_field( $args, $field ) {
		$escaped_value = $field->escaped_value();
		if ( empty( $escaped_value ) ) {
			return;
		}

		$status = '';
		if ( $this->license_status ) {
			$status = 'valid' === $this->license_status ? 'active' : $this->license_status;
			$status = '<span class="license-status license-'. $status .'">' . sprintf( esc_html__( 'License: %s', 'sliced-invoices-recurring' ), $status ) . '</span>';
		}

		$nonce = wp_nonce_field( 'sliced_license_nonce', 'sliced_license_nonce_' . $this->slug, false, false );

		$id = $this->slug . ( 'valid' === $this->license_status ? '_license_deactivate' : '_license_activate' );

		$label = 'valid' === $this->license_status
			? esc_html__( 'Deactivate License', 'sliced-invoices-recurring' )
			: esc_html__( 'Activate License', 'sliced-invoices-recurring' );

		printf(
			'<p>%1$s%2$s<input type="submit" class="button-secondary" name="%3$s" value="%4$s"/></p>',
			$status,
			$nonce,
			$id,
			$label
		);

	}

	public function activate_license() {

		// listen for our activate button to be clicked
		if ( isset( $_POST[ $this->slug . '_license_activate'], $_POST[ $this->key_name ] ) ) {

			// run a quick security check
			if ( ! check_admin_referer( 'sliced_license_nonce', 'sliced_license_nonce_' . $this->slug ) ) {
				return; // get out if we didn't click the Activate button
			}

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => sanitize_text_field( $_POST[ $this->key_name ] ),
				'item_name'  => urlencode( $this->name ), // the name of our product in EDD
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post( $this->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );


			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			$licenses = get_option( 'sliced_licenses' );
			$licenses[ $this->key_name ]    = trim( $api_params['license'] );
			$licenses[ $this->status_name ] = trim( $license_data->license );
			
			// $license_data->license will be either "valid" or "invalid"
			if ( $license_data->license !== 'valid' && $license_data->error ) {
				$licenses[ $this->error_name ] = trim( $license_data->error );
			} else {
				$licenses[ $this->error_name ] = '';
			}
			
			update_option( 'sliced_licenses', $licenses );

			$this->license_key    = $licenses[ $this->key_name ];
			$this->license_status = $licenses[ $this->status_name ];
			$this->license_error  = $licenses[ $this->error_name ];
			//wp_redirect( admin_url( 'admin.php?page=sliced_licenses' ) );
			//exit;
		}
	}

	public function deactivate_license() {

		// listen for our activate button to be clicked
		if ( isset( $_POST[ $this->slug . '_license_deactivate'] ) ) {

			// run a quick security check
			if ( ! check_admin_referer( 'sliced_license_nonce', 'sliced_license_nonce_' . $this->slug ) ) {
				return; // get out if we didn't click the Activate button
			}

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $this->license_key,
				'item_name'  => urlencode( $this->name ), // the name of our product in EDD
				'url'        => home_url(),
			);

			// Call the custom API.
			$response = wp_remote_post( $this->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			$licenses = get_option( 'sliced_licenses' );
			$licenses[ $this->status_name ] = trim( $license_data->license );

			$this->license_status = $licenses[ $this->status_name ];

			// $license_data->license will be either "deactivated" or "failed"
			if ( $license_data->license == 'deactivated' ) {
				update_option( 'sliced_licenses', $licenses );
				//wp_redirect( admin_url( 'admin.php?page=sliced_licenses' ) );
				//exit;
			}

		}
	}
	
	public function check_license() {

		if ( $this->license_key === '' ) {
			// if there's no key to check, clear out any old status and stop
			$licenses = get_option( 'sliced_licenses' );
			$licenses[ $this->status_name ] = '';
			$licenses[ $this->error_name ] = '';
			update_option( 'sliced_licenses', $licenses );
			return false;
		}
		
		// otherwise, we go on...
		$api_params = array(
			'edd_action' => 'check_license',
			'license'    => $this->license_key,
			'item_name'  => urlencode( $this->name ),
			'url'        => home_url()
		);

		// Call the custom API.
		$response = wp_remote_post( $this->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		// decode response
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );
		
		if ( ! $license_data ) {
			return false;
		}
		
		// $license_data->license will be either "valid" or "inactive"
		if ( $this->license_status !== ( $license_data->license === 'valid' ? 'valid' : 'invalid' ) ) {
			// this means there was a change in status.
			// cache new status to DB
			$licenses = get_option( 'sliced_licenses' );
			$licenses[ $this->status_name ] = trim( $license_data->license === 'site_inactive' ? 'inactive' : $license_data->license );
			$licenses[ $this->error_name ] = '';
			update_option( 'sliced_licenses', $licenses );
			
			$this->license_status = $licenses[ $this->status_name ];
			$this->license_error  = $licenses[ $this->error_name ];
		}
		
		return $license_data->license;
	}
	
	public function updater_notices_clear( $exclude = '' ) {
	
		// check just in case we're on < Sliced Invoices v3.5.0
		if ( class_exists( 'Sliced_Admin_Notices' ) ) {
		
			$notices = array(
				$this->slug . '_license_expired',
				$this->slug . '_license_inactive',
				$this->slug . '_license_missing',
				$this->slug . '_license_disabled',
			);
		
			foreach ( $notices as $notice ) {
				if ( Sliced_Admin_Notices::has_notice( $notice ) && $notice !== $exclude ) {
					Sliced_Admin_Notices::remove_notice( $notice );
				}
			}
			
		}
		
	}
	
	public function updater_notices() {
		
		global $pagenow;
		
		// check just in case we're on < Sliced Invoices v3.5.0
		if ( class_exists( 'Sliced_Admin_Notices' ) ) {
		
			if (
				$pagenow === 'admin.php'
				&& isset( $_GET['page'] )
				&& $_GET['page'] === 'sliced_licenses'
				&& isset( $_POST['object_id'] )
			) {
				// we must have just saved, so clear any existing notices first
				$this->updater_notices_clear();
				$this->check_license();
			}
		
			if ( ! $this->license_key > '' ) {
			
				if ( ! Sliced_Admin_Notices::has_notice( $this->slug . '_license_missing' ) ) {
					$this->updater_notices_clear( $this->slug . '_license_missing' );
					$notice_args = array(
						'class' => 'notice-warning',
						'content' => '<p>Sliced Invoices ' . $this->name . ': ' . sprintf( __( 'No license key found.  Don\'t forget to enter your license key on the <a href="%s">Licenses page</a>.', 'sliced-invoices-recurring' ), admin_url( 'admin.php?page=sliced_licenses' ) ) . '</p>',
						'dismissable' => true,
						'dismiss_transient' => '2592000',
					);
					Sliced_Admin_Notices::add_custom_notice( $this->slug . '_license_missing', $notice_args );
				}
				
			} elseif ( $this->license_status === 'valid' ) {
			
				$this->updater_notices_clear();
				
			} elseif ( $this->license_error === 'expired' || $this->license_status === 'expired' ) {
			
				if ( ! Sliced_Admin_Notices::has_notice( $this->slug . '_license_expired' ) ) {
					$this->updater_notices_clear( $this->slug . '_license_expired' );
					$notice_args = array(
						'class' => 'notice-error',
						'content' => '<p>' . sprintf( __( 'Your license for %s has expired.  Please <a href="%s">renew your license</a> to continue receiving updates and support.', 'sliced-invoices-recurring' ), 'Sliced Invoices ' . $this->name, 'https://slicedinvoices.com/checkout/?edd_license_key='.$this->license_key.'&download_id='.$this->download_id ) . '</p>',
						'dismissable' => true,
						'dismiss_transient' => '2592000',
					);
					Sliced_Admin_Notices::add_custom_notice( $this->slug . '_license_expired', $notice_args );
				}
				
			} elseif ( $this->license_error === 'revoked' ) {
			
				if ( ! Sliced_Admin_Notices::has_notice( $this->slug . '_license_disabled' ) ) {
					$this->updater_notices_clear( $this->slug . '_license_disabled' );
					$notice_args = array(
						'class' => 'notice-error',
						'content' => '<p>' . sprintf( __( 'Your license for %s has been disabled.  Please <a href="%s">contact us</a> for assistance.', 'sliced-invoices-recurring' ), 'Sliced Invoices ' . $this->name, 'https://slicedinvoices.com/contact-us/' ) . '</p>',
						'dismissable' => false,
					);
					Sliced_Admin_Notices::add_custom_notice( $this->slug . '_license_disabled', $notice_args );
				}
			
			} elseif ( $this->license_status === 'inactive' || $this->license_status === 'site_inactive' ) {
			
				if ( ! Sliced_Admin_Notices::has_notice( $this->slug . '_license_inactive' ) ) {
					$this->updater_notices_clear( $this->slug . '_license_inactive' );
					$notice_args = array(
						'class' => 'notice-warning',
						'content' => '<p>' . sprintf( __( 'Your license for %s is not active.  Be sure to activate your license key on the <a href="%s">Licenses page</a> to receive updates and support.', 'sliced-invoices-recurring' ), 'Sliced Invoices ' . $this->name, admin_url( 'admin.php?page=sliced_licenses' ) ) . '</p>',
						'dismissable' => true,
						'dismiss_transient' => '2592000',
					);
					Sliced_Admin_Notices::add_custom_notice( $this->slug . '_license_inactive', $notice_args );
				}
				
			}
			
		}
		
	}
	

}
