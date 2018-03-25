define([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'classes/Utils',
    'views/AbstractContentView'
], function(app, $, _, Backbone,  Utils, AbstractContentView) {
    var FrontListView = AbstractContentView.extend({

        /**
         * The module containing this collection
         *
         * @type {String}
         */
        module: '',

        events: {
            "click a.pager-control": "changePage",
            "click a.pager-selector": "changePage"
        },

        initialize: function(options) {
            var options = options || {};
            this.module = options.module || '';
            this.isList = true;
            AbstractContentView.prototype.initialize.call(this, options);
        },

        changePage: function(e) {
            e.preventDefault();
            var $target = $(e.target);

            if ( $target.hasClass('disabled') === false ) {
                if ( $target.hasClass('page-first') ) {
                    this.collection.getFirstPage({no_fetch: true});
                } else if ( $target.hasClass('page-prev') ) {
                    this.collection.getPreviousPage({no_fetch: true});
                } else if ( $target.hasClass('page-next') ) {
                    this.collection.getNextPage({no_fetch: true});
                } else if ( $target.hasClass('page-last') ) {
                    this.collection.getLastPage({no_fetch: true});
                } else if ( $target.hasClass('pager-selector') ) {
                    var page = parseInt( $target.attr('data-page') );
                    if ( isNaN(page) === false && this.collection.state.currentPage !== page ) {
                        this.collection.getPage(page, {no_fetch: true});
                    } else {
                        return this;
                    }
                }

                // sets the loading spinner while items load
                app.AppView.loading('show');
                app.AppView.trigger('content:update:start');
                this.render();
                this.deferred.done(function(response) {
                    app.AppView.trigger('content:update:end');
                    app.AppView.loading('hide');
                });
            }
            return this;
        },

        getTemplate: function() {
            return AbstractContentView.prototype.getTemplate.call(this);
        },


        render: function() {
            var self = this;
            this.deferred = $.Deferred();
            this.trigger('view:update:start');

            this.collection.fetch({
                reset: true,
                success: function(collection, response, options) {
                    self.deferred.resolve(response);
                    self.trigger('view:update:end');
                },
                error: function(collection, response, options) {
                    var json = response.responseJSON;
                    if (app.debug) {
                        var errors = json.errors ? json.errors.join("\n") : 'An unknown error has occurred';
                        console.log("FrontListView.render: error(s) have occurred in AJAX call:\n" + errors);
                    }
                    self.deferred.resolve(response);
                    self.trigger('view:update:end');
                }
            });

            return AbstractContentView.prototype.render.call(this);
        }

    });

    return FrontListView;
});
