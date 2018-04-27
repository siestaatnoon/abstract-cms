define([
	'config',
	'jquery',
	'underscore',
	'backbone',
	'backgrid',
	'backbone.paginator',
	'abstract.paginator',
	'backgrid.textcell',
	'classes/ScriptLoader',
	'classes/Utils',
    'classes/I18n',
	'views/AdminListUpdaterView'
], function(app, $, _, Backbone, Backgrid, BBPaginator, Paginator, TextCell, ScriptLoader, Utils, I18n, AdminListUpdaterView) {
	var AdminListView = Backbone.View.extend({

		id: '#tpl-list-view',
		
		altListTmpl: '',
		
		altListTmplData: {},
		
		blocks: {},
		
		$container: {},
		
		grid: {},
		
		isDefaultListTmpl: true,
		
		/**
		 * The module containing this collection
		 *
		 * @type {String}
		 */
		module: '',
		
		paginator: {},
		
		scripts: {},
		
		$sorter: {},
		
		template: null,
		
		updater: {},

		events: {
			'click #module-filter-submit': 'filterList',
			'click .module-filter-clear': 'clearFilters',
			'keyup .module-filter-field.filter-input': '_toggleFilterDisable',
			'change .module-filter-field.filter-select': '_toggleFilterDisable'
		},

		initialize: function(options) {
			var options = options || {};
			var tpl = options.template || '';
            this.template = _.template(tpl);
			this.module = options.module || '';
			this.blocks = options.blocks || this.blocks;
			this.scripts = options.scripts || {};
			this.altListTmpl = options.altListTmpl || this.altListTmpl;
			this.altListTmplData = options.altListTmplData || this.altListTmplData;
			this.isDefaultListTmpl = ! this.altListTmpl;

			var css = ['ext/backgrid.paginator.css'];
			if (this.scripts.css) {
                this.scripts.css = css.concat(this.scripts.css);
			} else {
                this.scripts['css'] = css;
            }

            var el = this.template({});
			this.setElement( $(el).first() );
			this.$container = this.$el.find('.module-list');
			this.listenTo(this.collection, 'remove', this.render);
		},
		
		clearFilters: function() {
			var self = this;
			$('.module-filter-field').each(function() {
				var $field = $(this);
				var name = $field.attr('name');
				if ( _.isUndefined(name) === false) {
					self.collection.queryParams.filters[name] = '';
					if ( $field.hasClass('filter-select') === true){
						$field.prop('selectedIndex', 0);
						$field.selectmenu('refresh');
					} else {
						$field.val('');
					}
				}
			});
			
			var promise = this.render();
			promise.done(function() {
				$('.module-filter-clear').hide();
			});
		},
		
		filterList: function() {
			var self = this;
			$('.module-filter-field').each(function() {
				var name = $(this).attr('name');

				if ( _.isUndefined(name) === false) {
					self.collection.queryParams.filters[name] = Utils.getVal( $(this) );
				}
			});

			var promise = this.render();
			promise.done(function() {
				$('#module-filter-submit').blur();
				$('.module-filter-clear').show();
			});
		},


		refresh: function() {
			if (this.isDefaultListTmpl) {
                this.stopListening(this.paginator, 'view:refresh', this.refresh);
                this.stopListening(this.paginator, 'view:update:start', this._triggerUpdateStart);
                this.stopListening(this.paginator, 'view:update:end', this._triggerUpdateEnd);
                this.stopListening(this.updater, 'view:refresh', this.render);
                this.stopListening(this.updater, 'view:update:start', this._triggerUpdateStart);
                this.stopListening(this.updater, 'view:update:end', this._triggerUpdateEnd);

                $('h3.grid-header', this.$container).remove();
				this.updater.remove();
				this.$sorter.remove();
				this._setGrid();
			}
			this.delegateEvents();
			this.trigger('view:update:end');
		},
		
		remove: function() {
			$('.module-top-button', this.$el).off('click');

		    if (this.isDefaultListTmpl) {
			    $('#abstract-sort-selector select', this.$el).off('change');
				this.grid.remove();
				this.updater.remove();
				this.paginator.remove();
				if (this.$sorter.length) {
					this.$sorter.remove();
				}
			}
			
			this.$el.empty();
			Backbone.View.prototype.remove.call(this);
			
			//reset the template in case of browse back to page
            var el = this.template({});
			this.setElement( $(el).first(this.id) );
			this.$container = this.$el.find('.module-list');
		},

        render: function() {
            var self = this;
            var deferred = $.Deferred();
            this.trigger('view:update:start');
            this.$container.empty();

            $('.module-top-button', self.$el).on('click', function(e) {
                e.preventDefault();
                app.Router.navigate( $(this).attr('href'), {trigger: true} );
            });

            if (this.isDefaultListTmpl) {
                this.paginator = new Backgrid.Extension.Paginator({
                    collection: 	this.collection,
                    pagerType:		'table',
                    classAttr:		'ui-mini',
                    disabledClass:	'ui-disabled',
                    attr: {
                        first: { class: ['ui-link', 'ui-btn', 'ui-btn-icon-notext', 'ui-icon-angle-double-left'] },
                        prev: { class: ['ui-link', 'ui-btn', 'ui-btn-icon-notext', 'ui-icon-angle-left'] },
                        next: { class: ['ui-link', 'ui-btn', 'ui-btn-icon-notext', 'ui-icon-angle-right'] },
                        last: { class: ['ui-link', 'ui-btn', 'ui-btn-icon-notext', 'ui-icon-angle-double-right'] }
                    }
                });
                this.paginator.off('add', this.paginator.render);
                this.paginator.off('remove', this.paginator.render);
                this.paginator.off('reset', this.paginator.render);
            }

            this.collection.fetch({
                reset: true,
                success: function() {
                    if (self.isDefaultListTmpl) {
                        self.grid = new Backgrid.Grid({
                            columns: self.collection.columns,
                            collection: self.collection,
                            emptyText: I18n.t('message.items.not.found'),
                            presort: function() {
                                self.trigger('view:update:start');
                                self.$container.empty();
                            }
                        });
                        self.grid.off('sort', self.grid.refresh);

                        self.updater = new AdminListUpdaterView({
                            collection: 	self.collection,
                            module:			self.module,
                            table:			self.grid.$el,
                            isJqm:			true,
                            classAttr:		'ui-mini'
                        });

                        self._setGrid();
                    } else {
                        //alternative template used so render with alt data + collection
                        self.altListTmplData = _.extend(self.altListTmplData, {collection: self.collection});
                        var _template = _.template(self.altListTmpl);
                        var template = _template(self.altListTmplData);
                        $(template).appendTo(self.$container);
                        self.$container.enhanceWithin();
                    }

                    self.trigger('view:update:end');
                    deferred.resolveWith(self.collection);
                },
                error: function(collection, response, options) {
                    var resp = Utils.parseJqXHR(response);
                    var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
                    if (error.length === 0) {
                        error = I18n.t('error.general.unknown', 'AdminListView.render()');
                    }
                    Utils.showModalWarning( I18n.t('error'), error);
                    if (app.debug) {
                        console.log( error.replace('<br/>', "\n") );
                    }
                    deferred.resolveWith(self, []);
                }
            });

            return deferred.promise();
        },
		
		_resultsString: function() {
			var state = this.collection.state;
			var total = state.totalRecords;
			var start = ((state.currentPage - 1) * state.pageSize) + 1;
			var end = state.currentPage * state.pageSize;
			if (end > total) {
				end = total;
			}
			return I18n.t('list.results.title', [start, end, total]);
		},
		
		_setGrid: function() {
			if ( ! this.isDefaultListTmpl) {
				return false;
			}
			var $grid = this.grid.render().$el;
			if (this.collection.length === 0) {
			//no items found
				$('thead th', $grid).removeClass('ascending descending').off('click');
				$('<div/>', {id: 'grid'}).append($grid).appendTo(this.$container);
				this.$container.enhanceWithin();
				
				//add JQM styles to backgrid table
				$grid.first('.backgrid')
					.attr('id', 'backgrid')
					.attr('data-role', 'table')
					.addClass('ui-responsive ui-table')
					.table().table('refresh');
				return false;
			}
			
			this.$sorter = this._sortSelector();
			var header = this._resultsString();
			var $pager = this.paginator.render().$el;
			var $updater = this.updater.render().$el;
			var self = this;
			
			this.listenTo(this.paginator, 'view:refresh', this.refresh);
			this.listenTo(this.paginator, 'view:update:start', this._triggerUpdateStart);
			this.listenTo(this.paginator, 'view:update:end', this._triggerUpdateEnd);
			
			this.listenTo(this.updater, 'view:refresh', this.render);
			this.listenTo(this.updater, 'view:update:start', this._triggerUpdateStart);
			this.listenTo(this.updater, 'view:update:end', this._triggerUpdateEnd);
			
			//add asc/desc arrow to sort column and remove from the rest
			var col_class = '.' + this.collection.state.sortKey;
			var sort_class = parseInt(this.collection.state.order) === -1 ? 'ascending' : 'descending';
			$('thead th' + col_class, $grid).addClass(sort_class);
			$('thead th', $grid).not(col_class).removeClass('ascending descending');

			$('<h3/>', {class: 'grid-header'}).text(header).appendTo(this.$container);
			$('<div/>', {id: 'grid'}).append($grid).appendTo(this.$container);
			$updater.appendTo(this.$container);
			this.$sorter.appendTo(this.$container);
			$pager.appendTo(this.$container);
			this.$container.enhanceWithin();

			//add JQM styles to backgrid table
			$grid.first('.backgrid')
				.attr('id', 'backgrid')
				.attr('data-role', 'table')
				.addClass('ui-responsive ui-table')
				.table().table('refresh');
		},
		
		_sortSelector: function() {
			var columns = this.collection.columns;
			var state = this.collection.state;
			var $select = $('<select/>');
			var sort = [ I18n.t('descending'), I18n.t('ascending')];
			var self = this;

			for (var i=0; i < columns.length; i++) {
				var column = columns[i];
				if (column.sortable) {
					var has_key = column.name === state.sortKey;
					for (var j=0; j < sort.length; j++) {
					    var is_selected = has_key && (
					        (j === 0 && state.order === 1) ||
                            (j === 1 && state.order === -1)
                        );
						$('<option/>', {
							text: I18n.t('sort.by') + ': ' + column.label + ' ' + sort[j],
							val: column.name + ':' + sort[j],
							selected: is_selected
						}).appendTo($select);
					}
				}
			}
			
			$select.on('change', function(e) {
				var val = $(this).val();
				var vars = val.split(':');
				self.trigger('view:update:start');
				self.$container.empty();
				self.grid.sort(vars[0], vars[1]);
			});
			return $('<div/>', { id: 'abstract-sort-selector', class: 'ui-mini' }).append($select);
		},
		
		_toggleFilterDisable: function() {
			var is_disable = true;
			$('.module-filter-field', this.$el).each(function() {
				var val = $.trim( $(this).val() );
				if (val.length > 0) {
					is_disable = false;
					return false;
				}
			});

			$('#module-filter-submit', this.$el).prop('disabled', is_disable);
		},

		_triggerUpdateEnd: function() {
            this.trigger('view:update:end');
		},

        _triggerUpdateStart: function() {
            this.trigger('view:update:start');
        }

	});
	
	return AdminListView;
});
