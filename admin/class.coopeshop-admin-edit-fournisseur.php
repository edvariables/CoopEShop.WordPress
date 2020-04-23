<?php

/**
 * CoopEShop Admin -> Edit -> Fournisseur
 * Custom post type for WordPress in Admin UI.
 * 
 * Edition d'un fournisseur
 * Définition des metaboxes et des champs personnalisés des Fournisseurs 
 *
 * Voir aussi CoopEShop_Fournisseur, CoopEShop_Admin_Fournisseur
 */
class CoopEShop_Admin_Edit_Fournisseur {
	static $the_post_is_new = false;

	public static function init() {

		self::$the_post_is_new = basename($_SERVER['PHP_SELF']) == 'post-new.php';

		self::init_hooks();
	}

	public static function init_hooks() {

		add_filter( 'wp_insert_post_data', array(__CLASS__, 'wp_insert_post_data_cb'), 10, 2 );
		add_action( 'save_post_fournisseur', array(__CLASS__, 'save_post_fournisseur_cb'), 10, 3 );

		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metaboxes' ), 10 ); //edit
	}
	/****************/

	/**
	 * Callback lors de l'enregistrement d'un fournisseur.
	 * A ce stade, les metaboxes ne sont pas encore sauvegardées
	 */
	public static function wp_insert_post_data_cb ($data, $postarr ){
		//Sauvegarde de brouillon ou de modification rapide
		if(basename($_SERVER['PHP_SELF']) == 'admin-ajax.php'
		|| $data['post_type'] != CoopEShop_Fournisseur::post_type 
		|| in_array($data['post_status'], [ 'trash', 'trashed' ]) ){
			return $data;
		}

		if( array_key_exists('f-create-user', $postarr) && $postarr['f-create-user'] ){
			$data = self::create_user_on_save($data, $postarr);
		}

		return $data;
	}
	/**
	 * Callback lors de l'enregistrement d'un fournisseur.
	 * A ce stade, les metaboxes ne sont pas encore sauvegardées
	 */
	public static function save_post_fournisseur_cb ($post_id, $post, $is_update){

		//Sauvegarde de brouillon ou de modification rapide
		if(basename($_SERVER['PHP_SELF']) == 'admin-ajax.php')
			return;

		if($is_update){
			CoopEShop_Admin_Fournisseur_Menu::manage_menu_integration($post_id, $post, $is_update);
		}

		if( $post->post_status == 'trashed' ){
			return;
		}

		self::save_metaboxes($post_id, $post);
		
		if( array_key_exists('f-multisite-synchronise', $_POST) && $_POST['f-multisite-synchronise'] ){
			//TODO self::synchronise_to_others_blogs($post_id, $post, $is_update);
		}
	}

	/**
	 * Lors du premier enregistrement, on crée l'utilisateur
	 * A ce stade, les metaboxes ne sont pas encore sauvegardées
	 */
	public static function create_user_on_save ($data, $postarr){
		$email = array_key_exists('f-email', $postarr) ? $postarr['f-email'] : false;
		if(!$email || !is_email($email)) {
			CoopEShop_Admin::add_admin_notice("Il manque l'adresse e-mail du fournisseur ou elle est incorrecte.", 'error');
			return $data;
		}
		$user_name = array_key_exists('f-nom_humain', $postarr) ? $postarr['f-nom_humain'] : false;
		$user_login = array_key_exists('f-create-user-slug', $postarr) ? $postarr['f-create-user-slug'] : false;
	
		$user_data = array(
			'description' => 'Fournisseur ' . $data['post_title'],
		);
		$user = CoopEShop_User::create_user_for_fournisseur($email, $user_name, $user_login, $user_data);
		if( is_wp_error($user)) {
			CoopEShop_Admin::add_admin_notice($user, 'error');
			return;
		}
		if($user){
			unset($_POST['f-create-user']);
			unset($postarr['f-create-user']);

			$data['post_author'] = $user->ID;
			//CoopEShop_Admin::add_admin_notice(debug_print_backtrace(), 'warning');
			CoopEShop_Admin::add_admin_notice("Désormais, l'auteur de la page est {$user->display_name}", 'success');
		}

		return $data;
	}

