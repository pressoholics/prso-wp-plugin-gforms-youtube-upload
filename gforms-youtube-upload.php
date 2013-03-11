<?php
/*
Plugin Name: Gravity Forms Youtube Uploader
Version: 1.0
Author URI: http://Pressoholics.com
Plugin URI: http://Pressoholics.com
Description:  When used with Pressoholics Gravoty Forms Plupload plugin, allows video uploads to be passed
				directly to a youtube account rather than store videos on local server.
Author: Benjamin Moody
*/

/**
 * Gforms Youtube Upload
 *
 * MUST BE USED WITH Prso Gforms Plupload plugin
 * Moves any video files uploaded via gforms plupload plugin
 * to a youtube account and stores the youtube video ID against the
 * gravity forms submission rather than store the video file in wordpress media library.
 *
 * NOTE:: As a backup if any problems with youtube api, video will remain on local server
 *			within the wordpress media library.
 *
 * PHP versions 4 and 5
 *
 * @copyright     Pressoholics (http://pressoholics.com)
 * @link          http://pressoholics.com
 * @package       pressoholics theme framework
 * @since         Pressoholics v 1.0
 */

	
/**
* Include config file to set core definitions
*
*/
if( file_exists( dirname(__FILE__) . '/config.php' ) ) {
	
	include( dirname(__FILE__) . '/config.php' );
	
	if( class_exists('PrsoGformsYoutubeConfig') ) {
		
		new PrsoGformsYoutubeConfig( __FILE__ );
		
		//Core loaded, load rest of plugin core
		include( dirname(__FILE__) . '/bootstrap.php' );

		//Instantiate bootstrap class
		if( class_exists('PrsoGformsYoutubeBootstrap') ) {
			new PrsoGformsYoutubeBootstrap();
		}
		
	}
	
}

//Register plugin activation hook
register_activation_hook( __FILE__ , 'PrsoGformsYoutube_install' );
function PrsoGformsYoutube_install() {
		
	
}


//Register plugin de-activation hook
register_deactivation_hook( __FILE__ , 'PrsoGformsYoutube_uninstall' );
function PrsoGformsYoutube_uninstall() {
		
}