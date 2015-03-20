<div id="registration">

	<div class="reg-sidebar">
		<?php include( NTNLR_PATH . 'views/_reg_sidebar.php' ); ?>
	</div>

	<div class="reg-content">
		&nbsp;
		<?php

		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['step'] ) ) { ?>
			<p>
				<a href="<?php echo get_permalink(); ?>?action=register&step=1<?php echo NTNLR_Content::$_reg_anchor; ?>" class="button">Add New Person</a>
				<?php if ( edd_get_cart_contents() ) : ?>
					&nbsp;&nbsp;or&nbsp;&nbsp;&nbsp;<a href="<?php echo esc_url( edd_get_checkout_uri() ); ?>" class="button">Checkout</a>
				<?php endif; ?>
			</p>
		<?php
		} else {
			$attendee_id = ( isset( $_GET['user'] ) ) ? (int) $_GET['user'] : false;
			$step        = ( isset( $_GET['step'] ) ) ? (int) $_GET['step'] : 1;
			NTNLR_Content::next_step( $step, $attendee_id );
		}
		?>
	</div>

</div>
