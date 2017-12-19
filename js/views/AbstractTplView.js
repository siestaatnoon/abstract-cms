define([
    'config',
    'jquery',
    'underscore',
    'backbone'
], function(app, $, _, Backbone) {

    /**
     * Superclass for main template view in CMS and frontent.
     *
     * @exports models/AbstractModel
     * @requires config
     * @requires jquery
     * @requires Underscore
     * @requires Backbone
     * @constructor
     * @augments Backbone.View
     */
    var AbstractTplView = Backbone.View.extend({
    /** @lends views/AbstractTplView.prototype **/

        /**
         * @property {Object} DEFAULT_SCRIPTS
         * Default object containing CSS and Javascript script includes as well as
         * Javascript onload and unload code blocks.
         */
        DEFAULT_SCRIPTS: {
            css: [],
            js: {
                src: [],
                onload: '',
                unload: ''
            }
        },

        /**
         * @property {String} id
         * Element id for main template view container.
         */
        id: app.pageContentId,

        /**
         * @property {Array} blocks
         * Array containing HTML blocks for main template view or content view.
         */
        blocks: [],

        /**
         * @property {Array} blocksApp
         * Storage for HTML blocks in main template view.
         */
        blocksApp: [],

        /**
         * @property {Array} blocksView
         * Storage for HTML blocks in template content view.
         */
        blocksView: [],

        /**
         * @property {Backbone.View} contentView
         * View that is updated/changed when navigating to other pages.
         */
        contentView: {},

        /**
         * @property {Object} contentScripts
         * CSS and Javascript scripts include in the content view.
         */
        contentScripts: {},

        /**
         * @property {jqXHR} deferred
         * The jQuery promise object returned returned in render() function if view retrieving
         * page data by AJAX.
         */
        deferred: null,

        /**
         * @property {Boolean} hasLoadedScripts
         * True if main template view has loaded CSS and Javascript includes as to not
         * repeat loading upon updating the content view.
         */
        hasLoadedScripts: false,

        /**
         * @property {Array} headSelectors
         * HoldsjQuery selector strings for current page head tag elements.
         */
        headSelectors: [],

        /**
         * @property {String} loadingId
         * Element id for "loading" HTML used in page transitions.
         */
        loadingId: app.loadingId,

        /**
         * @property {String} requireId
         * Element id for the RequireJS script include.
         */
        requireId: app.requireJsId,

        /**
         * @property {Object} contentScripts
         * CSS and Javascript scripts include in the main template view.
         */
        scripts: {},

        /**
         * @property {classes/ScriptLoader} scriptLoader
         * The class utilized to load CSS and Javascript includes.
         */
        scriptLoader: null,

        /**
         * @property {Oject} template
         * Object utilized to render a template using the Underscore yemplate function.
         */
        template: null,

        /**
         * @property {String} templateURL
         * API url to retrieve main template data.
         */
        templateURL: '',

        /**
         * @property {Boolean} useJqm
         * True if main template view and/or content view uses jQuery Mobile.
         */
        useJqm: app.useFrontJqm,

        /**
         * @property {Object} events
         * Backbone events utilized in the main template view.
         */
        events: {

        },

        /**
         * Initializes the main template view by retrieving the data via AJAX and
         * setting the Backbone view element.
         *
         * @param {Object} options - View options (Backbone).
         */
        initialize: function(options) {
            this._setScriptLoader();

            if (this.deferred === null) {
                var self = this;
                var deferred = $.Deferred();

                $.ajax({
                    url:		this.templateURL,
                    type: 		'GET',
                    dataType: 	'json'
                }).done(function(data) {
                    if (data.errors) {
                        if(app.debug) {
                            var message = "TplView.initialize: an API error has occurred:\n";
                            message += data.errors.join("\n");
                            console.log(message);
                        }
                    } else {
                        self.postInit(data);
                    }
                    deferred.resolve();
                }).fail(function(jqXHR, status) {
                    if (app.debug) {
                        console.log('TplView.initialize: data retrieve failed: [' + status + "]\n" + jqXHR.responseText);
                    }
                });

                this.deferred = deferred.promise();
            } else {
                self.postInit({});
            }
        },

        /**
         * Renders the template content view. If loaded by AJAX, will wait until
         * resolved to render.
         *
         * @param {Backbone.View} view - The Backbone content view.
         */
        gotoContentView: function(view) {
            if ( this.onInit(this.gotoContentView, view) === false) {
                return false;
            }

            this.trigger('content:update:start');
            this._closeContentView();
            var render = view.render();

            if (render.promise) {
                var self = this;
                render.done(function() {
                    self._transitionPage(view);
                    self.trigger('content:update:end');
                    self.loading('hide');
                });
            } else {
                this._transitionPage(view);
                this.trigger('content:update:end');
                this.loading('hide');
            }
        },

        /**
         * Shows or hides the loading HTML between page transitions.
         *
         * @param {String} showHide - If "hide" will hide the loading HTML, otherwise will show it.
         */
        loading: function(showHide) {
            var task = showHide === 'hide' ? 'hide' : 'show';
            if (this.useJqm) {
                $.mobile.loading(task);
            }
        },

        onInit: function(callback, args) {
            if ( ! this.deferred) {
                return true;
            }
            var state = this.deferred.state();
            var is_loaded = true;

            if (state !== 'resolved' ) {
                if (state === 'pending' && _.isFunction(callback) ) {
                    //need to wait until template loaded via ajax
                    var self = this;
                    if ( _.isArray(args) === false) {
                        args = [args];
                    }
                    this.deferred.done(function() {
                        callback.apply(self, args);
                    });
                }
                is_loaded = false;
            }

            return is_loaded;
        },

        /**
         * Called before this.render() which sets the CSS/Javascript scrips and HTML
         * blocks in the main template.
         *
         * @param {Object} data - Template data to render on page.
         */
        postInit: function(data) {
            if ( _.isEmpty(data) === false ) {
                this.useJqm = data.useJqm || this.useJqm;
                this.blocks = data.blocks || this.blocks;
                this.scripts = data.scripts || this._getScriptsObject();
                var template = data.template ? $.trim(data.template) : '';
                if (template.length) {
                    this.template = _.template(template, {});
                }
            }

            this.setEl();
        },

        /**
         * Overwrites Backbone.View.render() and DOES NOT remove this view from the page DOM.
         * Instead clears the template of CSS/Javascript includes and HTML blocks and resets
         * the main app template.
         *
         */
        remove: function() {
            this.scriptLoader.unload();
            this.hasLoadedScripts = false;
            this._closeContentView();
            for (var i=0; i < this.blocksApp.length; i++) {
                this.blocksApp[i].remove();
            }
            this.blocksApp = [];
            this.undelegateEvents();
            this.$el.empty();
            //Backbone.View.prototype.remove.call(this);

            //reset the template in case user logged out
            //then back in since template removed from DOM
            this.setEl();
        },

        /**
         * Renders this view and, if data still loading via AJAX, will be called again upon
         * AJAX call being resolved.
         *
         * @return {AbstractTplView} This View object
         */
        render: function() {
            if ( this.onInit(this.render, null) === false) {
                return null;
            }

            //blocks only load once
            if (this.blocksApp.length === 0) {
                this._setBlocks(this.blocks, this.blocksApp);
            }

            this.$el.empty();
            if (this.useJqm) {
                $.mobile.initializePage();
                $('body').enhanceWithin();
            }

            return this;
        },

        /**
         * Sets or creates the DOM element container for the content view.
         *
         */
        setEl: function() {
            if ( $(this.id).length ) {
            // check for element in current DOM
                this.setElement( $(this.id)[0] );
            } else if (this.template) {
            // check for element in new template
                var $template = $(this.template);
                if ( '#' + $template.attr('id') === this.id ) {
                    this.setElement($template[0]);
                } else if ( $(this.id, $template).length ) {
                    this.setElement( $(this.id, $template)[0] );
                } else {
                    this.tagName = 'div';
                }
            } else {
            // create a new element appended to <body> tag
                var $body = $('body');
                var $el = $('<div/>').attr('id', this.id.substr(1) ).html( $body.html() );
                $body.html('');
                $el.appendTo($body);
                this.setElement($el[0]);
            }
        },

        /**
         * Initializes the ScriptLoader class to load CSS/Javascript includes.
         *
         */
        _setScriptLoader: function() {
            this.scriptLoader = new ScriptLoader();
        },

        /**
         * Removes the content view from the main application template, removing associated
         * CSS, Javascript and HTML blocks.
         *
         */
        _closeContentView: function() {
            // remove content view CSS/JS
            if (this.contentScripts.css || this.contentScripts.js) {
                if (this.contentScripts.css) {
                    this.scriptLoader.removeCss(this.contentScripts.css);
                }
                if (this.contentScripts.js) {
                    this.scriptLoader.removeJs(this.contentScripts.js.src);
                }
                this.scriptLoader.triggerUnload();
            }

            // TODO: remove onload/unload js

            this._setContentScripts(null);
            this._setHeadTags({});

            if ( _.isEmpty(this.contentView) === false ) {
                this.contentView.remove();
                this.contentView = {};
            }

            for (var i=0; i < this.blocksView.length; i++) {
                this.blocksView[i].remove();
            }
            this.blocksView = [];
        },

        /**
         * Retrieves a default Object used to configure CSS/Javascript includes for the
         * ScriptLoader class.
         *
         * @return {Object} The CSS/Javascript configuration object
         */
        _getScriptsObject: function() {
            return JSON.parse( JSON.stringify(this.DEFAULT_SCRIPTS) );
        },

        /**
         * Loads CSS/Javascript includes for the page, including the content view. Note,
         * will load content view includes separately if content view is updated.
         *
         */
        _loadScripts: function() {
            var cssInc = [];
            var jsInc = [];
            var jsOnload = '';
            var jsUnload = '';
            var js = this.scripts['js'] ? this.scripts['js'] : {};

            if (this.hasLoadedScripts) {
                cssInc = this.contentScripts.css;
                jsInc = this.contentScripts.js.src;
            } else {
                var css = this.scripts['css'] ? this.scripts['css'] : [];
                var src = js['src'] ? js['src'] : [];
                cssInc = css.concat(this.contentScripts.css);
                jsInc = src.concat(this.contentScripts.js.src);
                this.hasLoadedScripts = true;
            }

            var cvOnload = this.contentScripts.js.onload ? "\n\n" + this.contentScripts.js.onload : '';
            jsOnload = js['onload'] ? js['onload'] + cvOnload : '';
            var cvUnload = this.contentScripts.js.unload ? "\n\n" + this.contentScripts.js.unload : '';
            jsUnload = js['unload'] ? js['unload'] + cvUnload : '';
            this.scriptLoader.loadCss(cssInc);
            this.scriptLoader.loadJs(jsInc, jsOnload, jsUnload);
        },

        /**
         * Loads HTML block(s) for the main template view and content view.
         *
         * @param {Array} blocks - Array of HTML block configurations.
         * @param {Array} storage - Storage array for resulting jQuery objects from blocks, used to
         * update, remove etc.
         */
        _setBlocks: function(blocks, storage) {
            if ( _.isEmpty(blocks) ) {
                return false;
            } else if ( $.isPlainObject(blocks) ) {
                blocks = [blocks];
            }

            var validFunctions = ['insertAfter', 'insertBefore', 'appendTo', 'prependTo'];
            for (var i=0; i < blocks.length; i++) {
                var block = blocks[i];
                var $el = $(block['selector']) || $(this.id);
                var pos_func = $.inArray(block['pos_func'], validFunctions) ? block['pos_func'] : validFunctions[0];
                var html = '';

                if (block['template']) {
                    var data = block['data'] || {};
                    html = _.template(block['template'], data);
                } else if (block['html']) {
                    html = block['html'];
                } else {
                    continue;
                }

                $html = $(html);
                $html[pos_func]($el);
                storage.push($html);
            }
        },

        _setContentScripts: function(scripts) {
            this.contentScripts = this._getScriptsObject();

            if ( _.isObject(scripts) === false ) {
                return;
            }

            var css = this.scripts['css'] ? this.scripts['css'] : [];
            var contentCss = scripts['css'] ? scripts['css'] : [];
            for (var i=0; i < contentCss.length; i++) {
                var include = contentCss[i];
                if (include.substr(0, 1) === '/') {
                    include = include.substr(1);
                }
                var isDupe = false;
                for (var j=0; j < css.length; j++) {
                    var tocheck = css[j];
                    if (tocheck.substr(0, 2) !== '//' && tocheck.substr(0, 1) === '/') {
                        tocheck = tocheck.substr(1);
                    }
                    if (tocheck === include) {
                        isDupe = true;
                        break;
                    }
                }
                if ( ! isDupe) {
                    this.contentScripts['css'].push(contentCss[i]);
                }
            }

            var js = this.scripts['js'] ? this.scripts['js'] : {};
            var src = js['src'] ? js['src'] : [];
            var contentJs = scripts['js'] ? scripts['js'] : {};
            var contentSrc = contentJs['src'] ? contentJs['src'] : [];

            for (var i=0; i < contentSrc.length; i++) {
                var script = typeof contentSrc[i] === 'string' ? contentSrc[i] : Object.keys(contentSrc[i])[0];
                if (script.substr(0, 1) === '/') {
                    script = script.substr(1);
                }
                var isDupe = false;
                for (var j=0; j < src.length; j++) {
                    var tocheck = typeof src[j] === 'string' ? src[j] : Object.keys(src[j])[0];
                    if (tocheck.substr(0, 2) !== '//' && tocheck.substr(0, 1) === '/') {
                        tocheck = tocheck.substr(1);
                    }
                    if (tocheck === script) {
                        isDupe = true;
                        break;
                    }
                }
                if ( ! isDupe) {
                    this.contentScripts.js['src'].push(contentSrc[i]);
                }
            }

            if (contentJs['onload']) {
                this.contentScripts.js['onload'] = contentJs['onload'];
            }

            if (contentJs['unload']) {
                this.contentScripts.js['unload'] = contentJs['unload'];
            }
        },

        _setHeadTags: function(headTags) {
            if ( _.isObject(headTags) === false ) {
                return;
            }

            var $head = $('head');
            var selectors = [];
            for (var tag in headTags) {
                if (tag === 'title') {
                    $('title', $head).text(headTags[tag]);
                } else if ( _.isObject(headTags[tag]) === false ) {
                    continue;
                } else {
                    var props = headTags[tag];
                    for (var propName in props) {
                        var propVals = props[propName];
                        for (var val in propVals) {
                            var attributes = propVals[val];
                            var selector = tag + '[' + propName + '="' + val + '"]';
                            var $headEl = $(selector, $head);
                            if ($headEl.length === 0) {
                                $headEl = $('<' + tag + '/>').attr(propName, val);
                                $headEl.appendTo($head);
                            }

                            for (var attrName in attributes) {
                                var attrVal = attributes[attrName];
                                if (typeof attrVal === 'boolean') {
                                    $headEl.prop(attrName, attrVal);
                                } else {
                                    $headEl.attr(attrName, attrVal);
                                }
                            }

                            selectors.push(selector);
                        }
                    }
                }
            }

            // remove previous head elements
            var toRemove = _.difference(this.headSelectors, selectors);
            for (var i=0; i < toRemove.length; i++) {
                $(toRemove[i], $head).remove();
            }
            this.headSelectors = selectors;
        },

        _transitionPage: function(view) {
            this.contentView = view;
            var contentScripts = this.contentView.scripts || {};
            this._setContentScripts(contentScripts);
            this.listenTo(this.contentView, 'view:update:start', this.loading);
            this.listenTo(this.contentView, 'view:update:end', function() { this.loading('hide') } );

            var headTags = this.contentView.headTags || {};
            this._setHeadTags(headTags);
            this.$el.prepend(this.contentView.$el);

            if (this.contentView.blocks) {
                this._setBlocks(this.contentView.blocks, this.blocksView);
            }

            // want to make sure content loaded to DOM first
            // and then load any necessary css/js scripts
            this._loadScripts();

            if (this.useJqm) {
                $.mobile.initializePage();
                $('body').enhanceWithin();
                $(document).trigger('pageinit');
            }
        }
    });

    return AbstractTplView;
});
