define([
	'config',
	'jquery', 
	'jquerymobile', 
	'underscore',
	'backbone',
	'text!templates/errors/admin/401.html',
	'text!templates/errors/admin/403.html',
	'text!templates/errors/admin/404.html'
], function(app, $, jquerymobile, _, Backbone, template401, template403, template404) {

    /**
     * Renders a view corresponding to an HTTP error code (e.g. 401, 403, 404).
     *
     * @exports models/AbstractModel
     * @requires config
     * @requires jquery
     * @requires Underscore
     * @requires Backbone
     * @constructor
     * @augments AdminErrorView
     */
	var AdminErrorView = Backbone.View.extend({
    /** @lends AdminErrorView.prototype **/

        /**
         * @property {String} id
         * Element id for this error view
         */
		id: '#abstract-error',

        /**
         * @property {jQuery} $content
         * jQuery object that contains this view
         */
		$content: $(app.pageContentId),

        /**
         * @property {classes/admin/AdminAuth} auth
         * Admin authentication object containing user functions
         */
		auth: null,

        /**
         * @property {Integer} errorCode
         * HTTP error code of the error
         */
		errorCode: 0,

        /**
         * @property {Boolean} hasError
         * True if error exists
         */
		hasError: false,

        /**
         * @property {Backbone.Router} hasError
         * Reference to Backbone admin router
         */
		router: null,

        /**
         * @property {Array} templates
         * Storage for error page templates
         */
		templates: [],

        /**
         * @property {Object} events
         * Backbone events for this view
         */
		events: {
        	'click .button-back': 'goHome'
    	},


        /**
         * Initializes this error view.
         *
         * @param {Object} options - View options (Backbone)
         */
		initialize: function(options) {
			this.router = app.Router;
			this.auth = app.Auth;
			this.errorCode = parseInt(options.error) || 0;
			this.templates[401] = template401;
			this.templates[403] = template403;
			this.templates[404] = template404;
			this.hasError = true;
		},


        /**
         * Redirects  to the admin home page.
         *
         */
		goHome: function() {
			this.remove();
			this.router.navigate(app.adminHomeFrag, {trigger: true, replace: true});
		},


        /**
         * Calls the Backbone.View.remove() function.
         *
         */
		remove: function() {
			Backbone.View.prototype.remove.call(this);
			this.hasError = false;
		},


        /**
         * Renders this error view.
         *
         */
		render: function() {
			if ( _.isUndefined(this.templates[this.errorCode]) || ! this.auth.isAuthenticated() ) {
				self.router.navigate('admin/authenticate/login', {trigger: true, replace: true});
				return false;
			}
			
			$.mobile.loading('show');
			var template = this.templates[this.errorCode];
			this.setElement( $(template).first(this.id) );
			this.$content.empty();
			this.$el.appendTo(this.$content);
			$.mobile.initializePage();
			this.$content.enhanceWithin();
			$.mobile.loading('hide');
			
			return this;
		}
	});
	
	return AdminErrorView;
});