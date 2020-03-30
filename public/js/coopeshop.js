
/**
 * Toggle shortcode
 */
jQuery( function( $ ) {
	$( document ).ready(function() {
		$( ".toggle_container" ).hide();
		$( ".toggle-trigger" ).click( function() {
			$(this).toggleClass( "active" ).next().slideToggle( "normal" );
			return false;
		} );
	} );
} );