	public static function synchronise_to_others_blogs ($post_id, $post, $is_update){
			CoopEShop_Admin_Multisite::synchronise_to_others_blogs ($post_id, $post, $is_update);
	}

	/**
	 * Register Meta Boxes (boite en édition de fournisseur)
	 */
	public static function register_metaboxes(){
		//En création on n'affiche pas les blocs de texte
		if(self::$the_post_is_new)
			add_meta_box('coop_fournisseur-contact', __('Contact du fournisseur', 'coopeshop'), array(__CLASS__, 'metabox_callback'), CoopEShop_Fournisseur::post_type, 'normal', 'high');
		else {
			add_meta_box('coop_fournisseur-intro_text', __('Texte d\'introduction', 'coopeshop'), array(__CLASS__, 'metabox_callback'), CoopEShop_Fournisseur::post_type, 'normal', 'high');
			add_meta_box('coop_fournisseur-catalogue', __('Catalogue', 'coopeshop'), array(__CLASS__, 'metabox_callback'), CoopEShop_Fournisseur::post_type, 'normal', 'high');
			add_meta_box('coop_fournisseur-ending_text', __('Texte de conclusion', 'coopeshop'), array(__CLASS__, 'metabox_callback'), CoopEShop_Fournisseur::post_type, 'normal', 'high');
			add_meta_box('coop_fournisseur-contact', __('Contact du fournisseur', 'coopeshop'), array(__CLASS__, 'metabox_callback'), CoopEShop_Fournisseur::post_type, 'normal', 'high');
			/*add_meta_box('coop_fournisseur-general', __('Informations générales', 'coopeshop'), array(__CLASS__, 'metabox_callback'), CoopEShop_Fournisseur::post_type, 'side', 'high');*/
		}
		self::register_metabox_admin();
	}

	/**
	 * Register Meta Box pour un nouveau fournisseur.
	 Uniquement pour les admins
	 */
	public static function register_metabox_admin(){
		if( current_user_can('manage_options') ){
			$title = self::$the_post_is_new ? __('Nouveau fournisseur', 'coopeshop') : __('Fournisseur', 'coopeshop');
			add_meta_box('coop_fournisseur-admin', $title, array(__CLASS__, 'metabox_callback'), CoopEShop_Fournisseur::post_type, 'side', 'high');
		}
	}

	/**
	 * Callback
	 */
	public static function metabox_callback($post, $metabox){
		//var_dump(func_get_args());
		
		switch ($metabox['id']) {
			case 'coop_fournisseur-intro_text':
				self::metabox_html( self::get_metabox_intro_text_fields(), $post, $metabox );
				break;
			
			case 'coop_fournisseur-catalogue':
				self::metabox_html( self::get_metabox_catalogue_fields(), $post, $metabox );
				break;
			
			case 'coop_fournisseur-ending_text':
				self::metabox_html( self::get_metabox_ending_text_fields(), $post, $metabox );
				break;
			
			case 'coop_fournisseur-contact':
				self::metabox_html( self::get_metabox_contact_fields(), $post, $metabox );
				break;
			
			/*case 'coop_fournisseur-general':
				self::metabox_html( self::get_metabox_general_fields(), $post, $metabox );
				break;*/
			
			case 'coop_fournisseur-admin':
				self::post_author_metabox_field( $post );
				self::metabox_html( self::get_metabox_admin_fields(), $post, $metabox );
				break;
			
			default:
				break;
		}
	}

	public static function get_metabox_all_fields(){
		return array_merge(
			self::get_metabox_intro_text_fields(),
			self::get_metabox_catalogue_fields(),
			self::get_metabox_ending_text_fields(),
			self::get_metabox_contact_fields(),
			self::get_metabox_general_fields()
		);
	}	

