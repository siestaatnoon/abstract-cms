require.config({ 
	//baseURL: 				app.docRoot, //defaults to path of this file
	urlArgs: 				"_=" +  (new Date()).getTime(),
	waitSeconds: 			60,
	
	paths: {
		'jquery': 				'lib/jquery.min', 				//1.12.4
		'jquerymobile': 		'lib/jquery.mobile.min',		//1.4.5
		'underscore':			'lib/underscore.min',			//1.8.3
		'backbone': 			'lib/backbone.min',				//1.3.3
		'backbone.paginator': 	'lib/ext/backbone.paginator',	//2.0.6
		'backgrid': 			'lib/backgrid.min',				//0.3.8
		'abstract.paginator': 	'lib/ext/abstract.paginator',
		'backgrid.textcell': 	'lib/ext/backgrid.textcell'
	},
	
	shim: {
		'config': {
			deps: ['jquery'],
			exports: 'app'
		},
		
		'jquerymobile': ['config', 'jquery'],
		
		'underscore': {
			deps: ['jquery'],
			exports: '_'
		},

        'classes/I18n': ['config', 'jquery', 'underscore'],
		
		'backbone.paginator': ['underscore', 'backbone'],
		
		'backgrid': {
			deps: ['jquery', 'underscore', 'backbone'],
			exports: 'Backgrid'
		},
		
		'abstract.paginator': [
			'underscore', 
			'backbone',
			'backgrid',
			'backbone.paginator',
            'classes/I18n'
		],
					
		'backgrid.textcell': 		[
			'underscore', 
			'backgrid'
		],
		
		'classes/Class': 					['config', 'jquery', 'underscore'],
		'classes/Utils': 					['config', 'jquery', 'underscore', 'classes/I18n'],
		'classes/FormValidator': 			['config', 'jquery', 'underscore', 'classes/Class', 'classes/I18n'],
		'classes/ScriptLoader': 			['config', 'jquery', 'underscore', 'classes/Class', 'classes/I18n'],
        'classes/admin/SessionPoller': 		['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils', 'classes/I18n'],
		'classes/PageLoader': 				['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils', 'classes/I18n'],
		'classes/ModuleLoader': 			['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils', 'classes/I18n'],
		
		'classes/admin/AdminAuth': 			[
			'config', 
			'jquery', 
			'underscore', 
			'classes/Class', 
			'classes/admin/SessionPoller',
			'classes/Utils',
            'classes/I18n'
		],
		
		'classes/admin/AdminModuleLoader': 	['config', 'jquery', 'underscore', 'classes/Class', 'classes/ModuleLoader'],

		'models/AdminModel': 			[
			'config', 
			'underscore', 
			'backbone',
            'models/AbstractModel',
			'classes/admin/AdminModuleLoader',
			'classes/FormValidator'
		],
		
		'collections/AdminCollection': 	[
			'config', 
			'underscore', 
			'backbone',
			'backbone.paginator',
			'classes/admin/AdminModuleLoader',
			'models/AdminModel',
            'collections/AbstractCollection'
		],
		
		'views/AdminListUpdaterView': 	[
			'config', 
			'jquery',
			'underscore',
			'backbone', 
			'classes/Utils',
            'classes/I18n'
		],
		
		'views/AdminAuthView': 	[
			'config', 
			'jquery',
			'underscore',
			'backbone',
			'classes/Utils',
            'classes/I18n',
			'classes/admin/AdminAuth'
		],
		
		'views/AdminPageView': ['config', 'jquery', 'underscore', 'backbone'],
		
		'views/AdminFormView': 	[
			'config', 
			'jquery',
			'underscore', 
			'backbone',
			'classes/admin/AdminModuleLoader',
			'classes/FormValidator',
			'models/AdminModel',
            'classes/Utils',
            'classes/I18n'
		],
		
		'views/AdminListView': 	[
			'config', 
			'jquery',
			'underscore',
			'backbone',
			'backgrid',
			'backbone.paginator',
			'abstract.paginator',
			'backgrid.textcell',
			'classes/admin/AdminModuleLoader',
            'classes/Utils',
            'classes/I18n',
			'collections/AdminCollection',
			'views/AdminListUpdaterView'
		],
		
		'views/errors/AdminErrorView': 	[
			'config', 
			'jquery',
			'underscore',
			'backbone',
			'backgrid',
            'classes/I18n'
		],
		
		'views/AdminTplView': 	[
			'config',
			'jquery',
			'underscore',
			'backbone',
			'classes/admin/AdminModuleLoader',
            'classes/ScriptLoader',
            'views/AbstractTplView'
		],
		
		'routers/AdminRouter': 	[
			'views/AdminTplView',
            'routers/AbstractRouter'
		]
	}

});
	
require([
	'config',
	'jquery',
	'jquerymobile',
    'underscore',
    'backbone',
    'classes/admin/AdminAuth',
    'views/AdminTplView',
    'routers/AdminRouter'
  ], function(app, $, jquerymobile, _, Backbone, AdminAuth, AdminTplView, AdminRouter) {

	$(document).on("click", "a[href^='/']", function(e) {
		var href = $(this).attr('href');
		if ( ! e.altKey && ! e.ctrlKey && ! e.metaKey && ! e.shiftKey) {
			e.preventDefault();
			app.Router.navigate(href, {trigger: true});
			return false;
		}
	});
	
	app.AppView = new AdminTplView({}); 
	app.Router = new AdminRouter();
	app.Auth = new AdminAuth({
		destroyCallback: app.Router.reset
	});
		
	$.ajaxSetup({
        cache: false, // don't cache AJAX calls
		statusCode: {
			401: function() {
				app.Router.navigate('admin/401', {trigger: true});
		    },
			403: function() {
		    	app.Router.navigate('admin/403', {trigger: true});
			},
			404: function() {
		    	app.Router.navigate('admin/404', {trigger: true});
			}
		}
	});
		
	//In case any page gets a browser refresh during a session, 
	//this will check if a session is active and restart polling,
	//if needed, before firing up Backbone again. 
	//
	app.Auth.ping(function() {
		Backbone.history.start({
			pushState	: app.pushState,
			root		: app.docRoot,
			hashChange	: app.hashChange
		});
			
		//For some reason, the jQuery popstate event does not fire
		//on all Back/Prev button clicks (bug?), so replacing the Backbone
		//event with native JS version here seems to do the trick.
		//
		if (app.pushState) {
			Backbone.$(window).off('popstate');
			window.onpopstate = function() {	
				Backbone.history.checkUrl();
			};
		}
	});
	
});
