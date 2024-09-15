<?php 
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

?>

<div class="sliced client login">

    <div class="row sliced-upper">

        <div class="col-sm-12 sliced-login">

            <?php
            $args = array( 'redirect' => sliced_client_area_permalink() );
            if(isset($_GET['login']) && $_GET['login'] == 'failed' || $_GET['login'] == 'empty' ){
                ?>
                    <p class="sliced-message error" ><?php _e( 'Login failed, please try again.', 'sliced-invoices-client-area' ) ?></p>
                <?php
            }
            ?>

            <form id="loginform" name="loginform" class="sliced_login_form" action="<?php echo wp_login_url(); ?>" method="post">

            <h3 class="text-center"><?php _e( 'Login', 'sliced-invoices-client-area' ) ?></h3>

                <div class="form-group">
                    <input type="text" name="log" id="user_login" class="form-control required" placeholder="<?php _e( 'Username', 'sliced-invoices-client-area' ) ?>">
                </div>

                <div class="form-group">
                    <input type="password" name="pwd" id="user_pass" class="form-control required" placeholder="<?php echo Sliced_Client_Area::$translate['client-login-password']; ?>">
                </div>
				
				<?php do_action( 'login_form' ); ?>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" value="forever" id="rememberme" name="rememberme" checked="checked"> <?php echo Sliced_Client_Area::$translate['client-login-remember']; ?>
                    </label>
                </div>

                <input type="hidden" value="<?php echo esc_url( sliced_client_area_permalink() ); ?>" name="redirect_to">
                <input type="submit" id="wp-submit" name="wp-submit" class="btn btn-success btn-lg btn-block" value="<?php _e( 'Log In', 'sliced-invoices-client-area' ); ?>">

            </form>
			
			<?php do_action( 'login_footer' ); ?>

        </div>
        
    </div>

</div>
