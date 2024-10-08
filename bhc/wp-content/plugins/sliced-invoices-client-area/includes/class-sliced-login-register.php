<?php

class Personalize_Login_Plugin {

	/**
	 * Initializes the plugin.
	 *
	 * To keep the initialization fast, only add filter and action
	 * hooks in the constructor.
	 */
	public function __construct() {

		// Redirects
		add_action( 'login_form_login', array( $this, 'redirect_to_custom_login' ) );
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate' ), 101, 3 );
		add_filter( 'login_redirect', array( $this, 'redirect_after_login' ), 10, 3 );
		add_action( 'wp_logout', array( $this, 'redirect_after_logout' ) );

		add_action( 'login_form_register', array( $this, 'redirect_to_custom_register' ) );
		add_action( 'login_form_lostpassword', array( $this, 'redirect_to_custom_lostpassword' ) );
		add_action( 'login_form_rp', array( $this, 'redirect_to_custom_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'redirect_to_custom_password_reset' ) );

		// Handlers for form posting actions
		add_action( 'login_form_register', array( $this, 'do_register_user' ) );
		add_action( 'login_form_lostpassword', array( $this, 'do_password_lost' ) );
		add_action( 'login_form_rp', array( $this, 'do_password_reset' ) );
		add_action( 'login_form_resetpass', array( $this, 'do_password_reset' ) );

		// Other customizations
		add_filter( 'retrieve_password_message', array( $this, 'replace_retrieve_password_message' ), 10, 4 );

		// Setup
		add_action( 'wp_head', array( $this, 'add_captcha_js_to_header' ) );
		add_action( 'wp_print_footer_scripts', array( $this, 'add_captcha_js_to_footer' ) );

		// Shortcodes
		//add_shortcode( 'sliced-client-area', array( $this, 'render_login_form' ) );
		add_shortcode( 'sliced-login-form', array( $this, 'render_login_form' ) );
		add_shortcode( 'sliced-client-register', array( $this, 'render_register_form' ) );
		add_shortcode( 'sliced-lost-password', array( $this, 'render_password_lost_form' ) );
		add_shortcode( 'sliced-password-reset', array( $this, 'render_password_reset_form' ) );
	}
	
	/**
	 * Get client area url based on page set in settings
	 *
	 * @since 1.1.6
	 */
	public function get_client_area_url() {
		$general = get_option( 'sliced_general' );
		$login_url = ( isset( $general['client_area_id'] ) ? get_page_link( $general['client_area_id'] ) : home_url( 'client-area' ) );
		return $login_url;
	}
	
	/**
	 * Get client registration url (not currently available in settings, may be added in future)
	 *
	 * @since 1.1.6
	 */
	public function get_client_register_url() {
		$id = $this->the_slug_exists( 'client-register' );
		$url = ( $id ? get_page_link( $id ) : home_url( 'client-register' ) );
		return $url;
	}
	
	/**
	 * Get lost password url (not currently available in settings, may be added in future)
	 *
	 * @since 1.1.6
	 */
	public function get_lost_password_url() {
		$id = $this->the_slug_exists( 'lost-password' );
		$url = ( $id ? get_page_link( $id ) : home_url( 'lost-password' ) );
		return $url;
	}
	
	/**
	 * Get password reset url (not currently available in settings, may be added in future)
	 *
	 * @since 1.1.6
	 */
	public function get_password_reset_url() {
		$id = $this->the_slug_exists( 'password-reset' );
		$url = ( $id ? get_page_link( $id ) : home_url( 'password-reset' ) );
		return $url;
	}
	
	/**
	 * Helper to check if page exists via slug, return ID if found
	 *
	 * @since 1.1.6
	 */
	public function the_slug_exists( $post_name ) {
		global $wpdb;
		$post_id = $wpdb->get_row("SELECT ID FROM $wpdb->posts WHERE post_name = '" . $post_name . "'", 'ARRAY_A');
		if( $post_id ) {
			return (int) $post_id['ID'];
		} else {
			return false;
		}
	}
	
	/**
	 * Helper to check if Sliced Invoices is allowed to handle login/logout
	 *
	 * @since 1.1.8
	 */
	public function authentication_enabled() {
		$general = get_option('sliced_general');
		if ( isset( $general['client_area_enable_authentication'] ) && $general['client_area_enable_authentication'] == 'on' ) {
			return true;
		} else {
			return false;
		}
	}


	//
	// REDIRECT FUNCTIONS
	//

	/**
	 * Redirect the user to the custom login page instead of wp-login.php.
	 */
	public function redirect_to_custom_login() {
		if ( $_SERVER['REQUEST_METHOD'] == 'GET' && $this->authentication_enabled() ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
				exit;
			}
			
			$login_url = $this->get_client_area_url();

			// The rest are redirected to the login page
			if ( ! empty( $_REQUEST['redirect_to'] ) ) {
				$login_url = add_query_arg( 'redirect_to', $_REQUEST['redirect_to'], $login_url );
			}

			if ( ! empty( $_REQUEST['checkemail'] ) ) {
				$login_url = add_query_arg( 'checkemail', $_REQUEST['checkemail'], $login_url );
			}

			wp_redirect( $login_url );
			exit;
		}
	}

	/**
	 * Redirect the user after authentication if there were any errors.
	 *
	 * @param Wp_User|Wp_Error  $user       The signed in user, or the errors that have occurred during login.
	 * @param string            $username   The user name used to log in.
	 * @param string            $password   The password used to log in.
	 *
	 * @return Wp_User|Wp_Error The logged in user, or error information if there were errors.
	 */
	public function maybe_redirect_at_authenticate( $user, $username, $password ) {
		// Check if the earlier authenticate filter (most likely,
		// the default WordPress authentication) functions have found errors
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && $this->authentication_enabled() ) {
			if ( is_wp_error( $user ) ) {
				$error_codes = join( ',', $user->get_error_codes() );

				$login_url = $this->get_client_area_url();
				$login_url = add_query_arg( 'login', $error_codes, $login_url );

				wp_redirect( $login_url );
				exit;
			}
		}

		return $user;
	}

