<?php 
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
$inv_label_plural = sliced_get_invoice_label_plural();
$inv_label = sliced_get_invoice_label();
$quo_label_plural = sliced_get_quote_label_plural();
$quo_label = sliced_get_quote_label();
$general_settings = get_option( 'sliced_general' );
?>

    <div class="sliced client">

        <?php do_action( 'sliced_before_client_area' ) ?>
        
        <div class="row sliced-upper">

            <div class="col-sm-6 sliced-to-address sliced-address">

                <div class="name"><?php echo esc_html( sliced_get_client_business() ); ?></div>
                <?php echo sliced_get_client_address() ? 
                    '<div class="address">' . wpautop( wp_kses_post( sliced_get_client_address() ) ) . '</div>' : ''; ?>
                <?php echo sliced_get_client_extra_info() ? 
                    '<div class="extra_info">' . wpautop( wp_kses_post( sliced_get_client_extra_info() ) ) . '</div>' : ''; ?>
                <?php echo sliced_get_client_website() ? 
                    '<div class="website">' . esc_html( sliced_get_client_website() ) . '</div>' : ''; ?>
                <?php echo sliced_get_client_email() ? 
                    '<div class="email">' . esc_html( sliced_get_client_email() ) . '</div>' : ''; ?>

            </div>

            <div class="col-sm-6 sliced-client-snapshot">
                <h3><i class="fa fa-dot-circle-o"></i> <?php echo Sliced_Client_Area::$translate['client-accountsnapshot-label']; ?></h3>
                
				<?php if ( ! sliced_client_area_hide_quotes() ):?>
                <div class="owing">
                    <span class="amount sent"><?php echo esc_html( sliced_get_quote_totals( 'sent' ) ); ?></span> 
                    <?php echo Sliced_Client_Area::$translate['client-quotespending-label']; ?></div>
                <div class="small">
                    <span class="count"><?php echo count( sliced_user_items_ids( 'quote' ) ); ?></span>
                    <?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $quo_label_plural ); ?>,
                    <span class="count"><?php echo esc_html( sliced_get_quote_count( 'sent' ) ); ?></span> <?php echo Sliced_Client_Area::$translate['client-awaitingresponse-label']; ?>
                </div>
				<?php endif; ?>

				<?php if ( ! sliced_client_area_hide_invoices() ):?>
                <div class="owing">
                    <span class="amount unpaid"><?php echo esc_html( sliced_get_invoice_totals( array( 'unpaid', 'overdue' ) ) ); ?></span> 
                    <?php echo Sliced_Client_Area::$translate['client-totaloutstanding-label']; ?></div>                

                <div class="small">
                    <span class="count"><?php echo count( sliced_user_items_ids( 'invoice' ) ); ?></span> <?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $inv_label_plural ); ?>,
                    <span class="count"><?php echo esc_html( sliced_get_invoice_count( array( 'unpaid', 'overdue' ) ) ); ?></span> <?php echo Sliced_Client_Area::$translate['client-awaitingpayment-label']; ?>
                </div>
				<?php endif; ?>
				
            </div>
            
        </div>

        <hr>

		<?php if ( ! sliced_client_area_hide_quotes() ):?>
		
        <!-- QUOTES ////////////// -->
        <div class="row sliced-quote-items sliced-items">
            
            <div class="col-sm-12">

            <h3><i class="fa fa-pie-chart"></i> <?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $quo_label_plural ); ?></h3>

                <div class="quote-statuses statuses">
                    <span class="sent"><?php echo sliced_get_client_label( 'sent', __( 'Sent', 'sliced-invoices' ) ); ?> <?php echo esc_html( sliced_get_quote_totals( 'sent' ) ); ?></span>
					<?php if ( sliced_get_quote_count( 'accepted' ) > 0 ): ?>
					<span class="accepted"><?php echo sliced_get_client_label( 'accepted', __( 'Accepted', 'sliced-invoices' ) ); ?> <?php echo esc_html( sliced_get_quote_totals( 'accepted' ) ); ?></span>
					<?php endif; ?>
                    <span class="declined"><?php echo sliced_get_client_label( 'declined', __( 'Declined', 'sliced-invoices' ) ); ?> <?php echo esc_html( sliced_get_quote_totals( 'declined' ) ); ?></span>
                    <span class="cancelled"><?php echo sliced_get_client_label( 'cancelled', __( 'Cancelled', 'sliced-invoices' ) ); ?> <?php echo esc_html( sliced_get_quote_totals( 'cancelled' ) ); ?></span>
                </div>
               
            <!-- QUOTES TABLE ////////////// -->    
			<?php $quotes = sliced_user_items_ids( 'quote' );
            if( $quotes ) : ?>
                <div class="table-responsive">
                <table id="table-quotes" class="table table-sm table-bordered table-striped display" cellspacing="0" width="100%">
                    
                    <thead>
                        <tr>
                            <th class="id hidden">ID</th>
                            <th class="date"><strong><?php echo Sliced_Client_Area::$translate['client-date-label']; ?></strong></th>
                            <th class="title"><strong><?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $quo_label ); ?></strong></th>
                            <th class="status"><strong><?php echo Sliced_Client_Area::$translate['client-status-label']; ?></strong></th>
                            <th class="number"><strong><?php echo Sliced_Client_Area::$translate['client-number-label']; ?></strong></th>
                            <th class="totals"><strong><?php echo sliced_get_client_label( 'total', __( 'Total', 'sliced-invoices' ) ); ?></strong></th>
                            <th class="actions"></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="id hidden">ID</th>
                            <th class="date"><strong><?php echo Sliced_Client_Area::$translate['client-date-label']; ?></strong></th>
                            <th class="title"><strong><?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $quo_label ); ?></strong></th>
                            <th class="status"><strong><?php echo Sliced_Client_Area::$translate['client-status-label']; ?></strong></th>
                            <th class="number"><strong><?php echo Sliced_Client_Area::$translate['client-number-label']; ?></strong></th>
                            <th class="totals"><strong><?php echo sliced_get_client_label( 'total', __( 'Total', 'sliced-invoices' ) ); ?></strong></th>
                            <th class="actions" data-orderable="false"></th>
                        </tr>
                    </tfoot>

                    <tbody>

                    <?php
                    $count = 0;
                    foreach ( $quotes as $quote ) {
                        $class = ($count % 2 == 0) ? 'even' : 'odd'; ?>

                        <tr class="row_<?php echo esc_attr( $class ); ?> sliced-item">
                            <td class="id hidden"><?php echo esc_html( $quote ); ?></td>
                            <td class="date" data-order="<?php echo esc_attr( sliced_get_created( $quote ) ); ?>"><?php echo sliced_get_created( $quote ) ? Sliced_Shared::get_local_date_i18n_from_timestamp( sliced_get_created( $quote ) ) : __( 'N/A', 'sliced-invoices-client-area' ); ?></td>
                            <td class="title"><?php echo esc_html( get_the_title( $quote ) ); ?></td>
                            <td class="status"><span class="<?php echo sanitize_title( sliced_get_quote_status( $quote ) ); ?>"><?php echo esc_html( sliced_get_client_label( sliced_get_quote_status( $quote ), __( sliced_get_quote_status( $quote ), 'sliced-invoices' ) ) ); ?></span></td>
                            <td class="number"><?php echo esc_html( sliced_get_prefix( $quote ) . sliced_get_number( $quote ) ); ?></td>
                            <td class="totals"><?php echo esc_html( sliced_get_quote_total( $quote ) ); ?></td>
                            <td class="actions text-right">
                                <a href="<?php esc_url( the_permalink( $quote ) ); ?>" class="btn btn-default btn-sm"><?php echo Sliced_Client_Area::$translate['client-viewquote-label']; ?></a>
                            </td>
                        </tr>

                    <?php $count++; } ?>

                    </tbody>

                </table>
                </div>
                <script type="text/javascript" charset="utf-8">
                    jQuery(document).ready(function() {

                        var title = '<?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $quo_label_plural ) ?><?php echo sanitize_file_name( date_i18n( get_option( 'date_format' ), time() ) ) ?>';

                        jQuery('#table-quotes').DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "pageLength": 10,
                            buttons: [
                                { extend: 'copy', text: '<i class="fa fa-copy"></i> <?php echo Sliced_Client_Area::$translate['client-copy-label']; ?>', title: title, exportOptions: { columns: [1,2,3,4,5,6] }  },
                                { extend: 'csv', text: '<i class="fa fa-file-excel-o"></i> CSV', title: title, exportOptions: { columns: [1,2,3,4,5,6] }  },
                                { extend: 'pdf', text: '<i class="fa fa-file-pdf-o"></i> PDF', title: title, exportOptions: { columns: [1,2,3,4,5,6] } },
                            ],
                            "dom": "<'row'<'col-sm-12 search'f>>t<'row'<'col-sm-8' B><'col-sm-4'lp>><'clear'>",
							"oLanguage": {
								"sSearch": "<?php echo Sliced_Client_Area::$translate['client-search-label']; ?>",
								"oPaginate": {
									"sPrevious": "<?php echo Sliced_Client_Area::$translate['client-previous-label']; ?>",
									"sNext": "<?php echo Sliced_Client_Area::$translate['client-next-label']; ?>"
								}
							},
							<?php if ( isset( $general_settings['client_area_default_sort'] ) ): ?>
								<?php if ( $general_settings['client_area_default_sort'] === 'date_desc' ): ?>
									"order": [ 1, 'desc' ],
								<?php elseif ( $general_settings['client_area_default_sort'] === 'date_asc' ): ?>
									"order": [ 1, 'asc' ],
								<?php endif; ?>
							<?php endif; ?>
                        });
                    } );
                </script>

            <?php else : ?>
                <p class="none"><?php echo Sliced_Client_Area::$translate['client-currentlynoquotes-label']; ?></p> 
            <?php endif; ?>

            </div>

        </div>

        <hr>
		
		<?php endif; ?>

		<?php if ( ! sliced_client_area_hide_invoices() ):?>
		
        <!-- INVOICES ////////////// -->
        <div class="row sliced-invoice-items sliced-items">
            
            <div class="col-sm-12">

            <h3><i class="fa fa-pie-chart"></i> <?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $inv_label_plural ); ?></h3>

                <div class="invoice-statuses statuses">
                    <span class="paid"><?php echo sliced_get_client_label( 'paid', __( 'Paid', 'sliced-invoices' ) ); ?> <?php echo esc_html( sliced_get_invoice_totals( 'paid' ) ); ?></span>
                    <span class="unpaid"><?php echo sliced_get_client_label( 'unpaid', __( 'Unpaid', 'sliced-invoices' ) ); ?> <?php echo esc_html( sliced_get_invoice_totals( 'unpaid' ) ); ?></span>
                    <span class="overdue"><?php echo sliced_get_client_label( 'overdue', __( 'Overdue', 'sliced-invoices' ) ); ?> <?php echo esc_html( sliced_get_invoice_totals( 'overdue' ) ); ?></span>
                    <span class="cancelled"><?php echo sliced_get_client_label( 'cancelled', __( 'Cancelled', 'sliced-invoices' ) ); ?> <?php echo esc_html( sliced_get_invoice_totals( 'cancelled' ) ); ?></span>
                </div>
        
            <!-- INVOICES TABLE ////////////// -->        
            <?php $invoices = sliced_user_items_ids( 'invoice' );
            if( $invoices ) : ?>
                <div class="table-responsive">
                <table id="table-invoices" class="table table-sm table-bordered table-striped display" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th class="id hidden">ID</th>
                            <th class="date"><strong><?php echo Sliced_Client_Area::$translate['client-date-label']; ?></strong></th>
                            <th class="due"><strong><?php echo Sliced_Client_Area::$translate['client-due-label']; ?></strong></th>
                            <th class="title"><strong><?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $inv_label ); ?></strong></th>
                            <th class="status"><strong><?php echo Sliced_Client_Area::$translate['client-status-label']; ?></strong></th>
                            <th class="number"><strong><?php echo Sliced_Client_Area::$translate['client-number-label']; ?></strong></th>
                            <th class="totals"><strong><?php echo sliced_get_client_label( 'total', __( 'Total', 'sliced-invoices' ) ); ?></strong></th>
                            <th class="actions" data-orderable="false"></th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="id hidden">ID</th>
                            <th class="date"><strong><?php echo Sliced_Client_Area::$translate['client-date-label']; ?></strong></th>
                            <th class="due"><strong><?php echo Sliced_Client_Area::$translate['client-due-label']; ?></strong></th>
                            <th class="title"><strong><?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $inv_label ); ?></strong></th>
                            <th class="status"><strong><?php echo Sliced_Client_Area::$translate['client-status-label']; ?></strong></th>
                            <th class="number"><strong><?php echo Sliced_Client_Area::$translate['client-number-label']; ?></strong></th>
                            <th class="totals"><strong><?php echo sliced_get_client_label( 'total', __( 'Total', 'sliced-invoices' ) ); ?></strong></th>
                            <th class="actions"></th>
                        </tr>
                    </tfoot>

                    <tbody>

                    <?php
                    $count = 0;
                    foreach ( $invoices as $invoice ) {
                        $class = ($count % 2 == 0) ? 'even' : 'odd'; ?>

                        <tr class="row_<?php echo $class; ?> sliced-item">
                            <td class="id hidden"><?php echo esc_html( $invoice ); ?></td>
                            <td class="date" data-order="<?php echo esc_attr( sliced_get_created( $invoice ) ); ?>"><?php echo sliced_get_created( $invoice ) ? Sliced_Shared::get_local_date_i18n_from_timestamp( sliced_get_created( $invoice ) ) :  __( 'N/A', 'sliced-invoices-client-area' ); ?></td>
                            <td class="due" data-order="<?php echo esc_attr( sliced_get_invoice_due( $invoice ) ); ?>"><?php echo sliced_get_invoice_due( $invoice ) ? Sliced_Shared::get_local_date_i18n_from_timestamp( sliced_get_invoice_due( $invoice ) ) : __( 'N/A', 'sliced-invoices-client-area' ); ?></td>
                            <td class="title"><?php echo esc_html( get_the_title( $invoice ) ); ?></td>
                            <td class="status"><span class="<?php echo sanitize_title( sliced_get_invoice_status( $invoice ) ); ?>"><?php echo esc_html( sliced_get_client_label( sliced_get_invoice_status( $invoice ), __( sliced_get_invoice_status( $invoice ), 'sliced-invoices' ) ) ); ?></span></td>
                            <td class="number"><?php echo esc_html( sliced_get_prefix( $invoice ) . sliced_get_number( $invoice ) ); ?></td>
                            <td class="totals"><?php echo esc_html( sliced_get_invoice_total( $invoice ) ); ?></td>
                            <td class="actions text-right">
                                <a href="<?php esc_url( the_permalink( $invoice ) ); ?>" class="btn btn-default btn-sm"><?php echo Sliced_Client_Area::$translate['client-viewinvoice-label']; ?></a>
                            </td>
                        </tr>

                    <?php $count++; } ?>

                    </tbody>

                </table>
                </div>
                <script type="text/javascript" charset="utf-8">
                    jQuery(document).ready(function() {

                        var title = '<?php printf( esc_html__( '%s', 'sliced-invoices-client-area' ), $inv_label_plural ) ?><?php echo sanitize_file_name( date_i18n( get_option( 'date_format' ), time() ) ) ?>';
                        
                        jQuery('#table-invoices').DataTable({
                            "responsive": true,
                            "lengthChange": false,
                            "pageLength": 10,
                            buttons: [
                                { extend: 'copy', text: '<i class="fa fa-copy"></i> <?php echo Sliced_Client_Area::$translate['client-copy-label']; ?>', title: title, exportOptions: { columns: [1,2,3,4,5,6] }  },
                                { extend: 'csv', text: '<i class="fa fa-file-excel-o"></i> CSV', title: title, exportOptions: { columns: [1,2,3,4,5,6] }  },
                                { extend: 'pdf', text: '<i class="fa fa-file-pdf-o"></i> PDF', title: title, exportOptions: { columns: [1,2,3,4,5,6] } },
                            ],
                            "dom": "<'row'<'col-sm-12 search'f>>t<'row'<'col-sm-8' B><'col-sm-4'lp>><'clear'>",
							"oLanguage": {
								"sSearch": "<?php echo Sliced_Client_Area::$translate['client-search-label']; ?>",
								"oPaginate": {
									"sPrevious": "<?php echo Sliced_Client_Area::$translate['client-previous-label']; ?>",
									"sNext": "<?php echo Sliced_Client_Area::$translate['client-next-label']; ?>"
								}
							},
							<?php if ( isset( $general_settings['client_area_default_sort'] ) ): ?>
								<?php if ( $general_settings['client_area_default_sort'] === 'date_desc' ): ?>
									"order": [ 1, 'desc' ],
								<?php elseif ( $general_settings['client_area_default_sort'] === 'date_asc' ): ?>
									"order": [ 1, 'asc' ],
								<?php endif; ?>
							<?php endif; ?>
                        });

                    } );
                </script>
            
            <?php else : ?>

                <p class="none"><?php echo Sliced_Client_Area::$translate['client-currentlynoinvoices-label']; ?></p> 

            <?php endif; ?>

            </div>

        </div>
		
		<?php endif; ?>

    </div>