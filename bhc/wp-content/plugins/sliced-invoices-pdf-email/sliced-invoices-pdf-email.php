<?php

/**
 * @wordpress-plugin
 * Plugin Name:       Sliced Invoices PDF Email
 * Plugin URI:        https://slicedinvoices.com/extensions/pdf-email/
 * Description:       Create PDF invoices and email them direct to clients. Requirements: The Sliced Invoices Plugin
 * Version:           1.7.1
 * Author:            Sliced Invoices
 * Author URI:        https://slicedinvoices.com/
 * Text Domain:       sliced-invoices-pdf-email
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
define( 'SI_PDF_EMAIL_VERSION', '1.7.1' );
define( 'SI_PDF_EMAIL_FILE', __FILE__ );
define( 'SI_PDF_PATH', plugin_dir_path( __FILE__ ) );

include( plugin_dir_path( __FILE__ ) . '/updater/plugin-updater.php' );

register_activation_hook( __FILE__, array( 'Sliced_Pdf_Email', 'sliced_pdf_email_activate' ) );
register_deactivation_hook( __FILE__, array( 'Sliced_Pdf_Email', 'sliced_pdf_email_deactivate' ) );

function sliced_pdf_email_load_textdomain() {
    load_plugin_textdomain( 'sliced-invoices-pdf-email', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'plugins_loaded', 'sliced_pdf_email_load_textdomain' );


/**
 * Remove the action for All in one intranet plugin
 * `https://wordpress.org/plugins/all-in-one-intranet/
 *
 * @since 1.0.9
 */
if ( class_exists('aioi_basic_all_in_one_intranet') && isset( $_GET['create']) ) {
    remove_action( 'template_redirect', array( aioi_basic_all_in_one_intranet::get_instance(), 'aioi_template_redirect'), 10 );
}


/**
 * Calls the class.
 */
function sliced_call_pdf_email_class() {
	return Sliced_Pdf_Email::get_instance();
}
add_action( 'init', 'sliced_call_pdf_email_class', 5 ); // 5 calls this before any other extensions (except Secure Invoices)


/** 
 * The Class.
 */
class Sliced_Pdf_Email {

    /**
     * @var  object  Instance of this class
     */
    protected static $single_instance = null;