	/**
	 * Returns the URL to which the user should be redirected after the (successful) login.
	 *
	 * @param string           $redirect_to           The redirect destination URL.
	 * @param string           $requested_redirect_to The requested redirect destination URL passed as a parameter.
	 * @param WP_User|WP_Error $user                  WP_User object if login was successful, WP_Error object otherwise.
	 *
	 * @return string Redirect URL
	 */
	public function redirect_after_login( $redirect_to, $requested_redirect_to, $user ) {
		if ( ! $this->authentication_enabled() && $redirect_to > '' ) {
			return $redirect_to;
		}
		
		$redirect_url = home_url();

		if ( ! isset( $user->ID ) ) {
			return $redirect_url;
		}

		if ( user_can( $user, 'manage_options' ) ) {
			// Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
			if ( $requested_redirect_to == '' ) {
				$redirect_url = admin_url();
			} else {
				$redirect_url = $redirect_to;
			}
		} else {
			// Non-admin users always go to their account page after login
			$redirect_url = $this->get_client_area_url();
		}

		return wp_validate_redirect( $redirect_url, home_url() );
	}

	/**
	 * Redirect to custom login page after the user has been logged out.
	 */
	public function redirect_after_logout() {
		if ( ! $this->authentication_enabled() ) {
			return;
		}
		$redirect_url = add_query_arg( 'logged_out', 'true', $this->get_client_area_url() );
		wp_redirect( $redirect_url );
		exit;
	}

