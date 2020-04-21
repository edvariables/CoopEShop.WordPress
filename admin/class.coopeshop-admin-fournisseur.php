<?php

/**
 * CoopEShop Admin -> Fournisseur
 * Custom post type for WordPress in Admin UI.
 * 
 * Définition des metaboxes et des champs personnalisés des Fournisseurs 
 *
 * Voir aussi CoopEShop_Fournisseur
 */
class CoopEShop_Admin_Fournisseur {
	static $the_post_is_new = false;

	public static function init() {

		self::$the_post_is_new = basename($_SERVER['PHP_SELF']) == 'post-new.php';

		self::init_hooks();
		self::init_MetaBoxes();
		//self::init_PostType_Supports();
	}

	public static function init_hooks() {
		add_action( 'admin_head', array(__CLASS__, 'init_PostType_Supports'), 10, 4 );
		add_filter( 'map_meta_cap', array(__CLASS__, 'map_meta_cap'), 10, 4 );
		
		//add custom columns for list view
		add_filter( 'manage_' . CoopEShop_Fournisseur::post_type . '_posts_columns', array( __CLASS__, 'manage_columns' ) );
		add_action( 'manage_' . CoopEShop_Fournisseur::post_type . '_posts_custom_column', array( __CLASS__, 'manage_custom_columns' ), 10, 2 );
		//set custom columns sortable
		add_filter( 'manage_edit-' . CoopEShop_Fournisseur::post_type . '_sortable_columns', array( __CLASS__, 'manage_sortable_columns' ) );

		add_action( 'save_post_fournisseur', array(__CLASS__, 'new_post_fournisseur_cb'), 10, 4 );

		add_action('wp_dashboard_setup', array(__CLASS__, 'add_dashboard_widgets') );
	}

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
	 * Callback lors de la création d'un nouveau fournisseur.
	 * voir aussi CoopEShop_Fournisseur::save_post_fournisseur_cb
	 */
	public static function new_post_fournisseur_cb ($post_id, $post, $update){
		//Sauvegarde de brouillon ou de modification rapide
		if(basename($_SERVER['PHP_SELF']) == 'admin-ajax.php')
			return;

		if($update){

			CoopEShop_Admin_Fournisseur_Menu::manage_menu_integration($post_id, $post, $update);
		}

		if($update
		|| $post->post_status == 'trashed'
		|| !(current_user_can('manage_options'))) { 
			return;
		}

		//Ajoute une metabox spéciale "nouveau fournisseur"
		self::register_metabox_new_post();
	}

	public static function get_found_posts($args = null){
		return count(self::get_posts($args)); 
    }

	public static function get_posts($args = null){
		if(!is_array($args))
			$args = array();
		$args[ 'post_type'] = CoopEShop_Fournisseur::post_type;
        $the_query = new WP_Query( $args );
        return $the_query->posts; 
    }

	/**
	 * N'affiche l'éditeur que pour le fournisseur modèle ou si l'option CoopEShop::fournisseur_show_content_editor
	 */
	public static function init_PostType_Supports(){
		global $post;
		if( current_user_can('manage_options') ){
			/*if($post && $post->ID == CoopEShop_Fournisseur::get_fournisseur_model_post_id()
			|| CoopEShop::get_option('fournisseur_show_content_editor'))
				add_post_type_support( 'fournisseur', 'editor' );*/
		}
	}

	/**
	 * map_meta_cap
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

/*	private static function add_capabilities() {
	    $admins = get_role( 'administrator' );
	    $admins->add_cap( 'edit_taqrir' ); 
	    $admins->add_cap( 'read_taqrir' ); 
	    $admins->add_cap( 'delete_taqrir' ); 
	    $admins->add_cap( 'edit_others_taqrir' ); 
	    $admins->add_cap( 'publish_taqrir' ); 
	    $admins->add_cap( 'read_private_taqrir' ); 
	}
*/

