<?php

NTNLR_Checkin::get_instance();

class NTNLR_Checkin {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the NTNLR_Checkin
	 *
	 * @return NTNLR_Checkin
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof NTNLR_Checkin ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'wp_ajax_ntnlr_checkin_attendee', array( $this, 'checkin_attendee' ) );
		add_action( 'wp_ajax_nopriv_ntnlr_checkin_attendee', array( $this, 'checkin_attendee' ) );
		add_action( 'ntnlr_setup', array( $this, 'init' ) );

		// action setup by hide-wpengine-tab was causing issues with AJAX
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			remove_action( 'plugins_loaded', 'hwpet_hide_tab' );
		}
	}

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'ntnlr_checkin', NTNLR_URL . '/assets/js/app/checkin.js', array(
			'jquery',
			'wp-util'
		), NTNLR_VERSION, true );
		wp_localize_script( 'ntnlr_checkin', 'ntnlrCheckin', array(
			'security'   => wp_create_nonce( 'checkin' ),
			'conference' => NTNLR_Content::$_page_id
		) );
	}

	/**
	 * Handle attendee checkin
	 */
	public function checkin_attendee() {
		check_ajax_referer( 'checkin', 'security' );

		if ( empty( $_POST['attendee'] ) || empty( $_POST['conference'] ) ) {
			wp_send_json_error();
		}

		$attendee_id   = absint( $_POST['attendee'] );
		$conference_id = absint( $_POST['conference'] );

		if ( empty( $_POST['device_id'] ) ) {
			delete_post_meta( $attendee_id, "ntnlr_device_id_{$conference_id}" );
		} else {
			update_post_meta( $attendee_id, "ntnlr_device_id_{$conference_id}", sanitize_text_field( $_POST['device_id'] ) );
		}

		if ( empty( $_POST['checked_in'] ) ) {
			update_post_meta( $attendee_id, "ntnlr_checked_in_{$conference_id}", "0" );
		} else {
			// only save if we have not already checked this user in
			if ( ! get_post_meta( $attendee_id, "ntnlr_checked_in_{$conference_id}", true ) ) {
				update_post_meta( $attendee_id, "ntnlr_checked_in_{$conference_id}", time() );
			}
		}

		wp_send_json_success();

	}

}