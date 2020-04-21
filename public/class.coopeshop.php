<?php
class CoopEShop {

	const REQUIRED_PLUGINS = array(
		'Contact Form 7'     => 'contact-form-7/wp-contact-form-7.php'
	);

	public static function init() {
		self::init_includes();
		self::init_hooks();
		self::load_modules();

		do_action( 'coopeshop-init' );
	}

	public static function admin_init() {
		if(! class_exists('CoopEShop_Admin')){
			require_once( COOPESHOP_PLUGIN_DIR . '/admin/class.coopeshop-admin.php' );
			CoopEShop_Admin::init();
		}
	}

	public static function init_includes() {

		//TODO seulemet à l'activation / desactivation, non ? pourtant sans ça, le menu Fournisseurs n'est plus visible
		self::register_post_types();

		require_once( COOPESHOP_PLUGIN_DIR . '/public/shortcode.antispam.php' );
		require_once( COOPESHOP_PLUGIN_DIR . '/public/shortcode.toggle.php' );

		require_once( COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop-user.php' );
		add_action( 'coopeshop-init', array( 'CoopEShop_User', 'init' ) );

		require_once( COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop-fournisseur.php' );
		add_action( 'coopeshop-init', array( 'CoopEShop_Fournisseur', 'init' ) );

		require_once( COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop-fournisseur-shortcodes.php' );
		add_action( 'coopeshop-init', array( 'CoopEShop_Fournisseur_Shortcodes', 'init' ) );
	}

	public static function init_hooks() {

		add_action( 'wp_enqueue_scripts', array(__CLASS__, 'register_plugin_styles') );
		add_action( 'wp_enqueue_scripts', array(__CLASS__, 'register_plugin_js') );
		add_action( 'plugins_loaded', array(__CLASS__, 'load_plugin_textdomain') );

		//Test
		//add_action( 'get_template_part', array( 'CoopEShop', 'callback_function' ));
		//add_filter( 'the_content', array( 'CoopEShop', 'callback_function' ));


		//Contact Form 7 hooks
		add_filter( 'wp_mail', array(__CLASS__, 'wp_mail_check_headers_cb'), 10,1);

		// Interception des emails en localhost
		if( WP_DEBUG && in_array( $_SERVER['REMOTE_ADDR'], array( '127.0.0.1', '::1' ) ) ) {
			add_filter( 'wp_mail', array(__CLASS__, 'wp_mail_localhost'), 100, 1);
		}

		add_action( 'validate_password_reset', array(__CLASS__, 'validate_password_reset'), 100, 2 );
	}

	public static function wp_mail_localhost($args){
		echo "<h1>Interception des emails en localhost.</h1>";
		print_r(sprintf("%s : %s<br>\n", 'To', $args["to"]));
		print_r(sprintf("%s : %s<br>\n", 'Subject', $args["subject"]));
		print_r(sprintf("%s : <code>%s</code><br>\n", 'Message', preg_replace('/html\>/', 'code>', $args["message"] )));
		print_r(sprintf("%s : %s<br>\n", 'Headers', $args['headers']));
		//Cancels email without noisy error and clear log
		$args["to"] = '';
		$args["subject"] = '(localhost)';
		$args["message"] = '';
		$args['headers'] = '';
	    return $args;
	}

	public static function validate_password_reset ( $errors, $user ){
		// En retour de validation d'un nouveau mot de passe, redirige. Utile en multisites.
		if ( isset($_GET['action']) && $_GET['action'] == 'resetpass' 
		&&	( ! $errors->has_errors() )
		&& isset( $_POST['pass1'] ) && ! empty( $_POST['pass1'] ) 
		&& isset( $_POST['redirect_to'] ) && ! empty( $_POST['redirect_to'] ) ) {
			//code from wp-login.php
			list( $rp_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );
			$rp_cookie       = 'wp-resetpass-' . COOKIEHASH;
			reset_password( $user, $_POST['pass1'] );
			setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );

			wp_redirect( $_POST['redirect_to'] );
			/*login_header( __( 'Password Reset' ), '<p class="message reset-pass">' . __( 'Your password has been reset.' ) . ' <a href="' . esc_url( wp_login_url() ) . '">' . __( 'Log in' ) . '</a></p>' );
			login_footer();*/
			exit;
		}
	}

	public static function wp_mail_check_headers_cb($args) {
		if( ! $args['headers']){
			$args['headers'] = sprintf('From: %s', get_bloginfo('admin_email'));
		}
	    return $args;
	}

	public static function load_plugin_textdomain() {

	    load_plugin_textdomain( COOPESHOP_PLUGIN_NAME, FALSE, COOPESHOP_PLUGIN_DIR . '/languages/' );
	}
 
	public static function callback_function( $content ){
		/*var_dump( func_get_args() );*/
 
    	return $content;
	}

	public static function load_modules() {
		//self::load_module( 'fournisseur' );
	}

	protected static function load_module( $mod ) {
		$dir = COOPESHOP_PLUGIN_MODULES_DIR;

		if ( empty( $dir ) or ! is_dir( $dir ) ) {
			return false;
		}

		$file = path_join( $dir, $mod . '.php' );

		if ( file_exists( $file ) ) {
			include_once $file;
		}
	}

	/**
	 * Registers stylesheets.
	 */
	public static function register_plugin_styles() {
	    wp_register_style( 'coopeshop', plugins_url( 'coopeshop/public/css/coopeshop.css' ), array(), COOPESHOP_VERSION , 'all' );
	    wp_enqueue_style( 'coopeshop');
	}

	/**
	 * Registers js files.
	 */
	public static function register_plugin_js() {
	    wp_register_script( 'coopeshop', plugins_url( 'coopeshop/public/js/coopeshop.js' ), array(), COOPESHOP_VERSION , 'all' );
	    wp_enqueue_script( 'coopeshop' );
	}


	/**
	 * Retourne la valeur d'un paramétrage.
	 * Cf CoopEShop_Admin_Menu
	 */
	public static function get_option( $name, $default = false ) {
		$options = get_option( COOPESHOP_TAG );

		if ( false === $options ) {
			return $default;
		}

		if ( isset( $options[$name] ) ) {
			return $options[$name];
		} else {
			return $default;
		}
	}

	/**
	 * Enregistre la valeur d'un paramétrage.
	 * Cf CoopEShop_Admin_Menu
	 */
	public static function update_option( $name, $value ) {
		$options = get_option( COOPESHOP_TAG );
		$options = ( false === $options ) ? array() : (array) $options;
		$options = array_merge( $options, array( $name => $value ) );
		update_option( 'coopeshop', $options );
	}

	/**
	 * Returns then number of fournisseur this user manages
	 */
	public static function user_is_fournisseur( ) {
		//TODO
		return 1;
	}

////////////////////////////////////////////////////////////////////////////////////////////
/**
* 
*/

	/**
	 * Attached to activate_{ plugin_basename( __FILES__ ) } by register_activation_hook()
	 * @static
	 */
	public static function plugin_activation() {
		$missing_plugins = array();

		if ( version_compare( $GLOBALS['wp_version'], COOPESHOP_MINIMUM_WP_VERSION, '<' ) ) {
			load_plugin_textdomain( 'coopeshop' );
			
			$message = '<strong>'.sprintf(esc_html__( 'CoopEShop %s nécessite une version de WordPress %s ou plus.' , 'coopeshop'), COOPESHOP_VERSION, COOPESHOP_MINIMUM_WP_VERSION ).'</strong> ';

			die( sprintf('<div class="error notice"><p>%s</p></div>', $message) );

		} elseif ( count($missing_plugins = self::get_missing_plugins_list()) ) {
			load_plugin_textdomain( 'coopeshop' );
			
			$message = '<strong>'.sprintf(esc_html__( 'CoopEShop nécessite l\'extension "%s"' , 'coopeshop'), $missing_plugins[0] ).'</strong> ';

			die( sprintf('<div class="error notice"><p>%s</p></div>', $message) );
		
		} elseif ( ! empty( $_SERVER['SCRIPT_NAME'] ) && false !== strpos( $_SERVER['SCRIPT_NAME'], '/wp-admin/plugins.php' ) ) {
			add_option( 'Activated_CoopEShop', true );
		}
		self::register_user_roles();
		self::register_post_types();
	}

	/**
	 * @return string[] Names of plugins that we require, but that are inactive.
	 */
	private static function get_missing_plugins_list() {
		$missing_plugins = array();
		foreach ( self::REQUIRED_PLUGINS as $plugin_name => $main_file_path ) {
			if ( ! self::is_plugin_active( $main_file_path ) ) {
				$missing_plugins[] = $plugin_name;
			}
		}
		return $missing_plugins;
	}

	/**
	 * @param string $main_file_path Path to main plugin file, as defined in self::REQUIRED_PLUGINS.
	 *
	 * @return bool
	 */
	private static function is_plugin_active( $main_file_path ) {
		return in_array( $main_file_path, self::get_active_plugins() );
	}

	/**
	 * @return string[] Returns an array of active plugins' main files.
	 */
	private static function get_active_plugins() {
		return apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
	}

	/**
	 * Removes all connection options
	 * @static
	 */
	public static function plugin_deactivation( ) {
		
		// Remove any scheduled cron jobs.
		$coopeshop_cron_events = array(
			'coopeshop_schedule_cron_recheck',
			'coopeshop_scheduled_delete',
		);
		
		foreach ( $coopeshop_cron_events as $coopeshop_cron_event ) {
			$timestamp = wp_next_scheduled( $coopeshop_cron_event );
			
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, $coopeshop_cron_event );
			}
		}
		self::unregister_post_types();
		self::unregister_user_roles();
	}

