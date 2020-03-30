<?php

/**
 * CoopEShop -> Fournisseur
 * Custom post type for WordPress.
 * 
 * Définition du Post Type fournisseur
 * Définition de la taxonomie type_fournisseur
 * Redirection des emails envoyés depuis une page Fournisseur
 * A l'affichage d'un fournisseur, le Content est remplacé par celui du fournisseur Modèle
 * En Admin, le bloc d'édition du Content est masqué d'après la définition du Post type : le paramètre 'supports' qui ne contient pas 'editor'
 *
 * Voir aussi CoopEShop_Admin_Fournisseur
 */
class CoopEShop_Fournisseur {

	const post_type = 'fournisseur';
	const taxonomy_type_fournisseur = 'type_fournisseur';


	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::$initiated = true;

			self::init_hooks();
		}
	}

	/**
	 * Hook
	 */
	public static function init_hooks() {
		add_filter( 'the_content', array(__CLASS__, 'replace_content_from_model') );
		add_action( 'save_post_fournisseur', array(__CLASS__, 'save_post_fournisseur_cb'), 10, 4 );
		//Contact Form 7 hooks
		add_filter( 'wp_mail', array(__CLASS__, 'redirect_wpcf7_mails'), 10,1);
		add_filter( 'wpcf7_verify_nonce', array(__CLASS__, 'wpcf7_verify_nonce_cb' ));
	}
	/**
	 * Callback lors de l'enregistrement d'un fournisseur.
	 * A ce stade, les metaboxes ne sont pas encore sauvegardées
	 * voir aussi CoopEShop_Admin_Fournisseur::new_post_fournisseur_cb
	 */
	public static function save_post_fournisseur_cb ($post_id, $post, $is_update){
		if( ! $is_update
		|| $post->post_status == 'trashed'){
			return;
		}
		$create_user = array_key_exists('f-create-user', $_POST) ? $_POST['f-create-user'] : false;
		if( $create_user){
			self::create_user_on_save($post_id, $post, $is_update);
		}
	}

	/**
	 * Lors du premier enregistrement, on crée l'utilisateur
	 * A ce stade, les metaboxes ne sont pas encore sauvegardées
	 */
	public static function create_user_on_save ($post_id, $post, $is_update){
			
		$email = array_key_exists('f-email', $_POST) ? $_POST['f-email'] : false;
		if(!$email || !is_email($email)) {
			show_message("Il manque l'adresse mail ou elle est incorrecte.");
			die("perdu..."); //TODO peut mieux faire...
		}
		$user_name = array_key_exists('f-nom_humain', $_POST) ? $_POST['f-nom_humain'] : false;
		$user_login = array_key_exists('f-create-user-slug', $_POST) ? $_POST['f-create-user-slug'] : false;
	
		$data = array(
			'description' => 'Fournisseur ' . $post->post_name
		);
		$user = CoopEShop_User::create_user_for_fournisseur($email, $user_name, $user_login, $data);
		if( is_wp_error($user)) {
			show_message($user);
			die("perdu..."); //TODO peut mieux faire...
		}
		if($user){
			unset($_POST['f-create-user']);

			$post->post_author = $user->ID;

			wp_update_post(array(
				'ID' => $post_id,
				'post_author' => $user->ID
			), true);
		}
		/*var_dump('user', $user);
		var_dump(func_get_args());
		var_dump(get_post_meta($post_id, 'f-email', true));
		die('save_post_fournisseur_cb');*/
	}
 
 	/////////////
 	// Modèle //

	/**
	 * Hook
	 */
 	public static function replace_content_from_model( $content ) {
 		global $post;
 		if( ! $post
 		|| $post->post_type != self::post_type)
 			return $content;
	    return self::get_fournisseur_post_model_content( );
	}
 
 	/**
 	 * Retourne l'ID du post servant de modèle
 	 */
	public static function get_fournisseur_model_post_id( ) {
		$option_id = 'fournisseur_model_post_id';
		return CoopEShop::get_option($option_id);
	}
 
 	/**
 	 * Retourne le post servant de modèle
 	 */
	public static function get_fournisseur_post_model( ) {
		return get_post(self::get_fournisseur_model_post_id());
	}
 
 	/**
 	 * Retourne le Content du post servant de modèle
 	 */
	public static function get_fournisseur_post_model_content( ) {
		$post_id = self::get_fournisseur_model_post_id();
		if($post_id){
			$html = get_the_content(null, false, $post_id);
		}
		else {
			$html = '<p class="">Le modèle de fournisseur n\'est pas défini dans le paramétrage de CoopEShop.</p>';
		}
		if( ! isset($html) || ! $html){
			$html = '<p class="">Le modèle de fournisseur est vide dans sa zone de saisie principale. 
			Peut être qu\'elle n\'est pas visible alors allez voir du côté du paramétrage de CoopEShop.
			</p>';
		}
		return $html;
	}
	// Modèle //
	///////////
 	

 	/////////////////////
 	// redirect email //

	/**
	 * Redirige les mails des pages de fournisseurs vers le mail du fournisseur de la page ou, à défaut, vers l'auteur de la page.
	 * Attentu que ce soit avec un formulaire du plugin Contact Form 7
	 */
	public static function redirect_wpcf7_mails($args){

		if(! array_key_exists('_wpcf7_container_post', $_POST))
			return $args;

		$post_id = $_POST['_wpcf7_container_post'];

		// Only from fournisseur pages
		$post = get_post($post_id);
		if( ! $post
		|| $post->post_type != self::post_type)
			return $args;

		// Change l'adresse du destinataire
		$email = get_post_meta($post_id, 'f-email', true);

		// 2ème email ?
		if( ! is_email($email)){
			$email = get_post_meta($post_id, 'f-email2', true);
		}

		if( ! is_email($email)){
			// Email de l'auteur du post
			$email = the_author_meta('email', $post->post_author);
		}
		
		if( is_email($email))
			$args['to'] = $email;

		// Change l'adresse d'expéditeur avec celui de l'utilisateur connecté
		// Change l'adresse de réponse avec celui de l'utilisateur connecté
		if(is_user_logged_in()){
			global $current_user;
			get_currentuserinfo();
			$email = $current_user->user_email;
			if( is_email($email)){
				$separator = "\n";
				$user_name = $current_user->display_name;
				$site_title = get_bloginfo( 'name', 'display' );
				$headers = explode ($separator, $args['headers']);
				$replyto_index = count($headers);
				for ($i=0; $i < count($headers); $i++) { 
					if(strcasecmp(substr($headers[$i], 0, strlen('From:')), 'From:') === 0){
						// Peut être que les serveurs n'aiment pas que l'expéditeur ne soit pas du nom de domaine
						$headers[$i] = sprintf('%s: %s - %s<%s>', 'From', esc_html($site_title), esc_html($user_name), $email);
					}
					elseif(strcasecmp(substr($headers[$i], 0, strlen('Reply-To:')), 'Reply-To:') === 0){
						$replyto_index = $i;
						$headers[$i] = 'Reply-To: '.$email;
					}
				}
				
				//Ajoute ou remplace
				$headers[$replyto_index] = sprintf('%s: %s - %s<%s>', 'Reply-To', esc_html($site_title), esc_html($user_name), $email);

				$args['headers'] = implode ($separator, $headers);
			}
		}

		return $args;
	}
	// neither, is_user_logged_in() is false
	public static function wpcf7_verify_nonce_cb($is_active){
		return true;
	}
 	// redirect email //
	///////////////////


}
