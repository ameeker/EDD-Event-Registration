<hr />
<h4>Create an Account</h4>
<p class="description">Just enter your email and a password and an account will be created automatically. Then click 'Submit' to be taken to the next step.</p>
<form name="registerform" id="registerform" action="" method="post" novalidate="">

	<?php NTNLR_User_Login::print_errors(); ?>

	<p>
		<label for="user_email"><?php _e('E-mail') ?><br />
			<input type="email" name="user_email" id="user_email" class="input" size="25" /></label>
	</p>
	<p>
		<label for="user_pass"><?php _e('Password') ?><br />
			<input type="password" name="user_pass" id="user_pass" class="input" size="20" /></label>
	</p>
	<?php wp_nonce_field( 'ntnlr_user_signin', 'ntnlr_signin' ); ?>
	<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Submit'); ?>" /></p>

</form>

<p id="nav">
	<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" title="<?php esc_attr_e( 'Password Lost and Found' ) ?>"><?php _e( 'Lost your password?' ); ?></a>
</p>