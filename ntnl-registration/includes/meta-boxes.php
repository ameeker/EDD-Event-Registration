<?php
/**
 *
 * @package  Metaboxes
 * @license  http://www.opensource.org/licenses/gpl-license.php GPL v2.0 (or later)
 * @link     https://github.com/webdevstudios/Custom-Metaboxes-and-Fields-for-WordPress
 */

NTNLR_Meta_Boxes::get_instance();

class NTNLR_Meta_Boxes {

	/**
	 * @var
	 */
	protected static $_instance;

	public static $_prefix = '_ntnlr_';

	/**
	 * Only make one instance of the NTNLR_Meta_Boxes
	 *
	 * @return NTNLR_Meta_Boxes
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof NTNLR_Meta_Boxes ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		require_once( NTNLR_PATH . '/cmb2/init.php' );
		add_filter( 'cmb2_meta_boxes', array( $this, 'metaboxes_init' ) );
		add_filter( 'cmb2_show_on', array( $this, 'show_on_registration' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_products' ), 11, 3 );

		add_action( 'cmb2_render_content',       array( $this, 'render_content'       ), 10, 5 );
		add_action( 'cmb2_render_repeater_id',   array( $this, 'render_repeater_id'   ), 10, 5 );
		add_filter( 'cmb2_sanitize_repeater_id', array( $this, 'sanitize_repeater_id' ), 10, 5 );
	}

	public function metaboxes_init() {
		$meta_boxes[] = $this->is_registration();
		$meta_boxes[] = $this->registration_settings();
		$meta_boxes[] = $this->registration_price_point();
		$meta_boxes[] = $this->registration_member_type();
		$meta_boxes[] = $this->registration_reports();

		return $meta_boxes;
	}

	/**
	 * Mark this as a registration page
	 *
	 * @return array
	 */
	protected function is_registration() {
		return array(
			'id'           => 'registration_meta',
			'title'        => __( 'Registration' ),
			'object_types' => array( 'page' ),
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
			'fields'       => array(
				array(
					'desc' => __( 'Is this a registration page?', 'ntnlr' ),
					'id'   => self::$_prefix . 'is_registration_page',
					'type' => 'checkbox',
				)
			)
		);
	}

	/**
	 * Set the Gravity From to be used for all registrants
	 *
	 * @return array
	 */
	protected function registration_settings() {
		return array(
			'id'           => 'registration_settings',
			'title'        => __( 'Registration Settings', 'ntnlr' ),
			'object_types' => array( 'page' ),
			'show_names'   => true,
			'show_on'      => array(
				'meta'       => self::$_prefix . 'is_registration_page',
				'meta_value' => true,
			),
			'fields'       => array(
				array(
					'name'    => __( 'Registration Form', 'ntnlr' ),
					'desc'    => __( 'Select a custom Gravity Form to be shown to all registrants', 'ntnlr' ),
					'id'      => self::$_prefix . 'registration_gform',
					'type'    => 'select',
					'options' => array( $this, 'get_gforms' ),
				),
				array(
					'id'          => self::$_prefix . 'registration_options',
					'type'        => 'group',
					'description' => __( 'Generates registration options', 'cmb2' ),
					'count'       => 1,
					'options'     => array(
						'group_title'   => __( 'Add On {#}', 'ntnlr' ), // {#} gets replaced by row number
						'add_button'    => __( 'Add Another Option', 'ntnlr' ),
						'remove_button' => __( 'Remove Option', 'ntnlr' ),
						'sortable'      => true, // beta
					),
					'fields'      => array(
						array(
							'id' => 'id',
							'type' => 'repeater_id',
						),
						array(
							'name' => __( 'Name', 'ntnlr' ),
							'desc' => __( 'Name of Option', 'ntnlr' ),
							'id'   => 'name',
							'type' => 'text',
						),
						array(
							'name'    => __( 'Description', 'ntnlr' ),
							'desc'    => __( 'Description of this Option', 'ntnlr' ),
							'id'      => 'desc',
							'type'    => 'textarea',
						),
						array(
							'name' => __( 'Price', 'ntnlr' ),
							'desc' => __( 'Price for this Option', 'ntnlr' ),
							'id'   => 'price',
							'type' => 'text_money',
						),
						array(
							'name' => __( 'Restrict To', 'ntnlr' ),
							'desc' => __( 'Only show this Option to these registrants.', 'ntnlr' ),
							'id'   => 'restricted',
							'type' => 'multicheck',
							'options' => array( $this, 'get_restrictions' ),
						),
					),
				),

			)
		);
	}

