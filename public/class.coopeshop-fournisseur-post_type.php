<?php

/**
 * CoopEShop -> Fournisseur
 * Custom post type for WordPress.
 * 
 * Définition du Post Type fournisseur
 * Définition de la taxonomie type_fournisseur
 * A l'affichage d'un fournisseur, le Content est remplacé par celui du fournisseur Modèle
 * En Admin, le bloc d'édition du Content est masqué d'après la définition du Post type : le paramètre 'supports' qui ne contient pas 'editor', see CoopEShop_Admin_Fournisseur::init_PostType_Supports
 *
 * Voir aussi CoopEShop_Admin_Fournisseur
 */
class CoopEShop_Fournisseur_Post_type {
	/**
	 * Fournisseur post type.
	 */
	public static function register_post_type() {

		$labels = array(
			'name'                  => _x( 'Fournisseurs', 'Post Type General Name', 'coopeshop' ),
			'singular_name'         => _x( 'Fournisseur', 'Post Type Singular Name', 'coopeshop' ),
			'menu_name'             => __( 'Fournisseurs', 'coopeshop' ),
			'name_admin_bar'        => __( 'Fournisseur', 'coopeshop' ),
			'archives'              => __( 'Fournisseurs', 'coopeshop' ),
			'attributes'            => __( 'Attributs', 'coopeshop' ),
			'parent_item_colon'     => __( 'Fournisseur parent:', 'coopeshop' ),
			'all_items'             => __( 'Tous les fournisseurs', 'coopeshop' ),
			'add_new_item'          => __( 'Ajouter un fournisseur', 'coopeshop' ),
			'add_new'               => __( 'Ajouter', 'coopeshop' ),
			'new_item'              => __( 'Nouveau fournisseur', 'coopeshop' ),
			'edit_item'             => __( 'Modifier', 'coopeshop' ),
			'update_item'           => __( 'Mettre à jour', 'coopeshop' ),
			'view_item'             => __( 'Afficher', 'coopeshop' ),
			'view_items'            => __( 'Voir les fournisseurs', 'coopeshop' ),
			'search_items'          => __( 'Rechercher des fournisseurs', 'coopeshop' ),
			'items_list'            => __( 'Liste de fournisseurs', 'coopeshop' ),
			'items_list_navigation' => __( 'Navigation dans la liste de fournisseurs', 'coopeshop' ),
			'filter_items_list'     => __( 'Filtrer la liste de fournisseurs', 'coopeshop' ),
		);
		$capabilities = array(
			'edit_post'             => 'edit_fournisseur',
			'read_post'             => 'read_fournisseur',
			'delete_post'           => 'delete_fournisseur',
			'delete_posts'          => 'delete_fournisseur',
			'delete_others_posts'   => 'edit_others_fournisseurs',
			'edit_posts'            => 'edit_fournisseurs',
			'edit_others_posts'     => 'edit_others_fournisseurs',
			'publish_posts'         => 'publish_fournisseurs',
			'read_private_posts'    => 'read_private_fournisseurs',
		);
		$args = array(
			'label'                 => __( 'Fournisseur', 'coopeshop' ),
			'description'           => __( 'Fournisseur information pages.', 'coopeshop' ),
			'labels'                => $labels,
			'supports'              => array( 'title', 'author', 'thumbnail', 'revisions' ),//, 'editor' see CoopEShop_Admin_Fournisseur::init_PostType_Supports
			'taxonomies'            => array( 'type_fournisseur' ),
			'hierarchical'          => false,
			'public'                => true,
			'show_ui'               => true,
			'show_in_menu'          => true,
			'menu_position'         => 25,
			'show_in_admin_bar'     => true,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => true,
			'delete_with_user'		=> true,
			'exclude_from_search'   => false,
			'publicly_queryable'    => true,
			//'capabilities'			=> $capabilities,
			'capability_type'       => 'post'
		);
		register_post_type( CoopEShop_Fournisseur::post_type, $args );
	}


	/**
	 * Register Custom Taxonomy
	 */
	public static function register_taxonomy_type_fournisseur() {

		$labels = array(
			'name'                       => _x( 'Types de fournisseurs', 'Taxonomy General Name', 'coopeshop' ),
			'singular_name'              => _x( 'Type de fournisseur', 'Taxonomy Singular Name', 'coopeshop' ),
			'menu_name'                  => __( 'Types de fournisseurs', 'coopeshop' ),
			'all_items'                  => __( 'Tous les types de fournisseurs', 'coopeshop' ),
			'parent_item'                => __( 'Type parent', 'coopeshop' ),
			'parent_item_colon'          => __( 'Type parent:', 'coopeshop' ),
			'new_item_name'              => __( 'Nouveau type de fournisseur', 'coopeshop' ),
			'add_new_item'               => __( 'Ajouter un nouveau type', 'coopeshop' ),
			'edit_item'                  => __( 'Modifier', 'coopeshop' ),
			'update_item'                => __( 'Mettre à jour', 'coopeshop' ),
			'view_item'                  => __( 'Afficher', 'coopeshop' ),
			'separate_items_with_commas' => __( 'Séparer les éléments par une virgule', 'coopeshop' ),
			'add_or_remove_items'        => __( 'Ajouter ou supprimer des éléments', 'coopeshop' ),
			'choose_from_most_used'      => __( 'Choisir le plus utilisé', 'coopeshop' ),
			'popular_items'              => __( 'Types populaires', 'coopeshop' ),
			'search_items'               => __( 'Rechercher', 'coopeshop' ),
			'not_found'                  => __( 'Introuvable', 'coopeshop' ),
			'no_terms'                   => __( 'Aucun type', 'coopeshop' ),
			'items_list'                 => __( 'Liste des types', 'coopeshop' ),
			'items_list_navigation'      => __( 'Navigation parmi les types', 'coopeshop' ),
		);
		$args = array(
			'labels'                     => $labels,
			'hierarchical'               => true,
			'public'                     => true,
			'show_ui'                    => true,
			'show_admin_column'          => true,
			'show_in_nav_menus'          => true,
			'show_tagcloud'              => true,
		);
		register_taxonomy( CoopEShop_Fournisseur::taxonomy_type_fournisseur, array( CoopEShop_Fournisseur::post_type ), $args );

	}

}