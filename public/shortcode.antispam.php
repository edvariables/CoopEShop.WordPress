<?php
/**
 * [antispam]ed@coopeshop.net[/antispam]
 */
function antispam_shortcode_cb( $atts, $content = null ) {
	if(is_array($atts) && array_key_exists('mailto', $atts)){
		if(is_email($content))
			return antispam_mailto_shortcode_cb( $atts, $content);
		return antispam_mailto_shortcode_cb( $atts, $atts['mailto']);
	}
	return antispambot( $content );

}
/**
 * [mailto]ed@coopeshop.net[/mailto]
 */
function antispam_mailto_shortcode_cb( $atts, $content = null ) {
	$email = antispambot( sanitize_email ( $content ));
	return sprintf('<a href="mailto:%s">%s</a>', $email, $email);

}
add_shortcode( 'antispam', 'antispam_shortcode_cb' );
add_shortcode( 'mailto', 'antispam_mailto_shortcode_cb' );