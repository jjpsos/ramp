<div class="sliced client login">

    <div class="row sliced-upper">

        <div class="col-sm-12 sliced-login">

			<div id="register-form" class="widecolumn">
				<?php if ( $attributes['show_title'] ) : ?>
					<h3><?php _e( 'Register', 'sliced-invoices-client-area' ); ?></h3>
				<?php endif; ?>

				<?php if ( count( $attributes['errors'] ) > 0 ) : ?>
					<?php foreach ( $attributes['errors'] as $error ) : ?>
						<p>
							<?php echo $error; ?>
						</p>
					<?php endforeach; ?>
				<?php endif; ?>

				<form id="signupform" action="<?php echo wp_registration_url(); ?>" method="post">
					<p class="form-row">
						<label for="email"><?php _e( 'Email', 'sliced-invoices-client-area' ); ?> <strong>*</strong></label>
						<input type="text" name="email" id="email">
					</p>

					<p class="form-row">
						<label for="first_name"><?php _e( 'First name', 'sliced-invoices-client-area' ); ?></label>
						<input type="text" name="first_name" id="first-name">
					</p>

					<p class="form-row">
						<label for="last_name"><?php _e( 'Last name', 'sliced-invoices-client-area' ); ?></label>
						<input type="text" name="last_name" id="last-name">
					</p>

					<p class="form-row">
						<?php _e( 'Note: Your password will be generated automatically and emailed to the address you specify above.', 'sliced-invoices-client-area' ); ?>
					</p>

					<?php if ( $attributes['recaptcha_site_key'] ) : ?>
						<div class="recaptcha-container">
							<div class="g-recaptcha" data-sitekey="<?php echo $attributes['recaptcha_site_key']; ?>"></div>
						</div>
					<?php endif; ?>
					
					<?php do_action( 'login_form' ); ?>

					<p></p>

					<p class="signup-submit">
						<input type="submit" name="submit" class="register-button"
						       value="<?php _e( 'Register', 'sliced-invoices-client-area' ); ?>"/>
					</p>
				</form>
				
				<?php do_action( 'login_footer' ); ?>
				
			</div>

        </div>
        
    </div>

</div>
