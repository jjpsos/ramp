<div class="sliced client login">

    <div class="row sliced-upper">

        <div class="col-sm-12 sliced-login">

			<div id="password-lost-form" class="widecolumn">
				<?php if ( $attributes['show_title'] ) : ?>
					<h3><?php echo Sliced_Client_Area::$translate['client-login-forgot-password']; ?></h3>
				<?php endif; ?>

				<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
					<?php foreach ( $attributes['errors'] as $error ) : ?>
						<p>
							<?php echo $error; ?>
						</p>
					<?php endforeach; ?>
				<?php endif; ?>

				<p>
					<?php
						_e(
							"Enter your email address and we'll send you a link you can use to pick a new password.",
							'sliced-invoices-client-area'
						);
					?>
				</p>

				<form id="lostpasswordform" action="<?php echo wp_lostpassword_url(); ?>" method="post">
					<p class="form-row">
						<label for="user_login"><?php echo Sliced_Client_Area::$translate['client-login-user']; ?>
						<input type="text" name="user_login" id="user_login">
					</p>
					
					<?php do_action( 'login_form' ); ?>

					<p class="lostpassword-submit">
						<input type="submit" name="submit" class="lostpassword-button"
						       value="<?php _e( 'Reset Password', 'sliced-invoices-client-area' ); ?>"/>
					</p>
				</form>
				
				<?php do_action( 'login_footer' ); ?>
				
			</div>

        </div>
        
    </div>

</div>
