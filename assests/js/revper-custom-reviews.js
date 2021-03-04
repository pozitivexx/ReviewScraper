jQuery(function ($) {
	$('#revper_scrape_reviews').on('click', function () {
		jQuery('.revper-key').filter(function () {
			return jQuery(this).val().length
		}).each(function () {
			console.log('val', $(this).val());
			revper_save_url_reviews_ajax($(this).data('product'), $(this));
			//e.preventDefault();
		});
	});

	function revper_update_loading_screen_text(obj, text) {
		obj.next('div').html(text);
	}

	function revper_save_url_reviews_ajax(product, obj) {
		revper_update_loading_screen_text(obj, 'updating...');

		$.ajax({
			type: "post",
			url: ajaxurl,
			dataType: "json",
			data: {
				action: "revper_get_reviews",
				post_id: script_data.post_id,
				product: product,
			},
			success: function (response) {
				console.log('response', response);
				revper_update_loading_screen_text(obj, response.content);
			},
			error: function (e) {
				console.log(e);
				revper_update_loading_screen_text(obj, 'error while updating');
			}
		})
	}

});