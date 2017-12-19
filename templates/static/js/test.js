define([
    'config',
    'jquery'
], function(app, $) {

    /*

    Some notes on this test script:

    -> Template scripts are loaded by Require.js and best set up as a module as in this script
    -> The page:unload event is called whenever the page changes so best practice is to define
       this and an initialize function that can reinitialize on a page change
    -> The content:update:end event in app.AppView is called after a page change is complete
       and is used to reinitialize this script
    -> This script may be removed from the page and reloaded OR may stay on the page so binding
       and unbinding the events as shown will keep it functioning and without memory links

    */

    var initTest = function() {
        // need to be sure unbound before binding event again
        app.AppView.off('content:update:end', initTest);

        // calls this function upon successive content load events
        app.AppView.on('content:update:end', initTest);

        // note that this click event is namespaced so just
        // this click event can easily be unbound later
        $('body').on('click.test', 'a[href="#"]', function(e) {
            e.preventDefault();
            console.log('"#" link clicked, but this won\'t change the address bar');
        });

        // a page:unload.[namespace] event should be set to
        // unbind events and prevent memory leaks
        $(window).on('page:unload.test', function() {
            $('body').off('click.test', 'a[href="#"]');
            $(window).off('page:unload.test');
        });
    };

    // run once upon initial script load
    initTest();

});