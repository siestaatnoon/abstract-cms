define([
    'config',
    'jquery',
    'underscore',
    'backbone'
], function(app, $, _, Backbone) {

    /**
     * Creates a generic model extended from Backbone.Model.
     *
     * @exports models/AbstractModel
     * @requires config
     * @requires jQuery
     * @requires Underscore
     * @requires Backbone
     * @constructor
     * @augments Backbone.Model
     */
    var AbstractModel = Backbone.Model.extend(
    /** @lends models/AbstractModel.prototype **/
    {
        /**
         * @property {string} deleteUrl
         * URL to delete model on DELETE request.
         */
        deleteUrl: '',

        /**
         * @property {array} fields
         * Array of fields and validation parameters
         */
        fields: [],


        /**
         * Initializes this model.
         *
         * @param {Object} model - The attributes/values object to bootstrap to this model.
         * @param {Object} options - Model options (Backbone).
         */
        initialize: function(attr, options) {
            options = options || {};
            this.idAttribute = options.idAttribute || 'id';
            this.urlRoot = options.urlRoot || '';
            this.fields = options.fields || [];
            //Backbone.Model.prototype.initialize.call(this, attr, options);
        },

        /**
         * Overrides the Backbone.Model.destroy function to use a special URL
         * for the DELETE request.
         *
         * @param {Object} options - options Backbone.Router.navigate (Backbone)
         * @return {XHR} The XHR object
         */
        destroy: function(options) {
            options = options || {};
            var id = this.id === undefined ? this.get(this.collection.idAttribute) : this.id;
            var url = this.url.replace('list', id).replace('add', id).replace('/edit', '');
            var opts = _.extend({ url: url }, options);
            return Backbone.Model.prototype.destroy.call(this, opts);
        },

        /**
         * Overrides the Backbone.Model.isNew function by checking for a non-empty
         * id value instead of just the presence of the id attribute. Also more reliably checks
         * for the model collection idAttribute first.
         *
         * @return {Boolean} True of id attribute exists and has non-empty value
         */
        isNew: function() {
            var attr = this.attributes;
            var idAttribute = this.collection && this.collection.idAttribute ? this.collection.idAttribute : this.idAttribute;
            return ! attr[idAttribute] || attr[idAttribute].length === 0;
        },

        /**
         * Overrides the Backbone.Model.url function. Appends the ID attribute to the defined URL root.
         *
         * @return {String} The model API url
         */
        url: function() {
            var attr = this.attributes;
            return this.urlRoot + (attr[this.idAttribute] ? '/' + attr[this.idAttribute] : (this.id ? '/' + this.id : ''));
        },

        /**
         * Validates the model upon form submission.
         *
         * @param {Object} attributes - The changed attributes on this model.
         * @param {Object} options - Options for validation.
         */
        validate: function(attributes, options) {
            if ( _.isObject(this.fields) ) {
                var validate = {};
                for (field in this.fields) {
                    if (attributes[field] !== undefined && this.fields[field]['valid'] !== undefined) {
                        validate[field] = this.fields[field]['valid'];
                    }
                }

                app.Validator.init(validate);
                var errors = app.Validator.validate(attributes);
                if ( ! _.isEmpty(errors) ) {
                    return errors;
                }
            }
        }
    });

    return AbstractModel;
});