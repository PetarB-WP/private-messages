<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 *	Checks content of page, if contains pattern (short-code) return TRUE, if not return FALSE
 *	@param:		$page:	 page URL or page ID
 *  @return:	boolean
 *	version:	1.0
*/
function ppmess_check_short_code($page){
	
	if( empty($page) )
		return FALSE;
	
	// page ID is given
	if(is_numeric($page) && $page > 0){
		
		$page_id = $page;		
	
	}else{	// page URL is given	
		$id = url_to_postid($page);
		if( is_numeric($id) && $id > 0)
			$page_id = $id;
	}
	
	if(isset($page_id)){
		$page_info = get_post($page_id);
		if(isset($page_info)){
			if(strpos($page_info->post_content, '[ppmess-front-end]') !== FALSE)
				return TRUE;
		}
	}
	
	return FALSE;
}

/**
 *	Checks before displaying private message
 *	version:	1.0
*/
function ppmess_attached_to_post(){
	
	// is it current post type allowed
	if( ! ppmess_allowed_post_types())
		return '';
	
	$post_id = get_the_ID();
	
	// if user not logged
	if( ! is_user_logged_in()){
		
		echo '<div class="ppmess-info-light">';
		echo sprintf( __('Send private message to author of the post', 'ppmess') . '<a class="post-link-ppmess " href="%s">' . __('Login', 'ppmess') . '</a>', wp_login_url(apply_filters('the_permalink', get_permalink($post_id))) );
		echo '</div>';
		
	}else{
		ppmess_all_messages_attached_to_post_view();
	}
}

/**
 * Prepare data to display private messages on the post
 * version:	1.0
*/
function ppmess_all_messages_attached_to_post(){
	
	$data = array();
	
	/* Redirect to location if an error occurs */
	// $location = esc_url(site_url(''));
	
	// Fill class attribute on menu navigations
	//-------------------------------------------------------
	$data['mark_nav'] = array(
		'mark_pm'		=> 'unmark',
		'mark_wp'		=> 'mark',
		'content_pm'	=> 'display: none; opacity:0.0;',
		'content_wp' 	=> 'display: block' // class "content_wp" not used, id #respond is use
	);
	
	$current_user = wp_get_current_user();
	$post_id = get_the_ID();
	$post = get_post($post_id);
	
	// All private messages related to current post between logged user and author of the post
	//-----------------------------------------------------------------------------------------
	$user2_name = get_userdata($post->post_author);					
	
	if($post->post_author != $current_user->ID){
		
		$data['users_status'] = 'different_users';
		$all_messages = ppmess_get_all_messages_2($current_user->ID, $post->post_author, $post_id);
	
		// change status of the communication, message has been read
		if($all_messages[0]['message_parent'] == 0)
			ppmess_change_commun_status($all_messages[0]['message_id'], $post_id, $current_user->ID);
		
	}else{
		$data['users_status'] = 'euqal_users';
		
		// determinate link for page --> private messages <-- if pega exists and if selected
		$pmess_option_serial = get_option('ppmess_options');
		if( ! empty($pmess_option_serial) ){
			
			$pmess_option = maybe_unserialize($pmess_option_serial);
			
			if( ! empty($pmess_option['selected_page_options']) 
				&& ppmess_check_short_code($pmess_option['selected_page_options']['page_id']) ){
				
				$data['url_post_private_messages'] = esc_url($pmess_option['selected_page_options']['page_url']);
			
			}else{
				if( ! empty($pmess_option['url_page_options']) ){
					
					if( ppmess_check_short_code(esc_url($pmess_option['url_page_options'])) ){
						$data['url_post_private_messages'] = esc_url($pmess_option['url_page_options']);
					}else{
						$data['url_post_private_messages'] = FALSE;
					}
				}
			}
		}
	}
	
	// 1) will be empty if logged user and author of the post are same person
	// 2) author of the post can't see messages on this page
	if( ! empty($all_messages)){		
		$data['all_messages'] = $all_messages;
	}
	
	$data['current_user'] = array(
		'user_id' 		=> $current_user->ID,
		'user_login' 	=> $current_user->user_login
	);
	
	$data['post'] = array(
		'user2_id'		=> 	$post->post_author,
		'user2_name' 	=> 	$user2_name->user_login,
		'post_id' 		=>	$post->ID,
		'post_title'	=>	$post->post_title
	);
	
	return $data;
}

