<?php
/**
 * Plugin Name: NTNL Registration
 * Description: Handle event registration for NTNL. Uses EDD and Gravity Forms.
 * Version:     0.1.0
 * Author:      Tanner Moushey
 * Author URI:  http://tannermoushey.com
 * License:     GPLv2+
 * Text Domain: ntnlr
 * Domain Path: /languages
 */

/**
 * Copyright (c) 2015 Tanner Moushey (email : tanner@iwitnessdesign.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Built using grunt-wp-plugin
 * Copyright (c) 2013 10up, LLC
 * https://github.com/10up/grunt-wp-plugin
 */

// Useful global constants
define( 'NTNLR_VERSION', '0.1.0' );
define( 'NTNLR_URL',     plugin_dir_url( __FILE__ ) );
define( 'NTNLR_PATH',    dirname( __FILE__ ) . '/' );

include( NTNLR_PATH . 'includes/cpt.php'          );
include( NTNLR_PATH . 'includes/meta-boxes.php'   );
include( NTNLR_PATH . 'includes/output.php'       );
include( NTNLR_PATH . 'includes/user-login.php'   );
include( NTNLR_PATH . 'includes/checkin.php'      );
include( NTNLR_PATH . 'includes/attendee-csv.php' );

/**
 * Default initialization for the plugin:
 * - Registers the default textdomain.
 */
function ntnlr_init() {
	$locale = apply_filters( 'plugin_locale', get_locale(), 'ntnlr' );
	load_textdomain( 'ntnlr', WP_LANG_DIR . '/ntnlr/ntnlr-' . $locale . '.mo' );
	load_plugin_textdomain( 'ntnlr', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'ntnlr_init' );

/**
 * Activate the plugin
 */
function ntnlr_activate() {
	// First load the init scripts in case any rewrite functionality is being loaded
	ntnlr_init();

	remove_role( NTNLR_User_Login::$_registrant );

	add_role(
		NTNLR_User_Login::$_registrant,
		__( 'Registrant' ),
		array(
			'read' => true,
			'edit_posts' => true,
		)
	);

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'ntnlr_activate' );

/**
 * Deactivate the plugin
 * Uninstall routines should be in uninstall.php
 */
function ntnlr_deactivate() {

}
register_deactivation_hook( __FILE__, 'ntnlr_deactivate' );