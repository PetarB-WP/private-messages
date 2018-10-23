<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Display message on the page (post private messages) if user NOT LOGGED
 * version:	1.0
*/
function ppmess_shortcode_user_not_logged(){	
	echo '<div class="ppmess-information">';
		echo __( 'You must to be logged to see private message', 'ppmess');
		echo sprintf( '<a class="post-link-ppmess " href="%s">' . __('Login in', 'ppmess') . '</a>', wp_login_url(apply_filters('the_permalink', get_permalink())) );
	echo '</div>';
}

/**
 * Display message on the page (post private messages) if private message DISABLED
 * version:	1.0
*/
function ppmess_shortcode_disabled(){
	echo '<div class="ppmess-information">';
		echo __('Private messages are disabled', 'ppmess');
		echo '<br/>';
		echo __( 'To see private messages you must enable on the admin settings', 'ppmess');		
		
		if( ! is_user_logged_in)
			echo sprintf( '<a class="post-link-ppmess " href="%s">' . __('Login in', 'ppmess') . '</a>', wp_login_url(apply_filters('the_permalink', get_permalink())) );
	echo '</div>';
}

/**
 * Prepare communication for delete
 * version:	1.0
*/
function ppmess_shortcode_delete_commun(){
	
	$data = array();
	$current_user = wp_get_current_user();
	
	/*----------------- Delete single communication -----------------*/
	if(isset($_GET['message_id']) && isset($_GET['post_id']) && $_GET['token_delete_commun']){
					
		$delete_token = md5(SALT_DELETE_COMMUN_PPMESS . $_GET['message_id'] . $_GET['post_id'] . $current_user->ID );
		
		if($delete_token == $_GET['token_delete_commun']){
			
			$result = ppmess_get_single_commun($_GET['message_id'], $_GET['post_id'], $current_user->ID);
			
			if( ! empty($result) ){
				
				$user2_id = ppmess_get_user2($result[0]['message_id'], $result[0]['post_id'], $current_user->ID);
				$user2 = get_userdata($user2_id);
				
			}else{
				return array('error' => 'empty_result');
			}
		}else{
			return array('error' => 'token_fail');
		}
	}else{
		return array('error' => 'isset_fail');
	}

	$post_to_delete = get_post($result[0]['post_id']);
	$unread_messages = ppmess_all_unread_messages($result[0]['message_id'], $result[0]['post_id'], $current_user->ID);
	
	$data = array(
		'error'				=>	FALSE,
		'user2_login_name'	=>	!empty($user2) ? $user2->user_login : '',
		'user2_id'			=>	!empty($user2_id) ? $user2_id : '',  
		'logged_user'		=>	!empty($current_user) ? $current_user->user_login : '',
		'logged_user_id'	=>	!empty($current_user) ? $current_user->ID : '',	
		'post_title'		=>  isset($post_to_delete) ? $post_to_delete->post_title : '',
		'post_url'			=>	isset($post_to_delete) ? $post_to_delete->guid : '',
		'post_author_id'	=> 	isset($post_to_delete) ? $post_to_delete->post_author : '',
		'post_id'			=>  isset($post_to_delete) ? $post_to_delete->ID : '',
		'message_id'		=>	isset($result[0]['message_id']) ? $result[0]['message_id'] : '',
		'unread_messages'	=>  isset($unread_messages) ? $unread_messages : 0
	);
	
	return $data;
}

