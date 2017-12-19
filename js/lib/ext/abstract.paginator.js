(function (root, factory) {
	if (typeof exports == "object") {
		module.exports = factory(require("underscore"),
						 require("backbone"),
						 require("backgrid"),
						 require("backbone.paginator"));
	} else {
		factory(root._, root.Backbone, root.Backgrid);
	}
}(this, function (_, Backbone, Backgrid) {

  "use strict";

	  /**
	     @class Backgrid.Extension.Paginator
	  */
	var Paginator = Backgrid.Extension.Paginator = Backbone.View.extend({

	    className: "abstract-paginator",

	    events: {
	      "click a.pager-control": "changePage",
	      "click a.pager-selector": "changePage",
	      "change select.pager-selector": "changePage",
	      "change select.pager-size": "setPerPage"
	    },
	    
	    activeClass: 'active',
	    
	    attr: {
			first: {
				class: ['pager-control', 'page-first'],
				title: 'First page'
			},
			prev: {
				class: ['pager-control', 'page-prev'],
				title: 'Previous page'
			},
			page: {
				class: ['pager-selector']
			},
			next: {
				class: ['pager-control', 'page-next'],
				title: 'Next page'
			},
			last: {
				class: ['pager-control', 'page-last'],
				title: 'Last page'
			},
			perPage: {
				class: ['pager-size']
			}
		},
		
		classAttr: [],
		
		disabledClass: 'disabled',
		
		isMobile: false,
		
		listProps: {
			first: { text:'&laquo;', title:'First page' },
			prev: { text:'<', title:'Previous page' },
			next: { text:'>', title:'Next page' },
			last: { text:'&raquo;', title:'Last page' },
		},
		
		pagerType: 'list',
		
		/*
			TODO:
			
			1. isMobile?
			2. UL list pagination option (default)
			3. onRender, onPreRender for collection
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
			
			var $pre = $.parseHTML('Go to page: ');
			var $post = $.parseHTML(' of ' + totalPages);
			var $div = $('<div/>', { class: 'pager-page-cnt' });
			$div.append($pre).append($select).append($post);
			return $div;
		},
		
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
						$link.text(i).attr('title', 'Page ' + i).attr('data-page', i);
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
		
		_perPageSelector: function() {
			var $select = this._setAttr($('<select/>'), this.attr['perPage']);
			var state = this.collection.state;
			var pageSize = state.pageSize;
			var totalRecords = state.totalRecords;
			var steps = [];
			
			if (this.isMobile) {
				steps = [5, 10, 20, 50];
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
				var text = i < steps.length - 1 || this.isMobile ? i + ' per page' : 'View All';
				var is_selected = step === pageSize;
				$('<option/>', {
					text: text,
					val: step,
					selected: is_selected
				}).appendTo($select);
			}
			return $select;
		},
		
		_refresh: function() {
			this.trigger('view:refresh');
		},
		
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

}));
