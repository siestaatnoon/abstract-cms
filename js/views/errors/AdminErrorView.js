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
	var AdminErrorView = Backbone.View.extend({

		id: '#abstract-error',
		
		$content: $(app.pageContentId),
		
		auth: null,
		
		errorCode: 0,

		hasError: false,
		
		router: null,
		
		templates: [],

		events: {
        	'click .button-back': 'goHome'
    	},

		initialize: function(options) {
			this.router = app.Router;
			this.auth = app.Auth;
			this.errorCode = parseInt(options.error) || 0;
			this.templates[401] = template401;
			this.templates[403] = template403;
			this.templates[404] = template404;
			this.hasError = true;
		},
		
		goHome: function() {
			this.remove();
			this.router.navigate(app.adminHomeFrag, {trigger: true, replace: true});
		},

		remove: function() {
			Backbone.View.prototype.remove.call(this);
			this.hasError = false;
		},

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