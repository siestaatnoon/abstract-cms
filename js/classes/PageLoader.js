define([
	'config',
	'jquery',
	'classes/Class',
	'classes/Utils'
], function(app, $, C, Utils) {

    /**
     * Utility class that polls the server at regular intervals to check for an active session,
     * return the time left and execute and callbacks upon a session timeeout.
     *
     * @exports classes/PageLoader
     * @requires config
     * @requires jquery
     * @requires classes/Class
     * @requires classes/Utils
     * @constructor
     * @augments classes/Class
     */
	var PageLoader = Class.extend({
    /** @lends classes/PageLoader.prototype **/

        /**
         * @property {Boolean} isCms
         * True if loading page in admin area
         */
		isCms: false,

        /**
         * Initializes the SessionPoller. An optional object with property
         * isCms: true|false may be set.
         *
         * @param {Object} options - Options for page loader
         */
		init: function(options) {
			options = options || {};
			this.isCms = options.isCms || this.isCms;
		},

        /**
         * Loads a content page via AJAX and returns the jQuery XHR object where
         * the response can be handle via a done() callback to it.
         *
         * @param {String} slug - URI segment for page identifier
         * @return {jXHR} The jXHR promise object
         */
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
