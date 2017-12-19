define([
    'config',
    'underscore',
    'backbone',
    'collections/AbstractCollection',
    'models/AbstractModel'
], function(app, _, Backbone, AbstractCollection, AbstractModel) {

    /**
     * Creates a PageableCollection of type Backbone Collection for use with
     * BackGrid table layout and custom jQuery Mobile pagination.
     *
     * @exports collections/FrontCollection
     * @requires config
     * @requires Underscore
     * @requires Backbone
     * @requires Backbone.Paginator
     * @requires collection/AbstractCollection
     * @constructor
     * @augments collection/AbstractCollection
     */
    var FrontCollection = AbstractCollection.extend(
    /** @lends collections/FrontCollection.prototype **/
    {
        /**
         * The Backbone model used in this collection
         *
         * @type {models/AbstractModel}
         */
        model: AbstractModel,


        /**
         * Initializes this collection.
         *
         * @param {models/AdminModel[]} models - The array of models to bootstrap to this collection
         * @param {Object} options - Collection options (Backbone)
         */
        initialize: function(models, options) {
            this.state.pageSize = app.frontPagerPerPage;
            AbstractCollection.prototype.initialize.call(this, models, options);
        },

        /**
         * Overwrites Backbone.PageableCollection.fetch() to allow blocking the call to fetch
         * a collection using a "no_fetch" option.
         *
         * @param {Object} options - Collection options (Backbone)
         */
        fetch: function (options) {
        	options = options || {};
        	if (options.no_fetch) {
				return null;
			} 
            return AbstractCollection.prototype.fetch.call(this, options);
        }
    });

    return FrontCollection;
});