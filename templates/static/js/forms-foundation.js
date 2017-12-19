define([
    'config',
    'jquery'
], function(app, $) {
    var $view = app.AppView.contentView;
    var $mbClone = null;

    var $messageBox = $('<div/>').addClass('alert-box radius').attr('data-alert', '');
    $('<span/>').appendTo($messageBox);
    $('<a/>').attr('href', '#').addClass('close').html('&times;').on('click.forms', function(e) {
        e.preventDefault();
        var $a = $(this);
        $a.parent('.alert-box').fadeOut(function() {
            $a.off('click.forms');
            $(this).remove();
        });
    }).appendTo($messageBox);

    var clearForm = function(form) {
        if ( ! form || ! form.elements) {
            return;
        }

        var elements = form.elements;
        form.reset();

        for(i=0; i < elements.length; i++) {
            fieldType = elements[i].type.toLowerCase();

            switch(fieldType) {
                case "text":
                case "password":
                case "textarea":
                case "hidden":
                    elements[i].value = "";
                    break;
                case "radio":
                case "checkbox":
                    if (elements[i].checked) {
                        elements[i].checked = false;
                    }
                    break;
                case "select-one":
                case "select-multi":
                    elements[i].selectedIndex = -1;
                    break;
                default:
                    break;
            }
        }
    };

    var disablePage = function(isDisabled) {
        if (isDisabled) {
            $('<div/>').attr('id', 'loading').prependTo( $('#main-content') );
            $('#submit-save').attr('disabled', true);
        } else {
            $('#loading').remove();
            $('#submit-save').attr('disabled', false);
        }
    };

    var hideNotifications = function(form) {
        var $form = $(form);
        if ($mbClone) {
            $mbClone.remove();
        }
        $form.find('.form-alert-error,.form-alert-success').remove();
        $form.find('span.error').remove();
        $form.find('label.error').removeClass('error');
        $form.find('input.error').removeClass('error');
        $form.find('select.error').removeClass('error');
        $form.find('textarea.error').removeClass('error');
    };

    var showError = function(form, errors) {
        if ( ! errors) {
            return false;
        }
        if (typeof errors === 'string') {
            errors = [errors];
        }

        $mbClone = $messageBox.clone(true, true);
        $mbClone.addClass('form-alert-error alert').find('span').html( errors.join('<br/>') );
        $(form).prepend($mbClone);
    };

    var showMessage = function(form, message) {
        if ( ! message) {
            return false;
        }
        if (typeof message === 'string') {
            message = [message];
        }

        $mbClone = $messageBox.clone(true, true);
        $mbClone.addClass('form-alert-success success').find('span').html( message.join('<br/>') );
        $mbClone.prependTo( $(form) );
    };

    $view.on('form:submit:start', function(form) {
        hideNotifications(form);
        disablePage(true);
    });

    $view.on('form:submit:error', function(form, errors) {
        disablePage(false);
        showError(form, errors);
    });

    $view.on('form:submit:success', function(form, response) {
        disablePage(false);
        if ( ! response) {
            return false;
        }

        if (response.message) {
            showMessage(form, response.message);
        }

        if (response.clear_form) {
            clearForm(form);
        }
    });

    $view.on('form:validate:show', function(field, errors) {
        var $field = $(field).addClass('error');
        var $label = $field.parents('label.control-label').addClass('error');
        $('<span/>').addClass('error').html( errors.join('<br/>') ).insertAfter($label);
    });

    $view.on('form:validate:hide', function(field) {
        var $field = $(field).removeClass('error');
        var $label = $field.parents('label.control-label').removeClass('error');
        $label.next('span.error').remove();
    });

    $view.on('form:validate:hideall', function(form) {
        hideNotifications(form);
    });

    $(window).on('page:unload.forms', function() {
        $('.alert-box > a').off('click.forms');
        $view.off('form:submit:start');
        $view.off('form:submit:error');
        $view.off('form:submit:success');
        $view.off('form:validate:show');
        $view.off('form:validate:hide');
        $view.off('form:validate:hideall');
        if ($mbClone) {
            $mbClone.remove();
        }
        $(this).off('page:unload.forms');
    });

});