/**
 * Delete single communication
 * version:	1.0
*/
function ppmess_shortcode_delete_communication_view(){
	
	global $post;
	$data = ppmess_shortcode_delete_commun();
?>
	<h3><?php echo esc_html__( 'Delete communication', 'ppmess' ); ?></h3>
		
<?php
	if( $data['error'] !== FALSE ):	?>
		<!-- Error occurred -->
		<div class="ppmess-info-dark">
			<?php echo esc_html__('Error occurred: ', 'ppmess');	?>
				<a class="post-link-ppmess" href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo __('Go Back', 'ppmess'); ?></a>
				<?php // echo $data['error'];  	?>
		</div>
<?php 
	else:	?>	
		<div id="ppmessDeleteCommun">
			<div class="user-info-delete-commun">
				<?php echo esc_html__('Communication bitween', 'ppmess'); ?>
				<span class="span-mark" title="<?php echo esc_attr__('Info of the logged user', 'ppmess'); ?>">
					<?php echo esc_html($data['logged_user']); ?>
				</span>
				<?php echo $data['logged_user_id'] == $data['post_author_id'] ? ' (' . esc_html__('author of the post', 'ppmess') . ') ' : '';	?>
				&#x21d4;
				<span class="span-mark" title="<?php echo esc_attr__('Info of the user 2', 'ppmess'); ?>">
					<?php echo esc_html($data['user2_login_name']);	?>
				</span>
				<?php echo $data['user2_id'] == $data['post_author_id'] ? ' (' . esc_html__('author of the post', 'ppmess') . ') ' : '';
						echo esc_html__('refers to post:', 'ppmess'); ?>
					<a class="post-link-ppmess" href="<?php echo esc_url( $data['post_url'] ); ?>" title="<?php echo esc_attr( $data['post_title'] ); ?>">
						<?php echo $data['post_title']; ?>
					</a>
					<strong><?php echo esc_html__('will be deleted !', 'ppmess'); ?></strong>
					<br/>
					<span class="colorMark-1"><?php echo esc_html__('unread messages', 'ppmess') . '(' . $data['unread_messages'] . ')'; ?></span> 
			</div>
			
			<?php	$token_delete_commun = md5(SALT_DELETE_COMMUN_PPMESS . $data['message_id'] . $data['post_id'] . $data['logged_user_id']);	?>
			
			<!--------------------- Delete communication --------------------->
			<div class="confirm-delete-commun">
				<?php // Confirm delete
					$link = admin_url('admin-ajax.php?action=ppmess_delete_commun&confirm_delete=1&post_id=' . $data['post_id'] . '&message_id=' . $data['message_id'] . '&token_delete_commun=' . $token_delete_commun . '&go_back_url=' . get_permalink($post));
					echo '<a class="ppmess-tag-a ppmess-delete-commun-confirm" data-post_id="' . $data['post_id'] . '" data-message_id="' . $data['message_id'] . '" data-confirm_delete="1" data-token_delete_commun="' . $token_delete_commun . '" data-go_back_url="' . get_permalink($post) . '"  href="' . $link . '">' . esc_html__('Confirm delete', 'ppmess') . '</a>';
				?>
				<!-- Cancel delete --->
				<a class="ppmess-tag-a ppmess-delete-commun-cancel" style="font-size:0.83em;" href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html__('Cancel', 'ppmess'); ?></a>
			</div>
		</div><!-- ppmessDeleteCommun -->
<?php	
	endif;
}

/**
 * Get all messages for single communication
 * version: 1.0
*/
function ppmess_shortcode_all_messages(){
	
	global $post;
	
	$url_page = get_permalink($post);
	$current_user = wp_get_current_user();
	
	if(isset($_GET['message_id']) && isset($_GET['post_id']) && isset($_GET['token_single_commun']))
		$token = md5(SALT_SINGLE_COMMUN_PPMESS . $_GET['message_id'] . $_GET['post_id'] . $current_user->ID );
	else
		return array('errors' => 'isset_fail');
	
	if( ! ($token == $_GET['token_single_commun']) ){
		return array('errors' => 'token_fail');
	}
	
	$message_id = intval($_GET['message_id']);
	$post_id = intval($_GET['post_id']);
	
	if( ! ppmess_user_legality($message_id, $post_id, $current_user->ID)){
		return array('errors' => 'user_legality_fail');
	}
	
	$user2_id = ppmess_get_user2($message_id, $post_id, $current_user->ID);
	$user2_info = get_userdata($user2_id);
	
	$messages = ppmess_get_all_messages($message_id, $post_id);	// FALSE or Array
	
	ppmess_change_commun_status($message_id, $post_id, $current_user->ID);
	
	$post_info = get_post($post_id);
	
	if( ! empty($post_info->post_title) )
		$post_title = $post_info->post_title;
	else
		$post_title = $messages[0]['post_name'];
			
	$data = array(
		'user_id'		=>	!empty($current_user) ? $current_user->ID : '',
		'user_login'	=>	!empty($current_user) ? $current_user->user_login : '',
		'message_id'	=> 	isset($message_id) ? $message_id : '',
		'post_id'		=>	isset($post_id) ? $post_id : '',
		'user2_id'		=>	!empty($user2_info) ? $user2_info->ID : '',
		'user2_login'	=>	!empty($user2_info) ? $user2_info->user_login : '',
		'post_title'	=>	!empty($post_title) ? $post_title : '',
		'post_status'	=>  !empty($post_info->post_status) && $post_info->post_status == 'publish' ? TRUE : FALSE,
		'post_author'	=>	!empty($post_info->post_author) ? $post_info->post_author : '',
		'page_url'		=>	esc_url($url_page),
		'messages'		=>	$messages // FALSE or array
	);
		
	return $data;
}

