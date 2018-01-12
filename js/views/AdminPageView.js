define([
	'config',
	'jquery',
	'underscore',
	'backbone',
    'views/AbstractContentView'
], function(app, $, _, Backbone, AbstractContentView) {
	var AdminPageView = AbstractContentView.extend({
		
		pageLoader: null,
		
		slug: '',

		initialize: function(options) {
			this.slug = options.slug;
			this.pageLoader = options.pageLoader;
            AbstractContentView.prototype.initialize.call(this, options);
		},
		
		render: function() {
            this.deferred = this.pageLoader.load(this.slug);
			return AbstractContentView.prototype.render.call(this);
		}
	});
	
	return AdminPageView;
});