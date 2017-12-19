define([
	'config',
	'jquery',
	'underscore',
	'classes/admin/SessionPoller',
	'classes/Utils',
    'classes/Class'
], function(app, $, _, SessionPoller, Utils) {

	/**
	 * Contains the authentication functions for the admin CMS.
	 *
	 * @exports classes/admin/AdminAuth
	 * @requires config
	 * @requires jquery
	 * @requires Underscore
	 * @constructor
	 * @augments Class
	 */
	var AdminAuth = Class.extend(
     /** @lends class/Class.prototype **/
     {
         /**
          * @property {string} _urlRoot
          * URL to access server REST API authentication functions
          */
		_urlRoot:	'',

         /**
          * @property {string} _dataRoot
          * URL to access server REST API authentication template functions
          */
	    _dataRoot:	'',

         /**
          * @property {string} _module
          * Module slug used for REST API functions
          */
	    _module:	'authenticate',

         /**
          * @property {Object} _session
          * classes/admin/SessionPoller instance used to poll current user for activity
          */
	    _session:	{},

         /**
          * Initializes this class.
          *
          * @param {Object} options - Class options: [destroyCallback] optional callback function
          * to execute after a user admin session is destroyed
          */
		init: function(options) {
			var callback = options.destroyCallback && typeof options.destroyCallback === 'function' ? 
					   	   options.destroyCallback : 
					   	   $.noop();
			this._urlRoot = app.adminApiRoot + '/' + this._module;
			this._dataRoot = app.adminDataRoot + '/' + this._module;
			this._session = new SessionPoller({
				destroyCallback: callback
			});
		},

         /**
          * Authenticates the admin user after a login form submit.
          *
          * @param {string} user - The username
          * @param {string} pass - The user password
          * @param {boolean} is_remember - True to keep session open past default server session time
          * @return {Object} The AJAX promise object after call to REST API to validate user
          */
		authenticate: function(user, pass, is_remember) {
			var deferred = $.Deferred();
			var self = this;
			var error_label = 'Error';
			var error_msg  = '';
			var data = {
				user: user,
				pass: pass,
				is_remember : is_remember
			};

			$.ajax({
				url:		this._urlRoot + '/login',
				data: 		data,
				type: 		'POST',
				dataType: 	'json'
			}).done(function(response) {
				if (response.session_active) {
					self._session.sessionStart();
				}
				deferred.resolve(response);
			}).fail(function(jqXHR, status, error) {
				if (app.debug) {
					error_msg = 'Authentication failed: [' + status + '] ' + error + '.';
					console.log(error_msg);
				}
			});
			
			return deferred.promise();
		},

         /**
          * Ends (invalidates) the user admin session.
          *
          * @return {Object} The AJAX promise object after call to REST API to invalidate user
          */
		invalidate: function() {
			var deferred = $.Deferred();
			var self = this;
			var error_label = 'Error';
			var error_msg  = '';
			
			$.ajax({
				url:		this._urlRoot + '/logout',
				type: 		'GET',
				dataType: 	'json'
			}).done(function(json) {
				if (json.logged_out) {
					self._session.sessionDestroy();
					deferred.resolve(json.logged_out);
				} else if (json.errors) {
					if (app.debug) {
						error_msg = json.errors.join('<br/><br/>');
						console.log( json.errors.join("\n") );
					}
					Utils.showModalWarning(error_label, error_msg);
				}
			}).fail(function(jqXHR, status, error) {
				if (app.debug) {
					error_msg = 'Logout failed: [' + status + '] ' + error + '.';
					console.log(error_msg);
				}
			});
			
			return deferred.promise();
		},

         /**
          * Checks if user admin session is still active.
          *
          * @return {boolean} True if user session is still active
          */
		isAuthenticated: function() {
			return this._session.isSessionValid();
		},

         /**
          * Loads the authentication pages via AJAX.
          *
          * @param {string} route - The page-specific URL segment for retrieval
          * @return {Object} The AJAX promise object after call to REST API to retrieve page
          */
		loadPageData: function(route) {
			var deferred = $.Deferred();
			var apiUrl = this._dataRoot + '/' + route;
			var self = this;
			var error_label = 'Error';
			var error_msg  = '';
			
			$.ajax({
				url : 		apiUrl,
				type: 		'GET',
				dataType: 	'json'
			}).done(function(json) {
				if (json.fields && json.template) {
					if (app.debug) {
						console.log('AdminAuth.loadAuthPage[' + self._module + '] page data retrieved ' + apiUrl);
					}
					deferred.resolve(json);
				} else if (json.errors) {
					if (app.debug) {
						error_msg = json.errors.join('<br/><br/>');
						console.log( json.errors.join("\n") );
					}
					Utils.showModalWarning(error_label, error_msg);
				}
				
			}).fail(function(jqXHR, status, error) {
				if (app.debug) {
					error_msg = 'AdminAuth.loadAuthPage[' + self._module + '] page data not retrieved [';
					error_msg += status + '] ' + error + '.';
					console.log(error_msg);
				}
			});
			
			return deferred.promise();
		},

         /**
          * Sends a ping to the admin user browser to validate current session.
          *
          * @param {Function} callback - The callback function to execute after a ping
          */
		ping: function(callback) {
			callback = callback && typeof callback === 'function' ? callback : $.noop();
			this._session.sessionPing(callback);
		}
	});
	
	return AdminAuth;
});