/**
 *	Displaying all private messages for single communication
 *	version: 1.0
*/
function ppmess_shortcode_all_messages_view(){

	$data = ppmess_shortcode_all_messages();
	
	if(isset($data['errors'])){
		echo ('An error has occurred');
		// echo $data['errors']		
		return;
	}
?>		
	<!------------ Post info ------------>
	<div class="post-info-commun">
		<?php echo __('Author of the post', 'ppmess');
			if($data['post_author'] == $data['user_id']):	?>
				<!-- logged user is author of the post -->
				<span class="span-mark"><?php echo esc_html($data['user_login']); ?></span>
				<a id="link-logout" href="<?php echo wp_logout_url(); ?>"><?php echo __('LogOut ?', 'ppmess'); ?></a>
		<?php 	
			else:	?>	
				<!-- user2 is author of the post -->
				<span class="span-mark"><?php echo esc_html($data['user2_login']); ?></span>
		<?php
			endif;
			
			echo ' | ';
			echo __('Refers to post: ', 'ppmess');		
			if( $data['post_status'] ):		?>
				<!-- post is available -->
				<a id="id-post-ppmess" href="<?php echo esc_url( get_permalink($data['post_id']) ); ?>" title="<?php echo esc_attr( $data['post_title'] ); ?>">
					<?php echo esc_html($data['post_title']); ?>
				</a>
		<?php 	
			else:  ?>
				<!-- is no longer available -->
				<span><?php echo esc_html($data['post_title']); ?></span>
		<?php 	
			endif; ?>
	</div>
		
	<!---------- Sender info ---------->
	<div class="sender-info-commun">
		<?php echo __('Talking with', 'ppmess');  // <!-- &#x20;&#x20; --><!-- space -->
			if($data['user_id'] != $data['post_author']):	?>
				<span class="span-mark" title="Logged user"><?php echo esc_html($data['user_login']); ?></span>
				<a id="link-logout" href="<?php echo wp_logout_url(); /* redirect: wp_logout_url(login-form) */ ?>"><?php echo __('LogOut ?', 'ppmess'); ?></a>				
		<?php 
			else: ?>
				<span class="span-mark" title=""><?php echo esc_html($data['user2_login']); ?></span>
		<?php 
			endif; ?>
	</div>
	
	<!-- Povratak na listu privatnih poruka -->
	<div class="ppmess-buck-link">
		<a class="ppmess-tag-a ppmess-buck-a" href="<?php echo $data['page_url']; ?>">&#x21d0;&#x20;<?php echo __('Back', 'ppmess'); ?></a>
	</div>
	
	<!---------- All messages of the single communication ---------->
	<div class="single-commun-frame">
		<div id="ppmessMessagesFrame" class="single-messages-frame">
	<?php 
			if( ! empty($data['messages']) ): 
	
				foreach($data['messages'] as $message):
					if($message['sender_id'] == $data['user_id']){
						$mess_style = 'background-color:#f0f0f0;text-align:left;';
						$sender = $data['user_login'];
					}else{
						$mess_style = 'background-color:#ffffff;text-align:right;';
						$sender = $data['user2_login'];
					}
				?>
					<div id="ppmessage-<?php echo $message['message_id']; ?>" class="single-message-frame" style="<?php echo esc_attr( $mess_style ); ?>">	
						<p class="p-row1-pmess">
							<?php echo __('from', 'ppmess'); ?>
							<span style="color:#0073aa;font-style:italic;"><?php echo esc_html( ' ' . $sender); ?></span>
							<?php echo ' | '; ?>
							<?php echo __('date', 'ppmess'); ?>
							<span style="color:#0073aa;font-style:italic;"><?php echo esc_html( date('d-M-Y ', strtotime($message['date_created'])) ); ?></span>
							<?php echo ' | '; ?>
							<?php echo __('time', 'ppmess'); ?>
							<span style="color:#0073aa;font-style:italic;"><?php echo esc_html( date('G:i ', strtotime($message['date_created'])) ); ?></span>
						</p>
						<p class="p-row2-pmess"><?php echo $message['message_content']; ?></p>
					</div>
		<?php	endforeach;
			endif;	?>
			<!-------------- New Comment - AJAX ---------------------------->
			<!---- There is append new <div> element with a new message ---->
			<!-------------------------------------------------------------->
		</div>
<?php 	if( $data['messages'] == FALSE ):	// will never be happen ?>
			<!-- No messages -->
			<div class="ppmess-info-dark">
				<?php echo __('No messages exists !', 'ppmess'); ?>
			</div>
	<?php 	
		endif;
		
		// post is available
		if( $data['post_status'] ):		?>			
			<h4 class="ppmess-h4">
				<?php echo __('Send private message to author of the post', 'ppmess'); ?>
			</h4>			
			<!-------------------- Error message -------------------------->
			<!----------- here will be displayed error if occurred -------->	
			<!------------------------------------------------------------->
			
			<!------------------------  Form za slanje komentara ------------------------>
			<form id="ppmessNewMessForm" method="POST" action="">
				<label for="ppmessMessageContent"><?php /* echo __('Message', 'ppmess'); */ ?></label><br/>
				<textarea name="ppmess_message_content" type="text" id="ppmessMessageContent" rows="10" autofocus placeholder="<?php echo __('Leave the message', 'ppmess'); ?>"></textarea>
			</form>
			<div class="single-commun-submit">
				<?php
					$link = admin_url('admin-ajax.php?action=ppmess_send_message&post_id=' . $data['post_id'] . '&message_id=' . $data['message_id'] . '&user2_id=' . $data['user2_id'] . '&current_user=' . $data['user_id'] .
						'&message_content_id=ppmessMessageContent'
					);
					echo '<a class="ppmess-tag-a ppmess-send-message ppmess-ajax" data-post_id="' . $data['post_id'] . '" data-message_id="' . $data['message_id'] . '" 
					data-user2_id="' . $data['user2_id'] . '" data-current_user="'. $data['user_id'] . 
					'" data-message_content_ID="ppmessMessageContent" href="' . $link . '">Send message</a>';
				?>
			</div>	
	<?php 
		else:	?>
			<!-- post no longer available -->
			<div class="ppmess-info-dark">
				<?php printf(__('Post %s no longer available !', 'ppmess'), '<span style="color:#1f3d7a;font-style:italic;">' . $data['post_title'] . '</span>'); ?>
			</div>
	<?php 
		endif;	?>
	</div>
	
	<?php
}

