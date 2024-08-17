<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class Sliced_Client_Area
 */
class Sliced_Client_Area {
	
	/** @var  object  Instance of this class */
	protected static $instance = null;
	
	private $plugin_name  = 'sliced-invoices-client-area';
	
	private $general;
	
	/** @var  array  Deprecated. For compatibility with Easy Translate Extension < v2.0.0. Will be removed soon. */
	public static $translate = array();
	
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
	 * @since   1.0.0
	 */
	public function __construct() {
		
		load_plugin_textdomain(
			'sliced-invoices-client-area',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
		
		if ( ! $this->validate_settings() ) {
			return;
		}
		
		$this->general = get_option( 'sliced_general' );
		
		$this->load_translations();
		
		Sliced_Invoices_Client_Area_Admin::get_instance();
		
		add_filter( 'sliced_client_data', array( $this, 'get_the_client_data' ) );
		add_filter( 'sliced_client_id', array( $this, 'get_the_client_id' ) );
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_client_area_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_client_area_scripts' ) );
		
		// Add the client area button to top bar of quote and invoice
		add_action( 'sliced_invoice_top_bar_right', array( $this, 'display_client_area_buttons' ) );
		add_action( 'sliced_quote_top_bar_right', array( $this, 'display_client_area_buttons' ) );
		
		// Add links to the top of client area
		add_action( 'sliced_before_client_area', array( $this, 'display_client_area_links' ) );
		
		add_shortcode( 'sliced-client-area', array( $this, 'client_area_shortcode' ) );
		add_shortcode( 'sliced-edit-profile', array( $this, 'edit_profile_shortcode' ) );
		
	}
	
	
	/**
	 * Client area shortcode.
	 *
	 * @since   1.0.0
	 */
	public function client_area_shortcode() {
		
		if ( is_user_logged_in() ) {
			
			$template = $this->locate_template('client-area.php');
			
			ob_start();
			if ( $template ) {
				require_once $template;
			} else {
				require_once plugin_dir_path( __FILE__ ) . 'public/templates/client-area.php';
			}
			
			wp_enqueue_style( 'sliced-client-area-datatables' );
			wp_enqueue_style( 'sliced-fontawesome' );
			wp_enqueue_script( 'sliced-client-area-datatables' );
			
			return ob_get_clean();
			
		} else {
			echo do_shortcode( '[sliced-login-form]' );
		}
	}
	
	
	/**
	 * Edit Profile shortcode.
	 *
	 * @since   1.0.0
	 */
	public function edit_profile_shortcode() {
		if ( is_user_logged_in() ) {
			$template = $this->locate_template('edit-profile.php');
			ob_start();
			if ( $template ) {
				require_once $template;
			} else {
				require_once plugin_dir_path( __FILE__ ) . 'public/templates/edit-profile.php';
			}
			return ob_get_clean();
		} else {
			echo do_shortcode( '[sliced-login-form]' );
		}
	}
	
	
	/**
	 * Enqueue or Register the stylesheets for the client area.
	 *
	 * @since   1.0.0
	 */
	public function enqueue_client_area_styles() {
		
		// enqueue as it may be used site-wide (login form, etc.)
		wp_enqueue_style( 
			'sliced-client-area',
			plugins_url( $this->plugin_name ) . '/public/css/client-area.css',
			array(),
			SLICED_INVOICES_CLIENT_AREA_VERSION,
			'all'
		);
		
		// register as these are only enqueued when needed
		wp_register_style( 
			'sliced-client-area-datatables', 
			plugins_url( $this->plugin_name ) . '/public/DataTables/datatables.min.css',
			array(),
			SLICED_INVOICES_CLIENT_AREA_VERSION,
			'all'
		);
		wp_register_style( 
			'sliced-fontawesome', 
			plugins_url( 'sliced-invoices' ) . '/public/css/font-awesome.min.css',
			array(),
			defined('SLICED_VERSION') ? SLICED_VERSION : false,
			'all'
		);
		
	}
	
	
	/**
	 * Enqueue or Register the scripts for the client area.
	 *
	 * @since   1.0.0
	 */
	public function enqueue_client_area_scripts() {
		
		// register as it's only enqueued when needed
		wp_register_script(
			'sliced-client-area-datatables',
			plugins_url( $this->plugin_name ) . '/public/DataTables/datatables.min.js',
			'jquery',
			SLICED_INVOICES_CLIENT_AREA_VERSION,
			false
		);
		
	}
	
	
	/**
	 * Load translations or use defaults.
	 * Deprecated. For compatibility with Easy Translate Extension < v2.0.0. Will be removed soon.
	 *
	 * @since 1.7.0
	 */
	public function load_translations() {
		Sliced_Client_Area::$translate = array(
			'client-login-user'                => __( 'Email or Username', 'sliced-invoices-client-area' ),
			'client-login-password'            => __( 'Password', 'sliced-invoices-client-area' ),
			'client-login-remember'            => __( 'Remember Me', 'sliced-invoices-client-area' ),
			'client-login-submit'              => __( 'Sign In', 'sliced-invoices-client-area' ),
			'client-login-forgot-password'     => __( 'Forgot your password?', 'sliced-invoices-client-area' ),
			'client-login-password-reset'      => __( 'Check your email for a link to reset your password.', 'sliced-invoices-client-area' ),
			'client-login-password-changed'    => __( 'Your password has been changed. You can sign in now.', 'sliced-invoices-client-area' ),
			'client-logout-message'            => __( 'You have signed out. Would you like to sign in again?', 'sliced-invoices-client-area' ),
			'client-myaccount-label'           => __( 'My Account', 'sliced-invoices-client-area' ),
			'client-editprofile-label'         => __( 'Edit Profile', 'sliced-invoices-client-area' ),
			'client-home-label'                => __( 'Home', 'sliced-invoices-client-area' ),
			'client-login-label'               => __( 'Login', 'sliced-invoices-client-area' ),
			'client-logout-label'              => __( 'Logout', 'sliced-invoices-client-area' ),
			'client-accountsnapshot-label'     => __( 'Account Snapshot', 'sliced-invoices-client-area' ),
			'client-quotespending-label'       => __( 'Quotes Pending', 'sliced-invoices-client-area' ),
			'client-awaitingresponse-label'    => __( 'awaiting response', 'sliced-invoices-client-area' ),
			'client-totaloutstanding-label'    => __( 'Total Outstanding', 'sliced-invoices-client-area' ),
			'client-awaitingpayment-label'     => __( 'awaiting payment', 'sliced-invoices-client-area' ),
			'client-currentlynoquotes-label'   => __( 'Currently no Quotes', 'sliced-invoices-client-area' ),
			'client-currentlynoinvoices-label' => __( 'Currently no Invoices', 'sliced-invoices-client-area' ),
			'client-date-label'                => __( 'Date', 'sliced-invoices-client-area' ),
			'client-due-label'                 => __( 'Due', 'sliced-invoices-client-area' ),
			'client-status-label'              => __( 'Status', 'sliced-invoices-client-area' ),
			'client-number-label'              => __( 'Number', 'sliced-invoices-client-area' ),
			'client-viewquote-label'           => __( 'View Quote', 'sliced-invoices-client-area' ),
			'client-viewinvoice-label'         => __( 'View Invoice', 'sliced-invoices-client-area' ),
			'client-search-label'              => __( 'Search', 'sliced-invoices-client-area' ),
			'client-copy-label'                => __( 'Copy', 'sliced-invoices-client-area' ),
			'client-previous-label'            => __( 'Previous', 'sliced-invoices-client-area' ),
			'client-next-label'                => __( 'Next', 'sliced-invoices-client-area' ),
		);
		if (
			class_exists( 'Sliced_Translate' )
			&& defined( 'SI_TRANSLATE_VERSION' )
			&& version_compare( SI_TRANSLATE_VERSION, '2.0.0', '<' )
		) {
			$translate = get_option( 'sliced_translate' );
			foreach ( Sliced_Client_Area::$translate as $key => $value ) {
				if ( isset( $translate[ $key ] ) ) Sliced_Client_Area::$translate[ $key ] = $translate[ $key ];
			}
		}
	}
	
	
	/**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * @since   1.4.0
	 */
	private function locate_template( $template_name ) {
		
		// No file found yet
		$located = false;
		
		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );
		
		// Check child theme first
		if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'sliced/' . $template_name ) ) {
			$located = trailingslashit( get_stylesheet_directory() ) . 'sliced/' . $template_name;
		
		// Check parent theme next
		} elseif ( file_exists( trailingslashit( get_template_directory() ) . 'sliced/' . $template_name ) ) {
			$located = trailingslashit( get_template_directory() ) . 'sliced/' . $template_name;
			
		} elseif ( file_exists( plugin_dir_path( dirname( __FILE__ ) ) . 'public/templates/' . $template_name ) ) {
			$located = plugin_dir_path( dirname( __FILE__ ) ) . 'public/templates/' . $template_name;
			
		} elseif ( file_exists( plugin_dir_path( __FILE__ ) . 'public/templates/' . $template_name ) ) {
			$located = plugin_dir_path( __FILE__ ) . 'public/templates/' . $template_name;
			
		}
		
		$located = apply_filters( 'sliced_client_area_locate_templates', $located, $template_name );
		
		return $located;
	}
	
	
	/**
	 * Display the client area nav buttons on quotes and invoices.
	 *
	 * @since   1.0.0
	 */
	public function display_client_area_buttons() {
		
		if ( isset( $this->general['invoice_links'] ) ) {
			
			if ( is_user_logged_in() ) :
				
				if ( in_array( 'client_area', $this->general['invoice_links'] ) ) { ?>
					<a href="<?php echo esc_url( apply_filters( 'sliced_client_area_myaccount_link', sliced_client_area_permalink() ) ); ?>" class="client-area-link btn btn-primary btn-sm"><?php echo Sliced_Client_Area::$translate['client-myaccount-label']; ?></a>
				<?php } ?>
				
				<?php if ( in_array( 'edit_profile', $this->general['invoice_links'] ) ) { ?>
					<a href="<?php echo esc_url( apply_filters( 'sliced_client_area_editprofile_link', sliced_edit_profile_permalink() ) ); ?>" class="edit-profile-link btn btn-primary btn-sm" ><?php echo Sliced_Client_Area::$translate['client-editprofile-label']; ?></a>
				<?php } ?>
				
				<?php if ( in_array( 'logout', $this->general['invoice_links'] ) ) { ?>
					<a href="<?php echo esc_url( wp_logout_url( apply_filters( 'sliced_client_area_logout_link', sliced_client_area_permalink() ) ) ); ?>" class="logout btn btn-primary btn-sm"><?php echo Sliced_Client_Area::$translate['client-logout-label']; ?></a>
				<?php } ?>
				
			<?php else : ?>
				
				<a href="<?php echo esc_url( apply_filters( 'sliced_client_area_home_link', home_url() ) ); ?>" class="home btn btn-primary btn-sm"><?php echo Sliced_Client_Area::$translate['client-home-label']; ?></a>
				<a href="<?php echo esc_url( apply_filters( 'sliced_client_area_login_link', sliced_client_area_permalink() ) ); ?>" class="login btn btn-primary btn-sm"><?php echo Sliced_Client_Area::$translate['client-login-label']; ?></a>
				
			<?php endif;
			
		}
		
	}
	
	
	/**
	 * Display the client area links within the client area.
	 *
	 * @since   1.0.1
	 */
	public function display_client_area_links() {
		
		if ( isset( $this->general['client_area_links'] ) ) {
			
			if ( is_user_logged_in() ) : ?>
			
			<div class="sliced-nav-links">
				
				<?php if ( in_array( 'client_area', $this->general['client_area_links'] ) ) { ?>
					<a href="<?php echo esc_url( apply_filters( 'sliced_client_area_myaccount_link', sliced_client_area_permalink() ) ); ?>" class="client-area-link"><?php echo Sliced_Client_Area::$translate['client-myaccount-label']; ?></a>
				<?php } ?>
				
				<?php if ( in_array( 'edit_profile', $this->general['client_area_links'] ) ) { ?>
					<a href="<?php echo esc_url( apply_filters( 'sliced_client_area_editprofile_link', sliced_edit_profile_permalink() ) ); ?>" class="edit-profile-link" ><?php echo Sliced_Client_Area::$translate['client-editprofile-label']; ?></a>
				<?php } ?>
				
				<?php if ( in_array( 'logout', $this->general['client_area_links'] ) ) { ?>
					<a href="<?php echo esc_url( wp_logout_url( apply_filters( 'sliced_client_area_logout_link', sliced_client_area_permalink() ) ) ); ?>" class="logout"><?php echo Sliced_Client_Area::$translate['client-logout-label']; ?></a>
				<?php } ?>
				
			</div>
			
			<?php endif;
			
		}
		
	}
	
	
	/**
	 * Check we are in the client area somewhere
	 *
	 * @since   1.0.0
	 */
	private function is_client_area() {
		
		$client_area    = $this->general['client_area_id'];
		$edit_profile   = $this->general['edit_profile_id'];
		if( is_page( array( $client_area, $edit_profile ) ) )
			return true;
		
		return false;
		
	}
	
	
	/**
	 * Get the client data
	 *
	 * @since   1.0.0
	 */
	public function get_the_client_data( $client ) {
		
		if ( ! $client && ! is_admin() && ! is_singular( 'sliced_invoice' ) && ! is_singular( 'sliced_quote' ) ) {
			
			// allows admin to check users page using GET request and passing user id
			if( current_user_can( 'manage_options' ) && isset( $_GET['client_id'] ) ) {
				$client = get_userdata( (int)$_GET['client_id'] );
			} else {
				$client = wp_get_current_user();
			}
		}
		
		return $client;
	}
	
	
	/**
	 * Get the client id
	 *
	 * @since   1.0.0
	 */
	public function get_the_client_id( $id ) {
		
		if ( ! $id && ! is_admin() && ! is_singular( 'sliced_invoice' ) && ! is_singular( 'sliced_quote' ) ) {
			
			// allows admin to check users page using GET request and passing user id
			if( current_user_can( 'manage_options' ) && isset( $_GET['client_id'] ) ) {
				$client = get_userdata( (int)$_GET['client_id'] );
				$id = (int)$_GET['client_id'];
			} else {
				$client = wp_get_current_user();
				$id = $client->ID;
			}
		}
		
		return $id;
	}
	
	
	/**
	 * Get the invoices & quotes for the user.
	 *
	 * @since   1.0.0
	 */
	public function user_items_query( $type ) {
		
		$args = array(
			'post_type'      => 'sliced_' . $type,
			'fields'         => 'ids',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'meta_query'     => array(
				array(
					'key'     => '_sliced_client',
					'value'   => (int) sliced_get_client_id(),
					'compare' => '=',
				),
			),
			'tax_query' => array(
				array(
					'taxonomy' => $type . '_status',
					'field'    => 'slug',
					'terms'    => 'draft',
					'operator' => 'NOT IN',
				),
			),
		);
		$ids = array();
		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$ids[] = get_the_ID();
			}
		} 
		wp_reset_postdata();
		return $ids;
	}
	
	
	/**
	 * Output requirements not met notice.
	 *
	 * @since   1.6.3
	 */
	public function requirements_not_met_notice() {
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'Sliced Invoices Client Area extension cannot find the required <a href="%s">Sliced Invoices plugin</a>. Please make sure the core Sliced Invoices plugin is <a href="%s">installed and activated</a>.', 'sliced-invoices-client-area' ), 'https://wordpress.org/plugins/sliced-invoices/', admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}
	
	
	/**
	 * Validate settings, make sure all requirements met, etc.
	 *
	 * @since   1.6.3
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
