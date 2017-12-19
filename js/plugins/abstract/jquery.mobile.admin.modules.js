define([
    'config',
    'jquery',
    'underscore',
    'backbone',
    'classes/Utils',
    'plugins/codemirror/codemirror.min',
    'plugins/codemirror/mode/htmlembedded',
    'plugins/codemirror/addon/display/autorefresh',
    'plugins/codemirror/addon/selection/active-line',
    'plugins/codemirror/addon/edit/matchbrackets'
], function(app, $, _, Backbone, Utils, CodeMirror) {
    $(function() {
        var showHideDelay = 300;
        var $formModules = $('#form-modules');
        var $formFormFields = $('#form-form_fields');
        var $pkField = $('input[name="pk_field"]', $formModules);
        var $useSlug = $('[name="use_slug"]', $formModules);
        var $formFields = $('input[name="form_fields[]"]', $formModules);
        var $useModel = $('[name="use_model"]', $formModules);
        var $modulesSubmit = $('#submit-save', $formModules);
        var $ffFieldId = $('input[name="field_id"]', $formFormFields);
        var $ffFieldName = $('input[name="name"]', $formFormFields);
        var $ffIsModel = $('[name="is_model"]', $formFormFields);
        var $ffFieldType = $('select[name="field_type_type"]', $formFormFields);
        var $ffRelationTypeOption = $('option[value="relation"]', $ffFieldType);
        var $ffRelationType = $('select[name="field_type_relation_type"]', $formFormFields);
        var $ffRelationName = $('select[name="field_type_relation_name"]', $formFormFields);
        var $ffIsAjax = $('[name="field_type_is_ajax"]', $formFormFields);
        var $ffIsCustom = $('[name="field_type_is_custom"]', $formFormFields);
        var $ffTemplate = $('[name="field_type_template"]', $formFormFields);
        var $ffDataType = $('select[name="data_type_type"]', $formFormFields);
        var $ffValidationType = $('.validation-field-type', $formFormFields);
        var $ffValueSelector = $('input[name="field_type_value_select"]', $formFormFields);
        var isEdit = $('input[name="id"]', $formModules).val() !== '';
        var moduleName = $('.field-modules', $formModules).val();
        var reservedFields = JSON.parse( $('input[name="reserved_fields"]', $formModules).val() );
        var isValidModuleForm = true;
        var isValidFieldForm = true;
        var widgetId = '#modules-validation';
        var $widget = $(widgetId);
        var $widgetUl = $('.modules-validation-list', $widget);
        var $validation = $('input[name="validation"]', $formFormFields);
        var $widgetSave = $('.modules-validation-save', $widget);
        var $widgetRules = $('.validation-field-rules', $widget);
        var isReadOnly = parseInt( $widget.parents('.field-custom-template').data('readonly') ) === 1;

        var cmRules = CodeMirror.fromTextArea( $widgetRules.get(0), {
            mode:               'application/x-ejs',
            theme:              'night',
            indentUnit:         4,
            smartIndent:        false,
            lineNumbers:        true,
            indentWithTabs:     true,
            lineWrapping:       true,
            styleActiveLine:    true,
            matchBrackets:      true,
            autoRefresh:        true,
            readOnly:           isReadOnly
        });

        var init = function() {
            if (isEdit) {
                $('.field-disable-edit', $formModules).prop('readonly', true);
            }
            slugDisplay();
            modelDisplay(false);
        };

        var dataOptionsDisplay = function() {
            var val = $ffDataType.val();
            var $ct_fields = $('.ct-data-fields', $formFormFields);
            $ct_fields.hide(showHideDelay);

            switch (val) {
                case 'varchar':
                case 'char':
                case 'tinyint':
                case 'int':
                    $('.ct-data-length', $formFormFields).show(showHideDelay);
                    break;
                case 'enum':
                    $('.ct-data-enum', $formFormFields).show(showHideDelay);
                    break;
            }

            resetValues($ct_fields);
        };

        var getFormFieldsArray = function() {
            var val = $formFields.val();
            return val === '' ? [] : JSON.parse(val);
        };

        var getFormFieldsList = function() {
            var ff = getFormFieldsArray();
            var fields = {};
            for (var i=0; i < ff.length; i++) {
                var field = ff[i];
                fields[field.name] = field.label;
            }
            return fields;
        };

        var modelDisplay = function(hasChanged) {
            var $model_ct = $('.ct-model', $formModules);
            var val = parseInt( $useModel.filter(':checked').val() );

            if (val === 1) {
            	if (hasChanged) {
	                $('.field-model-options').each(function() {
	                    if ( $(this).hasClass('ct-model-on') ) {
	                        $(this).val('1');
	                        Utils.refreshJqmField( $(this) );
	                    }
	                });
                }
                $ffRelationTypeOption.prop('disabled', false);
                $model_ct.show(showHideDelay);
            } else {
                $('.field-model-options').each(function() {
                    $(this).val('0');
                    Utils.refreshJqmField( $(this) );
                });
                $ffRelationTypeOption.prop('selected', false).prop('disabled', true);
                $model_ct.hide(showHideDelay);
            }

            if (isEdit) {
                $useModel.checkboxradio().checkboxradio('disable');
                $('<input/>')
                    .attr('type', 'hidden')
                    .attr('name', 'use_model')
                    .val( $useModel.filter(':checked').val() )
                .appendTo( $('.form-hidden', $formModules) );
			}
        };

        var resetValues = function($selector) {
            var $ct_hidden = $selector.not(':visible');
            $ct_hidden.find('input:text,textarea').val('');
            $ct_hidden.find('select:not([data-role="flipswitch"]) option').prop('selected', false);
            $ct_hidden.find('select:not([data-role="flipswitch"])').selectmenu({defaults:true}).selectmenu('refresh');
            $ct_hidden.find('select[data-role="flipswitch"]').val(['0']).flipswitch('refresh');

            $ct_hidden.find('input[type="hidden"]').val('[]');
            $ct_hidden.find('ul.name-value-pairs-list > li').each(function() {
                $(this).remove();
            });
            $ct_hidden.find('ul.widget-values-list > li').each(function() {
                $(this).remove();
            });
        };

        var slugDisplay = function() {
            var $slug_ct = $('.ct-slug', $formModules);
            var val = parseInt( $useSlug.val() );
            if (val === 1) {
                $slug_ct.show(showHideDelay);
            } else {
                var $slug = $slug_ct.find('select[name="slug_field"]');
                $slug.val('');
                Utils.refreshJqmField($slug);
                $slug_ct.hide(showHideDelay);
            }
        };

        var subformInit = function() {
            typeOptionsDisplay(false);
            dataOptionsDisplay();

            if ( $ffFieldId.val() !== '' ) {
                $('.field-disable-edit', $formFormFields).prop('readonly', true);
            }

            if ( parseInt( $ffIsCustom.val() ) === 1 ) {
                $('.ct-custom', $formFormFields).show(showHideDelay);
            }

            if ( parseInt( $ffIsAjax.val() ) === 1 ) {
                $('.ct-custom-html,.ct-custom-template', $formFormFields).hide(showHideDelay);
            } else if ( $ffTemplate.val() !== '' ) {
                $('.ct-custom-html', $formFormFields).hide(showHideDelay);
            }

            if ( ($ffFieldId.val() !== '' &&  parseInt( $useModel.filter(':checked').val() ) === 0) ||
                parseInt( $ffIsModel.val() ) === 0 ) {
                $ffDataType.hide(showHideDelay);
                $('.ct-data-type', $formFormFields).hide(showHideDelay);
            }

            var $relOptions = $('option[data-is-core="1"],option[data-has-1n="1"]', $ffRelationName);
            if ( $ffRelationType.val() === '1:n' ) {
                var $selected = $('option:selected', $ffRelationName);
                if ( $selected.attr('data-is-core') !== undefined || $selected.attr('data-has-1n') !== undefined ) {
                    $ffRelationName.val('');
                }
                $relOptions.prop('readonly', true);
            } else {
                $relOptions.prop('readonly', false);
            }
            $ffRelationName.selectmenu('refresh');

            widgetInit();
            validationOptionsDisplay();
        };

        var typeOptionsDisplay = function(isChange) {
            var multiTypes = ['checkbox', 'file', 'image', 'multiselect', 'name_value_widget', 'relation', 'values_widget'];
            var $ct_fields = $('.ct-type-fields', $formFormFields);
            var $infoHide = $('.ct-info-hide', $formFormFields);
            $ct_fields.hide(showHideDelay);
            $('.ct-ct-attr', $formFormFields).show(showHideDelay);
            var val = $ffFieldType.val();
            if (val === 'info') {
                $infoHide.hide(showHideDelay);
            } else {
                $infoHide.show(showHideDelay);
            }

            if (isChange) {
                $ffValueSelector.prop('checked', false).checkboxradio('refresh');
                $('[name="field_type_is_multiple"]', $formFormFields).val(['0']).flipswitch('refresh').flipswitch('enable');
                $('.ct-field-default', $formFormFields).val('').show(showHideDelay);
                $ffIsModel.val('1').flipswitch('refresh').flipswitch('enable');
            }

            switch (val) {
                case 'hidden':
                    $('.ct-ct-attr', $formFormFields).hide(showHideDelay);
                    $('.ct-hidden', $formFormFields).show(showHideDelay);
                    break;
                case 'image':
                case 'file':
                    $('.ct-upload', $formFormFields).show(showHideDelay);
                    break;
                case 'editor':
                    $('.ct-editor', $formFormFields).show(showHideDelay);
                    break;
                case 'info':
                    $('.ct-info', $formFormFields).show(showHideDelay);
                    $ffIsModel.val('0').flipswitch('refresh').flipswitch('disable');
                    resetValues($infoHide);
                    resetValues($ct_fields);
                    return;
                case 'name_value_widget':
                    $('.ct-widget', $formFormFields).show(showHideDelay);
                    break;
                case 'values_widget':
                    $('.ct-widget:not(.ct-widget-nv)', $formFormFields).show(showHideDelay);
                    break;
                case 'custom':
                    $('.ct-custom', $formFormFields).show(showHideDelay);
                    break;
                case 'relation':
                    $('.ct-relation', $formFormFields).show(showHideDelay);
                    break;
                case 'text':
                case 'textarea':
                    $('.ct-text', $formFormFields).show(showHideDelay);
                    break;
                case 'date':
                    $('.ct-text, .ct-date', $formFormFields).show(showHideDelay);
                    break;
                case 'time':
                    $('.ct-text, .ct-time', $formFormFields).show(showHideDelay);
                    break;
                case 'checkbox':
                case 'radio':
                case 'select':
                case 'multiselect':
                    if (val === 'multiselect') {
                        $('[name="field_type_is_multiple"]', $formFormFields).val(['1']).flipswitch('refresh').flipswitch('disable');
                        $('.ct-select,.ct-multiselect', $formFormFields).show(showHideDelay);
                    } else {
                        $('.ct-' + val, $formFormFields).show(showHideDelay);
                    }
                    $('.ct-type-selector', $formFormFields).show(showHideDelay);
                    typeValuesDisplay();
                    break;
            }

            if ( multiTypes.indexOf(val) !== -1 ) {
                $ffDataType.val('text');
                Utils.refreshJqmField($ffDataType);
                $('[name="default"]', $formFormFields).val('[]');
                $('.ct-field-default,.ct-data-type,.ct-data-fields', $formFormFields).hide(showHideDelay);
            } else if (isChange && parseInt( $useModel.filter(':checked').val() ) == 1 ) {
                $('[name="default"]', $formFormFields).val('');
                $('.ct-field-default,.ct-data-type,.ct-data-fields', $formFormFields).show(showHideDelay);
            }

            resetValues($ct_fields);
        };

        var typeValuesDisplay = function() {
            var val = $('input[name="field_type_value_select"]:checked', $formFormFields).val() || '';
            var $ct_values = $('.ct-values', $formFormFields);
            $ct_values.hide(showHideDelay);

            if (val.length === 0) {
                if ($('input[name="field_type_values[]"]', $formFormFields).val() !== '[]') {
                    val = 'values';
                } else if ($('select[name="field_type_config_file"]', $formFormFields).val() !== '') {
                    val = 'config';
                } else if ($('input[name="field_type_dir"]', $formFormFields).val() !== '') {
                    val = 'dir';
                } else if ($('select[name="field_type_module"]', $formFormFields).val() !== '') {
                    val = 'module';
                }
            }
            if (val.length > 0) {
                $('.ct-type-' + val).show(showHideDelay);
                $ffValueSelector.each(function() {
                    if ( $(this).val() === val ) {
                        if ( $(this).prop('checked') === false ) {
                            $(this).prop('checked', true).checkboxradio('refresh');
                        }
                        return false;
                    }
                });
                resetValues($ct_values);
            }
        };

        var updateFieldList = function() {
            $modulesSubmit.attr('disabled', true);
            var $ff_selector = $('.ct-form-fields select', $formModules);
            var fields = getFormFieldsArray();
            var validTypes = ['countries', 'regions', 'select', 'text'];

            $ff_selector.each(function() {
                var $selector = $(this);
                var $first = $('option:first-child', $selector);
                var val = $selector.val();
                var has_val = false;
                $selector.html('').append($first);
                $.each(fields, function(i, field) {
                    if (validTypes.indexOf(field.field_type_type) !== -1 && parseInt(field.is_model) === 1) {
                        $('<option/>').text(field.label).val(field.name).appendTo($selector);
                        if (field.name === val) {
                            has_val = true;
                        }
                    }
                });
                $selector.val(has_val ? val : '');
                Utils.refreshJqmField($selector);
            });
            $modulesSubmit.attr('disabled', false);
        };

        var updateFieldModuleVals = function() {
            var module = $('#field-modules-name', $formModules).val();
            var module_pk = $pkField.val();
            var fields = getFormFieldsArray();
            var use_model = parseInt( $useModel.filter(':checked').val() );

            if ( isNaN(use_model) ) {
                use_model = 1;
            }
            if ($.isArray(fields) && fields.length > 0) {
                for (var i=0; i < fields.length; i++) {
                    fields[i]['module'] = module;
                    fields[i]['module_pk'] = use_model === 1 ? module_pk : '';
                }
                $formFields.val( JSON.stringify(fields) );
            }
        };

        var validationOptionsDisplay = function() {
            var val = $('option:selected', $ffValidationType).val();
            var $ct_fields = $('.ct-valid-fields', $formFormFields);
            $ct_fields.hide(showHideDelay);

            switch (val) {
                case 'required':
                case 'email':
                case 'natural':
                case 'natural_not_zero':
                case 'strong_password':
                    $('.ct-valid-message', $formFormFields).show(showHideDelay);
                    break;
                case 'min':
                    $('.ct-valid-min', $formFormFields).show(showHideDelay);
                    break;
                case 'max':
                    $('.ct-valid-max', $formFormFields).show(showHideDelay);
                    break;
                case 'custom':
                    $('.ct-valid-custom', $formFormFields).show(showHideDelay);
                    break;
            }

            resetValues($ct_fields);
        };

        var widgetClear = function() {
            widgetResetForm();
            $('li', $widgetUl).each(function() {
                $(this).remove();
            });
        };

        var widgetDeleteRule = function($link) {
            var index = $link.parent('li').attr('data-index');
            var values = widgetGetValues();
            if (values[index] === undefined) {
                return false;
            }
            delete values[index];
            $validation.val( JSON.stringify(values) ).trigger('change');

            $link.off();
            $link.parent('li').fadeOut(500, function() {
                $(this).remove();
                $widgetUl.listview().listview('refresh');
            });

            widgetResetForm();
            widgetEnableForm();
        };

        var widgetDisableForm = function() {
            $('.modules-validation-btn a[href="' + widgetId + '"]').addClass('ui-disabled');
            $('.modules-validation-edit', $widget).addClass('ui-disabled');
            $('.form-control', $widget).prop('readonly',true);
            $('.modules-validation-save', $widget).attr('disabled', true);
            $('.form-group', $widget).hide(showHideDelay);
            cmRules.setOption('readOnly', true);
        };

        var widgetEnableForm = function() {
            $('.modules-validation-btn a[href="' + widgetId + '"]').removeClass('ui-disabled');
            $('.modules-validation-edit', $widget).removeClass('ui-disabled');
            $('.form-control', $widget).prop('readonly', false);
            $('.modules-validation-save', $widget).attr('disabled', false);
            $('.ct-valid-type').show(showHideDelay);
            cmRules.setOption('readOnly', false);
        };

        var widgetGetCM = function() {
            var first = cmRules.firstLine() + 1;
            var last = cmRules.lastLine() - 1;
            var last_line = cmRules.getLine(last);
            return cmRules.getRange({line:first, ch:0}, {line:last, ch:last_line.length});
        }

        var widgetGetValues = function() {
            var values = $validation.val();
            values = values.length && values !== '[]' ? JSON.parse(values) : {};
            return $.isPlainObject(values) ? values : {};
        };

        var widgetGetRuleTypes = function() {
            var types = {};
            $('select.validation-field-type option', $widget).each(function() {
                var val = $(this).val();
                var title = $(this).text();
                if (val.length > 0) {
                    types[val] = title;
                }
            });
            return types;
        };

        var widgetInit = function() {
            var types = widgetGetRuleTypes();
            var values = $validation.val();
            values =  ! values || values === '[]' ? {} : JSON.parse(values);

            $widgetUl.empty();
            for (var name in values) {
                var title = types[name] === undefined ? 'Custom [' + name + ']' : types[name];
                var $li = $('<li/>').attr('data-index', name);
                var $a = $('<a/>').attr('href', widgetId).addClass('modules-validation-edit');
                $('<h3/>').text(title).appendTo($a);
                $a.appendTo($li);

                if ( ! isReadOnly) {
                    $('<a/>')
                        .attr('href', widgetId)
                        .addClass('modules-validation-delete')
                        .text('Delete')
                        .appendTo($li);
                }

                $li.appendTo($widgetUl);
            }

            $widgetUl.listview().listview('refresh');
            var $btn = $('.modules-validation-btn a[href="' + widgetId + '"]');
            $btn.removeClass('ui-icon-carat-u ui-btn-active').addClass('ui-icon-carat-d closed');
            $widget.hide(showHideDelay);
            if ( ( ! isReadOnly || ( isReadOnly && $.isEmptyObject(values) === false) ) && $widget.data('visible') ) {
                $btn.removeClass('ui-icon-carat-d closed').addClass('ui-icon-carat-u ui-btn-active');
                $widget.show(showHideDelay);
            }
            if (isReadOnly) {
                widgetDisableForm();
            }

            widgetSelectRefresh();
        };

        var widgetResetForm = function() {
            Utils.removeAllFieldErrors($widget); //in case leftover from previous edit
            $('.validation-field-custom', $widget).val('');
            $('.validation-field-min', $widget).val('');
            $('.validation-field-max', $widget).val('');
            $('.validation-field-param', $widget).val('');
            $('.validation-field-message', $widget).val('');
            $widgetSave.text( $widgetSave.data('addText') + ' Rule' );
            $('.modules-validation-cancel-cnt', $widget).slideUp(300);
            widgetSetCM("\t");
            $ffValidationType.val('').removeAttr('data-old');
            widgetSelectRefresh();
        };

        var widgetSelectEvent = function() {
            if ($('option:selected', $ffValidationType).val() !== '') {
                Utils.removeFieldError($ffValidationType);
            }
            validationOptionsDisplay();
        };

        var widgetSelectRefresh = function() {
            // An odd quirk when refreshing the validation type select,
            // The first time throws a "prior to initialization" error
            // but after, it doesn't. Solution: call it again in
            // the catch block
            //
            try {
                $ffValidationType.selectmenu().selectmenu('refresh');
            } catch (e) {
                $ffValidationType.selectmenu().selectmenu('refresh');
            }

            // when refreshed, need to reattach event
            $ffValidationType.off('change', widgetSelectEvent);
            $ffValidationType.on('change', widgetSelectEvent);
        };

        var widgetSetCM = function(js) {
            if ( ! js) {
               return;
            }

            var cm_token = '[%SCRIPT%]';
            var cm_default = "function(value, param) {\n" + cm_token + "\n}";
            js = Utils.unescapeSingleQuotes(js);
            js = cm_default.replace(cm_token, js);
            cmRules.setValue(js);
        };

        var widgetSetForm = function(index) {
            if ( ! index) {
                return false;
            }
            var values = widgetGetValues();
            var type = $('option[value="' + index + '"]', $ffValidationType).length ? index : 'custom';
            var rules = values[index];
            $ffValidationType.val(type);
            $ffValidationType.attr('data-old', index);
            widgetSelectRefresh();

            if (typeof rules !== 'boolean') {
                for (var name in rules) {
                    if (typeof rules[name] === 'string') {
                        Utils.unescapeSingleQuotes(rules[name]);
                    }
                }

                if (type === 'custom') {
                    $('.validation-field-custom', $widget).val(index);
                    if (rules['rules'] !== undefined) {
                        widgetSetCM(rules['rules']);
                    }
                }
                if (type === 'min') {
                    $('.validation-field-min', $widget).val(rules['param']);
                }
                if (type === 'max') {
                    $('.validation-field-max', $widget).val(rules['param']);
                }
                if (rules['param'] !== undefined) {
                    $('.validation-field-param', $widget).val(rules['param']);
                }
                if (rules['message'] !== undefined) {
                    $('.validation-field-message', $widget).val(rules['message']);
                }
            }

            Utils.removeAllFieldErrors($widget); //in case leftover from previous edit
            $widgetSave.text( $widgetSave.data('updateText') + ' Rule' );
            $('.modules-validation-cancel-cnt', $widget).slideDown(300);
            widgetEnableForm();
            validationOptionsDisplay();
        };

        var widgetSubmitForm = function() {
            var $type_sel = $('option:selected', $ffValidationType);
            var $custom = $('.validation-field-custom', $widget);
            var $min = $('.validation-field-min', $widget);
            var $max = $('.validation-field-max', $widget);
            var $param = $('.validation-field-param', $widget);
            var $message = $('.validation-field-message', $widget);
            var values = widgetGetValues();
            var val = {};

            //validate fields
            Utils.removeAllFieldErrors($widget);
            var type = $type_sel.val();
            var title = $type_sel.text();
            var old_index = type;
            var has_error = false;

            var validEmpty = function($field) {
                var val = $.trim( $field.val() );
                if (val.length === 0) {
                    Utils.showFieldError($field, 'Required field');
                    has_error = true;
                    return;
                }
                return val;
            }

            var validInt = function($field) {
                var val = $.trim( $field.val() );
                val = parseInt(val);
                if ( isNaN(val) || val <= 0 ) {
                    Utils.showFieldError($field, 'Must be whole number greater than zero');
                    has_error = true;
                    return;
                }
                return val;
            }

            validEmpty($type_sel);
            values[type] = {
                param: null,
                rules: null,
                message: null
            };
            switch (type) {
                case 'custom':
                    var rules = widgetGetCM();
                    if ( $.trim(rules) === '' ) {
                        Utils.showFieldError($widgetRules, 'Required field');
                    }

                    var type_custom = validEmpty($custom);
                    values[type_custom] = values[type];
                    delete values[type];
                    type = type_custom;
                    var param = $.trim( $param.val() );
                    if (param.length > 0) {
                        values[type]['param'] = param;
                    }
                    values[type]['rules'] = rules;
                    values[type]['message'] = validEmpty($message);
                    title += ' [' + type + ']';
                    break;
                case 'min':
                    values[type]['param'] = validInt($min);
                    break;
                case 'max':
                    values[type]['param'] = validInt($max);
                    break;
            }

            if (has_error) {
                return false;
            }
			
			var old_index = $ffValidationType.attr('data-old');
            if (old_index !== type) {
                delete values[old_index];
            }
            $ffValidationType.removeAttr('data-old');
            
            var message = $.trim( $message.val() );
            if (message.length > 0) {
                values[type]['message'] = message;
            }
            for (var name in values[type]) {
                if (values[type][name] === null) {
                    delete values[type][name];
                }
            }
            if ( $.isEmptyObject(values[type]) ) {
                values[type] = true;
            }

            $validation.val( JSON.stringify(values) ).trigger('change');
            widgetUpdateRuleList(title, type, old_index);
            widgetResetForm();
            validationOptionsDisplay();
        };

        var widgetUpdateRuleList = function(title, index, old_index) {
            if ( ! title) {
                return false;
            } else if ( ! old_index) {
                old_index = index;
            }

            var $li = $widgetUl.children('li[data-index="' + old_index + '"]');
            if ( $li.attr('data-index') === undefined) {
                var $li = $('<li/>').attr('data-index', index);
                var $a = $('<a/>').attr('href', widgetId).addClass('modules-validation-edit');
                $('<h3/>').text(title).appendTo($a);
                $a.appendTo($li);
                $('<a/>')
                    .attr('href', widgetId)
                    .addClass('modules-validation-delete')
                    .text('Delete')
                    .appendTo($li);
                $widgetUl.append($li);
            } else {
                $li.find('h3').text(title);
            }

            $widgetUl.listview().listview('refresh');
        };

        $('.field-alphanum').on('blur', function() {
            var $field = $(this);
            var name = $field.attr('name');
            var val = $field.val();
            if (val.length === 0) {
                return false;
            }
            val = val.replace(/[ ]/g, '_').replace(/[\W]/g, '').toLowerCase();
            $field.val(val);

            //update form_fields if module name (slug) or pk field changes
            var id = $field.attr('id');
            if (id === 'field-modules-name' || id === 'field-modules-pk_field') {
                updateFieldModuleVals();
            }
        });

        // Checks if a module name/slug is already in use by "borrowing"
        // the module select list for form fields form and comparing with
        // those values
        //
        $('.field-modules').on('blur', function() {
            var $field = $(this);
            var module = $.trim( $field.val() );
            isValidModuleForm = true;
            Utils.removeFieldError($field);
            if (module.length > 0) {
                $('select[name="field_type_module"] option', $formFormFields).each(function () {
                    if (module === $(this).val() && module !== moduleName) {
                        var error = 'Module [' + module + '] already exists, please enter another name';
                        Utils.showFieldError($field, error);
                        isValidModuleForm = false;
                        return false;
                    }
                });
            }
        });

        $pkField.on('blur', function() {
            var val = $.trim( $(this).val() );
            isValidModuleForm = true;
            Utils.removeFieldError($pkField);
            if (val.length > 0) {
                var formFields = getFormFieldsList();

                // check form fields for duplicate name
                $.each(formFields, function(field, label) {
                    if (val === field) {
                        var name = label.length ? label : field;
                        var error = 'Field name [' + field + '] is already in use for Form Field "' + name + '", change name or delete field';
                        Utils.showFieldError($pkField, error);
                        isValidModuleForm = false;
                        return false;
                    }
                });
            }
        });

        $useSlug.on('change', function() {
            slugDisplay();
        });

        $useModel.on('click', function() {
            modelDisplay(true);
            updateFieldModuleVals();
        });

        $modulesSubmit.on('click', function(e) {
            if ( ! isValidModuleForm) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        $ffFieldName.on('blur', function() {
            var val = $.trim( $(this).val() );
            isValidFieldForm = true;
            Utils.removeFieldError($ffFieldName);
            if (val.length > 0) {
                // check field name is not module primary key field
                if (val === $pkField.val() ) {
                    var error = 'Field name [' + val + '] cannot be the same as the module primary key field';
                    Utils.showFieldError($ffFieldName, error);
                    isValidFieldForm = false;
                } else {
                    // check reserved model fields for duplicate name
                    $.each(reservedFields, function (i, field) {
                        if (val === field) {
                            var error = 'Field name [' + field + '] is a reserved name';
                            Utils.showFieldError($ffFieldName, error);
                            isValidFieldForm = false;
                            return false;
                        }
                    });
                }

                // check form fields for duplicate name
                var formFields = getFormFieldsArray();
                var field_id = parseInt( $ffFieldId.val() );
                $.each(formFields, function(i, ff) {
                    if (field_id !== parseInt(ff.field_id) && val === ff.name) {
                        var error = 'Field name [' + val + '] is already in use';
                        Utils.showFieldError($ffFieldName, error);
                        isValidFieldForm = false;
                        return false;
                    }
                });
            }
        });

        $ffFieldType.on('change', function() {
            typeOptionsDisplay(true);
        });

        $ffValueSelector.on('click', function() {
            typeValuesDisplay();
        });

        $ffRelationType.on('change', function() {
            var $relOptions = $('option[data-is-core="1"],option[data-has-1n="1"]', $ffRelationName);
            var val = $(this).val();
            if (val === '1:n') {
                var $selected = $('option:selected', $ffRelationName);
                if ( $selected.attr('data-is-core') !== undefined || $selected.attr('data-has-1n') !== undefined ) {
                    $ffRelationName.val('');
                }
                $relOptions.prop('readonly', true);
            } else {
                $relOptions.prop('readonly', false);
            }
            $ffRelationName.selectmenu('refresh');
        });

        $ffDataType.on('change', function() {
            dataOptionsDisplay();
        });

        $ffIsCustom.on('change', function() {
            if ( parseInt( $(this).val() ) === 1 ) {
                $('.ct-custom', $formFormFields).show(showHideDelay);
            } else if ( $(this).is(':visible') ) {
                $('.ct-custom', $formFormFields).hide(showHideDelay);
            }
        });

        $ffIsAjax.on('change', function() {
            if ( $(this).is(':hidden') ) {
                return false;
            }
            var $custom = $('.ct-custom-html,.ct-custom-template', $formFormFields);
            var val = parseInt( $(this).val() );
            if (val === 1) {
                $custom.hide(showHideDelay);
                resetValues($custom);
            } else {
                $custom.show(showHideDelay);
            }
        });

        $ffTemplate.on('change', function() {
            var $html = $('.ct-custom-html', $formFormFields);
            if ( $(this).val() !== '' ) {
                $html.hide(showHideDelay);
            } else {
                $html.show(showHideDelay);
            }
        });

        $ffIsModel.on('change', function() {
            if ( $(this).is(':hidden') ) {
                return false;
            }
            var val = parseInt( $(this).val() );
            if (val === 1) {
                $('.ct-data-type', $formFormFields).show(showHideDelay);
                $ffDataType.show(showHideDelay);
            } else {
                $('.ct-data-type', $formFormFields).hide(showHideDelay);
                $ffDataType.val('');
                Utils.refreshJqmField($ffDataType);
                $ffDataType.hide(showHideDelay);
            }
            dataOptionsDisplay();
        });

        $formFormFields.on('subform:init', function() {
            subformInit();
        });

        $formFormFields.on('subform:submit', function() {
            updateFieldList();
            updateFieldModuleVals();
        });

        $formFormFields.on('subform:delete', function() {
            updateFieldList();
        });

        $widgetUl.on('click', '.modules-validation-edit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (isReadOnly) {
				return false;
			}
            var index = $(this).parent('li').data('index');
            widgetSetForm(index);
        });

        $widgetUl.on('click', '.modules-validation-delete', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (isReadOnly) {
				return false;
			}
            $link = $(this);
            var message = 'Delete ' + $link.prev().find('h3').text() + '?';
            Utils.showModalConfirm('Confirm', message, function() {
                widgetDeleteRule($link);
            });
        });

        $('.modules-validation-save').click(function(e) {
            e.preventDefault();
            if (isReadOnly) {
				return false;
			}
            widgetSubmitForm();
        });

        $('.modules-validation-cancel').click(function(e) {
            e.preventDefault();
            if (isReadOnly) {
				return false;
			}
            widgetResetForm();
            validationOptionsDisplay();
        });

        $('.modules-validation-list-open').click(function(e) {
        	if (isReadOnly) {
				return false;
			}
            $link = $(this);
            var id = $link.attr('href');
            if ( $(id).is(':visible') ) {
                $link.removeClass('ui-icon-carat-u').addClass('ui-icon-carat-d closed');
                $(id).slideUp(300);
            } else {
                e.preventDefault();
                $link.removeClass('ui-icon-carat-d closed').addClass('ui-icon-carat-u ui-btn-active');
                $(id).slideDown(300);
            }
        });

        $('.form-panel-save', $formFormFields).on('click', function(e) {
            if ( ! isValidFieldForm) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }

            var val = $('option:selected', $ffIsAjax).val();
            if ( parseInt(val) === 1 ) {
                $('.ct-custom-html', $formFormFields).val('');
            }
            return true;
        });

        $('body').on('widget:reset', '.modules-validation-hidden', function(e) {
            e.stopPropagation();
            widgetClear();
            widgetInit();
        });

        $(window).on('page:unload', function() {
            $('.field-alphanum').off('blur');
            $('.field-modules').off('blur');
            $pkField.off('blur');
            $useSlug.off('change');
            $useModel.off('click');
            $modulesSubmit.off('click');
            $ffFieldName.off('blur');
            $ffFieldType.off('change');
            $ffValueSelector.off('click');
            $ffRelationType.off('change');
            $ffDataType.off('change');
            $ffValidationType.off('change');
            $formFormFields.off('subform:open');
            $formFormFields.off('subform:submit');
            $formFormFields.off('subform:delete');
            $ffIsCustom.off('change');
            $ffIsAjax.off('change');
            $ffTemplate.off('change');
            $ffIsModel.off('change');
            $widgetUl.off('click', '.modules-validation-edit');
            $widgetUl.off('click', '.modules-validation-delete');
            $('.modules-validation-save').off('click');
            $('.modules-validation-cancel').off('click');
            $('.modules-validation-list-open').off('click');
            $('.form-panel-save', $formFormFields).off('click');
            $('body').off('widget:reset');
            cmRules.toTextArea();
            cmRules = null;
            $(this).off('page:unload');
        });

        init();
    });
});