    public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}


    public function __construct() {
		
		if ( ! $this->validate_settings() ) {
			return;
		}
		
		// PDF class
        require_once SI_PDF_PATH . 'includes/pdf/class-sliced-pdf.php';
		Sliced_Pdf::get_instance();
		
		// Emails class
        require_once SI_PDF_PATH . 'includes/emails/class-sliced-emails.php';
		Sliced_Emails::get_instance();
		
		// Everything else
        add_filter( 'plugin_action_links_sliced-invoices-pdf-email/sliced-invoices-pdf-email.php', array( $this, 'plugin_action_links' ) );
        add_filter( 'sliced_pdf_option_fields', array( $this, 'sliced_add_pdf_options' ), 1 );
		add_filter( 'sliced_email_option_fields', array( $this, 'sliced_add_email_options' ), 1 );
        add_filter( 'sliced_invoice_option_fields', array( $this, 'sliced_add_invoice_pdf_options' ), 1 );
        add_filter( 'sliced_quote_option_fields', array( $this, 'sliced_add_quote_pdf_options' ), 1 );
        add_filter( 'sliced_general_option_fields', array( $this, 'sliced_add_pdf_ssl_options' ), 1 );
        //add_filter( 'sliced_edit_admin_columns', array( $this, 'sliced_add_actions_column' ) );
        add_filter( 'sliced_actions_column', array( $this, 'sliced_add_pdf_email_buttons' ) );
		add_filter( 'upload_mimes', array( $this, 'sliced_add_font_mimes' ) );
        //add_action( 'add_meta_boxes', array( $this, 'sliced_add_pdf_email_meta_box' ) );
		add_action( 'admin_init', array( $this, 'admin_notices' ) );
        add_action( 'sliced_head', array( $this, 'sliced_pdf_enqueue_styles' ), 999 );
		
		add_filter( 'sliced_invoices_request_data', array( $this, 'request_data' ), 10, 2 );
		
    }


    /**
     * Add links to plugin page
     *
     * @since   2.0.0
     */
    public function plugin_action_links( $links ) {
       $links[] = '<a href="'. esc_url( get_admin_url( null, 'admin.php?page=sliced_invoices_settings&tab=emails' ) ) .'">' . __( 'Settings', 'sliced-invoices' ) . '</a>';
       return $links;
    }

    /**
     * Add default options to database.
     *
     * @since 1.0.0
     */
    public static function sliced_pdf_email_activate() {

        $email = get_option( 'sliced_emails' );
		
        // if a new install
        if( ! $email ) {

            $email['body_bg'] = isset( $email['body_bg'] ) ? $email['body_bg'] : '#eeeeee';
            $email['header_bg'] = isset( $email['header_bg'] ) ? $email['header_bg'] : '#dddddd';
            $email['content_bg'] = isset( $email['content_bg'] ) ? $email['content_bg'] : '#ffffff';
            $email['content_color'] = isset( $email['content_color'] ) ? $email['content_color'] : '#444444';
            $email['footer_bg'] = isset( $email['footer_bg'] ) ? $email['footer_bg'] : '#f6f6f6';
            $email['footer_color'] = isset( $email['footer_color'] ) ? $email['footer_color'] : '#444444';
            $email['footer'] = isset( $email['footer'] ) ? $email['footer'] : sprintf( 'Copyright %1s. %2s', date('Y'), function_exists( 'sliced_get_business_name' ) ? sliced_get_business_name(): '' );
            $email['quote_available'] = isset( $email['quote_available'] ) ? $email['quote_available'] : 'Hi %client_first_name%,

                            Please find attached our quote ( %number% ) for %client_business%.<br>
                            ';
            $email['invoice_available'] = isset( $email['invoice_available'] ) ? $email['invoice_available'] : 'Hi %client_first_name%,

                            Please find attached our invoice ( %number% ) for %client_business%.<br>
                            ';

            update_option('sliced_emails', $email);

        }

    }
	
	
	public static function sliced_pdf_email_deactivate() {
	
		wp_clear_scheduled_hook( 'sliced_invoices_pdf_invoice_updater' );
		$main = Sliced_Pdf_Email::get_instance();
		$main->admin_notices_clear();
		$updater = Sliced_Pdf_Invoice_Updater::get_instance();
		$updater->updater_notices_clear();
		
	}


	/**
     * Add the options fields for PDFs.
     *
     * @since 1.2.5
     */
    public function sliced_add_pdf_options( $options ) {

        $prefix = 'sliced_';
  	
		$options['fields'][] = array(
			'name'      => __( 'Page Size', 'sliced-invoices-pdf-email' ),
			'desc'      => __( 'Select paper size for generated PDFs', 'sliced-invoices-pdf-email' ),
			'id'        => 'page_size',
			'type'      => 'select',
			'default'   => 'LETTER',
			'options'   => array(
				'LETTER'    => __( 'Letter', 'sliced-invoices-pdf-email' ),
				'LEGAL'     => __( 'Legal', 'sliced-invoices-pdf-email' ),
				'LEDGER'    => __( 'Ledger', 'sliced-invoices-pdf-email' ),
				'TABLOID'   => __( 'Tabloid', 'sliced-invoices-pdf-email' ),
				'EXECUTIVE' => __( 'Executive', 'sliced-invoices-pdf-email' ),
				'FOLIO'     => __( 'Folio', 'sliced-invoices-pdf-email' ),
				'B'         => __( 'B', 'sliced-invoices-pdf-email' ),
				'A'         => __( 'A', 'sliced-invoices-pdf-email' ),
				'DEMY'      => __( 'Demy', 'sliced-invoices-pdf-email' ),
				'ROYAL'     => __( 'Royal', 'sliced-invoices-pdf-email' ),
				'4A0'       => __( '4A0', 'sliced-invoices-pdf-email' ),
				'2A0'       => __( '2A0', 'sliced-invoices-pdf-email' ),
				'A0'        => __( 'A0', 'sliced-invoices-pdf-email' ),
				'A1'        => __( 'A1', 'sliced-invoices-pdf-email' ),
				'A2'        => __( 'A2', 'sliced-invoices-pdf-email' ),
				'A3'        => __( 'A3', 'sliced-invoices-pdf-email' ),
				'A4'        => __( 'A4', 'sliced-invoices-pdf-email' ),
				'A5'        => __( 'A5', 'sliced-invoices-pdf-email' ),
				'A6'        => __( 'A6', 'sliced-invoices-pdf-email' ),
				'A7'        => __( 'A7', 'sliced-invoices-pdf-email' ),
				'A8'        => __( 'A8', 'sliced-invoices-pdf-email' ),
				'A9'        => __( 'A9', 'sliced-invoices-pdf-email' ),
				'A10'       => __( 'A10', 'sliced-invoices-pdf-email' ),
				'B0'        => __( 'B0', 'sliced-invoices-pdf-email' ),
				'B1'        => __( 'B1', 'sliced-invoices-pdf-email' ),
				'B2'        => __( 'B2', 'sliced-invoices-pdf-email' ),
				'B3'        => __( 'B3', 'sliced-invoices-pdf-email' ),
				'B4'        => __( 'B4', 'sliced-invoices-pdf-email' ),
				'B5'        => __( 'B5', 'sliced-invoices-pdf-email' ),
				'B6'        => __( 'B6', 'sliced-invoices-pdf-email' ),
				'B7'        => __( 'B7', 'sliced-invoices-pdf-email' ),
				'B8'        => __( 'B8', 'sliced-invoices-pdf-email' ),
				'B9'        => __( 'B9', 'sliced-invoices-pdf-email' ),
				'B10'       => __( 'B10', 'sliced-invoices-pdf-email' ),
				'C0'        => __( 'C0', 'sliced-invoices-pdf-email' ),
				'C1'        => __( 'C1', 'sliced-invoices-pdf-email' ),
				'C2'        => __( 'C2', 'sliced-invoices-pdf-email' ),
				'C3'        => __( 'C3', 'sliced-invoices-pdf-email' ),
				'C4'        => __( 'C4', 'sliced-invoices-pdf-email' ),
				'C5'        => __( 'C5', 'sliced-invoices-pdf-email' ),
				'C6'        => __( 'C6', 'sliced-invoices-pdf-email' ),
				'C7'        => __( 'C7', 'sliced-invoices-pdf-email' ),
				'C8'        => __( 'C8', 'sliced-invoices-pdf-email' ),
				'C9'        => __( 'C9', 'sliced-invoices-pdf-email' ),
				'C10'       => __( 'C10', 'sliced-invoices-pdf-email' ),
				'RA0'       => __( 'RA0', 'sliced-invoices-pdf-email' ),
				'RA1'       => __( 'RA1', 'sliced-invoices-pdf-email' ),
				'RA2'       => __( 'RA2', 'sliced-invoices-pdf-email' ),
				'RA3'       => __( 'RA3', 'sliced-invoices-pdf-email' ),
				'RA4'       => __( 'RA4', 'sliced-invoices-pdf-email' ),
				'SRA0'      => __( 'SRA0', 'sliced-invoices-pdf-email' ),
				'SRA1'      => __( 'SRA1', 'sliced-invoices-pdf-email' ),
				'SRA2'      => __( 'SRA2', 'sliced-invoices-pdf-email' ),
				'SRA3'      => __( 'SRA3', 'sliced-invoices-pdf-email' ),
				'SRA4'      => __( 'SRA4', 'sliced-invoices-pdf-email' ),
			),
		);
		$options['fields'][] = array(
			'name'      => __( 'Page Orientation', 'sliced-invoices-pdf-email' ),
			'id'        => 'page_orientation',
			'type'      => 'select',
			'default'   => 'portrait',
			'options'   => array(
				'portrait'    => __( 'Portrait', 'sliced-invoices-pdf-email' ),
				'landscape'   => __( 'Landscape', 'sliced-invoices-pdf-email' ),
			),
		);
		$options['fields'][] = array(
			'name'      => __( 'Page Font Size', 'sliced-invoices-pdf-email' ),
			'desc'      => __( 'Default font size to use in the PDF.  Can be overridden by custom CSS, if any.', 'sliced-invoices-pdf-email' ),
			'id'        => 'page_font_size',
			'type'      => 'select',
			'default'   => '',
			'options'   => array(
				''        => __( 'Default', 'sliced-invoices-pdf-email' ),
				'8px'     => '8',
				'9px'     => '9',
				'10px'    => '10',
				'11px'    => '11',
				'12px'    => '12',
				'13px'    => '13',
				'14px'    => '14',
				'15px'    => '15',
				'16px'    => '16',
				'17px'    => '17',
				'18px'    => '18',
			),
		);
        $options['fields'][] = array(
			'name'      => __( 'Add Unicode Font (.ttf)', 'sliced-invoices-pdf-email' ),
			'desc'      => __( 'If you need to print PDFs in languages requiring unicode characters (Japanese, Chinese, Korean, etc.), you may upload the required font here. <br>(For a good source of international language fonts, see <a href="https://www.google.com/get/noto/" target="_blank">Google Noto Fonts</a>)', 'sliced-invoices-pdf-email' ),
			'id'        => 'extra_font',
			'type'      => 'file',
			'options' => array(
				'url' => false,
			),
		);
		$options['fields'][] = array(
			'name'      => __( 'Add Extended Unicode Font (.ttf)', 'sliced-invoices-pdf-email' ),
			'desc'      => __( 'If your unicode font requires a second, extended font file, you may upload it here.', 'sliced-invoices-pdf-email' ),
			'id'        => 'extra_font_ext',
			'type'      => 'file',
			'options' => array(
				'url' => false,
			),
		);
		$options['fields'][] = array(
			'name'      => __( 'PDF Generation Mode', 'sliced-invoices-pdf-email' ),
			'desc'      => __( 'Try "compatibility" mode if you encounter issues with creating PDFs.', 'sliced-invoices-pdf-email' ),
			'id'        => 'mode',
			'type'      => 'select',
			'default'   => 'fast',
			'options'   => array(
				'fast'    => __( 'Default', 'sliced-invoices-pdf-email' ),
				'slow'    => __( 'Compatibility', 'sliced-invoices-pdf-email' ),
			),
		);

        return $options;

    }
	
	
    /**
     * Add the options fields for the emails.
     *
     * @since 1.0.0
     */
    public function sliced_add_email_options( $options ) {

        $prefix = 'sliced_';
  	
        $options['fields'][] = array(
            'name'      => __( 'Body Background', 'sliced-invoices-pdf-email' ),
            'desc'      => __( '', 'sliced-invoices-pdf-email' ),
            'default'   => '#eeeeee',
            'type'      => 'colorpicker',
            'id'        => 'body_bg',
        );
        $options['fields'][] = array(
            'name'      => __( 'Header Background', 'sliced-invoices-pdf-email' ),
            'desc'      => __( '', 'sliced-invoices-pdf-email' ),
            'default'   => '#dddddd',
            'type'      => 'colorpicker',
            'id'        => 'header_bg',
        );
        $options['fields'][] = array(
            'name'      => __( 'Content Background', 'sliced-invoices-pdf-email' ),
            'desc'      => __( '', 'sliced-invoices-pdf-email' ),
            'default'   => '#ffffff',
            'type'      => 'colorpicker',
            'id'        => 'content_bg',
        );
        $options['fields'][] = array(
            'name'      => __( 'Content Text Color', 'sliced-invoices-pdf-email' ),
            'desc'      => __( '', 'sliced-invoices-pdf-email' ),
            'default'   => '#444444',
            'type'      => 'colorpicker',
            'id'        => 'content_color',
        );
        $options['fields'][] = array(
            'name'      => __( 'Footer Background', 'sliced-invoices-pdf-email' ),
            'desc'      => __( '', 'sliced-invoices-pdf-email' ),
            'default'   => '#444444',
            'type'      => 'colorpicker',
            'id'        => 'footer_bg',
        );
        $options['fields'][] = array(
            'name'      => __( 'Footer Text Color', 'sliced-invoices-pdf-email' ),
            'desc'      => __( '', 'sliced-invoices-pdf-email' ),
            'default'   => '#ffffff',
            'type'      => 'colorpicker',
            'id'        => 'footer_color',
        );
        $options['fields'][] = array(
            'name'      => __( 'Footer Text', 'sliced-invoices-pdf-email' ),
            'desc'      => __( '', 'sliced-invoices-pdf-email' ),
            'type'      => 'wysiwyg',
            'default'   => '',
            'id'        => 'footer',
            'sanitization_cb' => false,
            'options' => array(
                'media_buttons' => false, // show insert/upload button(s)
                'textarea_rows' => get_option('default_post_edit_rows', 5), // rows="..."
                'teeny' => true, // output the minimal editor config used in Press This
                'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
                'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
            ),
        );
        

        return $options;

    }



    /**
     * Add the options field to the invoice section.
     *
     * @since 1.0.0
     */
    public function sliced_add_invoice_pdf_options( $options ) {

        $prefix = 'sliced_';

        $options['fields'][] = array(
            'name'      => __( 'Custom PDF CSS', 'sliced-invoices-pdf-email' ),
            'desc'      => __( 'Add custom CSS to the PDF. Due to the nature of printing PDF\'s (and it\'s limited support of CSS), occasionally you may need to add extra styles to get the PDF to look right.', 'sliced-invoices-pdf-email' ),
            'default'   => '',
            'type'      => 'textarea_small',
            'id'        => 'pdf_css',
        );

        return $options;

    }

    /**
     * Add the options field to the quote section.
     *
     * @since 1.0.0
     */
    public function sliced_add_quote_pdf_options( $options ) {

        $prefix = 'sliced_';

        $options['fields'][] = array(
            'name'      => __( 'Custom PDF CSS', 'sliced-invoices-pdf-email' ),
            'desc'      => __( 'Add custom CSS to the PDF. Due to the nature of printing PDF\'s (and it\'s limited support of CSS), occasionally you may need to add extra styles to get the PDF to look right.', 'sliced-invoices-pdf-email' ),
            'default'   => '',
            'type'      => 'textarea_small',
            'id'        => 'pdf_css',
        );

        return $options;

    }


    /**
     * Add the options field to the general section.
     *
     * @since 1.0.0
     */
    public function sliced_add_pdf_ssl_options( $options ) {

        $prefix = 'sliced_';

        $options['fields'][] = array(
            'name'      => __( 'SSL Verify', 'sliced-invoices-pdf-email' ),
            'desc'      => __( 'Set this to False if there are issues printing PDF\'s. It may resolve the issue.', 'sliced-invoices-pdf-email' ),
            'default'   => 'true',
            'type'      => 'select',
            'id'        => 'pdf_ssl',
            'options' => array(
                'true' => 'True', 
                'false' => 'False', 
            ),
        );

        return $options;

    }



    public function sliced_add_pdf_email_buttons( $button ) {
        return Sliced_Pdf::get_pdf_button();
    }    


    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since   2.0.0
     */
    public function sliced_pdf_enqueue_styles() {

		$css = '';
		$pdf_options = get_option( 'sliced_pdf' );
		
		// do we need to add an extra_font clause?
		if ( $pdf_options ) {
			if ( ! empty ( $pdf_options['extra_font_id'] ) || ! empty( $pdf_options['extra_font_ext_id'] ) ) {
				$css .= "@media only print { body{ font-family: extra_font; } }\n";
			}
		}
		
		// custom font size
		if ( $pdf_options ) {
			if ( ! empty ( $pdf_options['page_font_size'] ) ) {
				$css .= "@media only print { body{ font-size: " . $pdf_options['page_font_size'] . "; } }\n";
			}
		}
		
		// add the users custom PDF css last, if any
		$type = sliced_get_the_type();
		$options = get_option( "sliced_{$type}s" );
		$custom_css = isset( $options['pdf_css'] ) ? html_entity_decode( $options['pdf_css'] ) : false;
		if ( $custom_css ) {
			// wrap in a print only query
			$css .= "@media only print { " . $custom_css . "}\n";
		}
		
		$css = apply_filters( 'sliced_pdf_custom_css', $css );
		
		?>
		<link rel='stylesheet' id='print-css' href='<?php echo plugins_url( 'sliced-invoices-pdf-email' ) . '/public/css/print.css'; ?>?ver=<?php echo SI_PDF_EMAIL_VERSION; ?>' type='text/css' media='print' />
		<style id='print-inline-css' type='text/css'>
			<?php echo $css; ?>
		</style>
		<?php

    }
	
	
	/**
     * Allow upload of font files
     *
     * @since   1.2.5
     */
	function sliced_add_font_mimes( $mimes ) {
		// see https://core.trac.wordpress.org/ticket/40175
		$mimes = array_merge($mimes, array(
			'ttf' => 'application/octet-stream',    // for WordPress 4.7.1-4.7.2
			'ttf|ttf' => 'application/x-font-ttf',  // hack for WordPress 4.7.3+
			// and a little future proofing:
			// (see http://www.iana.org/assignments/media-types/application/font-sfnt)
			'ttf|ttf|ttf' => 'application/font-sfnt',
		));
		return $mimes;
	}
	
	
	/**
     * Admin notices for various things...
     *
     * @since   1.5.0
     */
	public function admin_notices() {
		
		// check just in case we're on < Sliced Invoices v3.5.0
		if ( class_exists( 'Sliced_Admin_Notices' ) ) {
		
			// Low memory warning
			$memory_good = false;
			$memory_limit = ini_get('memory_limit');
			if ( $memory_limit == -1 || $memory_limit === '' ) {
				$memory_good = true; // if it's -1, we're good!
			} else {
				$bytes = trim($memory_limit);
				$last = strtolower($bytes[strlen($bytes)-1]);
				$bytes = intval($memory_limit);
				switch($last) {
					// The 'G' modifier is available since PHP 5.1.0
					case 'g':
						$bytes *= 1024;
					case 'm':
						$bytes *= 1024;
					case 'k':
						$bytes *= 1024;
				}
				if ($bytes >= (64 * 1024 * 1024)) {
					$memory_good = true;
				}
			}
			if ( ! $memory_good ) {
				if ( ! Sliced_Admin_Notices::has_notice( 'pdf_invoice_low_memory_warning' ) ) {
					$notice_args = array(
						'class' => 'notice-warning',
						'content' => '<p>' . sprintf( __( 'Sliced Invoices PDF Extension has detected your server\'s memory limit is very low.  If you experience difficulty generating PDF files, please increase your PHP memory limit.  For further information, <a href="%s">see here</a>.', 'sliced-invoices-pdf-email' ), 'https://slicedinvoices.com/question/increase-php-memory-limit/' ) . '</p>',
						'dismissable' => true,
						'dismiss_permanent' => '1',
					);
					Sliced_Admin_Notices::add_custom_notice( 'pdf_invoice_low_memory_warning', $notice_args );
				}
			} else {
				Sliced_Admin_Notices::remove_notice( 'pdf_invoice_low_memory_warning' );
			}
			
			// check for mbstring support (error, not dismissable)
			if ( ! extension_loaded( 'mbstring' ) ) {
			
				if ( ! Sliced_Admin_Notices::has_notice( 'pdf_invoice_mbstring_missing' ) ) {
					$notice_args = array(
						'class' => 'notice-error',
						'content' => '<p>' . sprintf( __( 'Sliced Invoices PDF Extension has detected \'mbstring\' is not enabled on your server.  Mbstring is required and must be enabled for PDF functionality to work properly.  For further information, <a href="%s">see here</a>.', 'sliced-invoices-pdf-email' ), 'https://slicedinvoices.com/question/what-is-mbstring-and-how-do-i-enable-it/' ) . '</p>',
						'dismissable' => false,
					);
					Sliced_Admin_Notices::add_custom_notice( 'pdf_invoice_mbstring_missing', $notice_args );
				}
			
			} else {
				Sliced_Admin_Notices::remove_notice( 'pdf_invoice_mbstring_missing' );
			}
			
			// WP Super Cache compatibility warning (warning, dismissable permanently (or until this plugin is activated again))
			if ( function_exists( 'wp_super_cache_text_domain' ) ) {
			
				if ( ! Sliced_Admin_Notices::has_notice( 'pdf_invoice_wp_super_cache_warning' ) ) {
					$notice_args = array(
						'class' => 'notice-warning',
						'content' => '<p>' . sprintf( __( 'Hey there, we noticed you are using WP Super Cache, which is great plugin... However, certain settings in WP Super Cache can potentially conflict with your Sliced Invoices PDF Extension.  Please be sure to <a href="%s">read this page</a> to make sure everything keeps running smoothly. --<em>Your friends at Sliced Invoices</em>', 'sliced-invoices-pdf-email' ), 'https://slicedinvoices.com/question/pdfs-look-like-gibberish-using-wp-super-cache/' ) . '</p>',
						'dismissable' => true,
						'dismiss_permanent' => '1',
					);
					Sliced_Admin_Notices::add_custom_notice( 'pdf_invoice_wp_super_cache_warning', $notice_args );
				}
				
			}
			
		}
		
	}
	
	public function admin_notices_clear( $exclude = '' ) {
	
		// check just in case we're on < Sliced Invoices v3.5.0
		if ( class_exists( 'Sliced_Admin_Notices' ) ) {
		
			$notices = array(
				'pdf_invoice_low_memory_warning',
				'pdf_invoice_mbstring_missing',
				'pdf_invoice_wp_super_cache_warning',
			);
		
			foreach ( $notices as $notice ) {
				if ( Sliced_Admin_Notices::has_notice( $notice ) && $notice !== $exclude ) {
					Sliced_Admin_Notices::remove_notice( $notice );
				}
			}
			
		}
		
	}
	
	
	/**
     * Output requirements not met notice.
     *
     * @since   1.6.2
     */
	public function requirements_not_met_notice() {
		echo '<div id="message" class="error">';
		echo '<p>' . sprintf( __( 'Sliced Invoices PDF extension cannot find the required <a href="%s">Sliced Invoices plugin</a>. Please make sure the core Sliced Invoices plugin is <a href="%s">installed and activated</a>.', 'sliced-invoices-pdf-email' ), 'https://wordpress.org/plugins/sliced-invoices/', admin_url( 'plugins.php' ) ) . '</p>';
		echo '</div>';
	}
	
	
	/**
	 * Validate settings, make sure all requirements met, etc.
	 *
	 * @version 1.7.1
	 * @since   1.6.2
	 */
	public function validate_settings() {
		
		if ( ! class_exists( 'Sliced_Invoices' ) ) {
			
			// Add a dashboard notice.
			add_action( 'admin_notices', array( $this, 'requirements_not_met_notice' ) );
			
			return false;
		}
		
		return true;
	}


	/**
	 * Defines the function used to interact with the cURL library.
	 *
	 * @since   1.7.0
	 * @author  David Grant
	 */
	private static function curl( $url ) {
		
		if ( ! function_exists( 'curl_init' ) ) {
			return false;
		}
		
		$curl = curl_init( $url );
		
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $curl, CURLOPT_HEADER, 0 );
		curl_setopt( $curl, CURLOPT_USERAGENT, 'Sliced Invoices/'.SLICED_VERSION.' (via cURL)' );
		curl_setopt( $curl, CURLOPT_CONNECTTIMEOUT, 10 );
		curl_setopt( $curl, CURLOPT_TIMEOUT, 20 );
		curl_setopt( $curl, CURLOPT_TIMEOUT_MS, 20000 );
		curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
		
		do_action( 'sliced_pre_curl_exec', $curl );
		
		$response = curl_exec( $curl );
		
		if ( 0 !== curl_errno( $curl ) || 200 !== curl_getinfo( $curl, CURLINFO_HTTP_CODE ) ) {
			$response = null;
		}
		curl_close( $curl );
		
		return $response;
	}
	
	
	/**
	 * Retrieves the response from the specified URL using one of PHP's outbound
	 * request facilities.
	 *
	 * This compensates for deficiencies in WP's wp_remote_get() function that the
	 * WordPress team refuses to fix.
	 *
	 * Original idea from Tom McFarlin (https://tommcfarlin.com/wp_remote_get/)
	 * Yes, the blog post is dated 2013.  Yes, the problems he describes are still
	 * present in WordPress as of 2020. :-/
	 *
	 * Includes my own modifications based on 4+ years of success using this
	 * approach.
	 *
	 * @since   1.7.0
	 * @author  David Grant
	 */
	public static function request_data( $response, $url ) {
		
		$response = null;
		
		// First, we try to use wp_remote_get
		$response = wp_remote_get(
			$url, 
			array(
				'sslverify' => false,
				'timeout'   => 10,
			)
		);
		
		if ( ! $response || is_wp_error( $response ) ) {
			
			// If that doesn't work, then we'll try file_get_contents
			$response = @file_get_contents( $url );
			
			if ( false == $response ) {
				
				// And if that doesn't work, then we'll try curl
				$response = self::curl( $url );
				
			}
		
		}
		
		// If the response is an array, it's coming from wp_remote_get,
		// so we just want to capture to the body index for json_decode.
		if ( is_array( $response ) ) {
			$response = $response['body'];
		}
		
		return $response;
	}

}
