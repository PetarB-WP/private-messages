<?php
/*
	Plugin Name:  Private Messages
	Plugin URI:   
	Description:  Sending a private message to author of the post
	Version:      1.0
	Author:       Petar Bogic
	Author URI:   
	License:      GPL2
	
	{Private Messages} is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 2 of the License, or
	any later version.
	 
	{Private Messages} is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.
	 
	You should have received a copy of the GNU General Public License
	along with {Private Messages}. If not, see https://www.gnu.org/licenses/gpl-2.0.html
	
	Text Domain:  ppmess
	Domain Path:  /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

//	Activation plugin
/*------------------------------------------------------------------*/
include_once dirname( __FILE__ ) . '/activation.php';
register_activation_hook( __FILE__ , 'pmess_activation' );

//	De-activation plugin
/*------------------------------------------------------------------*/
include_once dirname( __FILE__ ) . '/deactivation.php';
register_deactivation_hook( __FILE__ , 'pmess_deactivation' );

//	Admin page - settings
/*------------------------------------------------------------------*/
include_once dirname( __FILE__ ) . '/template-functions/admin-functions.php';

//	Front-end page
/*------------------------------------------------------------------*/
include_once dirname( __FILE__ ) . '/template-functions/post-functions.php';

//	Function for working with messages. Insert, select, delete from database 
/*------------------------------------------------------------------*/
include_once dirname( __FILE__ ) . '/template-functions/database-functions.php';

//	Realizing shortcode functionality on the front-end side  
/*------------------------------------------------------------------*/
include_once dirname( __FILE__ ) . '/template-functions/shortcode-functions.php';

//	Admin menu - Settings options
/*------------------------------------------------------------------*/
function ppmess_add_menu_pages()
{
	// If the function parameter is omitted, the menu_slug should be the PHP file 
	// that handles the display of the menu page content
	
	// Top level menu page SIMPLE PRIVATE MESSAGES
	$menu_page = add_menu_page(
		__('Simple private message', 'ppmess'),			/* page title */
		__('Simple private message', 'ppmess'),			/* menu title */
		'manage_options',								/* capability */
		'ppmess_settings_page_top_menu', 				/* menu slug */
		'ppmess_show_top_menu_page'						/* function */
	);
	
	// Submenu page SETTINGS
	$submenu_page_1 = add_submenu_page( 
		'ppmess_settings_page_top_menu', 				/* parent_slug */
		__('Settings', 'ppmess'),						/* page title */
		__('Settings', 'ppmess'), 						/* menu title */
		'manage_options',								/* capability */
		'ppmess_settings_page_top_menu', 				/* menu slug */ 
		'ppmess_show_submenu_page_settings'				/* function */
	);
	
	// Submenu page ALL MESSAGES
	$submenu_page_2 = add_submenu_page( 
		'ppmess_settings_page_top_menu', 				/* parent_slug */
		__('All messages', 'ppmess'),					/* page title */
		__('All messages', 'ppmess'), 					/* menu title */
		'manage_options',								/* capability */
		'ppmess_all_messages_page_submenu', 			/* menu slug */ 
		'ppmess_show_submenu_page_all_messages'			/* function */
	);
	
	// var_dump($menu_page);
	// var_dump($submenu_page_1);
	// var_dump($submenu_page_2); exit;
}
add_action('admin_menu', 'ppmess_add_menu_pages');

function ppmess_show_top_menu_page(){
	return '';
}
function ppmess_show_submenu_page_settings(){
	include_once plugin_dir_path(__FILE__) . 'admin/settings-view.php';
}
function ppmess_show_submenu_page_all_messages(){
	include_once plugin_dir_path(__FILE__) . 'admin/all-messages-view.php';
}

global $ppmess_enabled; 
$ppmess_enabled = pmess_pivate_message_on();

// display private message on the post, switch between wordpress comments and private messages
// private message flag must be enabled by admin
// post type must be enabled by admin
if($ppmess_enabled){
	add_action('comment_form_comments_closed', 'ppmess_attached_to_post');
	add_action('comment_form_before', 'ppmess_attached_to_post');
}

