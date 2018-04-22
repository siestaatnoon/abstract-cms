define([
	'config',
	'jquery',
	'underscore'
], function(app, $, _) {
	
	var Utils = {

        escapeSingleQuotes: function(mixed) {
            var type = typeof mixed;
            if ( $.isArray(type) ) {
                for (var i=0; i < mixed.length; i++) {
                    mixed[i] = this.escapeSingleQuotes(mixed[i]);
                }
            } else if (type === 'object') {
                for (var field in mixed) {
                    mixed[field] = this.escapeSingleQuotes(mixed[field]);
                }
            } else if (type === 'string') {
                mixed = mixed.replace(/[']/g, '\\u0027');
            }
            return mixed;
        },
		
		gcReadyObject: function(obj) {
			if ( $.isPlainObject(obj) ) {
				for (var prop in obj) {
					obj[prop] = this.gcReadyObject(obj[prop]);
				}
			}
			
			obj = null;
			return obj;
		},
		
		getVal: function($field) {
			if ($field instanceof jQuery === false || $.isFunction($field.val) === false) {
				return false;
			}

			var field_type = $field.attr('type');
			var name = $field.attr('name') || '';
			var is_multi = name.indexOf('[]') !== -1;
			var values = '';
				
			if ( this.isJson( $field.val() ) ) {
			//JSON data
				var val = JSON.parse( $field.val() );
				if (is_multi && $.isArray(val) === false ) {
					val = [val];
				}
				values = val;
			} else if (is_multi) {
			//field of array values
				var tag = $field.prop('nodeName') || '';
				var vals = [];
				if (field_type === 'checkbox') {
				//retrieve multiple vals for checkbox
					$field.filter(':checked').each(function() {
						vals.push( $(this).val() );
					});
				} else if (tag.toLowerCase() === 'select') {
				//retrieve vals for multiple select
					vals = $field.val() || vals;
				} else {
				//other multi input field (e.g. text input or textarea)
					$field.each(function() {
						vals.push( $(this).val() );
					});
				}
				values = vals;
			} else if (field_type === 'checkbox' || field_type === 'radio') {
			//in case a single checkbox not checked or radio button 
			//not selected, set to empty string value
				var val = '';
				$field.filter(':checked').each(function() {
					val = $(this).val();
				});
				values = val;
			} else if ( $field.hasClass('tinymce') ) {
			//content editor
				values = $field.html();
			} else {
			//all other fields... NOTE: if value undefined
			//then an empty string is given as the value
				var val = $field.val();
				values = val === undefined  || val === null ? '' : val;
			}
			return values;
		},

        hideOverlay: function() {
            if ( $('.abstract-overlay').length ) {
                $('.abstract-overlay').css('display', 'none');
            }
        },
		
		isJson: function(str) {
			var is_json = true;
			var obj = false;
			try {
                obj = JSON.parse(str);
			} catch (e) {
				is_json = false;
			}
			return is_json && _.isObject(obj);
		},

        /**
         * Returns a formatted object from a jqXHR object returned from an AJAX call with
         * the following properties:<br/><br/>
         * statusCode: HTTP status code in response<br/>
         * data: The response data and, if JSON, the parsed object from the JSON<br/>
         * errors: The array of errors, if any<br/>
         * response: The raw response string
         *
         * @param {jqXHR} jqXHR - The jQuery XHR object
         * @return {Object/null} The formatted object or null if jqXHR parameter invalid
         */
        parseJqXHR: function(jqXHR) {
            if ( _.isObject(jqXHR) === false || _.has(jqXHR, 'responseText') === false || _.has(jqXHR, 'status') === false ) {
                return null;
            }

            var response = jqXHR.responseText;
            var isJson = this.isJson(response);
            var data = isJson ? JSON.parse(response) : response;
            var errors = [];
            if ( isJson && _.has(data, 'errors') ) {
                errors = _.isArray(data.errors) ? data.errors : [data.errors];
            }
            return {
                statusCode: jqXHR.status,
                data: data,
                errors: errors,
                response: response
            };
        },

		
		refreshJqmField: function(selector) {
			var $field = $.type(selector) === 'string' ? $(selector) : selector;
			if ( ! $field) {
				return false;
			}
			var tag = $field.prop('nodeName');
			tag = tag === undefined ? false : tag.toLowerCase();
			var type = $field.attr('type');
			type = type === undefined ? false : type.toLowerCase();
			if ( ! tag && ! type) {
				return false;
			}

			if ($field.data('role') === 'flipswitch') {
				$field.flipswitch().flipswitch('refresh');
			} else if (tag === 'select') {
				$field.selectmenu().selectmenu('refresh');
			} else if (type === 'checkbox' || type === 'radio') {
				$field.checkboxradio().checkboxradio('refresh');
			}
		},
		
		removeAllFieldErrors: function(selector) {
			var $container = $.type(selector) === 'string' ? $(selector) : selector;
			if ( ! $container) {
				return false;
			}
			$('.form-group', $container).removeClass('has-error has-feedback');
			$('.field-error-message', $container).remove();
			
			//Bootstrap
			//$('.form-control-feedback', $container).remove();
		},
		
		removeFieldError: function(selector) {
			var $field = $.type(selector) === 'string' ? $(selector) : selector;
			if ( ! $field) {
				return false;
			}
			var $field_group  = $field.closest('.form-group');
			var $field_block  = $field.closest('.field-block');
			var $block = $field_block.length ? $field_block : $field_group;
			$('.field-error-message', $block).remove();
			$field_group.removeClass('has-error has-feedback');
			
			//Bootstrap
			//$('.form-control-feedback', $field_block).remove();
		},

		setCrsfToken: function() {
			var crsf_token = '';
			var cookie_name = app.csrfToken;

			if (document.cookie) {
				var index = document.cookie.indexOf(cookie_name);
				if (index != -1) {
					var start = (document.cookie.indexOf("=", index) + 1);
					var end = document.cookie.indexOf(";", index);
					if (end == -1) {
						end = document.cookie.length;
					}
					crsf_token = document.cookie.substring(start, end);
				}
			}
			
			var config = {};
			config['headers'] =  {};
			config['headers'][cookie_name] =  crsf_token;
			$.ajaxSetup(config);
		},
		
		showFieldError: function(selector, messages) {
			var $field = $.type(selector) === 'string' ? $(selector) : selector;
			if ( ! $field) {
				return false;
			}
			var $field_group  = $field.closest('.form-group');
			var $field_block  = $field.closest('.field-block');
			if ( ! $field_block.length) {
				$field_block = $field_group;
			}

			/*
			//Bootstrap
			if ($field.attr('type') === 'text') {
				$('<span />')
					.addClass('glyphicon glyphicon-remove form-control-feedback')
					.insertAfter($field);
			}
			*/

			if ( $.type(messages) === 'array' ) {
                messages = messages.join('<br/>');
            }
	
			$('<span />')
				.addClass('help-block field-error-message')
				.html(messages)
			.appendTo($field_block);
			$field_group.addClass('has-error has-feedback');
		},
		
		showModalConfirm: function(label, message, onOk, onCancel, context, okText, cancelText) {
			var label = label || 'Message';
			var message = message || '';
			var onCancel = onCancel !== undefined && typeof onCancel === 'function' ? onCancel : false;
			var onOk = onOk !== undefined && typeof onOk === 'function' ? onOk : false;
			context = context || null;
			okText = okText || 'OK';
			cancelText = cancelText || 'Cancel';
			
			$('.modal-confirm-cancel').off().click(function() {
            	if (onCancel !== false) {
            		onCancel.call(context);
            	}
				$(this).off();
				$('#modal-confirm').popup('close').popup('destroy').hide();
			}).text(cancelText);
			
			$('.modal-confirm-ok').off().click(function() {
            	if (onOk !== false) {
            		onOk.call(context);
            	}
				$(this).off();
				$('#modal-confirm').popup('close').popup('destroy').hide();
			}).text(okText);
			
			$('#modal-confirm-label').text(label);
			$('#modal-confirm-body').html(message);
			$('#modal-confirm').show().popup({ dismissable: false }).popup('open');
		},
		
		showModalDialog: function(label, message, callback, context, closeText) {
			var label = label || 'Message';
			var message = message || '';
			var callback = callback !== undefined && typeof callback === 'function' ? callback : false;
			context = context || null;
			closeText = closeText || 'Continue';
			
			$('.modal-dialog-close').off().click(function() {
				if (callback !== false) {
            		callback.call(context);
            	}
				$(this).off();
				$('#modal-dialog').popup('close').popup('destroy').hide();
			}).text(closeText);
			
			$('#modal-dialog-label').text(label);
			$('#modal-dialog-body').html(message);
			$('#modal-dialog').show().popup({ dismissable: false }).popup('open');
		},
		
		showModalWarning: function(label, message, callback, context, closeText) {
			var label = label || 'Error';
			var message = message || 'An application error has occurred.';
			var callback = callback !== undefined && typeof callback === 'function' ? callback : false;
			context = context || null;
			closeText = closeText || 'Continue';
			
			$('.modal-warning-close').off().click(function() {
            	if (callback !== false) {
            		callback.call(context);
            	}
				$(this).off();
				$('#modal-warning').popup('close').popup('destroy').hide();
			}).text(closeText);
			
			$('#modal-warning-label').text(label);
			$('#modal-warning-body').html(message);
			$('#modal-warning').show().popup({ dismissable: false }).popup('open');
		},

		showOverlay: function() {
        	if ( $('.abstract-overlay').length ) {
                $('.abstract-overlay').css('display', 'block');
            }
        },

        unescapeSingleQuotes: function(mixed) {
            var type = typeof mixed;
            if ( $.isArray(type) ) {
                for (var i=0; i < mixed.length; i++) {
                    mixed[i] = this.unescapeSingleQuotes(mixed[i]);
                }
            } else if (type === 'object') {
                for (var field in mixed) {
                    mixed[field] = this.unescapeSingleQuotes(mixed[field]);
                }
            } else if (type === 'string') {
                mixed = mixed.replace(/\\u0027/g, "'");
            }
            return mixed;
        }
	};
	
	return Utils;
});