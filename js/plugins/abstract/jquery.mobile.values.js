define([
	'config',
	'jquery',
	'classes/Utils',
    'classes/I18n',
    'plugins/jquery-ui/jquery-ui.min',
    'plugins/abstract/jquery.ui.touch-punch.min'
], function(app, $, Utils, I18n) {
	$(function() {
		
		var clear = function(widget_id) {
			if ( $.trim(widget_id).length === 0) {
				return false;
			}
			
			var $widget = $(widget_id);
			Utils.removeAllFieldErrors($widget);
			$('.widget-values-field-index', $widget).val('');
			$('.widget-values-field-value', $widget).val('');
			$('.widget-values-save', $widget).text( I18n.t('add') );
			$('.widget-values-cancel-cnt', $widget).hide(300);
			
			$ul = $('.widget-values-list', $widget);
			if ($ul) {
				$('li', $ul).each(function() {
					$(this).remove();
				});
			}
			if ( $ul.hasClass('ui-sortable')) {
				$ul.sortable('destroy');
			}

			$('input[type="text"]', $widget).off('keypress');
		};
		
		var deleteVal = function($link) {
			var widget_id = $link.attr('href');
			var $widget = $(widget_id);
            var isReadOnly = parseInt( $widget.data('readonly') ) === 1;
            if (isReadOnly) {
                return false;
            }

			var field_name = $widget.data('field');
			var index = parseInt( $link.parent('li').attr('data-index') );
			var values = $('input[name="' + field_name + '"]', $widget).val();
			values = values.length ? JSON.parse(values) : [];
			if ( isNaN(index) || ! $.isArray(values) || values[index] === undefined || ! field_name) {
				return false;
			}

			values.splice(index, 1);
			$('input[name="' + field_name + '"]', $widget).val( JSON.stringify(values) ).trigger('change');
			
			var $ul = $link.closest('ul.widget-values-list');
			$link.off();
			$link.parent('li').fadeOut(500, function() {
				$(this).remove();
				
				$('li', $ul).each(function(i) {
					$(this).attr('data-index', i);
				});
				
				$ul.listview().listview('refresh');
				if ( $ul.hasClass('ui-sortable')) {
					$ul.sortable('refresh');
				}
			});

			resetForm(widget_id);
			enableForm(widget_id);
		};
		
		var disableForm = function(widget_id) {
			if ( $.trim(widget_id).length === 0) {
				return false;
			}
			
			var $widget = $(widget_id);
			$('.form-control', $widget).attr('disabled', true);
			$('.btn', $widget).attr('disabled', true);
		};
		
		var enableForm = function(widget_id) {
			if ( $.trim(widget_id).length === 0) {
				return false;
			}
			
			var $widget = $(widget_id);
			$('.form-control', $widget).attr('disabled', false);
			$('.btn', $widget).attr('disabled', false);
		};

        var init = function(selector) {
            var $widget = $.type(selector) === 'string' ? $(selector) : selector;
            var widget_id =  $widget.attr('id');
            var field_name = $widget.data('field');
            var maxItems = parseInt( $widget.data('maxItems') );
            var isReadOnly = parseInt( $widget.data('readonly') ) === 1;
            var $field = $('input[name="' + field_name + '"]', $widget);
            var $ul = $('.widget-values-list', $widget);
            var values = $field.val();
            values =  ! values ? [] : JSON.parse(values);
            if ( ! $.isArray(values) || ! field_name || ! $ul) {
                return false;
            }

            if ( isNaN(maxItems) ) {
                maxItems = 0;
            }

            if (maxItems <= values.length) {
                disableForm(widget_id);
            }

            for (var i=0; i < values.length; i++) {
                if (maxItems !== 0 && i > maxItems - 1) {
                    continue;
                }
                var title = Utils.unescapeSingleQuotes(values[i]);
                var $li = $('<li/>').attr('data-index', i);
                var $a = $('<a/>').attr('href', '#' + widget_id).addClass('widget-values-edit');
                $('<h3/>').text(title).appendTo($a);
                $a.appendTo($li);

                if ( ! isReadOnly) {
                    $('<a/>')
                        .attr('href', '#' + widget_id)
                        .addClass('widget-values-delete')
                        .text( I18n.t('delete') )
                        .appendTo($li);
                }
                $li.appendTo($ul);
            }

            $ul.listview().listview('refresh');
            if (isReadOnly) {
                $('.widget-values-edit, .widget-values-delete', $widget).addClass('ui-disabled');
            } else if ( $widget.data('sort') ) {
                $ul.sortable({
                    axis: 'y',
                    stop: function(e, ui) {
                        var values = $field.val();
                        values =  ! values ? [] : JSON.parse(values);
                        var aux = [];
                        $('li', $ul).each(function(i) {
                            var index = parseInt( $(this).attr('data-index') );
                            aux.push(values[index]);
                            $(this).attr('data-index', i);
                        });
                        $field.val( JSON.stringify(aux) ).trigger('change');
                    }
                });
            }

            var $btn = $('.widget-values-btn a[href="#' + widget_id + '"]');
            if (isReadOnly && values.length === 0) {
                $btn.addClass('ui-disabled');
            } else if ( $widget.data('visible') ) {
                $btn.removeClass('ui-icon-carat-d closed').addClass('ui-icon-carat-u ui-btn-active');
                $widget.show();
            }

            $('.widget-values-field-value', $widget).on('keypress', function(e) {
                if (e.which == 13) {
                    e.preventDefault();
					e.stopPropagation();
                    var id = '#' + $(this).closest('.widget-values').attr('id');
                    submitForm(id);
                }
            });
        };
		
		var resetForm = function(widget_id) {
			if ( $.trim(widget_id).length === 0) {
				return false;
			}
			
			var $widget = $(widget_id);
            var isReadOnly = parseInt( $widget.data('readonly') ) === 1;
            if (isReadOnly) {
                return false;
            }
			var maxItems = parseInt( $widget.data('maxItems') );
			var field_name = $widget.data('field');
			var $field = $('input[name="' + field_name + '"]', $widget);
			var values = $field.val();
			values =  ! values ? [] : JSON.parse(values);
			
			Utils.removeAllFieldErrors($widget); //in case leftover from previous edit
			$('.widget-values-field-index', $widget).val('');
			$('.widget-values-field-value', $widget).val('');
			$('.widget-values-save', $widget).text( I18n.t('add') );
			$('.widget-values-cancel-cnt', $widget).slideUp(300);
			
			if ( isNaN(maxItems) ) {
				maxItems = 0;
			}
			
			if (maxItems !== 0 && maxItems <= values.length) {
				disableForm(widget_id);
			}
			
			//re-enable sorting if item edited
			$ul = $('.widget-values-list', $widget);
			if ( $ul.hasClass('ui-sortable') ) {
				$ul.sortable('enable');
			}
		};
		
		var setForm = function(widget_id, index) {
			if ( $.trim(widget_id).length === 0) {
				return false;
			}
			
			var $widget = $(widget_id);
            var isReadOnly = parseInt( $widget.data('readonly') ) === 1;
            if (isReadOnly) {
                return false;
            }

			var field_name = $widget.data('field');
			var values = $('input[name="' + field_name + '"]', $widget).val();
			values =  ! values ? [] : JSON.parse(values);
			if ( ! $.isArray(values) || values[index] === undefined) {
				return false;
			}
			
			var value = Utils.unescapeSingleQuotes(values[index]);
			Utils.removeAllFieldErrors($widget); //in case leftover from previous edit
			$('.widget-values-field-index', $widget).val(index);
			$('.widget-values-field-value', $widget).val(value);
			$('.widget-values-save', $widget).text( I18n.t('update') );
			$('.widget-values-cancel-cnt', $widget).slideDown(300);
			enableForm(widget_id);
			
			//disable sorting for edit form
			$ul = $('.widget-values-list', $widget);
			if ( $ul.hasClass('ui-sortable') ) {
				$ul.sortable('disable');
			}
		};
		
		var submitForm = function(widget_id) {
			if ( $.trim(widget_id).length === 0) {
				return false;
			}

			var $widget = $(widget_id);
            var isReadOnly = parseInt( $widget.data('readonly') ) === 1;
            if (isReadOnly) {
                return false;
            }
			var $value = $('.widget-values-field-value', $widget);
			var index = $('.widget-values-field-index', $widget).val();
			if (index.length) {
				index = parseInt(index);
			}

			//validate fields
			var value = $value.val();
			var val_required = $widget.data('required');
			var error = [ I18n.t('validate.required') ];
			var has_error = false;
			Utils.removeAllFieldErrors($widget);
			if ( val_required && $.trim(value).length === 0) {
				Utils.showFieldError($value, error);
				has_error = true;
			}
			
			if (has_error) {
				return false;
			}
			
			var field_name = $widget.data('field');
			var $field = $('input[name="' + field_name + '"]', $widget);
			var values = $field.val();
			values =  ! values ? [] : JSON.parse(values);

			if ( $.isArray(values) ) {
				if ( isNaN(index) === false && values[index] !== undefined ) {
					values[index] = value;
				} else {
					values.push(value);
				}
			} else {
				values = [value];
			}

			$field.val( JSON.stringify(values) ).trigger('change');
			updateList(widget_id, value, index);
			resetForm(widget_id);
		};
		
		var updateList = function(widget_id, value, index) {
			if ( ! widget_id || ! value ) {
				return false;
			}
			
			var $widget = $(widget_id);
            var isReadOnly = parseInt( $widget.data('readonly') ) === 1;
			var $ul = $('.widget-values-list', $widget);
			index = parseInt(index);
			if ( ! $widget || ! $ul) {
				return false;
			}

			if ( isNaN(index) ) {
				var count = 0;
				$ul.children('li').each(function() { count++; });
				var $li = $('<li/>').attr('data-index', count);
				var $a = $('<a/>').attr('href', widget_id).addClass('widget-values-edit');
				$('<h3/>').text(value).appendTo($a);
				$a.appendTo($li);

                if ( ! isReadOnly) {
                    $('<a/>')
                        .attr('href', widget_id)
                        .addClass('widget-values-delete')
                        .text( I18n.t('delete') )
                    .appendTo($li);
                }
				$ul.append($li);
			} else {
				$ul.children('li[data-index="' + index + '"]').find('h3').text(value);
			}
			
			$ul.listview().listview('refresh');
			if ( ! isReadOnly && $ul.hasClass('ui-sortable') ) {
				$ul.sortable('refresh');
			}
		};
		
		$('.widget-values').each(function() {
			init( $(this) );
		});
			
		$('.widget-values-list').on('click', '.widget-values-edit', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var id = $(this).attr('href');
			var index = $(this).parent('li').data('index');
			setForm(id, index);
		});
			
		$('.widget-values-list').on('click', '.widget-values-delete', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$link = $(this);
			var message = I18n.t('delete') + ' ' + $link.prev().find('h3').text() + '?';
			Utils.showModalConfirm( I18n.t('confirm'), message, function() {
				deleteVal($link);
			});
		});
			
		$('.widget-values-save').click(function(e) {
			e.preventDefault();
			var id = '#' + $(this).closest('.widget-values').attr('id');
			submitForm(id);
		});
		
		$('.widget-values-cancel').click(function(e) {
			e.preventDefault();
			var id = '#' + $(this).closest('.widget-values').attr('id');
			resetForm(id);
		});
		
		$('.widget-values-list-open').click(function(e) {
			$link = $(this);
			var id = $link.attr('href');
			var $widget = $(id);
			
			if ( parseInt( $widget.data('readonly') ) === 1 ) {
				return false;
			} else if ( $widget.is(':visible') ) {
				$link.removeClass('ui-icon-carat-u').addClass('ui-icon-carat-d closed');
				$widget.slideUp(300);
			} else {
				e.preventDefault();
				$link.removeClass('ui-icon-carat-d closed').addClass('ui-icon-carat-u ui-btn-active');
				$widget.slideDown(300);
			}
		});
		
		$('body').on('widget:reset', '.widget-values-hidden', function(e) {
			e.stopPropagation();
			var $widget = $(this).closest('.widget-values');
			var id = '#' + $widget.attr('id');
			clear(id);
			init($widget);
		});
		
		$(window).on('page:unload', function() {
            $('.widget-values').each(function() {
                $('.widget-values-field-value', $(this) ).off('keypress');
            });

			$list = $('.widget-values-list');
			$list.off('click', '.widget-values-edit');
			$list.off('click', '.widget-values-delete');
			if ( $list.hasClass('ui-sortable') ) {
				$list.sortable('destroy');
			}
			$('.widget-values-save').off('click');
			$('.widget-values-cancel').off('click');
			$('.widget-values-list-open').off('click');
			$('body').off('widget:reset');
			$(this).off('page:unload');
		});
	});
});