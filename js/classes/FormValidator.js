define([
	'config',
	'jquery',
	'underscore',
	'classes/Class'
], function(app, $, _, C) {
	var FormValidator = Class.extend({
	    _has_required: false,

	    _rule_defaults: {
            param: null,
            message: '',
            rules: ''
        },

		_rules: {
			required: {
				param: null,
				message: 'This field is required'
			},
			email: {
				param: null,
				message: 'Please enter a valid email'
			},
			min: {
				param: 0,
				message: 'Please select at least %p item(s)'
			},
			max: {
				param: 1,
				message: 'Please select at most %p item(s)'
			},
			natural: {
				param: null,
				message: 'Please enter a whole number'
			},
			natural_not_zero: {
				param: null,
				message: 'Please enter a whole number greater than zero'
			},
			strong_password: {
				param: null,
				message: 'Password must contain at least 1 uppercase, 1 lowercase and 1 number'
			}
		},
		
		_toValidate: {},
		
		init: function(fields) {
			if ( _.isObject(fields) ) {
				for (field in fields) {
					this.addField(field, fields[field]);
				}
			}
		},
		
		addField: function(field_name, field_rules) {
			if ( _.isEmpty(field_rules) ) {
				return false;
			}
			var new_rules = {};
			
			for (var rule in field_rules) {
                var fr = field_rules[rule];
                var validate = {};
                if (rule === 'required' || rule.indexOf('required') === 0 ) {
                    this._has_required = true;
                }

				if ( _.isUndefined(this._rules[rule]) ) {
                    var is_valid = false;
                    if (fr['rules'] !== undefined && fr['message'] !== undefined) {
                    //custom rule
                        validate = _.extend(this._rule_defaults, fr);
                        var param = validate['param'] ? this.parseParam(validate['param']) : validate['param'];
                        validate['param'] = param;
                        is_valid = this.rule(rule, validate['rules'], validate['message']);
                    }

                    if ( ! is_valid) {
                        if (app.debug) {
                            //rule undefined, see this.rules
                            var message = 'FormValidator.addField: Rule ['+ rule + '] undefined or invalid for field [';
                            message += field_name + '], validation skipped';
                            console.log(message);
                        }
                        continue;
                    }
				} else {
					validate = _.clone(this._rules[rule]);
					if ( _.isObject(fr) ) {
                        if (fr['param'] !== undefined) {
                            validate['param'] = this.parseParam(fr['param']);
                        }
                        if (fr['message'] !== undefined && fr['message'].length) {
                            validate['message'] = fr['message'];
                        }
					} else {
						validate['param'] = fr;
					}
				}

                new_rules[rule] = validate;
			}

			if ( ! _.isEmpty(new_rules) ) {
				this._toValidate[field_name] = new_rules;
				return true;
			}
			
			return false;
		},

        parseParam: function(str) {
            try {
                var obj = JSON.parse(str);
                if (obj && typeof obj === "object") {
                    return obj;
                }
            } catch (e) {}
            return str;
        },
		
		reset: function() {
			this._toValidate = {};
		},
		
		rule: function(name, fn, error_msg) {
			var message = '';
			var is_valid = true;
			
			if ( ! _.isString(name) || $.trim(name).length == 0) {
				is_valid = false;
				if (app.debug) {
					message = 'FormValidator.rule: Invalid param 1 [name] must be non-zero length string';
					console.log(message);
				}
			} else if ( ! _.isFunction(fn) && typeof fn === 'string' && $.trim(fn).length == 0 ) {
				is_valid = false;
				if (app.debug) {
					message = 'FormValidator.rule: Invalid param 2 [fn] must be a function or executable javascript';
					console.log(message);
				}
			} else if ( ! _.isString(error_msg) || $.trim(error_msg).length == 0) {
				is_valid = false;
				if (app.debug) {
					message = 'FormValidator.rule: Invalid param 3 [error_msg] must be non-zero length string';
					console.log(message);
				}
			}
			
            if (is_valid) {
			 	this._rules[name] = {
					param: null,
					message: error_msg
				}

				var validFn = fn;
				if (typeof validFn === 'string') {
                    var fnStr = 'try{' + fn + '}catch(e){';
                    if (app.debug) {
                        fnStr += "console.log('FormValidator.rule["+name+"]: '+e.name+': '+e.message);";
                    }
                    fnStr += 'return false;}';
                    validFn = new Function('value', 'param', fnStr);
                }

				this._validators[name] = validFn;
            }
			 
			 return is_valid;
		},
		
		validate: function(changedFields) {
			if ( _.isEmpty(this._toValidate) ) {
				return true;
			}
			var errors = {};

			for (var field in changedFields) {
				if ( _.isUndefined(this._toValidate[field]) ) {
				//validation rule not set for this field so skip
					continue;
				}

				var rules = this._toValidate[field];
				var value = _.isBoolean(changedFields[field]) || 
							_.isNull(changedFields[field]) || 
							_.isUndefined(changedFields[field]) ? 
							'' : changedFields[field];
				var field_errors = [];
				
				if (this._has_required === false && value.length == 0) {
				//Field isn't required and hasn't been filled in so validation unnecessary
					continue;
				}

				for (var rule in rules) {
					var r = rules[rule];
					if ( this._validators[rule].call(this, value, r['param']) === false ) {
						var message = r['message'].replace('%p', r['param']);
						field_errors.push(message);
					}
				}
				
				if (field_errors.length > 0) {
					errors[field] = field_errors;
				}
			}

			this._toValidate = {};
			return errors;
		},
		
		_validators: {
			email: function(value, param) {
				var regex = /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i
				
				return value.match(regex) !== null;
			},
			
			max: function(value, param) {
				var length = parseInt(param);
				if ( isNaN(length)) {
					if (app.debug) {
					//rule parameter invalid, must be an int
					//return true to skip validation
						var message = 'FormValidator.max: Invalid parameter ['+ param + '] found, validation skipped';
						console.log(message);
					} 
					return true;
				}
				return value.length <= length;
			},
			
			min: function(value, param) {
				var length = parseInt(param);
				if ( isNaN(length)) {
					if (app.debug) {
					//rule parameter invalid, must be an int
					//return true to skip validation
						var message = 'FormValidator.min: Invalid parameter ['+ param + '] found, validation skipped';
						console.log(message);
					} 
					return true;
				}
				return value.length >= length;
			},
			
			natural: function(value, param) {
                if ( $.type(value) !== 'string' ) {
                    value = value.toString();
                }
				return value.match(/^\d+$/) !== null;
			},

			natural_not_zero: function(value, param) {
				if ( $.type(value) !== 'string' ) {
                    value = value.toString();
                }
				return value.match(/^\d+$/) !== null && parseInt(value) !== 0;
			},
			
			required: function(value, param) {
                if ( _.isArray(value) && value.length === 1 && value[0] === '') {
                    //case where value could be an empty array of hidden/text field
                    return false;
                } else if ( _.isObject(value) ) {
				    return $.isEmptyObject(value) === false;
                } else if ( _.isString(value) ) {
					value = $.trim(value);
				} else if ( _.isNumber(value) ) {
					value = value.toString();
				}

				return value.length > 0;
			},
			
			strong_password: function(value, param) {
			    if ( $.trim(value) === '' ) {
			    // empty values are not validated
                    return true;
                }

				//checks for one uppercase char, one lowercase char and one number
				var upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
				var lower = 'abcdefghijklmnopqrstuvwxyz';
				var numbers = '0123456789';
				var has_upper = false;
				var has_lower = false;
				var has_number = false;

				for (var i=0; i < value.length; i++) {
					var c = value.substr(i, 1);
					if (upper.search(c) > -1) {
						has_upper = true;
					} else if (lower.search(c) > -1) {
						has_lower = true;
					} else if (numbers.search(c) > -1) {
						has_number = true;
					}
				}
				
				return has_upper && has_lower && has_number;
			}
		}
	});
	
	return FormValidator;
});