	/**
	 * Redirects the user to the custom registration page instead
	 * of wp-login.php?action=register.
	 */
	public function redirect_to_custom_register() {
		if ( ! $this->the_slug_exists( 'client-register' ) || ! $this->authentication_enabled() ) { 
			return;	// bypass
		}
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
			} else {
				wp_redirect( $this->get_client_register_url() );
			}
			exit;
		}
	}

	/**
	 * Redirects the user to the custom "Forgot your password?" page instead of
	 * wp-login.php?action=lostpassword.
	 */
	public function redirect_to_custom_lostpassword() {
		if ( ! $this->the_slug_exists( 'lost-password' ) || ! $this->authentication_enabled() ) { 
			return;	// bypass
		}
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if ( is_user_logged_in() ) {
				$this->redirect_logged_in_user();
				exit;
			}

			wp_redirect( $this->get_lost_password_url() );
			exit;
		}
	}

	/**
	 * Redirects to the custom password reset page, or the login page
	 * if there are errors.
	 */
	public function redirect_to_custom_password_reset() {
		if ( ! $this->the_slug_exists( 'password-reset' ) || ! $this->authentication_enabled() ) { 
			return;	// bypass
		}
		if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			// Verify key / login combo
			$user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( add_query_arg( 'login', 'expiredkey', $this->get_client_area_url() ) );
				} else {
					wp_redirect( add_query_arg( 'login', 'invalidkey', $this->get_client_area_url() ) );
				}
				exit;
			}

			$redirect_url = $this->get_password_reset_url();
			$redirect_url = add_query_arg( 'login', esc_attr( $_REQUEST['login'] ), $redirect_url );
			$redirect_url = add_query_arg( 'key', esc_attr( $_REQUEST['key'] ), $redirect_url );

			wp_redirect( $redirect_url );
			exit;
		}
	}


	//
	// FORM RENDERING SHORTCODES
	//

	/**
	 * A shortcode for rendering the login form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
     * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_login_form( $attributes, $content = null ) {
		// Parse shortcode attributes

		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return $this->get_template_html( 'client-area', $attributes );
		}

		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// request parameter, use it.
		$attributes['redirect'] = '';
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$attributes['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $attributes['redirect'] );
		}

		// Error messages
		$errors = array();
		if ( isset( $_REQUEST['login'] ) ) {
			$error_codes = explode( ',', $_REQUEST['login'] );

			foreach ( $error_codes as $code ) {
				$errors []= $this->get_error_message( $code );
			}
		}
		$attributes['errors'] = $errors;

		// Check if user just logged out
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && $_REQUEST['logged_out'] == true;

		// Check if the user just registered
		$attributes['registered'] = isset( $_REQUEST['registered'] );

		// Check if the user just requested a new password
		$attributes['lost_password_sent'] = isset( $_REQUEST['checkemail'] ) && $_REQUEST['checkemail'] == 'confirm';

		// Check if user just updated password
		$attributes['password_updated'] = isset( $_REQUEST['password'] ) && $_REQUEST['password'] == 'changed';

		// Render the login form using an external template
		return $this->get_template_html( 'login_form', $attributes );
	}

	/**
	 * A shortcode for rendering the new user registration form.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_register_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'sliced-invoices-client-area' );
		} elseif ( ! get_option( 'users_can_register' ) ) {
			return __( 'Registering new users is currently not allowed.', 'sliced-invoices-client-area' );
		} else {
			// Retrieve possible errors from request parameters
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['register-errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['register-errors'] );

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] []= $this->get_error_message( $error_code );
				}
			}

			// Retrieve recaptcha key
			$general = get_option( 'sliced_general');
			$attributes['recaptcha_site_key'] = $general['render_recaptcha_site_key_field'];
			
			return $this->get_template_html( 'register_form', $attributes );
		}
	}

	/**
	 * A shortcode for rendering the form used to initiate the password reset.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_lost_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'sliced-invoices-client-area' );
		} else {
			// Retrieve possible errors from request parameters
			$attributes['errors'] = array();
			if ( isset( $_REQUEST['errors'] ) ) {
				$error_codes = explode( ',', $_REQUEST['errors'] );

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'] []= $this->get_error_message( $error_code );
				}
			}

			return $this->get_template_html( 'password_lost_form', $attributes );
		}
	}

	/**
	 * A shortcode for rendering the form used to reset a user's password.
	 *
	 * @param  array   $attributes  Shortcode attributes.
	 * @param  string  $content     The text content for shortcode. Not used.
	 *
	 * @return string  The shortcode output
	 */
	public function render_password_reset_form( $attributes, $content = null ) {
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );

		if ( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'sliced-invoices-client-area' );
		} else {
			if ( isset( $_REQUEST['login'] ) && isset( $_REQUEST['key'] ) ) {
				$attributes['login'] = $_REQUEST['login'];
				$attributes['key'] = $_REQUEST['key'];

				// Error messages
				$errors = array();
				if ( isset( $_REQUEST['error'] ) ) {
					$error_codes = explode( ',', $_REQUEST['error'] );

					foreach ( $error_codes as $code ) {
						$errors []= $this->get_error_message( $code );
					}
				}
				$attributes['errors'] = $errors;

				return $this->get_template_html( 'password_reset_form', $attributes );
			} else {
				return __( 'Invalid password reset link.', 'sliced-invoices-client-area' );
			}
		}
	}

	/**
	 * An action function used to include the reCAPTCHA JavaScript file
	 * at the end of the page.
	 */
	public function add_captcha_js_to_footer() {
		if ( $this->authentication_enabled() ) {
			echo "<script src='https://www.google.com/recaptcha/api.js?hl=en'></script>";
		}
	}
	
	/**
	 * Include login scripts in header
	 *
	 * @since 1.3.0
	 */
	public function add_captcha_js_to_header() {
		if ( $this->authentication_enabled() ) {
			do_action( 'login_head' );
		}
	}

	/**
	 * Renders the contents of the given template to a string and returns it.
	 *
	 * @param string $template_name The name of the template to render (without .php)
	 * @param array  $attributes    The PHP variables for the template
	 *
	 * @return string               The contents of the template.
	 */
	private function get_template_html( $template_name, $attributes = null ) {
		if ( ! $attributes ) {
			$attributes = array();
		}

		// No file found yet
		$located = false;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );
		$template_name = $template_name . '.php';
		// Check child theme first
		if ( file_exists( trailingslashit( get_stylesheet_directory() ) . 'sliced/' . $template_name ) ) {
			$located = trailingslashit( get_stylesheet_directory() ) . 'sliced/' . $template_name;

		// Check parent theme next
		} elseif ( file_exists( trailingslashit( get_template_directory() ) . 'sliced/' . $template_name ) ) {
			$located = trailingslashit( get_template_directory() ) . 'sliced/' . $template_name;

		} elseif ( file_exists( plugin_dir_path( dirname( __FILE__ ) )  . 'public/templates/' .  $template_name ) ) {
			$located = plugin_dir_path( dirname( __FILE__ ) )  . 'public/templates/' .  $template_name;

		} elseif ( file_exists( plugin_dir_path( __FILE__ ) . 'public/templates/' .  $template_name ) ) {
			$located = plugin_dir_path( __FILE__ ) . 'public/templates/' .  $template_name;

		}
		ob_start();
		
		require( apply_filters( 'sliced_locate_new_templates', $located, $template_name ) );
		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}


	/**
	 * Handles the registration of a new user.
	 *
	 * Used through the action hook "login_form_register" activated on wp-login.php
	 * when accessed through the registration action.
	 */
	public function do_register_user() {
		if ( ! $this->the_slug_exists( 'client-register' ) || ! $this->authentication_enabled() ) { 
			return;	// bypass
		}
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$redirect_url = $this->get_client_register_url();

			if ( ! get_option( 'users_can_register' ) ) {
				// Registration closed, display error
				$redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
			} elseif ( ! $this->verify_recaptcha() ) {
				// Recaptcha check failed, display error
				$redirect_url = add_query_arg( 'register-errors', 'captcha', $redirect_url );
			} else {
				$email = $_POST['email'];
				$first_name = sanitize_text_field( $_POST['first_name'] );
				$last_name = sanitize_text_field( $_POST['last_name'] );

				$result = $this->register_user( $email, $first_name, $last_name );

				if ( is_wp_error( $result ) ) {
					// Parse errors into a string and append as parameter to redirect
					$errors = join( ',', $result->get_error_codes() );
					$redirect_url = add_query_arg( 'register-errors', $errors, $redirect_url );
				} else {
					// Success, redirect to login page.
					$redirect_url = $this->get_client_area_url();
					$redirect_url = add_query_arg( 'registered', $email, $redirect_url );
				}
			}

			wp_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Initiates password reset.
	 */
	public function do_password_lost() {
		if ( ! $this->the_slug_exists( 'lost-password' ) || ! $this->authentication_enabled() ) { 
			return;	// bypass
		}
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$errors = retrieve_password();
			if ( is_wp_error( $errors ) ) {
				// Errors found
				$redirect_url = $this->get_lost_password_url();
				$redirect_url = add_query_arg( 'errors', join( ',', $errors->get_error_codes() ), $redirect_url );
			} else {
				// Email sent
				$redirect_url = $this->get_client_area_url();
				$redirect_url = add_query_arg( 'checkemail', 'confirm', $redirect_url );
				if ( ! empty( $_REQUEST['redirect_to'] ) ) {
					$redirect_url = $_REQUEST['redirect_to'];
				}
			}

			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Resets the user's password if the password reset form was submitted.
	 */
	public function do_password_reset() {
		if ( ! $this->the_slug_exists( 'password-reset' ) || ! $this->authentication_enabled() ) { 
			return;	// bypass
		}
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			$rp_key = $_REQUEST['rp_key'];
			$rp_login = $_REQUEST['rp_login'];

			$user = check_password_reset_key( $rp_key, $rp_login );

			if ( ! $user || is_wp_error( $user ) ) {
				if ( $user && $user->get_error_code() === 'expired_key' ) {
					wp_redirect( add_query_arg( 'login', 'expiredkey', $this->get_client_area_url() ) );
				} else {
					wp_redirect( add_query_arg( 'login', 'invalidkey', $this->get_client_area_url() ) );
				}
				exit;
			}

			if ( isset( $_POST['pass1'] ) ) {
				if ( $_POST['pass1'] != $_POST['pass2'] ) {
					// Passwords don't match
					$redirect_url = $this->get_password_reset_url();

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_mismatch', $redirect_url );

					wp_redirect( $redirect_url );
					exit;
				}

				if ( empty( $_POST['pass1'] ) ) {
					// Password is empty
					$redirect_url = $this->get_password_reset_url();

					$redirect_url = add_query_arg( 'key', $rp_key, $redirect_url );
					$redirect_url = add_query_arg( 'login', $rp_login, $redirect_url );
					$redirect_url = add_query_arg( 'error', 'password_reset_empty', $redirect_url );

					wp_redirect( $redirect_url );
					exit;

				}

				// Parameter checks OK, reset password
				reset_password( $user, $_POST['pass1'] );
				wp_redirect( add_query_arg( 'password', 'changed', $this->get_client_area_url() ) );
			} else {
				echo "Invalid request.";
			}

			exit;
		}
	}


	//
	// OTHER CUSTOMIZATIONS
	//

	/**
	 * Returns the message body for the password reset mail.
	 * Called through the retrieve_password_message filter.
	 *
	 * @param string  $message    Default mail message.
	 * @param string  $key        The activation key.
	 * @param string  $user_login The username for the user.
	 * @param WP_User $user_data  WP_User object.
	 *
	 * @return string   The mail message to send.
	 */
	public function replace_retrieve_password_message( $message, $key, $user_login, $user_data ) {
		if ( ! $this->authentication_enabled() ) {
			return $message;
		}
			
		// Create new message
		$msg  = __( 'Hello!', 'sliced-invoices-client-area' ) . "\r\n\r\n";
		$msg .= sprintf( __( 'You asked us to reset your password for your account using the email address %s.', 'sliced-invoices-client-area' ), $user_login ) . "\r\n\r\n";
		$msg .= __( "If this was a mistake, or you didn't ask for a password reset, just ignore this email and nothing will happen.", 'sliced-invoices-client-area' ) . "\r\n\r\n";
		$msg .= __( 'To reset your password, visit the following address:', 'sliced-invoices-client-area' ) . "\r\n\r\n";
		$msg .= site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' ) . "\r\n\r\n";
		$msg .= __( 'Thanks!', 'sliced-invoices-client-area' ) . "\r\n";

		return $msg;
	}


	//
	// HELPER FUNCTIONS
	//

	/**
	 * Validates and then completes the new user signup process if all went well.
	 *
	 * @param string $email         The new user's email address
	 * @param string $first_name    The new user's first name
	 * @param string $last_name     The new user's last name
	 *
	 * @return int|WP_Error         The id of the user that was created, or error if failed.
	 */
	private function register_user( $email, $first_name, $last_name ) {
		$errors = new WP_Error();

		// Email address is used as both username and email. It is also the only
		// parameter we need to validate
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', $this->get_error_message( 'email' ) );
			return $errors;
		}

		if ( username_exists( $email ) || email_exists( $email ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists') );
			return $errors;
		}

		// Generate the password so that the subscriber will have to check email...
		$password = wp_generate_password( 12, false );

		$user_data = array(
			'user_login'    => $email,
			'user_email'    => $email,
			'user_pass'     => $password,
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'nickname'      => $first_name,
		);

		$user_id = wp_insert_user( $user_data );
		wp_new_user_notification( $user_id, $password );

		return $user_id;
	}

	/**
	 * Checks that the reCAPTCHA parameter sent with the registration
	 * request is valid.
	 *
	 * @return bool True if the CAPTCHA is OK, otherwise false.
	 */
	private function verify_recaptcha() {
		// This field is set by the recaptcha widget if check is successful
		if ( isset ( $_POST['g-recaptcha-response'] ) ) {
			$captcha_response = $_POST['g-recaptcha-response'];
		} else {
			return false;
		}

		$general = get_option( 'sliced_general');
		$secret = $general['render_recaptcha_secret_key_field'];
		// Verify the captcha response from Google
		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret' => $secret,
					'response' => $captcha_response
				)
			)
		);

		$success = false;
		if ( $response && is_array( $response ) ) {
			$decoded_response = json_decode( $response['body'] );
			$success = $decoded_response->success;
		}

		return $success;
	}

	/**
	 * Redirects the user to the correct page depending on whether he / she
	 * is an admin or not.
	 *
	 * @param string $redirect_to   An optional redirect_to URL for admin users
	 */
	private function redirect_logged_in_user( $redirect_to = null ) {
		$user = wp_get_current_user();
		if ( user_can( $user, 'manage_options' ) ) {
			if ( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
			} else {
				wp_redirect( admin_url() );
			}
		} else {
			wp_redirect( $this->get_client_area_url() );
		}
	}

	/**
	 * Finds and returns a matching error message for the given error code.
	 *
	 * @param string $error_code    The error code to look up.
	 *
	 * @return string               An error message.
	 */
	private function get_error_message( $error_code ) {
	
		switch ( $error_code ) {
			
			// Login errors
			case 'empty_username':
				return __( 'You do have an email address, right?', 'sliced-invoices-client-area' );

			case 'empty_password':
				return __( 'You need to enter a password to login.', 'sliced-invoices-client-area' );

			case 'invalid_username':
				return __(
					"We don't have any users with that email address. Maybe you used a different one when signing up?",
					'sliced-invoices-client-area'
				);
			case 'incorrect_password':
				$err = __(
					"The password you entered wasn't quite right. <a href='%s'>Did you forget your password</a>?",
					'sliced-invoices-client-area'
				);
				return sprintf( $err, wp_lostpassword_url() );

			// Registration errors
			case 'email':
				return __( 'The email address you entered is not valid.', 'sliced-invoices-client-area' );

			case 'email_exists':
				return __( 'An account exists with this email address.', 'sliced-invoices-client-area' );

			case 'closed':
				return __( 'Registering new users is currently not allowed.', 'sliced-invoices-client-area' );

			case 'captcha':
				return __( 'The Google reCAPTCHA check failed. Are you a robot?', 'sliced-invoices-client-area' );

			// Lost password
			case 'empty_username':
				return __( 'You need to enter your email address to continue.', 'sliced-invoices-client-area' );

			case 'invalid_email':
			case 'invalidcombo':
				return __( 'There are no users registered with this email address.', 'sliced-invoices-client-area' );

			// Reset password
			case 'expiredkey':
			case 'invalidkey':
				return __( 'The password reset link you used is not valid anymore.', 'sliced-invoices-client-area' );

			case 'password_reset_mismatch':
				return __( "The two passwords you entered don't match.", 'sliced-invoices-client-area' );

			case 'password_reset_empty':
				return __( "Sorry, we don't accept empty passwords.", 'sliced-invoices-client-area' );
				
			// Captchas
			case 'captcha_error':	// generic
			case 'cptch_error':		// for compatibility with BestWebSoft Captcha plugin
				return __('Captcha error, please try again.', 'sliced-invoices-client-area' );
			
			default:
				break;
		}

		return __( 'An unknown error occurred. Please try again later.', 'sliced-invoices-client-area' );
	}


}

// Initialize the plugin
$personalize_login_pages_plugin = new Personalize_Login_Plugin();