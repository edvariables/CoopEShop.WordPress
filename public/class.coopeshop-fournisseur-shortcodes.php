<?php

/**
 * CoopEShop -> Fournisseur
 * Custom post type for WordPress.
 * 
 * Définition des shortcodes 
 *
 * Voir aussi CoopEShop_Admin_Fournisseur
 */
class CoopEShop_Fournisseur_Shortcodes {


	private static $initiated = false;

	public static function init() {
		if ( ! self::$initiated ) {
			self::$initiated = true;

			self::init_hooks();
			self::init_shortcodes();
		}
	}

	/**
	 * Hook
	 */
	public static function init_hooks() {
	}

	/////////////////
 	// shortcodes //
 	/**
 	 * init_shortcodes
 	 */
	public static function init_shortcodes(){

		add_shortcode( 'fournisseur-texte-intro', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'fournisseur-catalogue', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'fournisseur-texte-fin', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'fournisseur-details', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'fournisseur-bon-commande', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'fournisseur', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'fournisseur-avec-email', array(__CLASS__, 'shortcodes_callback') );
		add_shortcode( 'post', array(__CLASS__, 'shortcode_post_callback') );

	}

	/**
	 * [post]
	 * [post info="f-email"]
	 * [post info="f-telephone"]
	 * [post info="mailto"]
	 * [post info="uri"] [post info="url"]
	 * [post info="a"] [post info="link"]
	 * [post info="post_type"]
	 * [post info="dump"]
	 */
	public static function shortcode_post_callback($atts, $content = '', $shortcode){
		$post = get_post();
		if(!$post){
			echo $content;
			return;
		}

		if( ! is_array($atts)){
			$atts = array();
		}

		if(! array_key_exists('info', $atts)
		|| ! ($info = $atts['info']))
			$info = 'post_title';

		switch($info){
			case 'uri':
			case 'url':
				return $_SERVER['HTTP_REFERER'];
			case 'link':
			case 'a':
				return sprintf('<a href="%s">%s - %s</a>', $_SERVER['HTTP_REFERER'], 'CoopEShop', $post->post_title);

			case 'mailto':
				$email = get_post_meta( $post->ID, 'f-email', true);
				return sprintf('<a href="mailto:%s">%s</a>', $email, $post->post_title);

			case 'dump':
				return sprintf('<pre>%s</pre>', var_export($post, true));

			case 'title':
				$info = 'post_title';

			default :
				if(isset($post->$info))
					return $post->$info;
				return get_post_meta( $post->ID, $info, true);

		}
	}

