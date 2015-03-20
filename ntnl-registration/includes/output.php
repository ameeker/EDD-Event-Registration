<?php

NTNLR_Content::get_instance();
class NTNLR_Content {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Store the price points for this registration
	 * @var
	 */
	public static $_price_points;

	/**
	 * Store the member types for this registration
	 * @var
	 */
	public static $_member_types;

	/**
	 * Store the page id
	 * @var
	 */
	public static $_page_id;

	/**
	 *
	 */
	public static $_reg_anchor = '#registration';

	/**
	 * store current users attendees
	 *
	 * @var bool
	 */
	public static $_attendees;

	/**
	 * Only make one instance of the NTNLR_Content
	 *
	 * @return NTNLR_Content
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof NTNLR_Content ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		// Fire before GravityForms form processing
		add_action( 'wp', array( $this, 'initialize_registration' ), 8 );
		add_action( 'edd_complete_purchase', array( $this, 'store_payment_info' ) );
	}

	/**
	 * Is this a registration page? If so initialize registration functionality
	 */
	public function initialize_registration() {

		if ( ! is_page() ) {
			return;
		}

		if ( ! get_post_meta( get_the_ID(), '_ntnlr_is_registration_page', true ) ) {
			return;
		}

		// Setup global variables
		self::$_page_id      = get_the_ID();
		self::$_price_points = get_post_meta( self::$_page_id, '_ntnlr_registration_price_points', true );
		self::$_member_types = get_post_meta( self::$_page_id, '_ntnlr_registration_member_types', true );
		self::$_attendees    = new WP_Query( array(
			'post_type'      => 'ntnlr_attendee',
			'author'         => get_current_user_id(),
			'posts_per_page' => - 1
		) );

		foreach( self::$_price_points as &$price_point ) {
			foreach( self::$_member_types as $type ) {
				if ( empty( $type['price_point'] ) || $type['price_point'] != $price_point['id'] ) {
					continue;
				}

				$price_point['member_types'][] = $type;
			}
		}

		// Save registration options on the backend
		if ( isset( $_POST['ntnlr_options'] ) ) {
			$this->save_options();
		}

		if ( isset( $_POST['ntnlr_password_nonce'] ) ) {
			$this->set_view_password_cookie();
		}

		// Redirect new users to the general registration form
		if ( empty( $_GET ) && ! self::$_attendees->post_count ) {
			$get = array(
				'action' => 'register',
				'step'   => 1,
			);
			$redirect = add_query_arg( $get, get_permalink( self::$_page_id ) );
			wp_safe_redirect( $redirect );
			exit;
		}

		// We've made it this far, now enqueue scripts and add content
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts'   ) );
		add_filter( 'body_class',         array( $this, 'view_class'      ) );
		add_filter( 'the_content',        array( $this, 'reg_content'       ) );
		add_filter( 'gform_confirmation', array( $this, 'form_confirmation' ), 10, 3 );
		add_filter( 'ifai_report_field',  array( $this, 'report_fields'     ), 10, 3 );

		do_action( 'ntnlr_setup' );

	}

	public function reg_content( $content ) {
		ob_start();

		if ( $this->is_view() ) {
			// don't show normal content on view page
			$content = '';
			if ( $this->view_password_required() ) {
				$this->the_view_password_form();
			} else {
				include( $this->get_view_template_path() );
			}
		} elseif ( is_user_logged_in() ) {
			include( NTNLR_PATH . 'views/registration.php' );
		} else {
			include( NTNLR_PATH . 'views/login.php' );
		}

		return $content . ob_get_clean();
	}

	/**
	 * Should the current page return a view?
	 *
	 * @return bool
	 */
	protected function is_view() {
		if ( empty( $_GET['view'] ) ) {
			return false;
		}

		return (bool) $this->get_view_template_path();
	}

	/**
	 * Get the path for the current view template
	 *
	 * @return bool|string
	 */
	protected function get_view_template_path() {
		if ( ! file_exists( NTNLR_PATH . 'views/' . $_GET['view'] . '.php' ) ) {
			return false;
		}

		return NTNLR_PATH . 'views/' . $_GET['view'] . '.php';
	}

