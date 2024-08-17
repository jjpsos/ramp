(function( $ ) {
	'use strict';

	/**
     * calculate the totals on the fly when editing or adding a quote or invoice
     */
 	function workOutTotals(){

        var global_tax          = sliced_payments.tax != 0 ? sliced_payments.tax / 100 : 0;
        var additional_tax      = sliced_payments.additional_tax != 0 ? sliced_payments.additional_tax / 100 : 0;
		var additional_tax_type = sliced_payments.additional_tax_type;
        var symbol              = sliced_payments.currency_symbol;
        var position            = sliced_payments.currency_pos;
        var thousand_sep        = sliced_payments.thousand_sep;
        var decimal_sep         = sliced_payments.decimal_sep;
        var decimals            = sliced_payments.decimals;

        // sorts out the number to enable calculations
        function rawNumber(x) {
            // removes the thousand seperator
            var parts = x.toString().split(thousand_sep);
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '');
            var amount = parts.join('');
            // makes the decimal seperator a period
            var output = amount.toString().replace(/\,/g, '.');
            return parseFloat( output );
        }

        // formats number into users format
        function formattedNumber(nStr) {
            var num = nStr.split('.');
            var x1 = num[0];
            var x2 = num.length > 1 ? decimal_sep + num[1] : '';
            var rgx = /(\d+)(\d{3})/;
            while (rgx.test(x1)) {
                x1 = x1.replace(rgx, '$1' + thousand_sep + '$2');
            }
            return x1 + x2;
		}


		// format the amounts
        function formattedAmount(amount) {
            // do the symbol position formatting
            var formatted = 0;
            var amount = ( amount ).toFixed( decimals );
            switch (position) {
                case 'left':
                    formatted = symbol + formattedNumber( amount );
                    break;
                case 'right':
                    formatted = formattedNumber( amount ) + symbol;
                    break;
                case 'left_space':
                    formatted = symbol + ' ' + formattedNumber( amount );
                    break;
                case 'right_space':
                    formatted = formattedNumber( amount ) + ' ' + symbol;
                    break;
                default:
                    formatted = symbol + formattedNumber( amount );
                    break;
            }
            return formatted;
        }

        // work out the line total
        var sum = $.map($('.sliced input.item_amount'), function(item) {

            var group       = $(item).parents('.cmb-repeatable-grouping');
            var index       = group.data('iterator');

	    	var amount      = rawNumber( item.value );
            var tax_perc    = rawNumber( $(group).find('#_sliced_items_' + index + '_tax').val() );
            var qty         = rawNumber( $(group).find('#_sliced_items_' + index + '_qty').val() );

            if( isNaN( tax_perc ) ) { tax_perc = 0; }

            // work out the line totals and taxes/discounts
            var line_tax_perc   = tax_perc != 0 ? tax_perc / 100 : 0; // 0.10
            var line_sub_total  = qty * amount; // 100
            var line_tax_amt    = line_sub_total * line_tax_perc; // 10
            var line_total      = line_sub_total + line_tax_amt; // 110

            // display 0 instead of NaN
            if( isNaN( line_total ) ) { line_total = 0; }

            // display the calculated amount
            $( item ).parents('.cmb-type-text-money').find('.line_total').html( formattedAmount( line_total ) );
            // console.log(parseFloat(line_total));
	        return parseFloat( line_total );

	    }).reduce(function(a, b) {
	        return a + b;
	    }, 0);

        // display 0 instead of NaN
	    if( isNaN( sum ) ) { sum = 0; }

        var raw_total = sum;
        var raw_tax = 0;
        var add_raw_tax = 0;
        var add_raw_total = sum;

        // add global tax if any
        if ( global_tax > 0 ) {
            var raw_tax = sum * global_tax;
            var raw_total = sum + raw_tax;
        }
		
        // add additional tax if any
        if ( additional_tax != 0 ) {
			if ( additional_tax_type === 'compound' ) {
				var add_raw_tax = ( sum + raw_tax ) * additional_tax;
				var add_raw_total = sum + add_raw_tax;
			} else {
				var add_raw_tax = sum * additional_tax;
				var add_raw_total = sum + add_raw_tax;
			}
        } 

        //var final_tax   = sum + raw_tax + add_raw_tax;
        var final_total = sum + raw_tax + add_raw_tax;
        $("#_sliced_line_items #sliced_additional_tax").html( formattedAmount( add_raw_tax ) );
        $("#_sliced_line_items #sliced_total").html( formattedAmount( final_total ) );
        $("input#_sliced_totals_for_ordering").val( formattedAmount( final_total ) );

    };

	/* DAPP rounding fix.  Temporary pending new DAPP update */
	//$(document).on('keyup change', '.sliced input.item_amount, .sliced input.item_qty, .sliced input.item_tax', function () {
	//	workOutTotals();
	//});
	$(document).on('keyup change', '.sliced_discount_value, .sliced input.item_amount, .sliced input.item_qty, .sliced input.item_tax, select.pre_defined_products', function () {
		setTimeout( function(){ 
			workOutTotals();
		}, 3 );
	});
	/* End DAPP rounding fix */

    /**
     * add pre-defined items from select into the empty line item fields
     */
    $(document).on('change', 'select.pre_defined_products', function () {
        workOutTotals();
    });
    /**
     * on page load
     */
    $(function(){
        /* DAPP rounding fix.  Temporary pending new DAPP update */
        //workOutTotals();
		setTimeout( function(){ 
			workOutTotals();
		}, 3 );
		/* End DAPP rounding fix */
    });


})( jQuery );
