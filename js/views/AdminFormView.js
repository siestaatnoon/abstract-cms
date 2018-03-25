define([
	'config',
	'jquery',
	'underscore', 
	'backbone',
	'classes/ScriptLoader',
	'classes/Utils'
], function(app, $, _, Backbone, ScriptLoader, Utils) {
	var AdminFormView = Backbone.View.extend({

		id: '#tpl-form-view',
		
		blocks: {},
		
		boostrapModel: null,
		
		form_id: '',
		
		fields: [],
		
		scripts: {},

		events: {
			'click #submit-save' : 'formSubmit',
			'click #button-cancel' : 'formCancel',
			'click #button-delete' : 'formDelete'
		},

		initialize: function(options) {
			var tpl = options.template || '';
			var fields = options.fields || [];
			
			if (_.isEmpty(fields) && app.debug) {
				console.log('FormView.initialize: form [fields] not returned in API call.');
			}
			
			if (tpl.length == 0 && app.debug) {
				console.log('FormView.initialize: form [template] not returned in API call.');
			}
		
			this.fields = fields;
			this.form_id = options.form_id || null;
			this.blocks = options.blocks || this.blocks;
			this.scripts = options.scripts || null;
			this.boostrapModel = options.bootstrapModel || null;
			this.template = _.template(tpl);
		},

		render: function() {
			//first check if model bootstrapped in initial API data call
			if (this.boostrapModel) {
				this.model.set(this.boostrapModel);
				var template = this.template(this.model.toJSON());
				this.setElement( $(template).first() );
				this.boostrapModel = null; //clear the bootstrapped model in case it gets updated
				return this;
			}
			
			//...else lazy load model and return deferred object
			var deferred = $.Deferred();
			var self = this;

			this.model.fetch({success: function(model, response, options) {
				var template = self.template(model.toJSON());
				self.setElement( $(template).first() );
				deferred.resolveWith(self, self.model);
			}, error: function(model, response, options) {
                var json = response.responseJSON;
			    var error_msg = 'Error(s) have ocurred while rendering form view';
			    var error_modal = error_msg;
				if ( app.debug && _.has(json, 'errors') ) {
                    if (json.errors.length) {
                        error_msg += ":\n" + json.errors.join("\n");
                        error_modal = json.errors.join('<br/><br/>');
                    }
                    console.log(error_msg);
				}
                Utils.showModalWarning('Error', error_modal);
                deferred.resolveWith(self, {});
			}});

			return deferred.promise();
		},

		formCancel: function(event) {
			event.preventDefault();
			var redirect = $('#button-cancel').data('redirect');
			app.Router.navigate(redirect, {trigger: true});
			return false;
		},
		
		formDelete: function(event) {
			event.preventDefault();
			var $btn = $('#button-delete');
			var titleField = $btn.data('titleField');
			var title = this.model.get(titleField) || '';
			var message = 'Delete "' + title + '"?';
			var self = this;

			Utils.showModalConfirm('Confirm', message, function() {
				self.model.destroy({ 
					wait: true, 
					success: function(model, response) {
						var redirect = $btn.data('redirect');
						Utils.showModalDialog('Message', '"' + title + '" has deleted successfully', 
							function() {
								app.Router.navigate(redirect, {trigger: true});
							}
						);
					}, 
					error: function(model, response) {
						Utils.showModalWarning('Error', '"' + title + '" could not be deleted', false);
					}
				});
			}, false);

			return false;
		},
		
		formSubmit: function(event) {
			event.preventDefault();
			var self = this;
			var changedFields = {};
			var hiddenFields = {};
			var selectors = {};
			var form_id = '#' + this.form_id;

			//first remove any error messages
			Utils.removeAllFieldErrors(form_id);

			for (var field in this.fields) {
				var is_multiple = _.isUndefined(this.fields[field]['is_multiple']) ? 
									false : 
									this.fields[field]['is_multiple'];
				var selector = this._getSelector(field, is_multiple);
				var $field = $(selector);
				
				if ( $field.is(':hidden') && 
					 $field.attr('type') !== 'hidden' &&
					 $field.hasClass('tinymce') === false) {
					//fields with display:none; EXCEPT hidden fields and 
					//TinyMCE editor do not get validated
					hiddenFields[field] = Utils.getVal($field);
					continue;
				}
				changedFields[field] = Utils.getVal($field);
				selectors[field] = selector;
			}
			
			var errors = this.model.validate(changedFields);
			if ( $.isEmptyObject(errors) === false ) {
				for (var field in selectors) {
					(function(field) {
						var $field = $(selectors[field]);
						if (errors[field]) {
							Utils.showFieldError(selectors[field], errors[field]);
						}

						$field.on('change.formview', function() {
							var obj = {};
							obj[field] = Utils.getVal($field);
							var errs = self.model.validate(obj);
							Utils.removeFieldError($field);
							if ( ! $.isEmptyObject(errs) ) {
								Utils.showFieldError($(this), errs[field]);
								Utils.refreshJqmField($field);
							}
						});
					})(field);
				}
				
				Utils.showModalWarning('Error', 'Errors were found that need correction', false);
				return false;
			}
			
			//passed validation, update model
			this.model.set(changedFields);
			this.trigger('view:update:start');
			
			//add non-validated hidden fields to model
			if ( _.isEmpty(hiddenFields) === false ) {
				this.model.set(hiddenFields);
			}

			this.model.save(null, {
				validate: false,
	            success: function(model, response) {
	            	self.trigger('view:update:end');
	            	Utils.showModalDialog('Message', 'The form has saved successfully.', 
	            		function() {
	            			var redirect = $('#submit-save').data('redirect');
	            			if (redirect.length) {
								app.Router.navigate(redirect, {trigger: true});
							}
	            		}
	            	);   
	            },
	            error: function (model, response) {
	            	self.trigger('view:update:end');
	            	var json = response.responseJSON;
	            	var error_msg = 'Error(s) have occurred while saving the form';
                    var error_modal = error_msg;
                    if (app.debug) {
                        if ( _.has(json, 'errors') && json.errors.length ) {
                            error_msg += ":\n" + json.errors.join("\n");
                            error_modal = json.errors.join('<br/><br/>');
                        }
                        console.log(error_msg);
                    }
                    self.trigger('view:update:end');
	            	Utils.showModalWarning('Error', error_modal);
	            }
	        });
			
			//needed to prevent default submit by form
			//while data saved by ajax
			return false;
		},
		
		remove: function() {
			$('#' + this.form_id).find(':input').off('change.formview');
			Backbone.View.prototype.remove.call(this);
		},
		
		_getSelector: function(field_name, is_multiple) {
			var form_id = '#' + this.form_id;
			return form_id + ' ' + '[name="' + field_name + (is_multiple ? '[]' : '') + '"]';
		}

	});
	
	return AdminFormView;
});