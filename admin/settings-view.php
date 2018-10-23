<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
	// check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
		
	$result = ppmess_admin_submit();
?>

<div class="wrap">
	<h1><?php echo __('Post Private Messages Settings', 'ppmess'); ?></h1>
	<form method="post" action="">
		<input type="hidden" name="pmess_settings" value="1">
		<input type="hidden" name="pmess_action" value="update">
		
		<!------------ Select post type ------------>
		<h2><?php echo __('Select post types', 'ppmess'); ?></h2>
		<p><?php echo __('Select post types where private messages will be displayed', 'ppmess'); ?></p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php echo __('Post types', 'ppmess'); ?></th>				
					<td>
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo __('Formatting', 'ppmess'); ?></span></legend>
						<?php	
							foreach($result['post_types'] as $res):		?>
								<label for="pmess_<?php echo esc_attr($res['post_type']); ?>">
								<input type="checkbox" name="pmess_<?php echo esc_attr($res['post_type']); ?>" id="pmess_<?php echo esc_attr($res['post_type']); ?>" value="<?php echo esc_attr($res['post_type']); ?>" <?php echo esc_attr($res['checked']) ? 'checked' : ''; ?>>
								<?php echo ucfirst(esc_attr($res['post_type'])); ?></label><br/>
						<?php 
							endforeach;	?>
					</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		
		<!----------- Enable or disable post private messages ----------->
		<h2><?php echo __('Enable or disable post private messages', 'ppmess'); ?></h2>
		<p><?php echo __('Displaying post private messages  on the front side (bottom of the post)', 'ppmess'); ?></p>
		<?php foreach($result['enable_flag'] as $key => $val):	?>
			<label for="pmess_<?php echo esc_attr($key); ?>">
			<input type="checkbox" name="pmess_<?php echo esc_attr($key); ?>" id="pmess_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($key); ?>" <?php echo esc_attr($val) ? 'checked' : ''; ?>>
			<?php echo ucfirst(esc_attr($key)); ?></label><br/>
		<?php endforeach; ?>
		
		<br/>
		
		<!--------- Page's url for displaying all post private messages --------->
		<h2><?php echo __('Page\'s URL for displaying all post private messages', 'ppmess'); ?></h2>
		<p><?php echo __('Before select page or insert url of page, the <strong>page</strong> must be created and shortcode <strong>[ppmess-front-end]</strong> need to be inserted to content of that page', 'ppmess'); ?></p>
		<p><?php echo __('Select page-template', 'ppmess'); ?></p>
		<select name="ppmess_selected_page">
			<option value="no_selected"><?php echo __('No selected', 'ppmess'); ?></option>
		<?php foreach($result['ppmess_page_list'] as $page_single):	?>
				<option value="<?php echo esc_attr($page_single['page_name']); ?>" <?php echo $page_single['page_selected'] ? 'selected' : ''; ?> ><?php echo esc_attr($page_single['page_title']); ?></option>
		<?php endforeach;	?>
		</select>
		<br/><br/>
		<?php echo __('- Or -', 'ppmess'); ?>
		<p><?php echo __('Insert URL of page that you created for post private messages', 'ppmess'); ?></p>
			<label for="ppmess_url_page">
				<input type="text" name="ppmess_url_page" id="ppmessUrlPage" value="<?php echo !empty($result['ppmess_url_page']) ? esc_attr($result['ppmess_url_page']) : ''; ?>">
			</label>
		
		<p class="submit">
			<input type="submit" name="submit" class="button button-primary" value="<?php echo __('Save changes', 'ppmess'); ?>">		
		</p>
	</form>
</div>
