<?php 
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
global $current_user;

?>

<div class="sliced client edit-profile">

    <?php do_action( 'sliced_before_client_area' ) ?>

    <div class="row">

        <div class="col-sm-12">

            <?php do_action( 'sliced_before_edit_profile_form' );?>

            <form method="post" id="update-user" action="<?php the_permalink(); ?>">

                <div class="form-group">
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="business"><?php _e('Business/Client Name', 'sliced-invoices-client-area' ); ?></label>
                            <input class="form-control" name="business" type="text" id="business" value="<?php echo $current_user->_sliced_client_business; ?>" />
                            <p class="help-block"><?php _e('The name of your business or your trading name.', 'sliced-invoices-client-area' ); ?></p>
                        </div>

                        <div class="col-sm-6">   

                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="first-name"><?php _e('First Name', 'sliced-invoices-client-area' ); ?></label>
                            <input class="form-control" name="first-name" type="text" id="first-name" value="<?php the_author_meta( 'first_name', $current_user->ID ); ?>" />
                        </div>

                        <div class="col-sm-6">   
                            <label for="last-name"><?php _e('Last Name', 'sliced-invoices-client-area' ); ?></label>
                            <input class="form-control" name="last-name" type="text" id="last-name" value="<?php the_author_meta( 'last_name', $current_user->ID ); ?>" />
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="email"><?php _e('E-mail *', 'sliced-invoices-client-area' ); ?></label>
                            <input class="form-control" name="email" type="text" id="email" value="<?php the_author_meta( 'user_email', $current_user->ID ); ?>" />
                        </div>

                        <div class="col-sm-6"> 
                            <label for="url"><?php _e('Website', 'sliced-invoices-client-area' ); ?></label>
                            <input class="form-control" name="url" type="text" id="url" value="<?php the_author_meta( 'user_url', $current_user->ID ); ?>" />
                        </div>
                    </div>
                </div>



                <div class="form-group">
                    <div class="row">
                        <div class="col-sm-6">        
                            <label for="address"><?php _e('Address', 'sliced-invoices-client-area' ); ?></label>
                            <textarea name="address" id="address" rows="3" cols="50" class="form-control"><?php echo $current_user->_sliced_client_address; ?></textarea>
                            <p class="help-block"><?php _e('Format the Address any way you like. HTML is allowed.', 'sliced-invoices-client-area' ); ?></p>
                        </div>

                        <div class="col-sm-6">     
                            <label for="extra_info"><?php _e('Extra Info', 'sliced-invoices-client-area' ); ?></label>
                            <textarea name="extra_info" id="extra_info" rows="3" cols="50" class="form-control"><?php echo $current_user->_sliced_client_extra_info; ?></textarea>
                            <p class="help-block"><?php _e('Phone number, business number, VAT details etc. HTML is allowed.', 'sliced-invoices-client-area' ); ?></p>
                        </div>
                    </div>
                </div>

                <div class="form-group">

                    <input name="updateuser" type="submit" id="updateuser" class="submit btn btn-success" value="<?php _e('Update Details', 'sliced-invoices-client-area'); ?>" />
                    <?php wp_nonce_field( 'update-user', 'update_user_nonce' ) ?>
                    <input name="action" type="hidden" id="action" value="update-user" />
                </div>


            </form><!-- #adduser -->

        </div>
        
    </div>

</div>
