define([
    'config',
    'jquery',
    'underscore'
], function(app, $, _) {

    /**
     * Utility class to add i18n text translations. Utilizes the app.i18n object containing
     * the translations for the configured local in ./js/config.js.
     *
     * @exports classes/I18n
     * @requires config
     * @requires jquery
     * @requires Underscore
     * @constructor
     */
    var I18n = {

        /**
         * Takes a string containing "%s" and replaces those with corresponding items in the
         * args parameter.
         *
         * @param {String} str - The string to convert
         * @param {String|Array|null|undefined} args - The array of strings to replace in str parameter
         * @return {String} The converted string
         */
        sprintf: function(str, args) {
            if ( _.isString(args) || _.isNumber(args) ) {
                args = [args];
            } else if ( _.isArray(args) === false && _.isObject(args) ) {
                args = [ args.toString() ];
            } else if ( ! args) {
                args = [];
            }

            var count = 0;
            return str.replace(/%s/g, function() {
                var arg = '';
                if (args[count] !== undefined) {
                    arg = args[count++];
                }
                return arg;
            });
        },

        /**
         * Checks the app.i18n object for a translation key and, if found, converts the string
         * with "%s" with the args array parameter (if necessary). If not found, the str
         * parameter is returned.
         *
         * @param {String} str - The i18n key
         * @param {String|Array|null} args - The array of strings to replace in translation string
         * @return {String} The i18n translation or str param if translation not found
         */
        t: function(str, args) {
            if ( _.has(app.i18n, str) ) {
                str = this.sprintf(app.i18n[str], args);
            }
            return str;
        }
    };

    return I18n;
});