	/**
	 * 
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'stupidtable', NTNLR_URL . '/assets/js/vendor/stupidtable.min.js', array( 'jquery' )  );
		wp_enqueue_script( 'js/app/app/app.js', NTNLR_URL . '/assets/js/app/app.js', array( 'wp-backbone', 'stupidtable' ),NTNLR_VERSION, true );
		wp_enqueue_style( 'ntnlr', NTNLR_URL . '/assets/css/ntnl_registration.css', array( 'dashicons' ) );
	}

	/**
	 * Add support for Backbone templates
	 */
	public function reg_templates() {
		if ( ! defined( 'NTNLR_IS_JS_TEMPLATE' ) ) {
			define( 'NTNLR_IS_JS_TEMPLATE', true );
		} ?>

		<script type="text/template" id="tmpl-ntnlr-sidebar">
			<?php include( NTNLR_PATH . 'views/_reg_sidebar.php' ); ?>
		</script>
		<?php
	}

	/**
	 * Determine if this is a js template
	 *
	 * @return bool
	 */
	public static function is_js_template () {
		return ( defined( 'NTNLR_IS_JS_TEMPLATE' ) && NTNLR_IS_JS_TEMPLATE );
	}

	public static function get_meta( $key ) {
		return get_post_meta( self::$_page_id, $key, true );
	}

	/**
	 * Hyjack GForm submission for this page
	 *
	 * @param $confirmation
	 * @param $form
	 * @param $lead
	 *
	 * @return array
	 */
	public function form_confirmation( $confirmation, $form, $lead ) {
		$get = $_GET;

		if ( isset( $lead['post_id'] ) ) {
			$get['user'] = (int) $lead['post_id'];
		}

		$get['step'] = ( isset( $get['step'] ) ) ? intval( $get['step'] ) + 1 : 1;

		$redirect = add_query_arg( $get, get_permalink() ) . self::$_reg_anchor;
		return array( 'redirect' => $redirect );
	}

	/**
	 * Get Price Point by ID
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public static function get_price_point( $id ) {
		foreach( self::$_price_points as $price_point ) {
			if ( $id == $price_point['id'] ) {
				return $price_point;
			}
		}

		return false;
	}

	/**
	 * Get Member Type by ID
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public static function get_member_type( $id ) {
		foreach( self::$_member_types as $type ) {
			if ( $id == $type['id'] ) {
				return $type;
			}
		}

		return false;
	}

	/**
	 * Output next step in registration process
	 *
	 * @param      $step
	 * @param bool $attendee_id
	 */
	public static function next_step( $step, $attendee_id = false ) {

		// make our steps start at 1
		$steps[0] = null;

		if ( $general_form = NTNLR_Content::get_meta( '_ntnlr_registration_gform' ) ) {
			$steps[] = $general_form;
		}

		if ( ! empty( self::$_price_points ) ) {
			$steps[] = 'options';
		}

		$event_meta = get_post_meta( $attendee_id, 'ntnlr_registration_meta', true );
		if ( isset( $event_meta[ self::$_page_id ] ) ) {
			$event_meta = $event_meta[ self::$_page_id ];

			// get price point form
			if ( isset( $event_meta['price_point'] ) && $price_point = self::get_price_point( $event_meta['price_point'] ) ) {
				if ( ! empty( $price_point['gform'] ) ) {
					$steps[] = $price_point['gform'];
				}
			}

			// get member type form
			if ( isset( $event_meta['member_type'] ) && $member_type = self::get_member_type( $event_meta['member_type'] ) ) {
				if ( ! empty( $member_type['gform'] ) ) {
					$steps[] = $member_type['gform'];
				}
			}

		}

		if ( ! isset( $steps[ $step ] ) ) {
			$redirect = add_query_arg( array( 'action' => 'success' ), get_permalink( self::$_page_id ) ) . self::$_reg_anchor;
			wp_safe_redirect( $redirect );
			exit;
		}

		// Are we on the options step
		if ( 'options' == $steps[ $step ] ) {
			include( NTNLR_PATH . 'views/_reg_content.php' );
			return;
		}

		// We are on a Gravity Form
		$form_id = $steps[ $step ];

		if ( $attendee_id ) {
			do_action( 'gform_update_post/setup_form', array( 'post_id' => (int) $attendee_id, 'form_id' => $form_id ) );
		}

		gravity_form( $form_id, false, false, false, null, true );

	}

