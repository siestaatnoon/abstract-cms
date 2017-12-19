define([
	'config',
	'jquery',
	'classes/Utils'
], function(app, $, Utils) {
	$(function() {
		var deferreds = [];

		var setErrors = function(field, errors) {
			if (errors.length) {
				deferreds[field]['errors'] = errors;
			}
		};
		
		var showErrors = function() {
			var errors = [];
			var is_complete = true;
			for (var field in deferreds) {
				var d = deferreds[field];
				if (d['defer'] === false || d['defer'].state() === 'pending' ) {
					is_complete = false;
					break;
				} else if (d['errors'].length) {
					errors = errors.concat(d['errors']);
				}
			}
			
			if (is_complete && errors.length) {
				error_msg = errors.join('<br/><br/>');
				Utils.showModalWarning('Error', error_msg);
			}
		};
		
		$('.field-custom-ajax').each(function() {
			var $custom = $(this);
			var field = $custom.data('field');
            var field_module = $custom.data('module');
            var field =  $custom.data('field');
            var value =  $custom.data('value');
			var module = app.ModuleLoader.getModuleName();
			var params = app.ModuleLoader.getModuleParams();
			var ajax_url = app.adminCustomFieldURL + '/' + module + (params === false ? '' : '/' + params.join('/') );
			var data = {
				module: field_module,
				field: 	field,
				value:	value,
				id: 	$custom.data('id')
			};

			var deferred = $.Deferred(function(defer) {
				$.ajax({
					url:		ajax_url,
					data: 		data,
					type: 		'GET',
					dataType: 	'json'
				}).done(function(data) {
					if (data.html) {
						$custom.removeClass('field-custom-loading').html(data.html);
						$custom.enhanceWithin();
					} else if (data.errors) {
						setErrors(field, data.errors);
					}
				}).fail(function(jqXHR, status, error) {
					var error_msg = 'Custom field not retrieved for module [' + data['module'] + '], field [' ;
					error_msg += data['field'] + '], ID [' + data['id'] + '].';
					setErrors(field, [error_msg]);
				}).then(defer.resolve, defer.reject);
			}).promise();

            deferreds[field] = {
                defer: 	deferred.done(showErrors),
                errors: []
            };
		});
	});
});