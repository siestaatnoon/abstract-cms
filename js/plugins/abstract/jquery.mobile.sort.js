define([
    'config',
    'jquery',
    'classes/Utils',
    'classes/I18n',
    'plugins/jquery-ui/jquery-ui.min',
    'plugins/abstract/jquery.ui.touch-punch.min'
], function(app, $, Utils, I18n) {
    $(function() {
        var $form = $('.form-sort');
        var $ids =  $('#sort-ids', $form);
        var $sortList =  $('#sort-list', $form);
        var $submit = $('#submit-save', $form);
        var hasItems = false;
        var isReadonly = parseInt( $form.data('readonly') ) === 1;

        $('li', $sortList).each(function (i) {
            hasItems = true;
            return false;
        });

        if ( ! hasItems) {
            $submit.prop('disabled', true);
        }

        if ( ! isReadonly) {
            $sortList.sortable({
                placeholder: 'placeholder',
                stop: function (e, ui) {
                    var aux = [];
                    var $obj = JSON.parse( $ids.val() );
                    $('li', $sortList).each(function (i) {
                        $(this).removeClass('ui-last-child');
                        $(this).find('div.sort-number').text(i + 1);
                        var id = parseInt( $(this).attr('data-id') );
                        aux.push(id);
                    });
                    $ids.val( JSON.stringify(aux) ).trigger('change');
                    $('li:last-child', $sortList).addClass('ui-last-child');
                }
            });

            if ( $sortList.hasClass('sort-grid') === false ) {
                $sortList.sortable('option', 'axis', 'y');
            }
        }

        $('#module-sort-all').on('click', function() {
            var redirect = $(this).data('fragment');
            app.Router.navigate(redirect , {trigger: true});
        });

        if ( parseInt( $form.data('isAll') ) === 1 ) {
            $('#module-sort-all').attr('disabled', true);
        }

        $('.sort-relation').on('change', function() {
            var fragment = $(this).val();
            if (fragment.length) {
                app.Router.navigate(fragment , {trigger: true});
            }
        });

        $submit.on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (isReadonly) {
                return false;
            }

            var data = {
                ids: $ids.val()
            };

            $submit.attr('disabled', true);
            var deferred = $.Deferred(function(defer) {
                $.ajax({
                    url:		$form.attr('action'),
                    data: 		JSON.stringify(data),
                    type: 		'PUT',
                    contentType: 'application/json',
                    dataType: 	'json'
                }).done(function(data) {
                    var error = '';
                    if ( parseInt(data) === 1 ) {
                        var msg = I18n.t('message.sort.saved');
                        Utils.showModalConfirm( I18n.t('message'), msg, false, function() {
                            app.Router.navigate( $('#button-cancel').data('redirect'), {trigger: true});
                        }, false, I18n.t('ok'), I18n.t('exit') );
                    } else if ( parseInt(data) === 0 ) {
                        error = I18n.t('error.sort.saved');
                        Utils.showModalWarning( I18n.t('error'), error);
                    } else if (data.errors) {
                        error = data.errors.join('<br/>');
                        Utils.showModalWarning( I18n.t('error'), error);
                    }
                    $submit.attr('disabled', false);
                }).fail(function(jqXHR) {
                    var resp = Utils.parseJqXHR(jqXHR);
                    var error = resp.errors.length ? resp.errors.join('<br/>') : resp.response;
                    if (error.length === 0) {
                        error = I18n.t('error.general.unknown', 'jquery.mobile.sort');
                    }
                    Utils.showModalWarning( I18n.t('error'), error);
                    isValidForm = false;
                }).then(defer.resolve, defer.reject);
            }).promise();
        });

        $(window).on('page:unload', function() {
            $('body').off('change', '.permissions');
            $('#module-sort-all').off('click');
            $submit.off('click');
            $(this).off('page:unload');
        });
    });
});