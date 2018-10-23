<?php 
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	
	// check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
		
	$result = pmess_admin_submit();
?>

<div class="wrap">
	<h1><?php echo __('Private Messages Settings', 'pmess'); ?></h1>
	<form method="post" action="">
		<input type="hidden" name="pmess_settings" value="1">
		<input type="hidden" name="pmess_action" value="update">
		<?php wp_nonce_field( 'delete-comment_'); ?>
		
		<h2><?php echo __('Select post types', 'pmess'); ?></h2>
		<p><?php echo __('Select post types where private messages will be displayed', 'ppmess'); ?></p>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php echo __('Post types', 'pmess'); ?></th>						
					<td>
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo __('Formatting', 'pmess'); ?></span></legend>
						<?php	
							foreach($result['post_types'] as $res):	
							?>
								<label for="pmess_<?php echo $res['post_type']; ?>">
								<input type="checkbox" name="pmess_<?php echo $res['post_type']; ?>" id="pmess_<?php echo $res['post_type']; ?>" value="<?php echo $res['post_type']; ?>" <?php echo $res['checked'] ? 'checked' : ''; ?>>
								<?php echo ucfirst($res['post_type']); ?></label></br>							
						<?php 
							endforeach;	?>
					</fieldset>
					</td>
				</tr>
			</tbody>
		</table>
		
		<h2><?php echo __('Enable or disable private messages', 'pmess'); ?></h2>
		<p><?php echo __('Displaying private message on the front-end (bottom of the post) or as [shortcode]', 'ppmess'); ?></p>
		<?php foreach($result['enable_flag'] as $key => $val):	?>
			<label for="pmess_<?php echo $key; ?>">
			<input type="checkbox" name="pmess_<?php echo $key; ?>" id="pmess_<?php echo $key; ?>" value="<?php echo $key; ?>" <?php echo $val ? 'checked' : ''; ?>>
			<?php echo ucfirst($key); ?></label></br>
		<?php endforeach; ?>
		<p class="submit">
			<input type="submit" name="submit" class="button button-primary" value="<?php echo __('Save changes', 'pmess'); ?>">		
		</p>
	</form>
</div>
