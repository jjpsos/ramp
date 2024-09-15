(function( $ ) {
	'use strict';
	
	/**
	 * make sure dependencies are already loaded
	 */
	if ( typeof window.sliced_invoices === "undefined" ) {
		return;
	}
	
	if ( typeof window.sliced_invoices.hooks === "undefined" ) {
		return;
	}
	
	if ( typeof window.sliced_invoices.hooks.sliced_invoice_totals === "undefined" ) {
		return;
	}
	
	if ( typeof window.Decimal === "undefined" ) {
		return;
	}
	
	
	/**
	 * Begin
	 */
	sliced_invoices.hooks.sliced_invoice_totals.push( function calculate_additional_tax() {
		
		if ( typeof sliced_invoices.totals === "undefined" ) {
			sliced_invoices.totals = {};
		}
		
		if ( typeof sliced_invoices.totals.addons === "undefined" ) {
			sliced_invoices.totals.addons = [];
		}
		
		var output = {
			'sub_total_taxable':        new Decimal( 0 ),
			'sub_total_second_taxable': new Decimal( 0 ),
			'second_tax':               new Decimal( 0 ),
			'_name':                   'additional_tax'
		};
		if ( typeof sliced_payments.tax_calc_method === "undefined" || sliced_payments.tax_calc_method === 'exclusive' ) {
			output._adjustments = [{
				'type':   'add',
				'source': 'second_tax',
				'target': 'total'
			}];
		}

        // work out the line item totals
        jQuery('.sliced input.item_amount').each( function() {

            var group = jQuery(this).parents('.cmb-repeatable-grouping');
            var index = group.data('iterator');
			
	    	var qty = new Decimal( sliced_invoices.utils.rawNumber( jQuery(group).find('#_sliced_items_' + index + '_qty').val() ) );
			var amt = new Decimal( sliced_invoices.utils.rawNumber( jQuery(this).val() ) );
            var adj = new Decimal( sliced_invoices.utils.rawNumber( jQuery(group).find('#_sliced_items_' + index + '_tax').val() ) );
            
			var taxable        = jQuery(group).find('#_sliced_items_' + index + '_taxable').is(":checked");
			var second_taxable = jQuery(group).find('#_sliced_items_' + index + '_second_taxable').is(":checked");

            var line_adj        = adj.equals( 0 ) ? adj : adj.div( 100 ); // 0.10
            var line_sub_total  = qty.times( amt ); // 100
            var line_adj_amt    = line_sub_total.times( line_adj ); // 10
            var line_total      = line_sub_total.plus( line_adj_amt ); // 110
		
			if ( taxable ) {
				output.sub_total_taxable = output.sub_total_taxable.plus( line_total );
			}
			if ( second_taxable ) {
				output.sub_total_second_taxable = output.sub_total_second_taxable.plus( line_total );
			}

	    });
		
		// account for before-tax discounts, if any (after-tax discounts will be handled by core plugin)
		if ( $( 'input[name="_sliced_discount_tax_treatment"]:checked' ).val() === 'before' ) {
			var discountValue = sliced_invoices.utils.rawNumber( $( '#_sliced_discount' ).val() );
			if ( $( 'input[name="_sliced_discount_type"]:checked' ).val() === 'percentage' ) {
				var discountPercentage = new Decimal( discountValue );
				discountPercentage = discountPercentage.div( 100 );
				output.sub_total_second_taxable = output.sub_total_second_taxable.minus( output.sub_total_second_taxable.times( discountPercentage ) ).toDecimalPlaces( sliced_invoices.utils.decimals );
			} else {
				output.sub_total_second_taxable = output.sub_total_second_taxable.minus( discountValue ).toDecimalPlaces( sliced_invoices.utils.decimals );
			}
			if ( output.sub_total_second_taxable.lessThan( 0 ) ) {
				output.sub_total_second_taxable = new Decimal( 0 );
			}
		}
		
		var tax_percentage = new Decimal( 0 );
        if ( sliced_payments.tax != 0 ) {
			tax_percentage = new Decimal( sliced_payments.tax ); // don't filter it here. tax_percentage is saved as a real number internally.  The on.change handler already converts any formatted number to a real one.
			tax_percentage = tax_percentage.div( 100 );
		}
		
		var additional_tax = new Decimal( 0 );
        if ( sliced_payments.additional_tax != 0 ) {
			additional_tax = new Decimal( sliced_payments.additional_tax ); // don't filter it here. tax_percentage is saved as a real number internally.  The on.change handler already converts any formatted number to a real one.
			additional_tax = additional_tax.div( 100 );
		}
		var additional_tax_type = sliced_payments.additional_tax_type;
		
		// add additional tax if any
        if ( ! additional_tax.equals( 0 ) ) {
		
			if ( sliced_payments.tax_calc_method === 'inclusive' ) {
				// europe:
				if ( additional_tax_type === 'compound' ) {
					// since we don't have the first tax amount handy, we have to calculate it over again
					var tax_percentage_1 = tax_percentage.plus( 1 );
					var tax_amount_1 = output.sub_total_taxable.div( tax_percentage_1 );
					var tax_1 = output.sub_total_taxable.minus( tax_amount_1 ).toDecimalPlaces( sliced_invoices.utils.decimals );
					// tax basis = sub_total_second_taxable minus first tax
					var tax_basis = output.sub_total_second_taxable.minus( tax_1 );
				} else {
					var tax_basis = output.sub_total_second_taxable;
				}
				var additional_tax_1 = additional_tax.plus( 1 );
				var additional_tax_amount_1 = tax_basis.div( additional_tax_1 );
				output.second_tax = tax_basis.minus( additional_tax_amount_1 ).toDecimalPlaces( sliced_invoices.utils.decimals );
			} else {
				// everybody else:
				if ( additional_tax_type === 'compound' ) {
					// since we don't have the first tax amount handy, we have to calculate it over again
					var tax_1 = output.sub_total_taxable.times( tax_percentage );
					// tax basis = sub_total_second_taxable plus first tax
					var tax_basis = output.sub_total_second_taxable.plus( tax_1 );
				} else {
					var tax_basis = output.sub_total_second_taxable;
				}
				output.second_tax = tax_basis.times( additional_tax ).toDecimalPlaces( sliced_invoices.utils.decimals );
			}
			
        }
		
		// add hook
		sliced_invoices.totals.addons.push( output );
		
		// display it
		jQuery("#_sliced_line_items #sliced_additional_tax").html( sliced_invoices.utils.formattedAmount( output.second_tax ) );
	});
	
	
	$(document).on('keyup change', '#_sliced_additional_tax_rate', function() {
		sliced_payments.additional_tax = sliced_invoices.utils.rawNumber( $(this).val() );
		$('#_sliced_tax').change();
	});
	
	$(document).on('change', '#_sliced_additional_tax_type', function() {
		sliced_payments.additional_tax_type = $(this).val();
		$('#_sliced_tax').change();
	});


})( jQuery );
