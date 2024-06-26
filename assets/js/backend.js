var rsrcFindPosts;

(function ($) {

	rsrcFindPosts = {
		open: function (af_name, af_val) {
			var overlay = $('.rsrc-ui-find-overlay');

			if (overlay.length === 0) {
				$('body').append('<div class="rsrc-ui-find-overlay"></div>');
				rsrcFindPosts.overlay();
			}

			overlay.show();

			if (af_name && af_val) {
				$('#affected').attr('name', af_name).val(af_val);
			}

			$('#rsrc-find-posts').show();

			$('#rsrc-find-posts-input').trigger('focus').on('keyup', function (e) {
				if (e.which == 27) {
					rsrcFindPosts.close();
				} // close on Escape
			});

			// Pull some results up by default
			rsrcFindPosts.send();

			return false;
		},

		close: function () {
			$('#rsrc-find-posts-response').empty();
			$('#rsrc-find-posts').hide();
			$('.rsrc-ui-find-overlay').hide();
			// $( '#rsrc-find-posts' ).draggable( 'destroy' ).hide();
		},

		overlay: function () {
			$('.rsrc-ui-find-overlay').on('click', function () {
				rsrcFindPosts.close();
			});
		},

		send: function () {
			var $pt = '';
			$('input[name="rsrc-find-posts-what[]"]:checked').each(function () {
				$pt += $(this).val() + ',';
			});
			var post = {
				ps: $('#rsrc-find-posts-input').val(),
				action: 'rsrc_find_posts',
				_ajax_nonce: $('#_rsrc_ajax_nonce').val(),
				post_type: $pt
			},
				spinner = $('.rsrc-find-box-search .spinner');
			spinner.show();
			$.ajax(ajaxurl, {
				type: 'POST',
				cache: false,
				data: post,
				dataType: 'json'
			}).always(function () {
				spinner.hide();
			}).done(function (x) {
				if (!x.success) {
					$('#rsrc-find-posts-response').text(x.responseText);
				}
				$('#rsrc-find-posts-response').html(x.data.html);
			}).fail(function (x) {
				$('#rsrc-find-posts-response').text(x.responseText);
			});
		}
	};

	$(function () {

		function rsrc_open_find_posts_dialog(e) {
			e.preventDefault();
			rsrcFindPosts.open('from_post', rsrc_js.ID);
		}

		$('#rsrc-find-posts-submit').on('click', function (e) {
			if ('' == $('#rsrc-find-posts-response').html()) {
				e.preventDefault();
			}
		});
		$('#rsrc-find-posts .rsrc-find-box-search :input').on('keypress', function (e) {
			if (13 == e.which) {
				rsrcFindPosts.send();
				return false;
			}
		});
		$('#rsrc-find-posts-search').on('click', rsrcFindPosts.send);
		$('#rsrc-find-posts-close').on('click', rsrcFindPosts.close);

		$('#rsrc_open_find_posts_button').on('click', rsrc_open_find_posts_dialog);

		$('#rsrc_delete_related_posts').on('click', function () {
			$('.related-posts').animate({ opacity: 0 }, 500, function () {
				$(this).html('');
				$('#rsrc_post_ids').val('');
				$(this).css('opacity', '1');
			}
			);
		});

		$('body:first').prepend($('.rsrc-find-box-search input#_rsrc_ajax_nonce'));

		$(".related-posts").sortable({
			'update': function (e, ui) {
				var ids = [];
				$('.related-posts li').each(function (i, item) {
					ids.push($(item).attr('data-id'));
				});
				$('#rsrc_post_ids').val(ids.join(','));
			},
			'revert': true,
			'placeholder': 'sortable-placeholder',
			'tolerance': 'pointer',
			'axis': 'y',
			'containment': 'parent',
			'cursor': 'move',
			'forcePlaceholderSize': true,
			'dropOnEmpty': false,
		});

		$('#rsrc-find-posts-submit').on('click', function (e) {
			e.preventDefault();
			if ($('input[name="rsrc_found_post_id[]"]:checked').length == 0) {
				return false;
			}
			$('input[name="rsrc_found_post_id[]"]:checked').each(function (id) {
				var selectedID = $(this).val();
				var posts_ids = new Array();
				posts_ids = $('#rsrc_post_ids').val() != '' ? $('#rsrc_post_ids').val().split(',') : [];
				if ($.inArray(selectedID, posts_ids) == '-1' && selectedID != rsrc_js.ID) {
					posts_ids.push(selectedID);
					$('#rsrc_post_ids').val(posts_ids);
					$(this).parent().parent().css('background', '#ff0000').fadeOut(500, function () { $(this).remove() });
					var label = $(this).parent().next().text();
					label = label.replace(/</g, '&lt;').replace(/>/g, '&gt;');
					var elem_li = '<li data-id="' + selectedID + '"><span><a class="delete_related_post"><span class="dashicons dashicons-dismiss"></span></a>&nbsp;&nbsp;' + label + '</span></li>';
					$('.related-posts').append(elem_li);
				}
			});
			return false;
		});

		setInterval(function () {
			if ($('#rsrc-find-posts-response input:checkbox').length > 0) {
				var $forbidden_ids = $('#rsrc_post_ids').val().split(',');
				$('#rsrc-find-posts-response input[value="' + rsrc_js.ID + '"]')
					.prop('disabled', true);
				$('#rsrc-find-posts-response input').filter(function (i) {
					return $.inArray($(this).val(), $forbidden_ids) > -1;
				})
					.prop('disabled', true).prop('checked', true);
			}
		}, 100);

		// Delete related posts. The click event must be attached to a static parent
		// node in order to work with dynamically added related post entries
		$('.related-posts').on('click', '.delete_related_post', function () {
			var id = $(this).closest('li').attr('data-id');
			$(this).closest('li').fadeOut(500, function () { $(this).remove() });
			var posts_ids = ',' + $('#rsrc_post_ids').val() + ',';
			posts_ids = posts_ids.replace(',' + id + ',', ',');
			$('#rsrc_post_ids').val(posts_ids.length > 1 ? posts_ids.substring(1, posts_ids.length - 1) : '');
		});

	});

})(jQuery);            
