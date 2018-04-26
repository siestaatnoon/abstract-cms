define([
	'config',
	'jquery',
	'underscore',
    'classes/I18n',
	'classes/Class'
], function(app, $, _, I18n) {

    /**
     * Class for validating forms. By default, this class has the following field validation types:<br/><br/>
     * <ul>
     *   <li>required: field not empty</li>
     *   <li>email: valid email address (checks structure, does NOT validate mailbox/domain)</li>
     *   <li>min: minimum [param] length for String or Array</li>
     *   <li>max: maximum [param] length for String or Array</li>
     *   <li>natural: whole number zero or greater</li>
     *   <li>natural_not_zero: whole number greater than zero</li>
     *   <li>strong_password: string containing at least 1 uppercase, 1 lowercase and 1 number</li>
     * </ul>
     *
     * @exports classes/ScriptLoader
     * @requires config
     * @requires jquery
     * @requires Underscore
     * @requires classes/I18n
     * @constructor
     * @augments classes/Class
     */
	var FormValidator = Class.extend({
    /** @lends classes/FormValidator.prototype **/

        /**
         * @property {Boolean} _has_required
         * True if at least one form field is required (non-empty)
         */
	    _has_required: false,

        /**
         * @property {Object} _rule_defaults
         * Default storage object for a validation rule
         */
	    _rule_defaults: {
            param: null,
            message: '',
            rules: ''
        },

        /**
         * @property {Object} _rules
         * Default rules for validation
         */
		_rules: {
			required: {
				param: null,
				message: I18n.t('validate.required')
			},
			email: {
				param: null,
				message: I18n.t('validate.email')
			},
			min: {
				param: 0,
				message: I18n.t('validate.min', '%p')
			},
			max: {
				param: 1,
				message: I18n.t('validate.max', '%p')
			},
			natural: {
				param: null,
				message: I18n.t('validate.natural')
			},
			natural_not_zero: {
				param: null,
				message: I18n.t('validate.not_zero')
			},
			strong_password: {
				param: null,
				message: I18n.t('validate.password')
			}
		},

        /**
         * @property {Object} _toValidate
         * Form fields to validate organized with field name(s) as properties, populated with
         * call to this.addField()
         */
		_toValidate: {},


        /**
         * Initializes the FormValidator. Accepts an object of form fields to validate with
         * the field name(s) as the property and each containing and object of the following:<br/><br/>
         * <ul>
         *   <li>rules: function to validate field</li>
         *   <li>message: error message to display</li>
         *   <li>param: argument(s) to pass into rules function</li>
         * </ul>
         *
         * @param {Object} fields - Object of { [field_name]: {[validation parameter]} ... } for validation
         */
		init: function(fields) {
			if ( _.isObject(fields) ) {
				for (field in fields) {
					this.addField(field, fields[field]);
				}
			}
		},


        /**
         * Initializes the FormValidator. Accepts an object of form fields to validate with
         * the field name(s) as the property and each containing and object of the following:
         * <ul>
         *   <li>rules: function to validate field</li>
         *   <li>message: error message to display</li>
         *   <li>param: argument(s) to pass into rules function</li>
         * </ul>
         *
         * @param {String} field_name - Form field name
         * @param {Object} field_rules - Object containing the validation parameters
         * @see {@link init} for detail of validation parameters
         */
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
                        //rule undefined, see this.rules
						var args = [
							'FormValidator.addField:',
                            rule,
                            field_name
						];
                        console.log( I18n.t('error.validate.rule', args) );
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


        /**
         * Parses a JSON string parameter argument for a validation rule into
         * a valid javascript type.
         *
         * @param {String} args - The JSON string
         * @return {*} The argument as a javascript type
         */
        parseParam: function(arg) {
            try {
                var obj = JSON.parse(arg);
                if (obj && typeof obj === "object") {
                    return obj;
                }
            } catch (e) {}
            return arg;
        },


        /**
         * Clears the validation fields and parameters. Typically called upon
         * loading a new form page.
         *
         */
		reset: function() {
			this._toValidate = {};
		},


        /**
         * Creates a custom validation rule or overwrites an existing validation.
         *
         * @param {String} name - The rule name (alphanumeric and underscore)
         * @param{Function} fn - Function to validate form field
         * @param {String} error_msg - Error message to display on failed field validation
         */
		rule: function(name, fn, error_msg) {
			var message = '';
			var is_valid = true;
			
			if ( ! _.isString(name) || $.trim(name).length == 0) {
				is_valid = false;
                console.log( I18n.t('error.validate.name', 'FormValidator.rule:') );
			} else if ( ! _.isFunction(fn) && typeof fn === 'string' && $.trim(fn).length == 0 ) {
				is_valid = false;
                console.log( I18n.t('error.validate.fn', 'FormValidator.rule:') );
			} else if ( ! _.isString(error_msg) || $.trim(error_msg).length == 0) {
				is_valid = false;
                console.log( I18n.t('error.validate.error_msg', 'FormValidator.rule:') );
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
                        fnStr += "console.log('FormValidator.rule[" + name + "]: '+e.name+': '+e.message);";
                    }
                    fnStr += 'return false;}';
                    validFn = new Function('value', 'param', fnStr);
                }

				this._validators[name] = validFn;
            }
			 
			 return is_valid;
		},

        /**
         * Validates the form fields given an object of new/changed fields and corresponding values
         * of the form.
         *
         * @param {Object} changedFields - Object of { [field name]: [value] ... } to validate
         * @return {Void|Object} An object of errors with field name(s) as property and corresponding array
         * of error messages
         */
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

        /**
         * Default validation functions of this class. The followng are the validation types:<br/><br/>
         * <ul>
         *   <li>required: field not empty</li>
         *   <li>email: valid email address (checks structure, does NOT validate mailbox/domain)</li>
         *   <li>min: minimum [param] length for String or Array</li>
         *   <li>max: maximum [param] length for String or Array</li>
         *   <li>natural: whole number zero or greater</li>
         *   <li>natural_not_zero: whole number greater than zero</li>
         *   <li>strong_password: string containing at least 1 uppercase, 1 lowercase and 1 number</li>
         * </ul>
         *
         */
		_validators: {
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

			email: function(value, param) {
				var regex = /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i
				
				return value.match(regex) !== null;
			},
			
			max: function(value, param) {
				var length = parseInt(param);
				if ( isNaN(length)) {
					//rule parameter invalid, must be an int
					//return true to skip validation
                    console.log( I18n.t('error.validate.param', ['FormValidator._validators.max:', param]) );
					return true;
				}
				return value.length <= length;
			},
			
			min: function(value, param) {
				var length = parseInt(param);
				if ( isNaN(length)) {
					//rule parameter invalid, must be an int
					//return true to skip validation
                    console.log( I18n.t('error.validate.param', ['FormValidator._validators.min:', param]) );
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
