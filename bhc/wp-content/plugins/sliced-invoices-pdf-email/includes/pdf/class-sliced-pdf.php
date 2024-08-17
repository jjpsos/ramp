<?php
// Exit if accessed directly
if ( ! defined('ABSPATH') ) { exit; }

class Sliced_Pdf {

	/**
	 * @var  object  Instance of this class
	 */
	protected static $instance;


    public function __construct() {
	
    	add_action( 'init', array( $this, 'create_pdf' ), 99 );
    	add_action( 'sliced_invoice_top_bar_right', array( $this, 'front_pdf_button' ) );
    	add_action( 'sliced_quote_top_bar_right', array( $this, 'front_pdf_button' ) );
		
	}


    public static function get_instance() {
		if ( ! ( self::$instance instanceof self ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
     * Get the PDF button for the admin area.
     *
     * @since 1.0.0
     */
	public static function get_pdf_button() {

	    $id = sliced_get_the_id();
	    if ( ! isset( $id ) )
	    	return;

		$url = add_query_arg( array( 'create' => 'pdf', 'id' => $id ), sliced_get_the_link( $id ) );

	    $button = '<a title="View or download as a PDF" class="button ui-tip sliced-pdf-button" href="' . esc_url( wp_nonce_url( $url, 'sliced-print-pdf', 'print_pdf' ) ) . '" target="_blank"><div class="dashicons dashicons-media-default"></div></a>';

	    return $button;

	}

	/**
     * Get the PDF button for the front end.
     *
     * @since 1.0.0
     */
	public static function front_pdf_button() {

	    $id = Sliced_Shared::get_item_id();
	    if ( ! isset( $id ) )
	    	return;

		$url = add_query_arg( array( 'create' => 'pdf', 'id' => $id ), sliced_get_the_link( $id ) );

	    $button = '<a title="View or download as a PDF" class="btn btn-default btn-sm sliced-print-button" href="' . esc_url( wp_nonce_url( $url, 'sliced-print-pdf', 'print_pdf' ) ) . '" target="_blank"><i class="fa fa-file-pdf-o"></i> ' . __( 'PDF', 'sliced-invoices-pdf-email' ) . '</a>';

	    echo $button;

	}

	/**
     * Init the pdf class, add a watermark if required and write to the PDF.
     *
     * @since 1.0.0
     */
	public static function init_pdf( $id = 0, $html, $file, $mode = 'I', $debug ) {
	
		require_once SI_PDF_PATH . 'includes/vendor/sliced-pdf/sliced-pdf.php';
	
		$pdf_options = get_option( 'sliced_pdf' );
		$page_size = isset( $pdf_options['page_size'] ) ? $pdf_options['page_size'] : 'LETTER';
		if ( isset( $pdf_options['page_orientation'] ) && $pdf_options['page_orientation'] === 'landscape' ) {
			$page_size .= '-L';
		}

		$mpdf = new Sliced_mPDF( '', $page_size ); 
		$mpdf->debug = $debug;
		$mpdf->ignore_invalid_utf8 = true;
		$mpdf->useSubstitutions = true;
		$mpdf->setAutoTopMargin = 'stretch';
		$mpdf->setAutoBottomMargin = 'stretch';
		$mpdf = self::set_watermark( $id, $mpdf );
		if( $debug == true ) {
			echo '<pre style="white-space:pre-wrap;">';
			print_r( $html );
			echo '</pre>';
		}
		
		do_action( 'sliced_pdf_init', $mpdf );
		
		$mpdf->WriteHTML( $html );
		$mpdf->Output( $file . '.pdf', $mode );

		return $mpdf;
	}


	/**
     * NOT USED
     *
     * @since 1.0.0
     */
	public static function set_footer() { ?>

<!-- 		<div class="row sliced-footer no-print">
            <div class="col-xs-12">
            	<?php sliced_display_footer() ?>
         	</div>
        </div>
		<div class="print-only">Page {PAGENO}/{nbpg}</div> -->

		<?php
	}

	/**
     * Prints the watermark on to the PDF if it has paid or cancelled.
     *
     * @since 1.0.0
     */
	public static function set_watermark( $id = 0, $mpdf ) {

		if( has_term( 'paid', 'invoice_status', $id ) ) {
			//watermarks
			$mpdf->SetWatermarkText( __( 'Paid', 'sliced-invoices' ) );
			$mpdf->showWatermarkText = true;
			$mpdf->watermark_font = 'Helvetica';
			$mpdf->watermarkTextAlpha = 0.07;
			$mpdf->SetDisplayMode('fullpage');
		}
		if( has_term( 'cancelled', 'invoice_status', $id ) ) {
			//watermarks
			$mpdf->SetWatermarkText( __( 'Cancelled', 'sliced-invoices' ) );
			$mpdf->showWatermarkText = true;
			$mpdf->watermark_font = 'Helvetica';
			$mpdf->watermarkTextAlpha = 0.07;
			$mpdf->SetDisplayMode('fullpage');
		}

		return $mpdf;

	}


	/**
     * Print the PDF.
     *
     * @since 1.0.0
     */
	public static function create_pdf() {

		if ( ! isset( $_GET['print_pdf'] ) || ! wp_verify_nonce( $_GET['print_pdf'], 'sliced-print-pdf') )
			return;

		if ( ! isset( $_GET['create'] ) || $_GET['create'] != 'pdf' )
			return;

		if ( ! isset( $_GET['id'] ) )
			return;

		do_action( 'sliced_before_request_pdf' );

		// request the quote or invoice
		$id 	= (int) $_GET['id'];
		
		$pdf_options = get_option( 'sliced_pdf' );
		if ( $pdf_options && isset( $pdf_options['mode'] ) && $pdf_options['mode'] === 'slow' ) {
			$html = Sliced_Shared::request_data( add_query_arg( array( 'create' => 'pdf', 'id' => $id ), sliced_get_the_link( $id ) ) );
		} else {
			$html = Sliced_Pdf::get_html( $id );
			/*
			// this is an idea that still needs some work:
			if ( site_url() > '' && ABSPATH > '' ) {
				// ensure images and such use local file paths, so mPDF won't use cURL to try and get them
				$html = str_replace( site_url() . '/', ABSPATH, $html );
				$html = str_replace( '?ver=3.3.1', '', $html ); //need better way of filtering out ver=whatever params (regex?)
			}
			*/
		}
		
		$html = apply_filters( 'sliced_pdf_html', $html );
		
		$file 	= sliced_get_filename( $id );

		// are we debugging
		$debug = false;
		if( isset( $_GET['debug'] ) && $_GET['debug'] == 'true' ) {
			$debug = true;
		}
		// start the PDF
		$mpdf = self::init_pdf( $id, $html, $file, 'I', $debug ); 
		
		exit;

	}
	
	/**
     * Get HTML content for PDF.
	 * new method to use instead of Sliced_Shared::request_data()
	 * runs locally without need for wp_remote_get or curl functions
     *
     * @since 1.3.0
     */
	public static function get_html( $id ) {
		
		global $post;
		$post = get_post( $id );
		
		setup_postdata( $post ); 
		
		$public = Sliced_Public::get_instance();
		$template = $public->invoice_quote_template( '' );
		
		ob_start();
		require( $template );
		$html = ob_get_clean();
		
		return $html;
	}


}