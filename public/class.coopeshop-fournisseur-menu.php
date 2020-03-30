<?php

/**
 * CoopEShop Admin -> Fournisseur
 * Custom post type for WordPress in Admin UI.
 * 
 * Définition des metaboxes et des champs personnalisés des Fournisseurs 
 *
 * Voir aussi CoopEShop_Fournisseur
 */
class CoopEShop_Admin_Fournisseur_Menu {


	public static function init() {
	}

	/**
	 * Intégration du fournisseur dans le menu 
	 */
	public static function manage_menu_integration ($post_id, $post, $is_update){

		//Anticipe l'enregistrement des metas qui n'est pas forcément fait à ce niveau
		if( array_key_exists('post_ID', $_POST)
		&& array_key_exists('f-menu-position', $_POST) ) {

			$show_post = get_post_meta($post_id, 'f-menu', true);
			$has_changed = $show_post != ($_POST['f-menu'] ? '1' : null);
			if(!$has_changed)
				$has_changed = get_post_meta($post_id, 'f-menu-position', true) != $_POST['f-menu-position'];
			if(!$has_changed){
				$menu_item = self::get_the_post_menu_item($post_id);
				if($show_post && $menu_item
				|| !$show_post && !$menu_item)
					return;
			}
			update_post_meta($post_id, 'f-menu', $_POST['f-menu'] ? 1 : 0);
			update_post_meta($post_id, 'f-menu-position', $_POST['f-menu-position']);
		}
			 
		self::regenerate_menu();
		return;
/*
		$menu_integration = $post->post_status == 'publish'
		 					&& $post->post_title
		 					&& array_key_exists('f-menu', $_POST)
		 					&& $_POST['f-menu'];
		$menu_item_position = array_key_exists('f-menu-position', $_POST)
		 						&& is_numeric( $_POST['f-menu-position'] ) ? intval( $_POST['f-menu-position'] ) : 0;
			 
		$menu = self::get_integration_menu();

		$menu_items = wp_get_nav_menu_items($menu->term_id);

		$existing_item = null;
		$existing_item_index = null;
		$to_remove = [];

		foreach ($menu_items as $index => $item) {
			if($item->object_id == $post_id){
				if($menu_integration
				&& $existing_item === null){
					//Trouvé
					$existing_item_index = $index;
					$existing_item = $item;
				}
				else {
					//Doublons à supprimer
					$to_remove[] = $item;
				}
			}
		}

		if($menu_integration){
			if(!$menu_item_position)
				if($existing_item)
					$menu_item_position = $existing_item->menu_order;
				else
					$menu_item_position = count($menu_items) - 1;

			if($existing_item)
				$menu_item_index = $existing_item_index;
			else
				$menu_item_index = 0;//count($menu_items) - 2;

			$itemData =  array(
				'menu-item-object-id' => $post->ID,
				'menu-item-parent-id' => 0,
				'menu-item-menu_order'  => $menu_item_position,
				'menu-item-object' => CoopEShop_Fournisseur::post_type,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish'
			);
			wp_update_nav_menu_item($menu->term_id, $menu_item_index, $itemData);
		}
		foreach ($to_remove as $index => $item) {
			wp_delete_post($item->ID);
		}
		*/
	}

	/**
	 * Retourne le menu dans lequel on intègre les fournisseurs
	 */
	public static function get_integration_menu(){
		$locations = get_nav_menu_locations();
		$menu = get_term($locations['top'], 'nav_menu');
		return $menu;
	}

	/**
	 * Cherche l'item de menu correspondant au post
	 */
	public static function get_the_post_menu_item($post_id){

		$menu = self::get_integration_menu();

		$menu_items = wp_get_nav_menu_items($menu->term_id);

		$existing_item = null;
		$existing_item_index = null;
		$to_remove = [];

		foreach ($menu_items as $index => $item) {
			if($item->object_id == $post_id){
				return $item;
			}
		}
		return false;
	}

	/**
	 * Recrée le menu des fournisseurs
	 TODO refaire sans supprimer/recréer mais en modifiant post->menu_order
	 */
	public static function regenerate_menu(){
		$menu = self::get_integration_menu();
		$menu_items = wp_get_nav_menu_items($menu->term_id);

		$menu_items_before = [];
		$to_remove = [];
		$menu_items_after = [];
		
		// Remove all menu items
		$to_remove = [];

		foreach ($menu_items as $index => $item) {
			if($item->object == CoopEShop_Fournisseur::post_type){
				$to_remove[] = $item;
			}
			elseif(count($to_remove) === 0)
				$menu_items_before[] = $item;
			else
				$menu_items_else[] = $item;
		}
		foreach ($to_remove as $index => $item) {
			wp_delete_post($item->ID);
		}

		// Fournisseurs
		$posts = self::get_ordered_posts();

		// Add menu items
		$menu_item_position = count($menu_items_before) + 1;
		foreach($posts as $post_id => $post){
			$itemData =  array(
				'menu-item-object-id' => $post->ID,
				'menu-item-parent-id' => 0,
				'menu-item-menu_order'  => $menu_item_position,
				'menu-item-object' 		=> CoopEShop_Fournisseur::post_type,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish'
			);
			wp_update_nav_menu_item($menu->term_id, 0, $itemData);
			
			/* ne pas faire ça, sinon ça pourrit ceux qui sont décochés temporairement
			update_post_meta($post->ID, 'f-menu-position', $menu_item_position);
			*/
			++$menu_item_position;

		}
	}

	/**
	 * Retourne le menu dans lequel on intègre les fournisseurs
	 */
	public static function get_ordered_posts(){
		
		$posts = [];
		$posts_order = [];
		$the_query = new WP_Query( 
			array(
				'nopaging' => true,
				'post_type'=> CoopEShop_Fournisseur::post_type,
				
		));
		
		while ( $the_query->have_posts() ) {
			$the_query->the_post();
			$post = get_post();
			if($post->post_status == 'publish'
			&& $post->post_title
			&& get_post_meta($post->ID, 'f-menu', true)){
				$posts[$post->ID] = $post;
				if( is_numeric( get_post_meta($post->ID, 'f-menu-position', true) ))
					$posts_order[$post->ID] = intval(get_post_meta($post->ID, 'f-menu-position', true));
				else
					$posts_order[$post->ID] = PHP_INT_MAX;
			}
		}

		asort($posts_order);

		$ordered_posts = [];
		foreach($posts_order as $post_id => $order)
			$ordered_posts[$post_id] = $posts[$post_id];
		return $ordered_posts;
	}
}