/**
 *	Displaying private messages with possibility to switch between wordpress comment and post private messages
 *	version: 1.0
*/
function ppmess_all_messages_attached_to_post_view(){
	
	$data = ppmess_all_messages_attached_to_post();		?>
		
		<!-- navigation menu -->
		<ul class="ppmess-ul-navMenu">
			<li>
				<a id="ppmessWpMenuId" href="#ppmessWpHref" class="ppmess-a-navMenu <?php echo esc_attr($data['mark_nav']['mark_wp']); ?>">
					<?php echo __('WP Comments', 'ppmess'); ?>
				</a>
			</li>
			<li>
				<a id="ppmessPmMenuId" href="#ppmessPmHref" class="ppmess-a-navMenu <?php echo esc_attr($data['mark_nav']['mark_pm']); ?>">
					<?php echo __('Private Messages', 'ppmess'); ?>
				</a>	
			</li>
		</ul>
		
		<!--------------- Private messages for logged user --------------->
		<div id="ppmessSingleCommun" class="ppmess-single-commun" style="<?php echo esc_attr($data['mark_nav']['content_pm']); ?>">
			<h4 class="ppmess-h4">
				<?php echo __('Send private message to author of the post', 'ppmess'); ?>
			</h4>
		<?php	
			if( $data['users_status'] == 'different_users'):	?>
				<div id="ppmessMessagesFrame" class="single-messages-frame">
			<?php 
					if( ! empty($data['all_messages']) ):
			
						$last_message = count($data['all_messages']);
			
						foreach($data['all_messages'] as $message):
							if($message['sender_id'] == $data['current_user']['user_id']){
								$mess_style = 'background-color:#f0f0f0;text-align:left;';
								$sender = $data['current_user']['user_login'];
							}else{
								$mess_style = 'background-color:#ffffff;text-align:right;';
								$sender = $data['post']['user2_name'];
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
				
				<!------------- Communication between users ------------->
				<div class="post-info-commun" title="<?php echo __('Communication info', 'ppmess'); ?>">
					<span class="span-mark"><?php echo esc_html($data['current_user']['user_login']); ?></span>
					<?php echo '(' . __('logged user', 'ppmess') . ')'; ?>
					&rArr; <!-- strelica -->
					<span class="span-mark"><?php echo esc_html($data['post']['user2_name']); ?></span>
					<?php echo '(' . __('author of the post', 'ppmess') . ')'; ?>			
				</div>
				
				<!-------------------- Send message error --------------------->
				<!--<div class="infoBox-newMess"></div>-->
				<!------------------------------------------------------------->
				
				<!------------------------  Form za slanje komentara ------------------------>
				<form id="ppmessNewMessForm" method="POST" action="">
					<label for="ppmessMessageContent" style="font-size:.83em;"><?php /* echo __('Message', 'ppmess'); */ ?></label><br/>
					<textarea name="ppmess_message_content" type="text" id="ppmessMessageContent" rows="10" autofocus placeholder="<?php echo __('Leave the message', 'ppmess'); ?>"></textarea>
				</form>			
				<div class="single-commun-submit">
					<?php
						$link = admin_url('admin-ajax.php?action=ppmess_send_message&post_id=' . $data['post']['post_id'] . '&user2_id=' . $data['post']['user2_id'] . '&current_user=' . $data['current_user']['user_id'] .
							'&message_content_id=ppmessMessageContent'
						);
						echo '<a class="ppmess-tag-a ppmess-send-message ppmess-ajax" data-post_id="' . $data['post']['post_id'] . '" 
						data-user2_id="' . $data['post']['user2_id'] . '" data-current_user="'. $data['current_user']['user_id'] . 
						'" data-message_content_ID="ppmessMessageContent" href="' . $link . '">Send message</a>';
					?>
				</div>
		<?php 	
			else: // Logged user and the author of post	is same person ?>				
				<div class="post-info-commun" title="<?php echo __('Communication info', 'ppmess'); ?>">
					<span class="span-mark highlight-name"><?php echo esc_html($data['current_user']['user_login']); ?></span>
					<?php echo '(' . __('logged user', 'ppmess') . ')'; ?>
					&hArr; <!-- arrow -->
					<span class="span-mark highlight-name"><?php echo esc_html($data['post']['user2_name']); ?></span>
					<?php echo '(' . __('author of the post', 'ppmess') . ')'; ?>			
				</div>
				
				<div class="ppmess-info-dark">
					<?php
						if( ! empty($data['url_post_private_messages']) ): 
							echo __('The logged user is the same as the author of the post, see the next page: ', 'ppmess');	?>
							<a href="<?php echo $data['url_post_private_messages']; ?>"><?php echo __('Private messages', 'ppmess'); ?></a>
					<?php
						else:
							echo __('The logged user is the same as the author of the post', 'ppmess');
						endif;	?>
				</div>
		<?php
			endif;	?>
		</div>
	<?php
}	


