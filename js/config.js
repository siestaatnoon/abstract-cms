define([
	'jquery',
    'text!../abstract.json'
    ], function($, abstractCfgJson) {
    var abstractCfg = $.parseJSON(abstractCfgJson);
	var app = app || {};
	app.debug 				= abstractCfg.debug;
	app.csrfToken 			= abstractCfg.csrfToken;
    app.docRoot 			= abstractCfg.docRoot + '/';
    app.loadingId 	    	= abstractCfg.loadingId;
    app.pageContentId 	    = abstractCfg.pageContentId;
    app.pageDynContentId    = abstractCfg.pageDynContentId;
    app.tplInfoHeader       = abstractCfg.tplInfoHeader;
    app.requireJsId         = abstractCfg.requireJsId;

    app.adminPagerPerPage 	= abstractCfg.adminPagerPerPage;
    app.adminURI 		    = abstractCfg.adminUri;
    app.adminRoot 			= app.docRoot + app.adminURI;
	app.adminHomeFrag 		= app.adminURI + '/home';
	app.adminApiRoot 		= app.docRoot + 'api/admin';
    app.adminCssRoot 		= app.docRoot + 'css';
    app.adminJsRoot 		= app.docRoot + 'js/plugins';
	app.adminDataRoot 		= app.adminApiRoot + '/data';
	app.adminBulkUpdateURL 	= app.adminApiRoot + '/bulk_update';
	app.adminCustomFieldURL = app.adminApiRoot + '/form_field_custom';
    app.adminFileDeleteURL 	= app.adminApiRoot + '/delete_file';
	app.adminFileUploadURL 	= app.adminApiRoot + '/upload_file';
    app.adminPageRoot 		= app.adminApiRoot + '/page';
	app.adminSessPollURL 	= app.adminApiRoot + '/session_poll';
	app.adminTemplateURL 	= app.adminDataRoot + '/app';

    app.frontPagerPerPage 	= abstractCfg.frontPagerPerPage;
    app.frontTemplateDir 	= app.docRoot + 'templates';
    app.frontApiRoot 		= app.docRoot + 'api/front';
    app.frontCssRoot 		= app.frontTemplateDir + '/static';
    app.frontJsRoot 		= app.frontTemplateDir + '/static';
    app.frontDataRoot 		= app.frontApiRoot + '/data';
	app.frontHomeFrag 		= 'home';
	app.frontPageRoot 		= app.frontApiRoot + '/page';
    app.frontTemplateURL 	= app.frontDataRoot + '/app';
    app.useFrontJqm         = abstractCfg.useFrontJqm;

	app.hashChange 			= false;
	app.pushState 			= true;
	app.usePagination 		= true;
	app.ModuleLoader		= {};
	app.Validator			= {};
	app.Router				= {};
	app.AppView 			= {};
	app.Auth				= {};
	app.SessionPoller		= {};
	app.PageLoader			= {};
	app.appCache			= {};

	//JQM initialization... note that this comes
	//before JQM loads page
	//
	$(document).bind('mobileinit', function() {
	    if ($.mobile) {
            $.mobile.autoInitializePage = false;
            $.mobile.ajaxEnabled = false;
            $.mobile.hashListeningEnabled = false;
            $.mobile.pushStateEnabled = false;

            //commented since this is needed for JQM listbox
            //to function
            //$.mobile.linkBindingEnabled = false;

            $.mobile.loader.prototype.options.text = "";
            $.mobile.loader.prototype.options.textVisible = true;
            $.mobile.loader.prototype.options.theme = "a";
            $.mobile.loader.prototype.options.html = '<div class="ui-loader-centered"><span class="ui-icon-loading"></span><h1>loading</h1></div>';

            //add loading splash on page load/reload
            $('body').pagecontainer({
                beforeshow: function (e, ui) {
                    $('html').removeClass('abstract-splash');
                }
            });
        }
	});

	return app;
});