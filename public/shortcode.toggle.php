<?php

function toggle_shortcode_cb( $atts, $content = null ) {

	extract( shortcode_atts( array(
		'title' => __('Cliquez pour afficher', COOPESHOP_TAG),
		'admin_only' => false,
		'color' => ''
	), $atts ) );

	if(array_key_exists('admin_only', $atts)
	&& $atts['admin_only']){
		if( ! is_super_admin())
			return '';
	}

	return '<h3 class="toggle-trigger"><a href="#">' . esc_html( $title  ) . '</a></h3><div class="toggle_container">' . do_shortcode( wp_kses_post( $content ) ) . '</div>';

}
add_shortcode( 'toggle', 'toggle_shortcode_cb' );