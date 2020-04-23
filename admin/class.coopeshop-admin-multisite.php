<?php
class CoopEShop_Admin_Multisite {

	public static function init() {
	}

	public static function get_other_blogs_of_user ($user_id){
		$current_blog_id = get_current_blog_id();
		$blogs = get_blogs_of_user($user_id);
		if(isset($blogs[$current_blog_id]))
			unset($blogs[$current_blog_id]);

		/*if( WP_DEBUG ) { //TODO delete
			$blogs = array();
			$blogId= 3;
			$blogs[$blogId] = new stdClass();
			$blogs[$blogId]->userblog_id = $blogId;
			$blogs[$blogId]->blogname = 'CoopEShop de DEV';
			$blogId++;
			$blogs[$blogId] = new stdClass();
			$blogs[$blogId]->userblog_id = $blogId;
			$blogs[$blogId]->blogname = 'CoopEShop du Pays de Saint-Félicien';
			$blogId++;
			$blogs[$blogId] = new stdClass();
			$blogs[$blogId]->userblog_id = $blogId;
			$blogs[$blogId]->blogname = 'CoopEShop des Monts d\'Ardèche';
			$blogId++;
			$blogs[$blogId] = new stdClass();
			$blogs[$blogId]->userblog_id = $blogId;
			$blogs[$blogId]->blogname = 'CoopEShop des Monts du Lyonnais';
			$blogId++;
			$blogs[$blogId] = new stdClass();
			$blogs[$blogId]->userblog_id = $blogId;
			$blogs[$blogId]->blogname = 'CoopEShop du Pays de Lamatres';
		}*/
		return $blogs;
	}

	public static function synchronise_to_others_blogs ($post_id, $post, $is_update){
		if( $post->post_status != 'publish'){
			CoopEShop_Admin::add_admin_notice("La synchronisation n'a pas été effectuée car la page n'est pas encore publiée.", 'warning');
			return;
		}
		$blogs = self::get_other_blogs_of_user($post->post_author);
		foreach ($blogs as $blog) {
			self::synchronise_to_other_blog ($post_id, $post, $is_update, $blog);
		}
	}

	public static function synchronise_to_other_blog ($post_id, $post, $is_update, $to_blog){
		global $wpdb;
		$src_prefix = $wpdb->base_prefix;
		$src_prefix = preg_replace('/_$/', '', $src_prefix);
		$basic_prefix = preg_replace('/_\d*$/', '', $wpdb->base_prefix);
		$dest_prefix = $basic_prefix . ( $to_blog->userblog_id == 1 ? '' : '_' . $to_blog->userblog_id );

		//Find this in other blog 
		$sql = "SELECT dest.ID
				FROM {$dest_prefix}_posts dest
				INNER JOIN {$src_prefix}_posts src
					ON src.post_author = dest.post_author
					AND src.post_type = dest.post_type
					AND src.post_status = dest.post_status
					AND src.post_name = dest.post_name
				WHERE src.ID = {$post_id}
				";

		//CoopEShop_Admin::add_admin_notice($sql, 'warning');
		$results = $wpdb->get_results($sql);
		//CoopEShop_Admin::add_admin_notice(print_r($results, true), 'warning');

		if(count($results) == 1){
			//TODO Synchro des images

			//CoopEShop_Admin::add_admin_notice("Synchronisation vers le blog {$to_blog->blogname}", 'success');
		}
		CoopEShop_Admin::add_admin_notice("Désolé, la fonctionnalité de synchronisation vers le blog {$to_blog->blogname} n'est pas encore opérationnelle.", 'warning');
	}
}
