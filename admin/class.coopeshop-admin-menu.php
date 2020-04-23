<?php

/**
 * Chargé lors du hook admin_menu qui est avant le admin_init
 */
class CoopEShop_Admin_Menu {

	static $initialized = false;

	public static function init() {
		if(self::$initialized)
			return;
		self::$initialized = true;
		self::init_includes();
		self::init_hooks();
		self::init_settings();
		self::init_admin_menu();
	}

	public static function init_includes() {	
	}

	public static function init_hooks() {

		//TODO
		// Le hook admin_menu est avant le admin_init
		//add_action( 'admin_menu', array( __CLASS__, 'init_admin_menu' ), 5 ); 
		add_action('wp_dashboard_setup', array(__CLASS__, 'init_dashboard_widgets') );
	}

	public static function init_settings(){
		// register a new setting for "coopeshop" page
		register_setting( COOPESHOP_TAG, COOPESHOP_TAG );

		// register a new section in the "coopeshop" page
		add_settings_section(
			'coopeshop_section_general',
			__( 'En général', 'coopeshop' ),
			array(__CLASS__, 'settings_sections_cb'),
			COOPESHOP_TAG
		);

		// 
		$field_id = 'admin_message_contact_form_id';
		add_settings_field(
			$field_id, 
			__( 'Message de la part de l\'administrateur', 'coopeshop' ),
			array(__CLASS__, 'coopeshop_combos_contact_forms_cb'),
			COOPESHOP_TAG,
			'coopeshop_section_general',
			[
				'label_for' => $field_id,
				'class' => 'coopeshop_row',
			]
		);

		// 
		$field_id = 'fournisseur_register_form_id';
		add_settings_field(
			$field_id, 
			__( 'Formulaire d\'inscription des fournisseurs', 'coopeshop' ),
			array(__CLASS__, 'coopeshop_combos_contact_forms_cb'),
			COOPESHOP_TAG,
			'coopeshop_section_general',
			[
				'label_for' => $field_id,
				'class' => 'coopeshop_row',
			]
		);

		// 
		$field_id = 'newsletter_register_form_id';
		add_settings_field(
			$field_id, 
			__( 'Formulaire d\'inscription à la lettre-info', 'coopeshop' ),
			array(__CLASS__, 'coopeshop_combos_contact_forms_cb'),
			COOPESHOP_TAG,
			'coopeshop_section_general',
			[
				'label_for' => $field_id,
				'class' => 'coopeshop_row',
			]
		);

		// register a new section in the "coopeshop" page
		add_settings_section(
			'coopeshop_section_fournisseurs',
			__( 'Fournisseurs', 'coopeshop' ),
			array(__CLASS__, 'settings_sections_cb'),
			COOPESHOP_TAG
		);

		// 
		$field_id = 'fournisseur_bon_commande_post_id';
		add_settings_field(
			$field_id, 
			__( 'Bon de commande dans les pages des fournisseurs', 'coopeshop' ),
			array(__CLASS__, 'coopeshop_combos_contact_forms_cb'),
			COOPESHOP_TAG,
			'coopeshop_section_fournisseurs',
			[
				'label_for' => $field_id,
				'class' => 'coopeshop_row',
			]
		);

		// 
		$field_id = 'fournisseur_type_menus';
		add_settings_field(
			$field_id, 
			__( 'Mode de gestion du menu', 'coopeshop' ),
			array(__CLASS__, 'fournisseur_type_menus_cb'),
			COOPESHOP_TAG,
			'coopeshop_section_fournisseurs',
			[
				'label_for' => $field_id,
				'class' => 'coopeshop_row',
			]
		);
	}

	/**
	 * Section
	 */
	public static function settings_sections_cb($args ) {
		switch($args['id']){
			case 'coopeshop_section_general' : 
				$message = 'Paramètres réservés aux administrateurs, c\'est à dire à ceux qui savent ce qu\'ils font...';
				break;
			case 'coopeshop_section_fournisseurs' : 
				$message = 'Paramètres concernant les pages des fournisseurs.';
				break;
			default : 
				$message = '';
		}
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e(  $message, 'coopeshop' ); ?></p>
		<?php
	}

