(function( $ ) {
	'use strict';

    /**
     * Decline quote actions
     */
    $(function(){

        $( ".decline_quote" ).click(function() {
            // sets the hidden field value for the ID
            var id = $(this).data('id');
            $('#sliced_decline_quote_id').val(id);         
        });

        $( ".accept_quote" ).click(function() {
            // sets the hidden field value for the ID
            var id = $(this).data('id');
            var number = $(this).parent().siblings('.number').html();
            var amount = $(this).parent().siblings('.totals').html();

            $('#sliced_accept_quote_id').val(id);         
            $('.sliced_accept_quote_form_wrap .quote-number').html(number);         
            $('.sliced_accept_quote_form_wrap .quote-amount').html(amount);         
        });

    });

})( jQuery );
