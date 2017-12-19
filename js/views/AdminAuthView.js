define([
	'config',
	'jquery', 
	'jquerymobile', 
	'underscore',
	'backbone',
	'classes/Utils'
], function(app, $, jquerymobile, _, Backbone, Utils) {
	var AdminAuthView = Backbone.View.extend({

		id: app.pageDynContentId,
		
		$content: {},
		
		auth: null,
		
		pageData: {},
		
		pageRoute: '',
		
		router: null,

		events: {
        	'click #submit-login': 'login'
    	},

		initialize: function(options) {
			this.pageRoute = options.page || 'login';
			this.router = app.Router;
			this.auth = app.Auth;
            this.$content = $(app.pageContentId);
		},
		
		login: function(e) {
			e.preventDefault();
			var $user = $('#form-signin input[name="user"]');
			var $pass = $('#form-signin input[name="pass"]');
			var $login_btn = $('#submit-login');
			var $login_error = $('.login-error');
			var is_remember = $('#form-signin input[name="is_remember"]').is(':checked') ? 1 : 0;
			var errors = [];
			
			$login_btn.blur().attr('disabled', true);
			if ( $.trim( $user.val() ).length === 0 ) {
				errors.push('Please enter a username');
				$user.parent('div').addClass('has-error');
				$user.on('blur', function() {
					if ( $.trim( $(this).val() ).length > 0 ) {
						$(this).parent('div').removeClass('has-error');
					}
				});
			}
			if ( $.trim( $pass.val() ).length === 0 ) {
				errors.push('Please enter a password');
				$pass.parent('div').addClass('has-error');
				$pass.on('blur', function() {
					if ( $.trim( $(this).val() ).length > 0 ) {
						$(this).parent('div').removeClass('has-error');
					}
				});
			}
			if (errors.length > 0) {
				$login_error.html( errors.join('<br/>') ).show();
				$login_btn.attr('disabled', false);
				return false;
			}
			
			$login_error.hide();
			var user = $user.val();
			var pass = $pass.val();
			$pass.val('');
			
			var self = this;
			this.auth.authenticate(user, pass, is_remember).done(
				function(response) {
					if (response.session_active) {
						if (response.blocks) {
                        // sets nav menu, search panel blocks
                            app.AppView.blocks = _.isArray(response.blocks) ? response.blocks : [response.blocks];
						}
						$user.val('').off('blur');
						$pass.off('blur');
						self.remove();
						
						//need to reinitialize main template
						//in case user logged out then back in
						//since template removed from DOM
						//app.AppView.initialize({});

						self.router.navigate(app.adminHomeFrag, {trigger: true, replace: true});
					} else if (response.error) {
						$login_error.html(response.error.text).show();
						$login_btn.attr('disabled', false);
					}
				}
			);
		},

        render: function() {
            $.mobile.loading('show');
            if ( this.pageData[this.pageRoute] ) {
                this._setTemplate( this.pageData[this.pageRoute]['template'] );
                return this;
            }

            var data = this.auth.loadPageData(this.pageRoute);
            if (data.promise) {
                var self = this;
                data.done(function(data) {
                    if (data.fields && data.template) {
                        self.pageData[self.pageRoute] = data;
                        self._setTemplate(data.template);
                    }
                });
                return data.promise();
            }
        },
		
		setPage: function(route) {
			this.pageRoute = route;
		},
		
		_setTemplate: function(template) {
			this.setElement( $(template)[0] );
			this.$content.empty();
			this.$el.appendTo(this.$content);
			$.mobile.initializePage();
			this.$content.enhanceWithin();
            $('.jqm-navmenu-link, .jqm-search-link').hide();
			$.mobile.loading('hide');
		}
	});
	
	return AdminAuthView;
});