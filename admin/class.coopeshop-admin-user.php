<?php
class CoopEShop_Admin_User {

	public static function init() {
		//Add custom user contact Methods
		add_filter( 'user_contactmethods', array( __CLASS__, 'custom_user_contact_methods' ));
	}

	// Register User Contact Methods
	public static function custom_user_contact_methods( $user_contact_method ) {

/*		$user_contact_method['email3'] = __( 'Autre email', 'coopeshop' );

		$user_contact_method['tel'] = __( 'Téléphone', 'coopeshop' );
		$user_contact_method['tel2'] = __( 'Autre téléphone', 'coopeshop' );

		$user_contact_method['facebook'] = __( 'Compte Facebook', 'coopeshop' );
		$user_contact_method['twitter'] = __( 'Compte Twitter', 'coopeshop' );

		$user_contact_method['address'] = __( 'Adresse', 'coopeshop' );
		$user_contact_method['address2'] = __( 'Adresse (suite)', 'coopeshop' );
		$user_contact_method['city'] = __( 'Code postal et commune', 'coopeshop' );
*/
		return $user_contact_method;

	}

}
