<?php
if ( empty( $_GET['id'] ) || empty( $_GET['view'] ) || 'attendee-report' != $_GET['view'] ) {
	echo '<p>Something went wrong, please try again.</p>';
	return;
}

$attendee_id = absint( $_GET['id'] );

if ( ! $attendee = get_post( $attendee_id ) ) {
	echo '<p>That attendee does not exist, please try again.</p>';
	return;
}

$meta = get_post_meta( $attendee_id );
$general_report_link =  add_query_arg( 'view', 'general-report', get_the_permalink( NTNLR_Content::$_page_id ) );

unset( $meta['_edit_lock'] );
unset( $meta['_edit_last'] );

if ( ! empty( $meta['payment_ids'] ) ) {
	$value = maybe_unserialize( $meta['payment_ids'][0] );

	// get value for this registration, legacy support for non-indexed values
	$value = ( empty( $value[ NTNLR_Content::$_page_id ] ) || count( $value ) == 1 ) ? reset( $value ) : $value[ NTNLR_Content::$_page_id ];
	$payment_meta = edd_get_payment_by( 'id', $value );

	if ( ! empty( $payment_meta->ID ) ) {
		$meta['_edd_payment_id'] = array( $payment_meta->ID );
		$payment_meta = get_post_meta( $payment_meta->ID );
	}

	$meta = array_merge( $meta, $payment_meta );
	unset( $meta['payment_ids'] );
	unset( $meta['_edd_payment_meta'] );
}
?>

<p><a href="<?php echo esc_url( $general_report_link ); ?>">&leftarrow; Back to General Report</a></p>

<h3>Attendee: <?php echo get_the_title( $attendee_id ); ?></h3>

<table>
<?php foreach( $meta as $key => $value ) : ?>
	<tr>
		<td><?php echo esc_attr( $key ); ?></td>
		<td><?php echo apply_filters( "ifai_report_field", $value[0], array( $key ), get_the_ID() ); ?></td>
	</tr>
<?php endforeach; ?>
</table>