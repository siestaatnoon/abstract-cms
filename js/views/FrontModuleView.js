define([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'classes/Utils',
    'classes/I18n',
    'views/AbstractContentView'
], function(app, $, _, Backbone,  Utils, I18n, AbstractContentView) {
    var FrontModuleView = AbstractContentView.extend({

        boostrapModel: null,

        /**
         * The module containing this collection
         *
         * @type {String}
         */
        module: '',

        /**
         * Storage for fields used in validation events
         *
         * @type {Object}
         */
        selectors: {},

        events: {
            'click #submit-save' : 'formSubmit',
            'click #button-cancel' : 'formCancel'
        },

        initialize: function(options) {
            var options = options || {};
            this.module = options.module || '';
            this.boostrapModel = options.bootstrapModel || null;
            this.isList = false;
            AbstractContentView.prototype.initialize.call(this, options);
        },


        formCancel: function(e) {
            e.preventDefault();
            var $btn = $(e.target);
            var redirect = $btn.data('redirect');
            var fragment = $btn.data('fragment');

            if (fragment) {
                app.Router.navigate(fragment, {trigger: true});
            } else if (redirect) {
                window.location = redirect;
            }

            return false;
        },


        formSubmit: function(e) {
            if (this.isList) {
                return false;
            }
            e.preventDefault();
            var formId = '#' + (this.viewData.form_id ? this.viewData.form_id : 'form');
            if ( $(formId).length === 0 ) {
                var error = I18n.t('error.api.parameter', 'form_id');
                if (app.debug) {
                    console.log(error);
                }
                this.trigger('form:submit:error', $(formId).get(0), [error]);
                return false;
            }

            this.trigger('form:submit:start', $(formId).get(0) );
            var fields = this.viewData.fields ? this.viewData.fields : {};
            var self = this;
            var changedFields = {};
            var hiddenFields = {};
            this._resetSelectors();

            //first remove any error messages in form
            this.trigger('form:validate:hideall', $(formId).get(0) );

            for (var field in fields) {
                var isMultiple = _.isUndefined(fields[field]['is_multiple']) ?
                    false :
                    fields[field]['is_multiple'];
                var selector = this._getSelector(field, isMultiple);
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
                this.selectors[field] = selector;
            }

            var errors = this.model.validate(changedFields);

            // If there are any form errors, this will add an
            // event for ALL fields to validate upon changing the
            // field value
            if ( $.isEmptyObject(errors) === false ) {
                for (var field in this.selectors) {
                    (function(field) {
                        var $field = $(self.selectors[field]);
                        if (errors[field]) {
                            self.trigger('form:validate:show', $field.get(0), errors[field]);
                        }

                        $field.on('change', function() {
                            var obj = {};
                            obj[field] = Utils.getVal($field);
                            var errs = self.model.validate(obj);
                            self.trigger('form:validate:hide', $field.get(0) );

                            if ( ! $.isEmptyObject(errs) ) {
                                self.trigger('form:validate:show', $field.get(0), errs[field]);
                                if (app.AppView.useJqm) {
                                    Utils.refreshJqmField($field);
                                }
                            }
                        });
                    })(field);
                }

                var error = I18n.t('message.errors.found');
                self.trigger('form:submit:error', $(formId).get(0), [error]);
                if (app.debug) {
                    console.log(error);
                }
                return false;
            }

            //passed validation, update model
            this.trigger('form:validate:success', $(formId).get(0) );
            this.model.set(changedFields);

            //add non-validated hidden fields to model
            if ( _.isEmpty(hiddenFields) === false ) {
                this.model.set(hiddenFields);
            }

            this.model.save(null, {
                validate: false,
                success: function(model, response, options) {
                    if (response.model) {
                        self.model.set(response.model);
                    }
                    self.trigger('form:submit:success', $(formId).get(0), response);
                    if (app.debug) {
                        console.log( I18n.t('message.form.saved') );
                    }
                },
                error: function (model, response, options) {
                    var resp = Utils.parseJqXHR(response);
                    var errors = resp.errors.length ? resp.errors : (resp.response.length ? [resp.response] : []);
                    if (errors.length === 0) {
                        errors = [ I18n.t('error.general.unknown', 'FrontModuleView.formSubmit()') ];
                    }
                    if (app.debug) {
                        console.log( errors.join("\n") );
                    }
                    self.trigger('form:submit:error', $(formId).get(0), errors);
                }
            });

            //needed to prevent default submit by form
            //while data saved by ajax
            return false;
        },
        

        getTemplate: function() {
            return AbstractContentView.prototype.getTemplate.call(this);
        },


        remove: function() {
            this._resetSelectors();
            AbstractContentView.prototype.remove.call(this);
        },


        render: function() {
            this.trigger('view:update:start');

            //first check if model bootstrapped in initial API data call
            if (this.boostrapModel) {
                //this.model.set(this.boostrapModel);
                this.viewData = _.extend(this.viewData, this.model.toJSON() );
                this.setEl();
                this.boostrapModel = null; //clear the bootstrapped model in case it gets updated
                this.trigger('view:update:end');
                return this;
            }

            var self = this;
            this.deferred = $.Deferred();

            // sets the ID field of the generic model for API url call
            //
            // TODO: set model.id instead?
            //
            this.model.set(this.model.idAttribute, this.params);
            this.model.fetch({
                reset: true,
                success: function(model, response, options) {
                    self.deferred.resolve(response);
                    self.trigger('view:update:end');
                },
                error: function(model, response, options) {
                    var resp = Utils.parseJqXHR(response);
                    var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
                    if (error.length === 0) {
                        error = I18n.t('error.general.unknown', 'FrontModuleView.render()');
                    }
                    if (app.debug) {
                        console.log(error);
                    }
                    self.deferred.resolve(response);
                    self.trigger('view:update:end');
                }
            });

            return AbstractContentView.prototype.render.call(this);
        },


        _getSelector: function(fieldName, isMultiple) {
            var formId = '#' + (this.viewData.form_id ? this.viewData.form_id : 'form');
            return formId + ' ' + '[name="' + fieldName + (isMultiple ? '[]' : '') + '"]';
        },

        _resetSelectors: function() {
            for (var field in this.selectors) {
                $(this.selectors[field]).off('change');
            }
            this.selectors = {};
        }
    });

    return FrontModuleView;
});