	public static function get_metabox_intro_text_fields(){
		return array(
			array('name' => 'f-texte-intro',
				'label' => false,
				'input' => 'tinymce' )
		);
	}	

	public static function get_metabox_catalogue_fields(){
		return array(
			array('name' => 'f-catalogue',
				'label' => false,
				'input' => 'tinymce',
				'settings' => array (
					'textarea_rows' => 20
				)
			)
		);
	}	

	public static function get_metabox_ending_text_fields(){
		return array(
			array('name' => 'f-texte-fin',
				'label' => false,
				'input' => 'tinymce' )
		);
	}

	public static function get_metabox_contact_fields(){

		$field_show = array(
			'name' => '%s-show',
			'label' => __('afficher sur le site', 'coopeshop'),
			'type' => 'checkbox',
			'default' => '1'
		);
				
		return array(
			array('name' => 'f-nom_humain',
				'label' => __('Personne(s) physique(s)', 'coopeshop'),
				'fields' => array($field_show)
			),
			array('name' => 'f-telephone',
				'label' => __('Téléphone', 'coopeshop'),
				'type' => 'tel',
				'fields' => array($field_show)
			),
			array('name' => 'f-telephone2',
				'label' => __('Autre tél.', 'coopeshop'),
				'type' => 'tel',
				'fields' => array($field_show)
			),
			array('name' => 'f-email',
				'label' => __('Email', 'coopeshop'),
				'type' => 'email',
				'fields' => array($field_show)
			),
			array('name' => 'f-email2',
				'label' => __('Autre email', 'coopeshop'),
				'type' => 'email',
				'fields' => array($field_show)
			),
			array('name' => 'f-siteweb',
				'label' => __('Site Web', 'coopeshop'),
				'type' => 'url',
				'fields' => array($field_show)
			),
			array('name' => 'f-facebook',
				'label' => __('Facebook', 'coopeshop'),
				'type' => 'text',
				'fields' => array($field_show)
			),
			array('name' => 'f-adresse',
				'label' => __('Adresse', 'coopeshop'),
				'input' => 'textarea',
				'fields' => array($field_show)
			)/*,
			array('name' => 'f-gps',
				'label' => __('Coord. GPS', 'coopeshop'),
				'type' => 'gps',
				'fields' => array($field_show)
			)*/
		);
	}

	public static function get_metabox_general_fields(){
		$fields = array();

		if(! self::$the_post_is_new) {
			if(CoopEShop::get_option('fournisseur_type_menus') == 'fournisseurs'){		
				$fields[] =
					array('name' => 'f-menu',
						'label' => __('Afficher dans le menu', 'coopeshop'),
						'type' => 'bool'
					);	
				$fields[] =
					array('name' => 'f-menu-position',
						'label' => __('Position dans le menu', 'coopeshop'),
						'type' => 'number',
						'container_class' => 'admin_only'
					);
			}
			$fields[] =
				array('name' => 'f-bon-commande',
					'label' => __('Afficher le bon de commande', 'coopeshop'),
					'type' => 'bool',
					'default' => 'checked'
				)
			;
		}
		return $fields;
	}

