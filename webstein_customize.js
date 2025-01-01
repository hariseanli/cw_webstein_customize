(function($) {
	$(document).ready(function() {
		$('#ywsbs_subscription_disable_renewal').on('click', function(e) {
			e.preventDefault();
			var vthis = $(this);
			var intend_status = vthis.attr("intend_status");
			var subscription_id =vthis.attr("sub_id");
			vthis.addClass("loading");
			var data = {
				action: 'change_auto_renewal',
				subscription_id: subscription_id,
				intend_status: intend_status,
				nonce: ws_ajax_object.nonce,
			};
			console.log(data);
			$.ajax({
				type: 'POST',
				url: ws_ajax_object.ajax_url,
				data: data,
				success: function(response) {
					if(response.success) {
						console.log(response);
						var response_text = response.data.new_text;
						var response_status = response.data.new_status;
						vthis.text(response_text);
						vthis.attr("intend_status", response_status);
// console.log('comment_status');
// console.log(comment_status);
					} else {
						console.log(response)
					}
					vthis.removeClass("loading");
				},
				error: function(responsse) {
					vthis.removeClass("loading");
					console.log(responsse);
				}
			});
		});
	});
})(jQuery);