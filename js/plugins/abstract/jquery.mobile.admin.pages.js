define([
	'config',
	'jquery',
	'underscore',
	'backbone',
	'classes/Utils',
    'classes/I18n'
], function(app, $, _, Backbone, Utils, I18n) {
	$(function() {
		var pagesLiItem = _.template( $('#pages-li-item').html() );
		var collection = app.AppView.contentView.collection;
		var currentPage = collection.state.currentPage;
		var loaderUrl = app.docRoot + 'css/images/ajax-loader-sm.gif';
		var $tree = $('#pages-tree');
		var max_depth = parseInt( $tree.attr('data-max-depth') );
		var get_subpages_url = $tree.attr('data-subpages-url');
		var $loading = $('<div/>').addClass('toggle-loading');
		$('<img/>').attr('src', loaderUrl).attr('alt', I18n.t('loading') + '...').attr('align', 'absmiddle').appendTo($loading);
		
		//stop collection render on model delete
		app.AppView.contentView.stopListening(collection, 'remove');
		
		var refreshList = function() {
			$tree = $('#pages-tree');
			if (collection.length > 0) {
				var $pager = $('#pages-search-pagination');
				var $result = $('#search-results');

				var has_query = false;
				for (var field in collection.queryParams.filters) {
					if (collection.queryParams.filters[field].length) {
						has_query = true;
						break;
					}
				}
				
				var pageNum = collection.state.currentPage;
				var totalPages = collection.state.totalPages;
				var pageSize = collection.state.pageSize;
				var totalRecords = collection.state.totalRecords;
				var from = ( (pageNum - 1) * pageSize) + 1;
				var to = pageNum === totalPages ? totalRecords : from + pageSize - 1;
				
				$tree.empty();
				$('div', $pager).not('#pagination-link-prev,#pagination-link-next').remove();

				var title = I18n.t('list.results.title', [from, to, totalRecords]);
				$result.text(title);
				$result.show();

				if (has_query && totalPages > 1) {
				//add/remove/update pagination (only if search query entered)
					var pageNum = collection.state.currentPage;
					var $prev = $('#pagination-link-prev');
					var $next = $('#pagination-link-next');
					var $curr = $prev;
						
					for (i=1; i <= totalPages; i++) {
						var $d = $('<div/>');
						$('<a/>')
							.text(i)
							.attr('id', 'pagination-link-' + i)
							.attr('rel', i)
							.addClass('pagination-link pagination-link-' + (i===pageNum ? 'active' : 'inactive'))
						.appendTo($d);
						$d.insertAfter($curr);
						$curr = $d;
					}
					
					$prev.show();
					$next.show();
					if (pageNum === 1) {
						$prev.hide();
					} else if (pageNum === totalPages) {
						$next.hide();
					}
					$pager.show();
				} else {
					$pager.hide();
				}
			
				//add pages list items
				var pages = [];
				for (var i=0; i < collection.length; i++) {
					var model = collection.at(i);
					pages.push(model.attributes);
				}
				
				var items = pagesLiItem({pages: pages, has_depth: (has_query === false), pagesLiItem: pagesLiItem});
				$tree.append(items);

				$('li div.toggle-page', $tree).each(function() {
					$(this).attr('data-depth', 1);
				});
	
				//app.AppView.contentView.trigger('view:update:end');
				app.AppView.loading('hide');
			}
		}
		
		var showErrors = function(errors) {
			if (errors.length) {
				error_msg = typeof errors === 'Array' ? errors.join('<br/>') : errors;
				Utils.showModalWarning( I18n.t('error'), error_msg);
			}
		};
		
		$('.module-list').on('click', '.pagination-link', function() {
			if ( $(this).hasClass('pagination-link-active') ) {
				return false;
			}
			var page = $(this).attr('rel');
			var pageNum = 0;
			
			if (page === 'next') {
				pageNum = currentPage + 1;
			} else if (page === 'prev') {
				pageNum = currentPage - 1;
			} else {
				pageNum = parseInt(page);
			}
			
			if (pageNum !== currentPage) {
				collection.state.currentPage = pageNum;
				var promise = app.AppView.contentView.render();
				promise.done(function() {
					refreshList();
					currentPage = pageNum;
				});
			}
		});

		
		$('.module-list').on('click', '.toggle-hitarea', function(e) {
			e.stopPropagation();
			var $obj = $(this);
			var $links = $obj.siblings('.toggle-links');
			if ( $(this).hasClass('no-subpages') ) {
				$links.toggleClass('toggle-links-hide toggle-links-show');
				$obj.toggleClass('toggle-inactive toggle-active');
				return false;
			}
			
			var $li = $obj.closest('li');
			var parent_id = $obj.attr('id').replace('toggle-', '');
			var depth = parseInt( $obj.parent().attr('data-depth') );

			if ( $links.hasClass('toggle-links-show') ) {
				$('ul', $li).remove();
			} else if ( $obj.hasClass('page-node') === false ) {
				$.ajax({
					url:		get_subpages_url,
					data: 		{parent_id: parent_id},
					type: 		'GET',
					dataType: 	'json'
				}).done(function(data) {
					if (data.pages) {
						var has_depth = max_depth - depth > 1;
						var items = pagesLiItem({pages: data.pages, has_depth: has_depth, pagesLiItem: pagesLiItem});
						var $ul = $('<ul/>');
						$ul.append(items).appendTo($li);
						$('.toggle-loading', $links).remove();
						
						$('li div.toggle-page', $ul).each(function() {
							$(this).attr('data-depth', depth + 1);
						});
					} else if (data.errors) {
						showErrors(data.errors);
					}
				}).fail(function(jqXHR) {
                    var resp = Utils.parseJqXHR(jqXHR);
                    var errors = resp.errors.length ? resp.errors : (resp.response.length ? [resp.response] : []);
                    if (errors.length === 0) {
                        errors = [ I18n.t('error.general.unknown', 'jquery.mobile.admin.pages') ];
                    }
					showErrors(errors);
				});
				
				var $loader = $loading.clone();
				$loader.find('img').css('width', 13).css('height', 13);
				$links.append($loader);
			}
			
			$links.toggleClass('toggle-links-hide toggle-links-show');
			$obj.toggleClass('toggle-inactive toggle-active');
		});
		
		$('.module-list').on('click', '.toggle-delete', function() {
			var id = $(this).attr('rel');
			var title = $(this).attr('title');
			
			Utils.showModalConfirm( I18n.t('confirm'), I18n.t('delete') + ' "' + title + '"?', function() {
				var $obj = $('#toggle-' + id);
				var $loader = $loading.clone();
				$loader.find('img').css('width', 13).css('height', 13);
				$obj.siblings('.toggle-links').append($loader);
				
				//Backbone delete
				for (var i=0; i < collection.length; i++) {
					var model = collection.at(i);
					var model_id = model.get(collection.idAttribute);
					
					if ( parseInt(model_id) === id ) {
						model.destroy({ 
							wait: true, 
							success: function(model, response) {
								$obj.closest('li').fadeOut(500, function() {
									$(this).remove();
								});
								Utils.showModalDialog( I18n.t('message'), I18n.t('confirm.deleted', title), false);
							}, 
							error: function(model, response) {
								Utils.showModalWarning( I18n.t('error'), I18n.t('error.delete', title), false);
							}
						});
					}
				}
			}, false, null);
		});
		
		$('.module-list').on('click', '.module-route', function(e) {
			e.preventDefault();
			var fragment = $(this).attr('href');
			app.Router.navigate(fragment, {trigger: true, replace: true});
		});
		
		$(window).on('page:unload', function() {
			$list = $('.module-list');
			$list.off('click', '.toggle-delete');
			$list.off('click', '.toggle-hitarea');
			$list.off('click', '.pagination-link');
			$(this).off('page:unload');
		});
		
		//initial population of pages list
		refreshList();
		app.AppView.listenTo(app.AppView.contentView, 'view:update:end', refreshList);
	});
});