<?php

/**
 * fw_breadcrumbs
 *
 * Generates breadcrumb HTML for use with Foundation framework.
 *
 * @param array $bc Array of url => title crumbs
 * @return string The breadcrumbs HTML
 */
if ( ! function_exists('fw_breadcrumbs'))
{
    function fw_breadcrumbs($bc) {
        if ( empty($bc) ) {
            return '';
        }
        $count = 1;
        $html = '<ul class="breadcrumbs">'."\n";
        foreach ($bc as $url => $title) {
            if ( substr($url, 0, 1) !== '/' ) {
                $url = '/'.$url;
            }
            if ( substr($url, 0, strlen(WEB_BASE)) !== WEB_BASE ) {
                $url = WEB_BASE.$url;
            }
            $html .= '  <li';
            if ( $count++ === count($bc) ) {
                $html .= ' class="current"';
            }
            $html .= '><a href="'.$url.'">'.$title.'</a></li>'."\n";
        }
        $html .= '</ul>'."\n";
        return $html;
    }
}

/**
 * fw_navbar
 *
 * Generates the navbar HTML for use with Foundation framework.
 *
 * @param array $nav Array menu items if format label => [label], url => [url]
 * @param bool True to add search field to navigation
 * @return string The navigation HTML
 */
if ( ! function_exists('fw_navbar')) {
    function fw_navbar($nav, $has_search=false) {
        if ( empty($nav) ) {
            return '';
        }

        $get_nav_ul = function($items, $spaces='') use (&$get_nav_ul) {
            if ( empty($items) ) {
                return '';
            }
            $html = $spaces.'    <ul class="dropdown">'."\n";
            $html .= $spaces.'      <li class="divider"></li>'."\n";
            foreach ($items as $item) {
                $html .= $spaces.'      <li><a href="'.$item['url'].'">'.$item['label'].'</a>';
                if ( ! empty($item['items']) ) {
                    $html .= $get_nav_ul($item['items'], '  ');
                }
                $html .= $spaces.'      <li class="divider"></li>'."\n";
            }
            $html .= $spaces."    </ul>\n";
            return $html;
        };

        $html = '<section class="top-bar-section">'."\n";
        $html .= '  <ul class="right">'."\n";
        $html .= '    <li class="divider"></li>'."\n";
        foreach ($nav as $i => $item) {
            $has_items = ! empty($item['items']);
            $class = $i === 0 ? 'home' : '';
            $class .= $has_items ? (empty($class) ? '' : ' ').'has-dropdown' : '';
            $html .= '    <li'.(empty($class) ? '' : ' class="'.$class.'"').'><a href="'.$item['url'].'">'.$item['label'].'</a>';
            if ($has_items) {
                $html .= $get_nav_ul($item['items']).'    ';
            }
            $html .= '</li>'."\n";
            $html .= '    <li class="divider'.($i === 0 ? ' home' : '').'"></li>'."\n";
        }

        if ($has_search) {
            $html .= '    <li class="search has-form">'."\n";
            $html .= '      <div class="row collapse">'."\n";
            $html .= '        <div class="large-9 small-9 columns">'."\n";
            $html .= '          <input type="text" placeholder="Search..." />'."\n";
            $html .= '        </div>'."\n";
            $html .= '        <div class="large-3 small-3 columns">'."\n";
            $html .= '          <a href="#" class="button expand">Go</a>'."\n";
            $html .= '        </div>'."\n";
            $html .= '      </div>'."\n";
            $html .= '    </li>'."\n";
        }

        $html .= "  </ul>\n";
        $html .= "</section>\n";
        return $html;
    }
}

/**
 * fw_pagination
 *
 * Generates pagination HTML for use with Foundation framework.
 *
 * @param int $pagination The pagination configuration
 * @return string The pagination HTML
 */
if ( ! function_exists('fw_pagination'))
{
    function fw_pagination($pagination) {
        $html = '<div class="pagination-centered">'."\n";
        $html .= '  <ul class="pagination">'."\n";

        foreach ($pagination as $link) {
            $class = is_array($link['class']) ? implode(' ', $link['class']) : (empty($link['class']) ? '' : $link['class']);
            $data_page = empty($link['data-page']) ? '' : ' data-page="'.$link['data-page'].'"';
            $title = empty($link['title']) ? '' : ' title="'.$link['title'].'"';
            $html .= '    <li'.(empty($link['li_class']) ? '' : ' class="'.$link['li_class'].'"').'>';
            $html .= '<a href="#" class="'.$class.'"'.$data_page.$title.'>'.$link['label'].'</a></li>'."\n";
        }
        $html .= '  </ul>'."\n";
        $html .= '</div>'."\n";
        return $html;
    }
}


/* End of file template_foundation.php */
/* Location: ./App/Util/template_foundation.php */