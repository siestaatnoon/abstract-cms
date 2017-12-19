define([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'classes/Utils'
], function(app, $, _, Backbone, Utils) {
    var AbstractContentView = Backbone.View.extend({

        id: app.pageDynContentId.replace('#', ''),

        blocks: {},

        deferred: {},

        headTags: {},

        isNewPage: false,

        isList: false,

        module: '',

        viewData: {},

        newTpl: {
            template: '',
            blocks: [],
            headTags: {},
            scripts: {},
            tpl_params: '',
            useJqm: false
        },

        params: '',

        scripts: {},

        task: '',

        events: {

        },

        initialize: function(options) {
            var tpl = options.template || '';
            this.template = _.template(tpl);
            this.module = options.module || '',
            this.task = options.task || '',
            this.collection = options.collection || null;
            this.model = options.model || null;
            this.blocks = options.blocks || this.blocks;
            this.headTags = options.headTags || this.headTags;
            this.scripts = options.scripts || this.scripts;
            if (options.viewData) {
                this.viewData = options.viewData;
            }
            if (options.newTpl) {
                this.newTpl = options.newTpl;
                this.isNewPage = true;
            }
            app.Validator.reset();
        },

        getTemplate: function() {
            return this.template(this.viewData);
        },

        remove: function() {
            this.undelegateEvents();
            this.$el.removeData().off();
            this.isNewPage = false;
            Backbone.View.prototype.remove.call(this);
        },

        render: function() {
            if (this.deferred.promise) {
                var self = this;
                this.deferred.done(function(response) {
                    var data = typeof response === 'string' ? JSON.parse(response) : response;
                    if (data.newTpl) {
                        self.newTpl = data.newTpl;
                        self.isNewPage = true;
                    }

                    if (data.template) {
                        self.template = _.template(data.template);
                    }

                    if (self.isList) {
                        if (data.items) {
                            //self.collection.set(data.items);
                            self.viewData = _.extend(self.viewData, {items: self.collection.toJSON()});
                            self.viewData['state'] = self.collection.state;
                            delete data.items;
                        } else if (app.debug) {
                            console.log('AbstractContentView.render: collection data unavailable');
                        }
                    } else if (self.model) {
                        if (data.model) {
                            self.model.set(data.model);
                            self.viewData = _.extend(self.viewData, self.model.toJSON());
                            delete data.model;
                        } else if (app.debug) {
                            console.log('AbstractContentView.render: model data unavailable');
                        }
                    }

                    // extraneous variables can be added to template
                    // from "data" parameter from AJAX call
                    self.viewData = _.extend(self.viewData, data.data || {});
                    self.blocks = data.blocks || self.blocks;
                    self.headTags = data.headTags || self.headTags;
                    self.scripts = data.scripts || self.scripts;
                    self.setEl();

                    //NOTE: subclasses must define a resolve for deferred
                    //
                    //self.deferred.resolve(data);

                    self.deferred = {};
                });

                return this.deferred.promise();
            }

            this.setEl();
            return this;
        },

        setEl: function() {
            var template = this.getTemplate();
            if (this.$el.length) {
                this.$el.empty().append(template);
                return;
            }

            var $el = $(template);
            var id = '#' + this.id;
            if ( $el.attr('id') === this.id ) {
                this.setElement($el[0]);
            } else if ( $(id, $el).length ) {
                this.setElement( $(id, $el)[0] );
            } else {
                this.tagName = 'div';
            }
            this.delegateEvents();
            this.$el.append(template);
        },

        setParams: function(params) {
            params = params ? params.split('/') : [''] ;
            this.params = params.length === 1 ? params[0] : params;
        }
    });

    return AbstractContentView;
});