	public static function shortcodes_callback($atts, $content = '', $shortcode){

		$post = get_post();
		if(!$post){
			echo $content;
			return;
		}

		if( ! is_array($atts)){
			$atts = array();
		}

		// Si attribut toggle [fournisseur-details toggle="Contactez-nous !"]
		// Fait un appel récursif
		// TODO Sauf shortcode conditionnel
		if(array_key_exists('toggle', $atts)){
			
			$shortcode_atts = '';
			foreach($atts as $key=>$value)
				if($key == 'toggle')
					$title = $atts['title'] ? $atts['title'] : ($atts['toggle'] ? $atts['toggle'] : __($shortcode, COOPESHOP_TAG)) ;
				else
					$shortcode_atts .= sprintf('%s="%s"', $key, $value);
			//Inner
			$html = do_shortcode(sprintf('[%s %s]%s[/%s]', $shortcode, $shortcode_atts , $content, $shortcode));
			if(!$html)
				return;
			//toggle
			//Bugg du toggle qui supprime des éléments
			$guid = uniqid(COOPESHOP_TAG);
			$toogler = do_shortcode(sprintf('[toggle title="%s"]%s[/toggle]', $title, $guid));
			return str_replace($guid, $html, $toogler);
		}

		$post_id = $post->ID;
		$html = '';

		//De la forme [fournisseur info='texte-intro'] équivaut à [fournisseur-texte-intro]
		if($shortcode == 'fournisseur'
			&& array_key_exists('info', $atts)){
			if($atts['info'] == 'texte-intro'
			|| $atts['info'] == 'texte-fin'
			|| $atts['info'] == 'catalogue'
			|| $atts['info'] == 'bon-commande'){
				$shortcode .= '-' . $atts['info'];
			}
		}

		switch($shortcode){
			case 'fournisseur-texte-intro':
			case 'fournisseur-texte-fin':
			case 'fournisseur-catalogue':

				$meta_name = 'f-' . substr($shortcode, strlen('fournisseur-')) ;
				$val = get_post_meta($post_id, $meta_name, true);
				if($val || $content){
					$html = '<div class="coop-fournisseur coop-'. $shortcode .'">'
						. do_shortcode( wp_kses_post($val . $content))
						. '</div>';
				}
				return $html;
				break;

			case 'fournisseur-bon-commande':
				
				$meta_name = 'f-bon-commande' ;
				$bon_commande = self::get_post_meta($post_id, $meta_name, true, false);
				if( ! $bon_commande) {
					return;
				}

				$meta_name = 'f-email' ;
				$email = self::get_post_meta($post_id, $meta_name, true, false);
				if(!$email) {
					return '<div class="dashicons-before dashicons-warning coop-error-light">Vous ne pouvez pas envoyer de bon de commande, le fournisseur n\'a pas configuré son adresse email.</div>';
				}

				$form_id = CoopEShop::get_option('fournisseur_bon_commande_post_id');
				if(!$form_id){
					return '<div class="dashicons-before dashicons-warning coop-error-light">Un formulaire de bon de commande n\'est pas défini dans les réglages de CoopEShop.</div>';
				}

				$val = sprintf('[contact-form-7 id="%s" title="*** Formulaire de bon de commande ***"]', $form_id);
				return '<div class="coop-fournisseur coop-'. $shortcode .'">'
					. do_shortcode( $val)
					. '</div>';

			case 'fournisseur-details':

				$html = '';
				$meta_name = 'f-nom_humain'; 
					$val = self::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= esc_html($val) . '</br>';

				$meta_name = 'f-adresse';
					$val = self::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= esc_html($val) . '</br>';

				$meta_name = 'f-telephone';
					$val = self::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= esc_html(antispambot($val)) . '</br>';

				$meta_name = 'f-telephone2';
					$val = self::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= esc_html(antispambot($val)) . '</br>';

				$meta_name = 'f-email';
					$val = self::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= make_mailto($val) . '</br>';

				$meta_name = 'f-email2';
					$val = self::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= make_mailto($val) . '</br>';

				$meta_name = 'f-siteweb';
					$val = self::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= make_clickable(esc_html($val)) . '</br>';

				$meta_name = 'f-facebook';
					$val = self::get_post_meta($post_id, $meta_name, true, true);
					if($val){
						$val = trim(str_replace('@', '', esc_html($val)));
						$uri = sprintf('https://facebook.com/%s', $val);
						$html .= sprintf('<a href="%s">facebook : @%s</a>', $uri, $val) . '</br>';
					}

				$meta_name = 'f-gps';
					$val = self::get_post_meta($post_id, $meta_name, true, true);
					if($val)
						$html .= esc_html($val) . '</br>';
				/*
				$meta_name = 'f-horaires';
					$val = self::get_post_meta($post_id, $meta_name, true);
					if($val)
						$html .= sprintf('<pre>%s</pre>', esc_html($val)) . '</br>';
				*/
				
				if(! $html )
					return '';
				
				// date de création
				$html .= '<div class="entry-date">' ;
				$html .= sprintf('<span>ici depuis le %s</span>', get_the_date()) ;
				if(get_the_date() != get_the_modified_date())
					$html .= sprintf('<span>, mise à jour du %s</span>', get_the_modified_date()) ;
				$html .= '</div>' ;
				
				return '<div class="coop-fournisseur coop-'. $shortcode .'">'
					. do_shortcode( wp_kses_post($html.$content))
					. '</div>';

			case 'fournisseur':
				$meta_name = $atts['info'] ;
				if(!$meta_name)
					return '<div class="error">Le paramètre "info" du shortcode "fournisseur" est inconnu.</div>';
				$val = self::get_post_meta($post_id, 'f-' . $meta_name, true, true);
				if($val || $content){
					return '<div class="coop-fournisseur">'
						. do_shortcode( wp_kses_post($val . $content))
						. '</div>';
				}
				break;

			// shortcode conditionnel
			case 'fournisseur-condition':
				$meta_name = $atts['info'] ;
				if(!$meta_name)
					return '<div class="error">Le paramètre "info" du shortcode "fournisseur-condition" est inconnu.</div>';
				$val = self::get_post_meta($post_id, 'f-' . $meta_name, true, false);
				if($val || $content){
					return do_shortcode( wp_kses_post($val . $content));
				}
				break;


			// shortcode conditionnel sur email
			case 'fournisseur-avec-email':
				$meta_name = 'f-email' ;
				$email = self::get_post_meta($post_id, $meta_name, true, false);
				if(is_email($email)){
					return do_shortcode( wp_kses_post($content));
				}
				return '';

			default:
				return '<div class="error">Le shortcode "'.$shortcode.'" inconnu.</div>';
		}
	}

	/**
	 * Returns, par exemple, le meta f-siteweb. Mais si $check_show_field, on teste si le meta f-siteweb-show est vrai.
	 */
	public static function get_post_meta($post_id, $meta_name, $single = false, $check_show_field = null){
		if($check_show_field){
			if(is_bool($check_show_field))
				$check_show_field = '-show';
			if( ! get_post_meta($post_id, $meta_name . $check_show_field, true))
				return;
		}
		return get_post_meta($post_id, $meta_name, true);

	}

 	// shortcodes //
	///////////////

}
