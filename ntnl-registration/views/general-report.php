<?php

if ( ! $tax_slug = get_post_meta( get_the_ID(), NTNLR_Meta_Boxes::$_prefix . 'registration_tax_slug', true ) ) {
	printf( "<p>Looks like you haven't specified a taxonomy for this registration. Please do so and then come back.</p>" );

	return;
}

if ( ! $meta_fields = get_post_meta( get_the_ID(), NTNLR_Meta_Boxes::$_prefix . 'registration_report_meta', true ) ) {
	printf( "<p>Looks like you haven't specified a meta for this report. Please do so and then come back.</p>" );

	return;
}

$meta_fields = explode( ',', $meta_fields );

foreach( $meta_fields as $key => $field ) {
	$meta_fields[ $key ] = array_map( 'trim', explode( '|', $field ) );
}

$args = array(
	'post_type'      => 'ntnlr_attendee',
	'posts_per_page' => - 1,
	'tax_query'      => array(
		array(
			'taxonomy' => 'ntnlr_event',
			'field'    => 'slug',
			'terms'    => $tax_slug,
		),
	),
);

$attendees = new WP_Query( $args ); ?>

<?php if ( ! $attendees->have_posts() ) : ?>
	No attendees were found for this registration.
<?php else : ?>
	<table class="table-sort">
		<thead>
		<tr>
			<th>&nbsp;</th>
			<?php foreach ( $meta_fields as $field ) : ?>
				<?php
				$label = ( isset( $field[1] ) ) ? $field[1] : $field[0];

				// if we don't have a custom label, process it
				if ( $label == $field[0] ) {
					$label = str_replace( 'ntnlr_', '', $label );
					$label = str_replace( '_', ' ', $label );
					$label = str_replace( get_the_ID(), '', $label );
					$label = str_replace( 'registration', '', $label );
					$label = ucwords( $label );
				}
				?>
				<th data-sort="string" class="sort"><?php echo esc_html( $label ); ?></th>
			<?php endforeach; ?>
		</tr>
		</thead>
		<tbody>
		<?php while ( $attendees->have_posts() ) : $attendees->the_post(); ?>
			<?php
			$args = array(
				'view'   => 'attendee-report',
				'id'     => get_the_ID(),
			);

			$attendee_link = add_query_arg( $args, get_the_permalink( NTNLR_Content::$_page_id ) );
			?>
			<tr>
				<td>
					<a class="dashicons dashicons-search attendee-link" href="<?php echo esc_url( $attendee_link ); ?>"></a>
				</td>
				<?php foreach ( $meta_fields as $field ) : ?>
					<td><?php echo apply_filters( "ifai_report_field", get_post_meta( get_the_ID(), $field[0], true ), $field, get_the_ID() ); ?></td>
				<?php endforeach; ?>
			</tr>
		<?php endwhile; ?>
		</tbody>
	</table>
<?php endif; ?>
<?php wp_reset_postdata(); ?>