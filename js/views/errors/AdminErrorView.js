define([
	'config',
	'jquery', 
	'jquerymobile', 
	'underscore',
	'backbone',
    'classes/I18n',
	'text!templates/errors/admin/error.html'
], function(app, $, jquerymobile, _, Backbone, I18n, errorTemplate) {

    /**
     * Renders a view corresponding to an HTTP error code (e.g. 401, 403, 404).
     *
     * @exports models/AbstractModel
     * @requires config
     * @requires jquery
     * @requires Underscore
     * @requires Backbone
     * @requires classes/I18n
     * @constructor
     * @augments AdminErrorView
     */
	var AdminErrorView = Backbone.View.extend({
    /** @lends AdminErrorView.prototype **/

        /**
         * @property {String} id - Element id for this error view
         */
		id: '#abstract-error',

        /**
         * @property {jQuery} $content - jQuery object that contains this view
         */
		$content: $(app.pageContentId),

        /**
         * @property {classes/admin/AdminAuth} auth - Admin authentication object containing user functions
         */
		auth: null,

        /**
         * @property {Integer} errorCode - HTTP error code of the error
         */
		errorCode: 0,

        /**
         * @property {Boolean} hasError - True if error exists
         */
		hasError: false,

        /**
         * @property {Backbone.Router} hasError - Reference to Backbone admin router
         */
		router: null,

        /**
         * @property {Object} template - The error page template
         */
		template: null,

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
			this.errorCode = parseInt(options.error) || 500;
			this.template = _.template(errorTemplate);
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
         * Redirects  to the admin login page.
         *
         */
        goToLogin: function() {
            this.router.navigate('admin/authenticate/login', {trigger: true, replace: true});
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
         * Renders this error view. NOTE: if user not logged into admin, then all errors
		 * will redirect to login page. This effectively disables the 401 error page
         * but probably best course of action here.
         *
         * @return {views/error/AdminErrorView} Reference to this object
         */
		render: function() {
			if ( ! this.auth.isAuthenticated() ) {
				this.goToLogin();
				return false;
			}

			var data = {
			    title: I18n.t('error.' + this.errorCode + '.title'),
                content: I18n.t('error.' + this.errorCode + '.text'),
                buttonText: this.errorCode === 401 ? I18n.t('continue') : I18n.t('back')
            };
			$.mobile.loading('show');
			var template = this.template(data);
			this.setElement( $(template).first(this.id) );
			this.$content.empty();
			this.$el.appendTo(this.$content);

			// for 401 error page, make button return to login page
            if (this.errorCode === 401) {
            	var self = this;
                this.$el.find('button').removeClass('button-back').addClass('button-redirect').click(function() {
                    self.goToLogin();
                });
            }

			$.mobile.initializePage();
			this.$content.enhanceWithin();
			$.mobile.loading('hide');
			
			return this;
		}
	});
	
	return AdminErrorView;
});