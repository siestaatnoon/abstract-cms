define([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'views/AbstractTplView',
    'classes/ScriptLoader'
], function(app, $, _, Backbone, AbstractTplView, ScriptLoader) {

    /**
     * Subclass for main template view in admin.
     *
     * @exports models/AbstractModel
     * @requires config
     * @requires jquery
     * @requires Underscore
     * @requires Backbone
     * @requires views/AbstractTplView
     * @constructor
     * @augments AbstractTplView
     */
    var FrontTplView = AbstractTplView.extend({
    /** @lends FrontTplView.prototype **/

        /**
         * @property {Object} docIncludes
         * Storage for link and script tags from page template, NOT included
         * through RequireJS
         */
        docIncludes: {
            link: [],
            script: []
        },

        /**
         * @property {String} loadingEl
         * Element identifier to place loader for page transitions
         */
        loadingEl: 'body',

        /**
         * @property {String} tplParams
         * Parameters for template in current content view
         */
        tplParams: '',

        /**
         * @property {String} templateUrl
         * URL for initial AJAX call to retrieve page template data
         */
        templateUrl: '',

        /**
         * @property {Boolean} useJqm
         * True if main template view and/or content view uses jQuery Mobile
         */
        useJqm: false,

        // TODO: class needs to execute embedded js within changed page content

        /**
         * Overwrites AbstractTplView.initialize() setting the template retrieval URL
         * for the main admin template.
         *
         * @param {Object} options - View options (Backbone)
         */
        initialize: function(options) {
            options = options || {};
            this.loading('show');
            this.templateUrl = app.frontTemplateURL;
            this.setScriptLoader();
            if (options.skipLoad) {
                return;
            }
            AbstractTplView.prototype.initialize.call(this, options);
        },


        /**
         * Removes the content view from the main application template, removing associated
         * CSS, Javascript and HTML blocks.
         *
         */
        closeContentView: function() {
            this.setLoadingElementId(false);
            this.loading('show');

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

            this.setContentScripts(null);
            this.setHeadTags({});
            AbstractTplView.prototype.closeContentView.call(this);
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
            } else if (task === 'show') {
                if ( $(this.loadingId).length ) {
                    return;
                }
                var id = this.loadingId.substr(1);
                $('<div/>').attr('id', id).appendTo(this.loadingEl);
            } else {
                $(this.loadingId).fadeOut(500, function() {
                    $(this).remove();
                });
            }
        },


        /**
         * Called before this.render() which sets the CSS/Javascript scrips and HTML
         * blocks in the main template. Also initializes the menu and search panels.
         *
         * @param {Object} data - Template data to render on page
         */
        postInit: function(data) {
            if ( _.isEmpty(data) === false ) {
                this.useJqm = data.useJqm || this.useJqm;
                this.blocks = data.blocks || this.blocks;
                this.scripts = data.scripts || this.getScriptsObject();
                this.tplParams = data.tpl_params || this.tplParams;
                var template = data.template ? $.trim(data.template) : '';
                if (template.length) {
                    this.template = _.template(template, {});
                    this._updateDOM(this.template);
                }
            }

            if (this.tplParams.length) {
            // save template parameters in header of AJAX calls
                var config = {};
                config['headers'] = {};
                config['headers'][app.tplInfoHeader] = this.tplParams;
                $.ajaxSetup(config);
            }

            this.setEl();
        },


        /**
         * If a template change occurs (distinct from content view change), will reset
         * the main front template before rendering the content view.
         *
         */
        reset: function() {
            if ( ! this.contentView.isNewPage) {
                return false;
            }

            this.trigger('template:reset:start');
            for (var i=0; i < this.blocksApp.length; i++) {
                this.blocksApp[i].remove();
            }
            this.blocksApp = [];

            this.postInit(this.contentView.newTpl);

            // TODO: should this be before this.postInit call?
            //
            // (loading doesn't seem to show if placed above)
            //
            this.setLoadingElementId(true);
            this.loading('show');

            if (this.contentView.newTpl.blocks) {
                this.setBlocks(this.contentView.newTpl.blocks, this.blocksApp);
            }
            this.trigger('template:reset:end');
        },


        /**
         * Sets the DOM loading element to place the loader widget while the
         * page transition occurs.
         *
         * @param {Boolean} isNewPage - True if new page template to be loaded
         */
        setLoadingElementId: function(isNewPage) {
            this.loadingEl = isNewPage ? 'body' : this.id;
        },


        /**
         * Sets the ScriptLoader object that loads javascript and CSS into the DOM.
         *
         */
        setScriptLoader: function() {
            this.scriptLoader = new ScriptLoader({
                cssRoot: app.frontCssRoot,
                jsRoot: app.frontJsRoot,
                legacyMode: false
            });
        },


        /**
         * Sets javascript and CSS scripts, content blocks and renders the Backbone.View
         * content in the DOM. Also updates the head tag elements (e.g. meta/title tags).
         *
         * @param {Backbone.View} view - The Backbone content view
         */
        transitionPage: function(view) {
            this.contentView = view;
            var contentScripts = this.contentView.scripts || {};
            this.setContentScripts(contentScripts);
            //this.listenTo(this.contentView, 'view:update:start', this.loading);
            //this.listenTo(this.contentView, 'view:update:end', function() { this.loading('hide') } );

            var headTags = {};
            if (this.contentView.isNewPage) {
                this.reset();
                if (this.contentView.newTpl.headTags) {
                    headTags = this.contentView.newTpl.headTags;
                }
            }
            if (this.contentView.headTags) {
                headTags = this.contentView.headTags;
            }

            this.setHeadTags(headTags);
            this.$el.prepend(this.contentView.$el);

            if (this.contentView.blocks) {
                this.setBlocks(this.contentView.blocks, this.blocksView);
            }

            // want to make sure content loaded to DOM first
            // and then load any necessary css/js scripts
            this.loadScripts();

            if (this.useJqm) {
                $.mobile.initializePage();
                $('body').enhanceWithin();
                $(document).trigger('pageinit');
            }
        },


        /**
         * Searches the main template for link and script tags and saves a reference to them
         * for later template manipulation.
         *
         * @param {XMLDocument} dom - The template (new, to render) DOM
         */
        _findHeadScripts: function(dom) {
            var head = dom ? dom.head : $('head').get(0);
            this.docIncludes.link = [];
            this.docIncludes.script = [];
            var tags = head.children;

            for (var i=0; i < tags.length; i++) {
                var tag = tags[i];
                var nodeName = tag.nodeName.toLowerCase();
                if (nodeName === 'script') {
                    var src = tag.getAttribute('src');
                    if (src) {
                        this.docIncludes.script.push(src);
                    }
                } else if (nodeName === 'link') {
                    var href = tag.getAttribute('href');
                    if (href) {
                        this.docIncludes.link.push(href);
                    }
                }
            }
        },


        /**
         * Accepts the new main template HTML and replaces the head tag script and link
         * elements with the new template elements. Also executes newly added javascript
         * script src files or executable code by temporarily adding the script as a head
         * element and removing it.
         *
         * @param {String} html - The template HTML as string
         */
        _updateDOM: function(html) {
            var newDoc = new DOMParser().parseFromString(html, "text/html");
            var $head = $('head');

            // remove current script/link includes by src/href (in template, non Require JS loaded)
            for (var tag in this.docIncludes) {
                var attr = tag === 'script' ? 'src' : 'href';
                for (var i=0; i < this.docIncludes[tag].length; i++) {
                    $(tag + '[' + attr + '="' + this.docIncludes[tag][i] + '"]', $head).remove();
                }
            }

            // resets the current js scripts/css links in head tag to current template
            this._findHeadScripts(newDoc);

            // remove current script/link tags with executable js/css in <head> tag
            var tags = $head.get(0).children;
            for (var i=0; i < tags.length; i++) {
                var tag = tags[i];
                var nodeName = tag.nodeName.toLowerCase();
                if (nodeName === 'script') {
                    var src = tag.getAttribute('src');
                    if ( ! src) {
                        tag.remove();
                    }
                } else if (nodeName === 'link') {
                    var href = tag.getAttribute('href');
                    if ( ! href) {
                        tag.remove();
                    }
                }
            }

            // remove old head elements from DOM, except for CSS/js,
            // add new head elements to DOM,
            // trim and add line breaks for debugging
            $head.children(':not(link,script)').remove();
            var head = $head.get(0);
            head.innerHTML = "\n" + $.trim(newDoc.head.innerHTML) + "\n" + $.trim(head.innerHTML) + "\n";

            // workaround to execute newly added template scripts
            var scriptTags = newDoc.head.getElementsByTagName('script');
            for (var i=0; i < scriptTags.length; i++) {
                var script = document.createElement('script');
                var src = scriptTags[i].getAttribute('src');
                if (src) {
                    script.src = src;
                } else {
                    script.innerHTML = scriptTags[i].innerHTML;
                }
                head.appendChild(script);   // appending script as child node to head
                head.removeChild(script);   // will execute js, then we can remove it
            }

            // remove any <body> tag attributes, replacing with
            // attributes of current tag, if any
            var $body = $('body');
            var bodyAttr = newDoc.body.attributes;
            var attr = {};
            $.each(bodyAttr, function(i, a){
                attr[a.name] = a.value;
            });
            $body.each(function() {
                var $tag = $(this);
                $.each(this.attributes, function(i, a){
                    $tag.removeAttr(a.name);
                });
                $.each(attr, function(name, val){
                    if (val) {
                        $tag.attr(name, val);
                    } else {
                        $tag.prop(name, true);
                    }
                });
            });

            // remove all content from <body> tag except for Require JS script and loading spinner
            $body.children(':not(' + this.requireId + ',' + this.loadingId + ')').each(function() {
                $(this).remove();
            });

            // Add new content to <body> tag
            $body.prepend(newDoc.body.innerHTML);
        }
    });

    return FrontTplView;
});