	/**
	 * Ces champs ne sont PAS enregistrés car get_metabox_all_fields ne les retourne pas dans save_metaboxes
	 */
	public static function get_metabox_admin_fields(){
		global $post;
		$fields = array();
		if( ! self::$the_post_is_new ){
			$user_info = get_userdata($post->post_author);
			$user_email = $user_info->user_email;
		}
 		if(self::$the_post_is_new
		|| $user_email != get_post_meta($post->ID, 'f-email', true) ) {
			$fields[] = array(
				'name' => 'f-create-user',
				'label' => __('Créer l\'utilisateur d\'après l\'e-mail', 'coopeshop'),
				'input' => 'checkbox',
				'default' => 'checked',
				'container_class' => 'side-box'
			);
			/*$fields[] = array(
				'name' => 'f-create-user-slug',
				'label' => __('Identifiant du nouvel utilisateur', 'coopeshop'),
				'input' => 'text',
				'container_class' => 'side-box'
			);*/
		}
		//multisite
		if( ! self::$the_post_is_new && ( WP_DEBUG || is_multisite() )) {//
			$blogs = CoopEShop_Admin_Multisite::get_other_blogs_of_user($post->post_author);
			if(count($blogs) > 1){
				$field = array(
					'name' => 'f-multisite-synchronise',
					/*'label' => __('Synchroniser cette page vers', 'coopeshop'),
					'input' => 'checkbox',*/
					'label' => __('Vos autres sites', 'coopeshop'),
					'input' => 'label',
					'fields' => array()
				);
				foreach($blogs as $blog){
					$field['fields'][] = 
						array('name' => sprintf('f-multisite[%s]', $blog->userblog_id),
							//'label' => preg_replace('/CoopEShop\sd[eu]s?\s/', '', $blog->blogname),
							'label' => sprintf('<a href="%s/wp-admin">%s</a>', $blog->siteurl, preg_replace('/CoopEShop\sd[eu]s?\s/', '', $blog->blogname)),
							'input' => 'link',
							//'input' => 'label',
							'container_class' => 'description'
						)
					;	
				}
				$fields[] = $field;
			}
		}
		$fields = array_merge($fields, self::get_metabox_general_fields());
		return $fields;
	}

	/**
	 * Remplace la metabox Auteur par un liste déroulante dans une autre metabox
	 */
	private static function post_author_metabox_field( $post ) {
		global $user_ID;
		?><label for="post_author_override"><?php _e( 'Utilisateur' ); ?></label><?php
		wp_dropdown_users(
			array(
				'who'              => 'authors',
				'name'             => 'post_author_override',
				'selected'         => empty( $post->ID ) ? $user_ID : $post->post_author,
				'include_selected' => true,
				'show'             => 'display_name_with_login',
			)
		);
	}

