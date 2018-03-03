define([
    'config',
    'jquery'
], function(app, $) {

    //
    // TODO: retrieve this from, say a data- attribute from body tag
    //
    // breakpoint for mobile hamburger menu
    var TABLET_WIDTH = 1024;

    // Sets the navbar active menu items
    //
    var setActiveMenuItems = function() {
        var selector = '.top-bar-section li';
        $(selector).removeClass('active');
        if ( $('meta[name="data-url"]').length ) {
            var currentUrl = $('meta[name="data-url"]').attr('content');
            var parts = currentUrl.replace(app.docRoot, '').split('/');
            var currentRootUrl = app.docRoot + parts[0];

            $('a[href="' + currentRootUrl + '"]').parents(selector).addClass('active');

            if ( $(selector + ' a[href="' + currentUrl + '"]').length ) {
                $(selector + ' a[href="' + currentUrl + '"]').parent('li').addClass('active');
            }
        }
    };
    // Set to update menu active items after all AJAX calls (best way so far)
    $(document).ajaxComplete(function() {
        setActiveMenuItems();
    });
    // Run it once to initialize
    setActiveMenuItems();

    // vars for condensed navbar conversion
    var $topBar = null;
    var $topBarSearch = null;
    var $menu = null;
    var $divider = null;
    var $liMore = null;
    var $moreUl = null;
    var $headerFill = null;
    var topHeight = $('.header-main').outerHeight();
    var hasDivider = false;
    var hasCondensed = false;
    var smallWindow = false;

    /*
     Condenses the navbar menu by creating a "More" menu item and appending
     top-level menu items until able to fit the condensed, fixed-to-top navbar
     */
    var condenseNavbar = function() {
        if (hasCondensed || smallWindow) {
            return false;
        }
        var navItems = [];
        var navWidth = 0;
        var dividerWidth = 0;
        var dividerSum = 0;
        var $logo = $('.top-bar .title-area');
        var $headerLinks = $('ul.header-links > li').not('.header-search,.no-more');
        var ulWidth = $menu.outerWidth();

        // calc max width for nav items, top bar - logo - search area
        var maxWidth = $('.top-bar').outerWidth() - $logo.outerWidth(true) - $topBarSearch.outerWidth();

        // calc width of "More" nav item to hold menu items that do not fit
        $liMore.appendTo($menu);
        var moreWidth = $liMore.width();
        $liMore.detach();

        // in case menu items will fit within condensed navbar we'll
        // still need the More menu if there are header links
        var addWidth = $headerLinks.length ? moreWidth : 0;

        // calc width of current menu items + border
        $menu.children('li').not('.search').each(function(i, e) {
            $(this).show();
            var width = $(this).outerWidth();
            if ( $(this).hasClass('divider') ) {
                dividerSum += width;
                dividerWidth = width;
                return true;
            }
            var data = {
                el:     e,
                width:  width
            };
            navItems.push(data);
            navWidth += width;
        });

        if ( (navWidth + dividerSum + addWidth) > maxWidth) {
            // current menu items do not fit, create More menu item
            var currWidth = navWidth + dividerSum + moreWidth;
            $moreUl.empty().detach();

            // append links from top header, do not add links with "no-more" class
            $headerLinks.each( function() {
                $moreUl.append( $divider.clone() );
                $(this).clone().removeClass().addClass('header-link').appendTo($moreUl);
            });

            // append items from navbar starting from last
            while (currWidth > maxWidth) {
                var element = navItems.pop();
                var $el = $(element.el).removeClass('hover'); // so opened menu doesn't stay opened
                $moreUl.prepend( $el.clone() );
                $moreUl.prepend( $divider.clone() );
                currWidth -= element.width;
                if (hasDivider) {
                    $el.prev('.divider').remove();
                }
                $el.remove();
            }
            $liMore.append($moreUl);
            var $before = hasDivider ? $topBarSearch.prev('li.divider') : $topBarSearch;
            $liMore.insertBefore($before);
            if (hasDivider) {
                $divider.clone().insertBefore($liMore);
            }

            // if any items added to More menu active, then make this one active too
            if ( $liMore.find('li.active').length ) {
                $liMore.addClass('active');
            }
        }
        hasCondensed = true;
    };

    /*
     Returns items from the More menu back into the navbar menu
     */
    var expandNavbar = function() {
        if ( ! hasCondensed) {
            return false;
        }
        var $before = hasDivider ? $topBarSearch.prev() : $topBarSearch;
        $moreUl.children('li').not('.header-link,.divider').each(function() {
            var $li = $(this).clone();
            if ( $li.hasClass('has-dropdown') ) {
                $li.addClass('no-click');
            }
            $li.insertBefore($before);
            if (hasDivider) {
                $divider.clone().insertBefore($li);
            }
        });
        if (hasDivider) {
            $liMore.prev('li.divider').remove();
        }
        $liMore.removeClass('hover').remove();
        hasCondensed = false;
    };

    var scrollToTop = function() {
        var scroll = $(window).scrollTop();
        if (scroll > 0) {
            window.scrollTo(0, 0);
            expandNavbar();
        }
    };

    var initApp = function() {
        // Startup Foundation
        $(document).foundation();
        $(document).foundation('topbar', 'reflow');

        // unset all script events in case they're bound
        initUnload();

        // need to be sure unbound before binding Backbone.View event again
        app.AppView.off('content:update:end', initApp);

        // calls this function upon successive content load events
        app.AppView.on('content:update:end', initApp);

        // unbound event handlers upon script removal
        $(window).on('page:unload.init', initUnload);

        // vars used in navbar transformation
        $topBar = $('.top-bar-section');
        $topBarSearch = $topBar.find('li.search');
        $menu = $topBar.children('ul');
        $divider = $('<li/>').addClass('divider');
        $liMore = $('<li/>').addClass('more has-dropdown not-click');
        $moreUl = $('<ul/>').addClass('dropdown');
        $headerFill = $('.header-fill');
        hasDivider = $menu.children('li.divider').length > 0;
        $('<a/>').attr('href', '#').text('More').appendTo($liMore);

        // best results if page scrolled to top
        scrollToTop();

        // set the scroll events to convert navbar,
        // note namespacing used to not interfere
        // with other events
        $(window).on('scroll.init', function() {
            // when the user scolls past the header area, this will toggle
            // the condensed navbar, always visible when scrolling past the header,
            // or the regular header plus navbar when scrolling within the header height
            if (this.innerWidth <= TABLET_WIDTH) {
                // this is the breakpoint for the mobile hamburger menu
                // so return navbar to original state
                $('header').removeClass('condensed');
                $headerFill.height($('nav').outerHeight());
                return false;
            }

            var scroll = $(window).scrollTop();
            if (scroll >= topHeight) {
                // show condensed navbar
                $('header').addClass('condensed');
                var headerHeight = $('header').outerHeight();
                $headerFill.height(headerHeight);
                condenseNavbar();
            } else {
                // hide condensed navbar
                $('header').removeClass('condensed');
                $headerFill.height(0);
                expandNavbar();
            }
        }).on('resize.init', function() {
            // if the browser screen gets resized to less than the breakpoint
            // of the mobile hamburger menu, then need to restore navbar to
            // original state (remove More menu)
            if (this.innerWidth <= TABLET_WIDTH) {
                expandNavbar();
            }
            $(window).trigger('scroll.init');
        });

        // in case of page refresh, will show/hide
        // condensed navbar depending on scroll pos
        $(window).trigger('scroll.init');
    };

    var initUnload = function() {
        $(window).off('scroll.init');
        $(window).off('resize.init');
        $(window).off('page:unload.init');
        expandNavbar();
    };

    // run on initial content load
    initApp();

});