/**
 * Get all communications, communications with new messages will be displayed first
 * version:	1.0
*/
function ppmess_shortcode_all_communications(){

	$data = array();
	$current_user = wp_get_current_user();

	/*------------------------- Logged user info -------------------------*/
	$data['logged_user'] = array(
		'user_login'	=> $current_user->user_login, 
		'user_id'		=> $current_user->ID
	);
	
	$all_communs = ppmess_get_all_communications($current_user->ID);
	
	/*----------- Zamena datuma diskusije sa najnovijim datumom -----------*/
	if(! empty($all_communs)){
		foreach($all_communs as $key => $commu){
			
			$last_data_mess = ppmess_get_last_message_date($commu['message_id'], $commu['post_id'], $current_user->ID);
			
			// change the message with the latest message
			if( ! empty($last_data_mess)){
				$all_communs[$key]['date_created'] = $last_data_mess['date_created'];
			}	
		}
	}
		
	/*------------ Division the list of communications into two parts -----------*/
	/*-------- part1: unread messages (sent_to == logged user ID) ---------------*/
	/*-------- part2: rest of the messages --------------------------------------*/
	$part1_arr = array();
	$part2_arr = array();
	if(! empty($all_communs)){
		foreach($all_communs as $key => $value){
			if($value['sent_to'] ==  $current_user->ID){
				$part1_arr[] = $all_communs[$key];					
			}else{
				$part2_arr[] = $all_communs[$key];
			}
		}
	}
	
	/*------------- Sorting first part by date release, decreasing order -------------*/
	$date_created = array();
	if(! empty($part1_arr)){
		foreach ($part1_arr as $key => $row){
			$date_created[$key] = $row['date_created'];
		}
		// array_multisort($sent_to, SORT_DESC, $date_created, SORT_DESC, $part1_arr);
		array_multisort($date_created, SORT_DESC, $part1_arr);
	}
	
	/*------------- Sorting second part by date release, decreasing order -------------*/
	$date_created = array();
	if(! empty($part2_arr)){
		foreach ($part2_arr as $key => $row) {
			$date_created[$key] = $row['date_created'];
		}
		array_multisort($date_created, SORT_DESC, $part2_arr);
	}
	
	/*---------------- Connect two parts into one ------------------*/
	$data['all_communs'] = array_merge($part1_arr, $part2_arr);
	
	/*----------- Adding new row 'number_messages' per communication -----------*/
	foreach($data['all_communs'] as $key => $row){
		// new row
		$data['all_communs'][$key]['number_messages'] = ppmess_all_unread_messages($row['message_id'], $row['post_id'], $current_user->ID);	
	}
		
	return $data;
}

