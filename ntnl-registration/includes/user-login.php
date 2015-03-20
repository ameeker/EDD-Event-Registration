<?php

NTNLR_User_Login::get_instance();
class NTNLR_User_Login {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * @var array
	 */
	public static $_errors = array();

	/**
	 * @var string
	 */
	public static $_registrant = 'ntnlr_registrant';

	/**
	 * Only make one instance of the NTNLR_User_Login
	 *
	 * @return NTNLR_User_Login
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof NTNLR_User_Login ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'maybe_signin_user'   ) );

		add_filter( 'show_admin_bar', array( $this, 'admin_bar_visibility' ) );
	}

	/**
	 * Sign in the user. Log them in if they are already registered.
	 */
	public function maybe_signin_user() {
		if ( empty( $_POST['ntnlr_signin'] ) ) {
			return;
		}

		if ( empty( $_POST['user_email'] ) || empty( $_POST['user_pass'] ) ) {
			self::$_errors['missing_info'] = 'Please enter a valid username and password';
			return;
		}

		if ( ! wp_verify_nonce( $_POST['ntnlr_signin'], 'ntnlr_user_signin' ) ) {
			self::$_errors['nonce'] = 'Something is not quite right. Please reload the page and try again.';
			return;
		}

		$credentials['user_login']    = $_POST['user_email'];
		$credentials['user_password'] = $_POST['user_pass'];
		$credentials['remember']      = true;

		if ( $user = get_user_by( 'email', esc_html( $_POST['user_email'] ) ) ) {
			$credentials['user_login'] = $user->user_login;
		}

		$user = wp_signon( $credentials );

		// If we have a user, we are good
		if ( $user instanceof WP_User  ) {
			wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
			exit;
		}

		// check for any errors
		if ( is_wp_error( $user ) ) {
			self::$_errors = $user->errors;
		}

		// we expect an invalid username. That's when we create a new user.
		unset( self::$_errors['invalid_username'] );

		// The username is correct but the password is wrong.
		if ( isset( self::$_errors['incorrect_password'] ) ) {
			return;
		}

		if ( ! is_email( $credentials['user_login'] ) ) {
			self::$_errors['invalid_username'] = '<strong>Error:</strong> Please enter a valid email address';
			return;
		}

		// If we don't have a user, create one
		$sanitized_user_login = str_replace( ' ', '_', $credentials['user_login'] );
		if ( sanitize_user( $sanitized_user_login, true ) !== $credentials['user_login'] ) {
			self::$_errors['invalid_username'] = 'The username you entered is not valid, please enter an email that is at least 4 characters and only contains numbers, letters, underscores("_"), and dashes("-").';
			return;
		}

		$user_login = wp_slash( $credentials['user_login'] );
		$user_email = wp_slash( $credentials['user_login'] );
		$user_pass  = $credentials['user_password'];
		$role       = self::$_registrant;

		$userdata = compact( 'user_login', 'user_email', 'user_pass', 'role' );

		$user_id = wp_insert_user($userdata);

		// check for errors
		if ( ! $user_id || is_wp_error( $user_id ) ) {
			self::$_errors['register_fail'] = 'Something went wrong. Please reload the page and try again.';
			return;
		}

		// shouldn't have any errors since we just created this user.
		wp_signon( $credentials );

		wp_safe_redirect( $_SERVER['HTTP_REFERER'] );
		exit;
	}

	/**
	 * print errors
	 */
	public static function print_errors() {
		foreach( self::$_errors as $error ) {
			if ( is_array( $error ) ) {
				$error = $error[0];
			}

			printf( '<p class="error">%s</p>', $error );
		}
	}

	/**
	 * Hide the admin bar for registrants
	 *
	 * @param $visibility
	 *
	 * @return bool
	 */
	public function admin_bar_visibility( $visibility ) {
		if ( current_user_can( self::$_registrant ) ) {
			return false;
		}

		return $visibility;
	}

}