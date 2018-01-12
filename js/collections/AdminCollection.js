define([
	'config',
	'underscore',
	'backbone',
	'backbone.paginator',
	'models/AdminModel',
    'collections/AbstractCollection'
], function(app, _, Backbone, PageableCollection, AdminModel, AbstractCollection) {
	
	/**
	 * Creates a PageableCollection of type Backbone Collection for use with
	 * BackGrid table layout and custom jQuery Mobile pagination.
	 *
	 * @exports collections/AdminCollection
	 * @requires config
	 * @requires Underscore
	 * @requires Backbone
	 * @requires Backbone.Paginator
	 * @requires models/AdminModel
	 * @requires collections/AbstractCollection
	 * @constructor
	 * @augments AbstractCollection
	 */
	var AdminCollection = AbstractCollection.extend(
	/** @lends collections/AdminCollection.prototype **/
	{
		/**
		 * Object containing parameters to allow for toggle in Collection list view.
		 *
		 * @type {Object}
		 */
		bulkUpdate: {
			useActive: false,
			useArchive: false,
			useDelete: false,
			isArchive: false
		},
		
		/**
		 * Column names to be used in table to display collection.
		 *
		 * @type {Object}
		 */
		columns: {},

		/**
		 * The Backbone model used in this collection
		 * 
		 * @type {models/AdminModel}
		 */
		model: AdminModel,

		/**
		 * Mode used for Backgrid pagination in table view. Note that "server"
		 * chosen for most reliable performance
		 *
		 * @type {String}
		 */
		mode: 'server',

    
		/**
		 * Initializes this collection.
		 *
		 * @param {models/AdminModel[]} models - The array of models to bootstrap to this collection
		 * @param {Object} options - Collection options (Backbone)
		 */
		initialize: function(models, options) {
			var options = options || {};
			this.state.pageSize = app.adminPagerPerPage;
            AbstractCollection.prototype.initialize.call(this, models, options);
		},
		
		/**
		 * Overrides AbstractCollection.parseState(). Retrieves the pagination state from the server
         * response and formats it for use with Backbone.PageableCollection, saving additional parameters
         * for bulk list updates.
		 *
		 * @param {Array} response - The server response
		 * @param {Object} queryParams A copy of #queryParams
		 * @param {Object} state A copy of #state
		 * @param {Object} options - Collection options (Backbone)
		 */
		parseState: function (response, queryParams, state, options) {
			this.columns = response.columns;
			this.bulkUpdate.useActive = response.bulk_update.use_active;
			this.bulkUpdate.useArchive = response.bulk_update.use_archive;
			this.bulkUpdate.useDelete = response.bulk_update.use_delete;
			this.bulkUpdate.isArchive = response.bulk_update.is_archive;
			return AbstractCollection.prototype.parseState.call(this, response, queryParams, state, options);
		}
	});
	
	return AdminCollection;
});