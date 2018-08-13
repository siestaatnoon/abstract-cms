define([
	'config',
	'jquery',
	'underscore',
    'classes/I18n'
], function(app, $, _, I18n) {

    /**
     * Class containing utility functions used in Backbone/Javascript files.
     *
     * @exports classes/Utils
     * @requires config
     * @requires jquery
     * @requires Underscore
	 * @requires classes/I18n
     * @constructor
     */
	var Utils = {

        /**
         * Converts single quotes contained in a string array, object member properties or a string
		 * itself to the unicode u0027 character. Primarily used for JSON parsing.
         *
         * @param {Array|Object|String} mixed - The string or object or array whose string values to convert
         * @return {Array|Object|String} The string, object or array with single quotes converted
         */
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

        /**
         * Recursively sets an object's  member properties to null readying it for garbage collection.
         *
         * @param {Object} obj - The object
         * @return {null} The new property value
         */
		gcReadyObject: function(obj) {
			if ( $.isPlainObject(obj) ) {
				for (var prop in obj) {
					obj[prop] = this.gcReadyObject(obj[prop]);
				}
			}
			
			obj = null;
			return obj;
		},

        /**
         * Returns the value of a form field as a jQuery object.
         *
         * @param {jQuery} $field - The jQuery object representing the form field
         * @return {String|Array} The field value, note that for a non-existing field an empty string is returned
         */
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

        /**
         * Hides a jQuery overlay (semi-transparent page blocker).
         *
         */
        hideOverlay: function() {
            if ( $('.abstract-overlay').length ) {
                $('.abstract-overlay').css('display', 'none');
            }
        },

        /**
         * Checks if a string is a JSON object. Note that scalar values will return false.
         *
         * @param {String} str - The string to check
         * @return {Boolean} True if string represents a JSON object
         */
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
         * @return {Object|null} The formatted object or null if jqXHR parameter invalid
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

        /**
         * Refreshes a jQuery Mobile select, checkbox, radio button or flipswitch widget.
         *
         * @param {jQuery|String} selector - The jQuery object or selector string
         */
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

        /**
         * Removes all form field errors in an admin form page.
         *
         * @param {jQuery|String} selector - The form container jQuery object or selector string
         */
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

        /**
         * Removes the error(s) of a single form field in an admin form page.
         *
         * @param {jQuery|String} selector - The form field jQuery object or selector string
         */
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

        /**
         * Sets the CRSF token as a header in the AJAX configuration for an admin form.
         */
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

        /**
         * Shows the error(s) of a single form field in an admin form page.
         *
         * @param {jQuery|String} selector - The form field jQuery object or selector string
         * @param {String|Array} messages - The error message or array of error messages to join
         */
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

        /**
         * Displays a jQuery Mobile modal confirm box.
         *
         * @param {String} label - The modal label (top of confirm box)
         * @param {String} message - The message string
         * @param {Function} onOk - The callback when OK button is pressed
         * @param {Function} onCancel - The callback when Cancel button is pressed
         * @param {Object} context - The context for the above callbacks
         * @param {String} okText - The text for the OK button, defaults to "OK" if undefined or empty
         * @param {String} cancelText - The text for the Cancel button, defaults to "Cancel" if undefined or empty
         */
		showModalConfirm: function(label, message, onOk, onCancel, context, okText, cancelText) {
			label = label || I18n.t('message');
			message = message || '';
			onCancel = onCancel !== undefined && typeof onCancel === 'function' ? onCancel : false;
			onOk = onOk !== undefined && typeof onOk === 'function' ? onOk : false;
			context = context || null;
			okText = okText || I18n.t('ok');
			cancelText = cancelText || I18n.t('cancel');
			
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

        /**
         * Displays a jQuery Mobile modal dialog box.
         *
         * @param {String} label - The modal label (top of confirm box)
         * @param {String} message - The message string
         * @param {Function} callback - The callback when Continue button is pressed
         * @param {Object} context - The context for the above callbacks
         * @param {closeText} closeText - The text for the Continue button, defaults to "Continue" if undefined or empty
         */
		showModalDialog: function(label, message, callback, context, closeText) {
			label = label || I18n.t('message');
			message = message || '';
			callback = callback !== undefined && typeof callback === 'function' ? callback : false;
			context = context || null;
			closeText = closeText || I18n.t('continue');
			
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

        /**
         * Displays a jQuery Mobile modal warning box.
         *
         * @param {String} label - The modal label (top of confirm box)
         * @param {String} message - The message string
         * @param {Function} callback - The callback when Continue button is pressed
         * @param {Object} context - The context for the above callbacks
         * @param {closeText} closeText - The text for the Continue button, defaults to "Continue" if undefined or empty
         */
		showModalWarning: function(label, message, callback, context, closeText) {
			label = label || I18n.t('error');
			message = message || I18n.t('error.general.unknown', label + ':');
			callback = callback !== undefined && typeof callback === 'function' ? callback : false;
			context = context || null;
			closeText = closeText || I18n.t('continue');
			
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

        /**
         * Shows a jQuery overlay (semi-transparent page blocker).
         *
         */
		showOverlay: function() {
        	if ( $('.abstract-overlay').length ) {
                $('.abstract-overlay').css('display', 'block');
            }
        },

        /**
         * Converts the unicode u0027 contained in a string array, object member properties or a string
         * itself to a single quote character. Primarily used after JSON parsing.
         *
         * @param {Array|Object|String} mixed - The string or object or array whose string values to convert
         * @return {Array|Object|String} The string, object or array with unicode u0027 character converted
         */
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