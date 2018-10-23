<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*-------------------------------------------------------------------------------------*/
/*--------------------------------- Database function ---------------------------------*/
/*-------------------------------------------------------------------------------------*/

/**
 *	Insert new message
 * 	version:	1.0
 *	@param:		(array)
 *	@return:	(int) ID created message or (boolean) FALSE if fail 
*/
function ppmess_insert_private_message($data){
	
	global $wpdb;	
	
	if( empty($data['sender_id']) || empty($data['receiver_id']) || empty($data['post_id']) )
		return FALSE;
	
	if(!is_numeric($data['sender_id']) || !is_numeric($data['receiver_id']) || !is_numeric($data['post_id']))
		return FALSE;
	
	$sender_id = $data['sender_id'];
	$receiver_id = $data['receiver_id'];
	$post_id = $data['post_id'];
	
	// Check whether there is communication between these two users
 	$select_query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE ((sender_id = $receiver_id AND receiver_id = $sender_id ) 
					OR (sender_id = $sender_id AND receiver_id = $receiver_id)) 
					AND post_id = $post_id AND message_parent = 0 ORDER BY date_created ASC LIMIT 1";
	
	$result = $wpdb->get_results( $select_query, ARRAY_A );

	if($wpdb->num_rows == 1){ // One message exists
	
		$message_parent = $result[0]['message_id'];
		$show_status = $result[0]['show_status'];
		$post_name = $result[0]['post_name'];
		
	}else{  // No, this is first one
		$message_parent = 0;
		
		$post_info = get_post($data['post_id']);
		$post_name = $post_info->post_title;
		
		if($data['sender_id'] != $post_info->post_author)
			$user2 = $data['sender_id'];
		else
			$user2 = $data['receiver_id'];
		
		$show_status = '_' . $post_info->post_author . '_' . $user2;
	}
	
	// stripslashes !
	$data_value = array(
		'message_content' 	=> sanitize_text_field($data['message_content']), 
		'sender_id' 		=> intval($data['sender_id']), 
		'receiver_id' 		=> intval($data['receiver_id']), 
		'post_id' 			=> intval($data['post_id']),
		'post_name' 		=> $post_name, 
		'message_parent' 	=> $message_parent, // Za drugu, trecu.., i naredne poruke parent je = message_id prve poruke
		'show_status' 		=> $show_status,
		'sent_to' 			=> intval($data['receiver_id']) // oznacavanje sent_to u prvom redu ako je parent == 0 (postoji samo jedna poruka) ILI u redu gde je upisan novi komentar ako vec postoji jedna poruka (parent != 0)	
	);
	
	$wpdb->insert( $wpdb->prefix . 'private_messages', $data_value, array('%s', '%d', '%d', '%d', '%s', '%d', '%s', '%d') );	
	$new_message_id = $wpdb->insert_id;
	
	// Oznacavanje sanduceta o prijemu nove poruke
	// Sanduce se oznacava promenom kolone sent_to = 'receiver_id' ( ID user-a koji prima poruku)
	if($new_message_id){
		
		// kolona sent_to ima dve funkcije 1) oznacava korisnika koji prima poruku, 2) i u isto vreme status poruke da li je procitana (!= 0) ili ne ( = 0 )	
		// ako postoji vise poruka od jedne potrebno je dodatno oznaciti sent_to za trenutno upisanu poruku u PRVOM REDU (message_parent == 0)
		if($message_parent > 0){			
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}private_messages SET sent_to = %d WHERE message_parent = %d
					AND post_id = %d AND ((sender_id = %d AND receiver_id = %d ) 
					OR (sender_id = %d AND receiver_id = %d))", 
					$data['receiver_id'], 0, $data['post_id'], $data['receiver_id'], $data['sender_id'], 
					$data['sender_id'], $data['receiver_id']) );	
		}
		
		return $new_message_id;
		
	}else{
		return FALSE;
	} 
}

