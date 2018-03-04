({	  
	removeCombined: 		true,
    findNestedDependencies: true,
	optimizeCss: 			'standard.keepComments',
	
	/*
	baseUrl: 				'css',
	fileExclusionRegExp: 	/^(r|build)\.js$/,
	dir:					'dist',
	paths: {
		'dev': 		'empty:',
		'plugins': 	'empty:'
	}
	*/
	
	/*
	baseUrl: 				'js',
	mainConfigFile : 		'js/admin.js',
	fileExclusionRegExp: 	/^(r|build)\.js$/,
	dir:					'dist',
	wrapShim: 				true,
	modules: [
		{
			name: 'app',
			exclude: [
				'libs',
				'node_modules'
	        ]

		},
		{
			name: 'libs',
			exclude: [
				'node_modules'
	        ]
		}
	],
	paths: {
		'node_modules': 'empty:',
		'jquery': 		'lib/jquery.min'
	}
	*/
	
	baseUrl: 				'js',
	name: 					'admin',
	fileExclusionRegExp: 	/^(r|build|lib)\.js$/,
	out: 					'dist/admin.js',
	wrapShim: 				true,
	
	paths: {
		'jquery':				'lib/jquery.min', //must include jQuery to prevent Backgrid error
		'jquerymobile': 		'lib/jquery.mobile.min',
		//'bootstrap': 			'lib/bootstrap.min',
		'underscore':			'lib/underscore.min',
		'backbone': 			'lib/backbone.min',
		'backbone.paginator': 	'lib/ext/backbone.paginator',
		'backgrid': 			'lib/backgrid.min',
		'abstract.paginator': 	'lib/ext/abstract.paginator',
		'backgrid.textcell': 	'lib/ext/backgrid.textcell'
	},

	shim: {
		'config': {
			deps: ['jquery'],
			exports: 'app'
		},
		
		'jquerymobile': ['config', 'jquery'],
		
		//'bootstrap': ['jquery'],
		
		'underscore': {
			deps: ['jquery'],
			exports: '_'
		},
		
		'backbone.paginator': ['underscore', 'backbone'],
		
		'backgrid': {
			deps: ['jquery', 'underscore', 'backbone'],
			exports: 'Backgrid'
		},
		
		'abstract.paginator': [
			'underscore', 
			'backbone',
			'backgrid',
			'backbone.paginator'
		],
					
		'backgrid.textcell': 		[
			'underscore', 
			'backgrid'
		],

        'classes/Class': 					['config', 'jquery', 'underscore'],
        'classes/Utils': 					['config', 'jquery', 'underscore'],
        'classes/FormValidator': 			['config', 'jquery', 'underscore', 'classes/Class'],
        'classes/ScriptLoader': 			['config', 'jquery', 'underscore', 'classes/Class'],
        'classes/PageLoader': 				['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils'],
        'classes/admin/SessionPoller': 		['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils'],
        'classes/ModuleLoader': 			['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils'],

        'classes/admin/AdminAuth': 			[
            'config',
            'jquery',
            'underscore',
            'classes/Class',
            'classes/admin/SessionPoller',
            'classes/Utils'
        ],

        'classes/admin/AdminModuleLoader': 	['config', 'jquery', 'underscore', 'classes/Class', 'classes/ModuleLoader'],

        'models/AdminModel': 			[
            'config',
            'underscore',
            'backbone',
            'classes/admin/AdminModuleLoader',
            'classes/FormValidator'
        ],

        'collections/AdminCollection': 	[
            'config',
            'underscore',
            'backbone',
            'backbone.paginator',
            'classes/admin/AdminModuleLoader',
            'models/AdminModel'
        ],

        'views/AdminListUpdaterView': 	[
            'config',
            'jquery',
            'underscore',
            'backbone',
            'classes/Utils'
        ],

        'views/AdminAuthView': 	[
            'config',
            'jquery',
            'underscore',
            'backbone',
            'classes/Utils',
            'classes/admin/AdminAuth'
        ],

        'views/AdminPageView': ['config', 'jquery', 'underscore', 'backbone'],

        'views/AdminFormView': 	[
            'config',
            'jquery',
            'underscore',
            'backbone',
            'classes/admin/AdminModuleLoader',
            'classes/ScriptLoader',
            'classes/FormValidator',
            'models/AdminModel'
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
            'classes/ScriptLoader',
            'collections/AdminCollection',
            'views/AdminListUpdaterView'
        ],

        'views/errors/AdminErrorView': 	[
            'config',
            'jquery',
            'underscore',
            'backbone',
            'backgrid'
        ],

        'views/AdminTplView': 	[
            'config',
            'jquery',
            'underscore',
            'backbone',
            'classes/admin/AdminModuleLoader',
            'views/AbstractTplView'
        ],

        'routers/AdminRouter': 	[
            'views/AdminTplView',
            'routers/AbstractRouter'
        ]
	}
})