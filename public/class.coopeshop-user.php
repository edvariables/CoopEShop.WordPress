<?php
class CoopEShop_User {

	public static function init() {
		
	}

	public static function create_user_for_fournisseur($email = false, $user_name = false, $user_login = false, $data = false){

		if( ! $email){
			$post = get_post();
			$email = get_post_meta($post->ID, 'f-email', true);
		}
		
		$user_id = email_exists( $email );
		if($user_id){
			return new WP_User( $user_id );
		}

		if(!$user_login) {
			$user_login = sanitize_key( $user_name ? $user_name : $email );
		}
		if(!$user_id && $user_login) {
			$i = 2;
			while(username_exists( $user_login)){
				$user_login .= $i++;
			}
		}

		// Generate the password and create the user
		$password = wp_generate_password( 12, false );
		$user_id = wp_create_user( $user_login ? $user_login : $email, $password, $email );

		if( is_wp_error($user_id) ){
			return $user_id;
		}

		if( ! is_array($data))
			$data = array();
		$data = array_merge($data, 
			array(
				'ID'				=>	$user_id,
				'nickname'			=>	$user_name ? $user_name : ($user_login ? $user_login : $email),
				'first_name'			=>	$user_name ? $user_name : ($user_login ? $user_login : $email),
				'display_name'			=>	$user_name ? $user_name : ($user_login ? $user_login : $email)

			)
		);

		wp_update_user($data);

		// Set the role
		$user = new WP_User( $user_id );
		if($user) {
			$user->set_role( 'author' );
			/*if($user->Errors){

			}
			else {
				// Email the user
				//wp_mail( $email_address, 'Welcome!', 'Your Password: ' . $password );
			}*/		
		}
		
		return $user;
	}

	public static function get_blog_admin_id(){
		$email = get_bloginfo('admin_email');
		return null;
	}

	/**
	 * Retourne un blog auquel appartient l'utilisateur et en priorité le blog en cours
	 */
	public static function get_current_or_default_blog_id($user){
		$blog_id = get_current_blog_id();
		if($user){
			$blogs = get_blogs_of_user($user->ID);
			if( ! array_key_exists($blog_id, $blogs))
				foreach($blogs as $blog){
					$blog_id = $blog->userblog_id;
					break;
				}
		}
		return $blog_id;
	}

}
