define([
	'config',
	'jquery',
	'underscore', 
	'backbone'
], function(app, $, _, Backbone) {
	var AdminTplView = Backbone.View.extend({

		id: app.pageContentId,
		
		blocks: [],
		
		blocksApp: [],
		
		blocksView: [],
		
		contentView: {},
		
		deferred: null,
		
		template: null,
		
		contentId: app.pageContentId,

		events: {

		},

		initialize: function(options) {
			if (this.deferred === null) {
				var self = this;
				var deferred = $.Deferred();
				
				$.ajax({
					url:		app.adminTemplateURL,
					type: 		'GET',
					dataType: 	'json'
				}).done(function(data) {
					self.template = '';
                    if (data.errors) {
                        if(app.debug) {
                            var message = "AdminTplView.initialize: an API error has occurred:\n";
                            message += data.errors.join("\n");
                            console.log(message);
                        }
					} else {
                        self.postInit(data);
					}
					deferred.resolve();
				}).fail(function(jqXHR, status) {
					if (app.debug) {
						console.log('AdminTplView.initialize: data retrieve failed: [' + status + "]\n" + jqXHR.responseText);
					}
				});
				
				this.deferred = deferred.promise();
			} else {
                self.postInit({});
			}
		},

        gotoContentView: function(view) {
            if ( this.onInit(this.gotoContentView, view) === false) {
                return false;
            }

            this._closeContentView();
            var render = view.render();

            if (render.promise) {
                var self = this;
                render.done(function() {
                    self._transitionPage(view);
                });
            } else {
                this._transitionPage(view);
            }
        },

        loading: function(showHide) {
            var task = showHide === 'hide' ? 'hide' : 'show';
            $.mobile.loading(task);
        },

        onInit: function(callback, args) {
            var state = this.deferred.state();
            var is_loaded = true;

            if (state !== 'resolved' ) {
                if (state === 'pending' && _.isFunction(callback) ) {
                    //need to wait until template loaded via ajax
                    var self = this;
                    if ( _.isArray(args) === false) {
                        args = [args];
                    }
                    this.deferred.done(function() {
                        callback.apply(self, args);
                    });
                }
                is_loaded = false;
            }

            return is_loaded;
        },

        postInit: function(data) {
            if ( _.isEmpty(data) === false ) {
                this.useJqm = data.useJqm || this.useJqm;
                this.blocks = data.blocks || this.blocks;
                var template = data.template ? $.trim(data.template) : '';
                if (template.length) {
                    this.template = _.template(template, {});
                }
            }
            this.setEl();

            $(document).on('pageinit', function() {
                $('.jqm-navmenu-link, .jqm-search-link').show();
                $('.jqm-navmenu-panel ul').listview();

                $('body').on('click', '.jqm-navmenu-link', function(e) {
                    $('.jqm-navmenu-panel').panel('open');
                });

                // Initalize search panel list and filter also remove collapsibles
                var searchContents = $( ".jqm-search ul.jqm-list" ).find( "li:not(.ui-collapsible)" );

                $('body').on('click', '.jqm-search-link', function(e) {
                    $('.jqm-search-panel').panel('open');
                });

                $('.jqm-search-panel').on('panelopen', function() {
                    $( this ).find( "input" ).focus();
                });


                $('.jqm-search ul.jqm-list').html(searchContents).listview({
                    inset: false,
                    theme: null,
                    dividerTheme: null,
                    icon: false,
                    autodividers: true,
                    autodividersSelector: function ( li ) {
                        return "";
                    }
                }).filterable();
            });
        },
		
		remove: function() {
			this._closeContentView();
			for (var i=0; i < this.blocksApp.length; i++) {
				this.blocksApp[i].remove();
			}

			this.blocksApp = [];
            this.$el.empty();
            //Backbone.View.prototype.remove.call(this);

			//reset the template in case user logged out 
			//then back in since template removed from DOM
            this.setEl();
            $('.jqm-navmenu-link, .jqm-search-link').hide();
		},

        render: function() {
            if ( this.onInit(this.render, null) === false) {
                return false;
            }

            //blocks only load once
            if (this.blocksApp.length === 0) {
                this._setBlocks(this.blocks, this.blocksApp);
            }

            this.$el.empty();
            //this.$el.appendTo( $(this.contentId) );

            $.mobile.initializePage();
            $('body').enhanceWithin();
            return this;
        },

        setEl: function() {
            if ( $(this.id).length ) {
                this.setElement( $(this.id)[0] );
            } else if (this.template) {
                var $template = $(this.template);
                if ( '#' + $template.attr('id') === this.id ) {
                    this.setElement($template[0]);
                } else if ( $(this.id, $template).length ) {
                    this.setElement( $(this.id, $template)[0] );
                } else {
                    this.tagName = 'div';
                }
            } else {
                var $body = $('body');
                var $el = $('<div/>').attr('id', this.id.substr(1) ).html( $body.html() );
                $body.html('');
                $el.appendTo($body);
                this.setElement($el[0]);
            }
        },
		
		_closeContentView: function() {
		    if ( _.isEmpty(this.contentView) === false ) {
                this.contentView.remove();
                this.contentView = {};
            }

			for (var i=0; i < this.blocksView.length; i++) {
				this.blocksView[i].remove();
			}
			this.blocksView = [];
		},
		
		_setBlocks: function(blocks, storage) {
			if ( _.isEmpty(blocks) ) {
				return false;
			} else if ( $.isPlainObject(blocks) ) {
				blocks = [blocks];
			}

			var validFunctions = ['insertAfter', 'insertBefore', 'appendTo', 'prependTo'];
			for (var i=0; i < blocks.length; i++) {
				var block = blocks[i];
				var $el = $(block['selector']) || $(this.id);
				var pos_func = $.inArray(block['pos_func'], validFunctions) ? block['pos_func'] : validFunctions[0];
				var html = '';
				
				if (block['template']) {
					var data = block['data'] || {};
					html = _.template(block['template'], data);
				} else if (block['html']) {
					html = block['html'];
				} else {
					continue;
				}
				
				$html = $(html);
				$html[pos_func]($el);
				storage.push($html);
			}
		},
		
		_transitionPage: function(view) {
			this.contentView = view;
			this.listenTo(this.contentView, 'view:update:start', this.loading);
			this.listenTo(this.contentView, 'view:update:end', function() { this.loading('hide') } );
			this.$el.append(this.contentView.$el);
			
			if (this.contentView.blocks) {
				this._setBlocks(this.contentView.blocks, this.blocksView);
			}
			
			//want to make sure content loaded to DOM first and
			//JQM enhanced, then load any necessary css/js scripts
			//
			if (this.contentView.loadScripts) {
				this.contentView.loadScripts();
			}

			$.mobile.initializePage();
			$('body').enhanceWithin();
			$(document).trigger('pageinit');
			this.loading('hide');
		}
	});
	
	return AdminTplView;
});
