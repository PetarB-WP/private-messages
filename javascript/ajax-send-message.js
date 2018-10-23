
jQuery( document ).on( 'click', '.ppmess-ajax', function() {
		
	var postId = jQuery(this).data('post_id');
	var messageId = jQuery(this).data("message_id");
	var user2Id = jQuery(this).data("user2_id");
	var currentUser = jQuery(this).data("current_user");
	
	var messageContentId = jQuery(this).data("message_content_id"); 
	var messageContent = document.getElementById(messageContentId);
	
	var newMessage = document.createElement("div");
	var ppmessMessagesFrame = document.getElementById("ppmessMessagesFrame");
	
	jQuery.ajax({
		type : 'post',
		dataType : 'json',
		url : ppmess_send_message_obj.ajax_url,
		data : {
			action 					: 'ppmess_send_message',
			send_message_nonce		: ppmess_send_message_obj.send_message_nonce,
			post_id 				: postId,
			message_content 		: messageContent.value,
			message_id 				: messageId,
			user2_id 				: user2Id,
			current_user			: currentUser,
		},
		success : function( response ){
			if(response.type == 'ppmess_success'){
				newMessage.setAttribute("id", "ppmessage-" + response.message_id);		
				newMessage.setAttribute("class", "single-message-frame");
				newMessage.setAttribute("style", response.message_style);
				
				newMessage.innerHTML = '<p class="p-row1-pmess">' 
										+ response.w_from
										+ '<span style="' + 'color:#0073aa;font-style:italic;' + '">' + ' ' + response.sender + '</span>'
										+ ' | '
										+ response.w_date
										+ '<span style="' + 'color:#0073aa;font-style:italic;' + '">' + ' ' + response.date_dMY + '</span>'
										+ ' | '
										+ response.w_time
										+ '<span style="' + 'color:#0073aa;font-style:italic;' + '">' + ' ' + response.date_Gi + '</span>'
									+ '</p>'
									+ '<p class="' + 'p-row2-pmess' + '">' + response.message_content + '</p>';
									
				ppmessMessagesFrame.appendChild(newMessage);
				messageContent.value = "";
				
				// ispis poruke, poruka je uspesno poslata 
				process_response(response.success_message, "infoBox-message-success");
			}
			
			if(response.type == 'ppmess_fail'){
				// ispis poruke, neuspesno slanje 
				process_response(response.error_message, "infoBox-message-error");				
			}
		},
		error: function (e){
			alert("ERORR AJAX");
		}
		
	});

	return false;
})

// param:	sadrzaj poruke, atribut "class"
 function process_response(message_content, message_class){
	var message = document.createElement("div");
	var ppmessNewMessForm = document.getElementById("ppmessNewMessForm");
					
	message.innerHTML = message_content;				
	message.setAttribute("class", message_class);
	
	ppmessNewMessForm.parentNode.insertBefore(message, ppmessNewMessForm);
	
	setTimeout(function alertFunc(){
		message.parentNode.removeChild(message);					
	}, 6000);	
}