	/**
	 * MetaBox
	 */
	public static function init_MetaBoxes() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_metaboxes' ), 10 ); 
		add_action( 'save_post', array( __CLASS__, 'save_post_cb'), 10, 2 );
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
			add_meta_box('coop_fournisseur-general', __('Informations générales', 'coopeshop'), array(__CLASS__, 'metabox_callback'), CoopEShop_Fournisseur::post_type, 'side', 'high');
		}
	}

	/**
	 * Register Meta Box pour un nouveau fournisseur.
	 Uniquement pour les admins
	 */
	public static function register_metabox_new_post(){
		if( current_user_can('manage_options') ){
			add_meta_box('coop_fournisseur-new_post', __('Nouveau fournisseur', 'coopeshop'), array(__CLASS__, 'metabox_callback'), CoopEShop_Fournisseur::post_type, 'side', 'high');
		}
	}

	/**
	 * Callback
	 */
	public static function metabox_callback($post, $metabox){
		//var_dump(func_get_args());
		
		switch ($metabox['id']) {
			case 'coop_fournisseur-intro_text':
				return self::metabox_html( self::get_metabox_intro_text_fields(), $post, $metabox );
				break;
			
			case 'coop_fournisseur-catalogue':
				return self::metabox_html( self::get_metabox_catalogue_fields(), $post, $metabox );
				break;
			
			case 'coop_fournisseur-ending_text':
				return self::metabox_html( self::get_metabox_ending_text_fields(), $post, $metabox );
				break;
			
			case 'coop_fournisseur-contact':
				return self::metabox_html( self::get_metabox_contact_fields(), $post, $metabox );
				break;
			
			case 'coop_fournisseur-general':
				return self::metabox_html( self::get_metabox_general_fields(), $post, $metabox );
				break;
			
			case 'coop_fournisseur-new_post':
				return self::metabox_html( self::get_metabox_new_post_fields(), $post, $metabox );
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
		return $fields;
	}

	/**
	 * Ces champs ne sont PAS enregistrés car get_metabox_all_fields ne les retourne pas dans save_metaboxes
	 */
	public static function get_metabox_new_post_fields(){
		return array(
			array('name' => 'f-create-user',
				'label' => __('Créer un nouvel utilisateur', 'coopeshop'),
				'input' => 'checkbox',
				'default' => 'checked',
				'container_class' => 'side-box'
			),
			array('name' => 'f-create-user-slug',
				'label' => __('Identifiant du nouvel utilisateur', 'coopeshop'),
				'input' => 'text',
				'container_class' => 'side-box'
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
			if($label && $input != 'checkbox') {
				echo '<label for="'.$name.'">' . htmlentities($label) . ' : </label>';
			}

			switch ($input) {
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
	public static function save_post_cb($post_ID, $post){

		//Sauvegarde de brouillon ou de modification rapide
		if(basename($_SERVER['PHP_SELF']) == 'admin-ajax.php')
			return;

		if( $post->post_type != CoopEShop_Fournisseur::post_type )
			return;

		self::save_metaboxes($post_ID, $post);
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

			//sub fields
			if(isset($field['fields']) && is_array($field['fields'])){
				self::save_metaboxes($post_ID, $post, $field);
			}
		}
		return false;
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
					sprintf( '<cite class="entry-terms">' ), ', ', '</cite>' );
			?></header><?php
			?><div class="entry-summary">
				<?php echo get_the_excerpt($fournisseur); ?>
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
		?><ul><?php
		foreach($fournisseurs as $fournisseur){
			//;
			echo '<li>';
			?><header class="entry-header"><?php 
				edit_post_link( $fournisseur->post_title, '<h2 class="entry-title">', '</h2>', $fournisseur );
				the_terms( $fournisseur->ID, 'type_fournisseur', 
					sprintf( '<cite class="entry-terms">' ), ', ', '</cite>' );
			?></header><?php
			?><div class="entry-summary">
				<?php echo get_the_excerpt($fournisseur); ?>
			</div><?php
			echo '</li>';
			
		}
		?></ul><?php
	}

}