/**
 * Displaying communications for logged user
 * version:	1.0 
 */
function ppmess_shortcode_all_communications_view($atts = [], $content = null){
	
	global $post;
	
	$data = ppmess_shortcode_all_communications();
?>	
	<!--------- User info --------->
	<div class="post-info-commun">
		<?php echo __('Logged user', 'ppmess'); ?>
		<span class="span-mark"><?php echo esc_html($data['logged_user']['user_login']); ?></span>
		<a id="link-logout" href="<?php echo wp_logout_url(); ?>">
			<?php echo __('LogOut ?', 'ppmess'); ?>
		</a>
		<div class="circle-mark-div-frame">
			<div class="ppmess-box-inline">
				<span class="circle-mark-1"></span>
				<span style="margin-left:5px;"><?php echo __('my posts', 'ppmess'); ?></span>
			</div>
			<div class="ppmess-box-inline">
				<span class="circle-mark-2"></span>
				<span style="margin-left:5px;"><?php echo __('sender\'s posts', 'ppmess'); ?></span>
			</div>
		</div>	
	</div>
			
	<div class="ppmess-commun-frame">
<?php	
		if( ! empty($data['all_communs'])): ?>
			<!------------ List of the communications ------------>
			<div id="communsListFrame" class="communsList-frame">				
				<ul>
					<li>
						<div><?php echo __('Sender', 'ppmess'); ?></div>
						<div><?php echo __('Post title', 'ppmess'); ?></div>
						<div><?php echo __('Date', 'ppmess'); ?></div>
						<div><?php echo __('Status', 'ppmess'); ?></div>
						<div></div>
					</li>
				<?php	
					foreach( $data['all_communs'] as $value ):					
						
						$post_info = get_post($value['post_id']);
						$post_author_id = $post_info->post_author;	
						
						// Determining secound user
						if($value['sender_id'] != $data['logged_user']['user_id'])
							$sender = get_userdata($value['sender_id']);
						else
							$sender = get_userdata($value['receiver_id']);
						
						// Mark new message
						if($value['sent_to'] == $data['logged_user']['user_id']){
							$status_class = 'ppmess-new-message';
							$status_mess = '';
							$title_mess = __('new message', 'ppmess');
						}else{
							$status_class = '';	
							$status_mess = 'OK';
							$title_mess = __('message read', 'ppmess');
						}
						
						// check if the post was deleted or not
						if( ! empty($post_info->post_title) )
							$post_title = $post_info->post_title;	
						else
							$post_title =  $value['post_name'];
													
						// token
						$token_single_commun = md5(SALT_SINGLE_COMMUN_PPMESS . $value['message_id'] . $value['post_id'] . $data['logged_user']['user_id'] );
						$token_delete_commun = md5(SALT_DELETE_COMMUN_PPMESS . $value['message_id'] . $value['post_id'] . $data['logged_user']['user_id'] );
					?>
						<li onmouseover="ppmess_change_style(this)" onmouseout="ppmess_return_style(this)">
							<!----------------------- Sender ---------------------->
							<div><?php echo esc_html($sender->user_login); ?></div>
							
							<!-------------------- Post title -------------------->
							<?php if( !empty($post_info->post_status) && $post_info->post_status == 'publish'): ?>
									<div><?php echo esc_html($post_title); ?>
										<?php if($data['logged_user']['user_id'] == $post_author_id): ?>
												<br/><span class="circle-mark-1"></span>
										<?php else: if($sender->ID == $post_author_id): ?>
												<br/><span class="circle-mark-2"></span>
										<?php endif; endif; ?>
									</div>
							<?php else: ?>
									<div><span style="font-style:italic;text-decoration:line-through;">
										<?php echo esc_html($post_title); ?></span>
										<?php if($data['logged_user']['user_id'] == $post_author_id): ?>
												<br/><span class="circle-mark-1"></span>
										<?php else: if($sender->ID == $post_author_id): ?>
												<br/><span class="circle-mark-2"></span>
										<?php endif; endif; ?>
									</div>
							<?php endif; ?>							
							
							<!------------------ Created message date --------------------->
							<div><?php echo esc_html(date('d-M-Y', strtotime($value['date_created'])) ); ?></div>
							
							<!--------------------- Message status -------------------------->
							<div class="<?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($title_mess); ?>">
								<?php echo esc_html($status_mess); ?>
								<?php if($value['number_messages'] > 0): ?>
									<span class="colorMark-1" style="float:right;"><?php echo ' (' . $value['number_messages'] . ')'; ?></span>
								<?php endif;?>	
							</div>
							
							<!--------------------- View, Delete links --------------------->
							<div>
								<!-------------- Delete single communication -------------->
								<a class="delete-commun-a" title="<?php echo __('Delete the single communication', 'ppmess'); ?>" href="<?php echo esc_url( get_permalink() . '?delete_commun=1&message_id=' . $value['message_id'] . 
										'&post_id=' . $value['post_id'] . '&token_delete_commun=' . $token_delete_commun ); ?>">
									<?php echo __('Delete', 'ppmess'); ?>
								</a>
								<!-------------- View single communication -------------->
								<a class="view-commun-a" title="<?php echo __('View the single communication', 'ppmess'); ?>" href="<?php echo esc_url(get_permalink($post) . '?message_id=' . $value['message_id'] . 
										'&post_id=' . $value['post_id'] . '&token_single_commun=' . $token_single_commun . '&single_commun=1#ppmessage-' . $value['message_id']); ?>">
									<?php echo __('View', 'ppmess'); ?>
								</a>
							</div>
						</li>
				<?php	
					endforeach;	?>
				</ul>
			</div><!-- all-communications -->