	/**
	 * Save registration options
	 *
	 * @return array|bool|int
	 */
	protected function save_options() {
		$_post = $_POST;

		if ( ! isset( $_post['ntnlr_options'], $_post['price-point'] ) ) {
			return false;
		}

		if ( ! isset( $_GET['user'] ) || ! $attendee = get_post( (int) $_GET['user'] )) {
			return false;
		}


		// make sure we have a valid price point
		if ( ! $price_point = self::get_price_point( $_post['price-point'] ) ) {
			return false;
		}

		if ( ! empty( $price_point['member_types'] ) ) {

			// this price point requires a member type
			if ( empty( $_post['member-type'] ) ) {
				return false;
			}

			// make sure the member type is valid
			$valid_member_types = wp_list_pluck( $price_point['member_types'], 'id' );
			if ( ! in_array( $_post['member-type'], $valid_member_types ) ) {
				return false;
			}

		} else {
			// there are no member types for this price point so make sure the submitted value is 0
			$_post['member-type'] = 0;
		}

		if ( $options = self::get_meta( '_ntnlr_registration_options' ) ) {
			// maker sure we are dealing with valid options
			$_post['options'] = array_intersect( $_post['options'], wp_list_pluck( $options, 'id' ) );

			foreach( $_post['options'] as $key => $option ) {

				$option_meta = $options[ array_search( $option, wp_list_pluck( $options, 'id' ) ) ];

				// this option has no restrictions
				if ( empty( $option_meta['restricted'] ) ) {
					continue;
				}

				// if this option does not meet restriction, remove
				if ( ! ( in_array( $_post['price-point'], $option_meta['restricted'] ) || in_array( $_post['member-type'], $option_meta['restricted'] ) ) ) {
					unset( $_post['options'][$key]);
				}
			}

		}


		if ( ! $edd_id = self::get_meta( '_ntnlr_edd_id' ) ) {
			return false;
		}

		$registration_meta = get_post_meta( $attendee->ID, 'ntnlr_registration_meta', true );

		// remove any
		if ( isset( $registration_meta[ self::$_page_id ]['cart_key'] ) ) {
			foreach( $registration_meta[ self::$_page_id ]['cart_key'] as $key ) {
				edd_remove_from_cart( $key );
			}
		}

		// make sure we remove any existing items for this attendee
		if ( $items = edd_get_cart_contents() ) {
			// remove contents from bottom up to preserve keys
			$items = array_reverse( $items, true );
			foreach( $items as $key => $item ) {
				if ( isset( $item['options']['attendee'] ) && $item['options']['attendee'] == $attendee->ID ) {
					edd_remove_from_cart( $key );
				}
			}
		}

		$cart['price_id']   = $_post['options'];
		$cart['price_id'][] = $_post['price-point'];

		foreach( $cart['price_id'] as $item ) {
			edd_add_to_cart( $edd_id, array( 'price_id' => $item, 'attendee' => $attendee->ID ) );
		}

		$registration_meta[ self::$_page_id ] = array(
			'price_point' => sanitize_text_field( $_post['price-point'] ),
			'member_type' => sanitize_text_field( $_post['member-type'] ),
			'options'     => array_filter( $_post['options'], 'sanitize_text_field' ),
		);

		update_post_meta( $attendee->ID, 'ntnlr_registration_meta', $registration_meta );
		update_post_meta( $attendee->ID, 'ntnlr_registration_price_point_' . self::$_page_id, $registration_meta[ self::$_page_id ]['price_point'] );
		update_post_meta( $attendee->ID, 'ntnlr_registration_member_type_' . self::$_page_id, $registration_meta[ self::$_page_id ]['member_type'] );

		$_GET['step'] = ( isset( $_GET['step'] ) ) ? intval( $_GET['step'] ) + 1 : 1;

		$redirect = add_query_arg( $_GET, get_permalink() ) . self::$_reg_anchor;
		wp_safe_redirect( $redirect );
		exit;

	}

	/**
	 * Store payment id with attendee meta
	 *
	 * @param $payment_id
	 */
	public function store_payment_info( $payment_id ) {
		$cart_details   = edd_get_payment_meta_cart_details( $payment_id );
		foreach( $cart_details as $item ) {

			// was this item attached to an attendee?
			$attendee_id = ( empty( $item['item_number']['options']['attendee'] ) ) ? false : (int) $item['item_number']['options']['attendee'];
			if ( ! $attendee_id ) {
				continue;
			}

			$payment_ids = get_post_meta( $attendee_id, 'payment_ids', true );

			// has this payment id already been recorded?
			if ( false !== array_search( $payment_id, (array) $payment_ids ) ) {
				continue;
			}

			$payment_ids[ self::$_page_id ] = $payment_id;
			update_post_meta( $attendee_id, 'payment_ids', $payment_ids );
		}
	}

