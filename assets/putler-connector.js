;jQuery(function( $ ) {


    function show_message( status, msg ){

        $("#putler_message").removeClass().addClass('updated');
        $("#putler_message").show();
        if( status ){
            if( status == 'ERR' ) {
                $("#putler_message").removeClass().addClass('error');
            }
            $("#putler_message p").text( status + ':' + msg );
        } else {
            $("#putler_message p").text( msg );
        }

    }
    
    function progress(percent, $element) {
        
        var progressBarWidth = percent * $element.width() / 100;
        $element.find('div').css({ width: percent + '%' }).html(percent + "%&nbsp;");

    }
    
    function show_data_loaded_msg( msg ){
        
        var img_url = putler_params.image_url + 'green-tick.png';
        
        setTimeout( function() {
            
                $('#putler_connector_progress_label').removeClass('putler_connector_progressbar_label');
                $('#putler_connector_progressbar').removeClass('putler_connector_progressbar');
                
                
                $('#putler_connector_progress_label').html('<img src="' + img_url + '"alt="Complete" height="16" width="16" class="alignleft"><h3>'+ msg +'</h3><p>New orders will sync automatically.</p>');
            }, 300 );
    }

    $("#putler_connector_settings_form").on('submit', function( event ) {

        var form = $(this), inputs = form.find("input, select, button, textarea"), 
                data = form.serialize();
                
                var email_id = $('#putler_email_address', form).val(),
                    new_tokens = $('#add_api_token', form).val(),
                    api_tokens_selected = '',
                    api_tokens = new Array();

                $("#api_token_list").find(":checkbox").each(function() {
                    var name = $(this).attr("name");
                    
                    if (name != 'all') {
                        api_tokens.push(name);
                        if ($(this).prop('checked')==true){ 
                            if (api_tokens_selected != '') {
                                api_tokens_selected += ', ' + name;
                            } else {
                                api_tokens_selected = name;
                            }
                        }
                    }
                });

                api_tokens = api_tokens.join();

                // Code for handling newly added tokens
                if( new_tokens != '' ) {
                    api_tokens_selected = (api_tokens_selected != '') ? api_tokens_selected +', '+ new_tokens : new_tokens;
                    api_tokens = (api_tokens != '') ? api_tokens +', '+ new_tokens : new_tokens;
                }
                
                event.preventDefault();

                if( email_id == '' || (api_tokens.length == 0 && new_tokens == '') ) {
                    var msg = 'Email Address or API Token cannot be empty.';
                    show_message( 'ERR', msg );
                } else if( api_tokens_selected.length == 0 && new_tokens == '' ) {
                    var msg = 'No API tokens selected.';
                    show_message( 'ERR', msg );
                } else {

                    $('#putler_connector_progress_label').removeClass('putler_connector_progressbar_label');
                    $('#putler_connector_progressbar').addClass('putler_connector_progressbar');
                    $('#putler_connector_progress_label').text('Saving Settings...');

                    $("#putler_connector_progressbar").show();
                    inputs.prop("disabled", true);
                    
                    request = $.ajax({
                        url: ajaxurl + '?action=putler_connector_save',
                        type: "post",
                        data: {
                                'putler_email_address' : email_id,  
                                'putler_api_tokens' : api_tokens,
                                'putler_api_tokens_sync' : api_tokens_selected
                            }
                    });
                    
                    request.done(function ( response ){

                        response = JSON.parse(response);

                        if( response.status == "OK" ){
                            
                            var total_order_count = remaining_order_count = response.order_count;
                            var params = Array();
                            var per_orders_sent = 0;
                            var all_done = false; // Flag for handling all done message

                            // to hide div showing messages.
                            $("#putler_configure_message").hide();
                            $("#putler_message").hide();

                            var remaining_order_count = response.order_count;

                            if ( remaining_order_count > 0 ) {
                                $('#putler_connector_progress_label').text('Loading Past Orders...');
                                setTimeout(function() { send_data(remaining_order_count, params, api_tokens_selected); }, 2000);
                            } else {
                                show_data_loaded_msg( 'Settings Saved! No Past orders to send to Putler.' );
                            }

                            function send_data( remaining_order_count, params, api_tokens ) {
                                
                                if ( remaining_order_count == response.order_count ) {
                                    $('#putler_connector_progress_label').empty();
                                }
                                
                                $('#putler_connector_progress_label').addClass('putler_connector_progressbar_label');
                                
                                    send_batch_request = $.ajax({
                                        url: ajaxurl + '?action=putler_connector_send_batch',
                                        type: "post",
                                        async: 'false',
                                        data: {
                                            'params': params,
                                            'putler_api_tokens_sync' : api_tokens                           
                                        }
                                    });

                                send_batch_request.done(function ( send_batch_response, textStatus, jqXHR ){

                                    send_batch_response = JSON.parse( send_batch_response );
                                    
                                    if( send_batch_response.status == 'OK' ){

                                        params = send_batch_response.results;
                                        remaining_order_count = remaining_order_count - send_batch_response.sent_count;

                                        if ( total_order_count != remaining_order_count ) {
                                            per_orders_sent = Math.round(( total_order_count - remaining_order_count ) / total_order_count * 100);
                                            progress( per_orders_sent, $('#putler_connector_progressbar') );
                                        }

                                        if (remaining_order_count > 0) {
                                            send_data(remaining_order_count,JSON.stringify(params),api_tokens_selected);
                                        } else {
                                            all_done = true;
                                        }

                                    } else if ( send_batch_response.status == 'ALL_DONE' ) {
                                        all_done = true;
                                    } else {

                                        var status = send_batch_response.results.woocommerce.status;
                                        var msg = send_batch_response.results.woocommerce.message;
                                        setTimeout( function() { show_message( status, msg ); }, 1500 );
                                    }

                                    if ( all_done === true ) {

                                        per_orders_sent = 100;
                                        progress( per_orders_sent, $('#putler_connector_progressbar') );
                                        show_data_loaded_msg('Past orders were sent to Putler successfully.');    
                                        
                                    }

                                });
                            }


                        } else {

                            // Show error message if credential were not vaidated.
                            $("#putler_connector_progressbar").fadeOut(100);
                            var status = response.status;
                            var msg = response.message;
                            show_message( status, msg );
                        }


                    });
                
                    request.fail(function (jqXHR, textStatus, errorThrown){
                        console.error(
                            "The following error occured: "+
                            textStatus, errorThrown
                        );
                    });

                    request.always(function () {
                        inputs.prop("disabled", false);
                    });
                    
                }
        
                
    });

    //Code to show/hide delete icon
    $(document).on('mouseenter', '.api_token', function () {
        $(this).find("span[id^='delete_']").show();
    }).on('mouseleave', '.api_token', function () {
        $(this).find("span[id^='delete_']").hide();
    });

    //Code to handle the delete tokens functionality
    $(document).on('click', "span[id^='delete_']", function( event ) {

        var result = confirm("Are you sure, you wish to delete the token?");

        if (result === true) {
            $(this).parent().remove();

            var api_tokens = '';

            $("#api_token_list").find(":checkbox").each(function() {
                var name = $(this).attr("name");
                
                if (name != 'all') {
                    
                    if (api_tokens != '') {
                        api_tokens += ', ' + name;
                    } else {
                        api_tokens = name;
                    }
                }
            });



            request = $.ajax({
                            url: ajaxurl + '?action=putler_connector_delete',
                            type: "post",
                            data: {
                                    'putler_api_tokens' : api_tokens
                                }
                        });
        }
    });

    
    
    //code for select all functionality
    $("input[name='all']").on('click', function( event ) {
        if(this.checked) { 
            $("#api_token_list").find(":checkbox").each(function() {
                this.checked = true;  
            });
        }else{
            $("#api_token_list").find(":checkbox").each(function() {
                this.checked = false;
            });         
        }
    });


    //To show/hide add tokens
    $("#add_token_link").on('click', function( event ) {
        event.preventDefault();
        $("#add_token").toggle();
    });    

    //To handle add tokens
    $("#add_token_btn").on('click', function( event ) {

        var api_tokens = $('#add_api_token').val();
        
        if( api_tokens == '' ) {
            var msg = 'API Token cannot be empty.';
            show_message( 'ERR', msg );
        } else {

            api_tokens = api_tokens.split(",");
            api_tokens_html = '';

            for (var i in api_tokens) {
                api_tokens_html += '<div style="margin-top:7px;"> <input type="checkbox" name="'+api_tokens[i].trim()+'" value="'+api_tokens[i].trim()+'" checked> '+api_tokens[i].trim()+
                                        '<span id="delete_'+api_tokens[i].trim()+'" title="Delete" class="dashicons dashicons-trash" style="color:#FF5B5E !important;cursor:pointer;"></span>'+
                                    '</div>';
            }

            $('#api_token_list').append(api_tokens_html);
        }

    });

} );