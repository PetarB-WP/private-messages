<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	/**
	 *	Plugin activation function - create nwew table prefix_PRIVATE_MESSAGES
	*/
	function pmess_activation(){
		global $wpdb;
		$new_table = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'private_messages(
			message_id INT(11) NOT NULL AUTO_INCREMENT,
			message_parent INT(11) NOT NULL,
			message_content VARCHAR(1000),
			date_created TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
			post_id INT(11) NOT NULL,
			post_name VARCHAR(100),
			show_status VARCHAR(10),
			receiver_id INT(11) NOT NULL,
			sender_id INT(11) NOT NULL,
			sent_to INT(11) NOT NULL,
			PRIMARY KEY (message_id)
		)ENGINE=InnoDB';
		
		if($wpdb->query($new_table, OBJECT) === TRUE){
			return TRUE; // 'SUCCESSFUL CREATE TABLE';
		}else{
			return FALSE; // 'CREATE TABLE ERROR';
		}
	}