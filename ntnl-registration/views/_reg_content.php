<?php
$reg_meta = ( ! isset( $_GET['user'] ) ) ? array() : get_post_meta( (int) $_GET['user'], 'ntnlr_registration_meta', true );

$editing = true;

if ( ! isset( $reg_meta[ NTNLR_Content::$_page_id ] ) ) {
	$editing = false;
	$reg_meta[ NTNLR_Content::$_page_id ] = array();
}

$reg_meta = wp_parse_args( $reg_meta[ NTNLR_Content::$_page_id ], array(
	'price_point' => '',
	'member_type' => '',
	'options'     => '',
	'cart_key'    => '',
) );

$current_price_point = false;
?>
<form id="reg-options-form" class="registration" name="ntnlr_registration" action="" method="post">
	<h4>Registration Type</h4>
	<p><label for="price-point">Please select if this person is a Voting Member, Visitor/Guest, or Retired Rostered Voting Member.</label>
	<select id="price-point" name="price-point" data-pricepoints="<?php echo esc_attr( json_encode( NTNLR_Content::$_price_points ) ); ?>" >
		<option value="0">-- Select a registration type --</option>
		<?php foreach( NTNLR_Content::$_price_points as $price_point ) : ?>
			<?php $current_price_point = ( $price_point['id'] == $reg_meta['price_point'] ) ? $price_point : $current_price_point; ?>
			<option <?php selected( $reg_meta['price_point'], $price_point['id'] ); ?> value="<?php echo esc_attr( $price_point['id'] ); ?>"><?php echo esc_html( $price_point['title'] ); ?> - $<?php echo esc_html( $price_point['price'] ); ?></option>
		<?php endforeach; ?>
	</select></p>

	<p class="member-type" <?php echo ( empty( $reg_meta['member_type'] ) ) ? 'style="display:none;"' : ''; ?>><label for="member-type">Please select the type that best describes this person.</label>
	<select id="member-type" name="member-type">
		<option value="0">-- Select a member type --</option>
		<?php if ( ! empty( $current_price_point['member_types'] ) ) : foreach( $current_price_point['member_types'] as $type ) : ?>
			<option <?php selected( $reg_meta['member_type'], $type['id'] ); ?> value="<?php echo esc_attr( $type['id'] ); ?>" class="type"><?php echo esc_html( $type['title'] ); ?></option>
		<?php endforeach; endif; ?>
	</select></p>

	<?php if ( $options = get_post_meta( NTNLR_Content::$_page_id, NTNLR_Meta_Boxes::$_prefix . 'registration_options', true ) ) : ?>
		<h4 class="options">Select the options to add to this registration.</h4>
		<?php foreach( $options as $option ) : ?>
			<label <?php echo ( empty( $option['restricted'] ) ) ? '' : 'class="restricted ' . implode( ' ', $option['restricted'] ) . '"'; ?>>
				<input <?php checked( in_array( $option['id'], $reg_meta['options'] ) ); ?> type="checkbox" name="options[]" value="<?php echo esc_attr( $option['id'] ); ?>" />
				<div class="description">
					<strong><?php echo esc_html( $option['name'] ); ?> - $<?php echo esc_html( $option['price'] ); ?></strong>
					<?php if ( ! empty( $option['desc'] ) ) : ?>
						<br /><?php echo esc_html( $option['desc'] ); ?>
					<?php endif; ?>
				</div>
			</label>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php wp_nonce_field( 'ntnlr_save_options', 'ntnlr_options' ); ?>
	<br /><br />
	<input type="submit" value="Add to Cart" />
	<?php if ( $editing ) : ?>
		<?php $get = $_GET; ?>
		<?php $get['step'] ++; ?>
		&nbsp;|&nbsp;<a href="<?php echo add_query_arg( $get, get_permalink() ) . NTNLR_Content::$_reg_anchor; ?>" >Skip</a>
   <?php endif; ?>
</form>
