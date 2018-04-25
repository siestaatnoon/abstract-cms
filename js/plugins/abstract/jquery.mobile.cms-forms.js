define([
	'config',
	'jquery',
	'classes/Utils',
    'classes/I18n'
], function(app, $, Utils, I18n) {
	$(function() {
		var hasEdits = false;
		var leaveMsg = I18n.t('message.pending.changes');
		
		var activateFormEdits = function($field) {
			var $form = $field.parents('form');
			$('.form-save', $form).addClass('activate');
			hasEdits = true;
		};

		var showLeavingMesssage = function() {
            if (hasEdits) {
                return leaveMsg;
            }
		};
		
		$('body').on('click', "a[href^='/']", function(e) {
			if ( ! hasEdits) {
				return true;
			}
			
			e.preventDefault();	
			e.stopPropagation();
			var href = $(this).attr('href');
			var message = leaveMsg + '<br/><br/>' + I18n.t('message.exit');
			
			Utils.showModalConfirm( I18n.t('confirm'), message, false, function() {
				app.Router.navigate(href, {trigger: true});
			}, false, I18n.t('stay'), I18n.t('exit') );

		});
		
		$('.abstract-form').on('change', ':input', function(e) {
			activateFormEdits( $(this) );
		});
		
		$(window).on('beforeunload', showLeavingMesssage);
		
		$(window).on('page:unload', function() {
			$('.abstract-form:input').off('change', ':input');
			$('body').off('click', "a[href^='/']");
			$(window).off('beforeunload', showLeavingMesssage);
		});
	});
});