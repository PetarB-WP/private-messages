<?php 
	
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
	/*
		Plugin uninstall
	*/
	
	// if uninstall.php is not called by WordPress, die
	if (!defined('WP_UNINSTALL_PLUGIN')){
		die;
	}
	
	global $wpdb;
	$new_table = 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'private_messages';
	
	if($wpdb->query($new_table, OBJECT) === TRUE){
		return TRUE; // 'SUCCESSFUL REMOVE TABLE';
	}else{
		return FALSE; // 'REMOVE TABLE ERROR';
	}
	