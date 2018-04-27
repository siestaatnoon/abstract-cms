define([
    'config',
    'jquery',
    'underscore',
    'classes/Utils',
    'classes/I18n',
    'classes/Class'
], function(app, $, _, Utils, I18n) {

    /**
     * Dynamically loads to a page DOM CSS files plus Javascript files and executable code through RequireJS.
     *
     * @exports classes/ScriptLoader
     * @requires config
     * @requires jquery
     * @requires Underscore
     * @requires classes/Utils
     * @requires classes/I18n
     * @constructor
     * @augments classes/Class
     */
    var ScriptLoader = Class.extend({
    /** @lends classes/ScriptLoader.prototype **/

        /**
         * @property {Object} _css
         * Storage for CSS links loaded to page.
         */
        _css: {},

        /**
         * @property {String} _cssIdPrefix
         * Prefix for CSS link tag IDs.
         */
        _cssIdPrefix: 'css-',

        /**
         * @property {String} _cssRoot
         * Web root relative path to CSS scripts to include.
         */
        _cssRoot: '',

        /**
         * @property {String} _defaultCssRoot
         * Default web root relative path to use for CSS includes.
         */
        _defaultCssRoot: '',

        /**
         * @property {String} _defaultJsRoot
         * Default web root relative path to use for Javascript includes.
         */
        _defaultJsRoot: '',

        /**
         * @property {Boolean} _hasCss
         * True if class has loaded CSS links.
         */
        _hasCss: true,

        /**
         * @property {Boolean} _hasJs
         * True if class has loaded Javascript scripts.
         */
        _hasJs: true,

        /**
         * @property {Array} _js
         * Storage for Javascript scripts loaded to page.
         */
        _js: [],

        /**
         * @property {String} _jsRoot
         * Web root relative path to Javascript scripts to include.
         */
        _jsRoot: '',

        /**
         * @property {Boolean} _legacyMode
         * Legacy mode refers to loading a list of script strings in the order given, e.g. from an array,
         * by creating RequireJS shims with an array element and it's previous elements as dependencies. This
         * insured that the scripts would load in order. This class has since been updated to allow an array
         * of includes with objects defining the dependencies, along with strings for the script name.
         */
        _legacyMode: true,

        /**
         * @property {Boolean} _legacyUrlParam
         * Parameter added to a script src URL to mark as loaded via legacy mode.
         */
        _legacyUrlParam: 'plugin=1',

        /**
         * @property {Array} _require
         * RequireJS modules (scripts) to load.
         */
        _require: [],

        /**
         * @property {Object} _requireConfig
         * RequireJS configuration. Note that shims for Javascript includes will be defined here.
         */
        _requireConfig: {},

        /**
         * @property {Object} _requireConfig
         * RequireJS configuration defaults.
         */
        _requireConfigDefaults: {
            paths: {
                'underscore': 'lib/underscore.min'
            },
            shim: {
                'config': {deps: ['jquery'], exports: 'app'},
                'underscore': {deps: ['jquery'], exports: '_'},
                'classes/Utils': ['config', 'jquery', 'underscore']
            }
        },

        /**
         * @property {Array} _requireDefaultModules
         * Default RequireJS modules (scripts) to load. Note that the items listed are already defined
         * in the application configuration file and are accessible in scripts to include.
         */
        _requireDefaultModules: [
            'jquery',
            'config',
            'underscore',
            'classes/Utils',
            'classes/FormValidator'
        ],

        /**
         * @property {String} _requireOnload
         * Javascript executable code to run after includes are loaded. This will include
         * an "onload" block and an "unload" block for a page change.
         */
        _requireOnload: '',


        /**
         * Initializes the ScriptLoader.
         *
         */
        init: function(options) {
            if ( this._hasRequire('init') === false ) {
                return false;
            }
            options = options || {};
            this._defaultJsRoot = app.adminJsRoot;
            this._defaultCssRoot = app.adminCssRoot;
            this._cssRoot = options.cssRoot || this._defaultCssRoot;
            this._jsRoot = options.jsRoot || this._defaultJsRoot;

            if ( this._cssRoot.substr(this._cssRoot.length - 1) !== '/' ) {
                this._cssRoot += '/';
            }
            if ( this._jsRoot.substr(this._jsRoot.length - 1) !== '/' ) {
                this._jsRoot += '/';
            }
            this._legacyMode = options.legacyMode === undefined ? this._legacyMode : options.legacyMode;
        },

        /**
         * Loads CSS link(s) to the page DOM. NOTE, this function can only be called
         * once per page load.
         *
         * @param {(string|string[])} file - The path to the CSS file, or array of paths, relative to this._cssRoot.
         */
        loadCss: function(file) {
            if ( ! file || file.length === 0 ) {
                return;
            }

            var files = typeof file === 'string' ? [file] : file;
            this._css = {};

            for (var i=0; i < files.length; i++) {
                var file = files[i];
                var id = '';
                var isExternal = this._isExternalFile(file);

                if (isExternal) {
                    var parts = file.split('/');
                    var filename = parts[parts.length - 1];
                    id = this._createId(filename, this._cssIdPrefix);
                } else {
                    id = this._createId(file, this._cssIdPrefix);
                }

                if ( ! isExternal && file.substr(0, 1) === '/') {
                    file = file.substr(1);
                }

                if (this._css[id] !== undefined) {
                    //CSS already on page, continue
                    continue;
                }

                this._css[id] = file;
                var link = document.createElement("link");
                link.id = id;
                link.type = "text/css";
                link.rel = "stylesheet";
                link.href = isExternal ? file : this._cssRoot + file + "?v=" + (new Date()).getTime();
                document.head.appendChild(link);
            }

            this._hasCss = true;
        },

        /**
         * Loads Javascript scripts to the page DOM, executes a code block after scripts are loaded and
         * executes a code block upon a page unload. IMPORTANT: Events and plugin objects defined in
         * onload_script MUST be removed here, usually by the jQuery.off() function (events) or a
         * destroy() or similar function defined in a plugin. NOTE, this function can only be called
         * once per page load
         *
         * @param {(string|string[])} scripts - The path to the Javascript file, or array of paths, relative to this._jsRoot.
         * Array may also contain objects of { "script file": [array of dependencies (file paths)] } for scripts with
         * dependencies.
         * @param {String} onload_script - Code to execute after scripts are loaded.
         * @param {String} unload_script - Code to execute upon unloading of the page.
         */
        loadJs: function(scripts, onload_script, unload_script) {
            if ( this._hasRequire('loadJs') === false ) {
                return;
            } else if ( ! scripts && ! onload_script && ! unload_script) {
            //no scripts to load and no onload functions
                return;
            }

            this._js = [];
            this._require = this._requireDefaultModules.slice(0);
            this._requireConfig = JSON.parse( JSON.stringify(this._requireConfigDefaults) );

            // Since loading js may affect controls on the page and
            // how content is rendered, this will add an overlay
            // to temporarily disable controls while the js loads
            //
            // The overlay is removed at the end of the require.js
            // execute block (see line 176) within the load script
            // generated by this class
            //
            Utils.showOverlay();

            if (typeof scripts === 'string') {
                scripts = [scripts];
            }

            for (var i=0; i < scripts.length; i++) {
                var file = '';
                var depend = [];
                if (typeof scripts[i] === 'string') {
                    file = scripts[i];
                } else {
                    file = Object.keys(scripts[i])[0];
                    depend = scripts[i][file];
                }
                if (this._js.indexOf(file) !== -1) {
                // script already set to load, continue
                    continue;
                }

                if (this._legacyMode) {
                    depend = this._js.slice(0);
                }

                if ( this._isExternalFile(file) ) {
                    this._js.push(file);
                } else {
                    if (file.substr(0, 1) === '/') {
                        file = file.substr(1);
                    }
                    this._js.push(file);
                    file = this._jsRoot + file;
                }

                if (depend.length) {
                    for (var j=0; j < depend.length; j++) {
                        if ( this._isExternalFile(depend[j]) === false ) {
                            depend[j] = this._jsRoot + depend[j];
                        }
                    }
                    this._requireConfig.shim[file] = depend;
                }

                this._require.push(file);
            }

            if (this._legacyMode) {
                this._requireConfig.urlArgs = "_=" +  (new Date()).getTime() + '&' + this._legacyUrlParam;
            }

            var self = this;
            this._setOnload(onload_script, unload_script);
            require.config(this._requireConfig);
            require(this._require, function($, app, _, Utils) {
                if (self._requireOnload.length) {
                    if (app.debug) {
                        console.log(self._requireOnload);
                    }
                    var func = new Function('$', 'app', '_', 'Utils', self._requireOnload);
                    func.call(this, $, app, _, Utils);
                }
                Utils.hideOverlay();
            });

            this._hasJs = true;
        },

        /**
         * Removes CSS link tag(s) from the page DOM.
         *
         * @param {(string|string[])} css - The CSS paths to remove, or array of paths, relative to this._cssRoot.
         */
        removeCss: function(css) {
            if ( ! css || css.length === 0 ) {
                return false;
            }

            if (typeof css === 'string') {
                css = [css];
            }

            // trigger CSS unload event
            if (this._hasCss) {
                $(window).trigger('css:unload');
                this._hasCss = false;
            }

            for (var i=0; i < css.length; i++) {
                var file = css[i];
                if ( this._isExternalFile(file) === false && file.substr(0, 1) === '/') {
                    file = file.substr(1);
                }
                for (var id in this._css) {
                    if (this._css[id] === file) {
                        this._removeElement(id);
                        delete this._css[id];
                        break;
                    }
                }
            }
        },

        /**
         * Removes Javascript script tag(s) from the page DOM and executes the defined "unload" script.
         *
         * @param {(string|string[])} js - The path to the Javascript file to remove, or array of paths,
         * relative to this._jsRoot. Array may also contain objects of
         * { "script file": [array of dependencies (file paths)] } for scripts with dependencies. Dependencies
         * are removed recursively.
         */
        removeJs: function(js) {
            if (this._hasRequire('removeJs') === false || this._legacyMode || ! js || js.length === 0 ) {
                return;
            } else if (js.constructor !== Array) {
                js = [js];
            }

            //trigger event to notify js BEFORE unloading scripts
            if (this._hasJs) {
                $(window).trigger('javascript:unload');
                this._hasJs = false;
            }

            for (var i=js.length-1; i >= 0; i--) {
                var module = js[i];
                if (typeof module !== 'string') {
                    module = Object.keys(module)[0];
                }

                var tocheck = module;
                if ( this._isExternalFile(module) === false ) {
                    if ( module.substr(0, this._jsRoot.length) === this._jsRoot ) {
                    // need to strip js rootpath if there
                        module = module.substr(this._jsRoot.length);
                    }
                    if ( module.substr(0, 1) === '/' ) {
                        module = module.substr(1);
                    }
                    tocheck = module;
                    module = this._jsRoot + module;
                }

                var index = this._js.indexOf(tocheck);
                if (index !== -1) {
                // delete from script storage
                    this._js.splice(index, 1);
                }

                if ( this._requireConfig.shim[module] !== undefined ) {
                    if ( this._requireConfig.shim[module].constructor === Array ) {
                    // first remove dependencies
                        this.removeJs(this._requireConfig.shim[module]);
                    }
                    delete this._requireConfig.shim[module];
                }

                index = this._require.indexOf(module);
                if (index !== -1) {
                    this._require.splice(index, 1);
                }

                // remove js scripts loaded to page (using RequireJS)
                var scripts = document.getElementsByTagName('script');
                require.undef(module);
                for (var j=0; j < scripts.length; j++) {
                    var script = scripts[j];
                    if (script.getAttribute('data-requiremodule') === module) {
                        script.parentNode.removeChild(script);
                        break;
                    }
                }
            }

            // workaround to remove require modules loaded from plugins
            //this._removePlugins();

            // reset the onload script
            this._requireOnload = '';
        },

        /**
         * Sets the CSS root directory for includes.
         *
         * @param {String} dir - The path to the CSS root directory.
         */
        setCssRoot: function(dir) {
            if ( dir && dir.substr(dir.length - 1) !== '/' ) {
                dir += '/';
            }
            this._cssRoot = dir || this._defaultCssRoot;
        },

        /**
         * Sets the Javascript root directory for script includes.
         *
         * @param {String} dir - The path to the Javascript root directory.
         */
        setJsRoot: function(dir) {
            if ( this._hasRequire('setJsRoot') === false ) {
                return;
            }

            if ( dir && dir.substr(dir.length - 1) !== '/' ) {
                dir += '/';
            }
            this._jsRoot = dir || this._defaultJsRoot;
        },

        /**
         * Trigger execution of the defined "unload" script.
         *
         */
        triggerUnload: function() {
            $(window).trigger('page:unload');
        },


        /**
         * Removes all CSS link tags and Javascript script tags from the page DOM, loaded
         * through this class, and executes the defined "unload" script.
         */
        unload: function() {
            if ( this._hasRequire('unload') === false || ( ! this._hasCss && ! this._hasJs) ) {
                return;
            }

            //trigger event to notify CSS, js and page unloading
            $(window).trigger('css:unload');
            $(window).trigger('javascript:unload');
            this.triggerUnload();

            //remove all css links loaded to page
            for (var id in this._css) {
                this._removeElement(id);
            }

            // remove js scripts loaded to page (using RequireJS)
            for (var i=0; i < this._js.length; i++) {
                var module = this._js[i];
                if ( this._isExternalFile(module) === false ) {
                    if ( module.substr(0, 1) === '/' ) {
                        module = module.substr(1);
                    }
                    module = this._jsRoot + module;
                }

                if (this._requireConfig.shim[module] !== undefined && this._requireConfig.shim[module].constructor === Array ) {
                // first remove dependencies
                    this.removeJs(this._requireConfig.shim[module]);
                    delete this._requireConfig.shim[module];
                }

                var index = this._require.indexOf(module);
                if (index !== -1) {
                    this._require.splice(index, 1);
                }


                var scripts = document.getElementsByTagName('script');
                for (var j = scripts.length - 1; j >= 0; j--) {
                    var script = scripts[j];
                    if (script.getAttribute('data-requiremodule') === module) {
                        require.undef(module);
                        script.parentNode.removeChild(script);
                        break;
                    }
                }
            }

            // workaround to remove require modules loaded from plugins
            //
            // TODO: really need this?
            //
            //this._removePlugins();

            // reset the class vars
            this._requireConfig = {};
            this._require = [];
            this._requireOnload = '';
            this._css = {};
            this._js = [];
            this._hasCss = false;
            this._hasJs = false;
        },

        /**
         * Creates an id attribute value from a filename.
         *
         * @param {String} filename - The filename.
         * @param {String} prefix - String to prefix the id value.
         * @return {String} The id value.
         */
        _createId: function(filename, prefix) {
            return prefix +
                filename.toLowerCase()
                    .replace(/.js/g, '')
                    .replace(/.css/g, '')
                    .replace(/\./g, '-')
                    .replace(/\//g, '-');
        },

        /**
         * Returns true if RequireJS is loaded in page DOM. If not loaded, will print
         * an error message the the console if the application is configured for it.
         *
         * @param {String} funcName - The function name for debugging.
         * @return {Boolean} True if RequireJS is loaded.
         */
        _hasRequire: function(funcName) {
            funcName = funcName || '_hasRequire';
            var hasRequire = typeof require === "function";
            if ( ! hasRequire) {
                console.log( I18n.t('error.requirejs', 'ScriptLoader.' + funcName + ':') );
            }
            return hasRequire;
        },

        /**
         * Checks if a filename is from an external website.
         *
         * @param {String} filename - The filename.
         * @return {Boolean} True if filename from external website.
         */
        _isExternalFile: function(filename) {
            return typeof filename === 'string' &&
                (filename.substr(0, 7) === 'http://' ||
                filename.substr(0, 8) === 'https://' ||
                filename.substr(0, 2) === '//');
        },

        /**
         * Removes an element, by id attribute, from the page DOM.
         *
         * @param {String} elID - The element id (NOT prefixed with "#").
         */
        _removeElement: function(elID) {
            var el = document.getElementById(elID);
            if (el) {
                el.parentNode.removeChild(el);
            }
        },

        /**
         * Workaround function to remove remaining dependencies leftover when calling this.unload()
         * in legacy mode. Scripts loaded in legacy mode may contain RequireJS dependencies themselves
         * which are not removed while in legacy mode. Rather crude but works for now.
         *
         */
        _removePlugins: function() {
            if (this._legacyMode) {
                var scripts = document.getElementsByTagName('script');
                for (var j = 0; j < scripts.length; j++) {
                    var script = scripts[j];
                    var src = script.getAttribute('src');
                    var reqmodule = script.getAttribute('data-requiremodule');
                    if (reqmodule && src && src.indexOf(this._legacyUrlParam) !== -1) {
                        require.undef(reqmodule);
                        script.parentNode.removeChild(script);
                    }
                }
            }
        },

        /**
         * Sets the RequireJS onload script to execute after Javascript scripts are loaded. Note
         * this script contains the "onload" and "unload" scripts defined in this class.
         *
         * @param {String} onload_script - The defined "onload" script to run.
         * @param {String} unload_script - The defined "unload" script to run.
         * @return {String/Boolean} The RequireJS script or false if RequireJS not loaded.
         */
        _setOnload: function(onload_script, unload_script) {
            if ( this._hasRequire('_setOnload') === false ) {
                return false;
            }

            if (onload_script.length > 0) {
                var code = "\ttry {\n\n";
                code += onload_script;
                if (unload_script.length > 0) {
                    code += "\n\n$(window).on('page:unload', function(e){\n";
                    code += unload_script + "\n";
                    code += "$(this).off('page:unload');\n";
                    code += "});\n";
                } else {
                    console.log( I18n.t('warning.unload', 'ScriptLoader.loadJs: ') );
                }
                code += "\n\t} catch(e) {\n\t\tif (app.debug) console.log(e.message);\n\t} finally {\n";
                //code += "\t\tUtils.hideOverlay();\n";
                code += "\t}\n";
                this._requireOnload = code;
            }
        }
    });

    return ScriptLoader;
});
