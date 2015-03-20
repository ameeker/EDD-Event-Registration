<?php

if ( ! $tax_slug = get_post_meta( get_the_ID(), NTNLR_Meta_Boxes::$_prefix . 'registration_tax_slug', true ) ) {
	printf( "<p>Looks like you haven't specified a taxonomy for this registration. Please do so and then come back.</p>" );

	return;
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

$conference_id = NTNLR_Content::$_page_id;
$attendees = new WP_Query( $args ); ?>

<?php if ( ! $attendees->have_posts() ) : ?>
	No attendees were found for this registration.
<?php else : ?>
	<table class="table-sort">
		<thead>
		<tr>
			<th>&nbsp;</th>
			<th data-sort="string" class="sort">First Name</th>
			<th data-sort="string" class="sort">Last Name</th>
			<th data-sort="string" class="sort">Voting Device ID</th>
			<th data-sort="string" class="sort">Checked-in</th>
			<th>&nbsp;</th>
		</tr>
		</thead>
		<tbody>
		<?php while ( $attendees->have_posts() ) : $attendees->the_post(); ?>
			<?php
			$args = array(
				'view' => 'attendee-report',
				'id'   => get_the_ID(),
			);

			$attendee_link = add_query_arg( $args, get_the_permalink( NTNLR_Content::$_page_id ) );
			?>
			<tr>
				<td>
					<a class="dashicons dashicons-search attendee-link" href="<?php echo esc_url( $attendee_link ); ?>" target="_blank"></a>
				</td>
				<td>
					<?php echo get_post_meta( get_the_ID(), 'ntnlr_first_name', true ); ?>
				</td>
				<td>
					<?php echo get_post_meta( get_the_ID(), 'ntnlr_last_name', true ); ?>
				</td>
				<td>
					<div style="display:none"><?php echo get_post_meta( get_the_ID(), "ntnlr_device_id_{$conference_id}", true ); ?></div>
					<input type="text" name="device-id-<?php echo esc_attr( get_the_ID() ); ?>" placeholder="Voting Device ID" value="<?php echo get_post_meta( get_the_ID(), "ntnlr_device_id_{$conference_id}", true ); ?>" data-original="<?php echo get_post_meta( get_the_ID(), "ntnlr_device_id_{$conference_id}", true ); ?>" />
				</td>
				<td class="text-center">
					<div style="display:none"><?php echo get_post_meta( get_the_ID(), "ntnlr_checked_in_{$conference_id}", true ); ?></div>
					<input type="checkbox" name="checked-in-<?php echo esc_attr( get_the_ID() ); ?>" <?php checked( (bool) get_post_meta( get_the_ID(), "ntnlr_checked_in_{$conference_id}", true ) ); ?> data-original="<?php echo (bool) get_post_meta( get_the_ID(), "ntnlr_checked_in_{$conference_id}", true ); ?>" />
				</td>
				<td>
					<input type="submit" value="Save" class="attendee-save" data-attendee="<?php echo esc_attr( get_the_ID() ); ?>" />
				</td>
			</tr>
		<?php endwhile; ?>
		</tbody>
	</table>
<?php endif; ?>
<?php wp_reset_postdata(); ?>