	/**
	 * register_post_types
	 */
	private static function include_and_init($class_name){
		if(! class_exists($class_name)){
			switch($class_name){
				case 'CoopEShop_Post_Types':
		 			$file = COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop-post_types.php';
					break;

				case 'CoopEShop_Fournisseur_Post_type':
		 			$file = COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop-fournisseur-post_type.php';
					break;
				
				default:
					var_dump($class_name);//show calls stack
					die(sprintf('Classe inconnue : "%s"', $class_name));
			}
			require_once( $file );
			if(method_exists($class_name, 'init'))
				$class_name::init();
		}
	}

	/**
	 * Register user roles
	 */
	private static function register_user_roles(){
		self::include_and_init('CoopEShop_Fournisseur_Post_type');
		CoopEShop_Fournisseur_Post_type::register_user_role();
	}

	/**
	 * Unregister user roles
	 */
	private static function unregister_user_roles(){
		remove_role( 'fournisseur');
	}

	/**
	 * register_post_types
	 */
	private static function register_post_types(){
		self::include_and_init('CoopEShop_Post_Types');
		CoopEShop_Post_Types::register_post_types();
	}

	/**
	 * unregister_post_types
	 */
	private static function unregister_post_types(){
		self::include_and_init('CoopEShop_Post_Types');
		CoopEShop_Post_Types::unregister_post_types();
	}
}