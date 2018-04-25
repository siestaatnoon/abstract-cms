define([
	'config',
	'jquery',
	'classes/FormValidator',
    'classes/Utils',
    'classes/I18n',
    'plugins/jquery-ui/jquery-ui.min',
    'plugins/abstract/jquery.ui.touch-punch.min'
], function(app, $, FormValidator, Utils, I18n) {
	$(function() {
		var validateVal = null;
		
		var panelWidth = $('.form-panel').first().css('width') || $(window).width();
		
		var adjustPanelWidth = function() {
			var maxWidth = panelWidth;
			var screenWidth = $(window).width();
			
			if (maxWidth.indexOf('em') !== -1) {
				var fontSize = parseFloat( $('html').css('font-size') );
				maxWidth = parseInt(maxWidth) * fontSize;
			} else {
				maxWidth = parseInt(maxWidth);
			}			
			var val = maxWidth > screenWidth ? screenWidth + 'px' : panelWidth;
			$('.form-panel').css('width', val);
		};
		
		var closePanel = function(panel_id) {
			if ( ! panel_id) {
				return false;
			}
			
			var $panel = $(panel_id);
			var $form = $('form', $panel);
			$('.panel-overlay').remove();
			$('.plupload', $panel).trigger('upload:clear');
			$form.off('change', ':input', validateVal);
			$panel.panel('close');
			
			//reset form header title
            var newText = I18n.t('new');
            var updateText = I18n.t('update');
            var regex = new RegExp('(' + newText + '|' + updateText + ')[ ]');
			var $header = $panel.find('[data-role="header"]').children('h1');
			var title = $header.text();
			if ( title.indexOf(updateText) === 0) {
				var pos = title.indexOf(':');
				if (pos >= 0) {
					title = title.substr(0, pos);
				}
			}
			title = title.replace(regex, '');
			$header.text(title);
            $form.trigger('subform:close');
		};
		
		var deleteSubformObject = function($link) {
			var $form = $( $link.attr('href') + ' form');
            var isReadOnly = parseInt( $form.data('readonly') ) === 1;
            if (isReadOnly) {
                return false;
            }

			var object_id = $form ? $form.data('objectId') : false;
			var $obj = object_id ? $(object_id) : false;
			var index = parseInt( $link.parent('li').attr('data-index') );
			if ( ! object_id || ! $obj || isNaN(index)) {
				return false;
			}

			$obj = JSON.parse( $obj.val() );
			if ( ! $.isArray($obj) || $obj[index] === undefined) {
				return false;
			}
			
			$obj.splice(index, 1);
			$(object_id).val( JSON.stringify($obj) );
			
			var $ul = $link.parents('ul.form-panel-list');
			$link.unbind();
			$link.parent('li').fadeOut(500, function() {
				$(this).remove();
				
				$('li', $ul).each(function(i) {
					$(this).attr('data-index', i);
				});
				
				$ul.listview().listview('refresh');
				if ( $ul.hasClass('ui-sortable') ) {
					$ul.sortable('refresh');
				}
                $form.trigger('subform:delete');
			});
		};
		
		var openPanel = function(panel_id) {
			if ( ! panel_id) {
				return false;
			}
			
			var $panel = $(panel_id);
			$('<div/>').addClass('panel-overlay').appendTo('#abstract-cms-page');
			$('.form-save', $panel).removeClass('activate');
			$panel.trigger('updatelayout');
			$panel.panel('open');
            $('form', $panel).trigger('subform:open');
		};
		
		var setPanelFormVals = function(panel_id, is_default, index) {
			if ( $.trim(panel_id).length === 0) {
				return false;
			}
			
			var $panel = $(panel_id);
			var $form = $('form', $panel);
			var $obj = null; 
			if (is_default) {
				$obj = $form.data('defaults') || null;
			} else {
				$obj = $( $form.data('objectId') ).val();
				$obj = $obj ? JSON.parse($obj) : null;
			}
			if ($obj === null || ( ! $.isArray($obj) && ! $.isPlainObject($obj)) ) {
				return false;
			}
			var pk_field = $form.data('pkField');
			var $inputs = $form.find(':input');
			var $values = {};
			if ( $.isArray($obj) ) {
				$values = $obj[index];
				$form.data('index', index);
			} else {
				$values = $obj;
			}
			
			Utils.removeAllFieldErrors($panel); //in case leftover from previous edit
			var upload_fields = $values.uploads ? $values.uploads : {};
			for (var name in $values) {
				if (name === 'uploads' || upload_fields[name]) {
				//file upload data, not saved
					continue;
				} else if (name === pk_field) {
				//save id which may or may not have same name attribute
					var $field = $inputs.filter('[name="' + pk_field + '"]');
					if ($field !== undefined) {
						$field.val([ $values[name] ]);
					}
					continue;
				}
				var is_multi = $.isArray($values[name]);
				var selector = '[name="' + name + (is_multi ? '[]' : '') + '"]';
				var $field = $inputs.filter(selector);
				if ($field.attr('name') === undefined) {
					if (is_multi) {
					//check if field name not array, but default is
						selector = '[name="' + name + '"]';
						$field = $inputs.filter(selector);
						if ($field.attr('name') === undefined) {
							continue;
						}
					} else {
						continue;
					}
				}
				
				if ( $field.hasClass('tinymce') ) {
				//set content editor field
					$field.html($values[name]);
				} else if ( $field.hasClass('name-value-pairs-hidden') ||
					$field.hasClass('widget-values-hidden') ||
					$field.hasClass('widget-hidden') ) {
					//NV Pairs or Values Widget or custom widget
					var val = $values[name] || [];
					$field.val( JSON.stringify(val) ).trigger('widget:reset');
				} else if (is_multi) {
				//filed array
					$field.val($values[name]);
				} else {
					$field.val([ $values[name] ]);
				}
				
				if ( $field.hasClass('timebox') ) {
				//reinitialize time field for 12/24 hour display
					$field.trigger('timebox:init');
				}
				Utils.refreshJqmField($field);
			}

			if ( ! is_default) {
				if ($values.uploads) {
				//initialize uploads
					var uploads = $values.uploads;
					$('.plupload', $panel).each(function() {
						var $upl = $(this);
						var name = $upl.data('field');
						if (name !== undefined) {
							name = name.replace('[]', '');
							if (uploads[name]) {
								var data = $.isArray(uploads[name]) ? uploads[name] : [uploads[name]];
								var filedata = [];
								for (var i=0; i < data.length; i++) {
									var fileinfo = data[i];
									var files = $.isArray( $values[name] ) ? $values[name] : [ $values[name]];
									for (var j=0; j < files.length; j++) {
										if (files[j] === fileinfo.filename) {
											filedata.push(fileinfo);
										}
									}
								}
								$upl.trigger('upload:clear');
								$upl.attr('data-files', JSON.stringify(filedata) );
								$upl.attr('data-pk', $values[pk_field]);
								$upl.trigger('upload:reset'); 
							}
						}
					});
				}
			}
			
			var title_field = $form.data('titleField');
			var $header = $panel.find('[data-role="header"]').children('h1');
			var title = $header.text();
			if (is_default) {
				title = I18n.t('new') + ' ' + title;
			} else {
				title = I18n.t('update') + ' ' + title + ($values[title_field] ? ': ' + $values[title_field] : '');
			}
			$header.text(title);
			$form.trigger('subform:init');
		};
		
		var submitPanelFormVals = function(panel_id) {
			if ( $.trim(panel_id).length === 0) {
				return false;
			}
			
			var $panel = $(panel_id);
			var $form = $('form', $panel);
            var isReadOnly = parseInt( $form.data('readonly') ) === 1;
            if (isReadOnly) {
                return false;
            }

			var $defaults = $form.data('defaults') || false;
			var object_id = $form.data('objectId');
			var pk_field = $form.data('pkField');
			var $obj = object_id ? $(object_id).val() : false;
			$obj = $obj === undefined ? false : ($obj === '' ? [] : JSON.parse($obj) );
			if ( ! $defaults || ! $.isPlainObject($defaults) || $obj === false) {
				return false;
			}

			var index = parseInt( $form.data('index') );
			var $inputs = $form.find(':input');
			var $values = $.extend(true, {}, $defaults);
			var $fields = {};
            var toValidate = {};
			if ( isNaN(index) ) {
				index = false;
			}
			
			//retrieve id if exists
			if (pk_field.length > 0 && $values[pk_field] === undefined) {
				var $field = $inputs.filter('[name="' + pk_field + '"]');
				if ($field !== undefined) {
					$values[pk_field] = Utils.getVal($field);
				}
			}
				
			for (var name in $values) {
				if (name === 'uploads' || name === pk_field) {
					continue;
				}
				var is_multi = $.isArray($values[name]);
				var selector = '[name="' + name + (is_multi ? '[]' : '') + '"]';
				var $field = $inputs.filter(selector);
				if ($field.attr('name') === undefined) {
					if (is_multi) {
					//check if field name not array, but default is
						selector = '[name="' + name + '"]';
						$field = $inputs.filter(selector);
						if ($field.attr('name') === undefined) {
							continue;
						}
					} else {
						continue;
					}
				}
				
				if ( $field.attr('type') === 'hidden' ||
                     $field.is(':hidden') === false ||
					 $field.hasClass('tinymce') ) {
					//fields with display:none; EXCEPT hidden fields and 
					//TinyMCE editor do not get validated
					$fields[name] = $field;
                    toValidate[name] = Utils.getVal($field);
				}
				$values[name] = Utils.getVal($field);
			}

			//validate fields
			var $validation = $form.data('validation') || false;
			if ( $.isPlainObject($validation) ) {
				var validator = new FormValidator();
				validator.init($validation);
				var errors = validator.validate(toValidate);
				if ( ! $.isEmptyObject(errors) ) {
					Utils.removeAllFieldErrors($panel);
					
					//adding a change handler for input validation
					//so it can be turned off without affecting
					//JQM select handlers
					var validateVal = function(e) {
						var $field = e.data.obj;
						var field = e.data.field;
						var obj = {};
						obj[field] = Utils.getVal($field);
						var errs = validator.validate(obj);
						Utils.removeFieldError($field);
						if ( ! $.isEmptyObject(errs) ) {
							Utils.showFieldError($(this), errs[field]);
							Utils.refreshJqmField($field);
						}
					}
					
					for (var field in $fields) {
						(function(field) {
							if (errors[field]) {
								Utils.showFieldError($fields[field], errors[field]);
							}
							
							$fields[field].on('change', {
								field: field,
								obj: $fields[field]
							}, validateVal);
							/*
							$fields[field].on('change', function() {
								var obj = {};
								obj[field] = Utils.getVal($fields[field]);
								var errs = validator.validate(obj);
								Utils.removeFieldError($fields[field]);
								if ( ! $.isEmptyObject(errs) ) {
									Utils.showFieldError($(this), errs[field]);
									Utils.refreshJqmField($fields[field]);
								}
							});
							*/
						})(field);
					}
					
					Utils.showModalWarning( I18n.t('error'), I18n.t('message.errors.found'), false);
					return false;
				}
			}
			
			//check for uploads and add upload data to vals
			var uploads = {};
			$('.plupload', $panel).each(function() {
				var field = $(this).data('field');
				var files = $(this).attr('data-files');
				if (field !== undefined && files.length) {
					field = field.replace('[]', '');
					uploads[field] = JSON.parse(files);
				}
			});
			if ( ! $.isEmptyObject(uploads) ) {
				$values['uploads'] = uploads;
			}
			
			if ( $.isArray($obj) ) {
				if (index === false) {
					$obj.push($values);
				} else {
					$obj[index] = $values;
				}
			} else {
				$obj = $values;
			}
			
			updatePanelList(panel_id, $values, index);
			$(object_id).val( JSON.stringify($obj) ).trigger('change');
            $form.trigger('subform:submit');
			closePanel(panel_id);
		};
		
		var updatePanelList = function(panel_id, $obj, index) {
			if ( ! panel_id || ! $obj || $.isEmptyObject($obj) ) {
				return false;
			}
			var $form = $(panel_id + ' form');
            var isReadOnly = parseInt( $form.data('readonly') ) === 1;
            if (isReadOnly) {
                return false;
            }

			var module = panel_id.replace('#form-panel-', '');
			var $ul = $('#form-panel-list-' + (module ? module : '') );
			if ( ! $form || ! $ul) {
				return false;
			}

			index = parseInt(index);
			var title_field = $form.data('titleField');
			var title = $obj[title_field] === undefined ? false : $obj[title_field];
			if ( ! title) {
				for (var name in $obj) {
					title = $obj[name];
					break;
				}
			}

			if ( isNaN(index) ) {
				var count = 0;
				$ul.children('li').each(function() { count++; });
				var $li = $('<li/>').attr('data-index', count);
				var $a = $('<a/>').attr('href', '#form-panel-' + module).addClass('form-panel-edit');
				$('<h3/>').text(title).appendTo($a);
				$a.appendTo($li);
				$('<a/>')
					.attr('href', '#form-panel-' + module)
					.addClass('form-panel-delete')
					.text( I18n.t('delete') )
				.appendTo($li);
				$ul.append($li);
			} else {
				$ul.children('li[data-index="' + index + '"]').find('h3').text(title);
			}
			
			$ul.listview().listview('refresh');
			if ( $ul.hasClass('ui-sortable') ) {
				$ul.sortable('refresh');
			}
		};

		$('.form-panel').each(function(i) {
			var panel_id =  $(this).attr('id');
			var module = panel_id.replace('form-panel-', '');
			var $form = $('#' + panel_id + ' form');
			var isReadOnly = parseInt( $form.data('readonly') ) === 1;
			var object_id = $form ? $form.data('objectId') : false;
			var $obj = object_id ? $(object_id).val() : false;
			if ($form === undefined || ! object_id || ! $obj) {
				return false;
			}
			$obj = $obj ? JSON.parse($obj) : [];
			if ( $.isPlainObject($obj) ) {
				$obj = [$obj];
			}	
			var $ul = $('#form-panel-list-' + (module ? module : '') );
			if ( ! $ul ) {
				return false;
			}
	      
			var title_field = $form.data('titleField');
			for (var i=0; i < $obj.length; i++) {
				var $ob = $obj[i];
				if ( ! title_field || $ob[title_field] === undefined) {
					for (var name in $ob) {
						title_field = name;
						break;
					}
				}
				var $li = $('<li/>').attr('data-index', i);
				var $a = $('<a/>').attr('href', '#form-panel-' + module).addClass('form-panel-edit');
				$('<h3/>').text($ob[title_field]).appendTo($a);
				$a.appendTo($li);

                if ( ! isReadOnly) {
                    $('<a/>')
                        .attr('href', '#form-panel-' + module)
                        .addClass('form-panel-delete')
                        .text( I18n.t('delete') )
                    .appendTo($li);
                }
				$li.appendTo($ul);
			}
			
			$ul.listview().listview('refresh');
            if (isReadOnly) {
                $('.form-panel-add, .form-panel-delete', $form).addClass('ui-disabled');
            } else if ( $form.data('sort') ) {
				$ul.sortable({
					axis: 'y',
					stop: function(e, ui) {
						var aux = [];
						var $obj = JSON.parse( $(object_id).val() );
						$('li', $ul).each(function(i) {
							var index = parseInt( $(this).attr('data-index') );
							aux.push($obj[index]);
							$(this).attr('data-index', i);
						});
						$(object_id).val( JSON.stringify(aux) ).trigger('change');
					}
				});
			}

			//add panel id to cancel button
			var $panel = $('#' + panel_id);
			var href = $('a.form-panel-close', $panel).attr('href');
			$('button.form-panel-close', $panel).attr('data-panel-id', href);
		});
			
		$('.form-panel-add').click(function(e) {
			e.preventDefault();
			e.stopPropagation();
			var id = $(this).attr('href');
			setPanelFormVals(id, true, false);
			openPanel(id);
		});
			
		$('.form-panel-list').on('click', '.form-panel-edit', function(e) {
			e.preventDefault();
			e.stopPropagation();
			var id = $(this).attr('href');
			var index = $(this).parent('li').data('index');
			setPanelFormVals(id, false, index);
			$(this).removeClass('ui-btn-active').blur();
			openPanel(id);
		});
			
		$('.form-panel-list').on('click', '.form-panel-delete', function(e) {
			e.preventDefault();
			e.stopPropagation();
			$link = $(this);
			var message = I18n.t('delete') + ' ' + $link.prev().find('h3').text() + '?';
			Utils.showModalConfirm( I18n.t('confirm'), message, function() {
				deleteSubformObject($link);
			});
		});
			
		$('.form-panel-save').click(function(e) {
			e.preventDefault();
			var id = '#' + $(this).parents('.form-panel').attr('id');
			submitPanelFormVals(id);
		});
		
		$('.form-panel-close').click(function(e) {
			e.preventDefault();
			e.stopPropagation();
			var id = $(this).attr('href') || $(this).data('panelId');
			closePanel(id);
		});
		
		$('.relation-list-open').click(function(e) {
			$link = $(this);
			var id = $link.attr('href');
			if ( $(id).is(':visible') ) {
				$link.removeClass('ui-icon-carat-u').addClass('ui-icon-carat-d');
				$(id).slideUp(300);
			} else {
				e.preventDefault();
				$link.removeClass('ui-icon-carat-d').addClass('ui-icon-carat-u ui-btn-active');
				$(id).slideDown(300);
			}
		});
		
		$(window).on('resize', adjustPanelWidth);
		adjustPanelWidth();
		
		$(window).on('page:unload', function() {
			$('.form-panel').each(function() {
				$(this).panel('close');
			});
			$('.form-panel-add').off('click');
			$('.form-panel-save').off('click');
			$('.form-panel-close').off('click');
			$('.relation-list-open').off('click');
			$('.form-panel-list').off('click', '.form-panel-edit');
			$('.form-panel-list').off('click', '.form-panel-delete');
			$('.panel-overlay').remove();
			if ( $('.form-panel-list').hasClass('ui-sortable') ) {
				$('.form-panel-list').sortable('destroy');
			}
			$(window).off('resize', adjustPanelWidth);
			$(this).off('page:unload');
		});
	});
});