<?php

NTNLR_Attendee_CSV::get_instance();

class NTNLR_Attendee_CSV {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the NTNLR_Attendee_CSV
	 *
	 * @return NTNLR_Attendee_CSV
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof NTNLR_Attendee_CSV ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'handle_csv_download' ), 11 );

		if ( isset( $_POST['attendee_csv_nonce'], $_POST['ntnlr_attendee_registration'] ) ) {
			remove_action( 'plugins_loaded', 'hwpet_hide_tab' );
		}
	}

	/**
	 * Handle CSV export
	 */
	public function handle_csv_download() {

		if ( ! is_admin() ) {
			return;
		}

		if ( ! isset( $_POST['attendee_csv_nonce'], $_POST['ntnlr_attendee_registration'] ) || ! wp_verify_nonce( $_POST['attendee_csv_nonce'], 'attendee_csv_download' ) ) {
			return;
		}

		error_reporting( 0 );

		$registration_id = absint( $_POST['ntnlr_attendee_registration'] );

		$price_points = get_post_meta( $registration_id, '_ntnlr_registration_price_points', true );
		$member_types = get_post_meta( $registration_id, '_ntnlr_registration_member_types', true );

		$tax_slug = get_post_meta( $registration_id, NTNLR_Meta_Boxes::$_prefix . 'registration_tax_slug', true );
		$args     = array(
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

		$attendees = get_posts( $args );

		$meta_keys         = array();
		$payment_meta_keys = array();
		foreach ( $attendees as $attendee ) {
			$meta = get_post_meta( $attendee->ID );

			if ( ! empty( $meta['payment_ids'] ) ) {
				$payment_meta = maybe_unserialize( $meta['payment_ids'][0] );

				// get value for this registration, legacy support for non-indexed values
				$edd_id       = ( empty( $payment_meta[ $registration_id ] ) || count( $payment_meta ) == 1 ) ? reset( $payment_meta ) : $payment_meta[ $registration_id ];
				$payment_meta = edd_get_payment_by( 'id', $edd_id );

				if ( empty( $payment_meta->ID ) ) {
					$payment_meta = array();
				} else {
					$payment_meta = get_post_meta( $payment_meta->ID );
				}

				$payment_meta_keys = array_unique( array_merge( $payment_meta_keys, array_keys( $payment_meta ) ) );
			}

			$keys      = array_keys( $meta );
			$meta_keys = array_unique( array_merge( $meta_keys, $keys ) );
		}

		reset( $attendees );

		$filename = "attendee_csv_{$registration_id}.csv";

		header( "Content-Type: text/csv" );
		header( "Content-Disposition: attachment; filename={$filename}" ); // Disable caching
		header( "Cache-Control: no-cache, no-store, must-revalidate" ); // HTTP 1.1
		header( "Pragma: no-cache" ); // HTTP 1.0
		header( "Expires: 0" ); // Proxies

		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, array_merge( $meta_keys, $payment_meta_keys ) );

		foreach ( $attendees as $attendee ) {
			$attendee_output = array();

			foreach ( $meta_keys as $key ) {
				$attendee_output[ $key ] = maybe_serialize( get_post_meta( $attendee->ID, $key, true ) );
				if ( strpos( $key, 'price_point' ) ) {
					$attendee_output[ $key ] = $this->get_title( $attendee_output[ $key ], $price_points );
				}

				if ( strpos( $key, 'member_type' ) ) {
					$attendee_output[ $key ] = $this->get_title( $attendee_output[ $key ], $member_types );
				}
			}

			// set default value for each payment meta key
			foreach ( $payment_meta_keys as $key ) {
				$attendee_output[ $key ] = '';
			}

			if ( ! empty( $attendee_output['payment_ids'] ) ) {
				$payment_meta = maybe_unserialize( $attendee_output['payment_ids'] );

				// get value for this registration, legacy support for non-indexed values
				$edd_id       = ( empty( $payment_meta[ $registration_id ] ) || count( $payment_meta ) == 1 ) ? reset( $payment_meta ) : $payment_meta[ $registration_id ];
				$payment_meta = edd_get_payment_by( 'id', $edd_id );

				if ( ! empty( $payment_meta->ID ) ) {
					foreach ( $payment_meta_keys as $key ) {
						$attendee_output[ $key ] = maybe_serialize( get_post_meta( $payment_meta->ID, $key, true ) );
					}
				}
			}

			fputcsv( $output, $attendee_output );
		}

		fclose( $output );
		exit;

	}

	/**
	 * Search the given array and return the title
	 *
	 * @param $id
	 * @param $array
	 *
	 * @return mixed
	 */
	protected function get_title( $id, $array ) {
		foreach( $array as $item ) {
			if ( $id == $item['id'] ) {
				return $item['title'];
			}
		}

		return $id;
	}
}