define([
	'config',
    'jquery',
	'underscore',
	'backbone',
	'models/AbstractModel'
], function(app, $, _, Backbone, AbstractModel) {
	
	/**
	 * Creates an admin module model extended from Backbone.Model.
	 *
	 * @exports models/AdminModel
	 * @requires config
     * @requires jQuery
	 * @requires Underscore
	 * @requires Backbone
     * @requires models/AbstractModel
	 * @constructor
	 * @augments AbstractModel
	 */
	var AdminModel = AbstractModel.extend(
	/** @lends models/AdminModel.prototype **/
	{
		/**
		 * Initializes this model.
		 *
		 * @param {Object} model - The attributes/values object to bootstrap to this model.
		 * @param {Object} options - Model options (Backbone).
		 */
		initialize: function(attr, options) {
			options = options || {};
            this.idAttribute = options.idAttribute || 'id';
            this.fields = options.fields || [];
			this.url =  options.url || '';
            //AdminModel.prototype.initialize.call(this, attr, options);
		},
		
		/**
		 * Overrides the Backbone.Model.isNew function by checking for a non-empty
		 * id value instead of just the presence of the id attribute. Also more reliably checks
         * for the model collection idAttribute first.
		 *
		 * @return {Boolean} True of id attribute exists and has non-empty value
		 */
		isNew: function() {
			//	return ! this.has(this.id) && ( _.isUndefined(this.collection) || 
			// ! this.has(this.collection.idAttribute) ) && Backbone.Model.prototype.isNew.call(this);

            var attr = this.attributes;
			var idAttribute = this.collection && this.collection.idAttribute ? this.collection.idAttribute : this.idAttribute;
			return _.isUndefined(attr[idAttribute]) || attr[idAttribute].toString().length === 0;
		},

        /**
         * Overrides the Backbone.Model.url function. Appends the ID attribute to the defined URL root.
         *
         * @return {String} The model API url
         */
        url: function() {
            return this.url;
        },
	});
	
	return AdminModel;
});