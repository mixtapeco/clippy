//This gets loaded in the client side, to send the data back to the server, and update the browser with the server response vis a ve ajax
(function ($) {
	$(document).ready(function () {
		//Trigger by click on the id=addbtn button
		var clippyTimer = null;
        var clippyTimeoutDuration = 2000;

		$('.clippy-field').keyup(function () {

            var content = $(this).val();
            var container = $(this).closest('.clippy-container');
            console.log(container.find('.original_id').val());
            if (clippyTimer) {
                clearTimeout(clippyTimer);
            }
            container.find('.clippy-feedback').html('...');

            clippyTimer = setTimeout(function() {
                container.find('.clippy-feedback').html('Saving');
                var flag=container.find('.original_id').val();

    			$.post(
    					PT_Ajax.ajaxurl,  //wp ajax URL endpoint
    					{
    						// trigger custom wordpress action/function
    				    	action: 'ajax-clippySubmit',
                            post_id: container.find('.post_id').val(),
                            original_id: container.find('.original_id').val(),
                            post_title: container.find('.post_title').val(),
                            key_name: container.find('.key_name').val(),
    						//  this is where the clippy data field from the form
    						clippy: content,

    						// send the nonce along with the request
    						nonce: PT_Ajax.nonce
    					},
    					function (response) {

                            console.log(response);
    						//Clear the form field for next post
    						// $('#clippy').val("");

    						 if( response.status === "success" ) {
    							if ( flag == 0 ) {
        							//Success update DOM with new clippy from form into clippyresponse div
        							//@TODO you can add class name to div below to add stylin
        							container.find('.original_id').val(response.original_id);
        							// container.find('.clippy-display').html( response.content );
                                    container.find('.clippy-feedback').html('Saved');
                                    //	console.log(response);
    							} else {
                                    // $("#"+response.original_id).html(response.content);
                                    // container.find('.clippy-display').html( response.content );
                                    container.find('.clippy-feedback').html('Saved');
    						    }
    					    }
                        }
    			);
                return false;
            }, clippyTimeoutDuration);

		});


			$('#addbtn').click(function () {

                $('#original_id').val(0);

                 $('#clippy').val('');


		});

	});
	})(jQuery);