	/**
	 * Repeater list of Price Points
	 *
	 * @return array
	 */
	protected function registration_price_point() {
		return array(
			'id'           => 'registratin_price_points',
			'title'        => __( 'Registration Price Points', 'ntnlr' ),
			'object_types' => array( 'page' ),
			'show_names'   => true,
			'show_on'      => array(
				'meta'       => self::$_prefix . 'is_registration_page',
				'meta_value' => true,
			),
			'fields'       => array(
				array(
					'id'          => self::$_prefix . 'registration_price_points',
					'type'        => 'group',
					'description' => __( 'Generates reusable form entries', 'cmb2' ),
					'count'       => 1,
					'options'     => array(
						'group_title'   => __( 'Price Point {#}', 'ntnlr' ), // {#} gets replaced by row number
						'add_button'    => __( 'Add Another Price Point', 'ntnlr' ),
						'remove_button' => __( 'Remove Price Point', 'ntnlr' ),
						'sortable'      => true, // beta
					),
					'fields'      => array(
						array(
							'id' => 'id',
							'type' => 'repeater_id',
						),
						array(
							'name' => __( 'Title', 'ntnlr' ),
							'desc' => __( 'Title for this Price Point', 'ntnlr' ),
							'id'   => 'title',
							'type' => 'text',
						),
						array(
							'name' => __( 'Price', 'ntnlr' ),
							'desc' => __( 'Price for this Price Point', 'ntnlr' ),
							'id'   => 'price',
							'type' => 'text_money',
						),
						array(
							'name'    => __( 'Custom Form', 'ntnlr' ),
							'desc'    => __( 'Select a custom Gravity Form to be shown with this Price Point', 'ntnlr' ),
							'id'      => 'gform',
							'type'    => 'select',
							'options' => array( $this, 'get_gforms' ),
						)
					),
				),
			)
		);
	}

	/**
	 *
	 * @return array
	 */
	protected function registration_member_type() {
		return array(
			'id'           => 'registratin_settings_member_types',
			'title'        => __( 'Registration Member Types', 'ntnlr' ),
			'object_types' => array( 'page' ),
			'show_names'   => true,
			'show_on'      => array(
				'meta'       => self::$_prefix . 'is_registration_page',
				'meta_value' => true,
			),
			'fields'       => array(
				array(
					'id'          => self::$_prefix . 'registration_member_types',
					'type'        => 'group',
					'description' => __( 'Define the different member types if applicable.', 'ntnlr' ),
					'options'     => array(
						'group_title'   => __( 'Member Type {#}', 'ntnlr' ), // {#} gets replaced by row number
						'add_button'    => __( 'Add Another Member Type', 'ntnlr' ),
						'remove_button' => __( 'Remove Member Type', 'ntnlr' ),
						'sortable'      => true, // beta
					),
					'fields'      => array(
						array(
							'id' => 'id',
							'type' => 'repeater_id',
						),
						array(
							'name' => __( 'Title', 'ntnlr' ),
							'desc' => __( 'Title for this Member Type', 'ntnlr' ),
							'id'   => 'title',
							'type' => 'text',
						),
						array(
							'name'    => __( 'Price Point', 'ntnlr' ),
							'desc'    => __( 'Which Price Point does this Member Type belong to?', 'ntnlr' ),
							'id'      => 'price_point',
							'type'    => 'select',
							'options' => array( $this, 'get_price_points' ),
						),
						array(
							'name'    => __( 'Custom Form', 'ntnlr' ),
							'desc'    => __( 'Select a custom Gravity Form to be shown with this Price Point', 'ntnlr' ),
							'id'      => 'gform',
							'type'    => 'select',
							'options' => array( $this, 'get_gforms' ),
						)
					),
				)

			)
		);

	}