	/**
	 * HTML render in metaboxes
	 */
	public static function metabox_html($fields, $post, $metabox, $parent_field = null){
		
		foreach ($fields as $field) {
			$name = $field['name'];
			if($parent_field !== null)
				$name = sprintf($name, $parent_field['name']);
			$meta_value = get_post_meta($post->ID, $name, true);
			$id = ! array_key_exists ( 'id', $field ) || ! $field['id'] ? $name : $field['id'];
			if($parent_field !== null)
				$id = sprintf($id, array_key_exists('id', $parent_field) ? $parent_field['id'] : $parent_field['name']);
			$val = ! array_key_exists ( 'value', $field ) || ! $field['value'] ? $meta_value : $field['value'];
			$label = ! array_key_exists ( 'label', $field ) || ! $field['label'] ? false : $field['label'];
			$input = ! array_key_exists ( 'input', $field ) || ! $field['input'] ? '' : $field['input'];
			$input_type = ! array_key_exists ( 'type', $field ) || ! $field['type'] ? 'text' : $field['type'];
			$style = ! array_key_exists ( 'style', $field ) || ! $field['style'] ? '' : $field['style'];
			$class = ! array_key_exists ( 'class', $field ) || ! $field['class'] ? '' : $field['class'];
			$container_class = ! array_key_exists ( 'container_class', $field ) || ! $field['container_class'] ? '' : $field['container_class'];
			
			$container_class .= ' coop-metabox-row';
			$container_class .= ' is' . ( current_user_can('manage_options') ? '' : '_not') . '_admin';
			if($parent_field != null)
				$container_class .= ' coop-metabox-subfields';

			?><div class="<?php echo trim($container_class);?>"><?php

			switch ($input_type) {
				case 'number' :
				case 'int' :
					$input = 'text';
					$input_type = 'number';
					break;

				case 'checkbox' :
				case 'bool' :
					$input = 'checkbox';
					break;

				default:
					if(!$input_type)
						$input_type = 'text';
					break;
			}

			// Label , sous pour checkbox
			if($label && ! in_array( $input, ['label', 'link', 'checkbox'])) {
				echo '<label for="'.$name.'">' . htmlentities($label) . ' : </label>';
			}

			switch ($input) {
				////////////////
				case 'label':
					echo '<label id="'.$id.'" for="'.$name.'"'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. '>' . htmlentities($label).'</label>';
					break;

				////////////////
				case 'link':
					echo '<label id="'.$id.'" for="'.$name.'"'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. '>' . $label.'</label>';
					break;

				////////////////
				case 'textarea':
					echo '<textarea id="'.$id.'" name="'.$name.'">'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. htmlentities($val).'</textarea>';
					break;
				
				////////////////
				case 'tinymce':
					$editor_settings = ! array_key_exists ( 'settings', $field ) || ! $field['settings'] ? null : $field['settings'];
					$editor_settings = wp_parse_args($editor_settings, array( //valeurs par défaut
						'textarea_rows' => 10
					));
				    wp_editor( $val, $id, $editor_settings);
					break;
				
				
				////////////////
				case 'select':
					echo '<select id="'.$id.'"'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						.' name="' . $name . '">';

					$values = ! array_key_exists ( 'values', $field ) || ! $field['values'] ? false : $field['values'];
					if(is_array($values)){
						foreach($values as $item_key => $item_label){
							echo '<option ' . selected( $val, $item_key ) . ' value="' . $item_key . '">'. htmlentities($item_label) . '</option>';
						}
					}
					echo '</select>';
					break;
				
				////////////////
				case 'checkbox':
					echo '<label>';
					echo '<input id="'.$id.'" type="checkbox" name="'.$name.'" '
						. ($val ? ' checked="checked"' : '')
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. ($style ? ' style="'.str_replace('"', "'", $style).'"' : '') 
						. ' value="1" />';
					echo htmlentities($label) . '</label>';
					break;
				
				////////////////
				case 'input':
				default:
					//TODO phone, email, checkbox, number, int, bool, yes|no, ...
					echo '<input id="'.$id.'"'
						. ' type="' . $input_type .'"'
						. ' name="'.$name.'"'
						. ' value="'.htmlentities($val) .'"'
						. ($class ? ' class="'.str_replace('"', "'", $class).'"' : '') 
						. ($style ? ' style="'.str_replace('"', "'", $style).'"' : '')
						. '" />';
					break;
			}

			//sub fields
			if( array_key_exists('fields', $field) && is_array($field['fields'])){
				self::metabox_html($field['fields'], $post, $metabox, $field);
			}
		
			
			?></div><?php
		}
	}

	/**
	 * Save metaboxes' input values
	 * Field can contain sub fields
	 */
	public static function save_metaboxes($post_ID, $post, $parent_field = null){

		if($parent_field === null)
			$fields = self::get_metabox_all_fields();
		else
			$fields = $parent_field['fields'];
		foreach ($fields as $field) {
			if(!isset($field['type']) || $field['type'] != 'label'){
				$name = $field['name'];
				if($parent_field !== null)
					$name = sprintf($name, $parent_field['name']);
				// remember : a checkbox unchecked does not return any value
				if( isset($_POST[$name])){
					$val = $_POST[$name];
				}
				else {
					if(self::$the_post_is_new
					&& isset($field['default']) && $field['default'])
						$val = $field['default'];
					elseif( (isset($field['input']) && ($field['input'] === 'checkbox' || $field['input'] === 'bool'))
						 || (isset($field['type'])  && ($field['type']  === 'checkbox' || $field['type']  === 'bool')) ) {
						$val = '0';
					}
					else
						$val = null;
				}
				update_post_meta($post_ID, $name, $val);
			}

			//sub fields
			if(isset($field['fields']) && is_array($field['fields'])){
				self::save_metaboxes($post_ID, $post, $field);
			}
		}
		return false;
	}


}