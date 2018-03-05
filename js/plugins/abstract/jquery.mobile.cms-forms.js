define([
	'config',
	'jquery',
	'classes/Utils'
], function(app, $, Utils) {
	$(function() {
		var hasEdits = false;
		var leaveMsg = 'There are pending changes in this page.';
		
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
			var message = leaveMsg + '<br/><br/>' + 'Are you sure you would like to exit?';
			
			Utils.showModalConfirm('Confirm', message, false, function() {
				app.Router.navigate(href, {trigger: true});
			}, false, 'Stay', 'Exit');

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