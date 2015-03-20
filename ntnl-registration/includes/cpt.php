<?php

NTNL_CPT::get_instance();
class NTNL_CPT {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the NTNL_CPT
	 *
	 * @return NTNL_CPT
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof NTNL_CPT ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'attendees_cpt' ) );
	}

	/**
	 * Register Attendees CPT
	 */
	public function attendees_cpt() {
		$labels = array(
			'name'               => _x( 'Attendees', 'post type general name', 'ntnlr' ),
			'singular_name'      => _x( 'Attendee', 'post type singular name', 'ntnlr' ),
			'menu_name'          => _x( 'Attendees', 'admin menu', 'ntnlr' ),
			'name_admin_bar'     => _x( 'Attendee', 'add new on admin bar', 'ntnlr' ),
			'add_new'            => _x( 'Add New', 'ntnlr_attendee', 'ntnlr' ),
			'add_new_item'       => __( 'Add New Attendee', 'ntnlr' ),
			'new_item'           => __( 'New Attendee', 'ntnlr' ),
			'edit_item'          => __( 'Edit Attendee', 'ntnlr' ),
			'view_item'          => __( 'View Attendee', 'ntnlr' ),
			'all_items'          => __( 'All Attendees', 'ntnlr' ),
			'search_items'       => __( 'Search Attendees', 'ntnlr' ),
			'parent_item_colon'  => __( 'Parent Attendees:', 'ntnlr' ),
			'not_found'          => __( 'No attendees found.', 'ntnlr' ),
			'not_found_in_trash' => __( 'No attendees found in Trash.', 'ntnlr' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'attendees' ),
			'has_archive'        => false,
			'hierarchical'       => false,
			'capability_type'    => 'ntnlr_attendee',
			'menu_icon'          => 'dashicons-groups',
			'supports'           => array( 'title', 'author', 'custom-fields' )
		);

		register_post_type( 'ntnlr_attendee', $args );

		$labels = array(
			'name'              => _x( 'Events', 'taxonomy general name' ),
			'singular_name'     => _x( 'Event', 'taxonomy singular name' ),
			'search_items'      => __( 'Search Events' ),
			'all_items'         => __( 'All Events' ),
			'parent_item'       => __( 'Parent Event' ),
			'parent_item_colon' => __( 'Parent Event:' ),
			'edit_item'         => __( 'Edit Event' ),
			'update_item'       => __( 'Update Event' ),
			'add_new_item'      => __( 'Add New Event' ),
			'new_item_name'     => __( 'New Event Name' ),
			'menu_name'         => __( 'Event' ),
		);

		$args = array(
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
		);

		register_taxonomy( 'ntnlr_event', array( 'ntnlr_attendee' ), $args );
	}

}