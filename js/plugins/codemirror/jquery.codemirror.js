define([
    'config',
    'jquery',
    'plugins/codemirror/codemirror.min',
    'plugins/codemirror/mode/htmlembedded',
    'plugins/codemirror/addon/display/autorefresh',
    'plugins/codemirror/addon/selection/active-line',
    'plugins/codemirror/addon/edit/matchbrackets'
], function(app, $, CodeMirror) {

    var instances = [];

    $('.codemirror').each(function() {
        var $textarea = $(this);
        var is_readonly = $textarea.attr('readonly') !== undefined ||
            $textarea.attr('disabled') !== undefined ||
            parseInt( $textarea.attr('data-readonly') ) === 1;

        var cm = CodeMirror.fromTextArea(this, {
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
            readOnly:           is_readonly
        });

        instances.push(cm);
    });

    $(window).on('page:unload', function() {
        $.each(instances, function(i, inst) {
            inst.toTextArea();
        });
        instances = null;

        $(this).off('page:unload');
    });

});