define([
	'config',
	'jquery',
	'underscore',
	'classes/Class',
	'classes/Utils'
], function(app, $, _, C, Utils) {

    /**
     * Utility class that polls the server at regular intervals to check for an active session,
     * return the time left and execute and callbacks upon a session timeeout.
     *
     * @exports classes/ScriptLoader
     * @requires config
     * @requires jquery
     * @requires Underscore
     * @requires classes/Class
     * @requires classes/Utils
     * @constructor
     * @augments classes/Class
     */
	var SessionPoller = Class.extend({
    /** @lends classes/SessionPoller.prototype **/

        /**
         * @property {Object} _destroyCallback
         * Callback to execute upon a session timing out
         */
		_destroyCallback: {},

        /**
         * @property {Boolean} _hasSession
         * Flag if session is active and has not timed out
         */
		_hasSession: false,

        /**
         * @property {Number} _pollingInterval
         * Interval in milliseconds between polling calls via AJAX
         */
		_pollingInterval: 10000,

        /**
         * @property {Number} _poller
         * ID of timer used for session polling
         */
		_poller: null,

        /**
         * @property {Number} _timeLeft
         * Time left in session in seconds
         */
		_timeLeft: 0,

        /**
         * Initializes the SessionPoller. An optional object with properties
         * interval: [polling interval in seconds] and destroyCallback: [function()],
         * to execute upon session timeout, may be set.
         *
         * @param {Object} options - Options for poller
         */
		init: function(options) {
			if (options) {
				this._pollingInterval = options.interval || this._pollingInterval;
				this._destroyCallback = options.destroyCallback || null;
			}
		},

        /**
         * Returns true if session is active and has not timed out.
         *
         * @return {Boolean} True if session still active
         */
		isSessionValid: function() {
			return this._hasSession;
		},

        /**
         * Pings the API server, keeping the session active. No data is returned.
         *
         */
		keepAlive: function() {
			var url = app.adminSessPollURL + '/ping';
            var errorMsg = "SessionPoller.keepAlive: an API error has occurred";
			$.ajax({
				url:		url,
				type: 		'GET',
				dataType: 	'json'
			}).done(function(data) {
				if (data.errors) {
				    if (app.debug) {
                        errorMsg += ":\n" + data.errors.join("\n");
                    }
                    console.log(errorMsg);
				}
			}).fail(function(jqXHR) {
				if (app.debug) {
                    var response = Utils.parseJqXHR(jqXHR);
				    if (response.errors.length) {
                        errorMsg += ":\n" + response.errors.join("\n");
                    }
				}
                console.log(errorMsg);
			});
		},

        /**
         * Destroys the session and executes the callback if set in this.init().
         *
         */
		sessionDestroy: function() {
			window.clearInterval(this._poller);
			this._poller = null;
			this._hasSession = false;
			this._timeLeft = 0;
			if ( _.isFunction(this._destroyCallback) ) {
                this._destroyCallback.call(this);
            }
		},

        /**
         * Polls the API server, keeping the session active and returning the time left
         * in the session in seconds.
         *
         * @param {Function} callback - A function to execute upon polling serer
         */
		sessionPing: function(callback) {
			var self = this;
			var isActive = false;
			
			$.ajax({
				url:		app.adminSessPollURL + '/ping',
				type: 		'GET',
				dataType: 	'json'
			}).done(function(data) {
				if (data.session_active) { 
					isActive = true;
					self.sessionStart();
				} else if (data.errors) {
                    var message = "SessionPoller.sessionPing: an API error has occurred";
                    if (app.debug) {
                        message += ":\n" + data.errors.join("\n");
                    }
					console.log(message);
				}
			}).fail(function(jqXHR) {
                var errorMsg = "SessionPoller.sessionPing: an API error has occurred";
				if (app.debug) {
                    var response = Utils.parseJqXHR(jqXHR);
                    if (response.errors.length) {
                        errorMsg += ":\n" + response.errors.join("\n");
                    }
				}
                console.log(errorMsg);
			}).always(function() {
				if ( _.isFunction(callback)) {
					callback.call(this, isActive);
				}
			});
		},

        /**
         * Initializes the session polling. If session winding down within five minutes,
         * will display an alert to the user. If a session has timed out, will also notify
         * the user with an alert and execute the destroy callback set in this.init().
         *
         */
		sessionStart: function() {
			if (this._poller) {
			//to eliminate multiple calls here, just return if poller timer set
				return;
			}
			
			this._hasSession = true;
			var self = this;
			var sessionPoller = function() {
				$.ajax({
					url:		app.adminSessPollURL,
					type: 		'GET',
					dataType: 	'json'
				}).done(function(data) {
					var label = '';
					var message = '';

					if (data.session_active) { 
						self._hasSession = true;
						self._timeLeft = parseInt(data.time_left);
						var minutes = Math.ceil(self._timeLeft / 60);

						if (minutes < 5) {
							label = 'Your Session is About to Expire';
							message = 'Your session will end in less than %s. To continue your session, '; 
							message += 'click the Continue button below.';
							var time = minutes + (minutes == 1 ? ' minute' : ' minutes');
							if (self._timeLeft <= 30) {
								time = '30 seconds';
							}

							Utils.showModalWarning(label, message.replace('%s', time), self.keepAlive, self);
						} 
					} else if (data.errors) {
					//API error in AJAX call
						var message = "SessionPoller.sessionStart: poll initialize failed";
						if (app.debug) {
                            message += ":\n" + data.errors.join("\n");
                        }
                        console.log(message);
						self.sessionDestroy(); //end session
					} else {
					//session ended
						window.clearInterval(self._poller);
						label = 'Your Session Has Ended';
						message = 'Your session has ended. You will now be redirected to the login page.';
						Utils.showModalWarning(label, message, self.sessionDestroy, self);
					}
				}).fail(function(jqXHR) {
                    var errorMsg = "SessionPoller.sessionStart: poll initialize failed";
					if (app.debug) {
                        var response = Utils.parseJqXHR(jqXHR);
                        if (response.errors.length) {
                            errorMsg += ":\n" + response.errors.join("\n");
                        }
					}
                    console.log(errorMsg);
                    self.sessionDestroy(); //end session
				});
			};

			this._poller = window.setInterval(sessionPoller, this._pollingInterval);
		}
	});
	
	return SessionPoller;
});