	/**
	 * Option parmi la liste des formulaire de contact Contact Form 7
	 * Attention, les auteurs de ces formulaires doivent être administrateurs
	 */
	public static function coopeshop_combos_contact_forms_cb( $args ) {
		// get the value of the setting we've registered with register_setting()
		$option_id = $args['label_for'];
		$option_value = CoopEShop::get_option($option_id);
		if( ! isset( $option_value ) ) $option_value = -1;

		$the_query = new WP_Query( 
			array(
				'nopaging' => true,
				//TODO type in settings instead of WPCF7_ContactForm::post_type
				'post_type'=> WPCF7_ContactForm::post_type,
				//'author__in' => self::get_admin_ids(),
			)
		);
		if($the_query->have_posts() ) {
			// output the field
			?>
			<select id="<?php echo esc_attr( $option_id ); ?>"
				name="<?php echo COOPESHOP_TAG;?>[<?php echo esc_attr( $option_id ); ?>]"
			><option/>
			<?php
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$author_level = get_the_author_meta('user_level');
				if($author_level >= USER_LEVEL_ADMIN) { //Admin authors only
					echo sprintf('<option value="%d" %s>%s</option>'
						, get_the_ID()
						, selected( $option_value, get_the_ID(), false )
						, esc_html(__( get_the_title(), 'coopeshop' ))
					);
				}
			}
			echo '</select>';
		}

		if( ! $option_value){
			?>
			<div class="dashicons-before dashicons-warning">Un formulaire de contact doit être défini !</div>
			<?php
		}

		switch($args['label_for']){
			case 'admin_message_contact_form_id':
				?>
				<div class="dashicons-before dashicons-welcome-learn-more">Dans les pages de fournisseurs, seuls les administrateurs voient un formulaire d'envoi de message au fournisseur.</div>
				<?php
				break;
			case 'fournisseur_bon_commande_post_id':
				?>
				<div class="dashicons-before dashicons-welcome-learn-more">Dans les formulaires, les adresses emails comme fournisseur@<?php echo COOPESHOP_EMAIL_DOMAIN?> ou client@<?php echo COOPESHOP_EMAIL_DOMAIN?> sont remplacées par des valeurs dépendantes du contexte.</div>
				<?php
				break;
		}
	}

	/**
	 * Mode de gestion des fournisseurs dans le menu 
	 */
	public static function fournisseur_type_menus_cb( $args ) {
		// get the value of the setting we've registered with register_setting()
		$option_id = $args['label_for'];
		$option_value = CoopEShop::get_option($option_id);
		if( ! isset( $option_value ) ) $option_value = -1;

		?>	<select
				id="<?php echo $option_id?>"
				name="<?php echo COOPESHOP_TAG;?>[<?php echo esc_attr( $option_id ); ?>]">
				<option value="type_fournisseur" <?php if(!$option_value || $option_value == 'type_fournisseur') echo ' selected="selected"';?>>Menu des types de fournisseurs</option>
				<option value="fournisseurs" <?php if($option_value == 'fournisseurs') echo ' selected="selected"';?>>Menu des fournisseurs</option>
			</select>
		<?php
	}

	/**
	 * top level menu
	 */
	public static function init_admin_menu() {
		// add top level menu page
		add_menu_page(
			__('Réglages', 'coopeshop'),
			'CoopEShop',
			'manage_options',
			'coopeshop',
			array(__CLASS__, 'coopeshop_options_page_html'),
			'dashicons-lightbulb',
			35
		);

		if(! current_user_can('manage_options')){

		    $user = wp_get_current_user();
		    $roles = ( array ) $user->roles;
		    if(in_array('fournisseur', $roles)) {
				remove_menu_page('posts');//TODO
				remove_menu_page('wpcf7');
			}
		}
	}

	/**
	* top level menu:
	* callback functions
	*/
	public static function coopeshop_options_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add error/update messages

		// check if the user have submitted the settings
		// wordpress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'coopeshop_messages', 'coopeshop_message', __( 'Réglages enregistrés', 'coopeshop' ), 'updated' );

			
			if(CoopEShop::get_option('fournisseur_type_menus') == 'fournisseurs'){
				CoopEShop_Admin_Fournisseur_Menu::regenerate_fournisseurs_menu();
			}
			else {
				CoopEShop_Admin_Fournisseur_Menu::clear_fournisseurs_from_menu();
			}
			
		}

		// show error/update messages
		settings_errors( 'coopeshop_messages' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				// output security fields for the registered setting "coopeshop"
				settings_fields( 'coopeshop' );
				// output setting sections and their fields
				// (sections are registered for "coopeshop", each field is registered to a specific section)
				do_settings_sections( 'coopeshop' );
				// output save settings button
				submit_button( __('Enregistrer', 'coopeshop') );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 *
	 */
	public static function init_dashboard_widgets() {
	    self::remove_dashboard_widgets();
	}

	// TODO parametrage initiale pour chaque utilisateur
	public static function remove_dashboard_widgets() {
	    global $wp_meta_boxes, $current_user;
	    /*var_dump($wp_meta_boxes['dashboard']);*/
		if( ! in_array('administrator',(array)$current_user->roles) ) {
			remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
			remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		}
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	}
}