define([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'classes/FormValidator',
    'classes/Utils'
], function(app, $, _, Backbone, FormValidator, Utils) {

    /**
     * Extends Backbone.Router routing all URLs of the admin (CMS) pages.
     *
     * @exports routers/AbstractRouter
     * @requires config
     * @requires jQuery
     * @requires Underscore
     * @requires Backbone
     * @requires classes/FormValidator
     * @requires classes/Utils
     * @constructor
     * @augments Backbone.Router
     */
    var AbstractRouter = Backbone.Router.extend({
        /** @lends Backbone.Router.prototype **/

        /**
         * Backbone view for 401, 403 and/or 404 HTTP error pages, defined in subclass.
         *
         * @type {Object}
         */
        errorView: {},

        /**
         * Backbone view for Abstract module pages, defined in subclass.
         *
         * @type {Object}
         */
        moduleView: {},

        /**
         * Route fragments used in the admin CMS pages, defined in subclass.
         *
         * @type {Object}
         */
        routes: {},

        /**
         * Initializes this URL router.
         *
         * @param {Object} options - Router options (Backbone)
         */
        initialize: function(options) {
            app.Validator = new FormValidator({});
        },

        /**
         * Clears an error view, if shown, before loading another view.
         */
        clearError: function() {
            if ( _.isEmpty(this.errorView) === false && this.errorView.hasError) {
                this.errorView.remove();
            }
        },

        /**
         * Handles a route to an Error 401, unauthorized page.
         */
        error401: function() {
            this.showError(401);
        },

        /**
         * Handles a route to an Error 403, forbidden page.
         */
        error403: function() {
            this.showError(403);
        },

        /**
         * Handles a route to an Error 404, page not found.
         */
        error404: function() {
            this.showError(404);
        },

        /**
         * Routes to the admin/frontend home page.
         */
        home: function(params) {
            params = params || '';
            this.clearError();
            this.moduleRoute('home', params);
        },

        /**
         * Called after ModuleLoader completes API call to load module data. Module
         * model, collection and this.moduleView should be initialized by subclass overwrite,
         * then call to this parent function.
         *
         * @param {Object} data - The JSON object returned from API call
         * @param {String} module - The module name from Backbone route, used for caching
         * @param {String} task - The task name from Backbone route, used for caching
         * @param {String} params - The associated parameters from Backbone route, used for caching
         * @return {Boolean} False if no View defined in subclass overwrite, loads View otherwise
         */
        loadModuleView: function(data, module, task, params) {
            if ( _.isEmpty(this.moduleView) ) {
                return false;
            }

            //Cache module view then load it
            if ( _.isUndefined(data.no_cache) || ! data.no_cache ) {
                app.appCache[module] = app.appCache[module] || {};
                app.appCache[module][task] 			 = {};
                app.appCache[module][task]['view'] 	 = this.moduleView;
                app.appCache[module][task]['params'] = params;
            }
            app.AppView.gotoContentView(this.moduleView);
        },

        /**
         * Handles routing to a module list page and add/edit form pages.
         *
         * @param {String} module - The module name
         * @param {String} task - [list|add|edit] or other module defined page
         * @param {String} params - Parameter passed in to page (e.g. row id)
         * @return {Boolean} False if app.ModuleLoader not defined in subclass, loads module data otherwise
         */
        moduleRoute: function(module, task, params) {
            if ( _.isUndefined(app.ModuleLoader) || _.isEmpty(app.ModuleLoader) ) {
                return false;
            }

            app.AppView.render();
            this.clearError();

            if (_.isUndefined(app.appCache[module]) ||
                _.isUndefined(app.appCache[module][task]) ||
                app.appCache[module][task]['params'] !== params) {
                app.ModuleLoader.reset();
                app.ModuleLoader.setModule({
                    module	: module,
                    task	: task,
                    params	: params
                });

                // the following blocks the content view API call
                // until the App template API call is resolved
                var self = this;
                var initViewLoader = function() {
                    app.ModuleLoader.loadData().done(
                        function (data) {
                            self.loadModuleView(data, module, task, params);
                        }
                    );
                }
                if ( app.AppView.onInit(initViewLoader) ) {
                // if App API call not resolved, will execute
                // above function otherwise, it runs now
                    initViewLoader();
                }
            } else {
                //Load cached parser/model/collection/view
                var view = app.appCache[module][task]['view'];
                app.AppView.gotoContentView(view);
            }
        },

        /**
         * Overrides the Backbone.Router.navigate function to accept an href attribute
         * clicked from a link and parse it as a fragment used for routing in Backbone.
         *
         * @param {String} models - The href attribute or Backbone fragment
         * @param {Object} options - options Backbone.Router.navigate (Backbone)
         * @return {Backbone} The Backbone reference
         */
        navigate: function(fragment, options) {
            //replace hashbang for backwards compatibility
            fragment = fragment.replace("#!", "");

            //remove document root from fragment
            if (app.docRoot !== '/') {
                fragment = fragment.replace(app.docRoot, "");
            }

            //remove leading slash
            if ( fragment.substr(0, 1) === '/') {
                fragment = fragment.substr(1);
            }

            return Backbone.Router.prototype.navigate.call(this, fragment, options);
        },

        /**
         * Displays the error page view.
         *
         * @param {int} errorCode - The HTTP error code (e.g. 401, 403, 404)
         */
        showError: function(errorCode) {
            if ( _.isEmpty(this.errorView) ) {
                return false;
            }
            this.clearError();
            this.errorView.render();
        }

    });

    return AbstractRouter;
});
