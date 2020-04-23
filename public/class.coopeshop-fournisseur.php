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
		self::init_hooks_for_search();
		add_filter( 'the_content', array(__CLASS__, 'the_fournisseur_content') );
		
		//wp_mail from Contact Form 7
		add_filter( 'wp_mail', array(__CLASS__, 'wp_mail_coopeshop_fields'), 10,1);
		//Maintient de la connexion de l'utilisateur pendant l'envoi du mail
		add_filter( 'wpcf7_verify_nonce', array(__CLASS__, 'wpcf7_verify_nonce_cb' ));

		//Fenêtre de réinitialisation de mot de passe
		add_action( 'resetpass_form', array(__CLASS__, 'resetpass_form' ));
	}
 
 	/////////////
 	// Modèle //

	/**
	 * Hook
	 */
 	public static function the_fournisseur_content( $content ) {
 		global $post;
 		if( ! $post
 		|| $post->post_type != self::post_type)
 			return $content;
	    return self::get_fournisseur_content( );
	}
 
 	/**
 	 * Retourne l'ID du post servant de bon de commande
 	 */
	public static function get_fournisseur_bon_commande_post_id( ) {
		$option_id = 'fournisseur_bon_commande_post_id';
		return CoopEShop::get_option($option_id);
	}
 
 	/**
 	 * Retourne le Content du post servant de modèle
 	 */
	public static function get_fournisseur_content( ) {
		$html = '[fournisseur-texte-intro]
[fournisseur-catalogue]
[fournisseur-texte-fin]
[fournisseur-details notoggle="Contactez-nous"]
[fournisseur-bon-commande notoggle="Envoyez-nous une commande !"]';
		
		if( current_user_can('manage_options') ){
			$post_id = CoopEShop::get_option('admin_message_contact_form_id');
			if(! $post_id){
				return '<p class="">Le bon de commande de fournisseur n\'est pas défini dans le paramétrage de CoopEShop.</p>';
			}

			$html .= sprintf('[toggle title="Message de l\'administrateur au fournisseur"] [contact-form-7 id="%s"][/toggle]'
				, $post_id);
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
	public static function wp_mail_coopeshop_fields($args){

		static $wpcf7_mailcounter;

		$args = self::email_specialchars($args);

		if(! array_key_exists('_wpcf7_container_post', $_POST))
			return $args;

		$post_id = $_POST['_wpcf7_container_post'];
		if( ! $post_id )
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
		//Ce qui veut dire que la forme complète "[e-mail-ou-telephone]<client@coopeshop.net>" doit apparaitre pour deviner l'email du client
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
						if(count($real_email)){
							$emails['client'] = $real_email[0]['email'];
						}
						else {
							//on peut être ici si, dans le formulaire, on a "client@coopeshop.net" et non "[e-mail-ou-telephone]<client@coopeshop.net>"
							//TODO bof
							$real_email = parse_emails($_POST['e-mail-ou-telephone']);
							if(count($real_email)){
								$emails['client'] = $real_email[0]['email'];
							}	
						}
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

		if( ! $emails['client']
		|| ! is_email($emails['client'])
		|| ( $emails['client'] == 'client@coopeshop.net' ) ){
			// 2ème mail à destination du client mais email invalide
			if($wpcf7_mailcounter >= 2) {
				//Cancels email without noisy error and clear log
				$args["to"] = '';
				$args["subject"] = 'client précédent sans email';
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

		//remplace "XY<commande@coopeshop.net>" par "XY@coopeshop.net<NePasRepondre@coopeshop.net>"
		/*$args['headers'] = str_ireplace(
								  '"<commande@'.COOPESHOP_EMAIL_DOMAIN.'>'
								, '.'.COOPESHOP_EMAIL_DOMAIN.'"<commande@'.COOPESHOP_EMAIL_DOMAIN.'>'
								, $args['headers']);
		$args['headers'] = str_ireplace(
								  COOPESHOP_EMAIL_DOMAIN.'.'.COOPESHOP_EMAIL_DOMAIN
								, COOPESHOP_EMAIL_DOMAIN
								, $args['headers']);*/
		/*print_r($args['headers']);
		echo "\n";
		print_r( preg_replace('/@?([\w.]*)("?\<commande@coopeshop.net\>)/', '.$1@coopeshop.net$2', $args['headers']));
		echo "\n";
		echo array_flip(array_filter(get_defined_constants(true)['pcre'], function($v) { return is_integer($v); }))[preg_last_error()];
		die();*/
		$args['headers'] = preg_replace('/@?([\w.]*)("?\<commande@coopeshop.net\>)/', '_$1@coopeshop.net$2', $args['headers']);


		if($password_message = self::new_password_via_email($post->post_author)){
			$args['message'] .= "\r\n<br>" . $password_message;
		}

		return $args;
	}

	/**
	 * Dans un email au fournisseur, ajoute une invitation à saisir un nouveaui mot de passe.
	 * Returns a string to add to email for user to reset his password.
	 */
	private static function new_password_via_email($user_id){
		if(! array_key_exists( "new-password", $_POST)
		|| is_super_admin($user_id)
		|| $user_id == CoopEShop_User::get_blog_admin_id()
		)
			return;
		$user = new WP_USER($user_id);
		$password_key = get_password_reset_key($user);
		if( ! $password_key)
			return;
		$redirect_to = get_home_url( get_current_blog_id(), sprintf("wp-login.php?login=%s", rawurlencode( $user->user_login )), 'login' );
		$url = sprintf("wp-login.php?action=rp&key=%s&login=%s&redirect_to=%s", $password_key, rawurlencode( $user->user_login ), esc_url($redirect_to));
		$url = network_site_url( $url );
		$message .= sprintf(__( 'Pour définir votre mot de passe, <a href="%s">vous devez cliquer ici</a>.', COOPESHOP_TAG) , $url ) . "\r\n";
		return $message;
	}
	/**
	 * Fenêtre de réinitialisation de mot de passe
	 */
	public static function resetpass_form( $user ){
		//insert html code
		// redirect_to
		if ( isset( $_REQUEST['redirect_to'] ) ) {
			$url = $_REQUEST['redirect_to'];
		}
		if( ! $url) {
			$url = get_home_url( CoopEShop_User::get_current_or_default_blog_id($user), sprintf("wp-admin/"), 'admin' );
		}
		echo sprintf('<input type="hidden" name="%s" value="%s"/>', 'redirect_to', $url );
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
			$email = get_the_author_meta('email', $post->post_author);
		}
		return $email;
	}

	/***********************************************************/

	/**
	 * Initialize a new fournisseur
	 * Called from email interception
	 */
	public static function new_fournisseur($data){
		if(is_object($data) && is_a($data, 'WPCF7_ContactForm', true)){ //contact form 7 -> wp_mail -> $args['message']
			$contact_form = $data;
			$data = array(
				'title' => $_POST['f-title'],
				'f-nom_humain' => $_POST['f-nom_humain'],
				'f-email' => $_POST['f-email'],
				'f-email2' => $_POST['f-email2'],
				'f-telephone' => $_POST['f-telephone'],
				'f-telephone2' => $_POST['f-telephone2'],
				'f-siteweb' => $_POST['f-siteweb'],
				'f-facebook' => $_POST['f-facebook'],
				'f-adresse' => $_POST['f-adresse'],
				'f-texte-intro' => $_POST['f-texte-intro'],
				'f-catalogue' => $_POST['f-catalogue'],
				'f-texte-fin' => sprintf("<p>%s</p><p>%s</p>",
								$_POST['texte-retrait'],
								$_POST['texte-conclusion']),
			);
		}
		elseif( ! is_array($data)
			 || ! isset($data['title']) ){
			return;
		}


		$data['f-nom_humain-show'] = 1;
		$data['f-email-show'] = 1;
		$data['f-email2-show'] = 1;
		$data['f-telephone-show'] = 1;
		$data['f-telephone2-show'] = 1;
		$data['f-siteweb-show'] = 1;
		$data['f-facebook-show'] = 1;
		$data['f-adresse-show'] = 1;

		$post_author = CoopEShop_User::get_blog_admin_id();
		$post_title = $data['title'];
		unset($data['title']);
		
		$postarr = array(
			'post_status' => 'pending',
			'post_title' => $post_title,
			'post_type' => CoopEShop_Fournisseur::post_type,
			'post_author' => $post_author,
			'meta_input' => $data,
			'post_content' => '',
		);
		return wp_insert_post( $postarr, true );
	}

	/***********************************************************/
	/**
	 * Extend WordPress search to include custom fields
	 *
	 * https://adambalee.com
	 */
	private static function init_hooks_for_search(){
		add_filter('posts_join', array(__CLASS__, 'cf_search_join' ));
		add_filter( 'posts_where', array(__CLASS__, 'cf_search_where' ));
		add_filter( 'posts_distinct', array(__CLASS__, 'cf_search_distinct' ));
	}
	/**
	 * Join posts and postmeta tables
	 *
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_join
	 */
	public static function cf_search_join( $join ) {
	    global $wpdb;

	    if ( is_search() ) {    
	        $join .=' LEFT JOIN '.$wpdb->postmeta. ' ON '. $wpdb->posts . '.ID = ' . $wpdb->postmeta . '.post_id ';
	    }

	    return $join;
	}

	/**
	 * Modify the search query with posts_where
	 *
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_where
	 */
	public static function cf_search_where( $where ) {
	    global $pagenow, $wpdb;

	    if ( is_search() ) {
	        $where = preg_replace(
	            "/\(\s*".$wpdb->posts.".post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
	            "(".$wpdb->posts.".post_title LIKE $1) OR (".$wpdb->postmeta.".meta_value LIKE $1)", $where );
	    }

	    return $where;
	}

	/**
	 * Prevent duplicates
	 *
	 * http://codex.wordpress.org/Plugin_API/Filter_Reference/posts_distinct
	 */
	public static function cf_search_distinct( $where ) {
	    global $wpdb;

	    if ( is_search() ) {
	        return "DISTINCT";
	    }

	    return $where;
	}

}
