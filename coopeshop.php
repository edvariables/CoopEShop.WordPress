<?php
/**
 * @package CoopEShop
 */
/*
 * Plugin Name: CoopEShop
 * Plugin URI: https://github.com/edvariables/CoopEShop.WordPress
 * Description: CoopEShop, l'échoppe en coop'. Permet la création de pages de fournisseurs. Ces fournisseurs peuvent avoir un compte WordPress de type Author pour ne modifier que leur page.
 Only in french language...
 * Author: Emmanuel Durand
 * Author URI: http://wp.coopeshop.net
 * Tags: 
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Version: 1.0.6
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'COOPESHOP_VERSION', '1.0.6' );
define( 'COOPESHOP_MINIMUM_WP_VERSION', '4.0' );

define( 'COOPESHOP_PLUGIN', __FILE__ );
define( 'COOPESHOP_PLUGIN_BASENAME', plugin_basename( COOPESHOP_PLUGIN ) );
define( 'COOPESHOP_PLUGIN_NAME', trim( dirname( COOPESHOP_PLUGIN_BASENAME ), '/' ) );
define( 'COOPESHOP_PLUGIN_DIR', untrailingslashit( dirname( COOPESHOP_PLUGIN ) ) );
define( 'COOPESHOP_PLUGIN_MODULES_DIR', COOPESHOP_PLUGIN_DIR . '/modules' );

define( 'COOPESHOP_TAG', strtolower(COOPESHOP_PLUGIN_NAME) ); //coopeshop
define( 'COOPESHOP_EMAIL_DOMAIN', COOPESHOP_TAG . '.net' ); //coopeshop.net

// see translate_level_to_role()
define( 'USER_LEVEL_ADMIN', 8 ); 
define( 'USER_LEVEL_EDITOR', 5 ); 
define( 'USER_LEVEL_AUTHOR', 2 ); 
define( 'USER_LEVEL_CONTRIBUTOR', 1 ); 
define( 'USER_LEVEL_SUBSCRIBER', 0 ); 
define( 'USER_LEVEL_NONE', 0 ); 

require_once( COOPESHOP_PLUGIN_DIR . '/includes/functions.php' );
require_once( COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop.php' );

//plugin_activation
register_activation_hook( __FILE__, array( 'CoopEShop', 'plugin_activation' ) );
//plugin_deactivation
register_deactivation_hook( __FILE__, array( 'CoopEShop', 'plugin_deactivation' ) );

add_action( 'admin_menu', 'coopeshop_admin_menu' );
function coopeshop_admin_menu(){
	require_once( COOPESHOP_PLUGIN_DIR . '/admin/class.coopeshop-admin-menu.php' );
	CoopEShop_Admin_Menu::init();
}

add_action( 'init', array( 'CoopEShop', 'init' ) );
add_action( 'admin_init', array( 'CoopEShop', 'admin_init' ) );

