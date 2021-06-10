//This gets loaded in the client side, to send the data back to the server, and update the browser with the server response vis a ve ajax
(function ($) {
	$(document).ready(function () {
		//Trigger by click on the id=addbtn button
		$('#addbtn').click(function () {
			$.post(
				PT_Ajax.ajaxurl,  //wp ajax URL endpoint
				{
					// trigger custom wordpress action/function
			    	action: 'ajax-clippySubmit',
                    post_id:$('#post_id').val(),
					//  this is where the clippy data field from the form
					clippy: $('#clippy').val(),

					// send the nonce along with the request
					nextNonce: PT_Ajax.nextNonce
				},
				function (response) {
					//Clear the form field for next post
					 $('#clippy').val("");
					 if(response.status==="success") {
						//Success update DOM with new clippy from form into clippyresponse div
						//@TODO you can add class name to div below to add styling
						$("#clippyresponse").append("<div class=''>"+response.content+"</div>");
					//	console.log(response);
					 } else {
						 //Error status. Send JS Alert
						alert(response.content);
				//console.log(response);
					 }
				}
			);
			return false;
		});

	});
})(jQuery);
