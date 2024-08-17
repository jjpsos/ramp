<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Sliced_Invoices_Client_Area_Admin
 */
class Sliced_Invoices_Client_Area_Admin {
	
	/** @var  object  Instance of this class */
	protected static $instance = null;
	
	/**
	 * Gets the instance of this class, or constructs one if it doesn't exist.
	 */
	public static function get_instance() {
		
		if ( self::$instance === null ) {
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
	 * @version 1.7.0
	 * @since   1.7.0
	 */
	public function __construct() {
		
		add_filter( 'plugin_action_links_sliced-invoices-client-area/sliced-invoices-client-area.php', array( $this, 'plugin_action_links' ) );
		add_filter( 'sliced_general_option_fields', array( $this, 'add_options_fields') );
		add_filter( 'sliced_invoice_option_fields', array( $this, 'add_invoice_options' ) );
		add_filter( 'sliced_quote_option_fields', array( $this, 'add_quote_options' ) );
		add_filter( 'sliced_translate_option_fields', array( $this, 'add_translate_options' ) );
		
	}
	
	
	/**
	 * Add the options field to the invoice section.
	 *
	 * @since 1.6.0
	 */
	public function add_invoice_options( $options ) {
		
		$options['fields'][] = array(
			'name'      => __( 'Client Area', 'sliced-invoices-client-area' ),
			'desc'      => '',
			'id'        => 'invoice_client_area_title',
			'type'      => 'title',
		);
		$options['fields'][] = array(
			'name'      => __( 'Hide Invoices', 'sliced-invoices-client-area' ),
			'desc'      => __( 'Yes, hide the Invoices section from the Client Area, I won\'t need this', 'sliced-invoices-client-area' ),
			'type'      => 'checkbox',
			'id'        => 'client_area_hide_invoices',
		);
		
		return $options;
	}
	
	
	/**
	 * Add the options for this gateway to the admin general settings
	 */
	public function add_options_fields( $options ) {
		
		$general = get_option( 'sliced_general' );
		
		// add fields to end of options array
		$options['fields'][] = array(
			'name'      => __( 'Client Area', 'sliced-invoices-client-area' ),
			'id'        => 'general_client_area_title',
			'type'      => 'title',
		);
		$options['fields'][] = array(
			'name'      => __( 'Client Area Page', 'sliced-invoices-client-area' ),
			'desc'      => __( 'Choose the page to use as the Client Area. Must have the [sliced-client-area] shortcode on this page.', 'sliced-invoices-client-area' ),
			'default'   => $general['client_area_id'],
			'type'      => 'select',
			'id'        => 'client_area_id',
			'options'   => $this->get_the_pages(),
		);
		$options['fields'][] = array(
			'name'      => __( 'Edit Profile Page', 'sliced-invoices-client-area' ),
			'desc'      => __( 'Choose the page to use as the Edit Profile page for your clients. Must have the [sliced-edit-profile] shortcode on this page.', 'sliced-invoices-client-area' ),
			'default'   => $general['edit_profile_id'],
			'type'      => 'select',
			'id'        => 'edit_profile_id',
			'options'   => $this->get_the_pages(),
		);
		$options['fields'][] = array(
			'name'              => __( 'Quote & Invoice Links', 'sliced-invoices-client-area' ),
			'desc'              => __( 'Choose which links to display on individual Quotes & Invoices', 'sliced-invoices-client-area' ),
			'id'                => 'invoice_links',
			'type'              => 'multicheck',
			'select_all_button' => false,
			'options'           => array(
				'client_area'       => __( 'My Account', 'sliced-invoices-client-area' ),
				'edit_profile'      => __( 'Edit Profile', 'sliced-invoices-client-area' ),
				'logout'            => __( 'Logout', 'sliced-invoices-client-area' ),
			),
		);
		$options['fields'][] = array(
			'name'              => __( 'Client Area Links', 'sliced-invoices-client-area' ),
			'desc'              => __( 'Choose which links to display within the Client Area and Edit Profile pages', 'sliced-invoices-client-area' ),
			'id'                => 'client_area_links',
			'type'              => 'multicheck',
			'select_all_button' => false,
			'options'           => array(
				'client_area'       => __( 'My Account', 'sliced-invoices-client-area' ),
				'edit_profile'      => __( 'Edit Profile', 'sliced-invoices-client-area' ),
				'logout'            => __( 'Logout', 'sliced-invoices-client-area' ),
			),
		);
		$options['fields'][] = array(
			'name'      => __( 'Allow Sliced Invoices to manage user login/logout', 'sliced-invoices-client-area' ),
			'desc'      => __( 'Uncheck this if you need to use other plugin(s) for user authentication', 'sliced-invoices-client-area' ),
			'id'        => 'client_area_enable_authentication',
			'type'      => 'checkbox',
		);
		// $options['fields'][] = array(
		//             'name'          => __( 'reCAPTCHA site key', 'sliced-invoices-client-area' ),
		//             'desc'          => __( '', 'sliced-invoices-client-area' ),
		//             'id'            => 'render_recaptcha_site_key_field',
		//             'type'          => 'text',
		// );
		// $options['fields'][] = array(
		//             'name'          => __( 'reCAPTCHA secret key', 'sliced-invoices-client-area' ),
		//             'desc'          => __( '', 'sliced-invoices-client-area' ),
		//             'id'            => 'render_recaptcha_secret_key_field',
		//             'type'          => 'text',
		// );
		$options['fields'][] = array(
			'name'      => __( 'Default sorting', 'sliced-invoices-client-area' ),
			'desc'      => __( 'Choose how quotes/invoices should be sorted in the Client Area.', 'sliced-invoices-client-area' ),
			'default'   => 'date_desc',
			'type'      => 'select',
			'id'        => 'client_area_default_sort',
			'options'   => array(
				'date_desc' => __( 'Newest to oldest', 'sliced-invoices-client-area' ),
				'date_asc'  => __( 'Oldest to newest', 'sliced-invoices-client-area' ),
			),
		);
		
		return $options;
	}
	
	
	/**
	 * Add the options field to the quotes section.
	 *
	 * @since 1.6.0
	 */
	public function add_quote_options( $options ) {
		
		$options['fields'][] = array(
			'name'      => __( 'Client Area', 'sliced-invoices-client-area' ),
			'desc'      => '',
			'id'        => 'quote_client_area_title',
			'type'      => 'title',
		);
		$options['fields'][] = array(
			'name'      => __( 'Hide Quotes', 'sliced-invoices-client-area' ),
			'desc'      => __( 'Yes, hide the Quotes section from the Client Area, I won\'t need this', 'sliced-invoices-client-area' ),
			'type'      => 'checkbox',
			'id'        => 'client_area_hide_quotes',
		);
		
		return $options;
	}
	
	
	/**
	 * Add the options for this extension to the translate settings.
	 * Deprecated. For compatibility with Easy Translate Extension < v2.0.0. Will be removed soon.
	 *
	 * @version 1.7.0
	 * @since   1.1.4
	 */
	public function add_translate_options( $options ) {
		
		if (
			class_exists( 'Sliced_Translate' )
			&& defined( 'SI_TRANSLATE_VERSION' )
			&& version_compare( SI_TRANSLATE_VERSION, '2.0.0', '<' )
		) {
			
			// add fields to end of options array
			$options['fields'][] = array(
				'name'       => __( 'Client Area', 'sliced-invoices-client-area' ),
				'id'         => 'translate_client_area_title',
				'type'       => 'title',
			);
			$options['fields'][] = array(
				'name'       => __( 'My Account', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-myaccount-label',
				'default'    => Sliced_Client_Area::$translate['client-myaccount-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Edit Profile', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-editprofile-label',
				'default'    => Sliced_Client_Area::$translate['client-editprofile-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Home', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-home-label',
				'default'    => Sliced_Client_Area::$translate['client-home-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Login', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-login-label',
				'default'    => Sliced_Client_Area::$translate['client-login-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Email or Username', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-login-user',
				'default'    => Sliced_Client_Area::$translate['client-login-user'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Password', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-login-password',
				'default'    => Sliced_Client_Area::$translate['client-login-password'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Remember Me', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-login-remember',
				'default'    => Sliced_Client_Area::$translate['client-login-remember'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Sign In', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-login-submit',
				'default'    => Sliced_Client_Area::$translate['client-login-submit'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Forgot your password?', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-login-forgot-password',
				'default'    => Sliced_Client_Area::$translate['client-login-forgot-password'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Check your email for a link to reset your password.', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-login-password-reset',
				'default'    => Sliced_Client_Area::$translate['client-login-password-reset'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Your password has been changed. You can sign in now.', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-login-password-changed',
				'default'    => Sliced_Client_Area::$translate['client-login-password-changed'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Logout', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-logout-label',
				'default'    => Sliced_Client_Area::$translate['client-logout-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'You have signed out. Would you like to sign in again?', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-logout-message',
				'default'    => Sliced_Client_Area::$translate['client-logout-message'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Account Snapshot', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-accountsnapshot-label',
				'default'    => Sliced_Client_Area::$translate['client-accountsnapshot-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Quotes Pending', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-quotespending-label',
				'default'    => Sliced_Client_Area::$translate['client-quotespending-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'awaiting response', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-awaitingresponse-label',
				'default'    => Sliced_Client_Area::$translate['client-awaitingresponse-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Total Outstanding', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-totaloutstanding-label',
				'default'    => Sliced_Client_Area::$translate['client-totaloutstanding-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'awaiting payment', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-awaitingpayment-label',
				'default'    => Sliced_Client_Area::$translate['client-awaitingpayment-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Currently no Quotes', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-currentlynoquotes-label',
				'default'    => Sliced_Client_Area::$translate['client-currentlynoquotes-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Currently no Invoices', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-currentlynoinvoices-label',
				'default'    => Sliced_Client_Area::$translate['client-currentlynoinvoices-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Date', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-date-label',
				'default'    => Sliced_Client_Area::$translate['client-date-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Due', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-due-label',
				'default'    => Sliced_Client_Area::$translate['client-due-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Status', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-status-label',
				'default'    => Sliced_Client_Area::$translate['client-status-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Number', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-number-label',
				'default'    => Sliced_Client_Area::$translate['client-number-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'View Quote', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-viewquote-label',
				'default'    => Sliced_Client_Area::$translate['client-viewquote-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'View Invoice', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-viewinvoice-label',
				'default'    => Sliced_Client_Area::$translate['client-viewinvoice-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Search', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-search-label',
				'default'    => Sliced_Client_Area::$translate['client-search-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Copy', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-copy-label',
				'default'    => Sliced_Client_Area::$translate['client-copy-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Previous', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-previous-label',
				'default'    => Sliced_Client_Area::$translate['client-previous-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			$options['fields'][] = array(
				'name'       => __( 'Next', 'sliced-invoices-client-area' ),
				'type'       => 'text',
				'id'         => 'client-next-label',
				'default'    => Sliced_Client_Area::$translate['client-next-label'],
				'attributes' => array(
					'class'      => 'i18n-multilingual regular-text',
				),
			);
			
		}
		
		return $options;
	}
	
	
	/**
	 * Get the sites pages for the dropdowns.
	 *
	 * @since   1.0.0
	 */
	public function get_the_pages() {
		
		$pages = get_pages(); 
		foreach ( $pages as $page ) {
			$the_pages[$page->ID] = $page->post_title;
		}
		
		return $the_pages;
	}
	
	
	/**
	 * Add links to settings page
	 *
	 * @since   1.0.1
	 */
	public function plugin_action_links( $links ) {
		
		$links[] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=sliced_invoices_settings&tab=general' ) ) .'">' . __( 'Settings', 'sliced-invoices-client-area' ) . '</a>';
		return $links;
		
	}
	
}
