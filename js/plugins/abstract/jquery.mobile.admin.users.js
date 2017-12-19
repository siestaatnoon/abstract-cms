define([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'classes/Utils'
], function(app, $, _, Backbone, Utils) {
    $(function() {
        var userCheckUri = 'check_user';
        var permDefault = {
            d: 0,
            a: 0,
            u: 0,
            r: 0
        }
        var isValidForm = true;
        var $form = $('#form-users');
        var $fieldsUserEmail = $('input[name="username"],input[name="email"]', $form);
        var $submit = $('#submit-save', $form);

        $('body').on('change', '.permissions', function() {
            var $perm = $(this);
            var sel = $perm.val();
            var field = $perm.data('field');
            var is_obj = parseInt( $perm.data('object') ) === 1;
            var $input = $('input[name="' + field + (is_obj ? '[]' : '') + '"]', $form);
            var perm = $.extend({}, permDefault);

            if ( $.isArray(sel) && sel.length > 0 ) {
                var has_r = false;
                for (var i=0; i < sel.length; i++) {
                    var s = sel[i];
                    if (s === 'r') {
                        has_r = true;
                    }
                    perm[s] = 1;
                }

                if ( ! has_r) {
                    perm['r'] = 1;
                    $('option[value="r"]', $(this)).prop('selected', true);
                    Utils.refreshJqmField( $(this) );
                }
            }

            var pbin = '';
            for (var p in perm) {
                pbin += perm[p];
            }

            var val = $input.val();
            if (is_obj) {
                var name = $perm.attr('id').replace('permissions-', '');
                var perms = JSON.parse(val);
                if ( $.isPlainObject(perms) === false) {
                    perms = {};
                }
                perms[name] = pbin;
                val = JSON.stringify(perms);
            } else {
                val = pbin;
            }
            $input.val(val);
        });

        $fieldsUserEmail.on('change', function() {
            var $field = $(this);
            var isUsername = $field.attr('name') === 'username';
            var module = app.ModuleLoader.getModuleName();
            var ajaxUrl = app.adminApiRoot + '/' + module + '/' + userCheckUri;
            var val = $field.val();
            var data = {
                user_id: $('input[name="user_id"]').val()
            };
            if (isUsername) {
                val = val.replace(/[ ]/g, '_').replace(/[\W]/g, '').toLowerCase();
                $field.val(val);
                data['username'] = val;
            } else {
                data['email'] = val;
            }

            Utils.removeFieldError($field);
            $submit.attr('disabled', true);
            isValidForm = true;
            var deferred = $.Deferred(function(defer) {
                $.ajax({
                    url:		ajaxUrl,
                    data: 		data,
                    type: 		'GET',
                    dataType: 	'json'
                }).done(function(data) {
                    if ( parseInt(data) === 0 ) {
                        var error = (isUsername ? 'Username' : 'Email') + ' [' + val + '] already in use';
                        Utils.showFieldError($field, error);
                        isValidForm = false;
                    } else if (data.errors) {
                        var error_msg = data.errors.join('<br/><br/>');
                        Utils.showModalWarning('Error', error_msg);
                        isValidForm = false;
                    }
                    $submit.attr('disabled', false);
                }).fail(function(jqXHR, status, error) {
                    var error = 'Warning: ' + (isUsername ? 'username' : 'email') + ' [' + val + '] could not be verified';
                    Utils.showModalWarning('Error', error);
                    isValidForm = false;
                }).then(defer.resolve, defer.reject);
            }).promise();
        });

        $submit.on('click', function(e) {
            if ( ! isValidForm) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        $(window).on('page:unload', function() {
            $('body').off('change', '.permissions');
            $fieldsUserEmail.off('change');
            $submit.off('click');
            $(this).off('page:unload');
        });
    });
});