<?php
/**
 * Register provider post types and taxonomies.
 * ED200325
 */
class CoopEShop_Post_Types {

	public static function init() {
		self::init_includes();
	}

	public static function init_includes() {
		if(!class_exists('CoopEShop_Fournisseur'))
			require_once( COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop-fournisseur.php' );
		require_once( COOPESHOP_PLUGIN_DIR . '/public/class.coopeshop-fournisseur-post_type.php' );
	}

	/**
	 * Register post types and taxonomies.
	 */
	public static function register_post_types() {

		do_action( 'coopeshop_register_post_types' );

		CoopEShop_Fournisseur_Post_type::register_post_type();
		CoopEShop_Fournisseur_Post_type::register_taxonomy_type_fournisseur();

	    // clear the permalinks after the post type has been registered
	    flush_rewrite_rules();

		do_action( 'coopeshop_after_register_post_types' ); 
	}

	/**
	 * Unregister post types and taxonomies.
	 */
	public static function unregister_post_types() {

		do_action( 'coopeshop_unregister_post_types' );

		if ( post_type_exists( CoopEShop_Fournisseur::post_type ) ) 
    		unregister_post_type(CoopEShop_Fournisseur::post_type);    
		if ( taxonomy_exists( CoopEShop_Fournisseur::taxonomy_type_fournisseur ) ) 
			unregister_taxonomy(CoopEShop_Fournisseur::taxonomy_type_fournisseur);

		// clear the permalinks to remove our post type's rules from the database
    	flush_rewrite_rules();

		do_action( 'coopeshop_after_unregister_post_types' );
	}
}