/**
 *	Returnt number of unread messages
 *	version:	1.0
 *	@param:		(int) ID message, (int)ID post, (int)ID logged user
 *	@return		(int) messages number 
*/
function ppmess_all_unread_messages($message_id, $post_id, $logged_user_id){
	
	global $wpdb;
	
	$query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE (message_id = $message_id AND message_parent = 0 AND sent_to = $logged_user_id)
				OR (message_parent = $message_id AND sent_to = $logged_user_id)";
				
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	if($wpdb->num_rows == 1)
		return $wpdb->num_rows;
	elseif($wpdb->num_rows > 1)
		return $wpdb->num_rows - 1;
	else
		return 0;
}

/**
 *	Get all communications for logged user
 *  #	sortirano po datumu po opadajucem redosledu
 *	# 	prvo komunikacije koje sadrze neprocitanu poruku zatim ostale komunikacije
 *  #	ako ima vise neprocitanih poruka potrebno izvrsiti naknadno sortiranje
 * 	version:	1.0
 *	@param:		(int) ID logged user
 *	@return:	(array) all communications for logged user 
*/
function ppmess_get_all_communications($logged_user){
	
	global $wpdb;	 
	
	$show_status = '_' . $logged_user; 
	
	$query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE (sender_id = $logged_user OR receiver_id = $logged_user)
				AND message_parent = 0 AND show_status LIKE '%$show_status%' ORDER BY FIELD(sent_to, $logged_user) DESC";
	
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	if($wpdb->num_rows > 0)
		return $result;
}

/**
 *	Change sent_to => 0 (zero) for ALL rows
 * 	version:	1.0
 *	@param:		(int) ID message, (int) ID post, (int) ID logged user
 *	@return:	(boolean) true if successful
*/
function ppmess_change_commun_status($message_id, $post_id, $logged_user){
	
	global $wpdb;
	
	$status = 0; // when sent_to = 0 message is read, if sent_to != 0 message is not read
	
	return $wpdb->query(
		$wpdb->prepare("UPDATE {$wpdb->prefix}private_messages SET sent_to = %d WHERE (post_id = %d AND message_id = %d AND message_parent = 0 AND sent_to = %d) 
			OR (post_id = %d AND message_parent = %d AND receiver_id = %d) ", $status, $post_id, $message_id, $logged_user, $post_id, $message_id, $logged_user )
	);
}

/**
 *	Change sent_to => 0 (zero) only ONE ROW where message_parent = 0
 *  version:	1.0
 * 	@param:		(int)ID logged user, (int)ID post's author, (int)ID post
 *	@return:	(boolean) true if successful 
*/
function ppmess_change_commun_status_2($logged_user, $author_id, $post_id){
	global $wpdb;
	
	$status = 0; // when sent_to = 0 message is read, if sent_to != 0 message is not read
	
	return $wpdb->query(
		$wpdb->prepare("UPDATE {$wpdb->prefix}private_messages SET sent_to = %d WHERE ( post_id = %d AND ((receiver_id = %d AND sender_id = %d) 
			OR (receiver_id = %d AND sender_id = %d)) AND message_parent = 0 AND sent_to = %d )", 
				$status, $post_id, $logged_user, $author_id, $author_id, $logged_user, $logged_user)
	);
}

/**
 *	Get the second person having a conversation
 *  version:	1.0
 * 	@param:		(int)ID message, (int)ID post, (int)ID logged user
 *	@return:	(int) ID of user different then logged user or (boolean) false if fail 
*/
function ppmess_get_user2($message_id, $post_id, $logged_user){
	
	global $wpdb;
	
	$query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE ((sender_id = $logged_user) OR (receiver_id = $logged_user)) 
			AND message_parent = 0 AND message_id = $message_id AND post_id = $post_id ORDER BY date_created DESC LIMIT 1";
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	// Return ID user having conversation
	if($wpdb->num_rows > 0){
		
		if($result[0]['sender_id'] == $logged_user)
			return $result[0]['receiver_id'];
		else
			return $result[0]['sender_id'];
	}
	else
		return FALSE;
}