<?php	else: ?>
			<div class="ppmess-info-light">
				<span class="colorMark-1"><?php echo __('No one communication established', 'ppmess'); ?></span>
				<br/><br/>
				<?php echo __('For start communication you need a looked to certain post first and send private message to author of the post', 'ppmess'); ?>
			</div>
<?php 	endif; ?>						
	</div><!-- ppmess-commun-frame -->
	
	<?php
	return $content;
}

/** 
 *  Processing ajax handler to send new message for NOT logged user
 *	version:	1.0
*/ 
function ppmess_send_message_not_logged(){	
	wp_die('send message: user not logged');
}

/**
 *	Processing ajax handler to send new message for logged user
 *	version:	1.0	
*/
function ppmess_send_message_logged(){
	
	// var_dump(DOING_AJAX);
	// echo constant('DOING_AJAX');
	// exit;
	
	check_ajax_referer("ppmess_send_message_nonce", "send_message_nonce", false);
	
	// if( defined( 'DOING_AJAX' ) && DOING_AJAX ){ 
	
		// podaci za upis novog komentara u bazu
		$data = array();
		
		if( ! empty($_POST['message_content']) ){
			if( strlen($_POST['message_content']) < 500 ){
				// stripslashes deep
				$data['sender_id'] = intval($_POST['current_user']);
				$data['receiver_id'] = intval($_POST['user2_id']);
				$data['post_id'] = intval($_POST['post_id']);
				$data['message_content'] = sanitize_text_field($_POST['message_content']);
		
				//-----------------  INSERT NEW MESSAGE -----------------------
				$new_message = ppmess_insert_private_message($data);
				
				if($new_message){
					
					// get last added message
					$last_message = ppmess_get_last_message($_POST['post_id'], $_POST['current_user'], $_POST['user2_id']);					
					
					if( ! empty($last_message)){
						$sender = $last_message['sender_id'];
						$receiver = $last_message['receiver_id'];
					
						// determine and highlight a SENDER 
						if($sender == $_POST['current_user']){	
							$message_style = esc_html('background-color:#f0f0f0;text-align:left;');
							$logged_user = get_userdata($_POST['current_user']);
							$sender = $logged_user->user_login;
						}else{
							$message_style = esc_html('background-color:#ffffff;text-align:right;');
							$user2_name = get_userdata($sender);
							$sender = $user2_name->user_login;
						}
						
						$sender = esc_html( $sender );
						$result = array(
							'type'				=> 'ppmess_success',
							'message_style'		=> 	$message_style,
							'sender'			=>	$sender,
							'message_content'	=>	$last_message['message_content'],
							'message_id'		=>  $last_message['message_id'],
							'w_from'			=>	__('from', 'ppmess'),
							'w_date'			=>	__('date', 'ppmess'),
							'w_time'			=>	__('time', 'ppmess'),
							'success_message'	=>	__('Message successfully sent', 'ppmess'),
							'date_dMY'			=>  esc_html(date('d-M-Y ', strtotime($last_message['date_created']))), 
							'date_Gi'			=>	esc_html(date('G:i ', strtotime($last_message['date_created'])))
						);
						
						if( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
							wp_send_json($result);
						else{
							$error_message = esc_html__('An error has eccurred (0)', 'ppmess');	
							// header("Location: " . $_SERVER["HTTP_REFERER"]);
						}						
					}else{
						$error_message = esc_html__('An error has eccurred (1)', 'ppmess');
					}
				}else{
					$error_message = esc_html__('An error has occurred (2)', 'ppmess');
				}	
			}else{
				$error_message = esc_html__( 'Maximun number of 500 characters exceeded', 'ppmess');
			}
		}else{
			$error_message = esc_html__('Empty message', 'ppmess');
		}
		
		$result = array(
			'type' => 'ppmess_fail',
			'error_message'	=> $error_message
		);
		
		if( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
			wp_send_json($result);
		else
			header("Location: " . $_SERVER["HTTP_REFERER"]);
		
		
		// }// DO IT AJAX 
	wp_die();
}

/**
 * Processing ajax delete communication for NOT logged user
 * version:	1.0
*/
function ppmess_delete_commun_not_logged(){
	wp_die('delete communication:	user not logged');
}

/** 
 * Processing ajax delete communication for logged user
 * version:	1.0
*/
function ppmess_delete_commun_logged(){
	
	check_ajax_referer("ppmess_delete_commun_nonce", "delete_commun_nonce", false);
		
	if( isset($_POST['confirm_delete']) && $_POST['confirm_delete'] == 1 && isset($_POST['message_id']) && isset($_POST['post_id']) && $_POST['token_delete_commun']){
		
		$current_user = wp_get_current_user();
		$delete_commun = md5(SALT_DELETE_COMMUN_PPMESS . $_POST['message_id'] . $_POST['post_id'] . $current_user->ID );
		
		if($delete_commun == $_POST['token_delete_commun']){
			
			/*----------------- Brisanje komunikacije ---------------*/
			// $delete = ppmess_delete_commun($_POST['message_id'], $_POST['post_id'], $current_user->ID);
			$delete = 1;
			if($delete === 1){
				
				$result = array(
					'type'				=> 'delete_commun_success',
					'success_message'	=> __('Communication successfuly deleted ', 'ppmess'),
					'go_back_url'		=> isset($_POST['go_back_url']) ? esc_url($_POST['go_back_url']) : esc_url(site_url())
				);
				
				if( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
					wp_send_json($result);
				else{
					wp_redirect( $_SERVER["HTTP_REFERER"] );
					exit;
				}
			}else{
				$error_code = 'delete_fail';
			}
		}else{
			$error_code = 'token_fail';
		}
	}else{
		$error_code = 'isset_fail';
	}
	
	$result = array(
		'type'			=>	'delete_commun_fail',
		'error_message'	=> 	__('An error has occurred', 'ppmess'),
		'error_code'	=>	$error_code
	);
	
	if( ! empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
		wp_send_json($result);
	else{
		wp_redirect( $_SERVER["HTTP_REFERER"] );
		exit;
	}
}






