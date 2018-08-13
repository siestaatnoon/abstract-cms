define([
    'underscore',
    'backbone',
    'backgrid',
    'classes/I18n',
    'backbone.paginator'
], function(_, Backbone, Backgrid, I18n) {

  "use strict";

	  /**
	   * Class extending the Backgrid.Paginator to suit this application.
       *
       * TODO: 1. isMobile? 2. UL list pagination option (default) 3. onRender, onPreRender for collection
	   *
	   * @exports Backgrid.Extension.Paginator
       * @requires Underscore
       * @requires Backbone
       * @requires Backgrid
       * @requires classes/I18n
       * @requires backgrid.paginator
       * @constructor
       * @augments Backbone.View
	   */
	var Paginator = Backgrid.Extension.Paginator = Backbone.View.extend(
    /** @lends Backgrid.Extension.Paginator.prototype **/
	{

        /**
         * Class name of DOM container for pagination.
         *
         * @type {String}
         */
	    className: "abstract-paginator",

        /**
         * Backbone events for pagination.
         *
         * @type {Object}
         */
	    events: {
	      "click a.pager-control": "changePage",
	      "click a.pager-selector": "changePage",
	      "change select.pager-selector": "changePage",
	      "change select.pager-size": "setPerPage"
	    },

        /**
         * Class used for active pagination link (numeric).
         *
         * @type {String}
         */
	    activeClass: 'active',

        /**
         * Attributes for pagination controls.
         *
         * @type {Object}
         */
	    attr: {
			first: {
				class: ['pager-control', 'page-first'],
				title: I18n.t('first')
			},
			prev: {
				class: ['pager-control', 'page-prev'],
				title: I18n.t('previous')
			},
			page: {
				class: ['pager-selector']
			},
			next: {
				class: ['pager-control', 'page-next'],
				title: I18n.t('next')
			},
			last: {
				class: ['pager-control', 'page-last'],
				title: I18n.t('last')
			},
			perPage: {
				class: ['pager-size']
			}
		},

        /**
         * Class attributes to add to pagination container in DOM.
         *
         * @type {Array}
         */
		classAttr: [],

        /**
         * Class used to disable pagination control.
         *
         * @type {String}
         */
		disabledClass: 'disabled',

        /**
         * Flag if user device is a mobile or tablet.
         *
         * @type {Boolean}
         */
		isMobile: false,

        /**
         * Attributes for first, previous, next and last pagination controls.
         *
         * @type {Object}
         */
		listProps: {
			first: { text:'&laquo;', title: I18n.t('first') },
			prev: { text:'<', title: I18n.t('previous') },
			next: { text:'>', title: I18n.t('next') },
			last: { text:'&raquo;', title: I18n.t('last') },
		},

        /**
         * Type of pagination depending on device, mobile/tablet: list, desktop: table.
         *
         * @type {String}
         */
		pagerType: 'list',

        /**
         * Initializer.
         *
         * @param {Object} options - View options (Backbone)
         */
	    initialize: function(options) {
	    	var options = options || {};
	    	var self = this;
	    	
	    	if (options.attr) {
				this._mergeAttr(options.attr);
	    	}
	    	if (options.activeClass) {
				this.activeClass = options.activeClass;
	    	}
	    	if (options.classAttr) {
				var classAttr = _.isArray(options.classAttr) ? options.classAttr.join(' ') : options.classAttr;
				this.$el.addClass(classAttr);
	    	}
	    	if (options.disabledClass) {
				this.disabledClass = options.disabledClass;
	    	}

			this.isMobile = options.isMobile || false;
			this.pagerType = options.pagerType && options.pagerType === 'table' ? 'table' : 'list';
			
			this.listenTo(this.collection, "add", this.render);
			this.listenTo(this.collection, "remove", this.render);
			this.listenTo(this.collection, "reset", this.render);
			this.listenTo(this.collection, "backgrid:sorted", function() { self._refresh(); } );
			
	    },

        /**
         * Event handler for pagination controls.
         *
         * @param {jQuery} e - The jQuery event object
         */
	    changePage: function(e) {
			e.preventDefault();
			this.trigger('view:update:start');
			var $target = $(e.target);
			var self = this;
			
			if ( ! $target.hasClass(this.activeClass) && ! $target.hasClass(this.disabledClass) ) {
				if ( $target.hasClass('page-first') ) { 
					this.collection.getFirstPage({ reset: true, success: function() { self._refresh(); } });
	        	} else if ( $target.hasClass('page-prev') ) {
	        		this.collection.getPreviousPage({ reset: true, success: function() { self._refresh(); } });
				} else if ( $target.hasClass('page-next') ) {
					this.collection.getNextPage({ reset: true, success: function() { self._refresh(); } });
				} else if ( $target.hasClass('page-last') ) {
					this.collection.getLastPage({ reset: true, success: function() { self._refresh(); } });
				} else if ( $target.hasClass('pager-selector') ) {
					var page = $target.data('page');
					if ( isNaN(page) ) {
						page = parseInt( $target.val() );
					}
					if ( isNaN(page) === false) {
						this.collection.getPage(page, {
							reset: true,
							success: function() { self._refresh(); }
						});
					}
				}
			}
			return this;
	    },

        /**
         * Renders the pagination HTML.
         *
         * @return {Backgrid.Extension.Paginator} Reference to this object
         */
	    render: function () {
			this.$el.empty();
			if (this.pagerType === 'table') {
				this._pagerTable();
			} else {
				this._pagerList();
			}
			this.delegateEvents();
			return this;
	    },

        /**
         * Event handler for the per page selector. Updates the number of list items showing
         * in the page.
         *
         * @return {Backgrid.Extension.Paginator} Reference to this object
         */
	    setPerPage: function(e) {
	    	this.trigger('view:update:start');
			var perPage = $(e.target).val();
			var self = this;
			this.collection.setPageSize(perPage, {
				first: true,
				reset: true,
				success: self._refresh
			});
			return this;
		},

        /**
         * Creates a jQuery link object for a first, previous, next or last control and
         * adds its attributes and disabling it if needed.
         *
         * @return {jQuery} The link as a jQuery object
         */
		_controlLink: function(type) {
			var $link = $('<a/>');
			if ( _.isObject(this.attr[type]) ) {
				$link = this._setAttr($link, this.attr[type]);
				if (((type === 'first' || type === 'prev') && ! this.collection.hasPreviousPage()) ||
					((type === 'last' || type === 'next') && ! this.collection.hasNextPage())) {
					$link.addClass(this.disabledClass);
				}
			}
			return $link;
		},

        /**
         * Merges additional attributes to the default first, previous, next and last controls.
         *
         */
		_mergeAttr: function(attr) {
			if ( _.isObject(attr) ) {
				for (var prop in attr) {
					var control = attr[prop];
					if ( _.isUndefined(control) === false ) {
						for (var att in control) {
							var value = control[att];
							if (att === 'class') {
								if ( _.isString(value) && this.attr[prop][att].indexOf(value) === -1 ) {
									this.attr[prop][att].push(value);
								} else if ( _.isArray(value) ) {
									this.attr[prop][att] = _.union(this.attr[prop][att], value);
								}
							} else {
								this.attr[prop][att] = value;
							}
						}
					}
				}
			}
		},

        /**
         * Creates a jQuery select element used for a pagination jumpmenu.
         *
         * @return {jQuery} The select element as a jQuery object
         */
		_pageSelector: function() {
			var $select = this._setAttr($('<select/>'), this.attr['page']);
			var state = this.collection.state;
			var currentPage = state.currentPage;
			var totalPages = state.totalPages;
			
			for (var i=1; i <= totalPages; i++) {
				var is_selected = i === currentPage;
				$('<option/>', {
					text: i,
					val: i,
					selected: is_selected
				}).appendTo($select);
			}
			
			var $pre = $.parseHTML( I18n.t('paginate.goto') + ': ');
			var $post = $.parseHTML(' ' + I18n.t('of') + ' ' + totalPages);
			var $div = $('<div/>', { class: 'pager-page-cnt' });
			$div.append($pre).append($select).append($post);
			return $div;
		},

        /**
         * Creates the pagination HTML (jQuery) and adds it to this View. Used
         * for mobile list view.
         *
         * @return {jQuery} The pagination HTML as a jQuery object
         */
		_pagerList: function() {
			var $ul = $('<ul/>');

			for (var prop in this.attr) {
				var $element = null;
				if (prop === 'perPage') {
					continue;
				} else if (prop === 'page') {
					var state = this.collection.state;
					var currentPage = state.currentPage;
					var totalPages = state.totalPages;
					
					for (var i=1; i <= totalPages; i++) {
						var $li = $('<li/>');
						var $link = this._controlLink(prop);
						$link.text(i).attr('title', I18n.t('page') + ' ' + i).attr('data-page', i);
						if (i === currentPage) {
							$link.addClass(this.activeClass);
						}
						$link.appendTo($li);
						$li.appendTo($ul);
					}
				} else {
					var $li = $('<li/>');
					var p = this.listProps[prop];
					var $link = this._controlLink(prop);
					$link.text(p.text).attr('title', p.title);
					$link.appendTo($li);
					$li.appendTo($ul);
				}
			}
			$ul.appendTo(this.$el);
		},

        /**
         * Creates the pagination HTML (jQuery) and adds it to this View. Used
         * for table list view.
         *
         * @return {jQuery} The pagination HTML as a jQuery object
         */
		_pagerTable: function() {
			for (var prop in this.attr) {
				var $element = null;
				if (prop === 'page') {
					$element = this._pageSelector();
				} else if (prop === 'perPage') {
					$element = this._perPageSelector();
				} else {
					$element = this._controlLink(prop);
				}
				$element.appendTo(this.$el);
			}
		},

        /**
         * Creates a jQuery select menu used as a jumpmenu to change the number of list items showing.
         *
         * @return {jQuery} The select menu as a jQuery object
         */
		_perPageSelector: function() {
			var $select = this._setAttr($('<select/>'), this.attr['perPage']);
			var state = this.collection.state;
			var pageSize = state.pageSize;
			var totalRecords = state.totalRecords;
			var steps = [];
			
			if (this.isMobile) {
                if (totalRecords > 20) steps.push(5);
                if (totalRecords > 50) steps.push(10);
                if (totalRecords > 100) steps.push(20);
                if (totalRecords > 500) steps.push(50);
			} else {
				if (totalRecords > 20) steps.push(20);
				if (totalRecords > 50) steps.push(50);
				if (totalRecords > 100) steps.push(100);
				if (totalRecords > 500) steps.push(500);
				steps.push(totalRecords);
			}
			
			if ( ! this.isMobile && steps.length === 1) {
				$select.prop('disabled', true);
			}
			
			for (var i=0; i < steps.length; i++) {
				var step = steps[i];
				var text = i < steps.length - 1 || this.isMobile ?
                           step + ' ' + I18n.t('per.page') :
					       I18n.t('view.all');
				var is_selected = step === pageSize;
				$('<option/>', {
					text: text,
					val: step,
					selected: is_selected
				}).appendTo($select);
			}
			return $select;
		},

        /**
         * Triggers a "view:refresh" event for this view.
         *
         */
		_refresh: function() {
			this.trigger('view:refresh');
		},

        /**
         * Sets the attribute for a DOM element (jQuery) given the jQuery element
         * attributes as an object.
         *
         * @param {jQuery} $element - The jQuery element
         * @param {Object} attr - The attributes as an object and properties as attribute names
         * @return {jQuery} The element with updated attributes
         */
		_setAttr: function($element, attr) {
			var non_data_attr = ['id', 'title'];
			if ( _.isObject(attr) ) {
				for (var prop in attr) {
					var value = _.isArray(attr[prop]) ? attr[prop].join(' ') : attr[prop];
					if (prop === 'class') {
						$element.addClass(value);
					} else if (prop === 'text') {
						$element.text(value);
					} else {
						var name = non_data_attr.indexOf(prop) > -1 ? prop : 'data-' + prop;
						$element.attr(name, value);
					}
				}
			}
			return $element;
		}
	});

});
