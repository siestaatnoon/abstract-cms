<?php

use App\App;

/**
 * nav_cms_menu
 *
 * Creates the CMS navigation panel and Search panel HTML using jQuery Mobile markup.
 *
 * @param array $menu_data The menu and search panel menu data in an assoc array:<br/></br>
 * <ul>
 * <li>
 * menu_items => array(</br>
 * <ul>
 * <li>label => The page label</li>
 * <li>url => The page URL</li>
 * <ul>
 * </li>
 * <li>search_items => array(</br>
 * <ul>
 * <li>label => The page label</li>
 * <li>url => The page URL</li>
 * <li>keywords => array of keyword terms for a search of this item</li>
 * <ul>
 * </li>
 * <ul>
 * @return array The menu and search panel HTML in an assoc array:<br/></br>
 * <ul>
 * <li>navigation => The navigation menu HTML</li>
 * <li>search => The search panel HTML</li>
 * <ul>
 */
if ( ! function_exists('nav_cms_menu'))
{
    function nav_cms_menu($menu_data) {
        $navigation = '<div data-role="panel" class="jqm-navmenu-panel" data-position="left" data-display="overlay">'."\n";
        $navigation .= '  <ul class="jqm-list ui-alt-icon ui-nodisc-icon">'."\n";

        $search = '<div data-role="panel" class="jqm-search-panel" data-position="right" data-display="overlay">'."\n";
        $search .= '  <div class="jqm-search">'."\n";
        $search .= '    <form class="ui-filterable">'."\n";
        $search .= '      <input id="abstract-search" data-type="search" placeholder="Search...">'."\n";
        $search .= '    </form>'."\n";
        $search .= '    <ul class="jqm-list ui-listview ui-alt-icon ui-nodisc-icon" data-filter-reveal="true" data-input="#abstract-search">'."\n";'';

        if ( ! empty($menu_data['menu_items']) ) {
            $items = $menu_data['menu_items'];
            foreach ($items as $i => $item) {
                if ( ! empty($item['items']) ) {
                    $navigation .= '    <li data-role="collapsible" data-enhanced="true" data-collapsed-icon="carat-d" data-expanded-icon="carat-u" data-iconpos="right" data-inset="false" class="ui-collapsible">'."\n";
                    $navigation .= '      <h3 class="ui-collapsible-heading ui-collapsible-heading-collapsed">'."\n";
                    $navigation .= '        <a href="#" class="ui-btn ui-btn-icon-right ui-icon-carat-d">'.$item['label'];
                    $navigation .= ' <span class="ui-collapsible-heading-status">click to expand contents</span></a>'."\n";
                    $navigation .= '      </h3>'."\n";
                    $navigation .= '      <div class="ui-collapsible-content ui-collapsible-content-collapsed">'."\n";
                    $navigation .= '        <ul>'."\n";

                    foreach ($item['items'] as $li) {
                        $navigation .= '          <li>'."\n";
                        $navigation .= '            <a href="'.$li['url'].'" data-ajax="false">'.$li['label'].'</a>'."\n";
                        $navigation .= '          </li>'."\n";
                    }

                    $navigation .= '        </ul>'."\n";
                    $navigation .= '      </div>'."\n";
                    $navigation .= '    </li>'."\n";
                } else {
                    $navigation .= '    <li'.($i === 0 ? ' data-icon="home"' : '').'>'."\n";
                    $navigation .= '      <a href="'.$item['url'].'" data-ajax="false">'.$item['label'].'</a>'."\n";
                    $navigation .= '    </li>'."\n";
                }
            }
        }

        if ( ! empty($menu_data['search_items']) ) {
            $items = $menu_data['search_items'];
            foreach ($items as $i => $item) {
                if ( ! empty($item['keywords']) ) {
                    $filters = implode(' ', $item['keywords']);
                    $search .= '      <li data-filtertext="'.$filters.'"'.($i === 0 ? ' data-icon="home"' : '').'>'."\n";
                    $search .= '        <a href="'.$item['url'].'" class="ui-btn ui-btn-icon-right ui-icon-carat-r" data-ajax="false">'.$item['label'].'</a>'."\n";
                    $search .= '      </li>'."\n";
                }
            }
        }

        $navigation .= '  </ul>'."\n";
        $navigation .= '</div>'."\n";

        $search .= '    </ul>'."\n";
        $search .= '  </div>'."\n";
        $search .= '</div>'."\n";

        return array(
            'navigation' => $navigation,
            'search' => $search
        );
    }
}

/* End of file navigation.php */
/* Location: ./App/Util/navigation.php */