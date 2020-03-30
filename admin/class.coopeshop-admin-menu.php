<?php

/**
 * Chargé lors du hook admin_menu qui est avant le admin_init
 */
class CoopEShop_Admin_Menu {

	public static function init() {
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
	}

	public static function init_settings(){
		// register a new setting for "coopeshop" page
		register_setting( COOPESHOP_TAG, COOPESHOP_TAG );

		// register a new section in the "coopeshop" page
		add_settings_section(
			'coopeshop_section_fournisseurs',
			__( 'Fournisseurs', 'coopeshop' ),
			array(__CLASS__, 'settings_section_fournisseurs_cb'),
			COOPESHOP_TAG
		);

		// 
		$field_id = 'fournisseur_model_post_id';
		add_settings_field(
			$field_id, 
			__( 'Modèle des fournisseurs', 'coopeshop' ),
			array(__CLASS__, 'coopeshop_field_fournisseur_model_cb'),
			COOPESHOP_TAG,
			'coopeshop_section_fournisseurs',
			[
				'label_for' => $field_id,
				'class' => 'coopeshop_row',
			]
		);

		// 
		$field_id = 'fournisseur_show_content_editor';
		add_settings_field(
			$field_id, 
			__( 'Editeur de contenu', 'coopeshop' ),
			array(__CLASS__, 'fournisseur_show_content_editor_cb'),
			COOPESHOP_TAG,
			'coopeshop_section_fournisseurs',
			[
				'label_for' => $field_id,
				'class' => 'coopeshop_row',
			]
		);

		// 
		$field_id = 'fournisseur_bon_commande_post_id';
		add_settings_field(
			$field_id, 
			__( 'Bon de commande dans les pages des fournisseurs', 'coopeshop' ),
			array(__CLASS__, 'coopeshop_field_bon_commande_cb'),
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
	public static function settings_section_fournisseurs_cb($args ) {
		?>
		<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Paramètres réservés aux administrateurs, c\'est à dire à ceux qui savent ce qu\'ils font...' , 'coopeshop' ); ?></p>
		<?php
	}

	/**
	 * Modèle parmi la liste des fournisseurs
	 */
	public static function coopeshop_field_fournisseur_model_cb( $args ) {
		// get the value of the setting we've registered with register_setting()
		$option_id = $args['label_for'];
		$option_value = CoopEShop::get_option($option_id);
		if( ! isset( $option_value ) ) $option_value = -1;

		$the_query = new WP_Query( 
			array(
				'nopaging' => true,
				'post_type'=> CoopEShop_Fournisseur::post_type
				//TODO add filters 'author__in' => array( id admin 1, id admin 2, ... ) instead of get_the_author_meta below
			)
		);
		if($the_query->have_posts() ) {
			// output the field
			?>
			<select id="<?php echo esc_attr( $option_id ); ?>"
				name="<?php echo COOPESHOP_TAG;?>[<?php echo esc_attr( $option_id ); ?>]"
			>
			<?php
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$author_level = get_the_author_meta('user_level');
				if($author_level >= 10) { //Super_Admin authors only
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
			<div class="dashicons-before dashicons-warning">Un modèle doit être défini !</div>
			<?php
		}
		?>
		<div class="dashicons-before dashicons-welcome-learn-more">Le modèle doit comporter un texte incluant les shortcodes [fournisseur-*]</div>
		<div class="dashicons-before dashicons-arrow-right">Sauf sélection de l'option suivante, seule la page de ce modèle affiche l'éditeur de contenu.</div>
		<?php
	}

	/**
	 * Affichage du Content des fournisseurs en mode super_admin
	 */
	public static function fournisseur_show_content_editor_cb( $args ) {
		// get the value of the setting we've registered with register_setting()
		$option_id = $args['label_for'];
		$option_value = CoopEShop::get_option($option_id);
		if( ! isset( $option_value ) ) $option_value = -1;

		?><label>
			<input
				id="<?php echo $option_id?>"
				name="<?php echo COOPESHOP_TAG;?>[<?php echo esc_attr( $option_id ); ?>]"
				type="checkbox" <?php if($option_value) echo ' checked="checked"';?>/>
			&nbsp;Afficher
		</label>
		<div class="dashicons-before dashicons-welcome-learn-more">Seuls les administrateurs peuvent voir cet éditeur dans les pages Fournisseurs</div>
		<div class="dashicons-before <?php echo $option_value ? 'dashicons-warning' : 'dashicons-arrow-right'?>">Merci de ne pas laisser ce paramètre coché après usage.</div>
		<?php
	}

	/**
	 * Bon de commande parmi la liste des formulaire de contact Contact Form 7
	 */
	public static function coopeshop_field_bon_commande_cb( $args ) {
		// get the value of the setting we've registered with register_setting()
		$option_id = $args['label_for'];
		$option_value = CoopEShop::get_option($option_id);
		if( ! isset( $option_value ) ) $option_value = -1;

		$the_query = new WP_Query( 
			array(
				'nopaging' => true,
				//TODO type in settings instead of WPCF7_ContactForm::post_type
				'post_type'=> WPCF7_ContactForm::post_type
				//TODO add filters 'author__in' => array( id admin 1, id admin 2, ... ) instead of get_the_author_meta below
			)
		);
		if($the_query->have_posts() ) {
			// output the field
			?>
			<select id="<?php echo esc_attr( $option_id ); ?>"
				name="<?php echo COOPESHOP_TAG;?>[<?php echo esc_attr( $option_id ); ?>]"
			>
			<?php
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$author_level = get_the_author_meta('user_level');
				if($author_level >= 10) { //Super_Admin authors only
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
		?>
		<div class="dashicons-before dashicons-welcome-learn-more">Peu importe le paramétrage de l'email du destinataire dans ce formulaire car c'est l'email du fournisseur qui sera utilisé</div>
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
			15
		);
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
}