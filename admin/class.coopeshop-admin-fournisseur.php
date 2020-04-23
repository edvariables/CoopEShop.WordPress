<?php

/**
 * CoopEShop Admin -> Fournisseur
 * Custom post type for WordPress in Admin UI.
 * 
 * Capabilities
 * Colonnes de la liste des fournisseurs
 * Dashboard
 *
 * Voir aussi CoopEShop_Fournisseur
 */
class CoopEShop_Admin_Fournisseur {

	public static function init() {

		self::init_hooks();
	}

	public static function init_hooks() {
		add_action( 'admin_head', array(__CLASS__, 'init_post_type_supports'), 10, 4 );
		add_filter( 'map_meta_cap', array(__CLASS__, 'map_meta_cap'), 10, 4 );
		
		//add custom columns for list view
		add_filter( 'manage_' . CoopEShop_Fournisseur::post_type . '_posts_columns', array( __CLASS__, 'manage_columns' ) );
		add_action( 'manage_' . CoopEShop_Fournisseur::post_type . '_posts_custom_column', array( __CLASS__, 'manage_custom_columns' ), 10, 2 );
		//set custom columns sortable
		add_filter( 'manage_edit-' . CoopEShop_Fournisseur::post_type . '_sortable_columns', array( __CLASS__, 'manage_sortable_columns' ) );

		add_action( 'wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widgets'), 10 ); //dashboard
	}
	/****************/

	/**
	 * Liste de fournisseurs
	 */
	public static function manage_columns( $columns ) {
		unset( $columns );
		$columns = array(
			'title'     => __( 'Titre', COOPESHOP_TAG ),
			'author'        => __( 'Auteur', COOPESHOP_TAG ),
			'details'     => __( 'Contact', COOPESHOP_TAG ),
			'type_fournisseur'     => __( 'Type de fournisseur', COOPESHOP_TAG ),
			'date'      => __( 'Date', COOPESHOP_TAG ),
		);
		return $columns;
	}

	public static function manage_custom_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'type_fournisseur' :
				the_terms( $post_id, 'type_fournisseur', '<cite class="entry-terms">', ', ', '</cite>' );
				break;
			case 'details' :
				$nom_h = get_post_meta( $post_id, 'f-nom_humain', true );
				$email    = get_post_meta( $post_id, 'f-email', true );
				$email2    = get_post_meta( $post_id, 'f-email2', true );
				$tel    = get_post_meta( $post_id, 'f-telephone', true );
				$tel2    = get_post_meta( $post_id, 'f-telephone2', true );
				echo trim(
					  ($nom_h ? $nom_h . ' - ' : '')
					. ($email ? make_clickable( $email ) : '')
					. ($email2 ? ' - ' . make_clickable($email2) : '')
					. ($tel ? ' - ' . $tel : '')
					. ($tel2 ? ' - ' . $tel2 : '')
				);
				break;
		}
	}

	public static function manage_sortable_columns( $columns ) {
		$columns['author']    = 'author';
		$columns['details'] = 'details';
		return $columns;
	}
	/****************/

	/**
	 * N'affiche le bloc Auteur qu'en Archive (liste) / modification rapide
	 * N'affiche l'éditeur que pour le fournisseur modèle ou si l'option CoopEShop::fournisseur_show_content_editor
	 */
	public static function init_post_type_supports(){
		global $post;
		if( current_user_can('manage_options') ){
			if(is_archive()){
				add_post_type_support( 'fournisseur', 'author' );
			}
			/*if($post && $post->ID == CoopEShop_Fournisseur::get_fournisseur_model_post_id()
			|| CoopEShop::get_option('fournisseur_show_content_editor'))
				add_post_type_support( 'fournisseur', 'editor' );*/
		}
	}

	/**
	 * map_meta_cap
	 TODO all
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {

		if( 0 ) {
			echo "<br>\n-------------------------------------------------------------------------------";
			print_r(func_get_args());
			/*echo "<br>\n-----------------------------------------------------------------------------";
			print_r($caps);*/
		}
		if($cap == 'edit_fournisseurs'){
			//var_dump($cap, $caps);
					$caps = array();
					//$caps[] = ( current_user_can('manage_options') ) ? 'read' : 'do_not_allow';
					$caps[] = 'read';
			return $caps;
		}
		/* If editing, deleting, or reading a fournisseur, get the post and post type object. */
		if ( 'edit_fournisseur' == $cap || 'delete_fournisseur' == $cap || 'read_fournisseur' == $cap ) {
			$post = get_post( $args[0] );
			$post_type = get_post_type_object( $post->post_type );

			/* Set an empty array for the caps. */
			$caps = array();
		}

		/* If editing a fournisseur, assign the required capability. */
		if ( 'edit_fournisseur' == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->edit_posts;
			else
				$caps[] = $post_type->cap->edit_others_posts;
		}

		/* If deleting a fournisseur, assign the required capability. */
		elseif ( 'delete_fournisseur' == $cap ) {
			if ( $user_id == $post->post_author )
				$caps[] = $post_type->cap->delete_posts;
			else
				$caps[] = $post_type->cap->delete_others_posts;
		}

		/* If reading a private fournisseur, assign the required capability. */
		elseif ( 'read_fournisseur' == $cap ) {

			if ( 'private' != $post->post_status )
				$caps[] = 'read';
			elseif ( $user_id == $post->post_author )
				$caps[] = 'read';
			else
				$caps[] = $post_type->cap->read_private_posts;
		}

		/* Return the capabilities required by the user. */
		return $caps;
	}

	/**
	 * dashboard_widgets
	 */

	/**
	 * Init
	 */
	public static function add_dashboard_widgets() {
	    global $wp_meta_boxes;
	    $fournisseurs = self::get_posts(array( 'author' => get_current_user_id() ));
		if( count($fournisseurs) ) {
			add_meta_box( 'dashboard_my_fournisseurs',
				__('Je suis fournisseur', COOPESHOP_TAG),
				array(__CLASS__, 'dashboard_my_fournisseurs_cb'),
				'dashboard',
				'normal',
				'high',
				array('fournisseurs' => $fournisseurs) );
		}

		if(current_user_can('manage_options')){
		    $fournisseurs = self::get_posts();
			if( count($fournisseurs) ) {
				add_meta_box( 'dashboard_all_fournisseurs',
					__('Les fournisseurs', COOPESHOP_TAG),
					array(__CLASS__, 'dashboard_all_fournisseurs_cb'),
					'dashboard',
					'side',
					'high',
					array('fournisseurs' => $fournisseurs) );
			}
		}
	}

	/**
	 * Callback
	 */
	public static function dashboard_my_fournisseurs_cb($post , $widget) {
		$fournisseurs = $widget['args']['fournisseurs'];
		?><ul><?php
		foreach($fournisseurs as $fournisseur){
			//;
			echo '<li>';
			?><header class="entry-header"><?php 
				edit_post_link( $fournisseur->post_title, '<h2 class="entry-title">', '</h2>', $fournisseur );
				the_terms( $fournisseur->ID, 'type_fournisseur', 
					sprintf( '<div><cite class="entry-terms">' ), ', ', '</cite></div>' );
				$the_date = get_the_date('', $fournisseur);
				$the_modified_date = get_the_modified_date('', $fournisseur);
				$html = sprintf('<span>ici depuis le %s</span>', $the_date) ;
				if($the_date != $the_modified_date)
					$html .= sprintf('<span>, mise à jour le %s</span>', $the_modified_date) ;		
				edit_post_link( $html, '<cite>', '</cite><hr>', $fournisseur );			
			?></header><?php
			?><div class="entry-summary">
				<?php echo get_the_excerpt($fournisseur); //TODO empty !!!!? ?>
			</div><?php
			echo '</li>';
			
		}
		?></ul><?php
	}

	/**
	 * Callback
	 */
	public static function dashboard_all_fournisseurs_cb($post , $widget) {
		$fournisseurs = $widget['args']['fournisseurs'];
		$today_date = date(get_option( 'date_format' ));
		$max_rows = 4;
		$post_statuses = get_post_statuses();
		?><ul><?php
		foreach($fournisseurs as $fournisseur){
			echo '<li>';
			edit_post_link( $fournisseur->post_title, '<h3 class="entry-title">', '</h3>', $fournisseur );	
			$the_date = get_the_date('', $fournisseur);
			$the_modified_date = get_the_modified_date('', $fournisseur);
			$html = '';
			if($fournisseur->post_status != 'publish')
				$html .= sprintf('<b>%s</b> - ', $post_statuses[$fournisseur->post_status]) ;
			$html .= sprintf('<span>ici depuis le %s</span>', $the_date) ;
			if($the_date != $the_modified_date)
				$html .= sprintf('<span>, mise à jour le %s</span>', $the_modified_date) ;		
			edit_post_link( $html, '<cite>', '</cite><hr>', $fournisseur );			
			echo '</li>';

			if( --$max_rows <= 0 && $the_modified_date != $today_date )
				break;
		}
		echo sprintf('<li><a href="%s">%s...</a></li>', get_home_url( null, 'wp-admin/edit.php?post_type=' . CoopEShop_Fournisseur::post_type), __('Tous les fournisseurs', COOPESHOP_TAG));
		?>
		</ul><?php
	}

	/**
	 * Recherche de fournisseurs
	 */
	public static function get_posts($args = null){
		if( ! is_array($args))
			$args = array();
		$args['post_type'] = CoopEShop_Fournisseur::post_type;
        $the_query = new WP_Query( $args );
        return $the_query->posts; 
    }

	/**
	 * Recherche de fournisseurs et retourne le nombre trouvé
	 */
	public static function get_found_posts($args = null){
		if( ! is_array($args))
			$args = array();
		if( ! isset($args['nopaging']) )
			$args['nopaging'] = true;
		//TODO faire un SELECT COUNT() plutôt qu'un count(SELECT)
		return count(self::get_posts($args)); 
    }

}