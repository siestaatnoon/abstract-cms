define([
	'config',
	'jquery',
	'underscore', 
	'backbone',
	'classes/ScriptLoader',
	'classes/Utils',
    'classes/I18n'
], function(app, $, _, Backbone, ScriptLoader, Utils, I18n) {
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
			
			if ( _.isEmpty(fields) ) {
                console.log( I18n.t('error.api.missing', ['FormView.initialize:', 'fields']) );
			}
			
			if ( tpl.length == 0 ) {
                console.log( I18n.t('error.api.missing', ['FormView.initialize:', 'template']) );
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
			    var resp = Utils.parseJqXHR(response);
			    var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
			    if (error.length === 0) {
                    error = I18n.t('error.general.unknown', 'AdminFormView.render()');
			    }
                Utils.showModalWarning( I18n.t('error'), error);
				if (app.debug) {
				    console.log( error.replace('<br/>', "\n") );
                }
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
			var message = I18n.t('delete') + ' "' + title + '"?';
			var self = this;

			Utils.showModalConfirm( I18n.t('confirm'), message, function() {
				self.model.destroy({ 
					wait: true, 
					success: function(model, response) {
						var redirect = $btn.data('redirect');
						var message = I18n.t('confirm.deleted', title);
						Utils.showModalDialog( I18n.t('message'), message,
							function() {
								app.Router.navigate(redirect, {trigger: true});
							}
						);
					}, 
					error: function(model, response) {
                        var resp = Utils.parseJqXHR(response);
                        var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
                        if (error.length === 0) {
                            error = I18n.t('error.general.unknown', 'AdminFormView.formDelete()');
                        }
                        Utils.showModalWarning( I18n.t('error'), error);
                        if (app.debug) {
                            console.log( error.replace('<br/>', "\n") );
                        }
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
				
				Utils.showModalWarning( I18n.t('error'), I18n.t('message.errors.found'), false);
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
	            	Utils.showModalDialog( I18n.t('message'), I18n.t('message.form.saved'),
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
                    var resp = Utils.parseJqXHR(response);
                    var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
                    if (error.length === 0) {
                        error = I18n.t('error.general.unknown', 'AdminFormView.formSubmit()');
                    }
                    if (app.debug) {
                        console.log( error.replace('<br/>', "\n") );
                    }
                    self.trigger('view:update:end');
	            	Utils.showModalWarning( I18n.t('error'), error);
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