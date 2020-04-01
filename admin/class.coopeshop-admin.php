<?php


class CoopEShop_Admin {

	public static function init() {
		self::init_includes();
		self::init_hooks();

		do_action( 'coopeshop-admin_init' );
	}

	public static function init_includes() {	

		require_once( COOPESHOP_PLUGIN_DIR . '/admin/class.coopeshop-admin-menu.php' );
		CoopEShop_Admin_Menu::init();

		require_once( COOPESHOP_PLUGIN_DIR . '/admin/class.coopeshop-admin-user.php' );
		add_action( 'coopeshop-admin_init', array( 'CoopEShop_Admin_User', 'init' ) );

		require_once( COOPESHOP_PLUGIN_DIR . '/admin/class.coopeshop-admin-fournisseur.php' );
		add_action( 'coopeshop-admin_init', array( 'CoopEShop_Admin_Fournisseur', 'init' ) );

		require_once( COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop-fournisseur-menu.php' );
		add_action( 'coopeshop-admin_init', array( 'CoopEShop_Admin_Fournisseur_Menu', 'init' ) );
	}

	public static function init_hooks() {

	    add_action( 'admin_enqueue_scripts', array(__CLASS__, 'register_plugin_styles') );

	}

	/**
	 * Registers a stylesheet.
	 */
	public static function register_plugin_styles() {
	    wp_register_style( 'coopeshop', plugins_url( 'coopeshop/admin/css/coopeshop-admin.css' ), array(), COOPESHOP_VERSION , 'all'  );
	    wp_enqueue_style( 'coopeshop');
	}
}