define([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'classes/front/FrontModuleLoader',
    'classes/Utils',
    'models/AbstractModel',
    'collections/FrontCollection',
    'views/FrontModuleView',
    'views/FrontListView',
    'routers/AbstractRouter'
], function(app, $, _, Backbone, FrontModuleLoader, Utils, AbstractModel, FrontCollection, FrontModuleView,
    FrontListView, AbstractRouter) {

    /**
     * Extends Backbone.Router routing all URLs of the admin (CMS) pages.
     *
     * @exports routers/FrontRouter
     * @requires config
     * @requires jQuery
     * @requires Underscore
     * @requires Backbone
     * @requires classes/front/FrontModuleLoader
     * @requires classes/Utils
     * @requires models/AbstractModel
     * @requires collections/FrontCollection
     * @requires views/FrontModuleView
     * @requires views/FrontListView
     * @requires routers/AbstractRouter
     * @constructor
     * @augments AbstractRouter
     */
    var FrontRouter = AbstractRouter.extend({
    /** @lends AbstractRouter.prototype **/

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
         * Route fragments used in the admin CMS pages.
         *
         * @type {Object}
         */
        routes: {
            '(/)'							: 'home',
            'home(/*params)'				: 'home',
            '404' 							: 'error404',
            ':uri(/:task)(/*params)'		: 'moduleRoute'
        },

        /**
         * Object holding uri segments mapped to modules.
         *
         * @type {Object}
         */
        uriMap: {},

        /**
         * Initializes this URL router.
         *
         * @param {Object} options - Router options (Backbone)
         */
        initialize: function(options) {
            app.ModuleLoader = new FrontModuleLoader({
                apiRoot: app.frontApiRoot,
                dataRoot: app.frontDataRoot
            });
            AbstractRouter.prototype.initialize.call(this, options);
        },

        /**
         * Handles a route to an Error 404, page not found.
         */
        error404: function() {
            AbstractRouter.prototype.error404.call(this, 404);
        },

        /**
         * Called after ModuleLoader completes API call to load module data. Module
         * model, collection and this.moduleView should be initialized by subclass overwrite,
         * then call to this parent function.
         *
         * @param {Object} data - The JSON object returned from API call
         * @param {String} uri - The primary segment uri from Backbone route, mapped to module from API call
         * @param {String} task - The task name from Backbone route, used for caching
         * @param {String} params - The associated parameters from Backbone route, used for caching
         */
        loadModuleView: function(data, uri, task, params) {
            app.Validator.reset();
            var module = data.module || uri;
            task = data.task || task;
            var options = { module: module };

            if (uri !== module) {
                if ( this.uriMap.hasOwnProperty(uri) === false ) {
                    this.uriMap[uri] = {};
                    this.uriMap[uri][module] = {};
                    this.uriMap[uri][module][task] = params || '';
                } else if ( this.uriMap[uri][module].hasOwnProperty(task) === false ) {
                    this.uriMap[uri][module][task] = params || '';
                }
            }

            var viewData = data.data || {};
            options['template'] = data.template;
            options['module'] = module;
            options['task'] = task;
            options['blocks'] = data.blocks || [];
            options['headTags'] = data.headTags || {};
            options['scripts'] = data.scripts || {};
            options['viewData'] = viewData;
            options['validation'] = data.validation || {};
            options['useJqm'] = data.useJqm || false;
            if (data.newTpl) {
                options['newTpl'] = data.newTpl;
            }

            if (data.collection_url) {
                options['collection'] = new FrontCollection({}, {
                    idAttribute: data.idAttribute,
                    url: data.collection_url
                });
                this.moduleView = new FrontListView(options);
            } else {
                var bootstrapModel = data.bootstrapModel || {};
                options['model'] = new AbstractModel(bootstrapModel, {
                    idAttribute: data.idAttribute,
                    urlRoot: data.model_url,
                    fields: viewData.fields || {}
                });
                if (bootstrapModel) {
                    options['bootstrapModel'] = bootstrapModel;
                }
                this.moduleView = new FrontModuleView(options);
            }

            AbstractRouter.prototype.loadModuleView.call(this, data, module, task, params);
        },

        /**
         * Handles routing to a module list and item detail pages.
         *
         * @param {String} module - The module name
         * @param {String} task - [list|add|edit] or other module defined page
         * @param {String} params - Parameter passed in to page (e.g. row id)
         */
        moduleRoute: function(uri, task, params) {
            task = task || '';

            if ( this.uriMap.hasOwnProperty(uri) ) {
                var module = Object.keys(this.uriMap[uri])[0];
                var _task = task || 'get';
                if ( this.uriMap[uri][module].hasOwnProperty(_task) && app.appCache[module] && app.appCache[module][_task] ) {
                    var view = app.appCache[module][_task]['view'];
                    app.AppView.render();
                    this.clearError();
                    view.setParams(params || uri);
                    app.AppView.gotoContentView(view);
                    return;
                }
            }
            AbstractRouter.prototype.moduleRoute.call(this, uri, task, params);
        },

        /**
         * Handles routing to a Page module page.
         *
         * @param {String} slug - The page indentifier slug
         */
        pageRoute: function(slug) {
            AbstractRouter.prototype.pageRoute.call(this, slug);
        },

        /**
         * Since 404 errors not handled in Backbone for front pages (returned in template
         * instead), called as a regular page route.
         *
         * @param {int} errorCode - The HTTP error code (e.g. 401, 403, 404)
         */
        showError: function(errorCode) {
            this.moduleRoute(errorCode);
        }

    });

    return FrontRouter;
});
