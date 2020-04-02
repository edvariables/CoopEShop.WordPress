<?php
/**
 * [antispam]ed@coopeshop.net[/antispam]
 */
function antispam_shortcode_cb( $atts, $content = null ) {
	if(is_array($atts) && array_key_exists('mailto', $atts)){
		if(is_email($content))
			return antispam_mailto_shortcode_cb( $atts, $content);
		return antispam_mailto_shortcode_cb( $atts, $atts['mailto'], $content);
	}
	return antispambot( $content );

}
/**
 * [mailto]ed@coopeshop.net[/mailto]
 */
function antispam_mailto_shortcode_cb( $atts, $email = null, $content = null ) {
	return make_mailto($email, $content);

}
add_shortcode( 'antispam', 'antispam_shortcode_cb' );
add_shortcode( 'mailto', 'antispam_mailto_shortcode_cb' );