	/**
	 * The Fields to collect for the General Report
	 * @return array
	 */
	protected function registration_reports() {
		$post_id = false;

		if ( isset( $_POST['post_ID'] ) ) {
			$post_id = absint( $_POST['post_ID'] );
		} elseif ( isset( $_GET['post'] ) ) {
			$post_id = absint( $_GET['post'] );
		}

		return array(
			'id'           => 'registratin_settings_reports',
			'title'        => __( 'Registration Report', 'ntnlr' ),
			'object_types' => array( 'page' ),
			'show_names'   => true,
			'show_on'      => array(
				'meta'       => self::$_prefix . 'is_registration_page',
				'meta_value' => true,
			),
			'fields'       => array(
				array(
					'name' => __( 'Report Meta Fields', 'ntnlr' ),
					'id'   => self::$_prefix . 'registration_report_meta',
					'type' => 'textarea',
					'desc' => __( 'A comma separated list of the meta to show on the General Report page.', 'ntnlr' ),
				),
				array(
					'name' => __( 'Attendee Event Taxonomy Slug', 'ntnlr' ),
					'id'   => self::$_prefix . 'registration_tax_slug',
					'type' => 'text_medium',
					'desc' => __( 'The slug of the taxonomy associated with this event.', 'ntnlr' ),
				),
				array(
					'name' => __( 'Views Password', 'ntnlr' ),
					'id'   => self::$_prefix . 'registration_report_password',
					'type' => 'text_medium',
					'desc' => __( 'The password that will be required before viewing the custom pages', 'ntnlr' ),
				),
				array(
					'name'    => __( 'General Report Link', 'ntnlr' ),
					'id'      => self::$_prefix . 'registration_report_link',
					'type'    => 'content',
					'content' => sprintf( 'This report can be found at <a href="%1$s" target="_blank">%1$s</a>', add_query_arg( 'view', 'general-report', get_the_permalink( $post_id ) ) ),
				),
				array(
					'name'    => __( 'Checkin Link', 'ntnlr' ),
					'id'      => self::$_prefix . 'registration_checkin_link',
					'type'    => 'content',
					'content' => sprintf( 'Attendees can be checked in at at <a href="%1$s" target="_blank">%1$s</a>', add_query_arg( 'view', 'attendee-checkin', get_the_permalink( $post_id ) ) ),
				),
				array(
					'name'    => __( 'Attendee CSV Downlaod', 'ntnlr' ),
					'id'      => self::$_prefix . 'registration_csv_download',
					'type'    => 'content',
					'content' => sprintf( '
						<form method="post">
							%s
							<input type="hidden" name="ntnlr_attendee_registration" value="%s">
							<input type="submit" value="Download Attendee CSV" class="button" />
						</form>',
						wp_nonce_field( 'attendee_csv_download', 'attendee_csv_nonce', true, false ),
						isset( $_GET['post'] ) ? $_GET['post'] : 0
					),
				),
			)
		);

	}

	/**
	 * Create option array of Gravity Forms
	 * @return array
	 */
	public function get_gforms() {
		$forms = RGFormsModel::get_forms( 1, "title" );
		$forms = array_combine( wp_list_pluck( $forms, 'id' ), wp_list_pluck( $forms, 'title' ) );

		$options[0] = '-- Select Gravity Form --';

		return $options + $forms;
	}

	/**
	 * Get member types and price points to populate restrictions
	 * @return array
	 */
	public function get_restrictions() {
		$restrctions = array();

		if ( isset( $_GET['post'] ) ) {
			$post_id = $_GET['post'];
		} elseif ( isset( $_POST['post_ID'] ) ) {
			$post_id = $_POST['post_ID'];
		}

		if ( ! isset( $post_id ) ) {
			return $restrctions;
		}

		$price_points = get_post_meta( $post_id, self::$_prefix . 'registration_price_points', true );
		foreach( (array) $price_points as $price_point ) {
			$restrctions[ $price_point['id'] ] = '<strong>Price Point: </strong>' . $price_point['title'];
		}

		$member_types = get_post_meta( $post_id, self::$_prefix . 'registration_member_types', true );
		foreach( (array) $member_types as $type ) {
			$restrctions[ $type['id'] ] = '<strong>Member Type: </strong>' . $type['title'];
		}

		return $restrctions;
	}

