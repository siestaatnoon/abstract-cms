define([
	'config',
	'jquery',
	'underscore', 
	'backbone',
    'views/AbstractTplView'
], function(app, $, _, Backbone, AbstractTplView) {

    /**
     * Superclass for main template view in CMS and frontent.
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
	var AdminTplView = AbstractTplView.extend({
    /** @lends views/AdminTplView.prototype **/

        /**
         * @property {String} id
         * Element id for main template view container.
         */
		id: app.pageContentId,

        /**
         * @property {Boolean} useJqm
         * True if main template view and/or content view uses jQuery Mobile.
         */
        useJqm: true,

        /**
         * @property {Object} events
         * Backbone events utilized in the main template view.
         */
		events: {

		},


        /**
         * Overwrites AbstractTplView.initialize() setting the template retrieval URL
         * for the main admin template.
         *
         * @param {Object} options - View options (Backbone).
         */
		initialize: function(options) {
            this.templateUrl = app.adminTemplateURL;
            AbstractTplView.prototype.initialize.call(this, options);
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

            this.closeContentView();
            var render = view.render();

            if (render.promise) {
                var self = this;
                render.done(function() {
                    self.transitionPage(view);
                });
            } else {
                this.transitionPage(view);
            }
        },


        /**
         * Shows or hides the loading HTML between page transitions.
         *
         * @param {String} showHide - If "hide" will hide the loading HTML, otherwise will show it.
         */
        loading: function(showHide) {
            var task = showHide === 'hide' ? 'hide' : 'show';
            $.mobile.loading(task);
        },


        /**
         * Loads CSS/Javascript includes for the page, including the content view. Note,
         * will load content view includes separately if content view is updated.
         *
         */
        loadScripts: function() {
		    if ( _.isUndefined(this.contentView.scripts) ) {
		        return;
            }

		    var scripts = this.contentView.scripts;
            if ( ! _.isUndefined(scripts['css']) ) {
                this.scriptLoader.loadCss(scripts['css']);
            }

            if ( ! _.isUndefined(scripts['js']) ) {
                var include = scripts['js'];
                var src = include['src'] || [];
                var onload = include['onload'] || '';
                var unload = include['unload'] || '';
                this.scriptLoader.loadJs(src, onload, unload);
            }
        },


        /**
         * Called before this.render() which sets the CSS/Javascript scrips and HTML
         * blocks in the main template. Also initializes the menu and search panels.
         *
         * @param {Object} data - Template data to render on page.
         */
        postInit: function(data) {
            if ( _.isEmpty(data) === false ) {
                this.useJqm = data.useJqm || this.useJqm;
                this.blocks = data.blocks || this.blocks;
                var template = data.template ? $.trim(data.template) : '';
                if (template.length) {
                    this.template = _.template(template, {});
                }
            }
            this.setEl();

            $(document).on('pageinit', function() {
                $('.jqm-navmenu-link, .jqm-search-link').show();
                $('.jqm-navmenu-panel ul').listview();

                $('body').on('click', '.jqm-navmenu-link', function(e) {
                    $('.jqm-navmenu-panel').panel('open');
                });

                // Initalize search panel list and filter also remove collapsibles
                var searchContents = $( ".jqm-search ul.jqm-list" ).find( "li:not(.ui-collapsible)" );

                $('body').on('click', '.jqm-search-link', function(e) {
                    $('.jqm-search-panel').panel('open');
                });

                $('.jqm-search-panel').on('panelopen', function() {
                    $( this ).find( "input" ).focus();
                });


                $('.jqm-search ul.jqm-list').html(searchContents).listview({
                    inset: false,
                    theme: null,
                    dividerTheme: null,
                    icon: false,
                    autodividers: true,
                    autodividersSelector: function ( li ) {
                        return "";
                    }
                }).filterable();
            });
        },


        /**
         * Overwrites AbstractTplView.render() and DOES NOT remove this view from the page DOM.
         * Instead clears the template of CSS/Javascript includes and HTML blocks and resets
         * the main app template.
         *
         */
		remove: function() {
            this.scriptLoader.unload();
			this.closeContentView();
			for (var i=0; i < this.blocksApp.length; i++) {
				this.blocksApp[i].remove();
			}

			this.blocksApp = [];
            this.$el.empty();
            //Backbone.View.prototype.remove.call(this);

			//reset the template in case user logged out 
			//then back in since template removed from DOM
            this.setEl();
            $('.jqm-navmenu-link, .jqm-search-link').hide();
		},


        /**
         * Sets javascript and CS scripts, content blocks and renders the Backbone.View
         * content in the DOM.
         *
         * @param {Backbone.View} view - The Backbone content view.
         */
        transitionPage: function(view) {
            this.contentView = view;
            this.listenTo(this.contentView, 'view:update:start', this.loading);
            this.listenTo(this.contentView, 'view:update:end', function() { this.loading('hide') } );
            this.$el.append(this.contentView.$el);

            if (this.contentView.blocks) {
                this.setBlocks(this.contentView.blocks, this.blocksView);
            }

            //want to make sure content loaded to DOM first and
            //JQM enhanced, then load any necessary css/js scripts
            //
            this.loadScripts();

            $.mobile.initializePage();
            $('body').enhanceWithin();
            $(document).trigger('pageinit');
            this.loading('hide');
        }
	});
	
	return AdminTplView;
});
