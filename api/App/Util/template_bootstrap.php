<?php

/**
 * fw_breadcrumbs
 *
 * Generates breadcrumb HTML for use with Bootstrap framework.
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
        $html = '<ol class="breadcrumb">'."\n";
        foreach ($bc as $url => $title) {
            if ( substr($url, 0, 1) !== '/' ) {
                $url = '/'.$url;
            }
            if ( substr($url, 0, strlen(WEB_BASE)) !== WEB_BASE ) {
                $url = WEB_BASE.$url;
            }
            if ( $count++ < count($bc) ) {
                $html .= '<li><a href="'.$url.'">'.$title.'</a>';
            } else {
                $html .= '  <li class="active">'.$title;
            }
            $html .= '</li>'."\n";
        }
        $html .= '</ol>'."\n";
        return $html;
    }
}

/**
 * fw_navbar
 *
 * Generates the navbar HTML for use with Bootstrap framework.
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
            $html = $spaces.'    <ul class="dropdown-menu multi-level">'."\n";
            foreach ($items as $item) {
                $html .= $spaces.'      <li><a href="'.$item['url'].'">'.$item['label'].'</a>';
                if ( ! empty($item['items']) ) {
                    $html .= $get_nav_ul($item['items'], '  ');
                }
            }
            $html .= $spaces."    </ul>\n";
            return $html;
        };

        $html = '<nav class="navbar navbar-default" role="navigation">'."\n";
        $html .= '  <div class="navbar-header">'."\n";
        $html .= '    <button type="button" data-toggle="collapse" data-target="#nav1" class="navbar-toggle">'."\n";
        $html .= '      <span class="sr-only">Toggle Navigation</span>'."\n";
        $html .= '      <span class="icon-bar"></span>'."\n";
        $html .= '      <span class="icon-bar"></span>'."\n";
        $html .= '      <span class="icon-bar"></span>'."\n";
        $html .= '    </button>'."\n";
        $html .= '  </div>'."\n";

        $html .= '  <div id="navigation" class="navbar-collapse collapse">'."\n";
        $html .= '    <ul class="nav navbar-nav">'."\n";
        foreach ($nav as $i => $item) {
            $has_items = ! empty($item['items']);
            $class = $has_items ? ' class="dropdown"' : '';
            $html .= '    <li'.$class.'><a href="'.$item['url'].'" class="dropdown-toggle" data-toggle="dropdown" ';
            $html .= 'data-hover="dropdown">'.$item['label'].'</a>';
            if ($has_items) {
                $html .= $get_nav_ul($item['items']).'    ';
            }
            $html .= '</li>'."\n";
        }

        $html .= "    </ul>\n";
        $html .= "  </div>\n";
        $html .= "</nav>\n";
        return $html;
    }
}

/**
 * fw_pagination
 *
 * Generates pagination HTML for use with Bootstrap framework.
 *
 * @param int $pagination The pagination configuration
 * @return string The pagination HTML
 */
if ( ! function_exists('fw_pagination'))
{
    function fw_pagination($pagination) {
        $html = '<nav class="pagination-centered" aria-label="Pagination">'."\n";
        $html .= '  <ul class="pagination">'."\n";

        foreach ($pagination as $link) {
            $class = is_array($link['class']) ? implode(' ', $link['class']) : (empty($link['class']) ? '' : $link['class']);
            $data_page = empty($link['data-page']) ? '' : ' data-page="'.$link['data-page'].'"';
            $title = empty($link['title']) ? '' : ' title="'.$link['title'].'"';
            $html .= '    <li'.(empty($link['li_class']) ? '' : ' class="'.$link['li_class'].'"').'>';
            $html .= '<a href="#" class="'.$class.'"'.$data_page.$title.'>'.$link['label'].'</a></li>'."\n";
        }
        $html .= '  </ul>'."\n";
        $html .= '</nav>'."\n";
        return $html;
    }
}


/* End of file template_bootstrap.php */
/* Location: ./App/Util/template_bootstrap.php */