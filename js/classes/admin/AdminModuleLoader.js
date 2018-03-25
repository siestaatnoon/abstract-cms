define([
	'config',
	'jquery',
	'classes/ModuleLoader'
], function(app, $, ModuleLoader) {

	/**
	 * Extends classes/ModuleLoader, sets up REST API URLs for admin CMS AJAX from the browser page URL.
	 *
	 * @exports classes/admin/AdminModuleLoader
	 * @requires config
	 * @requires jQuery
	 * @requires Underscore
	 * @requires Backbone
	 * @requires classes/ModuleLoader
	 * @constructor
	 * @augments classes/ModuleLoader
	 */
	var AdminModuleLoader = ModuleLoader.extend({
    /** @lends classes/ModuleLoader.prototype **/

        /**
         * URL segment corresponding to a PUT call in REST API.
         *
         * @type {String}
         */
	    _putTask: 		'add',

        /**
         * URL segments corresponding to a POST call in REST API.
         *
         * @type {Array}
         */
	    _postTasks: 	['edit', 'sort', 'update'],

        /**
         * URL segments corresponding to a GET call in REST API.
         *
         * @type {Array}
         */
		_getTasks: 		['list', 'filter'],

        /**
         * Contains the item ID value in a GET call in the REST API.
         *
         * @type {String}
         */
		_id: 			'',


        /**
         * Initializes the AdminModuleLoader.
         *
         */
		init: function() {
			this._super();
        },

        /**
         * Returns the module name parsed from the browser URL.
         *
         * @return {String} The module name
         */
		getModuleName: function() {
			return this._super();
		},

        /**
         * Returns the module parameters parsed from the browser URL.
         *
         * @return {String} The module parameters
         */
		getModuleParams: function() {
			return this._super();
		},

        /**
         * Returns the module task parsed from the browser URL.
         *
         * @return {String} The module task
         */
		getModuleTask: function() {
			return this._super();
		},

        /**
         * Call the parent class to perform the REST API call via AJAX to retrieve module data.
         *
         * @return {jqXHR} The jQuery XMLHttpRequest object
         */
		loadData: function() {
			return this._super();
		},

        /**
         * Returns true if browser request URL is one of the default GET, PUT or POST tasks for module.
         *
         * @return {Boolean} True if default module task
         */
		isDefaultTask: function() {
			return this.isGetTask() || this.isPostTask() || this.isPutTask();
		},

        /**
         * Returns true if browser request URL is one of the default GET, PUT or POST tasks for module.
         *
         * @return {Boolean} True if default module task
         */
		isGetTask: function() {
			return this._getTasks.indexOf(this._task) >= 0;
		},

        /**
         * Returns true if browser request URL is a POST task for module.
         *
         * @return {Boolean} True if module POST task
         */
		isPostTask: function() {
			return this._postTasks.indexOf(this._task) >= 0;
		},

        /**
         * Returns true if browser request URL is a PUT task for module.
         *
         * @return {Boolean} True if module PUT task
         */
		isPutTask: function() {
			return this._task == this._putTask;
		},

        /**
         * Resets this class to a default state before performing the REST API call for module.
         *
         */
		reset: function() {
			this._super();
			this._id = '';
		},

        /**
         * Populates the class variables after performing the REST API call for module.
         *
         */
		setModule: function(options) {
			this._super(options);
            this._apiRoot = this._dataRoot;
			if (this._params.length > 0) {
				var p = this._params.split('/');
				if ( this.isPostTask() && ! isNaN( parseInt(p[0]) ) ) {
					this._id = p[0];
					p.shift();
				}
				this._params = p.length ? p.join('/') : '';
			}

			if ( this.isGetTask() ) {
				this._apiRoot += '/list' + (this._params.length > 0 ? '/' + this._params : '');
			} else if (this._task === 'sort') {
				this._apiRoot += '/sort' + (this._params.length > 0 ? '/' + this._params : '');
			} else {
				this._apiRoot += '/form';
				if (this._id.length > 0) {
					this._apiRoot += '/' + this._id;
				}
			}
			this._deferredData['collection_url'] = this._getCollectionURL();
			this._deferredData['model_url'] = this._getModelURL();
		},

        /**
         * Returns the REST API URL to retrieve module records.
         *
         * @return {String} The API URL
         */
		_getCollectionURL: function() {
			var url = this._urlRoot + '/list';

			if (this._params.length > 0) {
				url += '/' + this._params;
			}

			return url;
		},

        /**
         * Returns the REST API URL to retrieve a specific module record.
         *
         * @return {String} The API URL
         */
		_getModelURL: function() {
			var url = this._urlRoot;
			url += this._id.length > 0 ? '/edit/' + this._id : (this._task === 'update' ? '/update' : '/add');
			if (this._params.length > 0) {
				url += '/' + this._params;
			}	
			return url;
		}
		
	});
	
	return AdminModuleLoader;
});