/**
 *	Check to see if logged user also the author of current the post
 *  version:	1.0
 * 	@param:		(int)ID message, (int)ID post, (int)ID logged user
 *	@return:	(boolean) true if successful or false if fail 
*/
function ppmess_user_legality($message_id, $post_id, $logged_user){
	
	global $wpdb;
	
	$query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE (message_id = $message_id AND message_parent = 0 AND post_id = $post_id ) 
				AND ( receiver_id = $logged_user OR sender_id = $logged_user ) LIMIT 1";
			
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	if($wpdb->num_rows == 1)
		return TRUE;
	else 
		return FALSE;
}

/**
 *	Get date created of the last message
 *	version:	1.0
 *	@param:		(int)ID message, (int)post ID, (int)ID logegd user
 *	@return:	(date) date created of last message or (boolean) false if fail 
*/
function ppmess_get_last_message_date($message_id, $post_id, $logged_user){
	
	global $wpdb;
	
	$query = "SELECT date_created FROM {$wpdb->prefix}private_messages WHERE ((message_parent = $message_id OR ( message_parent = 0 AND message_id = $message_id))
			AND post_id = $post_id AND ( receiver_id = $logged_user OR sender_id = $logged_user )) ORDER BY date_created DESC LIMIT 1";
			
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	if($wpdb->num_rows == 1)
		return $result[0];
	else 
		return FALSE;	
}

/**
 *	Get last message 
 *  version:	1.0
 *	@param:		(int)ID post, (int)ID logged user, (int)ID user_2
 *	@Return: 	(array) last created message or (boolean) false if fail
*/
function ppmess_get_last_message($post_id, $logged_user, $user2){
	
	global $wpdb;
	
	$query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE post_id = $post_id AND ( ( receiver_id = $logged_user AND sender_id = $user2 ) OR (receiver_id = $user2 AND sender_id = $logged_user) ) ORDER BY date_created DESC LIMIT 1";
			
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	if($wpdb->num_rows == 1)
		return $result[0];
	else 
		return FALSE;
}

/**
 *	Get all messages for single communicatiions
 * 	version:	1.0
 *	@param:		(int) ID message, (int) ID post
 *	@return:	(array) list of messages or (boolean) FALSE if fail 
*/
function ppmess_get_all_messages($message_id, $post_id){
	
	global $wpdb;
	
	$query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE (message_id = $message_id AND message_parent = 0 AND post_id = $post_id) 
				OR (message_parent = $message_id AND post_id = $post_id)";
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	if($wpdb->num_rows > 0)
		return $result;
	else 
		return FALSE;
}

/**
 *	Get all messages for single communicatiions 2
 *	#	If logged user and author of the post are same empty result will be return 
 * 	version:	1.0
 *	@param:		(int)ID logged user, (int)ID author post, (int)ID post
 *  @return:	(array) list of messages or (boolean) FALSE if fail
*/
function ppmess_get_all_messages_2($logged_user, $author_id, $post_id){
	
	global $wpdb;
	
	$show_status = '_' . $logged_user;

	$query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE ( (sender_id = $logged_user AND receiver_id = $author_id) OR (sender_id = $author_id AND receiver_id = $logged_user) ) 
	AND post_id = $post_id AND show_status LIKE '%$show_status%' ORDER BY date_created ASC";
	
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	if($wpdb->num_rows > 0)
		return $result;
	else
		return FALSE;
}

/**
 *	Get the first message of the single communication
 *	version:	1.0
 * 	@param:		(int)ID message, (int)ID post, (int)ID logged user
 *  @return:	(array) data or (boolean) false if fail
*/
function ppmess_get_single_commun($message_id, $post_id, $user_logged){
	
	global $wpdb;
	
	$query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE message_id = $message_id AND post_id = $post_id AND message_parent = 0 AND ( receiver_id = $user_logged OR sender_id = $user_logged )";
	
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	if( $wpdb->num_rows > 0 )
		return $result;
	else
		return FALSE;
}

