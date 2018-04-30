({
    removeCombined: 		true,
    findNestedDependencies: true,
    optimizeCss: 			'standard.keepComments',
    baseUrl: 				'js',
    name: 					'front',
    fileExclusionRegExp: 	/^(r|build|lib)\.js$/,
    out: 					'js/front-build.js',
    wrapShim: 				true,

    paths: {
        'jquery':				'lib/jquery.min', //must include jQuery to prevent Backgrid error
        'jquerymobile': 		'lib/jquery.mobile.min',
        //'bootstrap': 			'lib/bootstrap.min',
        'underscore':			'lib/underscore.min',
        'backbone': 			'lib/backbone.min',
        'backbone.paginator': 	'lib/ext/backbone.paginator',
        'config': 	            'config-build'
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
        'classes/I18n':                     ['config', 'jquery', 'underscore'],
        'classes/Class': 					['config', 'jquery', 'underscore'],
        'classes/Utils': 					['config', 'jquery', 'underscore', 'classes/I18n'],
        'classes/FormValidator': 			['config', 'jquery', 'underscore', 'classes/Class', 'classes/I18n'],
        'classes/ScriptLoader': 			['config', 'jquery', 'underscore', 'classes/Class'],
        'classes/PageLoader': 				['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils', 'classes/I18n'],
        'classes/ModuleLoader': 			['config', 'jquery', 'underscore', 'classes/Class', 'classes/Utils', 'classes/I18n'],
        'classes/front/FrontModuleLoader': 	['config', 'jquery', 'underscore', 'classes/Class', 'classes/ModuleLoader', 'classes/I18n'],

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
            'backbone',
            'classes/I18n'
        ],

        'views/FrontListView': [
            'views/AbstractContentView',
            'classes/I18n'
        ],

        'views/FrontModuleView': [
            'views/AbstractContentView',
            'classes/I18n'
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