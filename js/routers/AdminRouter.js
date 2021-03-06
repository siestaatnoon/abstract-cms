define([
	'config',
	'jquery',
	'underscore',
	'backbone',
	'classes/admin/AdminModuleLoader',
	'classes/FormValidator',
	'classes/PageLoader',
	'classes/Utils',
	'models/AdminModel',
	'collections/AdminCollection',
	'views/AdminAuthView',
	'views/AdminPageView',
	'views/AdminFormView',
	'views/AdminListView',
	'views/errors/AdminErrorView',
    'routers/AbstractRouter'
], function(app, $, _, Backbone, AdminModuleLoader, FormValidator, PageLoader, Utils, AdminModel,
	AdminCollection, AdminAuthView, AdminPageView, AdminFormView, AdminListView, AdminErrorView, AbstractRouter) {

	/**
	 * Extends AbstractRouter routing all URLs of the admin (CMS) pages.
	 *
	 * @exports routers/AdminRouter
	 * @requires config
	 * @requires jQuery
	 * @requires Underscore
	 * @requires Backbone
	 * @requires classes/admin/AdminModuleLoader
	 * @requires classes/FormValidator
	 * @requires classes/PageLoader
	 * @requires classes/Utils
	 * @requires models/AdminModel
	 * @requires collections/AdminCollection
	 * @requires views/AdminAuthView
	 * @requires views/AdminPageView
	 * @requires views/AdminFormView
	 * @requires views/AdminListView
     * @requires views/errors/AdminErrorView
     * @requires routers/AbstractRouter
	 * @constructor
	 * @augments AbstractRouter
	 */
	var AdminRouter = AbstractRouter.extend({
	/** @lends routers/AdminRouter.prototype **/

		/**
		 * Route fragments used in the admin CMS pages.
		 *
		 * @type {Object}
		 */
		routes: {
			'admin(/)'								: 'home',
			'admin/home'							: 'home',
			'admin/401' 							: 'error401',
			'admin/403' 							: 'error403',
			'admin/404' 							: 'error404',
			'admin/authenticate/logout'				: 'invalidate',
			'admin/authenticate/:task'				: 'authenticate',
			'admin/docs/:slug'						: 'pageRoute',
			'admin/:module/:task(/*params)'		    : 'moduleRoute',
			'*slug' 								: 'error404'
		},

		/**
		 * Initializes this URL router.
		 *
		 * @param {Object} options - Router options (Backbone)
		 */
		initialize: function(options) {

		/*

	[-X-]	1. AppView initialized here, loads own template, menu/header views
	[DONE]	2. LoginParser, AdminTaskParser to use parent class?
	[-X-]	3. LoginView to use AppView to load view
	[DONE] 	4. AdminCollection "smart" paginated fetch
	[-X-]	5. RelationalModel integration to AdminModel
	[DONE]	6. (Abstract)Validation class
	[DONE]	7. Effects for page transitions
	[DONE] 	8. Handler for regular pages
	[DONE]	9. AdminTaskParser should only load the proper template instead of all at once
	[DONE] 	10. Redo RequireJS defines, test out optimizer (r.js)
	[DONE]	11. AdminListView loads CSS multiple times
	[DONE]	12. Reduce dependencies in Class subclasses
	[DONE]	13. listenTo's for Views to update from Model updates (Delete, Cancel)
	[DONE]	14. Polling for session activity, auto logout
	[DONE]	15. AJAX fail functions to show errors
			TODO: 16. Better debugging output in general
	[DONE]  17. Start DOC style comments
			TODO: 18. Frontend view template (including noscript view)
	[-X-]	19. Authentication moved to AdminRouter.initialize() [HERE]
			TODO: 20. Optimize CSS
	[DONE]	21. Optimizer for module + fix $ Error when using optimized JS file
	[DONE]	22. Close off CSS, JS directories to snoopers
	[DONE]	23. Restrict API calls to this domain
	[DONE]	24. Validator for login page
	[DONE]	25. Learn Node for optimizing, Docs
	[DONE]	26. Reduce dependencies in modules and main admin.js
			TODO: 27. Find new way to bootstrap app template
	[DONE]	28. Refactor for CMS/frontend
	[DONE]	29. Make Login/Logout/Lost password separate module
	[DONE]	30. ** FIX BACK/FORWARD BUTTONS NOT UPDATING PAGE **
			TODO: 31. Optimize gc in close/remove, run Chrome tests to compare!
			TODO: 32. ALL AJAX CALLS check for errors array in callback and pass to deferred
	[-X-]	33. Login template passed in to main require() call
			TODO: 34. 'use strict';
	[-X-]	35. Add observer to SessionPoller
			TODO: 36. Get correct params of AJAX.fail for errors
	[DONE]	37. Proper app.appCache for gc in AdminRouter.reset()
			TODO: 38. SEO "escape fragment"

			JSDOC3 Usage

			1. Open cmd prompt to directory C:\Users\JOHNNY\Documents\xampp\htdocs\backbone\dev\js
			2. Run: node_modules\.bin\jsdoc admin.js -c node_modules\conf.json -r

			RequireJS Optimizer

			1. Open cmd prompt to directory C:\Users\JOHNNY\Documents\xampp\htdocs\backbone\dev
			2. Run: node r.js -o build-admin.js

		*/

			app.ModuleLoader = new AdminModuleLoader();
			app.PageLoader = new PageLoader();
            app.PageLoader.init({isCms: true});
            AbstractRouter.prototype.initialize.call(this, options);
		},

		/**
		 * Checks for previous authentication and routes to admin home page if
		 * a user is logged in. Otherwise, loads the login page.
		 *
		 * @param {String} module - The module name
		 * @param {String} task - [list|add|edit] or other module defined page
		 * @param {String} params - Parameter passed in to page (e.g. row id)
		 */
		authenticate: function(page) {
			if ( app.Auth.isAuthenticated() ) {
				//this.navigate('admin/home', {trigger: true, replace: true});
				//return false;
			}

			var module = 'authenticate';
			var view = {};

			if ( _.isUndefined(app.appCache[module]) || _.isUndefined(app.appCache[module][page]) ) {
				view = new AdminAuthView({page: page});
				app.appCache[module] = app.appCache[module] || {};
				app.appCache[module][page] = view;
			} else {
			//Load cached auth page
				view = app.appCache[module][page];
				view.setPage(page);
			}

            this.clearError();
			view.render();
		},

		/**
		 * Routes to the admin home page.
		 */
		home: function() {
			if ( ! app.Auth.isAuthenticated() ) {
				this.reset();
				return false;
			}

            this.clearError();
			this.pageRoute('home');
		},

		/**
		 * Ends a user session in the CMS.
		 */
		invalidate: function() {
			var self = this;
			app.Auth.invalidate().done(function(is_invalidated) {
				if (is_invalidated) {
					// resetting App template already defined in admin.js
					//
					//self.reset();
				}
			});
		},

        /**
         * Handles routing to a module list page and add/edit form pages.
         *
         * @param {String} module - The module name
         * @param {String} task - [list|add|edit] or other module defined page
         * @param {String} params - Parameter passed in to page (e.g. row id)
         */
        moduleRoute: function(module, task, params) {
            if ( ! app.Auth.isAuthenticated()) {
                this.reset();
                return false;
            }

            Utils.setCrsfToken();
            app.AppView.render();
            app.ModuleLoader.reset();
            app.ModuleLoader.setModule({
                module	: module,
                task	: task,
                params	: params
            });

            this.clearError();
            if (_.isUndefined(app.appCache[module]) ||
                _.isUndefined(app.appCache[module][task]) ||
                app.appCache[module][task]['params'] !== params) {

                $.mobile.loading('show');

                app.ModuleLoader.loadData().done(
                    function(data) {
                        var model = {};
                        var collection = {};
                        var view = {};

                        if (app.ModuleLoader.isGetTask()) {
                            collection = new AdminCollection({}, {
                                idAttribute: data.pk_field,
                                url: data.collection_url
                            });
                            model = new AdminModel({}, {
                                collection: collection,
                                idAttribute: data.pk_field,
                                url: data.model_url
                            });
                            view = new AdminListView({
                                collection: collection,
                                module: module,
                                template: data.template,
                                altListTmpl: data.alt_list_tmpl || '',
                                altListTmplData: data.alt_list_tmpl_data || {},
                                blocks: data.blocks || null,
                                scripts: data.scripts || {}
                            });
                        } else {
                            app.Validator.reset();
                            model = new AdminModel({}, {
                                idAttribute: data.pk_field,
                                url: data.model_url,
                                fields: data.fields
                            });
                            view = new AdminFormView({
                                model: model,
                                bootstrapModel: data.model,
                                template: data.template,
                                fields: data.fields,
                                scripts: data.scripts || {},
                                form_id: data.form_id,
                                blocks: data.blocks || null
                            });
                        }

                        //Cache module view then load it
                        if ( _.isUndefined(data.no_cache) || ! data.no_cache ) {
                            app.appCache[module] = app.appCache[module] || {};
                            app.appCache[module][task] 			 = {};
                            app.appCache[module][task]['view'] 	 = view;
                            app.appCache[module][task]['params'] = params;
                        }
                        app.AppView.gotoContentView(view);
                    }
                );
            } else {
                //Load cached parser/model/collection/view
                var view = app.appCache[module][task]['view'];
                app.AppView.gotoContentView(view);
            }
        },

		/**
		 * Handles routing to a non-module page.
		 *
		 * @param {String} slug - The page indentifier slug
		 */
		pageRoute: function(slug) {
			if ( ! app.Auth.isAuthenticated()) {
				this.reset();
				return false;
			}

			var module = 'page';
			var view = {};

			Utils.setCrsfToken();
			app.AppView.render();

			if ( _.isUndefined(app.appCache[module]) || _.isUndefined(app.appCache[module][slug]) ) {
				view = new AdminPageView({
					slug: slug,
					pageLoader: app.PageLoader
				});
				app.appCache[module] = app.appCache[module] || {};
				app.appCache[module][slug] = view;
			} else {
				view = app.appCache[module][slug];
			}

            this.clearError();
			app.AppView.gotoContentView(view);
		},

		/**
		 * Resets the CMS page caching and main template view.
		 */
		reset: function() {
			app.appCache = Utils.gcReadyObject(app.appCache);
			app.appCache = {};
			app.AppView.remove();
			app.Router.navigate('admin/authenticate/login', {trigger: true});
		},

		/**
		 * Displays the error page view.
		 *
		 * @param {int} errorCode - The HTTP error code (e.g. 401, 403, 404)
		 */
		showError: function(errorCode) {
			if ( ! app.Auth.isAuthenticated() ) {
				this.navigate('admin/authenticate/login', {trigger: true, replace: true});
				return false;
			}

            this.clearError();
            this.errorView = new AdminErrorView({error: errorCode});
            this.errorView.render();
		}

	});

	return AdminRouter;
});