/**
 *	Delete single communication, change show_status column => 0
 *	#	Funkcija ne brise trajno celokupnu komunikaciju (svaki komentar ostvaren sa sagovornikom)
 *	#	Funkcija menja status za korisnika koji zeli da obrise komunikaciju u "neaktivan status"
 *	# 	npr. komunikacija izmedju korisnika 1 i 4 bi bila _1_4, ako je korisnik "1" obrisao komunikaciju bila bi ___4
 *	#	Promena statusa se vrsi u svim komentarima komunikacije, ne samo u prvom komentaru gde je parent = 0
 *	
 *	#	Ako se desi da postoji ne procitana poruka u komunikaciji i kao takva komunikacija bude obrisana 
 *	#	i polje "sent_to" se menja u stanje "0" 
 *
 *	version:	1.0
 *	@param:		(int)ID message, (int)ID post, (int)ID logged user
 *  @return:	(array) or (boolean) false if fail
*/
function ppmess_delete_commun($message_id, $post_id, $user_logged){
	
	global $wpdb;	
	
	// default state: npr. for user_id = 1 and user_id = 2   "_1_2" OR "_2_1"
	$new_status = '__'; // deaktiviranje aktivnosti diskusije za trenutnog korisnika
	$search_status = '_' . $user_logged; // trenutni status u bazi ( pocetan vrednost je "_NUMBER" - znaci da je diskusija aktuelna za korisnika user_NUMBER )
										// ako nema broja NUMBER u koloni show_status diskusija za korisnika NUMBER vise nije aktivna
	$new_sent_to = 0;									
	// get current status for desired communicatiion
	$query = "SELECT show_status FROM {$wpdb->prefix}private_messages WHERE message_id = $message_id AND post_id = $post_id AND message_parent = 0 AND (receiver_id = $user_logged OR sender_id = $user_logged)";
	$current_status = $wpdb->get_results( $query, ARRAY_A );
	
	if( $wpdb->num_rows == 1 ){
		foreach($current_status as $status){
			$new_value = str_replace($search_status, $new_status, $status['show_status']); 
		}
	}else{
		return FALSE;
	}
	
	return $wpdb->query(
		$wpdb->prepare("UPDATE {$wpdb->prefix}private_messages SET show_status = %s, sent_to = %d  WHERE (message_id = %d AND post_id = %d AND message_parent = 0
			AND (receiver_id = %d OR sender_id = %d)) OR (post_id = %d AND message_parent = %d AND (receiver_id = %d OR sender_id = %d))", 
			$new_value, $new_sent_to, $message_id, $post_id, $user_logged, $user_logged, $post_id, $message_id, $user_logged, $user_logged)
	);	
}








/* Svi primljeni komentari, komentari poslati ka ulogovanom korisniku  */
function pmess_get_all_received_comments($receiver_id, $sort_date, $next_page){
	
	global $wpdb;
	$query_all = "SELECT * FROM {$wpdb->prefix}private_messages WHERE receiver_id = $receiver_id AND message_parent = 0";
	$wpdb->get_results( $query_all, ARRAY_A );
	
	if( ! isset($next_page))
		$next_p = 0;
	else
		$next_p = $next_page;
	
	// echo '<br>next_p = ' . $next_p . '<br>';
	
	$number_rows = $wpdb->num_rows;
	$num_pages = ceil($number_rows / 10);
	
	if($next_page > $num_pages)
		return FALSE;
	
	// echo 'sort_date = ' . $sort_date;
	
	switch($sort_date){
		case 'ASC': 
		$sort = 'ORDER BY date_created ASC';
		break;
		
		case 'DESC': 
		$sort = 'ORDER BY date_created DESC';
		break;
		
		case 'not_read': 
		$sort = "ORDER BY FIELD(comment_status, 'not_read', 'read')";
		break;
		
		case 'read': 
		$sort = "ORDER BY FIELD(comment_status, 'read', 'not_read')";
		break;
		
		default:
		$sort = 'ORDER BY date_created DESC';
	}				
	
	// echo '<br>sort: ' . $sort;
		
	$query = "SELECT * FROM {$wpdb->prefix}private_messages WHERE receiver_id = $receiver_id $sort LIMIT 10 OFFSET $next_p";
	$result = $wpdb->get_results( $query, ARRAY_A );
	
	return $result;
}

/*---------------------------------------------------------------------------------------------------*/