// Shortcode - display private messages, custom page created by admin
function ppmess_shortcodes_init(){
	
	global $ppmess_enabled;
	
	/* Define a salt for new message */
	define('SALT_NEW_PPMESS', 'egassem-wen-dda');

	/* Define a salt for single comunication */
	define('SALT_SINGLE_COMMUN_PPMESS', 'noitacinummoc-elgnis');

	/* Define a salt for delete communication */
	define('SALT_DELETE_COMMUN_PPMESS', 'noitacinummoc-eteled');
	
	if( ! is_user_logged_in()){
		add_shortcode('ppmess-front-end', 'ppmess_shortcode_user_not_logged');
		return;
	}
	
	if( ! $ppmess_enabled ){
		add_shortcode('ppmess-front-end', 'ppmess_shortcode_disabled');
		return;
	}
	
	if(isset($_GET['single_commun']) && $_GET['single_commun'] == 1)
		add_shortcode('ppmess-front-end', 'ppmess_shortcode_all_messages_view'); // string $tag, callable $func
	elseif(isset($_GET['delete_commun']) && $_GET['delete_commun'])
		add_shortcode('ppmess-front-end', 'ppmess_shortcode_delete_communication_view'); // string $tag, callable $func
	else
		add_shortcode('ppmess-front-end', 'ppmess_shortcode_all_communications_view'); // string $tag, callable $func		
	
}
add_action('init', 'ppmess_shortcodes_init');


/*---------------------- Include javascript, css ----------------------*/
function ppmess_enqueue(){
	
	global $post;
	global $ppmess_enabled;
		
	if($ppmess_enabled /* AND post_type */ ){
		
		// SEND NEW MESSAGE
		/*--------------------------------------------------------------------------*/
		wp_enqueue_script( 'ajax_send_message',
			plugins_url( '/javascript/ajax-send-message.js', __FILE__ ),
			array('jquery'),
			false, 
			false
		);
		
		// Localize send message
		$nonce = wp_create_nonce("ppmess_send_message_nonce");
		wp_localize_script( 'ajax_send_message', 'ppmess_send_message_obj', array(
				'ajax_url' 				=> admin_url( 'admin-ajax.php' ),
				'send_message_nonce'	=> $nonce,	
			) 
		);
	}
	
	if($ppmess_enabled && (strpos($post->post_content, '[ppmess-front-end]' ) !== FALSE) ){
		
		// DELETE communication
		/*---------------------------------------------------------------------------*/
		wp_enqueue_script( 'ajax_delete_commun',
			plugins_url( '/javascript/ajax-delete-commun.js', __FILE__ ),
			array('jquery'),
			false, 
			false
		);
		
		// Localize delete communication
		$nonce_delete = wp_create_nonce("ppmess_delete_commun_nonce");
		wp_localize_script( 'ajax_delete_commun', 'ppmess_delete_commun_obj', array(
				'ajax_url' 				=> admin_url( 'admin-ajax.php' ),
				'delete_commun_nonce'	=> $nonce_delete,	
			) 
		);
	}
	
	// hook on post content
	if($ppmess_enabled /* AND post_type*/ ){
		
		// NAVIGATION (post)	
		/*--------------------------------------------------------------------------*/
		wp_register_script( 
			'ppmess_jquery',
			'https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js', 
			array(), 
			false, 
			false
		);
		
		wp_enqueue_script( 
			'ppmess_front_end',
			plugins_url( '/javascript/ppmess-front-end.js', __FILE__ ), 
			array('ppmess_jquery'), 
			false, 
			false
		);
	}
	
	if($ppmess_enabled){			
		// CSS
		/*--------------------------------------------------------------------------*/
		wp_enqueue_style( 'style_front_end', 
			plugins_url( '/front-end/css/front-end.css', __FILE__ ) 
		);
	}
}
add_action( 'wp_enqueue_scripts', 'ppmess_enqueue' );

/*----------------------------------------*/
/*----------------- AJAX -----------------*/
/*----------------------------------------*/
//	Send message 
// 	action:	ppmess_send_message
add_action("wp_ajax_nopriv_ppmess_send_message", "ppmess_send_message_not_logged");
add_action( "wp_ajax_ppmess_send_message", "ppmess_send_message_logged" );

//	Delete communication 
// 	action:	ppmess_delete_commun
add_action("wp_ajax_nopriv_ppmess_delete_commun", "ppmess_delete_commun_not_logged");
add_action( "wp_ajax_ppmess_delete_commun", "ppmess_delete_commun_logged" );
















