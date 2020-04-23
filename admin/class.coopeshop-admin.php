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

		if( WP_DEBUG || is_multisite()){
			require_once( COOPESHOP_PLUGIN_DIR . '/admin/class.coopeshop-admin-multisite.php' );
			add_action( 'coopeshop-admin_init', array( 'CoopEShop_Admin_Multisite', 'init' ) );
		}

		require_once( COOPESHOP_PLUGIN_DIR . '/admin/class.coopeshop-admin-fournisseur.php' );
		add_action( 'coopeshop-admin_init', array( 'CoopEShop_Admin_Fournisseur', 'init' ) );

		require_once( COOPESHOP_PLUGIN_DIR . '/admin/class.coopeshop-admin-edit-fournisseur.php' );
		add_action( 'coopeshop-admin_init', array( 'CoopEShop_Admin_Edit_Fournisseur', 'init' ) );

		require_once( COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop-fournisseur-menu.php' );
		add_action( 'coopeshop-admin_init', array( 'CoopEShop_Admin_Fournisseur_Menu', 'init' ) );
	}

	public static function init_hooks() {

	    add_action( 'admin_enqueue_scripts', array(__CLASS__, 'register_plugin_styles') );

        add_action( 'admin_notices', array(__CLASS__,'show_admin_notices') );

	}

	/**
	 * Registers a stylesheet.
	 */
	public static function register_plugin_styles() {
	    wp_register_style( 'coopeshop', plugins_url( 'coopeshop/admin/css/coopeshop-admin.css' ), array(), COOPESHOP_VERSION , 'all'  );
	    wp_enqueue_style( 'coopeshop');
	}



	/**
	 * admin_notices tag
	 */
	private static function admin_notices_tag(){
		return 'COO_ADMIN_NOTICES_' . get_current_user_id();
	}
	/**
	 *
	 * $type : success, warning, error
	 */
	public static function add_admin_notice( $msg, $type = 'success'){
		if( ! is_admin())
			return;
		
		$notices = get_transient(self::admin_notices_tag());
		if( ! is_array($notices))
			$notices = array();
		$notices[] = array(
			'message' => $msg,
			'type' => $type,
		);
		set_transient(self::admin_notices_tag(), $notices, 5);
	}
	public static function show_admin_notices(){
		$notices = get_transient(self::admin_notices_tag());
		if(is_array($notices)){
			foreach($notices as $notice){
				$class = 'notice notice-' . $notice['type'];
	    		$message = __( $notice['message'], COOPESHOP_TAG );
	    		if( is_wp_error($message)) {
					$message = $message->get_error_messages(); 
				}
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
			}
		}
		self::clear_admin_notices();
	}

	public static function clear_admin_notices(){
		delete_transient(self::admin_notices_tag());
	}
}