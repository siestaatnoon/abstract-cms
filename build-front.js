({
    removeCombined: 		true,
    findNestedDependencies: true,
    optimizeCss: 			'standard.keepComments',
    baseUrl: 				'js',
    name: 					'front',
    fileExclusionRegExp: 	/^(r|build|lib)\.js$/,
    out: 					'dist/front.js',
    wrapShim: 				true,

    paths: {
        'jquery':				'lib/jquery.min', //must include jQuery to prevent Backgrid error
        'jquerymobile': 		'lib/jquery.mobile.min',
        //'bootstrap': 			'lib/bootstrap.min',
        'underscore':			'lib/underscore.min',
        'backbone': 			'lib/backbone.min',
        'backbone.paginator': 	'lib/ext/backbone.paginator'
    },

    shim: {
        'config': {
            deps: ['jquery'],
            exports: 'app'
        },

        'underscore': {
            deps: ['jquery'],
            exports: '_'
        },

        //'bootstrap':                      ['jquery'],
        'jquerymobile':                     ['config', 'jquery'],
        'backbone.paginator':               ['underscore', 'backbone'],
        'classes/Class': 					['config', 'jquery', 'underscore'],
        'classes/Utils': 					['config', 'jquery', 'underscore'],
        'classes/FormValidator': 			['config', 'jquery', 'underscore', 'classes/Class'],
        'classes/ScriptLoader': 			['config', 'jquery', 'underscore', 'classes/Class'],
        'classes/PageLoader': 				['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils'],
        'classes/ModuleLoader': 			['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils'],
        'classes/front/FrontModuleLoader': 	['config', 'jquery', 'underscore', 'classes/Class', 'classes/ModuleLoader'],

        'models/AbstractModel': 			[
            'config',
            'underscore',
            'backbone',
            'classes/FormValidator'
        ],

        'collections/AbstractCollection': 	[
            'config',
            'underscore',
            'backbone',
            'backbone.paginator',
            'models/AbstractModel'
        ],

        'collections/FrontCollection': 	[
            'collections/AbstractCollection'
        ],

        'views/AbstractContentView': 	[
            'config',
            'jquery',
            'underscore',
            'backbone'
        ],

        'views/FrontListView': [
            'views/AbstractContentView'
        ],

        'views/FrontModuleView': [
            'views/AbstractContentView'
        ],

        'views/AbstractTplView': 	[
            'config',
            'jquery',
            'underscore',
            'backbone',
            'classes/ScriptLoader'
        ],

        'views/FrontTplView': 	[
            'classes/front/FrontModuleLoader',
            'views/AbstractTplView'
        ],

        'routers/FrontRouter': 	[
            'routers/AbstractRouter',
            'views/FrontTplView'
        ]
    }
})