	/**
	 * Customize Report Field
	 *
	 * @param $value
	 * @param $field
	 * @param $attendee_id
	 *
	 * @return mixed
	 */
	public function report_fields( $value, $field, $attendee_id ) {

		if ( strpos( $field[0], 'congregation' ) && ! empty( $_GET['view'] ) && 'general-report' == $_GET['view'] ) {
			$value = str_replace( "St Luke's", "St Lukes", $value );
			$value = str_replace( "Our Saviour's", "Our Saviours", $value );

			$congregation = explode( "'", $value );

			$congregation[0] = str_replace( "St Lukes", "St Luke's", $congregation[0] );
			$congregation[0] = str_replace( "Our Saviours", "Our Saviour's", $congregation[0] );

			if ( isset( $field[1], $congregation[2] ) && 'Conference' == $field[1] ) {
				return $congregation[2];
			} else {
				return $congregation[0];
			}
		}

		if ( strpos( $field[0], 'congregation' ) && ! empty( $_GET['view'] ) && 'attendee-report' == $_GET['view'] ) {

			$value = str_replace( "St Luke's", "St Lukes", $value );
			$value = str_replace( "Our Saviour's", "Our Saviours", $value );

			$congregation = explode( "'", $value );

			$congregation[0] = str_replace( "St Lukes", "St Luke's", $congregation[0] );
			$congregation[0] = str_replace( "Our Saviours", "Our Saviour's", $congregation[0] );

			$cong = array();

			if ( ! empty( $congregation[0] ) ) {
				$cong[] = 'Full Name: ' . $congregation[0];
			}

			if ( ! empty( $congregation[1] ) ) {
				$cong[] = 'Congregation ID: ' . $congregation[1];
			}

			if ( ! empty( $congregation[2] ) ) {
				$cong[] = 'Mission Conference: ' . $congregation[2];
			}
			if ( ! empty( $congregation[3] ) ) {
				$cong[] = 'Non-Geo Mission Conf: ' . $congregation[3];
			}
			if ( ! empty( $congregation[9] ) ) {
				$cong[] = 'Voting Delegates: ' . $congregation[9];
			}

			return implode( '<br />', $cong );

		}


		if ( strpos( $field[0], 'price_point' ) ) {
			$price_point = self::get_price_point( $value );
			return $price_point['title'];
		}

		if ( strpos( $field[0], 'member_type' ) ) {
			$member_type = self::get_member_type( $value );
			return $member_type['title'];
		}

		return $value;
	}

	/**
	 * Add class to body if this is a view page
	 *
	 * @param $classes
	 *
	 * @return array
	 */
	public function view_class( $classes ) {
		if ( $this->is_view() ) {
			$classes[] = 'ntnlr-view';
			if ( isset( $_GET['view'] ) ) {
				$classes[] = esc_attr( $_GET['view'] );
			}
		}

		return $classes;

	}

	protected function view_password_required() {

		if ( ! $password = get_post_meta( self::$_page_id, NTNLR_Meta_Boxes::$_prefix . 'registration_report_password', true ) ) {
			return false;
		}

		if ( ! isset( $_COOKIE['wp-general-view-' . self::$_page_id . COOKIEHASH] ) ) {
			return true;
		}

		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new PasswordHash( 8, true );

		$hash = wp_unslash( $_COOKIE['wp-general-view-' . self::$_page_id . COOKIEHASH] );
		if ( 0 !== strpos( $hash, '$P$B' ) ) {
			return true;
		}

		return ! $hasher->CheckPassword( $password, $hash );
	}

	protected function the_view_password_form() {
		$label = 'pwbox-' . self::$_page_id; ?>

		<form action="" class="post-password-form" method="post">
			<?php wp_nonce_field( 'ntnlr_password_enter', 'ntnlr_password_nonce' ); ?>
			<p>This view is password protected. To view it please enter your password below:</p>
			<p><label for="<?php echo $label; ?>">Password: <input name="ntnlr_view_password" id="<?php echo $label; ?>" type="password" size="20" /></label> <input type="submit" name="Submit" value="Submit" /></p>
		</form>

		<?php
	}

	public function set_view_password_cookie() {

		if ( ! isset( $_POST['ntnlr_password_nonce'], $_POST['ntnlr_view_password'] ) ) {
			return;
		}

		if ( empty( $_POST['ntnlr_password_nonce'] ) || ! wp_verify_nonce( $_POST['ntnlr_password_nonce'], 'ntnlr_password_enter' ) ) {
			return;
		}

		require_once ABSPATH . WPINC . '/class-phpass.php';
		$hasher = new PasswordHash( 8, true );
		$expire = apply_filters( 'ntnlr_view_password_expires', time() + 10 * DAY_IN_SECONDS );
		$secure = ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) );
		setcookie( 'wp-general-view-' . self::$_page_id . COOKIEHASH, $hasher->HashPassword( wp_unslash( $_POST['ntnlr_view_password'] ) ), $expire, COOKIEPATH, COOKIE_DOMAIN, $secure );

		wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
		exit();
	}

}