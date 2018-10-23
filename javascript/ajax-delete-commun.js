
jQuery( document ).on( 'click', '.ppmess-delete-commun-confirm', function() {
	
	var postId = jQuery(this).data('post_id');
	var messageId = jQuery(this).data("message_id");
	var confirmDelete = jQuery(this).data("confirm_delete");
	var tokenDeleteCommun = jQuery(this).data("token_delete_commun");
	var goBackUrl = jQuery(this).data("go_back_url");
	
	jQuery.ajax({
		type : 'post',
		dataType : 'json',
		url : ppmess_delete_commun_obj.ajax_url,
		data : {
			action 					: 'ppmess_delete_commun',
			delete_commun_nonce		: ppmess_delete_commun_obj.delete_commun_nonce,
			confirm_delete			: confirmDelete,
			post_id 				: postId,
			message_id 				: messageId,
			token_delete_commun		: tokenDeleteCommun,
			go_back_url				: goBackUrl,	
		},
		success : function( response ){
			// deletion the communication is successfuly
			if(response.type == 'delete_commun_success'){
				
				var deleteCommunDiv = document.getElementById("ppmessDeleteCommun");
				var returnLink = response.go_back_url;
				
				deleteCommunDiv.innerHTML = '';
				delete_response(response.success_message 
					+ ' <a class="post-link-ppmess" href="' 
					+ returnLink + '"> Go back</a>', "ppmess-info-light", false); //infoBox-message-success, predlog: infoBox-deleted-message
			}
			
			// deletion teh communication is not successfuly
			if(response.type == 'delete_commun_fail'){
				delete_response(response.error_message, "infoBox-message-error", true); //ppmess-info-dark,   predlog: infoBox-deleted-message-fail
			}
		},
		error: function (e){
			alert("ERORR: DELETE AJAX");
		}
		
	});

	return false;
})

// param:	sadrzaj poruke, atribut "class"
 function delete_response(message_content, message_class, disappire){
	var message = document.createElement("div");
	var appendTo = document.getElementById("ppmessDeleteCommun");
					
	message.innerHTML = message_content;	
	message.setAttribute("class", message_class);
	
	appendTo.parentNode.insertBefore(message, appendTo);
	
	if(disappire == true){
		setTimeout(function alertFunc(){
			message.parentNode.removeChild(message);					
		}, 6000);
	}
	
}