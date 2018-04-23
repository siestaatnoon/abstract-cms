define([
	'config',
	'jquery',
	'underscore',
	'classes/Utils',
    'classes/I18n',
	'classes/Class'
], function(app, $, _, Utils, I18n) {

    /**
     * Utility class that polls the server at regular intervals to check for an active session,
     * return the time left and execute and callbacks upon a session timeeout.
     *
     * @exports classes/ScriptLoader
     * @requires config
     * @requires jquery
     * @requires Underscore
     * @requires classes/Utils
     * @requires classes/I18n
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
         * Pings the API server, keeping the session active. No data is expected to be returned.
         *
         */
		keepAlive: function() {
			var url = app.adminSessPollURL + '/ping';

			$.ajax({
				url:		url,
				type: 		'GET',
				dataType: 	'json'
			}).done(function(response) {
                if (app.debug) {
                    console.log(response);
                }
			}).fail(function(jqXHR) {
                var resp = Utils.parseJqXHR(jqXHR);
                var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
                if (error.length === 0) {
                    error = I18n.t('error.general.unknown', 'SessionPoller.keepAlive()');
                }
                Utils.showModalWarning(I18n.t('error'), error);
                if (app.debug) {
                    console.log( error.replace('<br/>', "\n") );
                }
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
			}).done(function(response) {
                isActive = _.has(response, 'session_active') ? response.session_active : false;
                self.sessionStart();
			}).fail(function(jqXHR) {
                var resp = Utils.parseJqXHR(jqXHR);
                var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
                if (error.length === 0) {
                    error = I18n.t('error.general.unknown', 'SessionPoller.sessionPing()');
                }
                Utils.showModalWarning(I18n.t('error'), error);
                if (app.debug) {
                    console.log( error.replace('<br/>', "\n") );
                }
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
                            label = I18n.t('label.session.expire');
							var time = minutes + (minutes == 1 ? ' minute' : ' minutes');
							if (self._timeLeft <= 30) {
								time = '30 seconds';
							}
                            message = I18n.t('message.session.expire', time);
							Utils.showModalWarning(label, message, self.keepAlive, self);
						}
					} else {
					//session ended
						window.clearInterval(self._poller);
                        label = I18n.t('label.session.ended');
                        message = I18n.t('message.session.ended');
						Utils.showModalWarning(label, message, self.sessionDestroy, self);
					}
				}).fail(function(jqXHR) {
                    var resp = Utils.parseJqXHR(jqXHR);
                    var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
                    if (error.length === 0) {
                        error = I18n.t('error.general.unknown', 'SessionPoller.sessionStart()');
                    }
                    Utils.showModalWarning(I18n.t('error'), error);
                    if (app.debug) {
                        console.log( error.replace('<br/>', "\n") );
                    }
                    self.sessionDestroy(); //end session
				});
			};

			this._poller = window.setInterval(sessionPoller, this._pollingInterval);
		}
	});
	
	return SessionPoller;
});