	/**
	 * Create option array of Registration Price Points
	 * @return array
	 */
	public function get_price_points() {

		if ( ! $price_points = get_post_meta( get_the_ID(), '_ntnlr_registration_price_points', true ) ) {
			return array( '-- Create Price Points Above --' );
		}

		$options[0] = '-- Select Price Point --';
		foreach ( $price_points as $price_point ) {
			// append string so we aren't attempting to store a '0' value
			$options[ $price_point['id'] ] = $price_point['title'];
		}

		return $options;
	}

	/**
	 * Show metabox if post meta equals provided value
	 * @author Tanner Moushey
	 * @link   https://github.com/WebDevStudios/CMB2/wiki/Adding-your-own-show_on-filters
	 *
	 * @param bool  $display
	 * @param array $meta_box
	 *
	 * @return bool display metabox
	 */
	public function show_on_registration( $display, $meta_box ) {

		// Get the current ID
		if ( isset( $_GET['post'] ) ) {
			$post_id = $_GET['post'];
		} elseif ( isset( $_POST['post_ID'] ) ) {
			$post_id = $_POST['post_ID'];
		}

		if ( ! isset( $post_id ) ) {
			return $display;
		}

		if ( ! isset( $meta_box['show_on']['meta'], $meta_box['show_on']['meta_value'] ) ) {
			return $display;
		}

		$value = get_post_meta( $post_id, $meta_box['show_on']['meta'], true );

		if ( $value == $meta_box['show_on']['meta_value'] ) {
			return $display;
		} else {
			return false;
		}

	}

	public function save_products( $post_ID, $post, $update ) {
		$prices = array();

		// make sure we are on a registration page
		if ( 'page' !== get_post_type( $post_ID ) || ! get_post_meta( $post_ID, self::$_prefix . 'is_registration_page', true ) ) {
			return;
		}

		if ( ! $edd_product_id = get_post_meta( $post_ID, '_ntnlr_edd_id', true ) ) {
			$edd_product_id = false;
		}

		// make sure this always updates
		$edd_product = new EDD_Download( false, array(
			'ID'          => $edd_product_id,
			'post_title'  => get_the_title( $post_ID ),
			'post_status' => 'publish',
		) );

		if ( ! $edd_product->ID ) {
			return;
		}

		$price_points = get_post_meta( $post_ID, self::$_prefix . 'registration_price_points', true );
		$options      = get_post_meta( $post_ID, self::$_prefix . 'registration_options',       true );

		foreach( $price_points as $key => $pricepoint ) {
			$prices[ $pricepoint['id'] ] = array(
				'name'   => $pricepoint['title'],
				'amount' => $pricepoint['price'],
			);
		}

		foreach( $options as $key => $option ) {
			$prices[ $option['id'] ] = array(
				'name' => $option['name'],
				'amount' => $option['price'],
			);
		}

		update_post_meta( $edd_product->ID, 'edd_variable_prices', $prices );
		update_post_meta( $edd_product->ID, '_variable_pricing', 1 );
		update_post_meta( $edd_product->ID, '_edd_hide_download', 1 );
		update_post_meta( $edd_product->ID, '_edd_hide_redirect_download', 1 );

		update_post_meta( $post_ID, '_ntnlr_edd_id', $edd_product->ID );
	}

	public function render_repeater_id( $field, $escaped_value, $object_id, $object_type, $field_type ) {
		printf( '<input type="hidden" name="%s" value="%s" />', $field->args['_name'], $escaped_value );
	}

	public function render_content( $field ) {
		echo $field->args['content'];
	}

	public function sanitize_repeater_id( $sanitized_val, $val, $object_id, $args, $field ) {
		if ( $val ) {
			return $val;
		}

		return mt_rand( 10000, 99999 );
	}
}