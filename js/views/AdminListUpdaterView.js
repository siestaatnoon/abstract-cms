define([
	'config',
	'jquery',
	'underscore', 
	'backbone',
	'classes/Utils'
], function(app, $, _, Backbone, Utils) {
	
	var AdminListUpdaterModel = Backbone.Model.extend({	
		defaults: {
			module: '',
			task:	'',
			ids: 	[]
		},
		
		initialize: function(attr, options) {
			var module = options.module || '';
			this.urlRoot = app.adminBulkUpdateURL + '/' + module;
		}
	});
	
	var AdminListUpdaterView = Backbone.View.extend({

		id: 'abstract-bulk-update',
		
		classAttr: [],
		
		model: {},
		
		module: '',

		events: {
			'change select.bulk-update-selector' : 'confirmTask'
		},
		
		isJqm: false,
		
		$selectAll: {},
		
		$selector: {},
		
		showView: false,
		
		$table: {},
		
		updates: {
			active: {
				label: 'Set active',
				value: 'active',
				inUse: false
			},
			inactive: {
				label: 'Set inactive',
				value: 'inactive',
				inUse: false
			},
			archive: {
				label: 'Archive',
				value: 'archive',
				inUse: false
			},
			unarchive: {
				label: 'Unarchive',
				value: 'unarchive',
				inUse: false
			},
			del: {
				label: 'Delete',
				value: 'delete',
				inUse: false
			}
		},

		initialize: function(options) {
			var options = options || {};
			this.module = options.module;
			this.model = new AdminListUpdaterModel({}, { module: this.module });
			this.collection = options.collection;
			this.isJqm = options.isJqm || false;
			if (options.classAttr) {
				var classAttr = _.isArray(options.classAttr) ? options.classAttr.join(' ') : options.classAttr;
				this.$el.addClass(classAttr);
	    	}
			this.$table = options.table;

			var update = this.collection.bulkUpdate;
			if (update.useActive) {
				this.updates.active.inUse = true;
				this.updates.inactive.inUse = true;
			}
			if (update.useDelete) {
				this.updates.del.inUse = true;
			}
			this.updates.archive.inUse = update.useArchive && ! update.isArchive;
            this.updates.unarchive.inUse = update.useArchive && update.isArchive;
			this.showView = update.useActive || update.useDelete || this.updates.archive.inUse || this.updates.unarchive.inUse;
		},

		confirmTask: function(e) {
			var $select = $(e.target);
			var selected = $select.val();
			if (selected.length === 0){
				return false;
			}
			
			var self = this;
			var label = '';
			var message = '';
			var ids = [];
			$('.bulk-select', this.$table).each(function(i) {
				if ( $(this).is(':checked') ) {
					ids.push( $(this).val() );
				}
			});

			if (ids.length === 0) {
				label = 'Error';
				message = 'Please select one or more items';
				Utils.showModalDialog(label, message, 
					function() {
						$select.prop('selectedIndex', 0);
						if (self.isJqm) {
							$select.selectmenu('refresh');
						}
					}, this);
				return false;
			}
			
			var task = '';
			label = 'Confirm';
			message = ' the selected items?';
			for (var prop in this.updates) {
				var task = this.updates[prop];
				if (task.value === selected) {
					message = task.label + message;
					task = prop;
					break;
				}
			}
			
			Utils.showModalConfirm(label, message, function() {
				self.doTask(task);
				$select.prop('selectedIndex', 0);
				if (self.isJqm) {
					$select.selectmenu('refresh');
				}
			}, function() {
				$select.prop('selectedIndex', 0);
				if (self.isJqm) {
					$select.selectmenu('refresh');
				}
			}, this);
		},
		
		doTask: function(task) {
			if (task.length === 0 || this.updates[task] === undefined) {
				return false;
			}

			var self = this;
			var ids = [];
			this.trigger('view:update:start');
			$('.bulk-select', this.$table).each(function(i) {
				if ( $(this).is(':checked') ) {
					ids.push( $(this).val() );
				}
			});

			var attr = {
				module: this.module,
				task: this.updates[task].value,
				ids: ids
			};
			var action = task === 'del' ? 'deleted' : 'updated';
			this.model.save(attr, {
				type: 'PUT',
				success: function(response) {
					self.trigger('view:update:end');
	            	Utils.showModalDialog('Confirmation', 'The items have ' + action + ' successfully',
	            		function() {
			            	self.trigger('view:refresh');
	            		}
	            	);
	            },
	            error: function () {
	            	self.trigger('view:update:end');
	            	Utils.showModalWarning('Error', 'An error occurred while updating the items');
	            }
			});
		},
		
		remove: function() {
			$('thead tr > th:first-child', this.$table).remove();
			$('tbody tr > td:first-child', this.$table).remove();
			Backbone.View.prototype.remove.call(this);
		},
		
		render: function() {
			if (this.showView) {
				this.$el.empty();
				this._addRowCheckboxes();
				this._addSelector();
				this.delegateEvents();
			} else {
				this._addRowEvents();
			}
			return this;
		},
		
		selectAll: function(e) {
			var self = this;
			var is_checked = $(e.target).is(':checked');
			$('.bulk-select', this.$table).each(function(i) {
				$(this).prop('checked', is_checked);
				if (self.isJqm) {
					$(this).checkboxradio('refresh');
				}
			});
			
			if (this.isJqm) {
				var action = is_checked ? 'enable' : 'disable';
				this.$selector.selectmenu(action);
			} else {
				this.$selector.prop('disabled', ( ! is_checked) );
			}
		},
		
		toggleControls: function(e) {
			var has_all_checked = true;
			var has_checked = false;
			$('.bulk-select', this.$table).each(function(i) {
				if ( $(this).is(':checked') ) {
					has_checked = true;
				} else {
					has_all_checked = false;
				}
			});

			if (this.isJqm) {
				var action = has_all_checked || has_checked ? 'enable' : 'disable';
				this.$selector.selectmenu(action);
			} else {
				this.$selector.prop('disabled', (has_all_checked || has_checked) );
			}
		
			this.$selectAll.prop('checked', has_all_checked);
			if (this.isJqm) {
				this.$selectAll.checkboxradio('refresh');
			}
		},
		
		_addRowCheckboxes: function() {
			var self = this;
			var cellClass = 'bulk-select-cell';
			var spanClass = 'bulk-select-label';
			
			this.$selectAll = $('<input/>', {
				id: 'bulk-select-all',
				type: 'checkbox',
				value: 1
			}).on('click', function(e) {
				self.selectAll(e);
			});
			
			var $span = $('<span/>', {
				text: 'All',
				class: spanClass
			});
			
			var $label = $('<label/>').append(this.$selectAll).append($span);
			var $th = $('<th/>', { class:cellClass }).append($label);
			$('thead > tr', this.$table).prepend($th);
			
			for (var i=0; i < this.collection.length; i++) {
				var index = i + 1;
				var model = this.collection.at(i);
				var $row = $('tbody tr:nth-child(' + index + ')', this.$table);
				var $cb = null;
				
				(function(model) {
					var id = _.isUndefined(model.id) ? model.get(self.collection.idAttribute) : model.id;
					$cb = $('<input/>', {
						type: 'checkbox',
						name: 'ids[]',
						value: id,
						class: 'bulk-select',
					}).on('click', function(e) {
						e.preventDefault();
						e.stopPropagation();
						self.toggleControls(e);
					});
					
					$row.on('mouseenter', function() {
						$(this).addClass('row-hover');
					}).on('mouseleave', function() {
						$(this).removeClass('row-hover');
					}).on('click', function(e) {
						if (e.target.nodeName.toLowerCase() === 'td') {
							self._setActionPopup(model);
						}
					}).attr('data-id', id);
				})(model);
				
				var $span = $('<span/>', {
					text: 'Select',
					class: spanClass
				});
				var $label = $('<label/>').append($cb).append($span);
				var $td = $('<td/>', { class:cellClass }).append($label);
				$row.prepend($td);
			}
		},
		
		_addRowEvents: function() {
			var self = this;

			for (var i=0; i < this.collection.length; i++) {
				var index = i + 1;
				var model = this.collection.at(i);
				var $row = $('tbody tr:nth-child(' + index + ')', this.$table);
				
				(function(model) {
					var id = _.isUndefined(model.id) ? model.get(self.collection.idAttribute) : model.id;
					$row.on('mouseenter', function() {
						$(this).addClass('row-hover');
					}).on('mouseleave', function() {
						$(this).removeClass('row-hover');
					}).on('click', function(e) {
						if (e.target.nodeName.toLowerCase() === 'td') {
							self._setActionPopup(model);
						}
					}).attr('data-id', id);
				})(model);
			}
		},
		
		_addSelector: function() {
			this.$selector = $('<select/>', { 
				class: 'bulk-update-selector',
				disabled: true
			});
			$('<option/>', { text:'With selected items', val:'' }).appendTo(this.$selector);
			for (var prop in this.updates) {
				var task = this.updates[prop];
				if (task.inUse) {
					$('<option/>', { text:task.label, val:task.value }).appendTo(this.$selector);
				}
			}
			this.$el.append(this.$selector);
		},
		
		_setActionPopup: function(model) {
			model = model || {};
			var $popup = $('#list-action-popup');
			var titleField = $popup.data('titleField');
			var title = model.get(titleField) || '';
			var self = this;
			
			$('.model-title', $popup).text(title);
			
			$('.list-action-edit', $popup).on('click', function(e) {
				e.preventDefault();
				var fragment = $(this).data('fragment') + '/' + model.get(self.collection.idAttribute);
				app.Router.navigate(fragment, {trigger: true});
			});
			
			$('.list-action-delete', $popup).on('click', function(e) {
				e.preventDefault();
				$popup.popup('close');
				var message = 'Delete "' + title + '"?';
				
				Utils.showModalConfirm('Confirm', message, function() {
					model.destroy({ 
						wait: true, 
						success: function(model, response) {
                            Utils.showModalDialog('Message', '"' + title + '" has deleted successfully', false);
						}, 
						error: function(model, response) {
						    var error = response.responseJSON.errors ?
                                        response.responseJSON.errors.join('<br/>') :
                                        '"' + title + '" could not be deleted';
							Utils.showModalWarning('Error', error, false);
						}
					});
				}, false, this);
			});
			
			$popup.popup('open');
		}
		
	});
	
	return AdminListUpdaterView;
});
