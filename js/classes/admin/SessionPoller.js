define([
	'config',
	'jquery',
	'underscore',
	'classes/Class',
	'classes/Utils'
], function(app, $, _, C, Utils) {
	var SessionPoller = Class.extend({
		
		_destroyCallback: {},
		
		_hasInitialized: false,
		
		_hasSession: false,
		
		_pollingInterval: 10000,
		
		_poller: null,
		
		_timeLeft: 0,
		
		init: function(options) {
			if (options) {
				this._pollingInterval = options.interval || this._pollingInterval;
				this._destroyCallback = options.destroyCallback || null;
			}
		},
		
		isSessionValid: function() {
			return this._hasSession;
		},
		
		keepAlive: function() {
			var url = app.adminSessPollURL + '/ping';
			$.ajax({
				url:		url,
				type: 		'GET',
				dataType: 	'json'
			}).done(function(data) {
				if (data.errors && app.debug) {
					var message = "SessionPoller.keepAlive: an API error has occurred:\n";
					message += data.errors.join("\n");
					console.log(message);
				}
			}).fail(function(jqXHR, status, error) {
				if (app.debug) {
					console.log('SessionPoller.keepAlive: refresh failed: [' + status + '] ' + error);
				}
			});
		},
		
		sessionDestroy: function() {
			window.clearInterval(this._poller);
			this._poller = null;
			this._hasSession = false;
			this._timeLeft = 0;
			if (typeof this._destroyCallback === 'function') {
                this._destroyCallback.call(this);
            }
		},
		
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
				} else if (data.errors && app.debug) {
					var message = "SessionPoller.sessionPing: an API error has occurred:\n";
					message += data.errors.join("\n");
					console.log(message);
				}
			}).fail(function(jqXHR, status, error) {
				if (app.debug) {
					console.log('SessionPoller.sessionPing: restart failed: [' + status + '] ' + error);
				}
			}).always(function() {
				if ( _.isFunction(callback)) {
					callback.call(this, isActive);
				}
			});
		},
		
		sessionStart: function() {
			if (this._poller) {
			//to eliminate multiple calls here, just return if poller timer set
				return;
			}
			
			this._hasSession = true;
			this._hasInitialized = true;
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
					} else if (data.errors && app.debug) {
					//API error in AJAX call
						var message = "SessionPoller.sessionStart: an API error has occurred:\n";
						message += data.errors.join("\n");
						self.sessionDestroy(); //end session
						console.log(message);
					} else {
					//session ended
						window.clearInterval(self._poller);
						label = 'Your Session Has Ended';
						message = 'Your session has ended. You will now be redirected to the login page.';
						Utils.showModalWarning(label, message, self.sessionDestroy, self);
					}
				}).fail(function(jqXHR, status, error) {
					self._hasSession = false;
					if (app.debug) {
						console.log('SessionPoller.sessionStart: poll initialize failed: [' + status + '] ' + error);
					}
				});
			};

			this._poller = window.setInterval(sessionPoller, this._pollingInterval);
		}
	});
	
	return SessionPoller;
});
