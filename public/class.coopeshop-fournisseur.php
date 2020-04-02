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
		//Maintient de la connexion de l'utilisateur pendant l'envoi du mail
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
	 * Attendu que ce soit avec un formulaire du plugin Contact Form 7.
	 * Le email2, email de copie, ne subit pas la redirection.
	 */
	public static function redirect_wpcf7_mails($args){

		static $wpcf7_mailcounter;

/*print_r($wpcf7_mailcounter);
echo "\nIN redirect_wpcf7_mails\n";*/

		$args = self::email_specialchars($args);

		if(! array_key_exists('_wpcf7_container_post', $_POST))
			return $args;

		$post_id = $_POST['_wpcf7_container_post'];
		if( !$post_id )
			return $args;

		// Only from fournisseur pages
		$post = get_post($post_id);
		if( ! $post
		|| $post->post_type != self::post_type)
			return $args;

		$to_emails = parse_emails($args['to']);
		$headers_emails = parse_emails($args['headers']);
		$emails = array();
		//[ [source, header, name, user, domain], ]
		// 'user' in ['fournisseur', 'client', 'admin']
		//Dans la config du mail WPCF7, on a, par exemple, "To: [e-mail-ou-telephone]<client@coopeshop.net>"
		//on remplace client@coopeshop.net par l'email extrait de [e-mail-ou-telephone]
		foreach (array_merge($to_emails, $headers_emails) as $value) {
			if($value['domain'] === COOPESHOP_EMAIL_DOMAIN
			&& ! array_key_exists($value['user'], $emails)) {
				switch($value['user']){
					case 'fournisseur':
						$emails[$value['user']] = self::get_fournisseur_email($post);
						break;
					case 'admin':
						$emails[$value['user']] = get_bloginfo('admin_email');
						break;
					case 'client':
						$real_email = parse_emails($value['name']);
						if(count($real_email))
							$emails[$value['user']] = $real_email[0]['email'];
						break;
					case 'user':
					case 'utilisateur':
						if(is_user_logged_in()){
							global $current_user;
							get_currentuserinfo();
							$email = $current_user->user_email;
							if( is_email($email)){
								$user_name = $current_user->display_name;
								$site_title = get_bloginfo( 'name', 'display' );

								$user_emails = parse_emails($email);

								$emails['user'] = $user_emails[0]['email'];
							}
						}
						break;
					}
			}
		}

		//Cherche à détecter si on est dans le mail de copie
		if(isset($wpcf7_mailcounter))
			$wpcf7_mailcounter++;
		else
			$wpcf7_mailcounter = 1;

		if( ! $emails['client'] || ! is_email($emails['client']) || ( $emails['client'] == 'client@coopeshop.net' ) ){
			// 2ème mail à destination du client mais email invalide
			if($wpcf7_mailcounter >= 2) {
				//Cancels email without noisy error and clear log
				$args["to"] = '';
				$args["subject"] = 'annulation';
				$args["message"] = '';
				$args['headers'] = '';
				return $args;	
			}

			$emails['client'] = 'NePasRepondre@coopeshop.net';
		}

		foreach ($to_emails as $email_data) {
			if(array_key_exists($email_data['user'], $emails)
			&& $emails[$email_data['user']]) {
				$args['to'] = str_ireplace($email_data['user'].'@'.$email_data['domain'], $emails[$email_data['user']], $args['to']);
				$args['message'] = str_ireplace($email_data['user'].'@'.$email_data['domain'], $emails[$email_data['user']], $args['message']);
			}
		}
		foreach ($headers_emails as $email_data) {
			if(array_key_exists($email_data['user'], $emails)
			&& $emails[$email_data['user']]) {
				$args['headers'] = str_ireplace($email_data['user'].'@'.$email_data['domain'], $emails[$email_data['user']], $args['headers']);
				$args['message'] = str_ireplace($email_data['user'].'@'.$email_data['domain'], $emails[$email_data['user']], $args['message']);
			}
		}
		
/*print_r($emails);
echo "\n";
print_r($headers_emails);
echo "\n";
print_r($args);
if($wpcf7_mailcounter >= 2) {
	$args['headers'] = null;
	die();
}*/
//$args["message"] = ''; //cancel

		return $args;
	}

	/**
	 * Correction de caractères spéciaux
	 */
	public static function email_specialchars($args){
		$args['subject'] = str_replace('&#039;', "'", $args['subject']);
		return $args;
	}

	public static function wpcf7_verify_nonce_cb($is_active){
		// keep connected at mail send time
		return is_user_logged_in();
	}
 	// redirect email //
	///////////////////

	/**
	 * Email du fournisseur ou de l'auteur de la page Fournisseur
	 */
	public static function get_fournisseur_email($post){
		if(is_numeric($post)){
			$post_id = $post;
			$post = false;
		}
		else
			$post_id = $post->ID;
		if(!$post_id)
			return false;

		// Change l'adresse du destinataire
		$email = get_post_meta($post_id, 'f-email', true);

		// 2ème email ?
		if( ! is_email($email)){
			$email = get_post_meta($post_id, 'f-email2', true);
		}

		if( ! is_email($email)){
			if( ! $post)
				$post = get_post($post_id);
			// Email de l'auteur du post
			$email = the_author_meta('email', $post->post_author);
		}
		return $email;
	}

}
