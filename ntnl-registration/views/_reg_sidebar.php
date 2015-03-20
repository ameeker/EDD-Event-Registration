<?php
$this_page = get_permalink();
?>
&nbsp;
<?php if ( NTNLR_Content::$_attendees->have_posts() ) : ?>
	<p><strong>Attendees you are registering:</strong></p>
	<ul>
		<?php while( NTNLR_Content::$_attendees->have_posts() ) : NTNLR_Content::$_attendees->the_post(); ?>
			<li><a href="<?php echo $this_page; ?>?action=register&step=1&user=<?php echo get_the_ID(); ?><?php echo NTNLR_Content::$_reg_anchor; ?>"><?php the_title(); ?></a></li>
		<?php endwhile; ?>
	</ul>
<?php endif; ?>
<?php wp_reset_postdata(); ?>
<?php if ( edd_get_cart_contents() ) : ?>
	<p>
		Total: <?php edd_cart_total( true ); ?> <br />
		<a href="<?php echo esc_url( edd_get_checkout_uri() ); ?>">Checkout</a>
	</p>
<?php endif; ?>