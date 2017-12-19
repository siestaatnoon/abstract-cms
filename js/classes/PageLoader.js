define([
	'config',
	'jquery',
	'classes/Class',
	'classes/Utils'
], function(app, $, C, Utils) {
	var PageLoader = Class.extend({

		isCms: false,
		
		init: function(options) {
			options = options || {};
			this.isCms = options.isCms || this.isCms;
		},
		
		load: function(slug) {
			var deferred = $.Deferred();
			var url = (this.isCms ? app.adminPageRoot : app.frontPageRoot) + '/' + slug
			var self = this;
			var error_label = 'Error';
			var error_msg  = '';
			
			$.ajax({
				url:		url,
				type: 		'GET'
			}).done(function(data) {
				if (data.errors) {
					if (app.debug) {
						error_msg = data.errors.join('<br/><br/>');
						console.log( data.errors.join("\n") );
					}
					Utils.showModalWarning(error_label, error_msg);
				} else {
					if (app.debug) {
						console.log('Loaded page [' + slug + '] from URL: ' + url);
					}
					deferred.resolve(data);
				}
				
			}).fail(function(jqXHR, status, error) {
				if (app.debug) {
					error_msg = 'Page [' + slug + '] not retrieved: [' + status + '] ' + error + '.';
					console.log(error_msg);
				}
			});

			return deferred.promise();
		}
	});
	
	return PageLoader;
});
