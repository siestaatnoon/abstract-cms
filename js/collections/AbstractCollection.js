define([
    'config',
    'underscore',
    'backbone',
    'backbone.paginator',
    'models/AbstractModel'
], function(app, _, Backbone, PageableCollection, AbstractModel) {

    /**
     * Creates a PageableCollection of type Backbone Collection for use with
     * BackGrid table layout and custom jQuery Mobile pagination.
     *
     * @exports collections/AbstractCollection
     * @requires config
     * @requires Underscore
     * @requires Backbone
     * @requires Backbone.Paginator
     * @requires models/AbstractModel
     * @constructor
     * @augments Backbone.PageableCollection
     */
    var AbstractCollection = Backbone.PageableCollection.extend(
    /** @lends collections/AbstractCollection.prototype **/
    {

        /**
         * ID attribute for models of this collection.
         *
         * @type {String}
         */
        idAttribute: '',

        /**
         * The Backbone model used in this collection
         *
         * @type {models/AbstractModel}
         */
        model: AbstractModel,

        /**
         * Mode used for Backgrid pagination in table view. Note that "server"
         * chosen for most reliable performance
         *
         * @type {String}
         */
        mode: 'server',

        /**
         * Query params for module list filters
         *
         * @type {Object}
         */
        queryParams: {
            filters: {}
        },

        /**
         * Initial state of Backgrid collection display table
         *
         * @type {Object}
         */
        state: {
            pageSize: 10,
            sortKey: 'id',
            order: -1
        },

        /**
         * Initializes this collection.
         *
         * @param {models/AdminModel[]} models - The array of models to bootstrap to this collection
         * @param {Object} options - Collection options (Backbone)
         */
        initialize: function(models, options) {
            var options = options || {};
            this.idAttribute = options.idAttribute || 'id';
            this.url = options.url || '';
        },

        /**
         * Overwrites Backbone.PageableCollection.parseRecords(). Parses the server response
         * from call to Collection.fetch().
         *
         * @param {Array} response - The server response
         * @param {Object} options - Collection options (Backbone)
         */
        parseRecords: function (response, options) {
            return Backbone.PageableCollection.prototype.parseRecords.call(this, response.items, options);
        },

        /**
         * Overrides Backbone.PageableCollection.parseState(). Retrieves the pagination state
         * from the server response and formats it for use with Backbone.PageableCollection.
         *
         * @param {Array} response - The server response
         * @param {Object} queryParams A copy of #queryParams
         * @param {Object} state A copy of #state
         * @param {Object} options - Collection options (Backbone)
         */
        parseState: function (response, queryParams, state, options) {
            this.queryParams.filters = _.extend(this.queryParams.filters, response.query_params);
            var resp = [response.state, response.items];
            return Backbone.PageableCollection.prototype.parseState.call(this, resp, queryParams, state, options);
        }
    });

    return AbstractCollection;
});