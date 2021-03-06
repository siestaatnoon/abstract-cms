require.config({
    urlArgs: 				"_=" +  (new Date()).getTime(),
    waitSeconds: 			60,

    paths: {
        'jquery': 				'lib/jquery.min', 				//1.12.4
        'jquerymobile':         'lib/jquery.mobile.min',        //1.4.5
        'underscore':			'lib/underscore.min',			//1.8.3
        'backbone': 			'lib/backbone.min',				//1.3.3
        'backbone.paginator': 	'lib/ext/backbone.paginator',	//2.0.6
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

        'jquerymobile':             ['config', 'jquery'],
        'backbone.paginator':       ['underscore', 'backbone'],
        'classes/I18n':             ['config', 'jquery', 'underscore'],
        'classes/Class': 	        ['config', 'jquery', 'underscore'],
        'classes/Utils': 	        ['config', 'jquery', 'underscore'],
        'classes/FormValidator': 	['config', 'jquery', 'underscore', 'classes/Class', 'classes/I18n'],
    }
});

require([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'views/FrontTplView',
    'routers/FrontRouter'
], function(app, $, _, Backbone, FrontTplView, FrontRouter) {

    $(document).on("click", "a[href^='/']", function(e) {
        var href = $(this).attr('href');
        if ( ! e.altKey && ! e.ctrlKey && ! e.metaKey && ! e.shiftKey) {
            e.preventDefault();
            app.AppView.loading('show');
            app.Router.navigate(href, {trigger: true});
            return false;
        }
    });

    app.AppView = new FrontTplView({skipLoad: false});
    app.Router = new FrontRouter();

    $.ajaxSetup({
        cache: false, // don't cache AJAX calls
        statusCode: {
            401: function(){
                app.Router.navigate('404', {trigger: false, replace: true});
            },
            403: function() {
                app.Router.navigate('404', {trigger: false, replace: true});
            },
            404: function() {
                app.Router.navigate('404', {trigger: false, replace: true});
            }
        }
    });

    Backbone.history.start({
        pushState	: app.pushState,
        root		: app.docRoot,
        hashChange	: app.hashChange
    });

    // For some reason, the jQuery popstate event does not fire
    // on all Back/Prev button clicks (bug?), so replacing the Backbone
    // event with native JS version here seems to do the trick.
    //
    if (app.pushState) {
        Backbone.$(window).off('popstate');
        window.onpopstate = function() {
            Backbone.history.checkUrl();
        };
    }

});
