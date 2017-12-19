define([
    'config',
    'jquery',
    'classes/ModuleLoader'
], function(app, $, ModuleLoader) {

    /**
     * Extends classes/ModuleLoader, sets up the REST API url for frontend AJAX calls.
     *
     * @exports classes/front/FrontModuleLoader
     * @requires config
     * @requires jQuery
     * @requires Underscore
     * @requires Backbone
     * @requires classes/ModuleLoader
     * @constructor
     * @augments classes/ModuleLoader
     */
    var FrontModuleLoader = ModuleLoader.extend({
        /** @lends classes/ModuleLoader.prototype **/

        /**
         * Initializes the FrontModuleLoader.
         *
         */
        init: function(options) {
            this._super(options);
        },

        /**
         * Handles AJAX errors from the this.loadData() call. For frontend, a template
         * is included and rendered in the Backbone views.
         *
         * @param {Object} jqXHR - The jQuery XHR object from AJAX call
         * @param {Object} deferred - The AJAX promise object (to bind a done() call)
         */
        errorCallback: function(jqXHR, deferred) {
            this._super(jqXHR);
            var response = jqXHR.responseJSON;
            deferred.resolve(response);
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
         * Resets this class to a default state before performing the REST API call for module.
         *
         */
        reset: function() {
            this._super();
        },

        /**
         * Populates the class variables after performing the REST API call for module.
         *
         */
        setModule: function(options) {
            this._super(options);
            if (this._task.length) {
                this._apiRoot += '/' + this._task;
            }
            if (this._params.length) {
                this._apiRoot += '/' + this._params;
            }
        }
    });

    return FrontModuleLoader;
});
