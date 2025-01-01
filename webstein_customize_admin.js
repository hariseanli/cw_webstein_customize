(function($) {
	$(document).ready(function() {
		$('#ws_prosess_queue_entry').on('click', function(e) {
			e.preventDefault();
			var vthis = $(this);
			vthis.addClass("loading")
			var data = {
				action: 'proses_generate_entry',
				nonce: ws_admin_ajax_object.nonce,
			};
			$.ajax({
				type: 'POST',
				url: ws_admin_ajax_object.ajax_url,
				data: data,
				success: function(response) {
					if(response.success) {
						console.log(response);
						var html_text_result = "<p>"+response.data.message+"</p>";
						$(html_text_result).insertAfter(vthis);
						setTimeout(function () {
							location.reload(); // Reload the current page
						}, 3000);
					} else {
						console.log(response)
						var html_text_result = "<p>"+response.data.message+"</p>";
						$(html_text_result).insertAfter(vthis);
					}
					vthis.removeClass("loading");
				},
				error: function(response) {
					vthis.removeClass("loading");
					console.log(response);
				}
			});
		});


		$("#ws_direct_orderlist_filter").click(function(e){
			e.preventDefault();
			var ws_giveaway = $('.ws_input_fields [name="ws_giveaway"]').val();
			var ws_start_date = $('.ws_input_fields [name="ws_start_date"]').val();
			var ws_end_date = $('.ws_input_fields [name="ws_end_date"]').val();
			var show_correct = $('.ws_input_fields [name="show_correct"]').val();
			
			const currentUrl = window.location.href;
			var new_url = currentUrl+"&ws_giveaway="+ws_giveaway+"&start_date="+ws_start_date+"&end_date="+ws_end_date+"&show_correct="+show_correct;
			window.location.href = new_url;
		});

		$(".ws_entries_popup_close").on("click",function(){
			$(".ws_entries_popup").removeClass("active");
		})
		$("#ws_entries_popup_generate_new_entry").on("click",function(){
			var vthis = $(this);
			var vthis_order_id = vthis.attr("order_id");
			vthis.addClass("loading");
			var data = {
				action: 'add_order_entries',
				nonce: ws_admin_ajax_object.nonce,
				order_id:vthis_order_id,
			};
			$.ajax({
				type: 'POST',
				url: ws_admin_ajax_object.ajax_url,
				data: data,
				success: function(response) {
					if(response.success) {
						console.log(response);
						var html_text_result = "<p class='message'>"+response.data.message+"</p>";
						$(html_text_result).insertAfter(vthis);
					} else {
						console.log(response);
						var html_text_result = "<p class='message'>"+response.data.message+"</p>";
						$(html_text_result).insertAfter(vthis);
						
					}
					vthis.removeClass("loading");
				},
				error: function(response) {
					vthis.removeClass("loading");
					console.log(response);
					var html_text_result = "<p class='message'>"+response.data.message+"</p>";
					$(html_text_result).insertAfter(vthis);
				}
			});
		});
		$("body").on("click", ".delete_entry", function(){
			var vthis = $(this);
			var entry_id = vthis.attr("entry_id");
			var order_id = vthis.attr("order_id");
			var giveaway_id = vthis.attr("giveaway_id");
			var entry_amount = vthis.attr("entry_amount");

			var ws_table_list_entry = $(".ws_table_list_entry");
			ws_table_list_entry.addClass("loading");
			var data = {
				action: 'delete_entry',
				nonce: ws_admin_ajax_object.nonce,
				entry_id:entry_id,
				order_id:order_id,
				giveaway_id:giveaway_id,
				entry_amount:entry_amount,
			};
			console.log(data);
			$.ajax({
				type: 'POST',
				url: ws_admin_ajax_object.ajax_url,
				data: data,
				success: function(response) {
					if(response.success) {
						console.log(response);
						var html_text_result = response.data.message;
						ws_table_list_entry.append(html_text_result);
					} else {
						console.log(response);
						ws_table_list_entry.append(html_text_result);
					}
					ws_table_list_entry.removeClass("loading");
				},
				error: function(response) {
					// vthis.removeClass("loading");
					console.log(response);
					ws_table_list_entry.append(html_text_result);
					ws_table_list_entry.removeClass("loading");
				}
			});

		});

		$(".process_order_entry").on("click",function(){
			$(".ws_entries_popup").addClass("active");
			var vthis_co = $(".ws_subs_table_co");
			var vthis = $(this);
			var vthis_order_id = vthis.attr("order_id");
			var vthis_email = vthis.attr("email");
			var vthis_giveaway_id = vthis.attr("giveaway_id");
			$("#ws_entries_popup_generate_new_entry").attr("order_id", vthis_order_id);
			
			var data = {
				action: 'generate_entry_list',
				nonce: ws_admin_ajax_object.nonce,
				order_id:vthis_order_id,
				email:vthis_email,
				giveaway_id:vthis_giveaway_id,
			};

			console.log(data);
			// var content_to_replace = $(".ws_entries_popup_content_in>div");
			var ws_entries_popup_content_in = $(".ws_entries_popup_content_in");
			var content_to_replace_message = $(".ws_entries_popup_content_in .message");
			var content_to_replace_table = $(".ws_entries_popup_content_in .ws_table_list_entry");
			var ws_entries_popup_content = $(".ws_entries_popup_content")

			if(ws_entries_popup_content.length>0){
				ws_entries_popup_content.addClass("loading");
			}
			if(content_to_replace_message.length>0){
				content_to_replace_message.remove();
			}
			if(content_to_replace_table.length>0){
				content_to_replace_table.remove();
			}
			
			$.ajax({
				type: 'POST',
				url: ws_admin_ajax_object.ajax_url,
				data: data,
				success: function(response) {
					if(response.success) {
						console.log(response);
						var html_text_result = response.data.message;
						ws_entries_popup_content_in.append(html_text_result);
						// setTimeout(function () {
						// 	location.reload(); // Reload the current page
						// }, 3000);
					} else {
						console.log(response);
						ws_entries_popup_content_in.append(html_text_result);
						// var html_text_result = "<p>"+response.data.message+"</p>";
						// $(html_text_result).insertAfter(vthis);
					}
					ws_entries_popup_content.removeClass("loading");
				},
				error: function(response) {
					// vthis.removeClass("loading");
					console.log(response);
					ws_entries_popup_content_in.append(html_text_result);
					ws_entries_popup_content.removeClass("loading");
				}
			});

		});
		
	});
})(jQuery);