define([
	'config',
	'jquery',
	'plugins/jquery-ui/jquery-ui.min',
	'plugins/abstract/jquery.ui.touch-punch.min',
	'classes/Utils'
], function(app, $, ui, tp, Utils) {
	$(function() {
		
		var clear = function(widget_id) {
			if ( $.trim(widget_id).length === 0) {
				return false;
			}
			
			var $widget = $(widget_id);
			
			//clear form
			Utils.removeAllFieldErrors($widget);
			$('.name-value-field-index', $widget).val('');
			$('.name-value-field-label', $widget).val('');
			$('.name-value-field-value', $widget).val('');
			$('.name-value-pairs-save', $widget).text('Add');
			$('.name-value-pairs-cancel-cnt', $widget).hide(300);
			
			$ul = $('.name-value-pairs-list', $widget);
			if ($ul) {
				$('li', $ul).each(function() {
					$(this).remove();
				});
			}
			if ( $ul.hasClass('ui-sortable') ) {
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
			
			var $ul = $link.closest('ul.name-value-pairs-list');
			$link.off();
			$link.parent('li').fadeOut(500, function() {
				$(this).remove();
				
				$('li', $ul).each(function(i) {
					$(this).attr('data-index', i);
				});
				
				$ul.listview().listview('refresh');
				if ( $ul.hasClass('ui-sortable') ) {
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
            var $ul = $('.name-value-pairs-list', $widget);
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
                var $pair = values[i];
                var title = '';
                for (var lbl in $pair) {
                    var _lbl = Utils.unescapeSingleQuotes(lbl);
                    title = '[' + _lbl + '] : [' + Utils.unescapeSingleQuotes($pair[lbl]) + ']';
                    break;
                }
                var $li = $('<li/>').attr('data-index', i);
                var $a = $('<a/>').attr('href', '#' + widget_id).addClass('name-value-pairs-edit');
                $('<h3/>').text(title).appendTo($a);
                $a.appendTo($li);

                if ( ! isReadOnly) {
                    $('<a/>')
                        .attr('href', '#' + widget_id)
                        .addClass('name-value-pairs-delete')
                        .text('Delete')
                        .appendTo($li);
                }
                $li.appendTo($ul);
            }

            $ul.listview().listview('refresh');
            if (isReadOnly) {
                $('.name-value-pairs-edit, .name-value-pairs-delete', $widget).addClass('ui-disabled');
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

            var $btn = $('.name-value-pairs-btn a[href="#' + widget_id + '"]');
            if (isReadOnly && values.length === 0) {
                $btn.addClass('ui-disabled');
            } else if ( $widget.data('visible') ) {
                $btn.removeClass('ui-icon-carat-d closed').addClass('ui-icon-carat-u ui-btn-active');
                $widget.show();
            }

            $('input[type="text"]', $widget).on('keypress', function(e) {
                if (e.which == 13) {
                    e.preventDefault();
					e.stopPropagation();
                    var id = '#' + $(this).closest('.name-value-pairs').attr('id');
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
			$('.name-value-field-index', $widget).val('');
			$('.name-value-field-label', $widget).val('');
			$('.name-value-field-value', $widget).val('');
			$('.name-value-pairs-save', $widget).text('Add');
			$('.name-value-pairs-cancel-cnt', $widget).slideUp(300);
			
			if ( isNaN(maxItems) ) {
				maxItems = 0;
			}
			
			if (maxItems !== 0 && maxItems <= values.length) {
				disableForm(widget_id);
			}
			
			//re-enable sorting if item edited
			$ul = $('.name-value-pairs-list', $widget);
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
			
			var $pair = values[index];
			var label = '';
			var value = '';
			for (var lbl in $pair) {
				label = Utils.unescapeSingleQuotes(lbl);
				value = Utils.unescapeSingleQuotes($pair[lbl]);
				break;
			}
			
			Utils.removeAllFieldErrors($widget); //in case leftover from previous edit
			$('.name-value-field-index', $widget).val(index);
			$('.name-value-field-label', $widget).val(label);
			$('.name-value-field-value', $widget).val(value);
			$('.name-value-pairs-save', $widget).text('Update');
			$('.name-value-pairs-cancel-cnt', $widget).slideDown(300);
			enableForm(widget_id);
			
			//disable sorting for edit form
			$ul = $('.name-value-pairs-list', $widget);
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

			var $label = $('.name-value-field-label', $widget);
			var $value = $('.name-value-field-value', $widget);
			var index = $('.name-value-field-index', $widget).val();
			if (index.length) {
				index = parseInt(index);
			}

			//validate fields
			var label = $label.val();
			var value = $value.val();
			var val_required = $widget.data('required');
			var error = ['Required field'];
			var has_error = false;
			
			Utils.removeAllFieldErrors($widget);
			if ( $.trim(label).length === 0) {
				Utils.showFieldError($label, error);
				has_error = true;
			}
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
			var val = {};
			val[label] = value;
		
			if ( $.isArray(values) ) {
				if ( isNaN(index) === false && values[index] !== undefined ) {
					values[index] = val;
				} else {
					values.push(val);
				}
			} else {
				values = [val];
			}

			$field.val( JSON.stringify(values) ).trigger('change');
			updateList(widget_id, val, index);
			resetForm(widget_id);
		};
		
		var updateList = function(widget_id, val, index) {
			if ( ! widget_id || ! val ) {
				return false;
			}
			
			var $widget = $(widget_id);
            var isReadOnly = parseInt( $widget.data('readonly') ) === 1;
			var $ul = $('.name-value-pairs-list', $widget);
			
			if ( ! $widget || ! $ul) {
				return false;
			}

			index = parseInt(index);
			var title = '';
			for (var lbl in val) {
				title = '[' + lbl + '] : [' + val[lbl] + ']';
				break;
			}

			if ( isNaN(index) ) {
				var count = 0;
				$ul.children('li').each(function() { count++; });
				var $li = $('<li/>').attr('data-index', count);
				var $a = $('<a/>').attr('href', widget_id).addClass('name-value-pairs-edit');
				$('<h3/>').text(title).appendTo($a);
				$a.appendTo($li);

                if ( ! isReadOnly) {
                    $('<a/>')
                        .attr('href', widget_id)
                        .addClass('name-value-pairs-delete')
                        .text('Delete')
                    .appendTo($li);
                }
				$ul.append($li);
			} else {
				$ul.children('li[data-index="' + index + '"]').find('h3').text(title);
			}
			
			$ul.listview().listview('refresh');
			if ( ! isReadOnly && $ul.hasClass('ui-sortable') ) {
				$ul.sortable('refresh');
			}
		};
		
		$('.name-value-pairs').each(function() {
			init( $(this) );
		});
			
		$('.name-value-pairs-list').on('click', '.name-value-pairs-edit', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var id = $(this).attr('href');
			var index = $(this).parent('li').data('index');
			setForm(id, index);
		});
			
		$('.name-value-pairs-list').on('click', '.name-value-pairs-delete', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$link = $(this);
			var message = 'Delete ' + $link.prev().find('h3').text() + '?';
			Utils.showModalConfirm('Confirm', message, function() {
				deleteVal($link);
			});
		});
			
		$('.name-value-pairs-save').click(function(e) {
			e.preventDefault();
			var id = '#' + $(this).closest('.name-value-pairs').attr('id');
			submitForm(id);
		});
		
		$('.name-value-pairs-cancel').click(function(e) {
			e.preventDefault();
			var id = '#' + $(this).closest('.name-value-pairs').attr('id');
			resetForm(id);
		});
		
		$('.name-value-pairs-list-open').click(function(e) {
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
		
		$('body').on('widget:reset', '.name-value-pairs-hidden', function(e) {
			e.stopPropagation();
			var $widget = $(this).closest('.name-value-pairs');
			var id = '#' + $widget.attr('id');
			clear(id);
			init($widget);
		});
		
		$(window).on('page:unload', function() {
			$('.name-value-pairs').each(function() {
				$('input[type="text"]', $(this) ).off('keypress');
			});
			$list = $('.name-value-pairs-list');
			$list.off('click', '.name-value-pairs-edit');
			$list.off('click', '.name-value-pairs-delete');
			if ( $list.hasClass('ui-sortable') ) {
				$list.sortable('destroy');
			}
			$('.name-value-pairs-save').off('click');
			$('.name-value-pairs-cancel').off('click');
			$('.name-value-pairs-list-open').off('click');
			$('body').off('widget:reset');
			$(this).off('page:unload');
		});
	});
});