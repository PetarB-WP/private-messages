<?php 

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * List of existent post types on admin page
 * version:		1.0
 * @return:		(array) existing post types registred in wordpress
*/
function ppmess_existent_post_types(){
	$args = array(
		'public' => TRUE
	);
	
	$all_posts = get_post_types($args);
	
	// post types that should be excluded
	$exclude = array('attachment');
	
	foreach($all_posts as $post_type){
		if(in_array($post_type, $exclude)){
			unset($all_posts[$post_type]);
		}		
	}
	// return array_map('ucfirst', $all_posts);
	return $all_posts;
}

/**
 *	Processing admin submit
 *	version:	1.0
*/
function ppmess_admin_submit(){	
	
	$data = array();
	
	//------------------------------------------------------------------
	// allowed post types
	//------------------------------------------------------------------
	$post_types = ppmess_existent_post_types();	
	$post_selected = array();
	foreach($post_types as $post_type){
		if(isset($_POST['pmess_'.$post_type]))
			$post_selected[] = $_POST['pmess_'.$post_type];
 	}
	$data['allowed_post_options'] = $post_selected;
	
	//------------------------------------------------------------------
	// enable private messages
	//------------------------------------------------------------------	
	$data['enable_options'] = '';
	if(isset($_POST['pmess_enable'])){
		
		if($_POST['pmess_enable'] == 'enable')
			$data['enable_options'] = TRUE;
		else
			$data['enable_options'] = FALSE;
	}		
	
	//-----------------------------------------------------------------------
	// set URL is link where we want to go after processing some operation
	//-----------------------------------------------------------------------
	//	1)	page selected from drop-down list
	$pages_list = get_pages();
	$data['selected_page_options'] = array();	
	if(isset($_POST['ppmess_selected_page'])){
		foreach($pages_list as $page_obj){
			if($_POST['ppmess_selected_page'] == $page_obj->post_name){
				$data['selected_page_options'] = array(
					'page_title' =>	$page_obj->post_title, 		// not need to insert to BD
					'page_name'	 =>	$page_obj->post_name, 		// insert page name
					'page_url' 	 => esc_url($page_obj->guid),	// inser URL to DB
					'page_id'	 => $page_obj->ID
				);
				break;
			}				
		}		
	}
	
	// 2)	url inserted manualy
	$data['url_page_options'] = '';
	if(isset($_POST['ppmess_url_page']) ){
		$data['url_page_options'] = esc_url($_POST['ppmess_url_page']);
	}
	
	//-------------------------------------------------------------
	//	if form submited save new values
	//-------------------------------------------------------------
	if(	!empty($data['enable_options'])
		|| !empty($data['allowed_post_options']) 
		|| !empty($data['url_page_options']) 
		|| !empty($data['selected_page_options']) ){
		
		if(!is_serialized( $data )){
			$data = maybe_serialize($data); 
		}
		update_option('ppmess_options', $data, false);
	}
	
	//-------------------------------------------------------------	
	// get ppmess_optioins from database for fill form
	//-------------------------------------------------------------
	$result = array();
	$pmess_option_serial = get_option('ppmess_options');
	
	if( ! empty($pmess_option_serial) ){
		
		$ppmess_option = maybe_unserialize($pmess_option_serial);
		
		$post_selected = !empty($ppmess_option['allowed_post_options']) ? $ppmess_option['allowed_post_options'] : array();
		$enable_selected = !empty($ppmess_option['enable_options']) ? $ppmess_option['enable_options'] : '';
		$url_page_selected = !empty($ppmess_option['url_page_options']) ? $ppmess_option['url_page_options'] : '';
		$page_selected = !empty($ppmess_option['selected_page_options']) ? $ppmess_option['selected_page_options'] : array();
	}
	
	/*--------------- post types ---------------*/
	foreach($post_types as $post_type){
		$checked = FALSE;
		if(in_array($post_type, $post_selected))
			$checked = TRUE;
		
		$result['post_types'][] = array('post_type' => $post_type, 'checked' => $checked);
	}
	
	/*--------------- enable flag ---------------*/
	if($enable_selected){
		$result['enable_flag'] = array('enable' => TRUE);
	}else{
		$result['enable_flag'] = array('enable' => FALSE); 
	}
	
	/*----------------- url page -----------------*/
	$result['ppmess_url_page'] = $url_page_selected;
	
	//	pages list
	foreach($pages_list as $page_single){
		$selected = FALSE;
		if( isset($page_selected['page_name']) && $page_selected['page_name'] == $page_single->post_name){
			$selected = TRUE;
		}				
			
		$result['ppmess_page_list'][] = array(
			'page_title'		=> $page_single->post_title,
			'page_name'			=> $page_single->post_name,
			'page_url'			=> $page_single->guid,
			'page_id'			=> $page_single->ID,
			'page_selected'		=> $selected
		);
	}
	
	return $result;
}

/**
 * Checking to see that private messages enabled or not
 * version:	1.0
 * @return:	boolean
*/
function pmess_pivate_message_on(){
	
	$pmess_option_serial = get_option('ppmess_options');
	
	if( ! empty($pmess_option_serial) ){
		
		$pmess_option = maybe_unserialize($pmess_option_serial);
		
		if( isset($pmess_option['enable_options']) )
			return $pmess_option['enable_options'];
	}
	return FALSE;
}

/**
 *	Checking to see that currently post type enabled or not
 *	version: 1.0
 *	@return: boolean
*/
function ppmess_allowed_post_types(){
	
	global $post;
	
	$pmess_option_serial = get_option('ppmess_options');
	
	if( ! empty($pmess_option_serial) ){
		
		$pmess_option = maybe_unserialize($pmess_option_serial);
		if( isset($pmess_option['allowed_post_options']) )
			return in_array($post->post_type, $pmess_option['allowed_post_options']);
	}
	return FALSE;
}
