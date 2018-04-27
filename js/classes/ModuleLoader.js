define([
	'config',
	'jquery',
	'underscore',
	'classes/Utils',
    'classes/I18n',
    'classes/Class'
], function(app, $, _, Utils, I18n) {

	/**
	 * Configures the API urls and loads data for application modules.
	 *
	 * @exports classes/admin/ModuleLoader
	 * @requires config
	 * @requires jQuery
	 * @requires Underscore
	 * @requires classes/Utils
     * @requires classes/I18n
	 * @requires classes/Class
	 * @constructor
	 * @augments classes/Class
	 */
	var ModuleLoader = Class.extend({
    /** @lends classes/Class.prototype **/

        /**
         * URL base in REST API call.
         *
         * @type {String}
         */
        _apiBase:		'',

        /**
         * URL of in REST API call.
         *
         * @type {String}
         */
        _apiRoot:		'',

        /**
         * URL base for a module in REST API call.
         *
         * @type {String}
         */
	    _urlRoot:		'',

        /**
         * URL base for module data retrieval in REST API call.
         *
         * @type {String}
         */
        _dataBase:		'',

        /**
         * URL for module data retrieval in REST API call.
         *
         * @type {String}
         */
	    _dataRoot:		'',

        /**
         * Contains the module name parsed from the browser URL.
         *
         * @type {String}
         */
	    _module:		'',

        /**
         * Contains the module tasks (e.g. add, edit, list) parsed from the browser URL.
         *
         * @type {String}
         */
		_task:			'',

        /**
         * Contains associated parameters parsed from the browser URL.
         *
         * @type {String}
         */
	    _params:		'',

        /**
         * Object containing module data after call to REST API via AJAX.
         *
         * @type {Object}
         */
	    _deferredData:	{},

        /**
         * Flag indicating REST API call for module has completed (value is true).
         *
         * @type {Boolean}
         */
	    _hasModule:		false,

        /**
         * Initializes the ModuleLoader.
         *
         */
		init: function(options) {
            options = options || {};
            this._apiBase = options.apiRoot || app.adminApiRoot;
            this._dataBase = options.dataRoot || app.adminDataRoot;
		},

        /**
         * Callback to be overwritten by subclass to handle AJAX errors from this.loadData()
         * call.
         *
		 * @param {Object} jqXHR - The jQuery XHR object from AJAX call
         * @param {Object} deferred - The AJAX promise object (to bind a done() call)
         */
        errorCallback: function(jqXHR, deferred) {
            return false;
        },

        /**
         * Returns the module name parsed from the browser URL.
         *
         * @return {String} The module name
         */
		getModuleName: function() {
			return this._module;
		},

        /**
         * Returns the module parameters parsed from the browser URL.
         *
         * @return {String} The module parameters
         */
		getModuleParams: function() {
			return this._params.length === 0 ? false : this._params.split('/');
		},

        /**
         * Returns the module task parsed from the browser URL.
         *
         * @return {String} The module task
         */
		getModuleTask: function() {
			return this._task;
		},

        /**
         * Performs the REST API call via AJAX to retrieve module data. Callbacks can be
         * performed from the jqXHR promise object returned after the API call.
         *
         * @return {jqXHR} The jQuery XMLHttpRequest object
         */
		loadData: function() {
            var message = '';
            if ( ! this._hasModule) {
                message = I18n.t('error.module.init', 'ModuleLoader.loadData: setModule()');
                console.log(message);
				return false;
			}
			
			var deferred = $.Deferred();
			var self = this;
			var error_label = I18n.t('error');

			$.ajax({
				url : self._apiRoot,
				type: 'GET',
				dataType: 'json'
			}).done(function(data) {
                if (app.debug) {
                    var args = [
                        'ModuleLoader.loadData: [' + self._module + ']',
                        self._apiRoot
                    ];
                    message = I18n.t('message.data.loaded', args);
                    console.log(message);
                }
                self._deferredData = _.extend(self._deferredData, data);
                var clone = _.clone(self._deferredData);
                deferred.resolve(clone);
			}).fail(function(jqXHR) {
                var resp = Utils.parseJqXHR(jqXHR);
                var error = resp.errors.length ? resp.errors.join("\n") : resp.response;

                // NOTE: show alert() instead of modal error since HTML
                // may be contained in this AJAX call and/or modal error
                // may not be set up
                //
                //Utils.showModalWarning('Error', error);
				if (app.isAdmin && error.length) {
                    alert(error);
                    if (app.debug) {
                        console.log(error);
                    }
                }

                self.errorCallback.call(this, jqXHR, deferred);
			});
					
	        return deferred.promise();
		},

        /**
         * Resets this class to a default state before performing the REST API call for module.
         *
         */
		reset: function() {
			this._deferredData = null;
			
			this._module = '';
			this._task = '';
			this._params = '';
			this._urlRoot = '';
			this._dataRoot = '';
			this._apiRoot = '';
			this._deferredData = {};
			this._hasModule = false;
		},

        /**
         * Populates the class variables after performing the REST API call for module.
         *
         * @param {Object} options - The module configuration
         */
		setModule: function(options) {
			this._module = options.module;
			this._task = options.task || this._task;
			this._params = options.params || '';
			this._urlRoot = this._apiBase + '/' + this._module;
			this._dataRoot = this._dataBase + '/' + this._module;
			this._apiRoot = this._urlRoot;
			this._hasModule = true;
		}

	});
	
	return ModuleLoader;
});
